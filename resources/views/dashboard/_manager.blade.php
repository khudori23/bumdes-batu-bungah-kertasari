@php
    $sisaBersih = $now['omzet'] - $keluarNow;
    $sisaPrev   = $prev['omzet'] - $keluarPrev;
    $rasioKel   = $now['omzet'] > 0 ? $keluarNow / $now['omzet'] * 100 : null;
    $avgNota    = $now['trx'] ? $now['omzet'] / $now['trx'] : 0;
    $chipKel = function ($cur, $prv) use ($banding) {
        if ($prv <= 0) {
            return new \Illuminate\Support\HtmlString('<span class="dx-chip flat">&#9679; baru</span>');
        }
        $d   = round(($cur - $prv) / $prv * 100, 1);
        $cls = $d > 0 ? 'warn' : ($d < 0 ? 'up' : 'flat');
        $ico = $d > 0 ? '&#9650;' : ($d < 0 ? '&#9660;' : '&#9679;');
        return new \Illuminate\Support\HtmlString('<span class="dx-chip ' . $cls . '">' . $ico . ' ' . number_format(abs($d), 1, ',', '.') . '%</span> <span class="dx-mut">vs ' . e($banding) . '</span>');
    };
@endphp

@once
    <link rel="stylesheet" href="{{ asset('css/dashboard-mgr.css') }}?v={{ @filemtime(public_path('css/dashboard-mgr.css')) }}">
@endonce

<div class="mg-kpis">
    <div class="mg-k t-teal">
        <div class="mg-l">Omzet</div>
        @if ($kosongTrx)
            <div class="mg-v mg-na">Tidak ada data</div>
        @else
            <div class="mg-v">{{ $rp($now['omzet']) }}</div>
        @endif
        <div class="mg-c">{{ $chip($now['omzet'], $prev['omzet']) }}</div>
        <div class="mg-s">{{ number_format($now['trx'], 0, ',', '.') }} transaksi &middot; rata-rata {{ $rp($avgNota) }}</div>
    </div>

    <div class="mg-k t-blue">
        <div class="mg-l">Kas masuk</div>
        @if ($kosongTrx && $now['kas'] <= 0)
            <div class="mg-v mg-na">Tidak ada data</div>
        @else
            <div class="mg-v">{{ $rp($now['kas']) }}</div>
        @endif
        <div class="mg-c">{{ $chip($now['kas'], $prev['kas']) }}</div>
        <div class="mg-s">Termasuk pelunasan piutang</div>
    </div>

    <div class="mg-k t-amber">
        <div class="mg-l">Pengeluaran</div>
        @if ($keluarNow <= 0 && $keluarPrev <= 0)
            <div class="mg-v mg-na">Tidak ada data</div>
        @else
            <div class="mg-v">{{ $rp($keluarNow) }}</div>
        @endif
        <div class="mg-c">{{ $chipKel($keluarNow, $keluarPrev) }}</div>
        <div class="mg-s">@if ($rasioKel !== null){{ number_format($rasioKel, 1, ',', '.') }}% dari omzet @else Belum ada omzet @endif</div>
    </div>

    <div class="mg-k t-violet">
        <div class="mg-l">Sisa setelah pengeluaran</div>
        <div class="mg-v {{ $sisaBersih < 0 ? 'neg' : '' }}">{{ $rp($sisaBersih) }}</div>
        <div class="mg-c">{{ $chip($sisaBersih, $sisaPrev) }}</div>
        <div class="mg-s">Omzet dikurangi pengeluaran. Belum termasuk pembelian ayam &amp; gaji</div>
    </div>

    <div class="mg-k t-rose">
        <div class="mg-l">Piutang beredar</div>
        <div class="mg-v">{{ $rp(array_sum($aging)) }}</div>
        <div class="mg-s" style="margin-top:0">{{ $jumlahDebitur }} pelanggan masih berhutang</div>
        @if ($telat > 0)
            <div class="mg-c" style="margin-top:.3rem"><span class="dx-chip bad">&#9888; {{ $rp($telat) }} lewat tempo</span></div>
        @endif
    </div>

    <div class="mg-k t-slate">
        <div class="mg-l">Rendemen</div>
        @if ($rendemen !== null)
            <div class="mg-v">{{ number_format($rendemen, 1, ',', '.') }}<small> %</small></div>
            <div class="mg-s" style="margin-top:0">{{ number_format($hasilTot, 1, ',', '.') }} kg hasil dari {{ number_format($kgProd, 1, ',', '.') }} kg ayam</div>
        @else
            <div class="mg-v mg-na">Tidak ada data</div>
            <div class="mg-s" style="margin-top:0">Belum ada ayam masuk produksi</div>
        @endif
    </div>
</div>

<div class="mg-mini">
    <div class="mg-m">
        <div class="mg-l">Transaksi</div>
        <div class="mg-v">{{ number_format($now['trx'], 0, ',', '.') }}</div>
        <div class="mg-c">{{ $chip($now['trx'], $prev['trx']) }}</div>
    </div>
    <div class="mg-m">
        <div class="mg-l">Volume terjual</div>
        <div class="mg-v">{{ number_format($now['kg'], 1, ',', '.') }} <small>kg</small></div>
        <div class="mg-c">{{ $chip($now['kg'], $prev['kg']) }}</div>
    </div>
    <div class="mg-m">
        <div class="mg-l">Hasil produksi</div>
        <div class="mg-v">{{ number_format($hasilTot, 1, ',', '.') }} <small>kg</small></div>
        <div class="mg-s" style="margin-top:0">periode ini</div>
    </div>
</div>