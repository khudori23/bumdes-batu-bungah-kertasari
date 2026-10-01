@extends('layouts.app')

@section('title', 'Piutang')

@section('content')
@php $rp = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.'); @endphp

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1rem">
    <div class="card">
        <div style="font-size:.8rem;color:#64748b">TOTAL HUTANG</div>
        <div style="font-size:1.5rem;font-weight:700">{{ $rp($totalHutang) }}</div>
    </div>
    <div class="card" style="{{ $totalTerlambat > 0 ? 'border-color:#fecdd3;background:#fff1f2' : '' }}">
        <div style="font-size:.8rem;color:#64748b">TERLAMBAT (LEWAT TEMPO)</div>
        <div style="font-size:1.5rem;font-weight:700;color:{{ $totalTerlambat > 0 ? '#e11d48' : '#0d9488' }}">{{ $rp($totalTerlambat) }}</div>
    </div>
    <div class="card">
        <div style="font-size:.8rem;color:#64748b">PELANGGAN TERLAMBAT</div>
        <div style="font-size:1.5rem;font-weight:700">{{ $pelangganTerlambat }}</div>
    </div>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center">
        <h2 style="margin:0">Hutang Pelanggan</h2>
        <div style="display:flex;gap:.5rem">
            <a href="{{ route('piutang.index') }}" style="padding:.35rem .9rem;border-radius:999px;border:1px solid #cbd5e1;text-decoration:none;color:{{ $filter === 'semua' ? '#fff' : '#0f172a' }};background:{{ $filter === 'semua' ? '#0f172a' : '#fff' }}">Semua</a>
            <a href="{{ route('piutang.index', ['filter' => 'terlambat']) }}" style="padding:.35rem .9rem;border-radius:999px;border:1px solid #cbd5e1;text-decoration:none;color:{{ $filter === 'terlambat' ? '#fff' : '#0f172a' }};background:{{ $filter === 'terlambat' ? '#e11d48' : '#fff' }}">Terlambat</a>
        </div>
    </div>

    <table style="margin-top:1rem">
        <thead>
            <tr><th>Pelanggan</th><th>Telepon</th><th class="right">Sisa hutang</th><th>Jatuh tempo tertua</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($daftar as $d)
                <tr>
                    <td>{{ $d->customer->name }}</td>
                    <td>{{ $d->customer->phone ?: '-' }}</td>
                    <td class="right"><strong>{{ $rp($d->saldo) }}</strong></td>
                    <td>{{ \Illuminate\Support\Carbon::parse($d->jatuh_tempo)->format('d/m/Y') }}</td>
                    <td>
                        @if ($d->terlambat_hari > 0)
                            <span style="color:#e11d48;font-weight:600">Terlambat {{ $d->terlambat_hari }} hari</span>
                            <div style="font-size:.78rem;color:#64748b">{{ $rp($d->terlambat_jumlah) }} lewat tempo</div>
                        @else
                            <span style="color:#0d9488">Belum jatuh tempo</span>
                        @endif
                    </td>
                    <td><a href="{{ route('piutang.show', $d->customer) }}">Detail / Bayar</a></td>
                </tr>
            @empty
                <tr><td colspan="6">{{ $filter === 'terlambat' ? 'Tidak ada hutang yang terlambat.' : 'Tidak ada hutang.' }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection