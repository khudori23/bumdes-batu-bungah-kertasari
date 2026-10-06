<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PurchaseTransaction;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PembelianController extends Controller
{
    /** Kunci antar-transaksi di PostgreSQL (otomatis lepas saat transaksi selesai). */
    private function kunci(string $kunci): void
    {
        DB::select('select pg_advisory_xact_lock(?)', [(int) crc32($kunci)]);
    }

    /** Manager hanya membaca (sesuai PRD). */
    private function bolehTulis(): bool
    {
        return strtoupper((string) Auth::user()->role) !== 'MANAGER';
    }

    private function wajibBolehTulis(): void
    {
        abort_unless($this->bolehTulis(), 403, 'Manager tidak dapat mencatat pembelian.');
    }

    public function index(Request $request)
    {
        $filter = $request->validate([
            'dari'        => ['nullable', 'date'],
            'sampai'      => ['nullable', 'date'],
            'supplier_id' => ['nullable', 'uuid'],
        ]);

        $query = PurchaseTransaction::query()
            ->when($filter['dari'] ?? null, fn ($q, $v) => $q->where('purchase_date', '>=', $v))
            ->when($filter['sampai'] ?? null, fn ($q, $v) => $q->where('purchase_date', '<=', $v))
            ->when($filter['supplier_id'] ?? null, fn ($q, $v) => $q->where('supplier_id', $v));

        // Ringkasan dihitung dari semua data hasil filter, bukan hanya halaman ini
        $ringkasan = (clone $query)
            ->selectRaw('count(*) as jumlah, coalesce(sum(total_birds), 0) as ekor, coalesce(sum(total_weight_kg), 0) as kg, coalesce(sum(total_cost), 0) as biaya')
            ->first();

        $items = (clone $query)
            ->with('supplier:id,code,name')
            ->orderByDesc('purchase_date')
            ->orderByDesc('transaction_number')
            ->simplePaginate(25)
            ->withQueryString();

        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);

        return view('pembelian.index', [
            'items'      => $items,
            'ringkasan'  => $ringkasan,
            'suppliers'  => $suppliers,
            'filter'     => $filter,
            'bolehTulis' => $this->bolehTulis(),
        ]);
    }

    public function create()
    {
        $this->wajibBolehTulis();

        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);

        return view('pembelian.create', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $this->wajibBolehTulis();

        $data = $request->validate([
            'supplier_id'       => ['bail', 'required', 'uuid', 'exists:suppliers,id'],
            'purchase_date'     => \App\Support\Pencatatan::aturan(),
            'total_birds'       => ['required', 'integer', 'min:1', 'max:1000000'],
            'total_weight_kg'   => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'unit_price_per_kg' => ['required', 'numeric', 'min:1', 'max:9999999'],
            'notes'             => ['nullable', 'string', 'max:255'],
        ]);

        $supplier = Supplier::where('id', $data['supplier_id'])->where('is_active', true)->first();

        if (! $supplier) {
            throw ValidationException::withMessages([
                'supplier_id' => 'Pemasok ini sudah tidak aktif. Pilih pemasok lain.',
            ]);
        }

        $purchase = DB::transaction(function () use ($data, $supplier) {
            $tanggal = Carbon::parse($data['purchase_date']);

            // Nomor transaksi: PB-TanggalPembelian-Urut
            $this->kunci('purchase_number_' . $tanggal->format('Ymd'));

            $prefix   = 'PB-' . $tanggal->format('Ymd') . '-';
            $terakhir = PurchaseTransaction::where('transaction_number', 'like', $prefix . '%')->max('transaction_number');
            $urut     = $terakhir ? ((int) substr($terakhir, strlen($prefix))) + 1 : 1;

            // Total biaya selalu dihitung di server, bukan dari isian browser
            $berat = round((float) $data['total_weight_kg'], 2);
            $harga = round((float) $data['unit_price_per_kg'], 2);

            $purchase = PurchaseTransaction::create([
                'transaction_number' => $prefix . str_pad((string) $urut, 4, '0', STR_PAD_LEFT),
                'purchase_date'      => $tanggal->toDateString(),
                'supplier_id'        => $supplier->id,
                'total_birds'        => (int) $data['total_birds'],
                'total_weight_kg'    => $berat,
                'unit_price_per_kg'  => $harga,
                'total_cost'         => round($berat * $harga, 2),
                'notes'              => $data['notes'] ?? null,
                'received_by_id'     => Auth::id(),
            ]);

            AuditLog::create([
                'action'     => 'CREATE',
                'table_name' => 'purchase_transactions',
                'record_id'  => $purchase->id,
                'new_values' => $purchase->getAttributes(),
                'user_id'    => Auth::id(),
            ]);

            return $purchase;
        });

        return redirect()->route('pembelian.show', $purchase)
            ->with('status', 'Pembelian tersimpan: ' . $purchase->transaction_number . '.');
    }

    public function show(PurchaseTransaction $purchase)
    {
        $purchase->load(['supplier', 'receivedBy']);

        return view('pembelian.show', compact('purchase'));
    }
}