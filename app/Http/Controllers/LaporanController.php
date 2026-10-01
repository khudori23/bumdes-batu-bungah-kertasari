<?php

namespace App\Http\Controllers;

use App\Services\LaporanService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LaporanController extends Controller
{
    private function tanggal(string $s): Carbon
    {
        try {
            $d = Carbon::createFromFormat('!Y-m-d', $s);
            if ($d && $d->format('Y-m-d') === $s) {
                return $d;
            }
        } catch (\Throwable $e) {
            // jatuh ke hari ini
        }

        return now()->startOfDay();
    }

    public function index(Request $request, LaporanService $svc)
    {
        $jenis = (string) $request->query('jenis', 'harian');
        if (! isset(LaporanService::JENIS[$jenis])) {
            $jenis = 'harian';
        }

        $tgl = $this->tanggal((string) $request->query('tanggal'));
        [$from, $to] = $svc->rentang($jenis, $tgl);
        [$pf, $pt]   = $svc->sebelumnya($jenis, $from);

        $id = fn (Carbon $c, string $fmt) => $c->copy()->locale('id')->translatedFormat($fmt);
        $label = match ($jenis) {
            'mingguan' => $id($from, 'l, d F Y') . ' s.d. ' . $id($to, 'l, d F Y'),
            'bulanan'  => $id($from, 'F Y'),
            default    => $id($from, 'l, d F Y'),
        };

        return view('laporan.index', [
            'jenis'  => $jenis,
            'jenisList' => LaporanService::JENIS,
            'tanggal' => $tgl->toDateString(),
            'from'   => $from, 'to' => $to,
            'label'  => $label,
            'nomor'  => 'LAP/' . strtoupper($jenis) . '/' . $from->format('Ymd'),
            'd'      => $svc->data($from, $to, $jenis),
            'prev'   => $svc->ringkas($pf, $pt),
            'dibuat' => now()->locale('id')->translatedFormat('d F Y, H:i'),
        ]);
    }
}