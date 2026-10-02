@extends('layouts.app')

@section('title', 'Piutang')

@section('content')
@php $rp = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.'); @endphp

<div class="stats s3">
    <div class="card">
        <div class="stat-l">Total hutang</div>
        <div class="stat-v">{{ $rp($totalHutang) }}</div>
    </div>
    <div class="card" style="{{ $totalTerlambat > 0 ? 'border-color:#fecdd3;background:#fff1f2' : '' }}">
        <div class="stat-l">Terlambat (lewat tempo)</div>
        <div class="stat-v" style="color:{{ $totalTerlambat > 0 ? '#e11d48' : '#0d9488' }}">{{ $rp($totalTerlambat) }}</div>
    </div>
    <div class="card">
        <div class="stat-l">Pelanggan terlambat</div>
        <div class="stat-v">{{ $pelangganTerlambat }}</div>
    </div>
</div>

<div class="fchips">
    <a href="{{ route('piutang.index') }}" class="{{ $filter === 'semua' ? 'on' : '' }}">Semua</a>
    <a href="{{ route('piutang.index', ['filter' => 'terlambat']) }}" class="{{ $filter === 'terlambat' ? 'on' : '' }}">Terlambat</a>
</div>

<div class="card">
    <h2 style="margin:0">Hutang Pelanggan</h2>
    <table class="rtable" style="margin-top:1rem">
        <thead>
            <tr><th>Pelanggan</th><th>Telepon</th><th class="right">Sisa hutang</th><th>Jatuh tempo tertua</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($daftar as $d)
                <tr>
                    <td data-label="Pelanggan"><strong>{{ $d->customer->name }}</strong></td>
                    <td data-label="Telepon">{{ $d->customer->phone ?: '-' }}</td>
                    <td class="right" data-label="Sisa hutang"><strong>{{ $rp($d->saldo) }}</strong></td>
                    <td data-label="Jatuh tempo">{{ \Illuminate\Support\Carbon::parse($d->jatuh_tempo)->format('d/m/Y') }}</td>
                    <td data-label="Status">
                        @if ($d->terlambat_hari > 0)
                            <span class="chip down">Terlambat {{ $d->terlambat_hari }} hari</span>
                        @else
                            <span class="chip up">Belum jatuh tempo</span>
                        @endif
                    </td>
                    <td><a href="{{ route('piutang.show', $d->customer) }}">Detail / Bayar &rarr;</a></td>
                </tr>
            @empty
                <tr><td colspan="6">{{ $filter === 'terlambat' ? 'Tidak ada hutang yang terlambat.' : 'Tidak ada hutang.' }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection