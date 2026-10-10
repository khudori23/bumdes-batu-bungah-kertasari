<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\LiveBirdSale;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Sale;
use App\Services\PiutangService;
use App\Services\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Kasir menjual ayam ecer langsung: ekor, berat, harga, dan pembayaran dalam satu form.
 * Satu transaksi membuat nota + baris live_bird_sales (sale_id langsung terisi, sumber KASIR).
 * Stok dihitung dari nota (sale_items), jadi tidak ada jalur kedua yang mengurangi stok.
 * Pengawas memverifikasi sesudahnya (lihat VerifikasiAyamController).
 */
class KasirAyamLangsungController extends Controller
{
    private function kunci(string $kunci): void
    {
        DB::select('select pg_advisory_xact_lock(?)', [(int) crc32($kunci)]);
    }

    private function produk(): Product
    {
        $p = Product::where('is_live_bird', true)->where('is_active', true)->first();
        abort_if(! $p, 409, 'Produk Ayam Hidup belum dibuat atau tidak aktif. Hubungi admin.');

        return $p;
    }

    public function create(PiutangService $piutang, StokService $stok)
    {
        abort_unless(Auth::user()->canWrite('kasir'), 403);

        $produk = $this->produk();
        $types = CustomerType::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);
        $defaultTypeId = $types->firstWhere('code', 'MASYARAKAT_UMUM')?->id ?? $types->first()?->id;

        $harga = ProductPrice::where('product_id', $produk->id)->where('is_active', true)
            ->pluck('price', 'customer_type_id');

        $ringkasan = $piutang->ringkasanSemua();
        $pelanggan = Customer::where('is_active', true)->orderBy('name')
            ->get(['id', 'name', 'customer_type_id'])
            ->map(fn ($c) => [
                'name'  => $c->name,
                'type'  => (string) $c->customer_type_id,
                'saldo' => (float) ($ringkasan[$c->id]['saldo'] ?? 0),
            ])->values();

        $sisaStok = $stok->ayamHidup();
        $defaultTempo = (int) config('kasir.tempo_hari', 7);

