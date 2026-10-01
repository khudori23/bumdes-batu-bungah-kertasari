<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LaporanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

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
    }

    private function login(string $role): User
    {
        $u = User::create([
            'username' => 'u' . Str::lower(Str::random(8)), 'name' => 'Dummy ' . $role,
            'password' => 'rahasia12345', 'role' => $role, 'is_active' => true,
        ]);
        $this->actingAs($u);

        return $u;
    }

    private function nota(string $tgl, float $total, bool $void = false): void
    {
        DB::table('sales')->insert([
            'id' => (string) Str::uuid(), 'sale_number' => 'T-' . Str::random(8), 'sale_date' => $tgl,
            'customer_name' => 'Umum', 'customer_type_id' => (string) Str::uuid(),
            'subtotal' => $total, 'discount' => 0, 'total_amount' => $total,
            'previous_balance' => 0, 'balance_after' => 0,
            'status' => $void ? 'VOIDED' : 'COMPLETED', 'is_voided' => $void,
            'cashier_id' => (string) Str::uuid(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_hanya_admin_dan_manager(): void
    {
        foreach ([[User::ROLE_ADMIN, 200], [User::ROLE_MANAGER, 200], [User::ROLE_KASIR, 403], [User::ROLE_PENGAWAS, 403]] as [$role, $kode]) {
            $this->login($role);
            $this->get(route('laporan.index'))->assertStatus($kode);
        }
    }

    public function test_tamu_dialihkan_ke_login(): void
    {
        $this->get(route('laporan.index'))->assertRedirect(route('login'));
    }

    public function test_rentang_minggu_dan_bulan(): void
    {
        $svc = new LaporanService();
        $kamis = Carbon::parse('2026-10-01');

        [$a, $b] = $svc->rentang('mingguan', $kamis);
        $this->assertSame('2026-09-28', $a->toDateString());
        $this->assertSame('2026-10-04', $b->toDateString());

        [$a, $b] = $svc->rentang('bulanan', $kamis);
        $this->assertSame('2026-10-01', $a->toDateString());
        $this->assertSame('2026-10-31', $b->toDateString());

        [$a, $b] = $svc->rentang('harian', $kamis);
        $this->assertSame('2026-10-01', $a->toDateString());
        $this->assertSame('2026-10-01', $b->toDateString());

        [$a, ] = $svc->sebelumnya('bulanan', Carbon::parse('2026-10-01'));
        $this->assertSame('2026-09-01', $a->toDateString());
    }

    public function test_omzet_harian_tidak_menghitung_nota_void_dan_hari_lain(): void
    {
        $this->nota('2026-10-01', 1234000);
        $this->nota('2026-10-01', 555000, true);
        $this->nota('2026-10-02', 777000);

        $this->login(User::ROLE_MANAGER);
        $this->get(route('laporan.index', ['jenis' => 'harian', 'tanggal' => '2026-10-01']))
            ->assertOk()
            ->assertSee('Rp 1.234.000')
            ->assertDontSee('Rp 555.000')
            ->assertDontSee('Rp 777.000')
            ->assertSee('LAP/HARIAN/20261001');
    }

    public function test_laporan_mingguan_memuat_seluruh_minggu(): void
    {
        $this->nota('2026-09-28', 100000);
        $this->nota('2026-10-04', 200000);
        $this->nota('2026-10-05', 900000);

        $this->login(User::ROLE_ADMIN);
        $this->get(route('laporan.index', ['jenis' => 'mingguan', 'tanggal' => '2026-10-01']))
            ->assertOk()
            ->assertSee('LAP/MINGGUAN/20260928')
            ->assertSee('Rp 300.000')
            ->assertDontSee('Rp 900.000');
    }

    public function test_rendemen_rendah_memunculkan_peringatan(): void
    {
        $batch = (string) Str::uuid();
        DB::table('production_batches')->insert([
            'id' => $batch, 'batch_number' => 'B-' . Str::random(6), 'production_date' => '2026-10-01',
            'input_birds' => 40, 'input_weight_kg' => 100, 'status' => 'DONE',
            'created_by_id' => (string) Str::uuid(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('weighing_records')->insert([
            'id' => (string) Str::uuid(), 'receipt_number' => 'W-' . Str::random(6), 'batch_id' => $batch,
            'product_id' => (string) Str::uuid(), 'weight_kg' => 60, 'pieces' => 0,
            'operator_id' => (string) Str::uuid(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->login(User::ROLE_ADMIN);
        $this->get(route('laporan.index', ['jenis' => 'harian', 'tanggal' => '2026-10-01']))
            ->assertOk()->assertSee('60,0%')->assertSee('di bawah 65%');
    }

    public function test_tanggal_tidak_valid_memakai_hari_ini(): void
    {
        $this->login(User::ROLE_ADMIN);
        $this->get(route('laporan.index', ['jenis' => 'ngawur', 'tanggal' => 'bukan-tanggal']))
            ->assertOk()->assertSee('LAP/HARIAN/' . now()->format('Ymd'));
    }
}