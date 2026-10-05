@extends('layouts.app')

@section('title', 'Pesanan Dapur')

@section('content')
@php
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $kg = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
@endphp

<div class="card">
    <div class="hd">
        <h2 style="margin:0">Pesanan Dapur <small style="font-weight:400;color:#64748b">(50 terakhir)</small></h2>
        @if (auth()->user()->canWrite('kasir'))
            <a class="btn hide-m" style="margin:0;text-decoration:none" href="{{ route('kasir.dapur.create') }}">Pesanan baru</a>
        @endif
    </div>

    <table class="rtable" style="margin-top:1rem">
        <thead>
            <tr><th>No. Pesanan</th><th>Tanggal</th><th>Ditagih ke</th><th class="right">Baris</th><th class="right">Ayam hidup (kg)</th><th class="right">Total tagihan</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($orders as $o)
                @php $batal = $o->status === 'VOIDED'; @endphp
                <tr style="{{ $batal ? 'color:#94a3b8' : '' }}">
                    <td data-label="No. Pesanan" style="{{ $batal ? 'text-decoration:line-through' : '' }}">{{ $o->order_number }}</td>
                    <td data-label="Tanggal">{{ $o->order_date->format('d/m/Y') }}</td>
                    <td data-label="Ditagih ke">{{ $o->customer?->name }}</td>
                    <td class="right" data-label="Baris">{{ $o->lines_count }}</td>
                    <td class="right" data-label="Ayam hidup (kg)">{{ $kg($o->live_weight_kg) }}</td>
                    <td class="right" data-label="Total tagihan" style="{{ $batal ? 'text-decoration:line-through' : 'font-weight:700' }}">{{ $rp($o->total_amount) }}</td>
                    <td data-label="Status">
                        @if ($batal)
                            <span class="chip down">BATAL</span>
                        @else
                            <span class="chip up">Tercatat</span>
                        @endif
                    </td>
                    <td><a href="{{ route('kasir.dapur.show', $o) }}">Rincian &rarr;</a></td>
                </tr>
            @empty
                <tr><td colspan="8">Belum ada pesanan dapur.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if (auth()->user()->canWrite('kasir'))
    <a class="fab" href="{{ route('kasir.dapur.create') }}" aria-label="Pesanan dapur baru">+</a>
@endif
@endsection