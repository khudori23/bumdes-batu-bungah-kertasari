<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Setting;
use App\Services\GajiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class BonusRendemenTest extends TestCase
{
    use RefreshDatabase;

    private GajiService $svc;

    /** Pengaman: dipanggil SEBELUM migrate:fresh. Jangan pernah menyentuh database asli. */
    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDatabaseName() !== 'bumdesa_test') {
            throw new \RuntimeException('Test harus memakai database bumdesa_test (cek .env.testing). Dibatalkan agar data asli aman.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Lewati pengecekan foreign key supaya data dummy tidak perlu membuat user/produk/lokasi.
        DB::statement('SET session_replication_role = replica');

        $this->svc = new GajiService();
        Setting::simpan(['bonus_rendemen_per_kg' => '1000', 'bonus_rendemen_min' => '0']);
    }

    /* ---------- helper data dummy ---------- */

    private function karyawan(string $type = 'RELAWAN', float $harian = 0, float $perKg = 1500, string $nama = 'Dummy'): Employee
    {
        return Employee::create([
            'code' => 'E-' . Str::upper(Str::random(8)), 'name' => $nama, 'type' => $type, 'daily_rate' => $harian,
            'per_kg_rate' => $perKg, 'is_active' => true,
        ]);
    }

    private function presensi(Employee $e, string $tgl, float $kg, string $status = 'HADIR'): void
    {
        Attendance::create(['recorded_by_id' => (string) Str::uuid(), 'employee_id' => $e->id, 'work_date' => $tgl, 'status' => $status, 'kg_cut' => $kg]);
    }

    private function batch(string $tgl, float $masukKg, float $hasilKg): void
    {
        $id = (string) Str::uuid();
        $now = now();

        DB::table('production_batches')->insert([
            'id' => $id, 'batch_number' => 'B-' . Str::random(8), 'production_date' => $tgl,
            'input_birds' => 100, 'input_weight_kg' => $masukKg, 'status' => 'DONE',
            'created_by_id' => (string) Str::uuid(), 'created_at' => $now, 'updated_at' => $now,
        ]);

        $w = [
            'id' => (string) Str::uuid(), 'receipt_number' => 'W-' . Str::random(8), 'batch_id' => $id,
            'product_id' => (string) Str::uuid(), 'weight_kg' => $hasilKg,
            'operator_id' => (string) Str::uuid(), 'created_at' => $now, 'updated_at' => $now,
        ];
        if (Schema::hasColumn('weighing_records', 'storage_location_id')) {
            $w['storage_location_id'] = (string) Str::uuid();
        }
        DB::table('weighing_records')->insert($w);
    }

    private function bonus(Employee $e, int $bulan = 10): array
    {
        return $this->svc->hitungBonusRendemen($e, 2026, $bulan);
    }

    /* ---------- test ---------- */

    public function test_bonus_seribu_per_kg_tanpa_ambang(): void
    {
        $e = $this->karyawan();
        $this->batch('2026-10-05', 100, 72);
        $this->presensi($e, '2026-10-05', 40);

        $b = $this->bonus($e);
        $this->assertEqualsWithDelta(40.0, $b['kg'], 0.001);
        $this->assertEqualsWithDelta(40000.0, $b['amount'], 0.001);
    }

    public function test_ambang_70_persen_hanya_hari_yang_lolos(): void
    {
        Setting::simpan(['bonus_rendemen_min' => '70']);
        $e = $this->karyawan();

        $this->batch('2026-10-05', 100, 72); // 72% lolos
        $this->batch('2026-10-06', 100, 60); // 60% tidak lolos
        $this->presensi($e, '2026-10-05', 30);
        $this->presensi($e, '2026-10-06', 50);

        $b = $this->bonus($e);
        $this->assertEqualsWithDelta(30.0, $b['kg'], 0.001);
        $this->assertEqualsWithDelta(30000.0, $b['amount'], 0.001);
    }

    public function test_karyawan_harian_tidak_dapat_bonus(): void
    {
        $e = $this->karyawan('KARYAWAN', 70000, 0);
        $this->batch('2026-10-05', 100, 72);
        $this->presensi($e, '2026-10-05', 40);

        $this->assertEqualsWithDelta(0.0, $this->bonus($e)['amount'], 0.001);
    }

    public function test_hari_tanpa_batch_tidak_dapat_bonus(): void
    {
        $e = $this->karyawan();
        $this->presensi($e, '2026-10-07', 20);

        $this->assertEqualsWithDelta(0.0, $this->bonus($e)['amount'], 0.001);
    }

    public function test_dua_batch_di_hari_yang_sama_digabung(): void
    {
        Setting::simpan(['bonus_rendemen_min' => '70']);
        $e = $this->karyawan();

        $this->batch('2026-10-08', 100, 80);
        $this->batch('2026-10-08', 100, 60); // gabungan 140/200 = 70% -> lolos (>=)
        $this->presensi($e, '2026-10-08', 10);

        $this->assertEqualsWithDelta(10000.0, $this->bonus($e)['amount'], 0.001);
    }

    public function test_tiga_belas_relawan_dalam_satu_batch(): void
    {
        $this->batch('2026-10-09', 200, 150);

        $total = 0.0;
        for ($i = 1; $i <= 13; $i++) {
            $e = $this->karyawan('RELAWAN', 0, 1500, "Relawan $i");
            $this->presensi($e, '2026-10-09', 10 + $i);

            $b = $this->bonus($e);
            $this->assertEqualsWithDelta((10 + $i) * 1000.0, $b['amount'], 0.001);
            $total += $b['amount'];
        }

        $this->assertEqualsWithDelta(221000.0, $total, 0.001);
    }

    public function test_status_alfa_tidak_dihitung(): void
    {
        $e = $this->karyawan();
        $this->batch('2026-10-05', 100, 72);
        $this->presensi($e, '2026-10-05', 10, 'ALFA');

        $this->assertEqualsWithDelta(0.0, $this->bonus($e)['amount'], 0.001);
    }

    public function test_bulan_lain_tidak_ikut(): void
    {
        $e = $this->karyawan();
        $this->batch('2026-09-30', 100, 72);
        $this->presensi($e, '2026-09-30', 25);

        $this->assertEqualsWithDelta(0.0, $this->bonus($e, 10)['amount'], 0.001);
        $this->assertEqualsWithDelta(25000.0, $this->bonus($e, 9)['amount'], 0.001);
    }

    public function test_gross_dan_net_memuat_semua_komponen(): void
    {
        $e = $this->karyawan('RELAWAN', 70000, 1500);
        $this->batch('2026-10-05', 100, 72);
        $this->presensi($e, '2026-10-05', 40, 'HADIR');
        $this->presensi($e, '2026-10-06', 0, 'SETENGAH');

        $p = new Payroll([
            'period_year' => 2026, 'period_month' => 10,
            'bonus_amount' => 5000, 'other_deduction_amount' => 2000, 'loan_deduction_amount' => 3000,
        ]);
        $this->svc->terapkan($p, $e, $this->svc->hitungPresensi($e->id, 2026, 10));

        $this->assertEqualsWithDelta(1.5, (float) $p->days_present, 0.001);
        $this->assertEqualsWithDelta(105000.0, (float) $p->base_amount, 0.001);      // 1,5 hari x 70.000
        $this->assertEqualsWithDelta(60000.0, (float) $p->kg_amount, 0.001);          // 40 kg x 1.500
        $this->assertEqualsWithDelta(40000.0, (float) $p->rendemen_bonus_amount, 0.001);
        $this->assertEqualsWithDelta(210000.0, (float) $p->gross_amount, 0.001);      // 105k + 60k + 5k + 40k
        $this->assertEqualsWithDelta(205000.0, (float) $p->net_amount, 0.001);        // 210k - 2k - 3k
    }

    public function test_kolom_bonus_tersimpan_di_database(): void
    {
        $e = $this->karyawan('RELAWAN', 70000, 1500);
        $this->batch('2026-10-05', 100, 72);
        $this->presensi($e, '2026-10-05', 40);

        $p = new Payroll([
            'employee_id' => $e->id, 'period_year' => 2026, 'period_month' => 10,
            'status' => 'DRAFT', 'created_by_id' => (string) Str::uuid(),
        ]);
        $this->svc->terapkan($p, $e, $this->svc->hitungPresensi($e->id, 2026, 10));
        $p->save();

        $baca = Payroll::findOrFail($p->id);
        $this->assertEqualsWithDelta(40.0, (float) $baca->rendemen_bonus_kg, 0.001);
        $this->assertEqualsWithDelta(40000.0, (float) $baca->rendemen_bonus_amount, 0.001);
    }

    public function test_terbilang(): void
    {
        $this->assertSame('nol', GajiService::terbilang(0));
        $this->assertSame('seratus ribu', GajiService::terbilang(100000));
        $this->assertSame('satu juta lima ratus ribu', GajiService::terbilang(1500000));
    }
}