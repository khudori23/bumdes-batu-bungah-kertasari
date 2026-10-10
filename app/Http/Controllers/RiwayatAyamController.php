<?php

namespace App\Http\Controllers;

use App\Models\ProductionBatch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Halaman riwayat ayam: produksi, penjualan ecer pengawas, dan penjualan lama lewat kasir biasa.
 * Hanya membaca data.
 */
class RiwayatAyamController extends Controller
{
    public function index()
    {
        abort_unless(Auth::user()->canView('kandang'), 403);

        $awalBulan = now()->startOfMonth()->toDateString();
        $hariIni   = now()->toDateString();

        $batches = ProductionBatch::withSum('weighingRecords as hasil_kg', 'weight_kg')
            ->orderByDesc('production_date')->orderByDesc('created_at')
            ->limit(100)->get();

        $ecer = DB::table('live_bird_sales as l')
            ->leftJoin('sales as s', 's.id', '=', 'l.sale_id')
            ->orderByDesc('l.sale_date')->orderByDesc('l.created_at')
            ->limit(100)
            ->get(['l.*', 's.sale_number', 's.is_voided as nota_batal']);

        $ecerBulan = DB::table('live_bird_sales')
            ->whereBetween('sale_date', [$awalBulan, $hariIni])
            ->selectRaw('coalesce(sum(birds),0) as e, coalesce(sum(weight_kg),0) as k')->first();

        $dasarJual = fn () => DB::table('sale_items as i')
            ->join('sales as s', 's.id', '=', 'i.sale_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->where('s.is_voided', false)
            ->where('p.is_live_bird', true)
            ->whereNotIn('s.id', fn ($q) => $q->select('sale_id')->from('live_bird_sales')->whereNotNull('sale_id'));

        $jual = $dasarJual()
            ->orderByDesc('s.sale_date')->orderByDesc('s.created_at')
            ->limit(100)
            ->get(['s.sale_number', 's.sale_date', 's.customer_name', 'i.quantity', 'i.weight_kg']);

        $jualBulan = $dasarJual()
            ->whereBetween('s.sale_date', [$awalBulan, $hariIni])
            ->selectRaw('coalesce(sum(i.quantity),0) as e, coalesce(sum(i.weight_kg),0) as k')
            ->first();

        return view('riwayat-ayam.index', compact('batches', 'ecer', 'ecerBulan', 'jual', 'jualBulan'));
    }
}