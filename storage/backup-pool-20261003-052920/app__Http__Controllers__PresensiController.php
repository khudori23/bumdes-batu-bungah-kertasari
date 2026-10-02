<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HanyaAdmin;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

    public function index(Request $request)
    {
        $this->wajibAdmin();
        $tgl = $this->tanggal($request->query('tanggal'));

        $prod = DB::table('production_batches')->where('production_date', $tgl->toDateString())
            ->selectRaw('coalesce(sum(input_birds),0) as e, coalesce(sum(input_weight_kg),0) as k')->first();

        return view('presensi.index', [
            'tgl'      => $tgl,
            'karyawan' => Employee::where('is_active', true)->orderBy('type')->orderBy('name')->get(),
            'ada'      => Attendance::where('work_date', $tgl->toDateString())->get()->keyBy('employee_id'),
            'terkunci' => $this->terkunci($tgl),
            'prod'     => $prod,
            'status'   => self::STATUS,
        ]);
    }

    public function store(Request $request)
    {
        $this->wajibAdmin();

        $request->validate([
            'tanggal'          => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'rows'             => ['nullable', 'array'],
            'rows.*.status'    => ['nullable', Rule::in(array_keys(self::STATUS))],
            'rows.*.kg_cut'    => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'rows.*.notes'     => ['nullable', 'string', 'max:255'],
        ]);

        $tgl      = $this->tanggal($request->input('tanggal'));
        $karyawan = Employee::where('is_active', true)->get()->keyBy('id');
        $terkunci = $this->terkunci($tgl);
        $simpan = $hapus = $lewat = 0;

        DB::transaction(function () use ($request, $tgl, $karyawan, $terkunci, &$simpan, &$hapus, &$lewat) {
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
                    $hapus += Attendance::where('employee_id', $id)->where('work_date', $tgl->toDateString())->delete();
                    continue;
                }

                $kg = ((float) $e->per_kg_rate > 0 && in_array($status, ['HADIR', 'SETENGAH'], true))
                    ? round((float) ($r['kg_cut'] ?? 0), 2) : 0;

                Attendance::updateOrCreate(
                    ['employee_id' => $id, 'work_date' => $tgl->toDateString()],
                    ['status' => $status, 'kg_cut' => $kg, 'notes' => $r['notes'] ?? null, 'recorded_by_id' => Auth::id()]
                );
                $simpan++;
            }

            AuditService::catat('SAVE', 'attendances', $tgl->toDateString(), null, ['disimpan' => $simpan, 'dihapus' => $hapus, 'dilewati_terkunci' => $lewat]);
        });

        $pesan = "Presensi {$tgl->format('d/m/Y')} tersimpan ($simpan data).";
        if ($lewat > 0) {
            $pesan .= " $lewat orang dilewati karena slip gajinya sudah terbit.";
        }

        return redirect()->route('presensi.index', ['tanggal' => $tgl->toDateString()])->with('status', $pesan);
    }
}