<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceDay;
use App\Models\Payroll;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pembagian per kg (cara A): berat hasil produksi harian x tarif per kg,
 * dibagi ke semua yang hadir menurut poin (bobot status x bobot tipe).
 */
class PoolService
{
    public function pengaturan(): array
    {
        $s = Setting::semua();

        return [
            'tarif' => (float) ($s['pool_tarif_per_kg'] ?? 1000),
            'bobot' => [
                'KARYAWAN' => (float) ($s['pool_bobot_karyawan'] ?? 1),
                'RELAWAN'  => (float) ($s['pool_bobot_relawan'] ?? 1),
            ],
        ];
    }

    /** Total kg hasil timbang pada tanggal produksi tsb (sumber sama dengan GajiService). */
    public function hasilKg(string $tgl): float
    {
        return round((float) DB::table('production_batches as b')
            ->join('weighing_records as w', 'w.batch_id', '=', 'b.id')
            ->where('b.production_date', $tgl)
            ->sum('w.weight_kg'), 2);
    }

    /** Hitung pembagian dari presensi yang tersimpan (tidak menyimpan apa pun). */
    public function hitung(string $tgl): array
    {
        $set    = $this->pengaturan();
        $poolKg = $this->hasilKg($tgl);
        $poolRp = (int) round($poolKg * $set['tarif']);

        $rows = Attendance::with('employee')->where('work_date', $tgl)
            ->whereIn('status', ['HADIR', 'SETENGAH'])->get();

        $poin = [];
        foreach ($rows as $r) {
            $bs = $r->status === 'SETENGAH' ? 0.5 : 1.0;
            $bt = $set['bobot'][$r->employee->type ?? 'KARYAWAN'] ?? 1.0;
            $poin[$r->employee_id] = $bs * $bt;
        }
        $total = array_sum($poin);

        $bagian = [];
        if ($total > 0 && $poolRp > 0) {
            $lantai = [];
            $pecah  = [];
            foreach ($poin as $id => $pt) {
                $raw = $poolRp * $pt / $total;
                $lantai[$id] = (int) floor($raw);
                $pecah[$id]  = $raw - $lantai[$id];
            }
            $sisa = $poolRp - array_sum($lantai);
            $urut = array_keys($pecah);
            usort($urut, fn ($a, $b) => [-$pecah[$a], $a] <=> [-$pecah[$b], $b]);
            for ($i = 0; $i < $sisa; $i++) {
                $lantai[$urut[$i % count($urut)]]++;
            }
            foreach ($poin as $id => $pt) {
                $bagian[$id] = [
                    'poin' => round($pt, 2),
                    'rp'   => (float) $lantai[$id],
                    'kg'   => $set['tarif'] > 0 ? round($lantai[$id] / $set['tarif'], 3) : 0,
                ];
            }
        }

        return [
            'tgl' => $tgl, 'pool_kg' => $poolKg, 'tarif' => $set['tarif'], 'pool_rp' => $poolRp,
            'total_poin' => round($total, 2), 'peserta' => count($poin), 'bagian' => $bagian,
        ];
    }

    /** Nama peserta hari itu yang slip gaji bulannya sudah FINAL/PAID. */
    public function pesertaTerkunci(string $tgl): Collection
    {
        $t   = Carbon::parse($tgl);
        $ids = Payroll::where('period_year', $t->year)->where('period_month', $t->month)
            ->whereIn('status', ['FINAL', 'PAID'])->pluck('employee_id');

        if ($ids->isEmpty()) {
            return collect();
        }

        return Attendance::with('employee')->where('work_date', $tgl)->whereIn('employee_id', $ids)
            ->where(fn ($q) => $q->whereIn('status', ['HADIR', 'SETENGAH'])->orWhere('poin', '>', 0))
            ->get()->map(fn ($a) => $a->employee->name)->unique()->values();
    }

    private function gagal(string $pesan): never
    {
        throw ValidationException::withMessages(['tanggal' => $pesan]);
    }

    private function simpan(string $tgl, array $h, string $userId): void
    {
        Attendance::where('work_date', $tgl)->update(['poin' => 0, 'kg_bagian' => 0, 'bagian_rp' => 0]);
        foreach ($h['bagian'] as $id => $b) {
            Attendance::where('work_date', $tgl)->where('employee_id', $id)
                ->update(['poin' => $b['poin'], 'kg_bagian' => $b['kg'], 'bagian_rp' => $b['rp']]);
        }

        AttendanceDay::updateOrCreate(['work_date' => $tgl], [
            'pool_kg' => $h['pool_kg'], 'rate_per_kg' => $h['tarif'], 'pool_amount' => $h['pool_rp'],
            'total_poin' => $h['total_poin'], 'participants' => $h['peserta'],
            'closed_at' => now(), 'closed_by_id' => $userId,
        ]);
    }

    /** Tutup hari: bagi dan simpan. Panggil di dalam transaksi. */
    public function tutup(string $tgl, string $userId): array
    {
        $belum = Attendance::with('employee')->where('work_date', $tgl)
            ->whereNotNull('clock_in_at')->whereNull('status')->get();
        if ($belum->isNotEmpty()) {
            $this->gagal('Belum absen pulang: ' . $belum->map(fn ($a) => $a->employee->name)->implode(', ') . '. Isi statusnya di lembar presensi, lalu bagi lagi.');
        }

        $kunci = $this->pesertaTerkunci($tgl);
        if ($kunci->isNotEmpty()) {
            $this->gagal('Tidak bisa dibagi atau dihitung ulang: slip gaji ' . $kunci->implode(', ') . ' sudah terbit.');
        }

        $h = $this->hitung($tgl);
        if ($h['peserta'] === 0) {
            $this->gagal('Belum ada yang berstatus Hadir atau Setengah hari pada tanggal ini.');
        }
        if ($h['pool_kg'] <= 0) {
            $this->gagal('Hasil produksi tanggal ini belum diinput, jadi belum ada yang dibagi.');
        }

        $this->simpan($tgl, $h, $userId);

        return $h;
    }

    /** Hari sudah ditutup lalu presensi berubah: hitung ulang, atau buka kembali bila tidak ada lagi yang dibagi. */
    public function perbarui(string $tgl, string $userId): string
    {
        $h = $this->hitung($tgl);
        if ($h['peserta'] === 0 || $h['pool_kg'] <= 0) {
            Attendance::where('work_date', $tgl)->update(['poin' => 0, 'kg_bagian' => 0, 'bagian_rp' => 0]);
            AttendanceDay::where('work_date', $tgl)->delete();

            return 'dibuka kembali (tidak ada peserta atau hasil produksi)';
        }
        $this->simpan($tgl, $h, $userId);

        return 'dihitung ulang';
    }
}