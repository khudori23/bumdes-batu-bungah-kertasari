@extends('layouts.app')

@section('title', 'Ayam Keluar')

@section('content')
@include('layouts._errors')
@php
    $ekor = fn ($n) => number_format((float) $n, 0, ',', '.');
    $kg   = fn ($n) => number_format((float) $n, 2, ',', '.');
@endphp

<div class="card">
    <h2 style="margin-top:0">Ayam masuk produksi</h2>
    <p class="muted">
        Stok ayam hidup saat ini: <strong>{{ $ekor($stok['ekor']) }} ekor</strong> &middot; {{ number_format($stok['kg'], 1, ',', '.') }} kg.
        Catat ayam yang diserahkan ke pemotongan. Kalau diisi lebih dari sekali pada tanggal yang sama, jumlahnya digabung.
        Riwayatnya ada di menu <a href="{{ route('riwayatayam.index') }}">Riwayat Ayam</a>.
    </p>

    @if ($bolehTulis)
        <form method="POST" action="{{ route('ayamkeluar.store') }}">
            @csrf
            <div class="fgrid">
                <div>
                    <label>Tanggal produksi</label>
                    <input type="date" name="production_date" value="{{ old('production_date', now()->toDateString()) }}" min="{{ \App\Support\Pencatatan::mulai() }}" max="{{ now()->toDateString() }}" required>
                </div>
                <div>
                    <label>Ayam masuk produksi (ekor)</label>
                    <input type="number" inputmode="numeric" name="input_birds" min="1" step="1" value="{{ old('input_birds') }}" required>
                </div>
                <div>
                    <label>Berat ayam hidup (kg)</label>
                    <input type="number" inputmode="decimal" name="input_weight_kg" min="0.01" step="0.01" value="{{ old('input_weight_kg') }}" required>
                </div>
            </div>
            <label>Catatan (opsional)</label>
            <input type="text" name="notes" maxlength="255" value="{{ old('notes') }}">
            <button type="submit" class="btn" style="width:100%">Simpan ayam masuk produksi</button>
        </form>
    @else
        <p class="muted">Anda hanya dapat melihat data ini.</p>
    @endif
</div>

@include('ayam-keluar._jual-ecer')
@endsection