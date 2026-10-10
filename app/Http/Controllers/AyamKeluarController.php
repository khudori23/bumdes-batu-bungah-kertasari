<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ProductionBatch;
use App\Services\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AyamKeluarController extends Controller
{
    public function index(StokService $stok)
    {
        $batches = ProductionBatch::withSum('weighingRecords as hasil_kg', 'weight_kg')
            ->orderByDesc('production_date')->orderByDesc('created_at')
            ->limit(20)->get();

        $dasarJual = fn () => DB::table('sale_items as i')
            ->join('sales as s', 's.id', '=', 'i.sale_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->where('s.is_voided', false)
            ->where('p.is_live_bird', true)
            ->whereNotIn('s.id', fn ($q) => $q->select('sale_id')->from('live_bird_sales')->whereNotNull('sale_id'));

        $jual = $dasarJual()
            ->orderByDesc('s.sale_date')->orderByDesc('s.created_at')
            ->limit(20)
            ->get(['s.sale_number', 's.sale_date', 's.customer_name', 'i.quantity', 'i.weight_kg']);

        $jualBulan = $dasarJual()
            ->whereBetween('s.sale_date', [now()->startOfMonth()->toDateString(), now()->toDateString()])
            ->selectRaw('coalesce(sum(i.quantity),0) as e, coalesce(sum(i.weight_kg),0) as k')
            ->first();

        $ecer = DB::table('live_bird_sales as l')
            ->leftJoin('sales as s', 's.id', '=', 'l.sale_id')
            ->orderByDesc('l.sale_date')->orderByDesc('l.created_at')
            ->limit(20)
            ->get(['l.*', 's.sale_number', 's.is_voided as nota_batal']);
        $ecerBulan = DB::table('live_bird_sales')
            ->whereBetween('sale_date', [now()->startOfMonth()->toDateString(), now()->toDateString()])
            ->selectRaw('coalesce(sum(birds),0) as e, coalesce(sum(weight_kg),0) as k')->first();

        return view('ayam-keluar.index', [
            'stok'       => $stok->ayamHidup(),
            'batches'    => $batches,
            'jual'       => $jual,
            'jualBulan'  => $jualBulan,
            'ecer'       => $ecer,
            'ecerBulan'  => $ecerBulan,
            'bolehTulis' => Auth::user()->canWrite('kandang'),
        ]);
    }

    public function store(Request $request, StokService $stok)
    {
        abort_unless(Auth::user()->canWrite('kandang'), 403);

        $data = $request->validate([
            'production_date' => \App\Support\Pencatatan::aturan(),
            'input_birds'     => ['required', 'integer', 'min:1', 'max:1000000'],
            'input_weight_kg' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'notes'           => ['nullable', 'string', 'max:255'],
        ]);

        $batch = DB::transaction(function () use ($data, $stok) {
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

            $kg = round((float) $data['input_weight_kg'], 2);

            // Satu batch per tanggal: pakai yang sudah ada (dibuat Kasir atau Pengawas), kalau belum ada buat baru
            $batch = ProductionBatch::where('production_date', $tanggal->toDateString())
                ->orderBy('created_at')->lockForUpdate()->first();

            if ($batch) {
                $batch->update([
                    'input_birds'     => (int) $batch->input_birds + (int) $data['input_birds'],
                    'input_weight_kg' => round((float) $batch->input_weight_kg + $kg, 2),
                    'status'          => 'COMPLETED',
                    'notes'           => $batch->notes ?: ($data['notes'] ?? null),
                ]);
            } else {
                $prefix = 'PRD-' . $ymd . '-';
                $terakhir = ProductionBatch::where('batch_number', 'like', $prefix . '%')->max('batch_number');
                $urut = $terakhir ? ((int) substr($terakhir, strlen($prefix))) + 1 : 1;

                $batch = ProductionBatch::create([
                    'batch_number'    => $prefix . str_pad((string) $urut, 4, '0', STR_PAD_LEFT),
                    'production_date' => $tanggal->toDateString(),
                    'input_birds'     => (int) $data['input_birds'],
                    'input_weight_kg' => $kg,
                    'status'          => 'COMPLETED',
                    'notes'           => $data['notes'] ?? null,
                    'created_by_id'   => Auth::id(),
                ]);
            }

            AuditLog::create([
                'action'     => 'CREATE',
                'table_name' => 'production_batches',
                'record_id'  => $batch->id,
                'new_values' => ['batch_number' => $batch->batch_number, 'tambah_ekor' => (int) $data['input_birds'], 'tambah_kg' => $kg],
                'user_id'    => Auth::id(),
            ]);

            return $batch->fresh();
        });

        return redirect()->route('ayamkeluar.index')
            ->with('status', 'Ayam masuk produksi tersimpan (' . $batch->batch_number . '). Total tanggal itu: ' . number_format($batch->input_birds, 0, ',', '.') . ' ekor.');
    }
}