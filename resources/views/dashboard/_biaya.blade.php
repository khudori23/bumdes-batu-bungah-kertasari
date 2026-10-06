@php
    $totKel = (float) $keluarKat->sum();
    $maxKel = max(1, (float) ($keluarKat->max() ?? 0));
@endphp
<div class="dx-title">Pengeluaran</div>
<div class="dx-card" style="margin-bottom:.75rem">
    <div class="mg-total">
        <div>
            <div class="mg-l">Total pengeluaran</div>
            <div class="mg-v" style="margin:.2rem 0 0">{{ $rp($keluarNow) }}</div>
        </div>
        <a class="mg-more" href="{{ route('pengeluaran.index', ['bulan' => $from->format('Y-m')]) }}">Lihat rincian &rarr;</a>
    </div>
    @forelse ($keluarKat as $kat => $v)
        <div class="dx-row mg-bar">
            <div class="t">
                <span><strong>{{ $kat }}</strong> <span class="dx-mut">{{ $totKel > 0 ? round($v / $totKel * 100) : 0 }}%</span></span>
                <span>{{ $rp($v) }}</span>
            </div>
            <div class="dx-track"><div class="dx-fill" style="width:{{ round($v / $maxKel * 100) }}%"></div></div>
        </div>
    @empty
        <div class="dx-mut">Belum ada pengeluaran pada periode ini.</div>
    @endforelse
</div>