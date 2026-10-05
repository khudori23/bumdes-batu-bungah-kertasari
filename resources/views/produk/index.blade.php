@extends('layouts.app')

@section('title', 'Produk & Harga')

@section('content')
@include('produk._galat')
@php
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $bisaUbah = auth()->user()->isAdmin() || auth()->user()->roleName() === 'KASIR';
@endphp

<div class="card">
    <h2 style="margin-top:0">Produk &amp; Harga</h2>
    <p class="muted">Harga yang berlaku saat ini per jenis pelanggan. Nota yang sudah dibuat tidak ikut berubah kalau harga diganti.</p>
    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr>
                <th>Kode</th><th>Nama</th><th>Satuan</th>
                @foreach ($tipe as $t)<th class="right">{{ $t->name }}</th>@endforeach
                <th>Status</th>@if ($bisaUbah)<th></th>@endif
            </tr>
        </thead>
        <tbody>
            @forelse ($produk as $p)
                <tr>
                    <td>{{ $p->code }}</td>
                    <td><strong>{{ $p->name }}</strong> @if ($p->is_live_bird)<span class="chip warn">Ayam hidup</span>@endif</td>
                    <td>{{ $p->primary_unit }}</td>
                    @foreach ($tipe as $t)
                        @php $h = $harga->get($p->id)?->get($t->id); @endphp
                        <td class="right">{{ $h ? $rp($h->price) : '-' }}</td>
                    @endforeach
                    <td><span class="chip {{ $p->is_active ? 'up' : '' }}">{{ $p->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    @if ($bisaUbah)<td><a href="{{ route('produk.edit', $p->id) }}">Ubah</a></td>@endif
                </tr>
            @empty
                <tr><td colspan="{{ 4 + $tipe->count() + ($bisaUbah ? 1 : 0) }}">Belum ada produk.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

@if ($bisaUbah)
<div class="card">
    <h3 style="margin-top:0">Tambah produk</h3>
    <form method="POST" action="{{ route('produk.store') }}">
        @csrf
        <div class="fgrid">
            <div><label for="code">Kode (huruf besar, angka, minus)</label>
                <input type="text" id="code" name="code" maxlength="30" required value="{{ old('code') }}"></div>
            <div><label for="name">Nama</label>
                <input type="text" id="name" name="name" maxlength="100" required value="{{ old('name') }}"></div>
            <div><label for="description">Keterangan (opsional)</label>
                <input type="text" id="description" name="description" maxlength="255" value="{{ old('description') }}"></div>
        </div>
        <button type="submit" class="btn">Tambah produk</button>
    </form>
</div>
@endif
@endsection