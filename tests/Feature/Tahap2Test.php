<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Services\StokService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class Tahap2Test extends TestCase
{
    use DatabaseTransactions;

    // ---------- Pembantu ----------

    private function user(string $role, bool $aktif = true): User
    {
        return User::create([
            'username'  => 'uji_' . strtolower(substr($role, 0, 5)) . '_' . Str::lower(Str::random(6)),
            'name'      => 'Uji ' . $role,
            'email'     => null,
            'password'  => 'rahasia12345',
            'role'      => $role,
            'is_active' => $aktif,
        ]);
    }

    /** Sisip baris ke tabel; kolom yang tidak ada di tabel dibuang otomatis. */
    private function sisip(string $tabel, array $row): string
    {
        $row['id'] ??= (string) Str::uuid();
        $row += ['created_at' => now(), 'updated_at' => now()];
        $row = array_filter($row, fn ($k) => Schema::hasColumn($tabel, $k), ARRAY_FILTER_USE_KEY);
        DB::table($tabel)->insert($row);

        return $row['id'];
    }

    private function produk(bool $hidup = false): Product
    {
        return Product::create([
            'code'         => 'TST' . Str::upper(Str::random(8)),
            'name'         => ($hidup ? 'Ayam Hidup Uji ' : 'Produk Uji ') . Str::random(4),
            'primary_unit' => $hidup ? 'EKOR' : 'KG',
            'is_active'    => true,
            'is_live_bird' => $hidup,
        ]);
    }

    private function tipePelanggan(): string
    {
        return DB::table('customer_types')->where('is_active', true)->value('id')
            ?: $this->sisip('customer_types', ['code' => 'TST_UJI', 'name' => 'Uji', 'is_active' => true]);
    }

    private function harga(Product $p, string $tipe, float $harga): void
    {
        $this->sisip('product_prices', [
            'product_id' => $p->id, 'customer_type_id' => $tipe, 'price' => $harga, 'is_active' => true,
        ]);
    }

    private function stok(): array
    {
        return app(StokService::class)->ayamHidup();
    }

    private function beli(User $u, int $ekor, float $kg): void
    {
        $supplier = $this->sisip('suppliers', [
            'code' => 'TST' . Str::upper(Str::random(6)), 'name' => 'Pemasok Uji', 'is_active' => true,
        ]);

        $this->actingAs($u)->post('/pembelian', [
            'supplier_id'       => $supplier,
            'purchase_date'     => now()->toDateString(),
            'total_birds'       => $ekor,
            'total_weight_kg'   => $kg,
            'unit_price_per_kg' => 20000,
        ])->assertSessionHasNoErrors()->assertRedirect();
    }

    private function cek(?User $u, string $metode, string $url, int $harap, array $data = []): void
    {
        $req = $u ? $this->actingAs($u) : $this;
        $res = $metode === 'GET' ? $req->get($url) : $req->post($url, $data);

        $this->assertSame($harap, $res->getStatusCode(), ($u->role ?? 'tamu') . " $metode $url");
    }

    // ---------- 1. File dan struktur ----------

    public function test_file_ada_tidak_kosong_dan_struktur_db_lengkap(): void
    {
        $files = [
            'app/Models/LiveBirdMortality.php', 'app/Models/LiveBirdOpname.php',
            'app/Services/StokService.php', 'app/Http/Middleware/RoleMiddleware.php',
            'app/Http/Controllers/MortalitasController.php', 'app/Http/Controllers/OpnameAyamController.php',
            'app/Http/Controllers/ProduksiController.php', 'app/Http/Controllers/OpnameProdukController.php',
            'database/seeders/AyamHidupSeeder.php',
            'resources/views/layouts/_errors.blade.php', 'resources/views/mortalitas/index.blade.php',
            'resources/views/opname-ayam/index.blade.php', 'resources/views/produksi/index.blade.php',
            'resources/views/opname-produk/index.blade.php', 'resources/views/dashboard/_stok.blade.php',
            'resources/views/dashboard-kandang.blade.php',
        ];

        foreach ($files as $f) {
            $this->assertFileExists(base_path($f));
            $this->assertGreaterThan(0, filesize(base_path($f)), "File kosong: $f");
        }

        foreach (['mortalitas.index', 'mortalitas.store', 'opnameayam.index', 'opnameayam.store',
                  'produksi.index', 'produksi.store', 'opnameproduk.index', 'opnameproduk.store'] as $r) {
            $this->assertTrue(Route::has($r), "Route hilang: $r");
        }

        $this->assertTrue(Schema::hasTable('live_bird_mortalities'));
        $this->assertTrue(Schema::hasTable('live_bird_opnames'));
        $this->assertTrue(Schema::hasColumn('products', 'is_live_bird'));
        $this->assertTrue(Schema::hasColumn('sale_items', 'weight_kg'));
    }

    // ---------- 2. Matriks akses ----------

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/produksi')->assertRedirect(route('login'));
    }

    public function test_matriks_akses_halaman_per_peran(): void
    {
        $semua = ['/dashboard', '/pembelian', '/pemasok', '/mortalitas', '/opname-ayam', '/produksi',
                  '/opname-produk', '/kasir', '/kasir/riwayat', '/piutang'];

        $peta = [
            'ADMIN' => [200 => $semua],
            'PENGAWAS_KANDANG' => [
                200 => ['/dashboard', '/pembelian', '/pemasok', '/mortalitas', '/opname-ayam', '/produksi', '/opname-produk'],
                403 => ['/kasir', '/kasir/riwayat', '/piutang'],
            ],
            'KASIR' => [
                200 => ['/dashboard', '/kasir', '/kasir/riwayat', '/piutang', '/opname-produk'],
                403 => ['/pembelian', '/pemasok', '/mortalitas', '/opname-ayam', '/produksi'],
            ],
            'MANAGER' => [
                200 => ['/dashboard', '/pembelian', '/pemasok', '/mortalitas', '/opname-ayam', '/produksi',
                        '/opname-produk', '/kasir/riwayat', '/piutang'],
                403 => ['/kasir', '/pembelian/buat', '/pemasok/buat'],
            ],
        ];

        foreach ($peta as $role => $hasil) {
            $u = $this->user($role);
            foreach ($hasil as $kode => $urls) {
                foreach ($urls as $url) {
                    $this->cek($u, 'GET', $url, $kode);
                }
            }
        }
    }

    public function test_akun_nonaktif_ditolak(): void
    {
        $this->cek($this->user('KASIR', false), 'GET', '/kasir', 403);
        $this->cek($this->user('ADMIN', false), 'GET', '/pembelian', 403);
    }

    public function test_manager_tidak_bisa_menulis_apa_pun(): void
    {
        $m = $this->user('MANAGER');
        $sebelum = [
            DB::table('live_bird_mortalities')->count(), DB::table('production_batches')->count(),
            DB::table('stock_adjustments')->count(), DB::table('sales')->count(),
            DB::table('purchase_transactions')->count(),
        ];

        foreach (['/mortalitas', '/opname-ayam', '/produksi', '/opname-produk', '/pembelian', '/pemasok', '/kasir'] as $url) {
            $this->cek($m, 'POST', $url, 403, ['x' => 1]);
        }
        $this->cek($m, 'POST', '/kasir/' . Str::uuid() . '/void', 403, ['void_reason' => 'uji batal']);

        $sesudah = [
            DB::table('live_bird_mortalities')->count(), DB::table('production_batches')->count(),
            DB::table('stock_adjustments')->count(), DB::table('sales')->count(),
            DB::table('purchase_transactions')->count(),
        ];
        $this->assertSame($sebelum, $sesudah, 'Manager berhasil mengubah data!');
    }

    public function test_peran_tidak_bisa_menulis_di_luar_wewenang(): void
    {
        $kasir = $this->user('KASIR');
        foreach (['/produksi', '/mortalitas', '/opname-ayam', '/pembelian', '/pemasok'] as $url) {
            $this->cek($kasir, 'POST', $url, 403, ['x' => 1]);
        }

        $this->cek($this->user('PENGAWAS_KANDANG'), 'POST', '/kasir', 403, ['x' => 1]);
        $this->cek($this->user('PENGAWAS_KANDANG'), 'POST', '/piutang/' . Str::uuid() . '/bayar', 403, ['x' => 1]);
    }

    // ---------- 3. Alur kandang: pembelian -> mati -> produksi -> opname ----------

    public function test_alur_kandang_produksi_opname_dan_angka_dashboard(): void
    {
        $pengawas = $this->user('PENGAWAS_KANDANG');
        $kasir = $this->user('KASIR');
        $admin = $this->user('ADMIN');
        $tgl = now()->toDateString();
        $svc = app(StokService::class);

        $awal = $this->stok();
        $arusAwal = $svc->arus(now()->startOfDay(), now()->startOfDay());

        // Pembelian: stok bertambah
        $this->beli($pengawas, 100, 200);
        $s = $this->stok();
        $this->assertSame($awal['ekor'] + 100, $s['ekor']);
        $this->assertEqualsWithDelta($awal['kg'] + 200, $s['kg'], 0.01);

        // Ayam mati
        $this->actingAs($pengawas)->post('/mortalitas', [
            'record_date' => $tgl, 'birds' => 5, 'weight_kg' => 10, 'cause' => 'Sakit',
        ])->assertSessionHasNoErrors();
        $this->assertSame($awal['ekor'] + 95, $this->stok()['ekor']);

        // Mati melebihi stok -> ditolak
        $this->actingAs($pengawas)->post('/mortalitas', [
            'record_date' => $tgl, 'birds' => $this->stok()['ekor'] + 1, 'weight_kg' => 1,
        ])->assertSessionHasErrors('birds');

        // Produksi: hasil melebihi berat masuk -> ditolak
        $p1 = $this->produk();
        $p2 = $this->produk();
        $this->actingAs($pengawas)->post('/produksi', [
            'production_date' => $tgl, 'input_birds' => 40, 'input_weight_kg' => 90,
            'items' => [['product_id' => $p1->id, 'weight_kg' => 70], ['product_id' => $p2->id, 'weight_kg' => 30]],
        ])->assertSessionHasErrors('items');

        // Produksi: ayam melebihi stok -> ditolak
        $this->actingAs($pengawas)->post('/produksi', [
            'production_date' => $tgl, 'input_birds' => $this->stok()['ekor'] + 1, 'input_weight_kg' => 50,
            'items' => [['product_id' => $p1->id, 'weight_kg' => 10]],
        ])->assertSessionHasErrors('input_birds');

        // Produksi: produk ayam hidup tidak boleh jadi hasil potong
        $hidup = $this->produk(true);
        $this->actingAs($pengawas)->post('/produksi', [
            'production_date' => $tgl, 'input_birds' => 1, 'input_weight_kg' => 5,
            'items' => [['product_id' => $hidup->id, 'weight_kg' => 1]],
        ])->assertSessionHasErrors('items');

        // Produksi valid
        $this->actingAs($pengawas)->post('/produksi', [
            'production_date' => $tgl, 'input_birds' => 40, 'input_weight_kg' => 90,
            'items' => [['product_id' => $p1->id, 'weight_kg' => 60], ['product_id' => $p2->id, 'weight_kg' => 20, 'pieces' => '']],
        ])->assertSessionHasNoErrors();

        $this->assertSame($awal['ekor'] + 55, $this->stok()['ekor']);
        $this->assertEqualsWithDelta(60.0, (float) DB::table('weighing_records')->where('product_id', $p1->id)->sum('weight_kg'), 0.001);
        $this->assertEqualsWithDelta(60.0, (float) $svc->produk(now(), now())->firstWhere('id', $p1->id)->stok, 0.001);

        // Opname ayam hidup: selisih tanpa alasan -> ditolak; dengan alasan -> disimpan.
        // Isian 'system_birds' dari browser harus diabaikan (dihitung server).
        $sis = $this->stok();
        $payload = ['opname_date' => $tgl, 'actual_birds' => $sis['ekor'] - 3, 'actual_kg' => $sis['kg'] - 6, 'system_birds' => 999999];
        $this->actingAs($pengawas)->post('/opname-ayam', $payload)->assertSessionHasErrors('reason');
        $this->actingAs($pengawas)->post('/opname-ayam', $payload + ['reason' => 'Hitung ulang kandang'])->assertSessionHasNoErrors();

        $op = DB::table('live_bird_opnames')->orderByDesc('created_at')->first();
        $this->assertSame($sis['ekor'], (int) $op->system_birds, 'system_birds harus dari server');
        $this->assertSame(-3, (int) $op->diff_birds);
        $this->assertSame($awal['ekor'] + 52, $this->stok()['ekor']);

        // Opname hasil produksi oleh KASIR
        $this->actingAs($kasir)->post('/opname-produk', [
            'adjustment_date' => $tgl, 'fisik' => [$p1->id => 58],
        ])->assertSessionHasErrors('alasan');

        $this->actingAs($kasir)->post('/opname-produk', [
            'adjustment_date' => $tgl, 'fisik' => [$p1->id => 58], 'alasan' => [$p1->id => 'Susut timbang'],
            'system_qty' => 12345,
        ])->assertSessionHasNoErrors();

        $adj = StockAdjustment::where('product_id', $p1->id)->firstOrFail();
        $this->assertEqualsWithDelta(60.0, (float) $adj->system_qty, 0.001);
        $this->assertEqualsWithDelta(-2.0, (float) $adj->difference_qty, 0.001);
        $this->assertEqualsWithDelta(58.0, (float) $svc->produk(now(), now())->firstWhere('id', $p1->id)->stok, 0.001);

        // Id produk palsu -> ditolak
        $this->actingAs($kasir)->post('/opname-produk', [
            'adjustment_date' => $tgl, 'fisik' => [(string) Str::uuid() => 5],
        ])->assertSessionHasErrors('fisik');

        // Angka arus yang dipakai dashboard
        $arus = $svc->arus(now()->startOfDay(), now()->startOfDay());
        $this->assertSame($arusAwal['mati']['e'] + 5, $arus['mati']['e']);
        $this->assertSame($arusAwal['produksi']['e'] + 40, $arus['produksi']['e']);
        $this->assertEqualsWithDelta($arusAwal['produksi']['k'] + 90, $arus['produksi']['k'], 0.01);

        // Dashboard tampil di semua peran, termasuk periode yang tidak valid
        foreach ([$admin, $pengawas, $kasir, $this->user('MANAGER')] as $u) {
            $this->actingAs($u)->get('/dashboard')->assertOk()->assertSee('Stok ayam hidup')->assertSee($p1->name);
            $this->actingAs($u)->get('/dashboard?periode=ngawur')->assertOk();
        }
    }

    // ---------- 4. POS ayam hidup per ekor + void ----------

    public function test_pos_ayam_hidup_per_ekor_stok_dan_void(): void
    {
        $pengawas = $this->user('PENGAWAS_KANDANG');
        $kasir = $this->user('KASIR');
        $admin = $this->user('ADMIN');

        $this->beli($pengawas, 50, 100);

        $hidup = $this->produk(true);
        $tipe = $this->tipePelanggan();
        $this->harga($hidup, $tipe, 50000);
        $awal = $this->stok();
        $dasar = ['customer_type_id' => $tipe, 'payment_method' => 'CASH'];

        // Tanpa berat -> ditolak
        $this->actingAs($kasir)->post('/kasir', $dasar + ['amount_paid' => 150000,
            'items' => [['product_id' => $hidup->id, 'quantity' => 3]]])->assertSessionHasErrors('items');

        // Ekor pecahan -> ditolak
        $this->actingAs($kasir)->post('/kasir', $dasar + ['amount_paid' => 125000,
            'items' => [['product_id' => $hidup->id, 'quantity' => 2.5, 'weight_kg' => 5]]])->assertSessionHasErrors('items');

        // Melebihi stok -> ditolak
        $n = $awal['ekor'] + 1;
        $this->actingAs($kasir)->post('/kasir', $dasar + ['amount_paid' => $n * 50000,
            'items' => [['product_id' => $hidup->id, 'quantity' => $n, 'weight_kg' => 10]]])->assertSessionHasErrors('items');

        $this->assertSame($awal['ekor'], $this->stok()['ekor'], 'Transaksi gagal tidak boleh mengubah stok');

        // Valid (dengan percobaan menitipkan harga dari browser, harus diabaikan)
        $this->actingAs($kasir)->post('/kasir', $dasar + ['amount_paid' => 150000,
            'items' => [['product_id' => $hidup->id, 'quantity' => 3, 'weight_kg' => 6.5, 'unit_price' => 1]]])
            ->assertSessionHasNoErrors();

        $sale = Sale::where('cashier_id', $kasir->id)->latest('created_at')->firstOrFail();
        $this->assertEqualsWithDelta(150000.0, (float) $sale->total_amount, 0.001);
        $this->assertEqualsWithDelta(6.5, (float) DB::table('sale_items')->where('sale_id', $sale->id)->value('weight_kg'), 0.001);
        $this->assertSame($awal['ekor'] - 3, $this->stok()['ekor']);
        $this->assertEqualsWithDelta($awal['kg'] - 6.5, $this->stok()['kg'], 0.01);

        // Void: kasir ditolak, admin berhasil, stok kembali, void ganda ditolak
        $this->actingAs($kasir)->post("/kasir/{$sale->id}/void", ['void_reason' => 'uji batal'])->assertForbidden();
        $this->actingAs($admin)->post("/kasir/{$sale->id}/void", ['void_reason' => 'uji batal'])->assertSessionHasNoErrors();
        $this->assertTrue((bool) $sale->refresh()->is_voided);
        $this->assertSame($awal['ekor'], $this->stok()['ekor'], 'Void harus mengembalikan stok ayam hidup');
        $this->actingAs($admin)->post("/kasir/{$sale->id}/void", ['void_reason' => 'uji batal'])->assertSessionHasErrors('void_reason');
    }

    // ---------- 5. Kebocoran data di dashboard ----------

    public function test_dashboard_tidak_membocorkan_data_antar_peran(): void
    {
        $admin = $this->user('ADMIN');
        $kasir = $this->user('KASIR');
        $pengawas = $this->user('PENGAWAS_KANDANG');
        $manager = $this->user('MANAGER');

        $p = $this->produk();
        $tipe = $this->tipePelanggan();
        $this->harga($p, $tipe, 10000);

        foreach ([$admin, $kasir] as $u) {
            $this->actingAs($u)->post('/kasir', [
                'customer_type_id' => $tipe, 'payment_method' => 'CASH', 'amount_paid' => 10000,
                'items' => [['product_id' => $p->id, 'quantity' => 1]],
            ])->assertSessionHasNoErrors();
        }

        $nAdmin = Sale::where('cashier_id', $admin->id)->value('sale_number');
        $nKasir = Sale::where('cashier_id', $kasir->id)->value('sale_number');
        $this->assertNotEmpty($nAdmin);
        $this->assertNotEmpty($nKasir);

        // Kasir: hanya nota sendiri, tanpa piutang/omzet keseluruhan
        $this->actingAs($kasir)->get('/dashboard')->assertOk()
            ->assertSee($nKasir)->assertDontSee($nAdmin)
            ->assertDontSee('Piutang beredar')->assertSee('Stok ayam hidup');

        // Pengawas: hanya data kandang
        $this->actingAs($pengawas)->get('/dashboard')->assertOk()
            ->assertSee('Stok ayam hidup')
            ->assertDontSee('Omzet')->assertDontSee('Kas masuk')->assertDontSee('Piutang')
            ->assertDontSee('Transaksi terakhir')->assertDontSee($nAdmin)->assertDontSee($nKasir);

        // Manager dan admin: melihat semua
        foreach ([$manager, $admin] as $u) {
            $this->actingAs($u)->get('/dashboard')->assertOk()
                ->assertSee($nAdmin)->assertSee($nKasir)->assertSee('Piutang beredar')->assertSee('Stok ayam hidup');
        }
    }
}