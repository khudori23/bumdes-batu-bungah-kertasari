@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    use Illuminate\Support\HtmlString;

    $rp  = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $rpk = function ($n) {
        $a = abs($n);
        if ($a >= 1e9) return number_format($n / 1e9, 1, ',', '.') . ' M';
        if ($a >= 1e6) return number_format($n / 1e6, 1, ',', '.') . ' jt';
        if ($a >= 1e3) return number_format($n / 1e3, 0, ',', '.') . ' rb';
        return number_format($n, 0, ',', '.');
    };
    $chip = function ($cur, $prev) use ($banding) {
        if ($prev <= 0) return new HtmlString('<span class="db-chip flat">baru</span> <span class="db-muted">belum ada data ' . e($banding) . '</span>');
        $d = round(($cur - $prev) / $prev * 100, 1);
        $cls = $d > 0 ? 'up' : ($d < 0 ? 'down' : 'flat');
        $ico = $d > 0 ? '▲' : ($d < 0 ? '▼' : '•');
        return new HtmlString('<span class="db-chip ' . $cls . '">' . $ico . ' ' . number_format(abs($d), 1, ',', '.') . '%</span> <span class="db-muted">vs ' . e($banding) . '</span>');
    };

    $jam = (int) now()->format('H');
    $salam = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 19 ? 'Selamat sore' : 'Selamat malam'));
    $rentang = $from->equalTo($to)
        ? $from->locale('id')->translatedFormat('l, d F Y')
        : $from->locale('id')->translatedFormat('d M') . ' – ' . $to->locale('id')->translatedFormat('d M Y');
@endphp

<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ @filemtime(public_path('css/dashboard.css')) }}">

