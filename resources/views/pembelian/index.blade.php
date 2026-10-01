@extends('layouts.app')

@section('title', 'Pembelian Ayam Hidup')

@section('content')
@php
    $rp   = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $kg   = fn ($n) => number_format((float) $n, 2, ',', '.');
    $ekor = fn ($n) => number_format((float) $n, 0, ',', '.');
    $rataHarga = (float) $ringkasan->kg > 0 ? (float) $ringkasan->biaya / (float) $ringkasan->kg : 0;
@endphp

@if ($errors->any())
    <div style="background:#ffe4e6;border:1px solid #fecdd3;padding:.6rem .9rem;border-radius:8px;margin-bottom:1rem">
        @foreach ($errors->all() as $e)
            <div class="err" style="margin:0">{{ $e }}</div>
        @endforeach
    </div>
@endif

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1rem">
    <div class="card">
        <div style="font-size:.8rem;color:#64748b">TOTAL EKOR</div>
        <div style="font-size:1.4rem;font-weight:700">{{ $ekor($ringkasan->ekor) }}</div>
        <div style="font-size:.78rem;color:#64748b">{{ $ringkasan->jumlah }} transaksi</div>
    </div>
    <div class="card">
        <div style="font-size:.8rem;color:#64748b">TOTAL BERAT</div>
        <div style="font-size:1.4rem;font-weight:700">{{ $kg($ringkasan->kg) }} kg</div>
    </div>
    <div class="card">
        <div style="font-size:.8rem;color:#64748b">TOTAL BIAYA</div>
        <div style="font-size:1.4rem;font-weight:700">{{ $rp($ringkasan->biaya) }}</div>
    </div>
    <div class="card">
        <div style="font-size:.8rem;color:#64748b">RATA-RATA HARGA/KG</div>
        <div style="font-size:1.4rem;font-weight:700">{{ $rp($rataHarga) }}</div>
    </div>
</div>

<form method="GET" action="{{ route('pembelian.index') }}" class="card" style="display:flex;gap:.8rem;flex-wrap:wrap;align-items:end;margin-bottom:1rem">
    <div>
        <label for="dari" style="margin-top:0">Dari tanggal</label>
        <input type="date" id="dari" name="dari" value="{{ $filter['dari'] ?? '' }}">
    </div>
    <div>
        <label for="sampai" style="margin-top:0">Sampai tanggal</label>
        <input type="date" id="sampai" name="sampai" value="{{ $filter['sampai'] ?? '' }}">
    </div>
    <div>
        <label for="supplier_id" style="margin-top:0">Pemasok</label>
        <select id="supplier_id" name="supplier_id" style="width:220px">
            <option value="">Semua pemasok</option>
            @foreach ($suppliers as $s)
                <option value="{{ $s->id }}" @selected(($filter['supplier_id'] ?? '') === $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="btn" style="margin:0">Terapkan</button>
    <a href="{{ route('pembelian.index') }}" style="padding-bottom:.7rem">Reset</a>
</form>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center">
        <h2 style="margin:0">Pembelian Ayam Hidup</h2>
        @if ($bolehTulis)
            <a href="{{ route('pembelian.create') }}" class="btn" style="margin:0;text-decoration:none">+ Catat Pembelian</a>
        @endif
    </div>

    <table style="margin-top:1rem">
        <thead>
            <tr>
                <th>No. Transaksi</th><th>Tanggal</th><th>Pemasok</th>
                <th class="right">Ekor</th><th class="right">Berat (kg)</th>
                <th class="right">Harga/kg</th><th class="right">Total biaya</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $p)
                <tr>
                    <td><a href="{{ route('pembelian.show', $p) }}">{{ $p->transaction_number }}</a></td>
                    <td>{{ $p->purchase_date->format('d/m/Y') }}</td>
                    <td>{{ $p->supplier?->name ?? '-' }}</td>
                    <td class="right">{{ $ekor($p->total_birds) }}</td>
                    <td class="right">{{ $kg($p->total_weight_kg) }}</td>
                    <td class="right">{{ $rp($p->unit_price_per_kg) }}</td>
                    <td class="right"><strong>{{ $rp($p->total_cost) }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="7">Belum ada pembelian untuk filter ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($items->previousPageUrl() || $items->nextPageUrl())
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:1rem">
            @if ($items->previousPageUrl())
                <a href="{{ $items->previousPageUrl() }}">&larr; Sebelumnya</a>
            @else
                <span></span>
            @endif
            <span style="color:#64748b;font-size:.85rem">Halaman {{ $items->currentPage() }}</span>
            @if ($items->nextPageUrl())
                <a href="{{ $items->nextPageUrl() }}">Berikutnya &rarr;</a>
            @else
                <span></span>
            @endif
        </div>
    @endif
</div>
@endsection