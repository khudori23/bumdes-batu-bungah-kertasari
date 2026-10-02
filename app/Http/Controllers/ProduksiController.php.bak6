<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\WeighingRecord;
use App\Services\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProduksiController extends Controller
{
    public function index(StokService $stok)
    {
        $batches = ProductionBatch::with(['createdBy:id,name', 'weighingRecords.product:id,name'])
            ->orderByDesc('production_date')->orderByDesc('created_at')
            ->limit(30)->get();

        $produk = Product::where('is_active', true)->where('is_live_bird', false)
            ->orderBy('name')->get(['id', 'name']);

        return view('produksi.index', [
            'batches'    => $batches,
            'stok'       => $stok->ayamHidup(),
            'produkOpsi' => $produk->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values(),
            'bolehTulis' => Auth::user()->canWrite('produksi_input'),
        ]);
    }

    public function store(Request $request, StokService $stok)
    {
        abort_unless(Auth::user()->canWrite('produksi_input'), 403);

        $data = $request->validate([
            'production_date'    => ['required', 'date', 'before_or_equal:today'],
            'input_birds'        => ['required', 'integer', 'min:1', 'max:1000000'],
            'input_weight_kg'    => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'notes'              => ['nullable', 'string', 'max:255'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'items.*.weight_kg'  => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'items.*.pieces'     => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ]);

        $ids = collect($data['items'])->pluck('product_id')->unique()->all();
        $valid = Product::whereIn('id', $ids)->where('is_active', true)->where('is_live_bird', false)->count();

        if ($valid !== count($ids)) {
            throw ValidationException::withMessages([
                'items' => 'Ada produk yang tidak valid atau tidak aktif. Muat ulang halaman.',
            ]);
        }

        $masukKg = round((float) $data['input_weight_kg'], 2);
        $hasilKg = round((float) collect($data['items'])->sum(fn ($r) => (float) $r['weight_kg']), 2);

        if ($hasilKg > $masukKg) {
            throw ValidationException::withMessages([
                'items' => 'Total hasil (' . number_format($hasilKg, 2, ',', '.') . ' kg) tidak boleh melebihi berat ayam yang masuk produksi (' . number_format($masukKg, 2, ',', '.') . ' kg).',
            ]);
        }

        $batch = DB::transaction(function () use ($data, $stok, $masukKg) {
            $tanggal = Carbon::parse($data['production_date']);
            $ymd = $tanggal->format('Ymd');

            $stok->kunci('stok_ayam_hidup');
            $stok->kunci('produksi_number_' . $ymd);

            $sisa = $stok->ayamHidup();
            if ($data['input_birds'] > $sisa['ekor']) {
                throw ValidationException::withMessages([
                    'input_birds' => 'Jumlah ayam melebihi stok ayam hidup saat ini (' . number_format($sisa['ekor'], 0, ',', '.') . ' ekor).',
                ]);
            }

            $prefix = 'PRD-' . $ymd . '-';
            $terakhir = ProductionBatch::where('batch_number', 'like', $prefix . '%')->max('batch_number');
            $urut = $terakhir ? ((int) substr($terakhir, strlen($prefix))) + 1 : 1;

            $batch = ProductionBatch::create([
                'batch_number'    => $prefix . str_pad((string) $urut, 4, '0', STR_PAD_LEFT),
                'production_date' => $tanggal->toDateString(),
                'input_birds'     => (int) $data['input_birds'],
                'input_weight_kg' => $masukKg,
                'status'          => 'COMPLETED',
                'notes'           => $data['notes'] ?? null,
                'created_by_id'   => Auth::id(),
            ]);

            $rp = 'WR-' . $ymd . '-';
            $akhir = WeighingRecord::where('receipt_number', 'like', $rp . '%')->max('receipt_number');
            $no = $akhir ? ((int) substr($akhir, strlen($rp))) : 0;

            foreach ($data['items'] as $row) {
                $no++;
                $batch->weighingRecords()->create([
                    'receipt_number' => $rp . str_pad((string) $no, 4, '0', STR_PAD_LEFT),
                    'product_id'     => $row['product_id'],
                    'weight_kg'      => round((float) $row['weight_kg'], 2),
                    'pieces'         => ($row['pieces'] ?? '') !== '' ? (int) $row['pieces'] : null,
                    'operator_id'    => Auth::id(),
                ]);
            }

            AuditLog::create([
                'action'     => 'CREATE',
                'table_name' => 'production_batches',
                'record_id'  => $batch->id,
                'new_values' => $batch->getAttributes(),
                'user_id'    => Auth::id(),
            ]);

            return $batch;
        });

        $rend = $masukKg > 0 ? round($hasilKg / $masukKg * 100, 1) : 0;

        return redirect()->route('produksi.index')
            ->with('status', 'Produksi tersimpan: ' . $batch->batch_number . '. Rendemen ' . number_format($rend, 1, ',', '.') . '%.');
    }
}