        return view('kasir.ayam-hidup.langsung', compact('types', 'defaultTypeId', 'harga', 'pelanggan', 'sisaStok', 'defaultTempo'));
    }

    public function store(Request $request, PiutangService $piutang, StokService $stok)
    {
        abort_unless(Auth::user()->canWrite('kasir'), 403);

        $data = $request->validate([
            'customer_name'    => ['required', 'string', 'max:100'],
            'birds'            => ['required', 'integer', 'min:1', 'max:99999'],
            'weight_kg'        => ['required', 'numeric', 'min:0.01', 'max:99999'],
            'notes'            => ['nullable', 'string', 'max:255'],
            'customer_type_id' => ['required', 'exists:customer_types,id'],
            'sale_date'        => \App\Support\Pencatatan::aturan(false),
            'discount'         => ['nullable', 'numeric', 'min:0'],
            'payment_method'   => ['required', 'in:CASH,TRANSFER,HUTANG'],
            'amount_paid'      => ['nullable', 'numeric', 'min:0'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'due_days'         => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);

        [$sale, $kembalian, $sisaHutang] = DB::transaction(function () use ($data, $piutang, $stok) {
            $produk  = $this->produk();
            $tglJual = \App\Support\Pencatatan::tanggal($data['sale_date'] ?? null);
            $today   = $tglJual->toDateString();

            // Urutan kunci (sama dengan KasirController): nomor nota -> nama pelanggan -> id pelanggan -> stok ayam hidup
            $this->kunci('sale_number_' . $today);

            $nama    = trim((string) $data['customer_name']);
            $adaNama = $nama !== '' && mb_strtolower($nama) !== 'umum';

            $customer = null;
            if ($adaNama) {
                $this->kunci('cust_name_' . mb_strtolower($nama));

                $customer = Customer::whereRaw('lower(name) = ?', [mb_strtolower($nama)])->first()
                    ?? Customer::create([
                        'name'             => $nama,
                        'customer_type_id' => $data['customer_type_id'],
                        'is_active'        => true,
                    ]);

                $this->kunci('cust_' . $customer->id);
            }

            $ekor  = (int) $data['birds'];
            $berat = round((float) $data['weight_kg'], 2);

            // Stok dicek sebelum nota dibuat, jadi belum ada yang terhitung dua kali
            $stok->kunci('stok_ayam_hidup');
            $sisa = $stok->ayamHidup();
            if ($ekor > $sisa['ekor']) {
                throw ValidationException::withMessages([
                    'birds' => 'Stok ayam hidup tidak cukup (tersisa ' . number_format($sisa['ekor'], 0, ',', '.') . ' ekor).',
                ]);
            }

            $harga = (float) ProductPrice::where('product_id', $produk->id)
                ->where('customer_type_id', $data['customer_type_id'])
                ->where('is_active', true)->value('price');

            if ($harga <= 0) {
                throw ValidationException::withMessages([
                    'customer_type_id' => 'Harga Ayam Hidup untuk jenis pelanggan ini belum diatur.',
                ]);
            }

            $subtotal = round($harga * $ekor, 2);
            $discount = round((float) ($data['discount'] ?? 0), 2);

            if ($discount > $subtotal) {
                throw ValidationException::withMessages(['discount' => 'Diskon tidak boleh melebihi subtotal.']);
            }

            $total  = round($subtotal - $discount, 2);
            $metode = $data['payment_method'];
            $paid   = round((float) ($data['amount_paid'] ?? 0), 2);
            $tempo  = (int) ($data['due_days'] ?? config('kasir.tempo_hari', 7));

            if ($metode === 'HUTANG' && ! $customer) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Hutang harus atas nama pelanggan. Isi nama pelanggan (bukan "Umum").',
                ]);
            }

            if ($metode !== 'HUTANG' && $paid < $total) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'Uang dibayar kurang dari total. Pilih "Hutang" kalau pelanggan berhutang.',
                ]);
            }

            $hutangLama = $customer ? $piutang->saldo($customer->id) : 0.0;
            $tagihan    = round($total + $hutangLama, 2);

            if ($metode === 'TRANSFER' && $paid > $tagihan + 0.004) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'Transfer tidak boleh melebihi total tagihan (Rp ' . number_format($tagihan, 0, ',', '.') . ').',
                ]);
            }

            $dibayar = round(min($paid, $tagihan), 2);

            $prefix   = 'INV-' . $tglJual->format('Ymd') . '-';
            $terakhir = Sale::where('sale_number', 'like', $prefix . '%')->max('sale_number');
            $urut     = $terakhir ? ((int) substr($terakhir, strlen($prefix))) + 1 : 1;
            $number   = $prefix . str_pad((string) $urut, 4, '0', STR_PAD_LEFT);

            $sale = Sale::create([
                'sale_number'      => $number,
                'sale_date'        => $today,
                'due_date'         => $customer ? $tglJual->copy()->addDays($tempo)->toDateString() : null,
                'customer_id'      => $customer?->id,
                'customer_name'    => $customer?->name ?? 'Umum',
                'customer_type_id' => $data['customer_type_id'],
                'subtotal'         => $subtotal,
                'discount'         => $discount,
                'total_amount'     => $total,
                'previous_balance' => $hutangLama,
                'balance_after'    => 0,
                'status'           => 'COMPLETED',
                'cashier_id'       => Auth::id(),
            ]);

            $sale->items()->create([
                'product_id' => $produk->id,
                'quantity'   => $ekor,
                'unit'       => $produk->primary_unit ?? 'EKOR',
                'unit_price' => $harga,
                'subtotal'   => $subtotal,
                'weight_kg'  => $berat,
            ]);

            $metodeBayar = $metode === 'HUTANG' ? 'CASH' : $metode;
            $teralokasi  = 0.0;

            if ($dibayar > 0) {
                if ($customer) {
                    $teralokasi = $piutang->bayar(
                        $customer->id,
                        $dibayar,
                        $metodeBayar,
                        $data['reference_number'] ?? null,
                        Auth::id(), $sale->id, $tglJual);
                } else {
                    Payment::create([
                        'sale_id'            => $sale->id,
                        'payment_method'     => $metodeBayar,
                        'amount'             => $dibayar,
                        'reference_number'   => $data['reference_number'] ?? null,
                        'cashier_id'         => Auth::id(),
                        'settlement_sale_id' => $sale->id,
                        'created_at'         => \App\Support\Pencatatan::waktu($tglJual),
                    ]);
                    $teralokasi = $dibayar;
                }
            }

            $kembalian  = round($paid - $teralokasi, 2);
            $sisaHutang = $customer ? $piutang->saldo($customer->id) : 0.0;

            $tertua = ($customer && $sisaHutang > 0.004)
                ? $piutang->notaTerbuka($customer->id)->min('jatuh_tempo')
                : null;

            $sale->update([
                'balance_after'    => $sisaHutang,
                'balance_due_date' => $tertua,
            ]);

            // Baris catatan ayam ecer: langsung tersambung ke nota, sumbernya KASIR
            $rec = LiveBirdSale::create([
                'sale_date'     => $today,
                'customer_name' => $customer?->name ?? 'Umum',
                'birds'         => $ekor,
                'weight_kg'     => $berat,
                'notes'         => $data['notes'] ?? null,
                'sale_id'       => $sale->id,
                'sumber'        => 'KASIR',
                'created_by_id' => Auth::id(),
            ]);

            AuditLog::create([
                'action'     => 'CREATE',
                'table_name' => 'live_bird_sales',
                'record_id'  => $rec->id,
                'new_values' => ['sale_id' => $sale->id, 'sale_number' => $sale->sale_number, 'birds' => $ekor, 'weight_kg' => $berat, 'sumber' => 'KASIR'],
                'user_id'    => Auth::id(),
            ]);

            \App\Support\Pencatatan::catatMundur('sales', (string) $sale->id, $tglJual, ['sale_number' => $sale->sale_number]);

            return [$sale, $kembalian, $sisaHutang];
        });

        $pesan = 'Penjualan ayam ecer tersimpan.';
        if ($kembalian > 0.004) {
            $pesan .= ' Kembalian: Rp ' . number_format($kembalian, 0, ',', '.') . '.';
        }
        if ($sisaHutang > 0.004) {
            $pesan .= ' Sisa hutang pelanggan: Rp ' . number_format($sisaHutang, 0, ',', '.') . '.';
        }

        return redirect()->route('kasir.show', $sale)->with('status', $pesan);
    }
}