@extends('layouts.app')

@section('title', 'Bonus & Pembagian')

@section('content')
@include('gaji._nav')
<div class="card" style="max-width:640px">
    <h2 style="margin:0 0 .3rem">Pembagian per kg &amp; bonus</h2>
    <div class="muted" style="margin-bottom:1rem">
        Setiap hari, berat hasil produksi &times; tarif per kg dibagi ke semua yang hadir menurut poin.
        Poin = bobot status (Hadir 1, Setengah hari 0,5) &times; bobot tipe pegawai.
    </div>

    <form method="POST" action="{{ route('gaji.bonus.simpan') }}">
        @csrf
        @method('PUT')

        <label style="display:block;margin-top:.6rem"><strong>Tarif per kg hasil produksi (Rp)</strong></label>
        <input type="number" name="pool_tarif_per_kg" min="0" step="1" value="{{ old('pool_tarif_per_kg', $s['pool_tarif_per_kg']) }}" required style="padding:.5rem;border:1px solid #cbd5e1;border-radius:8px;width:100%">

        <label style="display:block;margin-top:.8rem"><strong>Bobot karyawan</strong></label>
        <input type="number" name="pool_bobot_karyawan" min="0" max="10" step="0.01" value="{{ old('pool_bobot_karyawan', $s['pool_bobot_karyawan']) }}" required style="padding:.5rem;border:1px solid #cbd5e1;border-radius:8px;width:100%">

        <label style="display:block;margin-top:.8rem"><strong>Bobot relawan</strong></label>
        <input type="number" name="pool_bobot_relawan" min="0" max="10" step="0.01" value="{{ old('pool_bobot_relawan', $s['pool_bobot_relawan']) }}" required style="padding:.5rem;border:1px solid #cbd5e1;border-radius:8px;width:100%">
        <div class="muted" style="margin-top:.2rem">Isi sama (1 dan 1) bila karyawan dan relawan mendapat porsi sama.</div>

        <label style="display:block;margin-top:.8rem"><strong>Bonus kuota per bulan (Rp)</strong></label>
        <input type="number" name="bonus_kuota_bulanan" min="0" step="1" value="{{ old('bonus_kuota_bulanan', $s['bonus_kuota_bulanan']) }}" required style="padding:.5rem;border:1px solid #cbd5e1;border-radius:8px;width:100%">
        <div class="muted" style="margin-top:.2rem">Terisi otomatis di slip saat draft dibuat, untuk semua karyawan dan relawan. Bonus penjualan bisa ditambahkan manual di slip.</div>

        <label style="display:block;margin-top:.8rem"><strong>Jam kerja minimal untuk dihitung Hadir (jam)</strong></label>
        <input type="number" name="absen_min_jam_hadir" min="0" max="24" step="0.25" value="{{ old('absen_min_jam_hadir', $s['absen_min_jam_hadir']) }}" required style="padding:.5rem;border:1px solid #cbd5e1;border-radius:8px;width:100%">
        <div class="muted" style="margin-top:.2rem">Absen pulang dengan durasi kurang dari ini tercatat Setengah hari.</div>

        <button type="submit" class="btn" style="margin-top:1rem">Simpan</button>
    </form>
</div>
@endsection