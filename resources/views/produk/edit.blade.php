@extends('layouts.app')

@section('title', 'Ubah Produk')

@section('content')
@include('produk._galat')
@php
    $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
@endphp

<div class="card" style="max-width:780px">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.6rem">
        <h2 style="margin:0">{{ $produk->name }} <span class="muted">({{ $produk->code }})</span></h2>
        <a href="{{ route('produk.index') }}" data-kembali>&larr; Kembali</a>
    </div>

    <form method="POST" action="{{ route('produk.update', $produk->id) }}" style="margin-top:1rem">
        @csrf @method('PUT')
        <div class="fgrid">
            <div><label for="name">Nama</label>
                <input type="text" id="name" name="name" maxlength="100" required value="{{ old('name', $produk->name) }}"></div>
            <div><label for="description">Keterangan</label>
                <input type="text" id="description" name="description" maxlength="255" value="{{ old('description', $produk->description) }}"></div>
        </div>
        <label style="display:flex;gap:.5rem;align-items:center">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $produk->is_active))> Produk aktif (tampil di kasir)
        </label>
        <p class="muted">Kode dan satuan tidak bisa diubah karena sudah dipakai di stok dan nota.</p>
        <button type="submit" class="btn" style="margin-top:.4rem">Simpan data produk</button>
    </form>
</div>

<div class="card" style="max-width:780px">
    <h3 style="margin-top:0">Harga per jenis pelanggan</h3>
    <p class="muted">Kosongkan kolom untuk menghapus harga jenis itu. Harga lama tidak hilang, tersimpan di riwayat.</p>
    <form method="POST" action="{{ route('produk.harga', $produk->id) }}">
        @csrf @method('PUT')
        <div class="fgrid">
            @foreach ($tipe as $t)
                <div>
                    <label for="h{{ $loop->index }}">{{ $t->name }} (Rp)</label>
                    <input type="number" id="h{{ $loop->index }}" name="harga[{{ $t->id }}]" min="0" max="100000000" step="any"
                           value="{{ old('harga.' . $t->id, $aktif->has($t->id) ? $num($aktif[$t->id]->price) : '') }}">
                </div>
            @endforeach
        </div>
        <button type="submit" class="btn">Simpan harga</button>
    </form>
</div>

<div class="card" style="max-width:780px">
    <h3 style="margin-top:0">Riwayat harga</h3>
    <div style="overflow-x:auto">
    <table>
        <thead><tr><th>Berlaku sejak</th><th>Jenis pelanggan</th><th class="right">Harga</th><th>Status</th></tr></thead>
        <tbody>
            @forelse ($riwayat as $r)
                <tr>
                    <td>{{ \Illuminate\Support\Carbon::parse($r->effective_date)->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                    <td>{{ $namaTipe[$r->customer_type_id] ?? '-' }}</td>
                    <td class="right">{{ $rp($r->price) }}</td>
                    <td><span class="chip {{ $r->is_active ? 'up' : '' }}">{{ $r->is_active ? 'Berlaku' : 'Lama' }}</span></td>
                </tr>
            @empty
                <tr><td colspan="4">Belum ada harga.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection