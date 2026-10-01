@extends('layouts.app')

@section('title', 'Pengaturan Slip')

@section('content')
@include('gaji._nav')

<div class="card" style="max-width:640px">
    <h2 style="margin-top:0">Pengaturan Slip Gaji</h2>
    <div class="muted">Kop slip (nama, alamat, telepon, logo) mengikuti menu Pengaturan utama.</div>

    <form method="POST" action="{{ route('gaji.pengaturan.simpan') }}" enctype="multipart/form-data">
        @csrf @method('PUT')

        <label for="slip_manager_name">Nama manager (penyetuju)</label>
        <input type="text" id="slip_manager_name" name="slip_manager_name" maxlength="100" required value="{{ old('slip_manager_name', $s['slip_manager_name'] ?? '') }}">

        <label for="slip_manager_title">Jabatan</label>
        <input type="text" id="slip_manager_title" name="slip_manager_title" maxlength="100" required value="{{ old('slip_manager_title', $s['slip_manager_title'] ?? 'Manager BUMDes') }}">

        <label for="slip_note">Catatan kaki slip (opsional)</label>
        <input type="text" id="slip_note" name="slip_note" maxlength="300" value="{{ old('slip_note', $s['slip_note'] ?? '') }}">

        <label for="ttd">Tanda tangan manager (PNG latar transparan paling bagus, maks. 512 KB)</label>
        @if (! empty($s['slip_signature_path']))
            <div style="margin:.4rem 0"><img src="{{ route('gaji.ttd') }}?v={{ time() }}" alt="Tanda tangan" style="max-height:90px;border:1px solid var(--line);border-radius:8px;padding:6px;background:#fff"></div>
        @endif
        <input type="file" id="ttd" name="ttd" accept="image/png,image/jpeg,image/webp">
        @if (! empty($s['slip_signature_path']))
            <label style="display:flex;gap:.5rem;align-items:center"><input type="checkbox" name="hapus_ttd" value="1"> Hapus tanda tangan</label>
        @endif
        <div class="muted" style="margin-top:.4rem">Gambar disimpan privat dan hanya tampil di slip yang sudah diterbitkan.</div>

        <button type="submit" class="btn">Simpan</button>
    </form>
</div>
@endsection