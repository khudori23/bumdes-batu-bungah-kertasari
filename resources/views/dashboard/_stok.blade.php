@php
    $fm = fn ($v, $d = 0) => number_format((float) $v, $d, ',', '.');
    $labelPeriode = $periodeList[$periode] ?? '';
    $totalHasil = (float) $stokProduk->sum('hasil');
    $rendPeriode = $arus['produksi']['k'] > 0 ? $totalHasil / $arus['produksi']['k'] * 100 : null;
    $lipat = (bool) ($manajemen ?? false);
    $oaTerakhir = collect($opnameAyam)->first();
    $ikon = fn ($v) => $v < 0 ? '&#9660;' : ($v > 0 ? '&#9650;' : '&#10003;');
    $kelas = fn ($v) => $v < 0 ? 'bad' : ($v > 0 ? 'warn' : 'up');

    $perhatian = [];
    if (! $lipat) {
        if ((int) $arus['mati']['e'] > 0) {
            $perhatian[] = ['Ayam mati tercatat', $fm($arus['mati']['e']) . ' ekor (' . $fm($arus['mati']['k'], 1) . ' kg) pada periode ini'];
        }
        if ($oaTerakhir && (float) $oaTerakhir->diff_birds != 0) {
            $perhatian[] = ['Selisih opname ayam hidup', ((float) $oaTerakhir->diff_birds > 0 ? '+' : '') . $oaTerakhir->diff_birds . ' ekor pada ' . \Illuminate\Support\Carbon::parse($oaTerakhir->opname_date)->format('d/m/Y')];
        }
    }
@endphp

@once
    <link rel="stylesheet" href="{{ asset('css/dashboard-stok.css') }}?v={{ @filemtime(public_path('css/dashboard-stok.css')) }}">
@endonce

