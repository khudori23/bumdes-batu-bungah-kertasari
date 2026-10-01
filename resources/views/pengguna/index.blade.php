@extends('layouts.app')

@section('title', 'Pengguna')

@section('content')
@php $peran = \App\Http\Controllers\PenggunaController::PERAN; @endphp

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center">
        <h2 style="margin:0">Pengguna</h2>
        <a href="{{ route('pengguna.create') }}" class="btn" style="margin:0;text-decoration:none">+ Tambah Pengguna</a>
    </div>

    <div style="overflow-x:auto">
    <table style="margin-top:1rem">
        <thead>
            <tr><th>Username</th><th>Nama</th><th>Peran</th><th>Email</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            @foreach ($users as $p)
                <tr style="{{ $p->is_active ? '' : 'color:#94a3b8' }}">
                    <td><strong>{{ $p->username }}</strong>
                        @if ($p->id === auth()->id()) <span class="chip">Anda</span> @endif
                    </td>
                    <td>{{ $p->name }}</td>
                    <td>{{ $peran[$p->role] ?? $p->role }}</td>
                    <td>{{ $p->email ?: '-' }}</td>
                    <td>
                        @if ($p->is_active)
                            <span class="chip up">Aktif</span>
                        @else
                            <span class="chip down">Nonaktif</span>
                        @endif
                    </td>
                    <td><a href="{{ route('pengguna.edit', $p) }}">Ubah</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>
</div>
@endsection