@extends('layouts.app')

@section('title', 'Riwayat Penjualan')

@section('content')
@php
    $rp = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');

    // Ringkasan hanya menghitung nota yang masih berlaku
    $aktif = $sales->where('is_voided', false);
    $totalAktif = $aktif->sum('total_amount');
    $totalHutang = $aktif->sum(fn ($s) => max(0, $s->total_amount - ($s->dibayar ?? 0)));
    $jumlahBatal = $sales->where('is_voided', true)->count();
@endphp

@if ($sales->isNotEmpty())
    <div class="stats s3">
        <div class="card"><div class="stat-l">Total penjualan</div><div class="stat-v">{{ $rp($totalAktif) }}</div></div>
        <div class="card"><div class="stat-l">Hutang belum lunas</div><div class="stat-v" style="color:{{ $totalHutang > 0.004 ? '#e11d48' : 'inherit' }}">{{ $rp($totalHutang) }}</div></div>
        <div class="card"><div class="stat-l">Nota batal</div><div class="stat-v">{{ $jumlahBatal }}</div></div>
    </div>
@endif

<div class="card">
    <div class="hd">
        <h2 style="margin:0">Riwayat Penjualan <small style="font-weight:400;color:#64748b">(50 terakhir)</small></h2>
        <a class="btn hide-m" style="margin:0;text-decoration:none" href="{{ route('kasir.create') }}">Transaksi baru</a>
    </div>

    <table class="rtable" style="margin-top:1rem">
        <thead>
            <tr><th>No. Nota</th><th>Tanggal</th><th>Pelanggan</th><th>Jenis</th><th class="right">Total</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($sales as $s)
                @php
                    $batal = (bool) $s->is_voided;
                    $sisa = $s->total_amount - ($s->dibayar ?? 0);
                @endphp
                <tr style="{{ $batal ? 'color:#94a3b8' : '' }}">
                    <td data-label="No. Nota" style="{{ $batal ? 'text-decoration:line-through' : '' }}">{{ $s->sale_number }}</td>
                    <td data-label="Tanggal">{{ $s->sale_date->format('d/m/Y') }}</td>
                    <td data-label="Pelanggan">{{ $s->customer_name }}</td>
                    <td data-label="Jenis">{{ $s->customerType?->name }}</td>
                    <td class="right" data-label="Total" style="{{ $batal ? 'text-decoration:line-through' : 'font-weight:700' }}">{{ $rp($s->total_amount) }}</td>
                    <td data-label="Status">
                        @if ($batal)
                            <span class="chip down" @if ($s->void_reason) title="{{ $s->void_reason }}" @endif>BATAL</span>
                        @elseif ($sisa > 0.004)
                            <span class="chip down">Hutang {{ $rp($sisa) }}</span>
                        @else
                            <span class="chip up">Lunas</span>
                        @endif
                    </td>
                    <td><a href="{{ route('kasir.show', $s) }}">Nota &rarr;</a></td>
                </tr>
            @empty
                <tr><td colspan="7">Belum ada transaksi.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<a class="fab" href="{{ route('kasir.create') }}" aria-label="Transaksi baru">+</a>
@endsection