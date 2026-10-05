@extends('layouts.app')

@section('title', 'Freezer & Batas Simpan')

@section('content')
@include('layouts._errors')
@php
    $fm = fn ($v, $d = 2) => number_format((float) $v, $d, ',', '.');
    $stLabel = ['lewat' => 'Lewat batas', 'dekat' => 'Hampir batas', 'aman' => 'Aman'];
    $stChip = ['lewat' => 'down', 'dekat' => 'warn', 'aman' => 'up'];
@endphp

<div class="card">
    <h2 style="margin-top:0">Freezer</h2>
    <p class="muted">
        Freezer dipilih saat input produksi. Umur simpan dihitung dari tanggal produksi.
        Freezer yang masih berisi stok tidak bisa dinonaktifkan.
    </p>
    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr><th>Freezer</th><th class="right">Isi sekarang (kg)</th><th>Status</th></tr>
        </thead>
        <tbody>
            @forelse ($freezer as $f)
                <tr>
                    <td>
                        @if ($bisaUbah)
                            <form method="POST" action="{{ route('freezer.update', $f->id) }}" style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
                                @csrf
                                @method('PUT')
                                <input type="text" name="name" value="{{ $f->name }}" maxlength="50" required style="flex:1 1 160px" aria-label="Nama freezer">
                                <label style="display:flex;align-items:center;gap:.3rem;margin:0">
                                    <input type="checkbox" name="is_active" value="1" style="width:auto" @checked($f->is_active)> Aktif
                                </label>
                                <button class="btn" type="submit">Simpan</button>
                            </form>
                        @else
                            <strong>{{ $f->name }}</strong>
                        @endif
                    </td>
                    <td class="right">{{ $fm($isi->get($f->id, 0)) }}</td>
                    <td><span class="chip {{ $f->is_active ? 'up' : '' }}">{{ $f->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                </tr>
            @empty
                <tr><td colspan="3" class="muted">Belum ada freezer.</td></tr>
            @endforelse
            @if ($isi->has('') && $isi->get('') > 0)
                <tr>
                    <td><em>Belum diatur (stok lama)</em></td>
                    <td class="right">{{ $fm($isi->get('')) }}</td>
                    <td></td>
                </tr>
            @endif
        </tbody>
    </table>
    </div>
</div>

@if ($bisaUbah)
<div class="card">
    <h3 style="margin-top:0">Tambah freezer</h3>
    <form method="POST" action="{{ route('freezer.store') }}">
        @csrf
        <div class="fgrid">
            <div>
                <label for="name">Nama freezer</label>
                <input type="text" id="name" name="name" maxlength="50" required value="{{ old('name') }}" placeholder="Contoh: Freezer 1">
            </div>
        </div>
        <button type="submit" class="btn">Tambah freezer</button>
    </form>
</div>
@endif

<div class="card">
    <h3 style="margin-top:0">Batas simpan per produk</h3>
    <p class="muted">
        Berapa hari hasil produksi boleh disimpan, dihitung dari tanggal produksi. Bawaannya 3 hari.
        Umur terlama dihitung dengan urutan FIFO: stok yang tersisa dianggap berasal dari produksi terbaru.
    </p>

    @if ($bisaUbah)
        <form method="POST" action="{{ route('freezer.batas') }}">
            @csrf
            @method('PUT')
    @endif
    <table class="rtable">
        <thead>
            <tr><th>Produk</th><th class="right">Stok (kg)</th><th class="right">Umur terlama</th><th>Status</th><th class="right">Batas (hari)</th></tr>
        </thead>
        <tbody>
            @forelse ($produk as $p)
                <tr>
                    <td data-label="Produk"><strong>{{ $p->name }}</strong></td>
                    <td class="right" data-label="Stok (kg)">{{ $fm($p->stok) }}</td>
                    <td class="right" data-label="Umur terlama">{{ $p->umur !== null ? $p->umur . ' hari' : '-' }}</td>
                    <td data-label="Status">
                        @if (isset($stLabel[$p->status]))
                            <span class="chip {{ $stChip[$p->status] }}">{{ $stLabel[$p->status] }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td class="right" data-label="Batas (hari)">
                        @if ($bisaUbah)
                            <input type="number" inputmode="numeric" step="1" min="1" max="365" name="batas[{{ $p->id }}]" value="{{ old('batas.' . $p->id, $p->batas) }}" required style="max-width:110px" aria-label="Batas simpan {{ $p->name }}">
                        @else
                            {{ $p->batas }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Belum ada produk aktif.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if ($bisaUbah)
            <button type="submit" class="btn" style="margin-top:.8rem">Simpan batas simpan</button>
        </form>
    @endif
</div>
@endsection