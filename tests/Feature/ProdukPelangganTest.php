<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProdukPelangganTest extends TestCase
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

    private function tipe(string $nama = 'Umum'): CustomerType
    {
        return CustomerType::create(['code' => 'T_' . Str::upper(Str::random(6)), 'name' => $nama, 'is_active' => true]);
    }

    private function produk(string $nama = 'Dada Uji'): Product
    {
        return Product::create(['code' => 'P-' . Str::upper(Str::random(6)), 'name' => $nama, 'primary_unit' => 'KG', 'is_active' => true]);
    }

    private function hargaAwal(Product $p, CustomerType $t, float $harga): void
    {
        ProductPrice::create([
            'product_id' => $p->id, 'customer_type_id' => $t->id, 'price' => $harga,
            'effective_date' => now(), 'is_active' => true,
        ]);
    }

    private function aktif(Product $p, CustomerType $t)
    {
        return ProductPrice::where('product_id', $p->id)->where('customer_type_id', $t->id)->where('is_active', true)->get();
    }

    private function pelanggan(string $nama, CustomerType $t, ?string $telp = null): Customer
    {
        return Customer::create(['name' => $nama, 'phone' => $telp, 'customer_type_id' => $t->id, 'is_active' => true]);
    }

    /* ---------- Produk ---------- */

    public function test_produk_hanya_admin_dan_manager(): void
    {
        foreach ([[User::ROLE_ADMIN, 200], [User::ROLE_MANAGER, 200], [User::ROLE_KASIR, 403], [User::ROLE_PENGAWAS, 403]] as [$role, $kode]) {
            $this->login($role);
            $this->get(route('produk.index'))->assertStatus($kode);
        }
    }

    public function test_manager_melihat_tanpa_form_tambah(): void
    {
        $this->produk('Produk Terlihat');

        $this->login(User::ROLE_MANAGER);
        $this->get(route('produk.index'))->assertOk()->assertSee('Produk Terlihat')->assertDontSee('Tambah produk');

        $this->login(User::ROLE_ADMIN);
        $this->get(route('produk.index'))->assertOk()->assertSee('Tambah produk');
    }

    public function test_admin_membuat_produk(): void
    {
        $this->login();
        $this->post(route('produk.store'), ['code' => ' dada-fillet ', 'name' => 'Dada Fillet', 'description' => 'Uji'])
            ->assertSessionHasNoErrors();

        $p = Product::where('code', 'DADA-FILLET')->first();
        $this->assertNotNull($p);
        $this->assertSame('KG', $p->primary_unit);
        $this->assertTrue((bool) $p->is_active);
        $this->assertFalse((bool) $p->is_live_bird);
    }

    public function test_kode_produk_kembar_atau_salah_format_ditolak(): void
    {
        $this->login();
        Product::create(['code' => 'KODE-1', 'name' => 'Sudah Ada', 'primary_unit' => 'KG', 'is_active' => true]);

        $this->post(route('produk.store'), ['code' => 'kode-1', 'name' => 'Kembar'])->assertSessionHasErrors('code');
        $this->post(route('produk.store'), ['code' => 'kode salah!', 'name' => 'Format'])->assertSessionHasErrors('code');
        $this->post(route('produk.store'), ['code' => 'KODE-2'])->assertSessionHasErrors('name');
        $this->assertSame(1, Product::count());
    }

    public function test_non_admin_tidak_bisa_menulis_produk(): void
    {
        foreach ([User::ROLE_MANAGER, User::ROLE_KASIR, User::ROLE_PENGAWAS] as $role) {
            $this->login($role);
            $acak = (string) Str::uuid();

            $this->post(route('produk.store'), ['code' => 'XYZ-1', 'name' => 'X'])->assertForbidden();
            $this->get(route('produk.edit', $acak))->assertForbidden();
            $this->put(route('produk.update', $acak), ['name' => 'X'])->assertForbidden();
            $this->put(route('produk.harga', $acak), ['harga' => []])->assertForbidden();
        }
        $this->assertSame(0, Product::count());
    }

    public function test_harga_baru_dibuat(): void
    {
        $this->login();
        $p = $this->produk();
        $t = $this->tipe();

        $this->put(route('produk.harga', $p->id), ['harga' => [$t->id => '45000']])->assertSessionHasNoErrors();

        $a = $this->aktif($p, $t);
        $this->assertCount(1, $a);
        $this->assertEqualsWithDelta(45000.0, (float) $a->first()->price, 0.001);
    }

    public function test_harga_berubah_riwayat_tersimpan(): void
    {
        $this->login();
        $p = $this->produk();
        $t = $this->tipe();
        $this->hargaAwal($p, $t, 40000);

        $this->put(route('produk.harga', $p->id), ['harga' => [$t->id => '45000']])->assertSessionHasNoErrors();

        $this->assertSame(2, ProductPrice::where('product_id', $p->id)->count());   // harga lama tidak dihapus
        $a = $this->aktif($p, $t);
        $this->assertCount(1, $a);                                                    // hanya satu yang berlaku
        $this->assertEqualsWithDelta(45000.0, (float) $a->first()->price, 0.001);
        $this->assertEqualsWithDelta(40000.0, (float) ProductPrice::where('product_id', $p->id)->where('is_active', false)->first()->price, 0.001);
    }

    public function test_harga_sama_tidak_membuat_riwayat(): void
    {
        $this->login();
        $p = $this->produk();
        $t = $this->tipe();
        $this->hargaAwal($p, $t, 40000);

        $this->put(route('produk.harga', $p->id), ['harga' => [$t->id => '40000']])->assertSessionHasNoErrors();
        $this->assertSame(1, ProductPrice::where('product_id', $p->id)->count());
    }

    public function test_harga_dikosongkan_menonaktifkan_dan_kunci_absen_tidak_mengubah(): void
    {
        $this->login();
        $p = $this->produk();
        $t1 = $this->tipe('Umum');
        $t2 = $this->tipe('UMKM');
        $this->hargaAwal($p, $t1, 40000);
        $this->hargaAwal($p, $t2, 30000);

        // Hanya t1 dikirim (kosong): t1 dihapus, t2 tidak disentuh
        $this->put(route('produk.harga', $p->id), ['harga' => [$t1->id => '']])->assertSessionHasNoErrors();

        $this->assertCount(0, $this->aktif($p, $t1));
        $this->assertCount(1, $this->aktif($p, $t2));
        $this->assertSame(1, ProductPrice::where('product_id', $p->id)->where('customer_type_id', $t1->id)->count());
    }

    public function test_harga_tidak_valid_ditolak(): void
    {
        $this->login();
        $p = $this->produk();
        $t = $this->tipe();

        $this->put(route('produk.harga', $p->id), ['harga' => [$t->id => '-5']])->assertSessionHasErrors("harga.{$t->id}");
        $this->put(route('produk.harga', $p->id), ['harga' => [$t->id => 'abc']])->assertSessionHasErrors("harga.{$t->id}");
        $this->assertSame(0, ProductPrice::count());
    }

    public function test_ubah_info_produk_kode_tidak_berubah(): void
    {
        $this->login();
        $p = $this->produk();
        $kode = $p->code;

        $this->put(route('produk.update', $p->id), ['name' => 'Nama Baru', 'description' => 'Ket', 'is_active' => '0', 'code' => 'HACK'])
            ->assertSessionHasNoErrors();

        $p->refresh();
        $this->assertSame('Nama Baru', $p->name);
        $this->assertFalse((bool) $p->is_active);
        $this->assertSame($kode, $p->code);
    }

    /* ---------- Pelanggan ---------- */

    public function test_pelanggan_index_per_peran(): void
    {
        $t = $this->tipe();
        $this->pelanggan('Warung Contoh', $t);

        foreach ([User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_KASIR] as $role) {
            $this->login($role);
            $this->get(route('pelanggan.index'))->assertOk()->assertSee('Warung Contoh');
        }

        $this->login(User::ROLE_KASIR);
        $this->get(route('pelanggan.index'))->assertSee('Tambah pelanggan')->assertDontSee('/edit');

        $this->login(User::ROLE_MANAGER);
        $this->get(route('pelanggan.index'))->assertDontSee('Tambah pelanggan')->assertDontSee('/edit');

        $this->login(User::ROLE_ADMIN);
        $this->get(route('pelanggan.index'))->assertSee('/edit');

        $this->login(User::ROLE_PENGAWAS);
        $this->get(route('pelanggan.index'))->assertForbidden();
    }

    public function test_kasir_bisa_tambah_pelanggan(): void
    {
        $t = $this->tipe();
        $this->login(User::ROLE_KASIR);

        $this->post(route('pelanggan.store'), [
            'name' => 'Warung Bu Ani', 'phone' => '0812345', 'address' => 'Jl. Uji', 'customer_type_id' => $t->id,
        ])->assertSessionHasNoErrors()->assertRedirect(route('pelanggan.index'));

        $c = Customer::where('name', 'Warung Bu Ani')->first();
        $this->assertNotNull($c);
        $this->assertSame($t->id, $c->customer_type_id);
        $this->assertTrue((bool) $c->is_active);
    }

    public function test_validasi_tambah_pelanggan(): void
    {
        $t = $this->tipe();
        $nonaktif = $this->tipe('Lama');
        $nonaktif->update(['is_active' => false]);
        $this->login(User::ROLE_KASIR);

        $this->post(route('pelanggan.store'), ['customer_type_id' => $t->id])->assertSessionHasErrors('name');
        $this->post(route('pelanggan.store'), ['name' => 'A', 'customer_type_id' => (string) Str::uuid()])->assertSessionHasErrors('customer_type_id');
        $this->post(route('pelanggan.store'), ['name' => 'B', 'customer_type_id' => $nonaktif->id])->assertSessionHasErrors('customer_type_id');
        $this->assertSame(0, Customer::count());

        // Kembar: nama dan telepon sama ditolak, telepon beda boleh
        $d = ['name' => 'Toko Kembar', 'phone' => '0811', 'customer_type_id' => $t->id];
        $this->post(route('pelanggan.store'), $d)->assertSessionHasNoErrors();
        $this->post(route('pelanggan.store'), ['name' => 'toko kembar'] + $d)->assertSessionHasErrors('name');
        $this->assertSame(1, Customer::count());
        $this->post(route('pelanggan.store'), ['phone' => '0899'] + $d)->assertSessionHasNoErrors();
        $this->assertSame(2, Customer::count());
    }

    public function test_peran_lain_tidak_bisa_tambah_pelanggan(): void
    {
        $t = $this->tipe();
        foreach ([User::ROLE_MANAGER, User::ROLE_PENGAWAS] as $role) {
            $this->login($role);
            $this->post(route('pelanggan.store'), ['name' => 'Tidak Boleh', 'customer_type_id' => $t->id])->assertForbidden();
        }
        $this->assertSame(0, Customer::count());
    }

    public function test_hanya_admin_boleh_ubah_pelanggan(): void
    {
        $t = $this->tipe();
        $c = $this->pelanggan('Nama Lama', $t, '0811');
        $data = ['name' => 'Diretas', 'phone' => '', 'address' => '', 'customer_type_id' => $t->id, 'is_active' => '1'];

        foreach ([User::ROLE_KASIR, User::ROLE_MANAGER, User::ROLE_PENGAWAS] as $role) {
            $this->login($role);
            $this->get(route('pelanggan.edit', $c->id))->assertForbidden();
            $this->put(route('pelanggan.update', $c->id), $data)->assertForbidden();
            $this->put(route('pelanggan.update', (string) Str::uuid()), $data)->assertForbidden();
        }
        $this->assertSame('Nama Lama', $c->fresh()->name);

        $this->login(User::ROLE_ADMIN);
        $this->put(route('pelanggan.update', $c->id), ['name' => 'Nama Baru', 'is_active' => '0'] + $data)->assertSessionHasNoErrors();

        $c->refresh();
        $this->assertSame('Nama Baru', $c->name);
        $this->assertNull($c->phone);
        $this->assertFalse((bool) $c->is_active);
    }

    public function test_pencarian_pelanggan(): void
    {
        $t = $this->tipe();
        $this->pelanggan('Warung Ani', $t);
        $this->pelanggan('Toko Budi', $t, '0877');

        $this->login(User::ROLE_ADMIN);
        $this->get(route('pelanggan.index', ['q' => 'ani']))->assertOk()->assertSee('Warung Ani')->assertDontSee('Toko Budi');
        $this->get(route('pelanggan.index', ['q' => '0877']))->assertOk()->assertSee('Toko Budi')->assertDontSee('Warung Ani');
    }
}