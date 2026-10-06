<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\PiutangService;
use App\Services\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private const PERIODE = [
        'hari'       => ['Hari ini', 'kemarin'],
        'minggu'     => ['7 hari', '7 hari sebelumnya'],
        'bulan'      => ['Bulan ini', 'periode sama bulan lalu'],
        'bulan_lalu' => ['Bulan lalu', 'bulan sebelumnya'],
    ];

    public function index(Request $request, PiutangService $piutang, StokService $stok)
    {
        $user      = Auth::user();
        if ($user->roleName() === 'PEGAWAI') {
            return redirect()->route('absen.index');
        }
        $manajemen = in_array(strtoupper((string) $user->role), ['ADMIN', 'MANAGER'], true);
        $pengawas  = $user->isPengawasKandang();
        $kasirId   = ($manajemen || $pengawas) ? null : $user->id; // kasir hanya melihat transaksinya sendiri

        // Admin/Manager dan Pengawas boleh memilih periode; kasir selalu "hari ini"
        $periode = ($manajemen || $pengawas)
            ? $request->query('periode', $manajemen ? 'bulan' : 'hari')
            : 'hari';
        if (! isset(self::PERIODE[$periode])) {
            $periode = $manajemen ? 'bulan' : 'hari';
        }

        $today = Carbon::today();
        [$from, $to, $pFrom, $pTo] = $this->rentang($periode, $today);

        $periodeList = array_map(fn ($p) => $p[0], self::PERIODE);

        // Data stok kandang & hasil produksi: tampil untuk semua peran
        $stokData = [
            'stokAyam'   => $stok->ayamHidup(),
            'arus'       => $stok->arus($from, $to),
            'opnameAyam' => $stok->opnameAyamTerakhir(3),
            'stokProduk' => $stok->produk($from, $to),
        ];

        // Pengawas kandang: hanya data kandang, tanpa omzet/kas/piutang
        if ($pengawas) {
            return view('dashboard-kandang', $stokData + [
                'periode'     => $periode,
                'periodeList' => $periodeList,
                'from'        => $from,
                'to'          => $to,
            ]);
        }

        $now  = $this->ringkas($from, $to, $kasirId);
        $prev = $this->ringkas($pFrom, $pTo, $kasirId);

        $recent = Sale::with('cashier')
            ->withSum(['payments as dibayar' => fn ($q) => $q->whereNull('voided_at')], 'amount')
            ->when($kasirId, fn ($q) => $q->where('cashier_id', $kasirId))
            ->latest('created_at')->limit(8)->get();

        $data = $stokData + [
            'manajemen'   => $manajemen,
            'periode'     => $periode,
            'periodeList' => $periodeList,
            'banding'     => self::PERIODE[$periode][1],
            'from'        => $from,
            'to'          => $to,
            'now'         => $now,
            'prev'        => $prev,
            'recent'      => $recent,
        ];

        if (! $manajemen) {
            return view('dashboard', $data);
        }

        // Tren harian (periode "hari ini" memakai 14 hari terakhir supaya grafik tetap bermakna)
        $cFrom = $periode === 'hari' ? $today->copy()->subDays(13) : $from->copy();
        $omzetHarian = DB::table('sales')->where('is_voided', false)
            ->whereBetween('sale_date', [$cFrom->toDateString(), $to->toDateString()])
            ->selectRaw('sale_date, sum(total_amount) as omzet, count(*) as trx')
            ->groupBy('sale_date')->get()->keyBy(fn ($r) => substr((string) $r->sale_date, 0, 10));
        $kasHarian = DB::table('payments')->whereNull('voided_at')
            ->whereRaw($this->tglKas() . ' between ? and ?', [config('app.timezone'), $cFrom->toDateString(), $to->toDateString()])
            ->selectRaw($this->tglKas() . ' as tgl, sum(amount) as kas', [config('app.timezone')])
            ->groupBy('tgl')->pluck('kas', 'tgl');

        $chart = [];
        for ($d = $cFrom->copy(); $d->lte($to); $d->addDay()) {
            $k = $d->toDateString();
            $chart[] = [
                'tgl'   => $d->copy(),
                'omzet' => (float) ($omzetHarian[$k]->omzet ?? 0),
                'trx'   => (int) ($omzetHarian[$k]->trx ?? 0),
                'kas'   => (float) ($kasHarian[$k] ?? 0),
            ];
        }

        $produk = DB::table('sale_items as i')
            ->join('sales as s', 's.id', '=', 'i.sale_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->where('s.is_voided', false)
            ->whereBetween('s.sale_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('p.name, upper(i.unit) as unit, sum(i.quantity) as qty, sum(i.subtotal) as omzet')
            ->groupBy('p.id', 'p.name', 'i.unit')->orderByDesc('omzet')->limit(6)->get();

        $batal = DB::table('sales')->where('is_voided', true)
            ->whereBetween('sale_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('count(*) as n, coalesce(sum(total_amount),0) as total')->first();

        // Umur piutang & pelanggan dengan hutang terbesar
        $aging = ['belum' => 0.0, 'd7' => 0.0, 'd30' => 0.0, 'd30p' => 0.0];
        $debitur = [];
        foreach ($piutang->notaTerbukaSemua() as $n) {
            $sisa  = (float) $n->sisa;
            $due   = Carbon::parse($n->jatuh_tempo)->startOfDay();
            $telat = $due->lt($today) ? (int) abs($due->diffInDays($today)) : 0;

            $aging[$telat === 0 ? 'belum' : ($telat <= 7 ? 'd7' : ($telat <= 30 ? 'd30' : 'd30p'))] += $sisa;

            $debitur[$n->customer_id] ??= ['id' => $n->customer_id, 'nama' => $n->customer_name, 'saldo' => 0.0, 'telat' => 0, 'nota' => 0];
            $debitur[$n->customer_id]['saldo'] += $sisa;
            $debitur[$n->customer_id]['nota']++;
            $debitur[$n->customer_id]['telat'] = max($debitur[$n->customer_id]['telat'], $telat);
        }
        usort($debitur, fn ($a, $b) => $b['saldo'] <=> $a['saldo']);

        return view('dashboard', $data + [
            'chart'         => $chart,
            'produk'        => $produk,
            'batal'         => $batal,
            'aging'         => $aging,
            'debitur'       => array_slice($debitur, 0, 5),
            'jumlahDebitur' => count($debitur),
            'jumlahTelat'   => count(array_filter($debitur, fn ($d) => $d['telat'] > 0)),
            'tertua'        => collect($debitur)->max('telat') ?? 0,
        ]);
    }

    /** Tanggal kas dalam zona waktu aplikasi (kolom created_at bertipe timestamptz). */
    private function tglKas(): string
    {
        return '(created_at at time zone ?::text)::date';
    }

    private function rentang(string $p, Carbon $today): array
    {
        if ($p === 'hari') {
            return [$today->copy(), $today->copy(), $today->copy()->subDay(), $today->copy()->subDay()];
        }
        if ($p === 'minggu') {
            $f = $today->copy()->subDays(6);
            return [$f, $today->copy(), $f->copy()->subDays(7), $f->copy()->subDay()];
        }
        if ($p === 'bulan_lalu') {
            $f  = $today->copy()->subMonthNoOverflow()->startOfMonth();
            $pf = $f->copy()->subMonthNoOverflow()->startOfMonth();
            return [$f, $f->copy()->endOfMonth()->startOfDay(), $pf, $pf->copy()->endOfMonth()->startOfDay()];
        }
        $f  = $today->copy()->startOfMonth();
        $pf = $f->copy()->subMonthNoOverflow();
        $pt = $pf->copy()->addDays($today->day - 1);
        if ($pt->gt($pf->copy()->endOfMonth())) {
            $pt = $pf->copy()->endOfMonth();
        }
        return [$f, $today->copy(), $pf, $pt->startOfDay()];
    }

    private function ringkas(Carbon $from, Carbon $to, ?string $kasirId): array
    {
        $f = $from->toDateString();
        $t = $to->toDateString();

        $s = DB::table('sales')->where('is_voided', false)->whereBetween('sale_date', [$f, $t])
            ->when($kasirId, fn ($q) => $q->where('cashier_id', $kasirId))
            ->selectRaw('coalesce(sum(total_amount),0) as omzet, count(*) as trx')->first();

        $kg = DB::table('sale_items as i')->join('sales as s', 's.id', '=', 'i.sale_id')
            ->where('s.is_voided', false)->whereBetween('s.sale_date', [$f, $t])
            ->whereRaw("upper(i.unit) = 'KG'")
            ->when($kasirId, fn ($q) => $q->where('s.cashier_id', $kasirId))
            ->sum('i.quantity');

        $kas = DB::table('payments')->whereNull('voided_at')
            ->whereRaw($this->tglKas() . ' between ? and ?', [config('app.timezone'), $f, $t])
            ->when($kasirId, fn ($q) => $q->where('cashier_id', $kasirId))
            ->sum('amount');

        return ['omzet' => (float) $s->omzet, 'trx' => (int) $s->trx, 'kg' => (float) $kg, 'kas' => (float) $kas];
    }
}
