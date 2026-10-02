<?php

namespace App\Http\Controllers;

use App\Services\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LaporanKandangController extends Controller
{
    private function tgl($s, string $default): string
    {
        $s = (string) $s;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
            try {
                $d = Carbon::createFromFormat('!Y-m-d', $s);
                if ($d && $d->format('Y-m-d') === $s) {
                    return $s;
                }
            } catch (\Throwable $e) {
                // pakai default
            }
        }

        return $default;
    }

    public function index(Request $request, StokService $stok)
    {
        $dari   = $this->tgl($request->query('dari'), now()->startOfMonth()->toDateString());
        $sampai = $this->tgl($request->query('sampai'), now()->toDateString());
        if ($dari > $sampai) {
            [$dari, $sampai] = [$sampai, $dari];
        }
        $rentang = [$dari, $sampai];

        $beli = DB::table('purchase_transactions')->whereBetween('purchase_date', $rentang)
            ->selectRaw('count(*) as jumlah, coalesce(sum(total_birds),0) as ekor, coalesce(sum(total_weight_kg),0) as kg, coalesce(sum(total_cost),0) as biaya')
            ->first();

        $perPemasok = DB::table('purchase_transactions as p')
            ->leftJoin('suppliers as s', 's.id', '=', 'p.supplier_id')
            ->whereBetween('p.purchase_date', $rentang)
            ->selectRaw("coalesce(s.name,'Tanpa pemasok') as nama, count(*) as jumlah, sum(p.total_birds) as ekor, sum(p.total_weight_kg) as kg, sum(p.total_cost) as biaya")
            ->groupByRaw("coalesce(s.name,'Tanpa pemasok')")
            ->orderByDesc('biaya')->get();

        $mati = DB::table('live_bird_mortalities')->whereBetween('record_date', $rentang)
            ->selectRaw('coalesce(sum(birds),0) as ekor, coalesce(sum(weight_kg),0) as kg')->first();

        $sebab = DB::table('live_bird_mortalities')->whereBetween('record_date', $rentang)
            ->selectRaw("coalesce(nullif(cause,''),'Tidak dicatat') as sebab, sum(birds) as ekor, sum(weight_kg) as kg")
            ->groupByRaw("coalesce(nullif(cause,''),'Tidak dicatat')")
            ->orderByDesc('ekor')->get();

        $opRing = DB::table('live_bird_opnames')->whereBetween('opname_date', $rentang)
            ->selectRaw('count(*) as jumlah, coalesce(sum(diff_birds),0) as selisih_ekor, coalesce(sum(diff_kg),0) as selisih_kg')->first();

        $opname = DB::table('live_bird_opnames')->whereBetween('opname_date', $rentang)
            ->orderByDesc('opname_date')->orderByDesc('created_at')->limit(10)->get();

        $hariIni = now()->toDateString();
        $preset = [
            'Hari ini'   => [$hariIni, $hariIni],
            '7 hari'     => [now()->subDays(6)->toDateString(), $hariIni],
            'Bulan ini'  => [now()->startOfMonth()->toDateString(), $hariIni],
            'Bulan lalu' => [now()->subMonthNoOverflow()->startOfMonth()->toDateString(), now()->subMonthNoOverflow()->endOfMonth()->toDateString()],
        ];

        return view('laporan.kandang', compact('dari', 'sampai', 'beli', 'perPemasok', 'mati', 'sebab', 'opRing', 'opname', 'preset') + [
            'stok' => $stok->ayamHidup(),
        ]);
    }
}