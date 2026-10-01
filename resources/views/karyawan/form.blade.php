@extends('layouts.app')

@section('title', $employee->exists ? 'Ubah Data' : 'Tambah Karyawan/Relawan')

@section('content')
@include('gaji._nav')
@php $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.'); @endphp

<div class="card" style="max-width:600px">
    <h2 style="margin-top:0">{{ $employee->exists ? 'Ubah ' . $employee->name . ' (' . $employee->code . ')' : 'Tambah Karyawan / Relawan' }}</h2>

    <form method="POST" action="{{ $employee->exists ? route('karyawan.update', $employee) : route('karyawan.store') }}" autocomplete="off">
        @csrf
        @if ($employee->exists) @method('PUT') @endif

        <label for="name">Nama lengkap</label>
        <input type="text" id="name" name="name" value="{{ old('name', $employee->name) }}" maxlength="100" required autofocus>

        <div class="fgrid">
            <div>
                <label for="type">Jenis</label>
                <select id="type" name="type" required>
                    @foreach ($jenis as $k => $v)
                        <option value="{{ $k }}" @selected(old('type', $employee->type) === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="position">Jabatan (opsional)</label>
                <input type="text" id="position" name="position" value="{{ old('position', $employee->position) }}" maxlength="60">
            </div>
        </div>

        <label for="phone">Telepon (opsional)</label>
        <input type="text" id="phone" name="phone" value="{{ old('phone', $employee->phone) }}" maxlength="30">

        <div class="fgrid">
            <div>
                <label for="daily_rate">Tarif harian (Rp)</label>
                <input type="number" id="daily_rate" name="daily_rate" value="{{ old('daily_rate', $num($employee->daily_rate)) }}" min="0" step="any" required>
            </div>
            <div>
                <label for="per_kg_rate">Tarif borongan per kg (Rp)</label>
                <input type="number" id="per_kg_rate" name="per_kg_rate" value="{{ old('per_kg_rate', $num($employee->per_kg_rate)) }}" min="0" step="any" required>
            </div>
        </div>
        <div class="muted" style="margin-top:.4rem">Relawan borongan: tarif harian 0, isi tarif per kg. Karyawan harian: isi tarif harian, tarif per kg 0. Keduanya boleh diisi bersamaan.</div>

        @if ($employee->exists)
            <label style="display:flex;gap:.5rem;align-items:center;margin-top:1rem">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $employee->is_active))>
                Aktif (yang nonaktif tidak muncul di presensi)
            </label>
        @endif

        <button type="submit" class="btn">Simpan</button>
        <a href="{{ route('karyawan.index') }}" style="margin-left:.8rem">Batal</a>
    </form>
</div>
@endsection