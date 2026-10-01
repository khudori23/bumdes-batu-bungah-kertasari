@extends('layouts.app')

@section('title', 'Karyawan & Relawan')

@section('content')
@include('gaji._nav')
@php $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); @endphp

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center">
        <h2 style="margin:0">Karyawan &amp; Relawan</h2>
        <a href="{{ route('karyawan.create') }}" class="btn" style="margin:0;text-decoration:none">+ Tambah</a>
    </div>
    <div style="overflow-x:auto">
    <table style="margin-top:1rem">
        <thead>
            <tr><th>Kode</th><th>Nama</th><th>Jenis</th><th>Jabatan</th><th class="right">Tarif harian</th><th class="right">Tarif / kg</th><th class="right">Saldo kasbon</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($list as $x)
                @php $saldo = (float) $x->kasbon - (float) $x->cicilan; @endphp
                <tr style="{{ $x->is_active ? '' : 'color:#94a3b8' }}">
                    <td>{{ $x->code }}</td>
                    <td><strong>{{ $x->name }}</strong></td>
                    <td><span class="chip">{{ $x->type === 'RELAWAN' ? 'Relawan' : 'Karyawan' }}</span></td>
                    <td>{{ $x->position ?: '-' }}</td>
                    <td class="right">{{ $rp($x->daily_rate) }}</td>
                    <td class="right">{{ $rp($x->per_kg_rate) }}</td>
                    <td class="right">{{ $saldo > 0.004 ? $rp($saldo) : '-' }}</td>
                    <td><span class="chip {{ $x->is_active ? 'up' : 'down' }}">{{ $x->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td><a href="{{ route('karyawan.edit', $x) }}">Ubah</a></td>
                </tr>
            @empty
                <tr><td colspan="9">Belum ada data. Tambahkan karyawan atau relawan pertama.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection