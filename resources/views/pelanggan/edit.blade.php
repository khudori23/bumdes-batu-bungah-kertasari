@extends('layouts.app')

@section('title', 'Ubah Pelanggan')

@section('content')
@include('produk._galat')

<div class="card" style="max-width:640px">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.6rem">
        <h2 style="margin:0">Ubah pelanggan</h2>
        <a href="{{ route('pelanggan.index') }}">&larr; Kembali</a>
    </div>
    <p class="muted">Pelanggan ini punya {{ $c->sales_count }} nota. Pelanggan yang sudah punya nota tidak dihapus, cukup dinonaktifkan.</p>

    <form method="POST" action="{{ route('pelanggan.update', $c->id) }}">
        @csrf @method('PUT')
        <div class="fgrid">
            <div><label for="name">Nama</label>
                <input type="text" id="name" name="name" maxlength="100" required value="{{ old('name', $c->name) }}"></div>
            <div><label for="phone">Telepon</label>
                <input type="text" id="phone" name="phone" maxlength="30" value="{{ old('phone', $c->phone) }}"></div>
            <div><label for="address">Alamat</label>
                <input type="text" id="address" name="address" maxlength="255" value="{{ old('address', $c->address) }}"></div>
            <div><label for="customer_type_id">Jenis pelanggan</label>
                <select id="customer_type_id" name="customer_type_id" required>
                    @foreach ($tipe as $t)
                        <option value="{{ $t->id }}" @selected(old('customer_type_id', $c->customer_type_id) === $t->id)>{{ $t->name }}{{ $t->is_active ? '' : ' (nonaktif)' }}</option>
                    @endforeach
                </select></div>
        </div>
        <label style="display:flex;gap:.5rem;align-items:center">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $c->is_active))> Pelanggan aktif
        </label>
        <button type="submit" class="btn">Simpan</button>
    </form>
</div>
@endsection