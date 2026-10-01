@extends('layouts.app')

@section('title', 'Pengeluaran')

@section('content')
@php $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); @endphp

@if ($bolehTulis)
<div class="card">
    <h2 style="margin-top:0">Catat Pengeluaran</h2>
    @if ($hanyaSendiri)
        <p class="muted" style="margin-top:0">Tanggal otomatis hari ini. Daftar di bawah hanya memuat catatan Anda sendiri.</p>
    @endif

    @if ($errors->any())
        <div style="background:#ffe4e6;border:1px solid #fecdd3;padding:.6rem .9rem;border-radius:8px;margin-bottom:1rem">
            @foreach ($errors->all() as $e)
                <div class="err" style="margin:0">{{ $e }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('pengeluaran.store') }}">
        @csrf
        <div class="fgrid">
            @unless ($hanyaSendiri)
            <div>
                <label for="expense_date">Tanggal</label>
                <input type="date" id="expense_date" name="expense_date" value="{{ old('expense_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
            </div>
            @endunless
            <div>
                <label for="category">Kategori</label>
                <select id="category" name="category" required>
                    @foreach ($kategori as $k)
                        <option value="{{ $k }}" @selected(old('category') === $k)>{{ $k }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="amount">Jumlah (Rp)</label>
                <input type="number" id="amount" name="amount" value="{{ old('amount') }}" min="1" step="1" required>
            </div>
        </div>

        <label for="description">Keterangan (opsional)</label>
        <input type="text" id="description" name="description" value="{{ old('description') }}" maxlength="255">

        <button type="submit" class="btn">Simpan</button>
    </form>
</div>
@endif

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.6rem">
        <h2 style="margin:0">Pengeluaran</h2>
        <form method="GET" action="{{ route('pengeluaran.index') }}" style="display:flex;gap:.5rem;align-items:center">
            <input type="month" name="bulan" value="{{ $bulan }}" style="padding:.45rem;border:1px solid #cbd5e1;border-radius:8px">
            <button type="submit" class="btn" style="margin:0;padding:.5rem 1rem">Tampilkan</button>
        </form>
    </div>

    <p style="font-size:1.4rem;margin:1rem 0 .4rem"><strong>{{ $rp($total) }}</strong>
        <span class="muted">total {{ $labelBulan }}</span></p>

    @if ($perKategori->isNotEmpty())
        <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:1rem">
            @foreach ($perKategori as $nama => $jumlah)
                <span class="chip">{{ $nama }}: {{ $rp($jumlah) }}</span>
            @endforeach
        </div>
    @endif

    <table>
        <thead>
            <tr><th>Tanggal</th><th>Kategori</th><th>Keterangan</th><th class="right">Jumlah</th><th>Dicatat oleh</th></tr>
        </thead>
        <tbody>
            @forelse ($items as $x)
                <tr>
                    <td>{{ $x->expense_date->format('d/m/Y') }}</td>
                    <td>{{ $x->category }}</td>
                    <td>{{ $x->description ?: '-' }}</td>
                    <td class="right">{{ $rp($x->amount) }}</td>
                    <td>{{ $x->recordedBy?->name ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Belum ada pengeluaran di bulan ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection