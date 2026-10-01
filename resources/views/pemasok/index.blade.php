@extends('layouts.app')

@section('title', 'Pemasok')

@section('content')
@php $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); @endphp

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center">
        <h2 style="margin:0">Pemasok Ayam Hidup</h2>
        @if ($bolehTulis)
            <a href="{{ route('pemasok.create') }}" class="btn" style="margin:0;text-decoration:none">+ Tambah Pemasok</a>
        @endif
    </div>

    <table style="margin-top:1rem">
        <thead>
            <tr>
                <th>Kode</th><th>Nama</th><th>Telepon</th><th>Alamat</th>
                <th class="right">Jumlah pembelian</th><th class="right">Total biaya</th><th>Status</th><th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($suppliers as $s)
                <tr>
                    <td>{{ $s->code }}</td>
                    <td><strong>{{ $s->name }}</strong></td>
                    <td>{{ $s->phone ?: '-' }}</td>
                    <td>{{ $s->address ?: '-' }}</td>
                    <td class="right">{{ $s->purchase_transactions_count }}</td>
                    <td class="right">{{ $rp($s->total_biaya ?? 0) }}</td>
                    <td>
                        @if ($s->is_active)
                            <span style="color:#0d9488">Aktif</span>
                        @else
                            <span style="color:#94a3b8">Nonaktif</span>
                        @endif
                    </td>
                    <td>
                        @if ($bolehTulis)
                            <a href="{{ route('pemasok.edit', $s) }}">Ubah</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">Belum ada pemasok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection