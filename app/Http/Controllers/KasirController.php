<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerType;
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

class KasirController extends Controller
{
    /** Kunci antar-transaksi di PostgreSQL (otomatis lepas saat transaksi selesai). */
    private function kunci(string $kunci): void
    {
        DB::select('select pg_advisory_xact_lock(?)', [(int) crc32($kunci)]);
    }

    public function index()
    {
        $sales = Sale::with('customerType')
            ->withSum(['payments as dibayar' => fn ($q) => $q->whereNull('voided_at')], 'amount')
            ->latest('created_at')
            ->limit(50)
            ->get();

        return view('kasir.index', compact('sales'));
    }

    public function create(PiutangService $piutang)
    {
        $types = CustomerType::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);
        $defaultTypeId = $types->firstWhere('code', 'MASYARAKAT_UMUM')?->id ?? $types->first()?->id;

        $products = Product::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'primary_unit', 'is_live_bird']);

        // Susunan: $prices[customer_type_id][product_id] = harga
        $prices = [];
        ProductPrice::where('is_active', true)
            ->get(['product_id', 'customer_type_id', 'price'])
            ->each(function ($pp) use (&$prices) {
                $prices[$pp->customer_type_id][$pp->product_id] = (float) $pp->price;
            });

        // Pelanggan + sisa hutang + keterlambatan, untuk pencarian di layar kasir
        $ringkasan = $piutang->ringkasanSemua();
        $customers = Customer::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'customer_type_id'])
            ->map(fn ($c) => [
                'id'    => $c->id,
                'name'  => $c->name,
                'type'  => $c->customer_type_id,
                'saldo' => $ringkasan[$c->id]['saldo'] ?? 0,
                'telat' => $ringkasan[$c->id]['terlambat_hari'] ?? 0,
            ])
            ->values();

        $defaultTempo = (int) config('kasir.tempo_hari', 7);

        return view('kasir.create', compact('types', 'defaultTypeId', 'products', 'prices', 'customers', 'defaultTempo'));
    }

    public function store(Request $request, PiutangService $piutang, StokService $stok)
    {
        $data = $request->validate([
            'customer_type_id'   => ['required', 'exists:customer_types,id'],
            'customer_id'        => ['nullable', 'exists:customers,id'],
            'customer_name'      => ['nullable', 'string', 'max:100'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'numeric', 'min:0.01', 'max:99999'],
            'items.*.weight_kg'  => ['nullable', 'numeric', 'min:0', 'max:99999'], // wajib untuk ayam hidup
            'discount'           => ['nullable', 'numeric', 'min:0'],
            'payment_method'     => ['required', 'in:CASH,TRANSFER,HUTANG'],
            'amount_paid'        => ['nullable', 'numeric', 'min:0'],
            'reference_number'   => ['nullable', 'string', 'max:100'],
            'due_days'           => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);

        // Harga selalu diambil dari database, bukan dari isian browser.
        // Produk dan harga diambil sekali jalan, tidak per baris.
        $productIds = collect($data['items'])->pluck('product_id')->unique()->all();

        $products = Product::whereIn('id', $productIds)->where('is_active', true)->get()->keyBy('id');

        $prices = ProductPrice::whereIn('product_id', $productIds)
            ->where('customer_type_id', $data['customer_type_id'])
            ->where('is_active', true)
            ->pluck('price', 'product_id');

        $lines = [];
        $subtotal = 0.0;
        $ekorHidup = 0; // total ayam hidup (ekor) pada nota ini

        foreach ($data['items'] as $row) {
            $product = $products->get($row['product_id']);

            if (! $product) {
                throw ValidationException::withMessages([
                    'items' => 'Ada produk yang sudah tidak aktif. Muat ulang halaman kasir.',
                ]);
            }

            $price = $prices->get($product->id);

            if ($price === null) {
                throw ValidationException::withMessages([
                    'items' => "Produk {$product->name} belum punya harga untuk jenis pelanggan ini.",
                ]);
            }

            $qty = round((float) $row['quantity'], 2);
            $sub = round((float) $price * $qty, 2);
            $subtotal = round($subtotal + $sub, 2);

            // Ayam hidup dijual per ekor: jumlah harus bulat dan berat total wajib diisi
            $berat = null;
            if ($product->is_live_bird) {
                if ($qty < 1 || floor($qty) != $qty) {
                    throw ValidationException::withMessages([
                        'items' => "Jumlah {$product->name} harus bilangan bulat (ekor).",
                    ]);
                }

                $berat = round((float) ($row['weight_kg'] ?? 0), 2);
                if ($berat <= 0) {
                    throw ValidationException::withMessages([
                        'items' => "Isi berat total (kg) untuk {$product->name}.",
                    ]);
                }

                $ekorHidup += (int) $qty;
            }

            $lines[] = [
                'product_id' => $product->id,
                'quantity'   => $qty,
                'unit'       => $product->primary_unit ?? 'KG',
                'unit_price' => (float) $price,
                'subtotal'   => $sub,
                'weight_kg'  => $berat,
            ];
        }

        $discount = round((float) ($data['discount'] ?? 0), 2);

        if ($discount > $subtotal) {
            throw ValidationException::withMessages(['discount' => 'Diskon tidak boleh melebihi subtotal.']);
        }

        $total  = round($subtotal - $discount, 2);
        $metode = $data['payment_method'];
        $paid   = round((float) ($data['amount_paid'] ?? 0), 2);
        $tempo  = (int) ($data['due_days'] ?? config('kasir.tempo_hari', 7));

        $nama = trim((string) ($data['customer_name'] ?? ''));
        $adaNama = $nama !== '' && mb_strtolower($nama) !== 'umum';
        $adaPelanggan = ! empty($data['customer_id']) || $adaNama;

        if ($metode === 'HUTANG' && ! $adaPelanggan) {
            throw ValidationException::withMessages([
                'customer_name' => 'Hutang harus atas nama pelanggan. Isi nama pelanggan dulu.',
            ]);
        }

        if ($metode !== 'HUTANG' && $paid < $total) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Uang dibayar kurang dari total belanja. Pilih "Hutang" kalau pelanggan berhutang.',
            ]);
        }

        [$sale, $kembalian, $sisaHutang] = DB::transaction(
            function () use ($data, $lines, $subtotal, $discount, $total, $metode, $paid, $nama, $adaNama, $tempo, $piutang, $stok, $ekorHidup) {
                $now   = now();
                $today = $now->toDateString();

                // Urutan kunci selalu: nomor nota -> nama pelanggan -> id pelanggan -> stok ayam hidup
                $this->kunci('sale_number_' . $today);

                // Tentukan pelanggan: dipilih dari daftar, cocok nama, atau baru
                $customer = null;
                if (! empty($data['customer_id'])) {
                    $customer = Customer::findOrFail($data['customer_id']);
                } elseif ($adaNama) {
                    // Kunci per nama supaya dua kasir tidak membuat pelanggan kembar
                    $this->kunci('cust_name_' . mb_strtolower($nama));

                    $customer = Customer::whereRaw('lower(name) = ?', [mb_strtolower($nama)])->first()
                        ?? Customer::create([
                            'name'             => $nama,
                            'customer_type_id' => $data['customer_type_id'],
                            'is_active'        => true,
                        ]);
                }

                // Kunci per pelanggan agar hutang tidak salah hitung kalau ada dua kasir
                if ($customer) {
                    $this->kunci('cust_' . $customer->id);
                }

                // Penjualan ayam hidup tidak boleh melebihi stok ekor
                if ($ekorHidup > 0) {
                    $stok->kunci('stok_ayam_hidup');
                    $sisa = $stok->ayamHidup();

                    if ($ekorHidup > $sisa['ekor']) {
                        throw ValidationException::withMessages([
                            'items' => 'Stok ayam hidup tidak cukup (tersisa ' . number_format($sisa['ekor'], 0, ',', '.') . ' ekor).',
                        ]);
                    }
                }

                $hutangLama = $customer ? $piutang->saldo($customer->id) : 0.0;
                $tagihan    = round($total + $hutangLama, 2);

                if ($metode === 'TRANSFER' && $paid > $tagihan + 0.004) {
                    throw ValidationException::withMessages([
                        'amount_paid' => 'Transfer tidak boleh melebihi total tagihan (Rp ' . number_format($tagihan, 0, ',', '.') . ').',
                    ]);
                }

                $dibayar = round(min($paid, $tagihan), 2);

                // Nomor nota: ambil nomor terakhir hari ini lalu tambah satu
                $prefix   = 'INV-' . $now->format('Ymd') . '-';
                $terakhir = Sale::where('sale_number', 'like', $prefix . '%')->max('sale_number');
                $urut     = $terakhir ? ((int) substr($terakhir, strlen($prefix))) + 1 : 1;
                $number   = $prefix . str_pad((string) $urut, 4, '0', STR_PAD_LEFT);

                $sale = Sale::create([
                    'sale_number'      => $number,
                    'sale_date'        => $today,
                    'due_date'         => $customer ? $now->copy()->addDays($tempo)->toDateString() : null,
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

                foreach ($lines as $line) {
                    $sale->items()->create($line);
                }

                // Uang yang dibayar sebagian (saat Hutang) dicatat sebagai tunai
                $metodeBayar = $metode === 'HUTANG' ? 'CASH' : $metode;
                $teralokasi  = 0.0;

                if ($dibayar > 0) {
                    if ($customer) {
                        // Melunasi nota tertua dulu, lalu nota ini
                        $teralokasi = $piutang->bayar(
                            $customer->id,
                            $dibayar,
                            $metodeBayar,
                            $data['reference_number'] ?? null,
                            Auth::id(),
                            $sale->id
                        );
                    } else {
                        Payment::create([
                            'sale_id'            => $sale->id,
                            'payment_method'     => $metodeBayar,
                            'amount'             => $dibayar,
                            'reference_number'   => $data['reference_number'] ?? null,
                            'cashier_id'         => Auth::id(),
                            'settlement_sale_id' => $sale->id,
                        ]);
                        $teralokasi = $dibayar;
                    }
                }

                // Kembalian dihitung dari uang yang benar-benar tercatat
                $kembalian  = round($paid - $teralokasi, 2);
                $sisaHutang = $customer ? $piutang->saldo($customer->id) : 0.0;

                // Jatuh tempo hutang tertua yang masih tersisa, untuk dicetak di nota
                $tertua = ($customer && $sisaHutang > 0.004)
                    ? $piutang->notaTerbuka($customer->id)->min('jatuh_tempo')
                    : null;

                $sale->update([
                    'balance_after'    => $sisaHutang,
                    'balance_due_date' => $tertua,
                ]);

                return [$sale, $kembalian, $sisaHutang];
            }
        );

        $pesan = 'Transaksi tersimpan.';
        if ($kembalian > 0.004) {
            $pesan .= ' Kembalian: Rp ' . number_format($kembalian, 0, ',', '.') . '.';
        }
        if ($sisaHutang > 0.004) {
            $pesan .= ' Sisa hutang pelanggan: Rp ' . number_format($sisaHutang, 0, ',', '.') . '.';
        }

        return redirect()->route('kasir.show', $sale)->with('status', $pesan);
    }

    public function show(Sale $sale)
    {
        $sale->load(['items.product', 'settlementPayments', 'customerType', 'cashier']);

        // Nota aktif: hanya uang yang masih berlaku. Nota batal: tampilkan uang aslinya.
        $dibayar = (float) $sale->settlementPayments
            ->when(! $sale->is_voided, fn ($c) => $c->whereNull('voided_at'))
            ->sum('amount');

        return view('kasir.show', compact('sale', 'dibayar'));
    }

    public function void(Request $request, Sale $sale)
    {
        abort_unless(Auth::user()->isAdmin(), 403, 'Hanya admin yang boleh membatalkan transaksi.');

        $data = $request->validate([
            'void_reason' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        DB::transaction(function () use ($sale, $data) {
            // Kunci per pelanggan, sama seperti saat transaksi dibuat
            if ($sale->customer_id) {
                $this->kunci('cust_' . $sale->customer_id);
            }

            $sale = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if ($sale->is_voided) {
                throw ValidationException::withMessages(['void_reason' => 'Transaksi ini sudah dibatalkan.']);
            }

            // Nota ini sudah dibayar lewat transaksi lain / halaman Piutang -> perlu penanganan manual
            $dibayarTerpisah = Payment::where('sale_id', $sale->id)
                ->whereNull('voided_at')
                ->where(function ($q) use ($sale) {
                    $q->whereNull('settlement_sale_id')
                      ->orWhere('settlement_sale_id', '!=', $sale->id);
                })
                ->exists();

            if ($dibayarTerpisah) {
                throw ValidationException::withMessages([
                    'void_reason' => 'Nota ini sudah dibayar lewat transaksi lain. Tidak bisa dibatalkan otomatis karena ada uang yang harus dikembalikan.',
                ]);
            }

            // Balik semua uang yang diterima di nota ini (termasuk yang melunasi nota lama)
            Payment::where('settlement_sale_id', $sale->id)
                ->whereNull('voided_at')
                ->update(['voided_at' => now()]);

            $sale->update([
                'is_voided'    => true,
                'status'       => 'VOIDED',
                'void_reason'  => $data['void_reason'],
                'voided_at'    => now(),
                'voided_by_id' => Auth::id(),
            ]);
        });

        return redirect()->route('kasir.show', $sale)->with('status', 'Transaksi dibatalkan.');
    }

    public function simpanPelanggan(\Illuminate\Http\Request $request)
    {
        abort_unless($request->user()->canWrite('kasir'), 403);

        $data = $request->validate([
            'name'             => ['required', 'string', 'max:100'],
            'phone'            => ['nullable', 'string', 'max:30'],
            'customer_type_id' => ['required', 'exists:customer_types,id'],
        ]);

        $c = \App\Models\Customer::firstOrCreate(
            ['name' => $data['name']],
            ['phone' => $data['phone'] ?? null, 'customer_type_id' => $data['customer_type_id']]
        );

        return response()->json([
            'id'    => (string) $c->id,
            'name'  => $c->name,
            'type'  => (string) $c->customer_type_id,
            'saldo' => 0,
            'telat' => 0,
        ]);
    }}