<div class="db">
    <div class="db-head">
        <div>
            <h2>{{ $salam }}, {{ auth()->user()->name }}</h2>
            <div class="db-muted" style="font-size:.88rem;margin-top:.2rem">{{ $rentang }}</div>
        </div>
        @if ($manajemen)
            <div class="db-seg">
                @foreach ($periodeList as $key => $label)
                    <a href="{{ route('dashboard', ['periode' => $key]) }}" class="{{ $periode === $key ? 'on' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
        @else
            <a class="btn" style="margin:0;text-decoration:none" href="{{ route('kasir.create') }}">Transaksi baru</a>
        @endif
    </div>

    {{-- KPI --}}
    <div class="db-kpis">
        <div class="db-card db-kpi">
            <div class="l">Omzet</div><div class="v">{{ $rp($now['omzet']) }}</div>
            <div>{{ $chip($now['omzet'], $prev['omzet']) }}</div>
        </div>
        <div class="db-card db-kpi">
            <div class="l">Kas masuk</div><div class="v">{{ $rp($now['kas']) }}</div>
            <div>{{ $chip($now['kas'], $prev['kas']) }}</div>
        </div>
        <div class="db-card db-kpi">
            <div class="l">Transaksi</div><div class="v">{{ number_format($now['trx'], 0, ',', '.') }}</div>
            <div>{{ $chip($now['trx'], $prev['trx']) }}</div>
            <div class="db-muted" style="margin-top:.25rem">Rata-rata {{ $rp($now['trx'] ? $now['omzet'] / $now['trx'] : 0) }} / nota</div>
        </div>
        @if ($manajemen)
            <div class="db-card db-kpi">
                <div class="l">Volume terjual</div><div class="v">{{ number_format($now['kg'], 1, ',', '.') }} <span style="font-size:.95rem;color:var(--muted)">kg</span></div>
                <div>{{ $chip($now['kg'], $prev['kg']) }}</div>
            </div>
            <div class="db-card db-kpi">
                <div class="l">Piutang beredar</div><div class="v">{{ $rp(array_sum($aging)) }}</div>
                <div class="db-muted">{{ $jumlahDebitur }} pelanggan masih berhutang</div>
            </div>
            @php $telat = $aging['d7'] + $aging['d30'] + $aging['d30p']; @endphp
            <div class="db-card db-kpi">
                <div class="l">Lewat jatuh tempo</div>
                <div class="v" style="color:{{ $telat > 0 ? 'var(--rose)' : 'inherit' }}">{{ $rp($telat) }}</div>
                <div class="db-muted">{{ $jumlahTelat }} pelanggan{{ $tertua > 0 ? ' · tertua ' . $tertua . ' hari' : '' }}</div>
            </div>
        @endif
    </div>

    @if ($manajemen)
        {{-- Grafik tren --}}
        @php
            $W = 720; $H = 250; $pl = 48; $pr = 12; $pt = 14; $pb = 28;
            $n = count($chart);
            $maxV = max(1, collect($chart)->max(fn ($d) => max($d['omzet'], $d['kas'])));
            $mag = 10 ** floor(log10($maxV));
            $top = ceil($maxV / $mag * 2) / 2 * $mag;
            $pw = $W - $pl - $pr; $ph = $H - $pt - $pb; $slot = $pw / $n; $bw = min(34, $slot * .62);
            $y = fn ($v) => $pt + $ph * (1 - $v / $top);
            $every = max(1, (int) ceil($n / 10));
            $kosong = collect($chart)->sum('omzet') + collect($chart)->sum('kas') <= 0;
            $pts = [];
        @endphp
        <div class="db-grid">
            <div class="db-card s8">
                <h3>Tren penjualan {{ $periode === 'hari' ? '14 hari terakhir' : '· ' . $periodeList[$periode] }}</h3>
                <div class="db-legend-line">
                    <span><i style="background:var(--teal)"></i>Omzet</span>
                    <span><i style="background:var(--amber)"></i>Kas masuk</span>
                </div>
                <div class="db-chart">
                    <div class="db-tip"></div>
                    <svg viewBox="0 0 {{ $W }} {{ $H }}" role="img" aria-label="Grafik omzet dan kas masuk harian">
                        @for ($g = 0; $g <= 4; $g++)
                            @php $gv = $top * $g / 4; @endphp
                            <line x1="{{ $pl }}" x2="{{ $W - $pr }}" y1="{{ $y($gv) }}" y2="{{ $y($gv) }}" stroke="#e2e8f0" stroke-dasharray="{{ $g ? '3 4' : '0' }}"/>
                            <text x="{{ $pl - 8 }}" y="{{ $y($gv) + 4 }}" text-anchor="end" font-size="10.5" fill="#64748b">{{ $rpk($gv) }}</text>
                        @endfor
                        @foreach ($chart as $i => $d)
                            @php
                                $cx = $pl + $slot * ($i + .5);
                                $pts[] = round($cx, 1) . ',' . round($y($d['kas']), 1);
                                $tip = $d['tgl']->locale('id')->translatedFormat('D, d M');
                            @endphp
                            <g class="db-col" data-d="{{ $tip }}" data-o="{{ $rp($d['omzet']) }}" data-k="{{ $rp($d['kas']) }}" data-t="{{ $d['trx'] }}">
                                <rect class="db-bar" x="{{ $cx - $bw / 2 }}" y="{{ $y($d['omzet']) }}" width="{{ $bw }}" height="{{ max(0, $pt + $ph - $y($d['omzet'])) }}" rx="3" style="animation-delay:{{ $i * 18 }}ms"/>
                                <rect class="db-hit" x="{{ $pl + $slot * $i }}" y="{{ $pt }}" width="{{ $slot }}" height="{{ $ph }}"/>
                            </g>
                            @if ($i % $every === 0)
                                <text x="{{ $cx }}" y="{{ $H - 8 }}" text-anchor="middle" font-size="10.5" fill="#64748b">{{ $d['tgl']->format('d/m') }}</text>
                            @endif
                        @endforeach
                        <polyline points="{{ implode(' ', $pts) }}" fill="none" stroke="#d97706" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" pointer-events="none"/>
                        @foreach ($pts as $p)
                            @php [$px, $py] = explode(',', $p); @endphp
                            <circle cx="{{ $px }}" cy="{{ $py }}" r="2.6" fill="#fff" stroke="#d97706" stroke-width="1.6" pointer-events="none"/>
                        @endforeach
                    </svg>
                    @if ($kosong)
                        <div class="db-muted" style="text-align:center;margin-top:-5.5rem;margin-bottom:4rem">Belum ada transaksi pada periode ini</div>
                    @endif
                </div>
            </div>

            {{-- Produk terlaris --}}
            <div class="db-card s4">
                <h3>Produk terlaris</h3>
                @php $maxP = max(1, (float) ($produk->max('omzet') ?? 0)); @endphp
                @forelse ($produk as $p)
                    <div class="db-row">
                        <div class="t">
                            <span><strong>{{ $p->name }}</strong> <span class="db-muted">{{ rtrim(rtrim(number_format($p->qty, 2, ',', '.'), '0'), ',') }} {{ strtolower($p->unit) }}</span></span>
                            <span style="font-variant-numeric:tabular-nums">{{ $rpk($p->omzet) }}</span>
                        </div>
                        <div class="db-track"><div class="db-fill" style="width:{{ round($p->omzet / $maxP * 100) }}%"></div></div>
                    </div>
                @empty
                    <div class="db-muted">Belum ada penjualan pada periode ini.</div>
                @endforelse
            </div>
        </div>

        {{-- Piutang --}}
        @php
            $tot = array_sum($aging);
            $seg = ['belum' => ['Belum jatuh tempo', '#cbd5e1'], 'd7' => ['Telat 1–7 hari', '#fcd34d'], 'd30' => ['Telat 8–30 hari', '#d97706'], 'd30p' => ['Telat > 30 hari', '#e11d48']];
        @endphp
        <div class="db-grid">
            <div class="db-card s5">
                <h3>Umur piutang</h3>
                @if ($tot > 0.004)
                    <div class="db-stack">
                        @foreach ($seg as $k => [$lab, $col])
                            @if ($aging[$k] > 0)<div style="width:{{ $aging[$k] / $tot * 100 }}%;background:{{ $col }}" title="{{ $lab }}"></div>@endif
                        @endforeach
                    </div>
                    <div class="db-legend">
                        @foreach ($seg as $k => [$lab, $col])
                            <div><i style="background:{{ $col }}"></i>{{ $lab }}<br><strong>{{ $rp($aging[$k]) }}</strong></div>
                        @endforeach
                    </div>
                @else
                    <div class="db-muted">Tidak ada piutang beredar.</div>
                @endif

                <h3 style="margin-top:1.3rem">Pengawasan periode ini</h3>
                <div style="font-size:.88rem">
                    Nota dibatalkan: <strong>{{ $batal->n }}</strong>
                    @if ($batal->n > 0)<span class="db-muted">({{ $rp($batal->total) }})</span>@endif
                </div>
            </div>

            <div class="db-card s7">
                <h3>Pelanggan dengan hutang terbesar</h3>
                <div class="db-scroll"><table class="db-table">
                    <thead><tr><th>Pelanggan</th><th class="r">Nota</th><th class="r">Sisa hutang</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($debitur as $d)
                            <tr>
                                <td><a href="{{ route('piutang.show', $d['id']) }}">{{ $d['nama'] }}</a></td>
                                <td class="r">{{ $d['nota'] }}</td>
                                <td class="r">{{ $rp($d['saldo']) }}</td>
                                <td>
                                    @if ($d['telat'] > 0)<span class="db-chip down">Telat {{ $d['telat'] }} hari</span>
                                    @else<span class="db-chip flat">Berjalan</span>@endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="db-muted">Tidak ada hutang pelanggan.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
    @endif

    {{-- Stok ayam hidup, mortalitas, produksi, penjualan per ekor, dan opname --}}
    @include('dashboard._stok')

    {{-- Transaksi terakhir --}}
    <div class="db-card">
        <div style="display:flex;justify-content:space-between;align-items:center">
            <h3 style="margin:0">Transaksi terakhir</h3>
            <a href="{{ route('kasir.index') }}" class="db-muted" style="text-decoration:none">Lihat semua →</a>
        </div>
        <div style="overflow-x:auto;margin-top:.5rem">
            <table class="db-table">
                <thead><tr><th>No. nota</th><th>Waktu</th><th>Pelanggan</th>@if ($manajemen)<th>Kasir</th>@endif<th class="r">Total</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($recent as $s)
                        @php $sisa = $s->total_amount - ($s->dibayar ?? 0); @endphp
                        <tr style="{{ $s->is_voided ? 'color:#94a3b8' : '' }}">
                            <td><a href="{{ route('kasir.show', $s) }}">{{ $s->sale_number }}</a></td>
                            <td>{{ $s->created_at->timezone(config('app.timezone'))->format('d/m H:i') }}</td>
                            <td>{{ $s->customer_name }}</td>
                            @if ($manajemen)<td>{{ $s->cashier?->name }}</td>@endif
                            <td class="r">{{ $rp($s->total_amount) }}</td>
                            <td>
                                @if ($s->is_voided)<span class="db-chip down">Batal</span>
                                @elseif ($sisa > 0.004)<span class="db-chip warn">Hutang {{ $rp($sisa) }}</span>
                                @else<span class="db-chip up">Lunas</span>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="db-muted">Belum ada transaksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function () {
    var box = document.querySelector('.db-chart');
    if (!box) return;
    var tip = box.querySelector('.db-tip');
    box.querySelectorAll('.db-col').forEach(function (g) {
        function show(e) {
            var r = box.getBoundingClientRect(), p = e.touches ? e.touches[0] : e;
            tip.innerHTML = '<strong>' + g.dataset.d + '</strong><br>Omzet ' + g.dataset.o +
                '<br>Kas masuk ' + g.dataset.k + '<br>' + g.dataset.t + ' transaksi';
            var x = p.clientX - r.left + 14;
            if (x + tip.offsetWidth > r.width) x = p.clientX - r.left - tip.offsetWidth - 14;
            tip.style.left = Math.max(0, x) + 'px';
            tip.style.top = (p.clientY - r.top - 10) + 'px';
            tip.style.opacity = 1;
        }
        g.addEventListener('mousemove', show);
        g.addEventListener('touchstart', show, { passive: true });
        g.addEventListener('mouseleave', function () { tip.style.opacity = 0; });
    });
})();
</script>
@endsection