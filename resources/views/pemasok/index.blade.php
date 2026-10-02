@extends('layouts.app')

@section('title', 'Pemasok')

@section('content')
@php $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); @endphp

<div class="card">
    <div class="hd">
        <h2 style="margin:0">Pemasok Ayam Hidup</h2>
        @if ($bolehTulis)
            <a href="{{ route('pemasok.create') }}" class="btn hide-m" style="margin:0;text-decoration:none">+ Tambah Pemasok</a>
        @endif
    </div>

    <table class="rtable" style="margin-top:1rem">
        <thead>
            <tr>
                <th>Kode</th><th>Nama</th><th>Telepon</th><th>Alamat</th>
                <th class="right">Jumlah pembelian</th><th class="right">Total biaya</th><th>Status</th><th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($suppliers as $s)
                <tr>
                    <td data-label="Kode">{{ $s->code }}</td>
                    <td data-label="Nama"><a href="{{ route('pemasok.show', $s) }}"><strong>{{ $s->name }}</strong></a></td>
                    <td data-label="Telepon">{{ $s->phone ?: '-' }}</td>
                    <td data-label="Alamat">{{ $s->address ?: '-' }}</td>
                    <td class="right" data-label="Pembelian">{{ $s->purchase_transactions_count }}</td>
                    <td class="right" data-label="Total biaya">{{ $rp($s->total_biaya ?? 0) }}</td>
                    <td data-label="Status">
                        @if ($s->is_active)<span class="chip up">Aktif</span>@else<span class="chip">Nonaktif</span>@endif
                    </td>
                    <td>
                        <a href="{{ route('pemasok.show', $s) }}">Detail</a>
                        @if ($bolehTulis) &middot; <a href="{{ route('pemasok.edit', $s) }}">Ubah</a>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">Belum ada pemasok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if ($bolehTulis)<a class="fab" href="{{ route('pemasok.create') }}" aria-label="Tambah Pemasok">+</a>@endif
@endsection