<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceDay;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Payroll;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class GajiService
{
    /** Tarif pembagian per kg (Rp) dari pengaturan. */
    public function tarifPool(): float
    {
        return (float) (Setting::semua()['pool_tarif_per_kg'] ?? 1000);
    }

    /** Bonus kuota tetap per bulan untuk semua karyawan dan relawan. */
    public function bonusKuota(): float
    {
        return (float) (Setting::semua()['bonus_kuota_bulanan'] ?? 50000);
    }

    /** Ringkasan presensi satu karyawan pada satu bulan. */
    public function hitungPresensi(string $employeeId, int $tahun, int $bulan): array
    {
        $awal  = Carbon::create($tahun, $bulan, 1);
        $akhir = $awal->copy()->endOfMonth()->toDateString();

        $rows = Attendance::where('employee_id', $employeeId)
            ->whereBetween('work_date', [$awal->toDateString(), $akhir])
            ->get(['work_date', 'status', 'kg_bagian', 'bagian_rp']);

        $ditutup = AttendanceDay::whereBetween('work_date', [$awal->toDateString(), $akhir])
            ->whereNotNull('closed_at')->get(['work_date'])
            ->map(fn ($d) => $d->work_date->toDateString())->flip();

        $belum = $rows->whereIn('status', ['HADIR', 'SETENGAH'])
            ->filter(fn ($r) => ! $ditutup->has($r->work_date->toDateString()))->count();

        $n = fn (string $s) => $rows->where('status', $s)->count();
        $c = ['hadir' => $n('HADIR'), 'setengah' => $n('SETENGAH'), 'izin' => $n('IZIN'), 'sakit' => $n('SAKIT'), 'alfa' => $n('ALFA')];

        return [
            'days'         => $c['hadir'] + $c['setengah'] * 0.5,
            'kg'           => round((float) $rows->sum('kg_bagian'), 2),
            'rp'           => round((float) $rows->sum('bagian_rp'), 2),
            'belum_dibagi' => $belum,
            'counts'       => $c,
        ];
    }

    /** Isi komponen gaji dari presensi + tarif karyawan saat ini. */
    public function terapkan(Payroll $p, Employee $e, array $h): void
    {
        $p->days_present       = $h['days'];
        $p->daily_rate         = (float) $e->daily_rate;
        $p->base_amount        = round($h['days'] * (float) $e->daily_rate, 2);
        $p->kg_total           = $h['kg'];
        $p->per_kg_rate        = $h['kg'] > 0 ? round($h['rp'] / $h['kg'], 2) : $this->tarifPool();
        $p->kg_amount          = round($h['rp'], 2);
        $p->attendance_counts  = $h['counts'];
        // Bonus rendemen tidak dipakai lagi: digantikan pembagian per kg.
        $p->rendemen_bonus_kg     = 0;
        $p->rendemen_bonus_amount = 0;
        $this->hitungTotal($p);
    }

    public function hitungTotal(Payroll $p): void
    {
        $gross = round((float) $p->base_amount + (float) $p->kg_amount + (float) $p->bonus_amount + (float) $p->rendemen_bonus_amount, 2);
        $p->gross_amount = $gross;
        $p->net_amount   = round($gross - (float) $p->other_deduction_amount - (float) $p->loan_deduction_amount, 2);
    }

    /**
     * (Tidak dipanggil lagi) Bonus rendemen relawan versi lama. Dibiarkan agar riwayat tetap terbaca.
     */
    public function hitungBonusRendemen(Employee $e, int $tahun, int $bulan): array
    {
        $nol = ['kg' => 0.0, 'amount' => 0.0];
        if ($e->type !== 'RELAWAN') {
            return $nol;
        }

        $set    = Setting::semua();
        $tarif  = (float) ($set['bonus_rendemen_per_kg'] ?? 1000);
        $ambang = (float) ($set['bonus_rendemen_min'] ?? 0);
        if ($tarif <= 0) {
            return $nol;
        }

        $awal  = Carbon::create($tahun, $bulan, 1);
        $akhir = $awal->copy()->endOfMonth()->toDateString();

        $perBatch = DB::table('production_batches as b')
            ->leftJoin('weighing_records as w', 'w.batch_id', '=', 'b.id')
            ->whereBetween('b.production_date', [$awal->toDateString(), $akhir])
            ->groupBy('b.id', 'b.production_date', 'b.input_weight_kg')
            ->selectRaw('b.production_date as d, b.input_weight_kg as masuk, coalesce(sum(w.weight_kg), 0) as hasil');

        $harian = DB::query()->fromSub($perBatch, 'x')
            ->groupBy('d')
            ->selectRaw('d, sum(masuk) as masuk, sum(hasil) as hasil')
            ->get()->keyBy(fn ($r) => substr((string) $r->d, 0, 10));

        $kg = 0.0;
        $rows = Attendance::where('employee_id', $e->id)
            ->whereIn('status', ['HADIR', 'SETENGAH'])
            ->where('kg_cut', '>', 0)
            ->whereBetween('work_date', [$awal->toDateString(), $akhir])
            ->get(['work_date', 'kg_cut']);

        foreach ($rows as $r) {
            $h = $harian->get($r->work_date->toDateString());
            if (! $h || (float) $h->masuk <= 0) {
                continue;
            }
            if ((float) $h->hasil / (float) $h->masuk * 100 >= $ambang) {
                $kg += (float) $r->kg_cut;
            }
        }

        return ['kg' => round($kg, 2), 'amount' => round($kg * $tarif, 2)];
    }

    /** Satu baris pengeluaran per bulan (total gaji dibayar), supaya gaji per orang tidak terlihat di menu Pengeluaran. */
    public function sinkronPengeluaran(int $tahun, int $bulan): void
    {
        $ket = sprintf('Penggajian %04d-%02d (otomatis dari modul Gaji)', $tahun, $bulan);
        $q   = Payroll::where('period_year', $tahun)->where('period_month', $bulan)->where('status', 'PAID');
        $total = round((float) $q->sum('net_amount'), 2);
        $exp = Expense::where('category', 'Upah / Gaji')->where('description', $ket)->first();

        if ($total <= 0) {
            $exp?->delete();
            return;
        }

        $tgl = Carbon::parse($q->max('paid_at'))->timezone(config('app.timezone'))->toDateString();

        if ($exp) {
            $exp->update(['amount' => $total, 'expense_date' => $tgl]);
        } else {
            Expense::create([
                'expense_date' => $tgl, 'category' => 'Upah / Gaji', 'amount' => $total,
                'description' => $ket, 'recorded_by_id' => Auth::id(),
            ]);
        }
    }

    public function kodeVerifikasi(Payroll $p): string
    {
        $data = $p->payroll_number . '|' . $p->employee_id . '|' . number_format((float) $p->net_amount, 2, '.', '');

        return strtoupper(substr(hash_hmac('sha256', $data, (string) config('app.key')), 0, 10));
    }

    public static function terbilang(int $n): string
    {
        return $n === 0 ? 'nol' : trim(self::eja($n));
    }

    private static function eja(int $n): string
    {
        $s = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
        $sisa = fn (int $x, int $b) => $x % $b ? ' ' . self::eja($x % $b) : '';

        if ($n < 12) return $s[$n];
        if ($n < 20) return self::eja($n - 10) . ' belas';
        if ($n < 100) return self::eja(intdiv($n, 10)) . ' puluh' . $sisa($n, 10);
        if ($n < 200) return 'seratus' . ($n > 100 ? ' ' . self::eja($n - 100) : '');
        if ($n < 1000) return self::eja(intdiv($n, 100)) . ' ratus' . $sisa($n, 100);
        if ($n < 2000) return 'seribu' . ($n > 1000 ? ' ' . self::eja($n - 1000) : '');
        if ($n < 1000000) return self::eja(intdiv($n, 1000)) . ' ribu' . $sisa($n, 1000);
        if ($n < 1000000000) return self::eja(intdiv($n, 1000000)) . ' juta' . $sisa($n, 1000000);

        return self::eja(intdiv($n, 1000000000)) . ' miliar' . $sisa($n, 1000000000);
    }
}