@extends('layouts.app')

@section('title', 'Nota Ayam Ecer')

@section('content')
@include('layouts._errors')
@php
    $ekor = fn ($n) => number_format((float) $n, 0, ',', '.');
    $kg   = fn ($n) => number_format((float) $n, 2, ',', '.');
    $tgl  = fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('d/m/Y');
@endphp

<div class="card">
    <p style="margin-top:0"><a class="btn" href="{{ route('kasir.ayamhidup.langsung') }}" style="width:auto">+ Jual ayam ecer langsung</a></p>
    <h2 style="margin-top:0">Ayam ecer menunggu nota</h2>
    <p class="muted">
        Penjualan ayam ecer yang dicatat pengawas. Buat nota di sini supaya harga, pembayaran, dan hutang tercatat.
        Stok tidak terhitung dua kali.
    </p>
    <table class="rtable">
        <thead><tr><th>Tanggal</th><th>Pelanggan</th><th class="right">Ekor</th><th class="right">Berat (kg)</th><th>Catatan</th><th></th></tr></thead>
        <tbody>
            @forelse ($daftar as $d)
                <tr>
                    <td data-label="Tanggal">{{ $tgl($d->sale_date) }}</td>
                    <td data-label="Pelanggan">{{ $d->customer_name }}</td>
                    <td class="right" data-label="Ekor">{{ $ekor($d->birds) }}</td>
                    <td class="right" data-label="Berat (kg)">{{ $kg($d->weight_kg) }}</td>
                    <td data-label="Catatan">{{ $d->notes ?: '-' }}</td>
                    <td>
                        <a class="btn" href="{{ route('kasir.ayamhidup.create', $d->id) }}">Buat nota</a>
                        @if (auth()->user()->isAdmin())
                            <form method="POST" action="{{ route('kasir.ayamhidup.hapuscatatan', $d->id) }}" style="margin-top:.4rem"
                                  onsubmit="var a=prompt('Alasan hapus permanen (min. 5 huruf):'); if(!a||a.length<5){return false;} this.alasan.value=a; this.konfirmasi.value='HAPUS'; return confirm('Hapus catatan ini permanen?');">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="alasan"><input type="hidden" name="konfirmasi">
                                <button type="submit" class="btn" style="background:#e11d48;width:auto">Hapus</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Tidak ada catatan yang menunggu nota.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="card">
    <h3 style="margin-top:0">Sudah dinota (20 terakhir)</h3>
    <table class="rtable">
        <thead><tr><th>Tanggal</th><th>Pelanggan</th><th class="right">Ekor</th><th class="right">Berat (kg)</th><th>No. Nota</th></tr></thead>
        <tbody>
            @forelse ($sudah as $s)
                <tr>
                    <td data-label="Tanggal">{{ $tgl($s->sale_date) }}</td>
                    <td data-label="Pelanggan">{{ $s->customer_name }}</td>
                    <td class="right" data-label="Ekor">{{ $ekor($s->birds) }}</td>
                    <td class="right" data-label="Berat (kg)">{{ $kg($s->weight_kg) }}</td>
                    <td data-label="No. Nota"><a href="{{ route('kasir.show', $s->sale_id) }}">{{ $s->sale_number }}</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Belum ada.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection