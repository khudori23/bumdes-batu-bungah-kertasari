@extends('layouts.app')

@section('title', 'Pembelian ' . $purchase->transaction_number)

@section('content')
@php
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $kg = fn ($n) => number_format((float) $n, 2, ',', '.');
    $rata = $purchase->total_birds > 0 ? (float) $purchase->total_weight_kg / $purchase->total_birds : 0;
@endphp

<div class="card" style="max-width:640px">
    <div style="display:flex;justify-content:space-between;align-items:center">
        <h2 style="margin:0">{{ $purchase->transaction_number }}</h2>
        <a href="{{ route('pembelian.index') }}">&larr; Kembali</a>
    </div>

    <table style="margin-top:1rem">
        <tr><th style="width:40%">Tanggal pembelian</th><td>{{ $purchase->purchase_date->format('d/m/Y') }}</td></tr>
        <tr><th>Pemasok</th><td>{{ $purchase->supplier?->name ?? '-' }} ({{ $purchase->supplier?->code ?? '-' }})</td></tr>
        <tr><th>Jumlah ayam</th><td>{{ number_format($purchase->total_birds, 0, ',', '.') }} ekor</td></tr>
        <tr><th>Berat total</th><td>{{ $kg($purchase->total_weight_kg) }} kg</td></tr>
        <tr><th>Rata-rata berat</th><td>{{ $kg($rata) }} kg/ekor</td></tr>
        <tr><th>Harga per kg</th><td>{{ $rp($purchase->unit_price_per_kg) }}</td></tr>
        <tr><th>Total biaya</th><td><strong style="font-size:1.2rem">{{ $rp($purchase->total_cost) }}</strong></td></tr>
        <tr><th>Catatan</th><td>{{ $purchase->notes ?: '-' }}</td></tr>
        <tr><th>Diterima oleh</th><td>{{ $purchase->receivedBy?->name ?? '-' }}</td></tr>
        <tr><th>Dicatat pada</th><td>{{ $purchase->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</td></tr>
    </table>
</div>
@endsection