<section class="dx-stok">

    @unless ($lipat)
        <div class="sk-act">
            <a href="{{ route('pembelian.index') }}"><svg viewBox="0 0 24 24"><path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/></svg>Pembelian</a>
            <a href="{{ route('opnameayam.index') }}"><svg viewBox="0 0 24 24"><rect x="6" y="4" width="12" height="17" rx="2"/><path d="M9 4h6v3H9zM9 12h6M9 16h6"/></svg>Opname Ayam</a>
            <a href="{{ route('ayamkeluar.index') }}"><svg viewBox="0 0 24 24"><path d="M5 12h12M13 6l6 6-6 6M5 5v14"/></svg>Ayam Keluar</a>
        </div>

        <div class="sk-h"><h3>Perlu perhatian</h3></div>
        @if (count($perhatian))
            <div class="sk-note">
                @foreach ($perhatian as [$jd, $ket])
                    <div class="sk-al"><div><b>{{ $jd }}</b><span>{{ $ket }}</span></div></div>
                @endforeach
            </div>
        @else
            <div class="sk-ok">&#10003; Semua aman pada periode ini</div>
        @endif
    @endunless

    <div class="sk-h"><h3>Ringkasan kandang</h3><span class="sk-badge">{{ $labelPeriode }}</span></div>
    <div class="sk-grid">
        <div class="sk-card">
            <div class="sk-ic"><svg viewBox="0 0 24 24"><path d="M3 11l9-8 9 8v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/></svg></div>
            <div class="sk-l">Stok ayam hidup</div>
            <div class="sk-v">{{ $fm($stokAyam['ekor']) }} <small>ekor</small></div>
            <div class="sk-s">{{ $fm($stokAyam['kg'], 1) }} kg (stok sekarang)</div>
        </div>
        <div class="sk-card t-amber">
            <div class="sk-ic"><svg viewBox="0 0 24 24"><path d="M12 3l10 18H2z"/><path d="M12 10v5M12 18h.01"/></svg></div>
            <div class="sk-l">Ayam mati</div>
            <div class="sk-v" style="color:{{ $arus['mati']['e'] > 0 ? '#B45309' : 'inherit' }}">{{ $fm($arus['mati']['e']) }} <small>ekor</small></div>
            <div class="sk-s">{{ $fm($arus['mati']['k'], 1) }} kg @if ($arus['mati']['e'] > 0)&middot; <span class="sk-chip warn">perlu dicek</span>@endif</div>
        </div>
        <div class="sk-card t-blue">
            <div class="sk-ic"><svg viewBox="0 0 24 24"><path d="M4 20V10l5 3V9l5 3V6h6v14z"/></svg></div>
            <div class="sk-l">Masuk produksi</div>
            <div class="sk-v">{{ $fm($arus['produksi']['e']) }} <small>ekor</small></div>
            <div class="sk-s">{{ $fm($arus['produksi']['k'], 1) }} kg @if ($rendPeriode !== null)&middot; rendemen {{ $fm($rendPeriode, 1) }}%@endif</div>
        </div>
        <div class="sk-card t-violet">
            <div class="sk-ic"><svg viewBox="0 0 24 24"><path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="8.5" r="1.2"/></svg></div>
            <div class="sk-l">Dijual per ekor</div>
            <div class="sk-v">{{ $fm($arus['jual']['e']) }} <small>ekor</small></div>
            <div class="sk-s">{{ $fm($arus['jual']['k'], 1) }} kg</div>
        </div>
    </div>

    <div class="sk-h"><h3>Opname ayam hidup</h3></div>
    <div class="sk-panel">
        <details class="sk-fold" @if (! $lipat) open @endif>
            <summary>Opname terakhir</summary>
            <table class="sk-t">
                <thead><tr><th>Tanggal</th><th class="r">Sistem</th><th class="r">Fisik</th><th class="r">Selisih (ekor)</th><th class="r">Selisih (kg)</th><th>Alasan</th></tr></thead>
                <tbody>
                    @forelse ($opnameAyam as $o)
                        <tr>
                            <td class="sk-name" data-label="Tanggal">{{ \Illuminate\Support\Carbon::parse($o->opname_date)->format('d/m/Y') }}</td>
                            <td class="r" data-label="Sistem">{{ $fm($o->system_birds) }}</td>
                            <td class="r" data-label="Fisik">{{ $fm($o->actual_birds) }}</td>
                            <td class="r" data-label="Selisih (ekor)"><span class="sk-chip {{ $kelas($o->diff_birds) }}">{!! $ikon($o->diff_birds) !!} {{ $o->diff_birds > 0 ? '+' : '' }}{{ $o->diff_birds }}</span></td>
                            <td class="r" data-label="Selisih (kg)">{{ $o->diff_kg > 0 ? '+' : '' }}{{ $fm($o->diff_kg, 2) }}</td>
                            <td data-label="Alasan">{{ $o->reason ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="sk-empty sk-mut">Belum ada opname ayam hidup.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </details>
    </div>

    @unless (auth()->user()->isPengawasKandang())
    <div class="sk-h"><h3>Hasil produksi</h3><span class="sk-badge">{{ $labelPeriode }}</span></div>
    <div class="sk-panel">
        <details class="sk-fold" open>
            <summary>Hasil, stok, dan opname per item</summary>
            <table class="sk-t">
                <thead><tr><th>Produk</th><th class="r">Hasil produksi (kg)</th><th class="r">Stok sekarang (kg)</th><th>Opname terakhir</th></tr></thead>
                <tbody>
                    @forelse ($stokProduk as $p)
                        <tr>
                            <td class="sk-name" data-label="Produk">{{ $p->name }}</td>
                            <td class="r" data-label="Hasil (kg)">{{ $fm($p->hasil, 2) }}</td>
                            <td class="r" data-label="Stok (kg)">{{ $fm($p->stok, 2) }}</td>
                            <td data-label="Opname">
                                @if ($p->opname)
                                    {{ \Illuminate\Support\Carbon::parse($p->opname->adjustment_date)->format('d/m') }}:
                                    {{ $fm($p->opname->system_qty, 2) }} &rarr; {{ $fm($p->opname->physical_qty, 2) }}
                                    <span class="sk-chip {{ $kelas($p->opname->difference_qty) }}">{!! $ikon($p->opname->difference_qty) !!} {{ $p->opname->difference_qty > 0 ? '+' : '' }}{{ $fm($p->opname->difference_qty, 2) }}</span>
                                @else
                                    <span class="sk-mut">belum pernah</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="sk-empty sk-mut">Belum ada produk.</td></tr>
                    @endforelse
                    @if ($stokProduk->count() > 0)
                        <tr class="sk-total">
                            <td class="sk-name" data-label="">Total</td>
                            <td class="r" data-label="Hasil (kg)">{{ $fm($totalHasil, 2) }}</td>
                            <td class="r" data-label="Stok (kg)">{{ $fm($stokProduk->sum('stok'), 2) }}</td>
                            <td></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </details>
    </div>
    @endunless
</section>