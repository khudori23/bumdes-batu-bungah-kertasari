@extends('layouts.app')

@section('title', 'Pengaturan Bonus')

@section('content')
@include('gaji._nav')
@php $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.') ?: '0'; @endphp

<div class="card" style="max-width:640px">
    <h2 style="margin-top:0">Pengaturan Bonus Rendemen</h2>
    <p class="muted" style="line-height:1.5">
        Bonus hanya untuk relawan. Besarnya = tarif per kg &times; kg potong pada hari yang rendemennya
        mencapai ambang. Rendemen harian = total kg hasil timbang &divide; total berat ayam masuk pada tanggal produksi itu.
    </p>

    <form method="POST" action="{{ route('gaji.bonus.simpan') }}">
        @csrf @method('PUT')
        <div class="fgrid">
            <div>
                <label for="bonus_rendemen_per_kg">Bonus per kg hasil potong (Rp)</label>
                <input type="number" id="bonus_rendemen_per_kg" name="bonus_rendemen_per_kg" min="0" max="1000000" step="any" required
                       value="{{ old('bonus_rendemen_per_kg', $num($perKg)) }}">
                <div class="muted">Isi 0 untuk menonaktifkan bonus.</div>
            </div>
            <div>
                <label for="bonus_rendemen_min">Ambang rendemen minimal (%)</label>
                <input type="number" id="bonus_rendemen_min" name="bonus_rendemen_min" min="0" max="100" step="any" required
                       value="{{ old('bonus_rendemen_min', $num($ambang)) }}">
                <div class="muted">0 = bonus berlaku untuk semua hari. Contoh: 70 = hanya hari dengan rendemen &ge; 70%.</div>
            </div>
        </div>
        <button type="submit" class="btn">Simpan</button>
    </form>

    <p class="muted" style="margin-top:1.2rem;line-height:1.5">
        Slip yang sudah diterbitkan tidak berubah. Draft yang sudah ada tetap memakai nilai lama sampai Anda
        membuka draft itu dan menekan <strong>Hitung ulang dari presensi</strong>.
    </p>
</div>
@endsection