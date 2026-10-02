<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceDay;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Setting;
use App\Services\AuditService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AbsenController extends Controller
{
    private function pegawai(): ?Employee
    {
        $u = Auth::user();
        if (! $u || $u->roleName() !== 'PEGAWAI' || ! $u->employee_id) {
            return null;
        }
        $e = Employee::find($u->employee_id);

        return ($e && $e->is_active) ? $e : null;
    }

    private function minJam(): float
    {
        return (float) (Setting::semua()['absen_min_jam_hadir'] ?? 4);
    }

    private function gagal(string $pesan): never
    {
        throw ValidationException::withMessages(['absen' => $pesan]);
    }

    private function kunci(string $kunci): void
    {
        DB::select('select pg_advisory_xact_lock(?)', [(int) crc32($kunci)]);
    }

    /** Hari sudah dibagi atau slip bulan ini sudah terbit: pegawai tidak boleh mengubah. */
    private function periksa(Employee $e, string $t): void
    {
        if (AttendanceDay::where('work_date', $t)->whereNotNull('closed_at')->exists()) {
            $this->gagal('Hari ini sudah dibagi oleh admin. Hubungi admin untuk koreksi.');
        }
        $d = Carbon::parse($t);
        $terbit = Payroll::where('employee_id', $e->id)
            ->where('period_year', $d->year)->where('period_month', $d->month)
            ->whereIn('status', ['FINAL', 'PAID'])->exists();
        if ($terbit) {
            $this->gagal('Slip gaji bulan ini sudah terbit. Hubungi admin untuk koreksi.');
        }
    }

    public function index()
    {
        $e = $this->pegawai();
        $a = $e ? Attendance::where('employee_id', $e->id)->where('work_date', now()->toDateString())->first() : null;

        $pesan = null;
        if (! $e) {
            $pesan = 'Akun ini belum terhubung ke data pegawai yang aktif. Hubungi admin.';
        }

        return view('absen.index', ['pegawai' => $e, 'a' => $a, 'pesan' => $pesan, 'minJam' => $this->minJam(), 'status' => PresensiController::STATUS]);
    }

    public function masuk()
    {
        $e = $this->pegawai();
        abort_unless($e, 403, 'Akun belum terhubung ke data pegawai aktif.');
        $t = now()->toDateString();

        DB::transaction(function () use ($e, $t) {
            $this->kunci('presensi_' . $t);
            $this->periksa($e, $t);

            $a = Attendance::where('employee_id', $e->id)->where('work_date', $t)->first();
            if ($a) {
                $this->gagal($a->clock_in_at ? 'Anda sudah absen masuk hari ini.' : 'Presensi hari ini sudah diisi admin.');
            }

            Attendance::create([
                'employee_id' => $e->id, 'work_date' => $t, 'status' => null,
                'clock_in_at' => now(), 'recorded_by_id' => Auth::id(),
            ]);
            AuditService::catat('CLOCK_IN', 'attendances', $t, null, ['employee_id' => $e->id]);
        });

        return redirect()->route('absen.index')->with('status', 'Absen masuk tercatat pukul ' . now()->format('H:i') . '.');
    }

    public function pulang()
    {
        $e = $this->pegawai();
        abort_unless($e, 403, 'Akun belum terhubung ke data pegawai aktif.');
        $t   = now()->toDateString();
        $min = $this->minJam();
        $hasil = '';

        DB::transaction(function () use ($e, $t, $min, &$hasil) {
            $this->kunci('presensi_' . $t);
            $this->periksa($e, $t);

            $a = Attendance::where('employee_id', $e->id)->where('work_date', $t)->lockForUpdate()->first();
            if (! $a || ! $a->clock_in_at) {
                $this->gagal('Anda belum absen masuk hari ini.');
            }
            if ($a->clock_out_at) {
                $this->gagal('Anda sudah absen pulang hari ini.');
            }

            $jam    = abs($a->clock_in_at->diffInMinutes(now())) / 60;
            $status = $jam >= $min ? 'HADIR' : 'SETENGAH';

            $a->update(['clock_out_at' => now(), 'status' => $status]);
            AuditService::catat('CLOCK_OUT', 'attendances', $t, null, [
                'employee_id' => $e->id, 'jam' => round($jam, 2), 'status' => $status,
            ]);
            $hasil = sprintf('Absen pulang tercatat pukul %s (%s jam). Status: %s.',
                now()->format('H:i'), number_format($jam, 1, ',', '.'), $status === 'HADIR' ? 'Hadir' : 'Setengah hari');
        });

        return redirect()->route('absen.index')->with('status', $hasil);
    }
}