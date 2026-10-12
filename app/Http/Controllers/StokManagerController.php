<?php

namespace App\Http\Controllers;

use App\Services\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StokManagerController extends Controller
{
    private const PERIODE = [
        'hari'       => 'Hari ini',
        'minggu'     => '7 hari',
        'bulan'      => 'Bulan ini',
        'bulan_lalu' => 'Bulan lalu',
    ];

    public function index(Request $request, StokService $stok)
    {
        $periode = (string) $request->query('periode', 'bulan');
        if (! isset(self::PERIODE[$periode])) {
            $periode = 'bulan';
        }

        $today = Carbon::today();
        [$from, $to] = match ($periode) {
            'hari'       => [$today->copy(), $today->copy()],
            'minggu'     => [$today->copy()->subDays(6), $today->copy()],
            'bulan_lalu' => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()->startOfDay()],
            default      => [$today->copy()->startOfMonth(), $today->copy()],
        };

        return view('stok.index', [
            'stokAyam'    => $stok->ayamHidup(),
            'arus'        => $stok->arus($from, $to),
            'opnameAyam'  => $stok->opnameAyamTerakhir(3),
            'stokProduk'  => $stok->produk($from, $to),
            'periode'     => $periode,
            'periodeList' => self::PERIODE,
            'from'        => $from,
            'to'          => $to,
        ]);
    }
}