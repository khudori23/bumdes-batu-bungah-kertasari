@extends('layouts.app')

@section('title', 'Laporan Kandang')

@section('content')
@php
    $rp   = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $kg   = fn ($n) => number_format((float) $n, 2, ',', '.');
    $ekor = fn ($n) => number_format((float) $n, 0, ',', '.');
    $tgl  = fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('d/m/Y');
    $rata = (float) $beli->kg > 0 ? (float) $beli->biaya / (float) $beli->kg : 0;
    $pct  = (int) $beli->ekor > 0 ? $mati->ekor / $beli->ekor * 100 : null;
@endphp

<div class="fchips">
    @foreach ($preset as $nama => $r)
        <a href="{{ route('laporankandang.index', ['dari' => $r[0], 'sampai' => $r[1]]) }}" class="{{ $dari === $r[0] && $sampai === $r[1] ? 'on' : '' }}">{{ $nama }}</a>
    @endforeach
</div>

<form method="GET" action="{{ route('laporankandang.index') }}" class="card filter" style="display:flex;gap:.8rem;flex-wrap:wrap;align-items:end">
    <div><label for="dari" style="margin-top:0">Dari tanggal</label><input type="date" id="dari" name="dari" value="{{ $dari }}"></div>
    <div><label for="sampai" style="margin-top:0">Sampai tanggal</label><input type="date" id="sampai" name="sampai" value="{{ $sampai }}"></div>
    <button type="submit" class="btn" style="margin:0">Terapkan</button>
</form>

<div class="card">
    <h2 style="margin:0">Laporan Kandang</h2>
    <div class="muted">{{ $tgl($dari) }} s.d. {{ $tgl($sampai) }} &middot; Stok ayam hidup sekarang: <strong>{{ $ekor($stok['ekor']) }} ekor</strong> ({{ $kg($stok['kg']) }} kg)</div>
</div>

<div class="stats">
    <div class="card"><div class="stat-l">Ayam dibeli</div><div class="stat-v">{{ $ekor($beli->ekor) }}</div><div class="stat-s">{{ $beli->jumlah }} transaksi &middot; {{ $kg($beli->kg) }} kg</div></div>
    <div class="card"><div class="stat-l">Total biaya</div><div class="stat-v">{{ $rp($beli->biaya) }}</div><div class="stat-s">Rata-rata {{ $rp($rata) }}/kg</div></div>
    <div class="card"><div class="stat-l">Ayam mati</div><div class="stat-v">{{ $ekor($mati->ekor) }}</div><div class="stat-s">{{ $kg($mati->kg) }} kg{{ $pct !== null ? ' · ' . number_format($pct, 1, ',', '.') . '% dari pembelian' : '' }}</div></div>
    <div class="card"><div class="stat-l">Selisih opname</div><div class="stat-v">{{ $opRing->selisih_ekor > 0 ? '+' : '' }}{{ $ekor($opRing->selisih_ekor) }}</div><div class="stat-s">{{ $opRing->jumlah }}x opname &middot; {{ $opRing->selisih_kg > 0 ? '+' : '' }}{{ $kg($opRing->selisih_kg) }} kg</div></div>
</div>

<div class="card">
    <h3 class="sec-title">Pembelian per pemasok</h3>
    <table class="rtable">
        <thead><tr><th>Pemasok</th><th class="right">Transaksi</th><th class="right">Ekor</th><th class="right">Berat (kg)</th><th class="right">Biaya</th></tr></thead>
        <tbody>
            @forelse ($perPemasok as $r)
                <tr>
                    <td data-label="Pemasok"><strong>{{ $r->nama }}</strong></td>
                    <td class="right" data-label="Transaksi">{{ $r->jumlah }}</td>
                    <td class="right" data-label="Ekor">{{ $ekor($r->ekor) }}</td>
                    <td class="right" data-label="Berat (kg)">{{ $kg($r->kg) }}</td>
                    <td class="right" data-label="Biaya">{{ $rp($r->biaya) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Tidak ada pembelian pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="card">
    <h3 class="sec-title">Ayam mati per penyebab</h3>
    <table class="rtable">
        <thead><tr><th>Penyebab</th><th class="right">Ekor</th><th class="right">Berat (kg)</th></tr></thead>
        <tbody>
            @forelse ($sebab as $r)
                <tr>
                    <td data-label="Penyebab">{{ $r->sebab }}</td>
                    <td class="right" data-label="Ekor">{{ $ekor($r->ekor) }}</td>
                    <td class="right" data-label="Berat (kg)">{{ $kg($r->kg) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="muted">Tidak ada ayam mati pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="card">
    <h3 class="sec-title">Opname ayam hidup (10 terakhir)</h3>
    <table class="rtable">
        <thead><tr><th>Tanggal</th><th class="right">Sistem</th><th class="right">Fisik</th><th class="right">Selisih</th><th>Alasan</th></tr></thead>
        <tbody>
            @forelse ($opname as $o)
                <tr>
                    <td data-label="Tanggal">{{ $tgl($o->opname_date) }}</td>
                    <td class="right" data-label="Sistem">{{ $ekor($o->system_birds) }}</td>
                    <td class="right" data-label="Fisik">{{ $ekor($o->actual_birds) }}</td>
                    <td class="right" data-label="Selisih"><span class="chip {{ $o->diff_birds < 0 ? 'down' : ($o->diff_birds > 0 ? 'warn' : 'up') }}">{{ $o->diff_birds > 0 ? '+' : '' }}{{ $o->diff_birds }}</span></td>
                    <td data-label="Alasan">{{ $o->reason ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Tidak ada opname pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection