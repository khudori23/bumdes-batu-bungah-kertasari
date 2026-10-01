<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HanyaAdmin;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LabaRugiController extends Controller
{
    use HanyaAdmin;

    public function __invoke(Request $request)
    {
        $this->wajibAdmin();

        $tahun = (int) $request->query('tahun', now()->year);
        if ($tahun < 2020 || $tahun > 2100) {
            $tahun = now()->year;
        }
        $awal  = sprintf('%04d-01-01', $tahun);
        $akhir = sprintf('%04d-12-31', $tahun);

        // Omzet: semua nota yang tidak di-void (termasuk yang masih piutang)
        $omzet = DB::table('sales')->where('is_voided', false)
            ->whereBetween('sale_date', [$awal, $akhir])
            ->selectRaw("extract(month from sale_date)::int as m, sum(total_amount) as t")
            ->groupBy('m')->pluck('t', 'm');

        // Pembelian ayam hidup (modal bahan baku)
        $beli = DB::table('purchase_transactions')
            ->whereBetween('purchase_date', [$awal, $akhir])
            ->selectRaw("extract(month from purchase_date)::int as m, sum(total_cost) as t")
            ->groupBy('m')->pluck('t', 'm');

        // Pengeluaran: gaji dibayar sudah masuk sini (kategori 'Upah / Gaji'), jangan dijumlah lagi dari payrolls
        $exp = DB::table('expenses')
            ->whereBetween('expense_date', [$awal, $akhir])
            ->selectRaw("extract(month from expense_date)::int as m,
                sum(case when category = 'Upah / Gaji' then amount else 0 end) as gaji,
                sum(case when category <> 'Upah / Gaji' then amount else 0 end) as lain")
            ->groupBy('m')->get()->keyBy('m');

        // Info saja: gaji sudah diterbitkan tapi belum ditandai dibayar
        $belum = DB::table('payrolls')->where('status', 'FINAL')->where('period_year', $tahun)
            ->selectRaw('period_month as m, sum(net_amount) as t')
            ->groupBy('period_month')->pluck('t', 'm');

        $baris = [];
        $tot = ['omzet' => 0, 'beli' => 0, 'gaji' => 0, 'lain' => 0, 'laba' => 0, 'belum' => 0];
        for ($m = 1; $m <= 12; $m++) {
            $o = (float) ($omzet[$m] ?? 0);
            $b = (float) ($beli[$m] ?? 0);
            $g = (float) ($exp[$m]->gaji ?? 0);
            $l = (float) ($exp[$m]->lain ?? 0);
            $u = (float) ($belum[$m] ?? 0);
            $laba = $o - $b - $g - $l;
            $baris[] = [
                'bulan' => Carbon::create($tahun, $m, 1)->locale('id')->translatedFormat('F'),
                'omzet' => $o, 'beli' => $b, 'gaji' => $g, 'lain' => $l, 'belum' => $u,
                'laba'  => $laba,
                'rasio' => $o > 0 ? round($g / $o * 100, 1) : null,
            ];
            $tot['omzet'] += $o; $tot['beli'] += $b; $tot['gaji'] += $g; $tot['lain'] += $l;
            $tot['laba'] += $laba; $tot['belum'] += $u;
        }
        $tot['rasio'] = $tot['omzet'] > 0 ? round($tot['gaji'] / $tot['omzet'] * 100, 1) : null;

        return view('gaji.labarugi', [
            'tahun' => $tahun, 'baris' => $baris, 'tot' => $tot, 'set' => Setting::semua(),
        ]);
    }
}