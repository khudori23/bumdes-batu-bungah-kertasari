<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class NavigasiTest extends TestCase
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
        $this->actingAs(User::create([
            'username' => 'u' . Str::lower(Str::random(8)), 'name' => 'Dummy',
            'password' => 'rahasia12345', 'role' => User::ROLE_ADMIN, 'is_active' => true,
        ]));
    }

    public function test_halaman_dalam_punya_kembali_dan_beranda(): void
    {
        foreach (['produk.index', 'pelanggan.index', 'pengeluaran.index', 'kasir.create'] as $r) {
            $this->get(route($r))->assertOk()
                ->assertSee('class="navhal"', false)
                ->assertSee('data-kembali', false)
                ->assertSee('Beranda');
        }
    }

    public function test_dashboard_tanpa_baris_kembali(): void
    {
        $this->get(route('dashboard'))->assertOk()->assertDontSee('class="navhal"', false);
    }

    public function test_laporan_kembali_memakai_riwayat(): void
    {
        $this->get(route('laporan.index'))->assertOk()->assertSee('data-kembali', false);
    }

    public function test_halaman_login_tidak_disimpan_cache(): void
    {
        $h = $this->get(route('dashboard'))->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', (string) $h);
    }
}