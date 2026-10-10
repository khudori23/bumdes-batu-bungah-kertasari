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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Nota kasir untuk ayam hidup ecer yang sudah dicatat pengawas.
 * Jumlah ekor dan berat diambil dari catatan pengawas. Stok tidak dicek lagi di sini
 * karena sudah berkurang saat pengawas mencatat, dan tidak boleh terhitung dua kali.
 */
class KasirAyamHidupController extends Controller
{
    private function kunci(string $kunci): void
    {
        DB::select('select pg_advisory_xact_lock(?)', [(int) crc32($kunci)]);
    }

    /** Catatan pengawas tanpa nota aktif (belum dinota, atau notanya dibatalkan). */
    private function belumDinota()
    {
        return LiveBirdSale::query()
            ->leftJoin('sales as s', 's.id', '=', 'live_bird_sales.sale_id')
            ->where(fn ($q) => $q->whereNull('live_bird_sales.sale_id')->orWhere(fn ($v) => $v->where('s.is_voided', true)->where(fn ($z) => $z->whereNull('live_bird_sales.sumber')->orWhere('live_bird_sales.sumber', '!=', 'KASIR'))))
            ->select('live_bird_sales.*');
    }

    private function produk(): Product
    {
        $p = Product::where('is_live_bird', true)->where('is_active', true)->first();
        abort_if(! $p, 409, 'Produk Ayam Hidup belum dibuat atau tidak aktif. Hubungi admin.');

        return $p;
    }

    public function index()
    {
        abort_unless(Auth::user()->canView('kasir'), 403);

        $daftar = $this->belumDinota()
            ->orderBy('live_bird_sales.sale_date')->orderBy('live_bird_sales.created_at')
            ->get();

        if ($daftar->isEmpty()) {
            return redirect()->route('kasir.ayamhidup.langsung');
        }

        $sudah = DB::table('live_bird_sales as l')
            ->join('sales as s', 's.id', '=', 'l.sale_id')
            ->where('s.is_voided', false)
            ->orderByDesc('l.sale_date')->orderByDesc('l.created_at')
            ->limit(20)
            ->get(['l.sale_date', 'l.customer_name', 'l.birds', 'l.weight_kg', 's.id as sale_id', 's.sale_number']);

        return view('kasir.ayam-hidup.index', compact('daftar', 'sudah'));
    }

    public function create(string $rec, PiutangService $piutang)
    {
        abort_unless(Auth::user()->canWrite('kasir'), 403);

        $catatan = $this->belumDinota()->where('live_bird_sales.id', $rec)->firstOrFail();
        $produk  = $this->produk();

        $types = CustomerType::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);
        $defaultTypeId = $types->firstWhere('code', 'MASYARAKAT_UMUM')?->id ?? $types->first()?->id;

        $harga = ProductPrice::where('product_id', $produk->id)->where('is_active', true)
            ->pluck('price', 'customer_type_id');

        $nama = mb_strtolower(trim((string) $catatan->customer_name));
        $pelanggan = $nama !== '' ? Customer::whereRaw('lower(name) = ?', [$nama])->first() : null;
        $saldo = $pelanggan ? $piutang->saldo($pelanggan->id) : 0.0;
        $defaultTempo = (int) config('kasir.tempo_hari', 7);

        return view('kasir.ayam-hidup.create', compact('catatan', 'types', 'defaultTypeId', 'harga', 'saldo', 'defaultTempo'));
    }

    public function store(Request $request, PiutangService $piutang, string $rec)
    {
        abort_unless(Auth::user()->canWrite('kasir'), 403);

        $data = $request->validate([
            'customer_type_id' => ['required', 'exists:customer_types,id'],
            'sale_date'        => \App\Support\Pencatatan::aturan(false),
            'discount'         => ['nullable', 'numeric', 'min:0'],
            'payment_method'   => ['required', 'in:CASH,TRANSFER,HUTANG'],
            'amount_paid'      => ['nullable', 'numeric', 'min:0'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'due_days'         => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);

        [$sale, $kembalian, $sisaHutang] = DB::transaction(function () use ($rec, $data, $piutang) {
            $produk  = $this->produk();
            $tglJual = \App\Support\Pencatatan::tanggal($data['sale_date'] ?? null);
            $today   = $tglJual->toDateString();

            // Urutan kunci: nomor nota -> catatan pengawas -> nama pelanggan -> id pelanggan
            $this->kunci('sale_number_' . $today);

            $catatan = LiveBirdSale::whereKey($rec)->lockForUpdate()->firstOrFail();

            if ($catatan->sale_id && ! Sale::whereKey($catatan->sale_id)->value('is_voided')) {
                throw ValidationException::withMessages(['payment_method' => 'Catatan ini sudah dibuatkan nota.']);
            }

            $harga = (float) ProductPrice::where('product_id', $produk->id)
                ->where('customer_type_id', $data['customer_type_id'])
                ->where('is_active', true)->value('price');

            if ($harga <= 0) {
                throw ValidationException::withMessages([
                    'customer_type_id' => 'Harga Ayam Hidup untuk jenis pelanggan ini belum diatur.',
                ]);
            }

            $ekor     = (int) $catatan->birds;
            $berat    = round((float) $catatan->weight_kg, 2);
            $subtotal = round($harga * $berat, 0); // harga per KG x berat total (BUKAN per ekor)
            $discount = round((float) ($data['discount'] ?? 0), 2);

            if ($discount > $subtotal) {
                throw ValidationException::withMessages(['discount' => 'Diskon tidak boleh melebihi subtotal.']);
            }

            $total  = round($subtotal - $discount, 2);
            $metode = $data['payment_method'];
            $paid   = round((float) ($data['amount_paid'] ?? 0), 2);
            $tempo  = (int) ($data['due_days'] ?? config('kasir.tempo_hari', 7));

            $nama    = trim((string) $catatan->customer_name);
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

            if ($metode === 'HUTANG' && ! $customer) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Hutang harus atas nama pelanggan, sedangkan catatan ini atas nama "Umum".',
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

            // Sambungkan catatan pengawas ke nota ini: stok kini dihitung dari nota, bukan dari catatan
            $catatan->update(['sale_id' => $sale->id]);

            AuditLog::create([
                'action'     => 'UPDATE',
                'table_name' => 'live_bird_sales',
                'record_id'  => $catatan->id,
                'new_values' => ['sale_id' => $sale->id, 'sale_number' => $sale->sale_number],
                'user_id'    => Auth::id(),
            ]);

            \App\Support\Pencatatan::catatMundur('sales', (string) $sale->id, $tglJual, ['sale_number' => $sale->sale_number]);

            return [$sale, $kembalian, $sisaHutang];
        });

        $pesan = 'Nota ayam ecer tersimpan.';
        if ($kembalian > 0.004) {
            $pesan .= ' Kembalian: Rp ' . number_format($kembalian, 0, ',', '.') . '.';
        }
        if ($sisaHutang > 0.004) {
            $pesan .= ' Sisa hutang pelanggan: Rp ' . number_format($sisaHutang, 0, ',', '.') . '.';
        }

        return redirect()->route('kasir.show', $sale)->with('status', $pesan);
    }
}