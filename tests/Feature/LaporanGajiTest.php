<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Setting;
use App\Models\User;
use App\Services\GajiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class LaporanGajiTest extends TestCase
{
    use RefreshDatabase;

    private GajiService $svc;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDatabaseName() !== 'bumdesa_test') {
            throw new \RuntimeException('Test harus memakai database bumdesa_test. Dibatalkan agar data asli aman.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET session_replication_role = replica');
        $this->svc = new GajiService();
        Setting::simpan(['bonus_rendemen_per_kg' => '1000', 'bonus_rendemen_min' => '0']);
    }

    /* ---------- helper data dummy ---------- */

    private function login(string $role = User::ROLE_ADMIN): User
    {
        $u = User::create([
            'username' => 'u' . Str::lower(Str::random(8)), 'name' => 'Dummy ' . $role,
            'password' => 'rahasia12345', 'role' => $role, 'is_active' => true,
        ]);
        $this->actingAs($u);

        return $u;
    }

    private function karyawan(string $nama, string $type = 'KARYAWAN', float $harian = 50000, float $perKg = 0): Employee
    {
        return Employee::create([
            'code' => 'E-' . Str::upper(Str::random(8)), 'name' => $nama, 'type' => $type,
            'daily_rate' => $harian, 'per_kg_rate' => $perKg, 'is_active' => true,
        ]);
    }

    private function slip(Employee $e, string $status, int $bulan = 10, ?array $h = null): Payroll
    {
        $h ??= ['days' => 2.0, 'kg' => 0.0, 'counts' => []];
        $p = new Payroll([
            'employee_id' => $e->id, 'period_year' => 2026, 'period_month' => $bulan,
            'status' => $status, 'created_by_id' => (string) Str::uuid(),
        ]);
        $this->svc->terapkan($p, $e, $h);

        if (in_array($status, ['FINAL', 'PAID'], true)) {
            $p->payroll_number = sprintf('SG-2026%02d-', $bulan) . Str::upper(Str::random(4));
            $p->finalized_at = now();
            $p->finalized_by_id = (string) Str::uuid();
        }
        if ($status === 'PAID') {
            $p->paid_at = now();
            $p->paid_by_id = (string) Str::uuid();
        }
        if ($status === 'VOID') {
            $p->voided_at = now();
            $p->voided_by_id = (string) Str::uuid();
            $p->void_reason = 'Dummy dibatalkan';
        }
        $p->save();

        return $p;
    }

    private function nota(string $tgl, float $total, bool $void = false): void
    {
        DB::table('sales')->insert([
            'id' => (string) Str::uuid(), 'sale_number' => 'N-' . Str::random(8), 'sale_date' => $tgl,
            'customer_name' => 'Umum', 'customer_type_id' => (string) Str::uuid(),
            'subtotal' => $total, 'discount' => 0, 'total_amount' => $total, 'status' => 'COMPLETED',
            'is_voided' => $void, 'cashier_id' => (string) Str::uuid(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function pengeluaran(string $tgl, string $kategori, float $jumlah): void
    {
        DB::table('expenses')->insert([
            'id' => (string) Str::uuid(), 'expense_date' => $tgl, 'category' => $kategori, 'amount' => $jumlah,
            'description' => 'Dummy', 'recorded_by_id' => (string) Str::uuid(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function pembelian(string $tgl, float $biaya): void
    {
        DB::table('purchase_transactions')->insert([
            'id' => (string) Str::uuid(), 'transaction_number' => 'PB-' . Str::random(8), 'purchase_date' => $tgl,
            'supplier_id' => (string) Str::uuid(), 'total_birds' => 100, 'total_weight_kg' => 200,
            'unit_price_per_kg' => $biaya / 200, 'total_cost' => $biaya,
            'received_by_id' => (string) Str::uuid(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function batch(string $tgl, float $masukKg, float $hasilKg): void
    {
        $id = (string) Str::uuid();
        DB::table('production_batches')->insert([
            'id' => $id, 'batch_number' => 'B-' . Str::random(8), 'production_date' => $tgl,
            'input_birds' => 100, 'input_weight_kg' => $masukKg, 'status' => 'DONE',
            'created_by_id' => (string) Str::uuid(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $w = [
            'id' => (string) Str::uuid(), 'receipt_number' => 'W-' . Str::random(8), 'batch_id' => $id,
            'product_id' => (string) Str::uuid(), 'weight_kg' => $hasilKg,
            'operator_id' => (string) Str::uuid(), 'created_at' => now(), 'updated_at' => now(),
        ];
        if (Schema::hasColumn('weighing_records', 'storage_location_id')) {
            $w['storage_location_id'] = (string) Str::uuid();
        }
        DB::table('weighing_records')->insert($w);
    }

    private function slipRelawanDenganBonus(): Payroll
    {
        $e = $this->karyawan('Relawan Bonus', 'RELAWAN', 0, 1500);
        $this->batch('2026-10-05', 100, 72);
        Attendance::create([
            'recorded_by_id' => (string) Str::uuid(), 'employee_id' => $e->id,
            'work_date' => '2026-10-05', 'status' => 'HADIR', 'kg_cut' => 40,
        ]);

        return $this->slip($e, 'FINAL', 10, $this->svc->hitungPresensi($e->id, 2026, 10));
    }

    private function oktober(array $viewData): array
    {
        return $viewData['baris'][9];
    }

    /* ---------- Laba Rugi ---------- */

    public function test_laba_rugi_hanya_untuk_admin(): void
    {
        $this->login(User::ROLE_KASIR);
        $this->get(route('gaji.labarugi'))->assertForbidden();

        $this->login(User::ROLE_MANAGER);
        $this->get(route('gaji.labarugi'))->assertForbidden();
    }

    public function test_laba_rugi_tidak_bisa_dibuka_tanpa_login(): void
    {
        $status = $this->get(route('gaji.labarugi'))->getStatusCode();
        $this->assertContains($status, [302, 401, 403]);
    }

    public function test_laba_rugi_tampil_untuk_admin(): void
    {
        $this->login();
        $this->get(route('gaji.labarugi', ['tahun' => 2026]))->assertOk()->assertSee('Laba Rugi Sederhana 2026');
    }

    public function test_nota_void_tidak_masuk_omzet(): void
    {
        $this->login();
        $this->nota('2026-10-03', 1000000);
        $this->nota('2026-10-04', 500000, true);

        $r = $this->get(route('gaji.labarugi', ['tahun' => 2026]))->assertOk();
        $this->assertEqualsWithDelta(1000000.0, $this->oktober($r->viewData('tot') ? ['baris' => $r->viewData('baris')] : [])['omzet'], 0.001);
        $this->assertEqualsWithDelta(1000000.0, $r->viewData('tot')['omzet'], 0.001);
    }

    public function test_gaji_dibayar_tidak_terhitung_dua_kali(): void
    {
        $this->login();
        $this->nota('2026-10-03', 1000000);
        $this->pengeluaran('2026-10-20', 'Upah / Gaji', 300000);
        $this->pengeluaran('2026-10-21', 'Operasional', 200000);
        $this->slip($this->karyawan('Paid', 'KARYAWAN', 150000), 'PAID');   // net 300.000, sudah ada di expenses
        $this->slip($this->karyawan('Final', 'KARYAWAN', 50000), 'FINAL');  // net 100.000, belum dibayar

        $r = $this->get(route('gaji.labarugi', ['tahun' => 2026]))->assertOk();
        $okt = $this->oktober(['baris' => $r->viewData('baris')]);

        $this->assertEqualsWithDelta(300000.0, $okt['gaji'], 0.001);
        $this->assertEqualsWithDelta(200000.0, $okt['lain'], 0.001);
        $this->assertEqualsWithDelta(500000.0, $okt['laba'], 0.001);
        $this->assertEqualsWithDelta(100000.0, $okt['belum'], 0.001);
    }

    public function test_pembelian_ayam_mengurangi_laba(): void
    {
        $this->login();
        $this->nota('2026-10-03', 2000000);
        $this->pembelian('2026-10-02', 800000);
        $this->pengeluaran('2026-10-20', 'Upah / Gaji', 300000);

        $r = $this->get(route('gaji.labarugi', ['tahun' => 2026]))->assertOk();
        $okt = $this->oktober(['baris' => $r->viewData('baris')]);

        $this->assertEqualsWithDelta(800000.0, $okt['beli'], 0.001);
        $this->assertEqualsWithDelta(900000.0, $okt['laba'], 0.001); // 2.000.000 - 800.000 - 300.000
    }

    public function test_rasio_gaji_terhadap_omzet(): void
    {
        $this->login();
        $this->nota('2026-10-03', 1000000);
        $this->pengeluaran('2026-10-20', 'Upah / Gaji', 200000);

        $r = $this->get(route('gaji.labarugi', ['tahun' => 2026]))->assertOk();
        $this->assertEqualsWithDelta(20.0, $this->oktober(['baris' => $r->viewData('baris')])['rasio'], 0.001);
    }

    public function test_data_tahun_lain_tidak_ikut(): void
    {
        $this->login();
        $this->nota('2025-10-03', 900000);
        $this->pembelian('2025-10-02', 400000);

        $r = $this->get(route('gaji.labarugi', ['tahun' => 2026]))->assertOk();
        $this->assertEqualsWithDelta(0.0, $r->viewData('tot')['omzet'], 0.001);
        $this->assertEqualsWithDelta(0.0, $r->viewData('tot')['beli'], 0.001);
    }

    /* ---------- Daftar penerimaan ---------- */

    public function test_penerimaan_hanya_slip_terbit_dan_dibayar(): void
    {
        $this->login();
        $this->slip($this->karyawan('Ani Final', 'KARYAWAN', 50000), 'FINAL');   // 100.000
        $this->slip($this->karyawan('Budi Paid', 'KARYAWAN', 100000), 'PAID');   // 200.000
        $this->slip($this->karyawan('Cici Draft', 'KARYAWAN', 50000), 'DRAFT');
        $this->slip($this->karyawan('Dedi Void', 'KARYAWAN', 50000), 'VOID');

        $this->get(route('gaji.penerimaan', ['bulan' => '2026-10']))
            ->assertOk()
            ->assertSee('Ani Final')->assertSee('Budi Paid')
            ->assertDontSee('Cici Draft')->assertDontSee('Dedi Void')
            ->assertSee('300.000');
    }

    public function test_penerimaan_kosong_memberi_404(): void
    {
        $this->login();
        $this->slip($this->karyawan('Cici Draft'), 'DRAFT');

        $this->get(route('gaji.penerimaan', ['bulan' => '2026-10']))->assertNotFound();
    }

    public function test_penerimaan_hanya_untuk_admin(): void
    {
        $this->login(User::ROLE_KASIR);
        $this->get(route('gaji.penerimaan', ['bulan' => '2026-10']))->assertForbidden();
    }

    /* ---------- Slip dan halaman detail ---------- */

    public function test_slip_menampilkan_bonus_rendemen(): void
    {
        $this->login();
        $p = $this->slipRelawanDenganBonus();

        $this->get(route('gaji.slip', $p))
            ->assertOk()
            ->assertSee('Bonus rendemen')
            ->assertSee('Rp 40.000')    // 40 kg x Rp 1.000
            ->assertSee('Rp 100.000');  // borongan 60.000 + bonus 40.000
    }

    public function test_slip_tanpa_bonus_tidak_menampilkan_barisnya(): void
    {
        $this->login();
        $p = $this->slip($this->karyawan('Harian Biasa'), 'DRAFT');

        $this->get(route('gaji.slip', $p))->assertOk()->assertDontSee('Bonus rendemen');
    }

    public function test_halaman_detail_menampilkan_bonus_rendemen(): void
    {
        $this->login();
        $p = $this->slipRelawanDenganBonus();

        $this->get(route('gaji.show', $p))->assertOk()->assertSee('Bonus rendemen')->assertSee('Rp 40.000');
    }
}