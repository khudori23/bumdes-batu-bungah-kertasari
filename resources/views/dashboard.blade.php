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
        if ($prev <= 0) return new HtmlString('<span class="dx-chip flat">&#9679; baru</span> <span class="dx-mut">belum ada data ' . e($banding) . '</span>');
        $d = round(($cur - $prev) / $prev * 100, 1);
        $cls = $d > 0 ? 'up' : ($d < 0 ? 'down' : 'flat');
        $ico = $d > 0 ? '&#9650;' : ($d < 0 ? '&#9660;' : '&#9679;');
        return new HtmlString('<span class="dx-chip ' . $cls . '">' . $ico . ' ' . number_format(abs($d), 1, ',', '.') . '%</span> <span class="dx-mut">vs ' . e($banding) . '</span>');
    };

    $jam = (int) now()->format('H');
    $salam = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 19 ? 'Selamat sore' : 'Selamat malam'));
    $rentang = $from->equalTo($to)
        ? $from->locale('id')->translatedFormat('l, d F Y')
        : $from->locale('id')->translatedFormat('d M') . ' - ' . $to->locale('id')->translatedFormat('d M Y');

    $kosongTrx = ((int) $now['trx']) === 0;
@endphp

<link rel="stylesheet" href="{{ asset('css/dashboard-v2.css') }}?v={{ @filemtime(public_path('css/dashboard-v2.css')) }}">

