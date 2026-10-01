@extends('layouts.app')

@section('title', $supplier->exists ? 'Ubah Pemasok' : 'Tambah Pemasok')

@section('content')
<div class="card" style="max-width:560px">
    <h2 style="margin-top:0">{{ $supplier->exists ? 'Ubah Pemasok ' . $supplier->code : 'Tambah Pemasok' }}</h2>

    @if ($errors->any())
        <div style="background:#ffe4e6;border:1px solid #fecdd3;padding:.6rem .9rem;border-radius:8px;margin-bottom:1rem">
            @foreach ($errors->all() as $e)
                <div class="err" style="margin:0">{{ $e }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ $supplier->exists ? route('pemasok.update', $supplier) : route('pemasok.store') }}">
        @csrf
        @if ($supplier->exists)
            @method('PUT')
        @endif

        <label for="name">Nama pemasok</label>
        <input type="text" id="name" name="name" value="{{ old('name', $supplier->name) }}" maxlength="100" required autofocus>

        <label for="phone">Telepon (opsional)</label>
        <input type="text" id="phone" name="phone" value="{{ old('phone', $supplier->phone) }}" maxlength="30">

        <label for="address">Alamat (opsional)</label>
        <input type="text" id="address" name="address" value="{{ old('address', $supplier->address) }}" maxlength="255">

        @if ($supplier->exists)
            <label style="display:flex;gap:.5rem;align-items:center;margin-top:1rem">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $supplier->is_active))>
                Pemasok aktif (yang nonaktif tidak muncul di form pembelian)
            </label>
        @endif

        <button type="submit" class="btn">Simpan</button>
        <a href="{{ route('pemasok.index') }}" style="margin-left:.8rem">Batal</a>
    </form>
</div>
@endsection