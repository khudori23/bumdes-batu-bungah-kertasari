<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HanyaAdmin;
use App\Models\CustomerType;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProdukController extends Controller
{
    use HanyaAdmin;

    /**
     * Melihat daftar produk dan harga: admin, manager, dan kasir.
     */
    private function bolehLihat(): void
    {
        $u = Auth::user();
        abort_unless(
            $u && ($u->isAdmin() || $u->isManager() || $u->roleName() === 'KASIR'),
            403,
            'Anda tidak memiliki akses ke halaman ini.'
        );
    }

    /**
     * Mengubah produk dan harga: admin dan kasir. Manager hanya melihat.
     */
    private function bolehUbah(): void
    {
        $u = Auth::user();
        abort_unless(
            $u && ($u->isAdmin() || $u->roleName() === 'KASIR'),
            403,
            'Anda tidak memiliki akses untuk mengubah produk dan harga.'
        );
    }

    public function index()
    {
        $this->bolehLihat();

        $harga = ProductPrice::where('is_active', true)->get()
            ->groupBy('product_id')->map(fn ($g) => $g->keyBy('customer_type_id'));

        return view('produk.index', [
            'produk' => Product::orderByDesc('is_active')->orderBy('name')->get(),
            'tipe'   => CustomerType::where('is_active', true)->orderBy('name')->get(),
            'harga'  => $harga,
        ]);
    }

    public function store(Request $request)
    {
        $this->bolehUbah();

        $request->merge(['code' => strtoupper(trim((string) $request->input('code', '')))]);
        $d = $request->validate([
            'code'        => ['required', 'regex:/^[A-Z0-9][A-Z0-9\-]{1,29}$/', 'unique:products,code'],
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ], [
            'code.regex'  => 'Kode hanya boleh huruf besar, angka, dan tanda minus (2 sampai 30 karakter).',
            'code.unique' => 'Kode produk sudah dipakai.',
        ]);

        $p = Product::create([
            'code' => $d['code'], 'name' => trim($d['name']), 'primary_unit' => 'KG',
            'description' => $d['description'] ?? null, 'is_active' => true,
        ]);
        AuditService::catat('CREATE', 'products', $p->id, null, ['code' => $p->code, 'name' => $p->name]);

        return redirect()->route('produk.edit', $p->id)->with('status', 'Produk dibuat. Atur harganya di bawah.');
    }

    public function edit(string $id)
    {
        $this->bolehUbah();
        $produk = Product::findOrFail($id);

        return view('produk.edit', [
            'produk'  => $produk,
            'tipe'    => CustomerType::where('is_active', true)->orderBy('name')->get(),
            'aktif'   => ProductPrice::where('product_id', $produk->id)->where('is_active', true)->get()->keyBy('customer_type_id'),
            'namaTipe' => CustomerType::pluck('name', 'id'),
            'riwayat' => ProductPrice::where('product_id', $produk->id)->orderByDesc('effective_date')->orderByDesc('created_at')->limit(20)->get(),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $this->bolehUbah();
        $produk = Product::findOrFail($id);

        $d = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
        $lama = ['name' => $produk->name, 'description' => $produk->description, 'is_active' => (bool) $produk->is_active];

        $produk->name = trim($d['name']);
        $produk->description = $d['description'] ?? null;
        $produk->is_active = $request->boolean('is_active');
        $produk->save();

        AuditService::catat('UPDATE', 'products', $produk->id, $lama, [
            'name' => $produk->name, 'description' => $produk->description, 'is_active' => (bool) $produk->is_active,
        ]);

        return redirect()->route('produk.edit', $produk->id)->with('status', 'Data produk tersimpan.');
    }

    /**
     * Ubah harga per jenis pelanggan. Harga lama TIDAK ditimpa: barisnya dinonaktifkan
     * (jadi riwayat), lalu dibuat baris baru. Kunci yang tidak dikirim = tidak diubah.
     * Kunci yang dikirim kosong = harga untuk jenis itu dihapus.
     */
    public function harga(Request $request, string $id)
    {
        $this->bolehUbah();
        $produk = Product::findOrFail($id);

        $request->validate([
            'harga'   => ['nullable', 'array'],
            'harga.*' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
        ], [
            'harga.*.numeric' => 'Harga harus berupa angka.',
            'harga.*.min'     => 'Harga tidak boleh negatif.',
            'harga.*.max'     => 'Harga terlalu besar.',
        ]);

        $input = (array) $request->input('harga', []);
        $ubah = [];

        DB::transaction(function () use ($produk, $input, &$ubah) {
            $this->kunci('harga_' . $produk->id);
            $aktif = ProductPrice::where('product_id', $produk->id)->where('is_active', true)->get()->groupBy('customer_type_id');

            foreach (CustomerType::where('is_active', true)->get() as $t) {
                if (! array_key_exists($t->id, $input)) {
                    continue;
                }
                $baris = $aktif->get($t->id, collect());
                $lama = $baris->isNotEmpty() ? (float) $baris->first()->price : null;
                $raw = $input[$t->id];

                if ($raw === null || $raw === '') {
                    if ($lama !== null) {
                        ProductPrice::where('product_id', $produk->id)->where('customer_type_id', $t->id)
                            ->where('is_active', true)->update(['is_active' => false]);
                        $ubah[$t->code] = ['dari' => $lama, 'ke' => null];
                    }
                    continue;
                }

                $baru = round((float) $raw, 2);
                if ($lama !== null && $baris->count() === 1 && abs($lama - $baru) < 0.005) {
                    continue;
                }

                ProductPrice::where('product_id', $produk->id)->where('customer_type_id', $t->id)
                    ->where('is_active', true)->update(['is_active' => false]);
                ProductPrice::create([
                    'product_id' => $produk->id, 'customer_type_id' => $t->id,
                    'price' => $baru, 'effective_date' => now(), 'is_active' => true,
                ]);
                $ubah[$t->code] = ['dari' => $lama, 'ke' => $baru];
            }

            if ($ubah) {
                AuditService::catat('UPDATE', 'product_prices', $produk->id, null, $ubah);
            }
        });

        return redirect()->route('produk.edit', $produk->id)
            ->with('status', $ubah ? count($ubah) . ' harga diperbarui. Harga lama tersimpan di riwayat.' : 'Tidak ada harga yang berubah.');
    }
}