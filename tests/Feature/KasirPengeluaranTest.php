<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class KasirPengeluaranTest extends TestCase
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

    public function test_kasir_bisa_catat_pengeluaran_tanggal_hari_ini(): void
    {
        $k = $this->login(User::ROLE_KASIR);

        $this->post(route('pengeluaran.store'), [
            'expense_date' => '2020-01-01', 'category' => 'Es', 'amount' => '50000', 'description' => 'Es balok',
        ])->assertSessionHasNoErrors();

        $e = Expense::first();
        $this->assertNotNull($e);
        $this->assertSame(now()->toDateString(), $e->expense_date->toDateString());
        $this->assertSame($k->id, $e->recorded_by_id);
        $this->assertEqualsWithDelta(50000.0, (float) $e->amount, 0.001);
    }

    public function test_kasir_tidak_boleh_kategori_gaji(): void
    {
        $this->login(User::ROLE_KASIR);

        $this->post(route('pengeluaran.store'), ['category' => 'Upah / Gaji', 'amount' => '100000'])
            ->assertSessionHasErrors('category');
        $this->assertSame(0, Expense::count());
    }

    public function test_kasir_hanya_melihat_catatan_sendiri(): void
    {
        $admin = $this->login(User::ROLE_ADMIN);
        Expense::create([
            'expense_date' => now()->toDateString(), 'category' => 'Lainnya', 'amount' => 1000,
            'description' => 'Milik Admin XYZ', 'recorded_by_id' => $admin->id,
        ]);

        $this->login(User::ROLE_KASIR);
        $this->post(route('pengeluaran.store'), ['category' => 'Plastik', 'amount' => '2000', 'description' => 'Milik Kasir ABC']);

        $this->get(route('pengeluaran.index'))->assertOk()
            ->assertSee('Milik Kasir ABC')->assertDontSee('Milik Admin XYZ');
    }

    public function test_manager_melihat_tapi_tidak_bisa_mencatat(): void
    {
        $this->login(User::ROLE_MANAGER);

        $this->get(route('pengeluaran.index'))->assertOk()->assertDontSee('Catat Pengeluaran');
        $this->post(route('pengeluaran.store'), ['category' => 'Es', 'amount' => '1000'])->assertForbidden();
        $this->assertSame(0, Expense::count());
    }

    public function test_pengawas_ditolak(): void
    {
        $this->login(User::ROLE_PENGAWAS);

        $this->get(route('pengeluaran.index'))->assertForbidden();
        $this->post(route('pengeluaran.store'), ['category' => 'Es', 'amount' => '1000'])->assertForbidden();
        $this->assertSame(0, Expense::count());
    }

    public function test_admin_bisa_pilih_tanggal_dan_kategori_gaji(): void
    {
        $this->login(User::ROLE_ADMIN);
        $kemarin = now()->subDay()->toDateString();

        $this->post(route('pengeluaran.store'), [
            'expense_date' => $kemarin, 'category' => 'Upah / Gaji', 'amount' => '300000',
        ])->assertSessionHasNoErrors();

        $this->assertSame($kemarin, Expense::first()->expense_date->toDateString());
    }

    public function test_layar_kasir_punya_link_pelanggan_baru(): void
    {
        $this->login(User::ROLE_KASIR);
        $this->get(route('kasir.create'))->assertOk()->assertSee(route('pelanggan.index'), false);
    }
}