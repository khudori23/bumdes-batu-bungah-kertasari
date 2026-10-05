<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\KitchenOrder;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\Sale;
use App\Services\PiutangService;
use App\Services\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KitchenOrderController extends Controller
{
    public function index()
    {
        $orders = KitchenOrder::with('customer:id,name')
            ->withCount('lines')
            ->orderByDesc('order_date')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('kasir.dapur.index', compact('orders'));
    }

    public function create()
    {
        abort_unless(Auth::user()->canWrite('kasir'), 403);

        $customers = Customer::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $produk = Product::where('is_active', true)->where('is_live_bird', false)
            ->orderBy('name')->get(['id', 'name']);

        // Hanya saran isian: bobot ayam hidup yang masuk produksi hari ini dan harga per kg terakhir
        $saranBerat = ProductionBatch::where('production_date', today()->toDateString())->value('input_weight_kg');
        $saranHarga = KitchenOrder::whereNotNull('price_per_kg')->orderByDesc('created_at')->value('price_per_kg');

        return view('kasir.dapur.create', compact('customers', 'produk', 'saranBerat', 'saranHarga'));
    }

    public function store(Request $request, PiutangService $piutang, StokService $stok)
    {
        abort_unless(Auth::user()->canWrite('kasir'), 403);

        $data = $request->validate([
            'order_date'           => ['required', 'date', 'before_or_equal:today'],
            'customer_id'          => ['required', 'uuid', 'exists:customers,id'],
            'live_weight_kg'       => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'price_per_kg'         => ['required', 'numeric', 'min:0', 'max:100000000'],
            'notes'                => ['nullable', 'string', 'max:255'],
            'lines'                => ['required', 'array', 'min:1'],
            'lines.*.kitchen_name' => ['required', 'string', 'max:100'],
            'lines.*.product_id'   => ['required', 'uuid', 'exists:products,id'],
            'lines.*.quantity_kg'  => ['required', 'numeric', 'min:0.01', 'max:1000000'],
        ]);

        $ids = collect($data['lines'])->pluck('product_id')->unique()->all();
        $valid = Product::whereIn('id', $ids)->where('is_active', true)->where('is_live_bird', false)->count();
        if ($valid !== count($ids)) {
            throw ValidationException::withMessages([
                'lines' => 'Ada produk yang tidak valid atau tidak aktif. Muat ulang halaman.',
            ]);
        }

        $order = DB::transaction(function () use ($data, $piutang, $stok) {
            $now     = now();
            $tanggal = Carbon::parse($data['order_date']);
            $ymd     = $tanggal->format('Ymd');

            // Urutan kunci: nomor pesanan -> nomor nota -> pelanggan (sama dengan kasir)
            $stok->kunci('dapur_number_' . $ymd);
            $stok->kunci('sale_number_' . $now->toDateString());
            $stok->kunci('cust_' . $data['customer_id']);

            $customer = Customer::findOrFail($data['customer_id']);

            $berat = round((float) $data['live_weight_kg'], 2);
            $harga = round((float) $data['price_per_kg'], 2);
            $total = round($berat * $harga, 2);

            $hutangLama = $piutang->saldo($customer->id);

            // Nomor nota (format sama dengan kasir)
            $prefixInv = 'INV-' . $now->format('Ymd') . '-';
            $terakhir  = Sale::where('sale_number', 'like', $prefixInv . '%')->max('sale_number');
            $urutInv   = $terakhir ? ((int) substr($terakhir, strlen($prefixInv))) + 1 : 1;
            $nomorNota = $prefixInv . str_pad((string) $urutInv, 4, '0', STR_PAD_LEFT);

            $tempo = (int) config('kasir.tempo_hari', 7);

            // Tagihan masuk tabel penjualan tanpa item, jadi ikut hutang tapi tidak mengubah stok produk
            $sale = Sale::create([
                'sale_number'      => $nomorNota,
                'sale_date'        => $tanggal->toDateString(),
                'due_date'         => $tanggal->copy()->addDays($tempo)->toDateString(),
                'customer_id'      => $customer->id,
                'customer_name'    => $customer->name,
                'customer_type_id' => $customer->customer_type_id,
                'subtotal'         => $total,
                'discount'         => 0,
                'total_amount'     => $total,
                'previous_balance' => $hutangLama,
                'balance_after'    => 0,
                'status'           => 'COMPLETED',
                'cashier_id'       => Auth::id(),
            ]);

            $sisa   = $piutang->saldo($customer->id);
            $tertua = $sisa > 0.004 ? $piutang->notaTerbuka($customer->id)->min('jatuh_tempo') : null;
            $sale->update(['balance_after' => $sisa, 'balance_due_date' => $tertua]);

            $batch = ProductionBatch::where('production_date', $tanggal->toDateString())
                ->orderBy('created_at')->first();

            $prefix = 'PD-' . $ymd . '-';
            $akhir  = KitchenOrder::where('order_number', 'like', $prefix . '%')->max('order_number');
            $urut   = $akhir ? ((int) substr($akhir, strlen($prefix))) + 1 : 1;

            $order = KitchenOrder::create([
                'order_number'        => $prefix . str_pad((string) $urut, 4, '0', STR_PAD_LEFT),
                'order_date'          => $tanggal->toDateString(),
                'customer_id'         => $customer->id,
                'production_batch_id' => $batch?->id,
                'live_weight_kg'      => $berat,
                'price_per_kg'        => $harga,
                'total_amount'        => $total,
                'status'              => 'CONFIRMED',
                'notes'               => $data['notes'] ?? null,
                'sale_id'             => $sale->id,
                'created_by_id'       => Auth::id(),
            ]);

            foreach ($data['lines'] as $row) {
                $order->lines()->create([
                    'kitchen_name' => trim($row['kitchen_name']),
                    'product_id'   => $row['product_id'],
                    'quantity_kg'  => round((float) $row['quantity_kg'], 2),
                ]);
            }

            return $order;
        });

        return redirect()->route('kasir.dapur.show', $order)
            ->with('status', 'Pesanan dapur tersimpan. Tagihan Rp ' . number_format((float) $order->total_amount, 0, ',', '.') . '.');
    }

    public function show(KitchenOrder $order)
    {
        $order->load(['customer', 'lines.product', 'creator', 'sale']);

        $dibayar = $order->sale_id
            ? (float) Payment::where('sale_id', $order->sale_id)->whereNull('voided_at')->sum('amount')
            : 0.0;

        return view('kasir.dapur.show', compact('order', 'dibayar'));
    }

    public function void(Request $request, KitchenOrder $order)
    {
        abort_unless(Auth::user()->isAdmin(), 403, 'Hanya admin yang boleh membatalkan pesanan dapur.');

        $data = $request->validate([
            'void_reason' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        DB::transaction(function () use ($order, $data) {
            DB::select('select pg_advisory_xact_lock(?)', [(int) crc32('cust_' . $order->customer_id)]);

            $order = KitchenOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->status === 'VOIDED') {
                throw ValidationException::withMessages(['void_reason' => 'Pesanan ini sudah dibatalkan.']);
            }

            $sale = $order->sale_id ? Sale::whereKey($order->sale_id)->lockForUpdate()->first() : null;

            if ($sale) {
                $adaBayar = Payment::where('sale_id', $sale->id)->whereNull('voided_at')->exists();
                if ($adaBayar) {
                    throw ValidationException::withMessages([
                        'void_reason' => 'Tagihan ini sudah ada pembayaran. Tidak bisa dibatalkan otomatis karena ada uang yang harus dikembalikan.',
                    ]);
                }

                $sale->update([
                    'is_voided'    => true,
                    'status'       => 'VOIDED',
                    'void_reason'  => $data['void_reason'],
                    'voided_at'    => now(),
                    'voided_by_id' => Auth::id(),
                ]);
            }

            $order->update(['status' => 'VOIDED']);
        });

        return redirect()->route('kasir.dapur.show', $order)->with('status', 'Pesanan dapur dibatalkan.');
    }
}