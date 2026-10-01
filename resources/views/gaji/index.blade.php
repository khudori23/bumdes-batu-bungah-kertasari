@extends('layouts.app')

@section('title', 'Gaji')

@section('content')
@include('gaji._nav')
@php
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $stat = ['DRAFT' => ['Draft', 'warn'], 'FINAL' => ['Diterbitkan', 'up'], 'PAID' => ['Dibayar', 'up']];
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
@endphp

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.6rem">
        <h2 style="margin:0">Penggajian {{ $label }}</h2>
        <form method="GET" action="{{ route('gaji.index') }}" style="display:flex;gap:.5rem">
            <input type="month" name="bulan" value="{{ $bulan }}" style="padding:.45rem;border:1px solid #cbd5e1;border-radius:8px">
            <button type="submit" class="btn" style="margin:0;padding:.5rem 1rem">Tampilkan</button>
        </form>
    </div>

    <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin:1rem 0">
        <span class="chip up">Gaji diterbitkan: {{ $rp($totalTerbit) }}</span>
        <span class="chip warn">{{ $jumlahDraft }} draft</span>
    </div>

    <div style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:center">
        <form method="POST" action="{{ route('gaji.generate') }}" onsubmit="return confirm('Buat draft gaji {{ $label }} untuk semua karyawan yang punya presensi?')">
            @csrf
            <input type="hidden" name="bulan" value="{{ $bulan }}">
            <button type="submit" class="btn" style="margin:0">Buat draft gaji {{ $label }}</button>
        </form>
        @if ($adaSlip)
            <a href="{{ route('gaji.slip.massal', ['bulan' => $bulan]) }}" target="_blank" class="btn" style="margin:0;text-decoration:none;background:#fff;color:#0f172a;border:1px solid #cbd5e1">Cetak semua slip</a>
            <a href="{{ route('gaji.penerimaan', ['bulan' => $bulan]) }}" target="_blank" class="btn" style="margin:0;text-decoration:none;background:#fff;color:#0f172a;border:1px solid #cbd5e1">Daftar penerimaan</a>
        @endif
    </div>

    <div style="overflow-x:auto">
    <table style="margin-top:1rem">
        <thead><tr><th>Nama</th><th>Jenis</th><th class="right">Hari</th><th class="right">Kg</th><th>Status</th><th class="right">Gaji diterima</th><th></th></tr></thead>
        <tbody>
            @forelse ($karyawan as $x)
                @php
                    $p = $payrolls->get($x->id);
                    $pre = $presensi->get($x->id);
                @endphp
                <tr>
                    <td><strong>{{ $x->name }}</strong></td>
                    <td><span class="chip">{{ $x->type === 'RELAWAN' ? 'Relawan' : 'Karyawan' }}</span></td>
                    <td class="right">{{ $fmt($p ? $p->days_present : ($pre->hari ?? 0)) }}</td>
                    <td class="right">{{ $fmt($p ? $p->kg_total : ($pre->kg ?? 0)) }}</td>
                    <td>
                        @if ($p)
                            <span class="chip {{ $stat[$p->status][1] }}">{{ $stat[$p->status][0] }}</span>
                        @else
                            <span class="chip">Belum ada draft</span>
                        @endif
                    </td>
                    <td class="right">{{ $p ? $rp($p->net_amount) : '-' }}</td>
                    <td>
                        @if ($p)
                            <a href="{{ route('gaji.show', $p) }}">Buka</a>
                            @if (in_array($p->status, ['FINAL', 'PAID'])) &middot; <a href="{{ route('gaji.slip', $p) }}" target="_blank">Slip</a> @endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">Belum ada karyawan.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection