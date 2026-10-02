@extends('layouts.app')

@section('title', 'Pengaturan')

@section('content')
@php $s = \App\Models\Setting::semua(); @endphp
<style>
    .pg textarea { width:100%; padding:.6rem .7rem; border:1px solid #cbd5e1; border-radius:8px; font:inherit; resize:vertical; }
    .pg input[type=file] { width:100%; padding:.4rem 0; }
    .pg input[type=color] { width:56px; height:38px; padding:2px; border:1px solid #cbd5e1; border-radius:8px; background:#fff; }
    .pg h3 { margin:0 0 .3rem; font-size:1.05rem; }
    .pg .prev { display:flex; align-items:center; gap:.8rem; margin:.4rem 0; }
    .pg .prev img { max-height:56px; max-width:160px; border:1px solid var(--line); border-radius:8px; padding:4px; background:#fff; }
    .pg label.cek { display:flex; gap:.5rem; align-items:center; margin-top:.6rem; font-size:.88rem; }
/* hp-2c */ @media (max-width: 600px) { .pg .prev img { max-width: 100%; } .pg input[type=color] { width: 72px; height: 44px; } .pg > .btn { width: 100%; min-height: 44px; } .pg label.cek { min-height: 40px; } }
</style>

<form method="POST" action="{{ route('pengaturan.update') }}" enctype="multipart/form-data" class="pg" style="max-width:680px">
    @csrf
    @method('PUT')

    @if ($errors->any())
        <div style="background:#ffe4e6;border:1px solid #fecdd3;padding:.6rem .9rem;border-radius:8px;margin-bottom:1rem">
            @foreach ($errors->all() as $e)
                <div class="err" style="margin:0">{{ $e }}</div>
            @endforeach
        </div>
    @endif

    <div class="card">
        <h3>Identitas</h3>
        <label for="app_name">Nama BUMDes / aplikasi</label>
        <input type="text" id="app_name" name="app_name" value="{{ old('app_name', $s['app_name']) }}" maxlength="100" required>

        <label for="address">Alamat (tampil di nota)</label>
        <input type="text" id="address" name="address" value="{{ old('address', $s['address']) }}" maxlength="255">

        <label for="phone">Telepon (tampil di nota)</label>
        <input type="text" id="phone" name="phone" value="{{ old('phone', $s['phone']) }}" maxlength="30">
    </div>

    <div class="card">
        <h3>Logo dan ikon</h3>

        <label for="logo">Logo (PNG/JPG/WEBP, maks. 1 MB)</label>
        @if ($s['logo_path'])
            <div class="prev"><img src="{{ asset('storage/' . $s['logo_path']) }}" alt="Logo"></div>
        @endif
        <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp">
        @if ($s['logo_path'])
            <label class="cek"><input type="checkbox" name="hapus_logo" value="1"> Hapus logo</label>
        @endif

        <label for="favicon" style="margin-top:1.2rem">Ikon tab browser / favicon (PNG/ICO, maks. 512 KB)</label>
        @if ($s['favicon_path'])
            <div class="prev"><img src="{{ asset('storage/' . $s['favicon_path']) }}" alt="Ikon" style="max-height:32px"></div>
        @endif
        <input type="file" id="favicon" name="favicon" accept="image/png,image/jpeg,image/webp,.ico">
        @if ($s['favicon_path'])
            <label class="cek"><input type="checkbox" name="hapus_favicon" value="1"> Hapus ikon</label>
        @endif
    </div>

    <div class="card">
        <h3>Nota</h3>
        <label for="receipt_title">Judul nota</label>
        <input type="text" id="receipt_title" name="receipt_title" value="{{ old('receipt_title', $s['receipt_title']) }}" maxlength="50" required>

        <label for="receipt_thanks">Ucapan di bawah nota</label>
        <textarea id="receipt_thanks" name="receipt_thanks" rows="2" maxlength="200">{{ old('receipt_thanks', $s['receipt_thanks']) }}</textarea>

        <label for="receipt_footer">Catatan kaki nota (opsional, mis. barang yang sudah dibeli tidak dapat dikembalikan)</label>
        <textarea id="receipt_footer" name="receipt_footer" rows="2" maxlength="300">{{ old('receipt_footer', $s['receipt_footer']) }}</textarea>

        <label class="cek">
            <input type="hidden" name="receipt_show_logo" value="0">
            <input type="checkbox" name="receipt_show_logo" value="1" @checked(old('receipt_show_logo', $s['receipt_show_logo'] ? '1' : '0') == '1')>
            Tampilkan logo di nota
        </label>
    </div>

    <div class="card">
        <h3>Tampilan</h3>
        <div class="fgrid">
            <div>
                <label for="warna_header">Warna header</label>
                <input type="color" id="warna_header" name="warna_header" value="{{ old('warna_header', $s['warna_header']) }}">
            </div>
            <div>
                <label for="warna_aksen">Warna tombol</label>
                <input type="color" id="warna_aksen" name="warna_aksen" value="{{ old('warna_aksen', $s['warna_aksen']) }}">
            </div>
        </div>
        <div class="muted" style="margin-top:.6rem">Pilih warna header yang cukup gelap supaya tulisan menu (putih) tetap terbaca.</div>
    </div>

    <button type="submit" class="btn" style="margin-top:0">Simpan Pengaturan</button>
</form>
@endsection