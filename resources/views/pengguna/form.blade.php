@extends('layouts.app')

@section('title', $user->exists ? 'Ubah Pengguna' : 'Tambah Pengguna')

@section('content')
@php
    $peran = \App\Http\Controllers\PenggunaController::PERAN;
    $diri  = $user->exists && $user->id === auth()->id();
@endphp

<div class="card" style="max-width:560px">
    <h2 style="margin-top:0">{{ $user->exists ? 'Ubah Pengguna ' . $user->username : 'Tambah Pengguna' }}</h2>

    @if ($errors->any())
        <div style="background:#ffe4e6;border:1px solid #fecdd3;padding:.6rem .9rem;border-radius:8px;margin-bottom:1rem">
            @foreach ($errors->all() as $e)
                <div class="err" style="margin:0">{{ $e }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ $user->exists ? route('pengguna.update', $user) : route('pengguna.store') }}" autocomplete="off">
        @csrf
        @if ($user->exists) @method('PUT') @endif

        <label for="username">Username (untuk login)</label>
        <input type="text" id="username" name="username" value="{{ old('username', $user->username) }}" maxlength="50" required autofocus>

        <label for="name">Nama lengkap</label>
        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" maxlength="100" required>

        <label for="email">Email (opsional)</label>
        <input type="text" id="email" name="email" value="{{ old('email', $user->email) }}" maxlength="100">

        <label for="role">Peran</label>
        @if ($diri)
            <input type="hidden" name="role" value="{{ $user->role }}">
            <div class="muted">{{ $peran[$user->role] ?? $user->role }} (akun Anda sendiri, peran tidak bisa diubah dari sini)</div>
        @else
            <select id="role" name="role" required>
                @foreach ($peran as $kode => $label)
                    <option value="{{ $kode }}" @selected(old('role', $user->role) === $kode)>{{ $label }}</option>
                @endforeach
            </select>
        @endif

        @unless ($user->exists)
            <label for="password">Password (minimal 8 karakter)</label>
            <input type="password" id="password" name="password" required autocomplete="new-password">

            <label for="password_confirmation">Ulangi password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
        @endunless

        @if ($user->exists && ! $diri)
            <label style="display:flex;gap:.5rem;align-items:center;margin-top:1rem">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active))>
                Akun aktif (yang nonaktif tidak bisa login dan langsung dikeluarkan)
            </label>
        @endif

        <button type="submit" class="btn">Simpan</button>
        <a href="{{ route('pengguna.index') }}" style="margin-left:.8rem">Batal</a>
    </form>
</div>

@if ($user->exists && ! $diri)
<div class="card" style="max-width:560px">
    <h3 style="margin-top:0">Atur ulang password</h3>
    <form method="POST" action="{{ route('pengguna.password', $user) }}" autocomplete="off">
        @csrf
        <label for="np">Password baru (minimal 8 karakter)</label>
        <input type="password" id="np" name="password" required autocomplete="new-password">

        <label for="np2">Ulangi password baru</label>
        <input type="password" id="np2" name="password_confirmation" required autocomplete="new-password">

        <button type="submit" class="btn" style="background:#d97706">Atur Ulang Password</button>
    </form>
</div>
@endif
@endsection