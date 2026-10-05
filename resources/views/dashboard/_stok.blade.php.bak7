@php
    $n = fn ($v, $d = 0) => number_format((float) $v, $d, ',', '.');
    $labelPeriode = $periodeList[$periode] ?? '';
    $totalHasil = (float) $stokProduk->sum('hasil');
    $rendPeriode = $arus['produksi']['k'] > 0 ? $totalHasil / $arus['produksi']['k'] * 100 : null;
    $lipat = (bool) ($manajemen ?? false);
@endphp

<div class="dx-title">Kandang &middot; {{ $labelPeriode }}</div>
<div class="dx-kpis">
    <div class="dx-card">
        <div class="dx-l">Stok ayam hidup</div>
        <div class="dx-v">{{ $n($stokAyam['ekor']) }} <small>ekor</small></div>
        <div class="dx-sub">{{ $n($stokAyam['kg'], 1) }} kg (stok sekarang)</div>
    </div>
    <div class="dx-card">
        <div class="dx-l">Ayam mati</div>
        <div class="dx-v" style="color:{{ $arus['mati']['e'] > 0 ? '#B45309' : 'inherit' }}">{{ $n($arus['mati']['e']) }} <small>ekor</small></div>
        <div class="dx-sub">{{ $n($arus['mati']['k'], 1) }} kg</div>
        @if ($arus['mati']['e'] > 0)<span class="dx-chip warn">&#9888; perlu dicek</span>@endif
    </div>
    <div class="dx-card">
        <div class="dx-l">Masuk produksi</div>
        <div class="dx-v">{{ $n($arus['produksi']['e']) }} <small>ekor</small></div>
        <div class="dx-sub">
            {{ $n($arus['produksi']['k'], 1) }} kg
            @if ($rendPeriode !== null) &middot; rendemen {{ $n($rendPeriode, 1) }}% @endif
        </div>
    </div>
    <div class="dx-card">
        <div class="dx-l">Dijual per ekor</div>
        <div class="dx-v">{{ $n($arus['jual']['e']) }} <small>ekor</small></div>
        <div class="dx-sub">{{ $n($arus['jual']['k'], 1) }} kg</div>
    </div>
</div>

<div class="dx-card dx-fold-wrap" style="margin-bottom:.75rem">
    <details class="dx-fold" style="margin:0" @if (! $lipat) open @endif>
        <summary>Opname ayam hidup terakhir</summary>
        <div class="dx-scroll">
            <table class="dx-t">
                <thead><tr><th>Tanggal</th><th class="r">Sistem</th><th class="r">Fisik</th><th class="r">Selisih (ekor)</th><th class="r">Selisih (kg)</th><th>Alasan</th></tr></thead>
                <tbody>
                    @forelse ($opnameAyam as $o)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($o->opname_date)->format('d/m/Y') }}</td>
                            <td class="r">{{ $n($o->system_birds) }}</td>
                            <td class="r">{{ $n($o->actual_birds) }}</td>
                            <td class="r"><span class="dx-chip {{ $o->diff_birds < 0 ? 'bad' : ($o->diff_birds > 0 ? 'warn' : 'up') }}">{{ $o->diff_birds < 0 ? '&#9660;' : ($o->diff_birds > 0 ? '&#9650;' : '&#10003;') }} {{ $o->diff_birds > 0 ? '+' : '' }}{{ $o->diff_birds }}</span></td>
                            <td class="r">{{ $o->diff_kg > 0 ? '+' : '' }}{{ $n($o->diff_kg, 2) }}</td>
                            <td>{{ $o->reason ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="dx-mut">Belum ada opname ayam hidup.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </details>
</div>

<div class="dx-title">Hasil produksi &middot; {{ $labelPeriode }}</div>
<div class="dx-card" style="margin-bottom:.75rem">
    <details class="dx-fold" style="margin:0" open>
        <summary>Hasil, stok, dan opname per item</summary>
        <div class="dx-scroll">
            <table class="dx-t">
                <thead>
                    <tr><th>Produk</th><th class="r">Hasil produksi (kg)</th><th class="r">Stok sekarang (kg)</th><th>Opname terakhir</th></tr>
                </thead>
                <tbody>
                    @forelse ($stokProduk as $p)
                        <tr>
                            <td><strong>{{ $p->name }}</strong></td>
                            <td class="r">{{ $n($p->hasil, 2) }}</td>
                            <td class="r">{{ $n($p->stok, 2) }}</td>
                            <td>
                                @if ($p->opname)
                                    {{ \Illuminate\Support\Carbon::parse($p->opname->adjustment_date)->format('d/m') }}:
                                    {{ $n($p->opname->system_qty, 2) }} &rarr; {{ $n($p->opname->physical_qty, 2) }}
                                    <span class="dx-chip {{ $p->opname->difference_qty < 0 ? 'bad' : ($p->opname->difference_qty > 0 ? 'warn' : 'up') }}">{{ $p->opname->difference_qty < 0 ? '&#9660;' : ($p->opname->difference_qty > 0 ? '&#9650;' : '&#10003;') }} {{ $p->opname->difference_qty > 0 ? '+' : '' }}{{ $n($p->opname->difference_qty, 2) }}</span>
                                @else
                                    <span class="dx-mut">belum pernah</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="dx-mut">Belum ada produk.</td></tr>
                    @endforelse
                    @if ($stokProduk->count() > 0)
                        <tr>
                            <td><strong>Total</strong></td>
                            <td class="r"><strong>{{ $n($totalHasil, 2) }}</strong></td>
                            <td class="r"><strong>{{ $n($stokProduk->sum('stok'), 2) }}</strong></td>
                            <td></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </details>
</div>