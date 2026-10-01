@extends('layouts.app')

@section('title', 'Laba Rugi ' . $tahun)

@section('content')
@include('gaji._nav')
@php
    $rp = fn ($x) => number_format($x, 0, ',', '.');
    $pct = fn ($x) => $x !== null ? number_format($x, 1, ',', '.') . '%' : '-';
@endphp
<style>
    .lr td.r { text-align: right; white-space: nowrap; }
    .lr tbody tr:nth-child(even) { background: #f8fafc; }
    .lr tfoot td { font-weight: 700; background: #e2e8f0; }
    .lr .neg { color: #e11d48; } .lr .pos { color: #0f766e; } .lr .warn { color: #b45309; }
    @media print { header, .noprint { display: none !important; } body { background: #fff; } main { max-width: none; margin: 0; } }
</style>

<div class="card lr">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.6rem">
        <h2 style="margin:0">Laba Rugi Sederhana {{ $tahun }}</h2>
        <form method="GET" action="{{ route('gaji.labarugi') }}" class="noprint" style="display:flex;gap:.5rem">
            <input type="number" name="tahun" value="{{ $tahun }}" min="2020" max="2100" style="width:100px;padding:.45rem;border:1px solid #cbd5e1;border-radius:8px">
            <button type="submit" class="btn" style="margin:0;padding:.5rem 1rem">Tampilkan</button>
            <button type="button" onclick="window.print()" class="btn" style="margin:0;padding:.5rem 1rem;background:#fff;color:#0f172a;border:1px solid #cbd5e1">Cetak</button>
        </form>
    </div>

    <div style="overflow-x:auto;margin-top:1rem">
    <table>
        <thead>
            <tr>
                <th>Bulan</th><th class="right">Omzet</th><th class="right">Pembelian ayam</th><th class="right">Beban gaji</th>
                <th class="right">Gaji/Omzet</th><th class="right">Pengeluaran lain</th><th class="right">Laba</th><th class="right">Terbit, belum dibayar</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($baris as $b)
                <tr>
                    <td>{{ $b['bulan'] }}</td>
                    <td class="r">{{ $rp($b['omzet']) }}</td>
                    <td class="r">{{ $rp($b['beli']) }}</td>
                    <td class="r">{{ $rp($b['gaji']) }}</td>
                    <td class="r {{ $b['rasio'] !== null && $b['rasio'] > 30 ? 'warn' : '' }}">{{ $pct($b['rasio']) }}</td>
                    <td class="r">{{ $rp($b['lain']) }}</td>
                    <td class="r {{ $b['laba'] < 0 ? 'neg' : 'pos' }}">{{ $rp($b['laba']) }}</td>
                    <td class="r">{{ $b['belum'] > 0 ? $rp($b['belum']) : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>TOTAL</td>
                <td class="r">{{ $rp($tot['omzet']) }}</td>
                <td class="r">{{ $rp($tot['beli']) }}</td>
                <td class="r">{{ $rp($tot['gaji']) }}</td>
                <td class="r">{{ $pct($tot['rasio']) }}</td>
                <td class="r">{{ $rp($tot['lain']) }}</td>
                <td class="r {{ $tot['laba'] < 0 ? 'neg' : 'pos' }}">{{ $rp($tot['laba']) }}</td>
                <td class="r">{{ $rp($tot['belum']) }}</td>
            </tr>
        </tfoot>
    </table>
    </div>

    <p class="muted" style="margin-top:1rem;line-height:1.5">
        Omzet = semua nota yang tidak dibatalkan (termasuk yang masih piutang). Pembelian ayam = total biaya pembelian pada bulan pembelian.
        Beban gaji = slip berstatus Dibayar. Pengeluaran lain = pengeluaran selain Upah / Gaji.
        Laba = omzet &minus; pembelian ayam &minus; gaji &minus; pengeluaran lain. Ini hitungan kas sederhana: stok ayam atau daging yang belum terjual belum diperhitungkan.
    </p>
</div>
@endsection