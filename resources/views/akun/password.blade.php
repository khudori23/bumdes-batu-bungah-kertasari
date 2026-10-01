@extends('layouts.app')

@section('title', 'Ganti Password')

@section('content')
    <div class="card" style="max-width:420px">
        <h2 style="margin-top:0">Ganti Password</h2>
        <form method="POST" action="{{ route('akun.password.update') }}">
            @csrf
            @method('PUT')

            <label for="password_lama">Password lama</label>
            <input type="password" id="password_lama" name="password_lama" autocomplete="current-password">
            @error('password_lama') <div class="err">{{ $message }}</div> @enderror

            <label for="password_baru">Password baru (minimal 8 karakter)</label>
            <input type="password" id="password_baru" name="password_baru" autocomplete="new-password">
            @error('password_baru') <div class="err">{{ $message }}</div> @enderror

            <label for="password_baru_confirmation">Ulangi password baru</label>
            <input type="password" id="password_baru_confirmation" name="password_baru_confirmation" autocomplete="new-password">

            <button class="btn" type="submit">Simpan</button>
        </form>
    </div>
@endsection