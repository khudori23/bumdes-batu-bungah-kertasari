@php
    $n = fn ($v, $d = 0) => number_format((float) $v, $d, ',', '.');
    $labelPeriode = $periodeList[$periode] ?? '';
    $totalHasil = (float) $stokProduk->sum('hasil');
    $rendPeriode = $arus['produksi']['k'] > 0 ? $totalHasil / $arus['produksi']['k'] * 100 : null;
@endphp



<div class="sk">
    <div class="sk-title">Ayam hidup &middot; {{ $labelPeriode }}</div>
    <div class="sk-grid">
        <div class="sk-card">
            <div class="sk-l">Stok ayam hidup</div>
            <div class="sk-v">{{ $n($stokAyam['ekor']) }} <small>ekor</small></div>
            <div class="sk-m">{{ $n($stokAyam['kg'], 1) }} kg (stok sekarang)</div>
        </div>
        <div class="sk-card">
            <div class="sk-l">Ayam mati</div>
            <div class="sk-v" style="color:{{ $arus['mati']['e'] > 0 ? '#e11d48' : 'inherit' }}">{{ $n($arus['mati']['e']) }} <small>ekor</small></div>
            <div class="sk-m">{{ $n($arus['mati']['k'], 1) }} kg</div>
        </div>
        <div class="sk-card">
            <div class="sk-l">Masuk produksi</div>
            <div class="sk-v">{{ $n($arus['produksi']['e']) }} <small>ekor</small></div>
            <div class="sk-m">
                {{ $n($arus['produksi']['k'], 1) }} kg
                @if ($rendPeriode !== null) &middot; rendemen {{ $n($rendPeriode, 1) }}% @endif
            </div>
        </div>
        <div class="sk-card">
            <div class="sk-l">Dijual per ekor</div>
            <div class="sk-v">{{ $n($arus['jual']['e']) }} <small>ekor</small></div>
            <div class="sk-m">{{ $n($arus['jual']['k'], 1) }} kg</div>
        </div>
    </div>

    <div class="sk-card">
        <h3>Opname ayam hidup terakhir</h3>
        <div style="overflow-x:auto">
            <table class="sk-t">
                <thead><tr><th>Tanggal</th><th class="r">Sistem</th><th class="r">Fisik</th><th class="r">Selisih (ekor)</th><th class="r">Selisih (kg)</th><th>Alasan</th></tr></thead>
                <tbody>
                    @forelse ($opnameAyam as $o)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($o->opname_date)->format('d/m/Y') }}</td>
                            <td class="r">{{ $n($o->system_birds) }}</td>
                            <td class="r">{{ $n($o->actual_birds) }}</td>
                            <td class="r"><span class="sk-chip {{ $o->diff_birds < 0 ? 'down' : ($o->diff_birds > 0 ? 'warn' : 'up') }}">{{ $o->diff_birds > 0 ? '+' : '' }}{{ $o->diff_birds }}</span></td>
                            <td class="r">{{ $o->diff_kg > 0 ? '+' : '' }}{{ $n($o->diff_kg, 2) }}</td>
                            <td>{{ $o->reason ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="sk-m">Belum ada opname ayam hidup.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="sk-title">Hasil produksi &middot; {{ $labelPeriode }}</div>
    <div class="sk-card">
        <h3>Hasil, stok, dan opname per item</h3>
        <div style="overflow-x:auto">
            <table class="sk-t">
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
                                    <span class="sk-chip {{ $p->opname->difference_qty < 0 ? 'down' : ($p->opname->difference_qty > 0 ? 'warn' : 'up') }}">{{ $p->opname->difference_qty > 0 ? '+' : '' }}{{ $n($p->opname->difference_qty, 2) }}</span>
                                @else
                                    <span class="sk-m">belum pernah</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="sk-m">Belum ada produk.</td></tr>
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
    </div>
</div>