@extends('layouts.app')

@section('title', 'Absen')

@section('content')
@php $jam = fn ($t) => $t?->timezone(config('app.timezone'))->format('H:i'); @endphp
<div class="card" style="max-width:480px">
    <h2 style="margin:0">Absen</h2>
    <div class="muted" style="margin-bottom:1rem">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</div>

    @if ($errors->any())
        <div style="background:#ffe4e6;border:1px solid #fecdd3;padding:.6rem .9rem;border-radius:8px;margin-bottom:1rem">
            @foreach ($errors->all() as $m)<div class="err" style="margin:0">{{ $m }}</div>@endforeach
        </div>
    @endif

    @if ($pesan)
        <p>{{ $pesan }}</p>
    @else
        <p style="font-size:1.1rem;margin:.2rem 0 1rem"><strong>{{ $pegawai->name }}</strong></p>

        @if (! $a)
            <p class="muted">Anda belum absen masuk hari ini.</p>
            <form method="POST" action="{{ route('absen.masuk') }}">
                @csrf
                <button type="submit" class="btn" style="width:100%;padding:1rem;font-size:1.1rem;margin:0">Absen Masuk</button>
            </form>
        @elseif (! $a->clock_in_at)
            <p>Presensi hari ini sudah diisi admin: <strong>{{ $status[$a->status] ?? $a->status }}</strong>.</p>
        @elseif (! $a->clock_out_at)
            <p>Masuk pukul <strong>{{ $jam($a->clock_in_at) }}</strong>. Belum absen pulang.</p>
            <form method="POST" action="{{ route('absen.pulang') }}" onsubmit="return confirm('Absen pulang sekarang?')">
                @csrf
                <button type="submit" class="btn" style="width:100%;padding:1rem;font-size:1.1rem;margin:0">Absen Pulang</button>
            </form>
            <div class="muted" style="margin-top:.6rem">Kerja minimal {{ rtrim(rtrim(number_format($minJam, 2, ',', '.'), '0'), ',') }} jam dihitung Hadir, kurang dari itu Setengah hari.</div>
        @else
            <p>Masuk <strong>{{ $jam($a->clock_in_at) }}</strong> &middot; Pulang <strong>{{ $jam($a->clock_out_at) }}</strong></p>
            <p>Status hari ini: <strong>{{ $status[$a->status] ?? $a->status }}</strong></p>
            <div class="muted">Bila ada yang salah, hubungi admin.</div>
        @endif
    @endif
</div>
@endsection