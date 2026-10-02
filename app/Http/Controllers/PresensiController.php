<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HanyaAdmin;
use App\Models\Attendance;
use App\Models\AttendanceDay;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\AuditService;
use App\Services\PoolService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PresensiController extends Controller
{
    use HanyaAdmin;

    public const STATUS = ['HADIR' => 'Hadir', 'SETENGAH' => 'Setengah hari', 'IZIN' => 'Izin', 'SAKIT' => 'Sakit', 'ALFA' => 'Alfa'];

    private function tanggal(?string $s): Carbon
    {
        $hariIni = now()->startOfDay();
        if (! $s || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
            return $hariIni;
        }
        try {
            $t = Carbon::createFromFormat('Y-m-d', $s)->startOfDay();
        } catch (\Throwable $e) {
            return $hariIni;
        }

        return $t->gt($hariIni) ? $hariIni : $t;
    }

    private function terkunci(Carbon $tgl)
    {
        return Payroll::where('period_year', $tgl->year)->where('period_month', $tgl->month)
            ->whereIn('status', ['FINAL', 'PAID'])->pluck('employee_id')->flip();
    }

    private function hariDitutup(string $t): ?AttendanceDay
    {
        return AttendanceDay::where('work_date', $t)->whereNotNull('closed_at')->first();
    }

    public function index(Request $request, PoolService $pool)
    {
        $this->wajibAdmin();
        $tgl = $this->tanggal($request->query('tanggal'));
        $t   = $tgl->toDateString();

        $prod = DB::table('production_batches')->where('production_date', $t)
            ->selectRaw('coalesce(sum(input_birds),0) as e, coalesce(sum(input_weight_kg),0) as k')->first();

        return view('presensi.index', [
            'tgl'             => $tgl,
            'karyawan'        => Employee::where('is_active', true)->orderBy('type')->orderBy('name')->get(),
            'ada'             => Attendance::where('work_date', $t)->get()->keyBy('employee_id'),
            'terkunci'        => $this->terkunci($tgl),
            'prod'            => $prod,
            'status'          => self::STATUS,
            'est'             => $pool->hitung($t),
            'hari'            => $this->hariDitutup($t),
            'pesertaTerkunci' => $pool->pesertaTerkunci($t),
        ]);
    }

    public function store(Request $request, PoolService $pool)
    {
        $this->wajibAdmin();

        $request->validate([
            'tanggal'          => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'rows'             => ['nullable', 'array'],
            'rows.*.status'    => ['nullable', Rule::in(array_keys(self::STATUS))],
            'rows.*.notes'     => ['nullable', 'string', 'max:255'],
        ]);

        $tgl      = $this->tanggal($request->input('tanggal'));
        $t        = $tgl->toDateString();
        $karyawan = Employee::where('is_active', true)->get()->keyBy('id');
        $terkunci = $this->terkunci($tgl);
        $simpan = $hapus = $lewat = 0;
        $info = '';

        DB::transaction(function () use ($request, $tgl, $t, $karyawan, $terkunci, $pool, &$simpan, &$hapus, &$lewat, &$info) {
            $this->kunci('presensi_' . $t);

            $tutup = $this->hariDitutup($t);
            if ($tutup) {
                $kunci = $pool->pesertaTerkunci($t);
                if ($kunci->isNotEmpty()) {
                    throw ValidationException::withMessages(['tanggal' =>
                        'Hari ini sudah dibagi dan slip gaji ' . $kunci->implode(', ') . ' sudah terbit, jadi presensinya tidak bisa diubah.']);
                }
            }

            foreach ((array) $request->input('rows', []) as $id => $r) {
                $e = $karyawan->get($id);
                if (! $e) {
                    continue;
                }
                if ($terkunci->has($id)) {
                    $lewat++;
                    continue;
                }

                $status = $r['status'] ?? null;
                if (! $status) {
                    $hapus += Attendance::where('employee_id', $id)->where('work_date', $t)->whereNull('clock_in_at')->delete();
                    continue;
                }

                Attendance::updateOrCreate(
                    ['employee_id' => $id, 'work_date' => $t],
                    ['status' => $status, 'notes' => $r['notes'] ?? null, 'recorded_by_id' => Auth::id()]
                );
                $simpan++;
            }

            $hasil = null;
            if ($tutup) {
                $hasil = $pool->perbarui($t, (string) Auth::id());
                $info = " Pembagian per kg $hasil.";
            }

            AuditService::catat('SAVE', 'attendances', $t, null, [
                'disimpan' => $simpan, 'dihapus' => $hapus, 'dilewati_terkunci' => $lewat, 'pembagian' => $hasil,
            ]);
        });

        $pesan = "Presensi {$tgl->format('d/m/Y')} tersimpan ($simpan data)." . $info;
        if ($lewat > 0) {
            $pesan .= " $lewat orang dilewati karena slip gajinya sudah terbit.";
        }

        return redirect()->route('presensi.index', ['tanggal' => $t])->with('status', $pesan);
    }

    public function tutup(Request $request, PoolService $pool)
    {
        $this->wajibAdmin();

        $request->validate(['tanggal' => ['required', 'date_format:Y-m-d', 'before_or_equal:today']]);
        $t = $this->tanggal($request->input('tanggal'))->toDateString();

        $h = null;
        DB::transaction(function () use ($t, $pool, &$h) {
            $this->kunci('presensi_' . $t);
            $h = $pool->tutup($t, (string) Auth::id());
            AuditService::catat('CLOSE', 'attendance_days', $t, null, [
                'pool_kg' => $h['pool_kg'], 'pool_rp' => $h['pool_rp'], 'peserta' => $h['peserta'], 'total_poin' => $h['total_poin'],
            ]);
        });

        $pesan = sprintf(
            'Hari %s dibagi: %s kg x Rp %s = Rp %s untuk %d orang.',
            Carbon::parse($t)->format('d/m/Y'),
            number_format($h['pool_kg'], 1, ',', '.'), number_format($h['tarif'], 0, ',', '.'),
            number_format($h['pool_rp'], 0, ',', '.'), $h['peserta']
        );

        return redirect()->route('presensi.index', ['tanggal' => $t])->with('status', $pesan);
    }
}