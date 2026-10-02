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
        $user = Auth::user();
        // Kasir tidak boleh melihat data ayam hidup (jumlah masuk, berat, rendemen)
        $lihatAyam = ! $user->isKasir();

        $batches = ProductionBatch::with(['createdBy:id,name', 'weighingRecords.product:id,name'])
            ->when(! $lihatAyam, fn ($q) => $q->has('weighingRecords'))
            ->orderByDesc('production_date')->orderByDesc('created_at')
            ->limit(30)->get();

        $produk = Product::where('is_active', true)->where('is_live_bird', false)
            ->orderBy('name')->get(['id', 'name']);

        return view('produksi.index', [
            'batches'    => $batches,
            'lihatAyam'  => $lihatAyam,
            'stok'       => $lihatAyam ? $stok->ayamHidup() : null,
            'produkOpsi' => $produk->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values(),
            'bolehTulis' => $user->canWrite('produksi_input'),
        ]);
    }

    public function store(Request $request, StokService $stok)
    {
        abort_unless(Auth::user()->canWrite('produksi_input'), 403);

        $data = $request->validate([
            'production_date'    => ['required', 'date', 'before_or_equal:today'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'items.*.weight_kg'  => ['required', 'numeric', 'min:0.01', 'max:1000000'],
        ]);

        $ids = collect($data['items'])->pluck('product_id')->unique()->all();
        $valid = Product::whereIn('id', $ids)->where('is_active', true)->where('is_live_bird', false)->count();
        if ($valid !== count($ids)) {
            throw ValidationException::withMessages([
                'items' => 'Ada produk yang tidak valid atau tidak aktif. Muat ulang halaman.',
            ]);
        }

        $batch = DB::transaction(function () use ($data, $stok) {
            $tanggal = Carbon::parse($data['production_date']);
            $ymd = $tanggal->format('Ymd');

            $stok->kunci('produksi_number_' . $ymd);

            // Satu batch per tanggal: pakai yang sudah ada (mungkin dibuat Pengawas), kalau belum ada buat baru
            $batch = ProductionBatch::where('production_date', $tanggal->toDateString())
                ->orderBy('created_at')->lockForUpdate()->first();

            if (! $batch) {
                $prefix = 'PRD-' . $ymd . '-';
                $terakhir = ProductionBatch::where('batch_number', 'like', $prefix . '%')->max('batch_number');
                $urut = $terakhir ? ((int) substr($terakhir, strlen($prefix))) + 1 : 1;

                $batch = ProductionBatch::create([
                    'batch_number'    => $prefix . str_pad((string) $urut, 4, '0', STR_PAD_LEFT),
                    'production_date' => $tanggal->toDateString(),
                    'input_birds'     => 0,
                    'input_weight_kg' => 0,
                    'status'          => 'COMPLETED',
                    'created_by_id'   => Auth::id(),
                ]);
            } elseif ($batch->status !== 'COMPLETED') {
                $batch->update(['status' => 'COMPLETED']);
            }

            $rp = 'WR-' . $ymd . '-';
            $akhir = WeighingRecord::where('receipt_number', 'like', $rp . '%')->max('receipt_number');
            $no = $akhir ? ((int) substr($akhir, strlen($rp))) : 0;

            foreach ($data['items'] as $row) {
                $no++;
                $batch->weighingRecords()->create([
                    'receipt_number' => $rp . str_pad((string) $no, 4, '0', STR_PAD_LEFT),
                    'product_id'     => $row['product_id'],
                    'weight_kg'      => round((float) $row['weight_kg'], 2),
                    'operator_id'    => Auth::id(),
                ]);
            }

            AuditLog::create([
                'action'     => 'CREATE',
                'table_name' => 'weighing_records',
                'record_id'  => $batch->id,
                'new_values' => ['batch_number' => $batch->batch_number, 'items' => $data['items']],
                'user_id'    => Auth::id(),
            ]);

            return $batch->fresh();
        });

        $pesan = 'Hasil produksi tersimpan (' . $batch->batch_number . ').';
        if (! Auth::user()->isKasir() && (float) $batch->input_weight_kg > 0) {
            $total = (float) $batch->weighingRecords()->sum('weight_kg');
            $pesan .= ' Rendemen tanggal itu ' . number_format($total / (float) $batch->input_weight_kg * 100, 1, ',', '.') . '%.';
        }

        return redirect()->route('produksi.index')->with('status', $pesan);
    }
}