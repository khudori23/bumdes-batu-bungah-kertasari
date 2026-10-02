@extends('layouts.app')

@section('title', $supplier->name)

@section('content')
@php
    $rp   = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $kg   = fn ($n) => number_format((float) $n, 2, ',', '.');
    $ekor = fn ($n) => number_format((float) $n, 0, ',', '.');
    $rata = (float) $ring->kg > 0 ? (float) $ring->biaya / (float) $ring->kg : 0;
    $telp = preg_replace('/[^0-9+]/', '', (string) $supplier->phone);
@endphp

<div class="card">
    <div class="hd">
        <div>
            <h2 style="margin:0">{{ $supplier->name }}</h2>
            <div class="muted">{{ $supplier->code }} &middot; {{ $supplier->phone ?: 'Telepon belum diisi' }}</div>
            <div class="muted">{{ $supplier->address ?: 'Alamat belum diisi' }}</div>
        </div>
        @if ($supplier->is_active)<span class="chip up">Aktif</span>@else<span class="chip">Nonaktif</span>@endif
    </div>
    <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:.6rem">
        @if ($telp !== '')<a class="btn" style="margin:0" href="tel:{{ $telp }}">Telepon</a>@endif
        <a class="btn" style="margin:0" href="{{ route('pembelian.index', ['supplier_id' => $supplier->id]) }}">Lihat di Pembelian</a>
        @if ($bolehTulis)<a class="btn" style="margin:0" href="{{ route('pemasok.edit', $supplier) }}">Ubah</a>@endif
    </div>
</div>

<div class="stats">
    <div class="card"><div class="stat-l">Transaksi</div><div class="stat-v">{{ $ring->jumlah }}</div>
        <div class="stat-s">{{ $ring->terakhir ? 'Terakhir ' . \Illuminate\Support\Carbon::parse($ring->terakhir)->format('d/m/Y') : 'Belum ada' }}</div></div>
    <div class="card"><div class="stat-l">Total ekor</div><div class="stat-v">{{ $ekor($ring->ekor) }}</div>
        <div class="stat-s">{{ $kg($ring->kg) }} kg</div></div>
    <div class="card"><div class="stat-l">Total biaya</div><div class="stat-v">{{ $rp($ring->biaya) }}</div></div>
    <div class="card"><div class="stat-l">Rata-rata harga/kg</div><div class="stat-v">{{ $rp($rata) }}</div></div>
</div>

<div class="card">
    <h3 class="sec-title">Riwayat pembelian</h3>
    <table class="rtable">
        <thead><tr><th>No. Transaksi</th><th>Tanggal</th><th class="right">Ekor</th><th class="right">Berat (kg)</th><th class="right">Harga/kg</th><th class="right">Total</th></tr></thead>
        <tbody>
            @forelse ($items as $p)
                <tr>
                    <td data-label="No."><a href="{{ route('pembelian.show', $p) }}">{{ $p->transaction_number }}</a></td>
                    <td data-label="Tanggal">{{ $p->purchase_date->format('d/m/Y') }}</td>
                    <td class="right" data-label="Ekor">{{ $ekor($p->total_birds) }}</td>
                    <td class="right" data-label="Berat (kg)">{{ $kg($p->total_weight_kg) }}</td>
                    <td class="right" data-label="Harga/kg">{{ $rp($p->unit_price_per_kg) }}</td>
                    <td class="right" data-label="Total"><strong>{{ $rp($p->total_cost) }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Belum ada pembelian dari pemasok ini.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div style="margin-top:.8rem">{{ $items->links() }}</div>
</div>
@endsection