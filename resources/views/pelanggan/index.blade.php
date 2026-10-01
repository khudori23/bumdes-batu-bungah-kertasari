@extends('layouts.app')

@section('title', 'Pelanggan')

@section('content')
@include('produk._galat')

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.6rem">
        <h2 style="margin:0">Pelanggan</h2>
        <form method="GET" action="{{ route('pelanggan.index') }}" style="display:flex;gap:.5rem">
            <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama atau telepon" style="padding:.45rem;border:1px solid #cbd5e1;border-radius:8px">
            <button type="submit" class="btn" style="margin:0;padding:.5rem 1rem">Cari</button>
        </form>
    </div>

    <div style="overflow-x:auto">
    <table style="margin-top:1rem">
        <thead><tr><th>Nama</th><th>Telepon</th><th>Jenis</th><th class="right">Nota</th><th>Status</th>@if ($admin)<th></th>@endif</tr></thead>
        <tbody>
            @forelse ($list as $c)
                <tr>
                    <td><strong>{{ $c->name }}</strong>@if ($c->address)<div class="muted">{{ $c->address }}</div>@endif</td>
                    <td>{{ $c->phone ?: '-' }}</td>
                    <td>{{ $c->customerType?->name ?? '-' }}</td>
                    <td class="right">{{ $c->sales_count }}</td>
                    <td><span class="chip {{ $c->is_active ? 'up' : '' }}">{{ $c->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    @if ($admin)<td><a href="{{ route('pelanggan.edit', $c->id) }}">Ubah</a></td>@endif
                </tr>
            @empty
                <tr><td colspan="{{ $admin ? 6 : 5 }}">Belum ada pelanggan.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>

    @if ($list->lastPage() > 1)
        <div style="display:flex;gap:1rem;margin-top:1rem;align-items:center">
            @if ($list->previousPageUrl())<a href="{{ $list->previousPageUrl() }}">&larr; Sebelumnya</a>@endif
            <span class="muted">Halaman {{ $list->currentPage() }} dari {{ $list->lastPage() }}</span>
            @if ($list->nextPageUrl())<a href="{{ $list->nextPageUrl() }}">Berikutnya &rarr;</a>@endif
        </div>
    @endif
</div>

@if ($bisaTambah)
<div class="card">
    <h3 style="margin-top:0">Tambah pelanggan</h3>
    <form method="POST" action="{{ route('pelanggan.store') }}">
        @csrf
        <div class="fgrid">
            <div><label for="name">Nama</label>
                <input type="text" id="name" name="name" maxlength="100" required value="{{ old('name') }}"></div>
            <div><label for="phone">Telepon (opsional)</label>
                <input type="text" id="phone" name="phone" maxlength="30" value="{{ old('phone') }}"></div>
            <div><label for="address">Alamat (opsional)</label>
                <input type="text" id="address" name="address" maxlength="255" value="{{ old('address') }}"></div>
            <div><label for="customer_type_id">Jenis pelanggan</label>
                <select id="customer_type_id" name="customer_type_id" required>
                    <option value="">Pilih jenis</option>
                    @foreach ($tipe as $t)
                        <option value="{{ $t->id }}" @selected(old('customer_type_id') === $t->id)>{{ $t->name }}</option>
                    @endforeach
                </select></div>
        </div>
        <button type="submit" class="btn">Tambah pelanggan</button>
    </form>
</div>
@endif
@endsection