<div class="dx">
    <div class="dx-head">
        <div>
            <h2>{{ $salam }}, {{ auth()->user()->name }}</h2>
            <div class="dx-mut" style="font-size:.88rem;margin-top:.2rem">{{ $rentang }}</div>
        </div>
        @if ($manajemen)
            <div class="dx-seg">
                @foreach ($periodeList as $key => $label)
                    <a href="{{ route('dashboard', ['periode' => $key]) }}" class="{{ $periode === $key ? 'on' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
        @else
            <a class="dx-btn" href="{{ route('kasir.create') }}">Transaksi baru</a>
        @endif
    </div>

    @if ($manajemen)
        @php
            $telat = $aging['d7'] + $aging['d30'] + $aging['d30p'];
            $kgProd = (float) ($arus['produksi']['k'] ?? 0);
            $stokColl = collect($stokProduk ?? []);
            $hasilTot = (float) $stokColl->sum('hasil');
            $rendemen = $kgProd > 0 ? $hasilTot / $kgProd * 100 : null;
        @endphp

        {{-- 4 KPI utama --}}
        <div class="dx-kpis">
            <div class="dx-card">
                <div class="dx-l">Omzet</div>
                @if ($kosongTrx)
                    <div class="dx-v na">Tidak ada data</div>
                @else
                    <div class="dx-v">{{ $rp($now['omzet']) }}</div>
                @endif
                <div>{{ $chip($now['omzet'], $prev['omzet']) }}</div>
            </div>
            <div class="dx-card">
                <div class="dx-l">Kas masuk</div>
                @if ($kosongTrx && $now['kas'] <= 0)
                    <div class="dx-v na">Tidak ada data</div>
                @else
                    <div class="dx-v">{{ $rp($now['kas']) }}</div>
                @endif
                <div>{{ $chip($now['kas'], $prev['kas']) }}</div>
            </div>
            <div class="dx-card">
                <div class="dx-l">Piutang beredar</div>
                <div class="dx-v">{{ $rp(array_sum($aging)) }}</div>
                <div class="dx-sub">{{ $jumlahDebitur }} pelanggan masih berhutang</div>
                @if ($telat > 0)
                    <div style="margin-top:.3rem"><span class="dx-chip bad">&#9888; {{ $rp($telat) }} lewat tempo</span></div>
                @endif
            </div>
            <div class="dx-card">
                <div class="dx-l">Rendemen</div>
                @if ($rendemen !== null)
                    <div class="dx-v">{{ number_format($rendemen, 1, ',', '.') }}<small> %</small></div>
                    <div class="dx-sub">{{ number_format($hasilTot, 1, ',', '.') }} kg hasil dari {{ number_format($kgProd, 1, ',', '.') }} kg ayam</div>
                @else
                    <div class="dx-v na">Tidak ada data</div>
                    <div class="dx-sub">Belum ada ayam masuk produksi</div>
                @endif
            </div>
        </div>

        {{-- Ringkasan lain --}}
        <div class="dx-mini">
            <div class="dx-card">
                <div class="dx-l">Transaksi</div>
                <div class="dx-v" style="font-size:20px">{{ number_format($now['trx'], 0, ',', '.') }}</div>
                <div>{{ $chip($now['trx'], $prev['trx']) }}</div>
                <div class="dx-sub">Rata-rata {{ $rp($now['trx'] ? $now['omzet'] / $now['trx'] : 0) }} / nota</div>
            </div>
            <div class="dx-card">
                <div class="dx-l">Volume terjual</div>
                <div class="dx-v" style="font-size:20px">{{ number_format($now['kg'], 1, ',', '.') }} <small>kg</small></div>
                <div>{{ $chip($now['kg'], $prev['kg']) }}</div>
            </div>
        </div>

        {{-- Perlu perhatian --}}
        @php
            $al = [];
            if ($telat > 0) {
                $al[] = ['bahaya', 'Piutang lewat jatuh tempo', $rp($telat) . ', ' . $jumlahTelat . ' pelanggan' . ($tertua > 0 ? ', tertua ' . $tertua . ' hari' : '')];
            }
            if ((int) $batal->n > 0) {
                $al[] = ['bahaya', 'Nota dibatalkan', $batal->n . ' nota (' . $rp($batal->total) . ') pada periode ini'];
            }
            $oa = collect($opnameAyam ?? [])->first();
            if ($oa && (float) $oa->diff_birds != 0) {
                $al[] = ['warn', 'Selisih opname ayam hidup', ((float) $oa->diff_birds > 0 ? '+' : '') . $oa->diff_birds . ' ekor pada ' . \Illuminate\Support\Carbon::parse($oa->opname_date)->format('d/m/Y')];
            }
            if ((int) ($arus['mati']['e'] ?? 0) > 0) {
                $al[] = ['warn', 'Ayam mati', number_format($arus['mati']['e'], 0, ',', '.') . ' ekor (' . number_format($arus['mati']['k'], 1, ',', '.') . ' kg) pada periode ini'];
            }
            $habis = $stokColl->filter(fn ($p) => (float) $p->stok <= 0)->count();
            if ($habis > 0) {
                $al[] = ['warn', 'Stok produk habis', $habis . ' produk stoknya 0 atau minus'];
            }
            $opKurang = $stokColl->filter(fn ($p) => $p->opname && (float) $p->opname->difference_qty < 0)->count();
            if ($opKurang > 0) {
                $al[] = ['warn', 'Opname produk kurang', $opKurang . ' produk fisiknya lebih sedikit dari sistem pada opname terakhir'];
            }
        @endphp
        <div class="dx-title">Perlu perhatian</div>
        @if (count($al))
            <div class="dx-alerts" style="margin-bottom:.75rem">
                @foreach ($al as [$lv, $tt, $dd])
                    <div class="dx-al {{ $lv }}">
                        <span class="ic">!</span>
                        <div>
                            <div class="tt">{{ $tt }} <span class="dx-chip {{ $lv === 'bahaya' ? 'bad' : 'warn' }}">{{ $lv === 'bahaya' ? 'Bahaya' : 'Cek' }}</span></div>
                            <div class="dd">{{ $dd }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="dx-card dx-ok" style="margin-bottom:.75rem"><span class="ic">&#10003;</span> Semua aman</div>
        @endif

        {{-- Tren --}}
        @php
            $W = 720; $H = 250; $pl = 48; $pr = 12; $pt = 14; $pb = 28;
            $cnt = count($chart);
            $maxV = max(1, collect($chart)->max(fn ($d) => max($d['omzet'], $d['kas'])));
            $mag = 10 ** floor(log10($maxV));
            $top = ceil($maxV / $mag * 2) / 2 * $mag;
            $pw = $W - $pl - $pr; $ph = $H - $pt - $pb; $slot = $pw / max(1, $cnt);
            $y = fn ($v) => $pt + $ph * (1 - $v / $top);
            $every = max(1, (int) ceil($cnt / 10));
            $kosong = collect($chart)->sum('omzet') + collect($chart)->sum('kas') <= 0;
            $ptsO = []; $ptsK = [];
        @endphp
        <div class="dx-grid two">
            <div class="dx-card">
                <h3>Tren omzet vs kas masuk {{ $periode === 'hari' ? '(14 hari terakhir)' : '(' . $periodeList[$periode] . ')' }}</h3>
                <div class="dx-legend-line">
                    <span><i style="background:var(--teal)"></i>Omzet</span>
                    <span><i style="background:var(--grey)"></i>Kas masuk</span>
                </div>
                @if ($kosong)
                    <div class="dx-mut" style="padding:2.5rem 0;text-align:center">Tidak ada data pada periode ini</div>
                @else
                    <div class="dx-chart">
                        <div class="dx-tip"></div>
                        <svg viewBox="0 0 {{ $W }} {{ $H }}" role="img" aria-label="Grafik omzet dan kas masuk harian">
                            @for ($g = 0; $g <= 4; $g++)
                                @php $gv = $top * $g / 4; @endphp
                                <line x1="{{ $pl }}" x2="{{ $W - $pr }}" y1="{{ $y($gv) }}" y2="{{ $y($gv) }}" stroke="#E2E8F0" stroke-dasharray="{{ $g ? '3 4' : '0' }}"/>
                                <text x="{{ $pl - 8 }}" y="{{ $y($gv) + 4 }}" text-anchor="end" font-size="10.5" fill="#64748B">{{ $rpk($gv) }}</text>
                            @endfor
                            @foreach ($chart as $i => $d)
                                @php
                                    $cx = $pl + $slot * ($i + .5);
                                    $ptsO[] = round($cx, 1) . ',' . round($y($d['omzet']), 1);
                                    $ptsK[] = round($cx, 1) . ',' . round($y($d['kas']), 1);
                                    $tip = $d['tgl']->locale('id')->translatedFormat('D, d M');
                                @endphp
                                <g class="dx-col" data-d="{{ $tip }}" data-o="{{ $rp($d['omzet']) }}" data-k="{{ $rp($d['kas']) }}" data-t="{{ $d['trx'] }}">
                                    <line class="dx-guide" x1="{{ $cx }}" x2="{{ $cx }}" y1="{{ $pt }}" y2="{{ $pt + $ph }}"/>
                                    <rect class="dx-hit" x="{{ $pl + $slot * $i }}" y="{{ $pt }}" width="{{ $slot }}" height="{{ $ph }}"/>
                                </g>
                                @if ($i % $every === 0)
                                    <text x="{{ $cx }}" y="{{ $H - 8 }}" text-anchor="middle" font-size="10.5" fill="#64748B">{{ $d['tgl']->format('d/m') }}</text>
                                @endif
                            @endforeach
                            <polygon points="{{ round($pl + $slot * .5, 1) }},{{ $pt + $ph }} {{ implode(' ', $ptsO) }} {{ round($pl + $slot * ($cnt - .5), 1) }},{{ $pt + $ph }}" fill="#0D9488" fill-opacity=".10" pointer-events="none"/>
                            <polyline points="{{ implode(' ', $ptsK) }}" fill="none" stroke="#94A3B8" stroke-width="2" stroke-dasharray="5 4" stroke-linejoin="round" stroke-linecap="round" pointer-events="none"/>
                            <polyline points="{{ implode(' ', $ptsO) }}" fill="none" stroke="#0D9488" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" pointer-events="none"/>
                            @if ($cnt <= 31)
                                @foreach ($ptsO as $p)
                                    @php [$px, $py] = explode(',', $p); @endphp
                                    <circle cx="{{ $px }}" cy="{{ $py }}" r="3" fill="#fff" stroke="#0D9488" stroke-width="1.8" pointer-events="none"/>
                                @endforeach
                            @endif
                        </svg>
                    </div>
                @endif
            </div>

            {{-- Produk terlaris --}}
            <div class="dx-card">
                <h3>Produk terlaris</h3>
                @php $maxP = max(1, (float) ($produk->max('omzet') ?? 0)); @endphp
                @forelse ($produk as $p)
                    <div class="dx-row">
                        <div class="t">
                            <span><strong>{{ $p->name }}</strong> <span class="dx-mut">{{ rtrim(rtrim(number_format($p->qty, 2, ',', '.'), '0'), ',') }} {{ strtolower($p->unit) }}</span></span>
                            <span>{{ $rpk($p->omzet) }}</span>
                        </div>
                        <div class="dx-track"><div class="dx-fill" style="width:{{ round($p->omzet / $maxP * 100) }}%"></div></div>
                    </div>
                @empty
                    <div class="dx-mut">Tidak ada penjualan pada periode ini.</div>
                @endforelse
            </div>
        </div>

        {{-- Piutang --}}
        @php
            $tot = array_sum($aging);
            $seg = ['belum' => ['Belum jatuh tempo', '#CBD5E1'], 'd7' => ['Telat 1-7 hari', '#FCD34D'], 'd30' => ['Telat 8-30 hari', '#B45309'], 'd30p' => ['Telat > 30 hari', '#E11D48']];
        @endphp
        <div class="dx-grid half">
            <div class="dx-card">
                <h3>Umur piutang</h3>
                @if ($tot > 0.004)
                    <div class="dx-stack">
                        @foreach ($seg as $k => [$lab, $col])
                            @if ($aging[$k] > 0)<div style="width:{{ $aging[$k] / $tot * 100 }}%;background:{{ $col }}" title="{{ $lab }}"></div>@endif
                        @endforeach
                    </div>
                    <div class="dx-legend">
                        @foreach ($seg as $k => [$lab, $col])
                            <div><i style="background:{{ $col }}"></i>{{ $lab }}<br><strong>{{ $rp($aging[$k]) }}</strong></div>
                        @endforeach
                    </div>
                @else
                    <div class="dx-mut">Tidak ada piutang beredar.</div>
                @endif
            </div>

            <div class="dx-card">
                <h3>Pelanggan dengan hutang terbesar</h3>
                @forelse ($debitur as $d)
                    <div class="dx-li">
                        <div class="a">
                            <div class="n"><a href="{{ route('piutang.show', $d['id']) }}">{{ $d['nama'] }}</a></div>
                            <div class="s">{{ $d['nota'] }} nota</div>
                        </div>
                        <div class="b">
                            <div class="n">{{ $rp($d['saldo']) }}</div>
                            @if ($d['telat'] > 0)<span class="dx-chip bad">&#9888; Telat {{ $d['telat'] }} hari</span>
                            @else<span class="dx-chip flat">&#9679; Berjalan</span>@endif
                        </div>
                    </div>
                @empty
                    <div class="dx-mut">Tidak ada hutang pelanggan.</div>
                @endforelse
            </div>
        </div>
    @else
        {{-- Kasir dan pengawas: KPI dasar tetap seperti sebelumnya --}}
        <div class="dx-kpis k3">
            <div class="dx-card">
                <div class="dx-l">Omzet</div><div class="dx-v">{{ $rp($now['omzet']) }}</div>
                <div>{{ $chip($now['omzet'], $prev['omzet']) }}</div>
            </div>
            <div class="dx-card">
                <div class="dx-l">Kas masuk</div><div class="dx-v">{{ $rp($now['kas']) }}</div>
                <div>{{ $chip($now['kas'], $prev['kas']) }}</div>
            </div>
            <div class="dx-card">
                <div class="dx-l">Transaksi</div><div class="dx-v">{{ number_format($now['trx'], 0, ',', '.') }}</div>
                <div>{{ $chip($now['trx'], $prev['trx']) }}</div>
                <div class="dx-sub">Rata-rata {{ $rp($now['trx'] ? $now['omzet'] / $now['trx'] : 0) }} / nota</div>
            </div>
        </div>
    @endif

    {{-- Stok ayam hidup, mortalitas, produksi, penjualan per ekor, dan opname --}}
    @unless (auth()->user()->isKasir())
        @include('dashboard._stok')
    @endunless

    {{-- Transaksi terakhir --}}
    <div class="dx-card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.3rem">
            <h3 style="margin:0">Transaksi terakhir</h3>
            <a href="{{ route('kasir.index') }}" class="dx-mut" style="text-decoration:none">Lihat semua &rarr;</a>
        </div>
        @forelse ($recent as $s)
            @php $sisa = $s->total_amount - ($s->dibayar ?? 0); @endphp
            <div class="dx-li {{ $s->is_voided ? 'void' : '' }}">
                <div class="a">
                    <div class="n"><a href="{{ route('kasir.show', $s) }}">{{ $s->sale_number }}</a> &middot; {{ $s->customer_name }}</div>
                    <div class="s">{{ $s->created_at->timezone(config('app.timezone'))->format('d/m H:i') }}@if ($manajemen) &middot; {{ $s->cashier?->name }}@endif</div>
                </div>
                <div class="b">
                    <div class="n">{{ $rp($s->total_amount) }}</div>
                    @if ($s->is_voided)<span class="dx-chip bad">&#10005; Batal</span>
                    @elseif ($sisa > 0.004)<span class="dx-chip warn">&#9888; Hutang {{ $rp($sisa) }}</span>
                    @else<span class="dx-chip up">&#10003; Lunas</span>@endif
                </div>
            </div>
        @empty
            <div class="dx-mut">Belum ada transaksi.</div>
        @endforelse
    </div>
</div>

<script>
(function () {
    var box = document.querySelector('.dx-chart');
    if (!box) return;
    var tip = box.querySelector('.dx-tip');
    box.querySelectorAll('.dx-col').forEach(function (g) {
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