@extends('layouts.app')

@section('title', 'Riwayat Ayam')

@section('content')
@include('layouts._errors')
@php
    $ekor = fn ($n) => number_format((float) $n, 0, ',', '.');
    $kg   = fn ($n) => number_format((float) $n, 2, ',', '.');
    $tgl  = fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('d/m/Y');
@endphp

<div class="card">
    <h2 style="margin-top:0">Riwayat ayam</h2>
    <p class="muted">
        Produksi dan penjualan ayam (100 data terakhir per tabel). Untuk mencatat data baru, buka menu Ayam Keluar.
    </p>
</div>

<div class="card">
    <h3 style="margin-top:0">Riwayat produksi per tanggal</h3>
    <table class="rtable">
        <thead><tr><th>Tanggal / No.</th><th class="right">Ayam masuk</th><th class="right">Hasil potong (kg)</th><th class="right">Rendemen</th></tr></thead>
        <tbody>
            @forelse ($batches as $b)
                @php
                    $hasil = (float) ($b->hasil_kg ?? 0);
                    $rend  = $b->input_weight_kg > 0 && $hasil > 0 ? $hasil / (float) $b->input_weight_kg * 100 : null;
                @endphp
                <tr>
                    <td data-label="Tanggal"><span>{{ $b->production_date->format('d/m/Y') }}<br><small class="muted">{{ $b->batch_number }}</small></span></td>
                    <td class="right" data-label="Ayam masuk">
                        @if ($b->input_birds > 0)<span>{{ $ekor($b->input_birds) }} ekor<br><small class="muted">{{ $kg($b->input_weight_kg) }} kg</small></span>@else - @endif
                    </td>
                    <td class="right" data-label="Hasil potong">{{ $hasil > 0 ? $kg($hasil) : '-' }}</td>
                    <td class="right" data-label="Rendemen">
                        @if ($rend !== null)<span class="chip {{ $rend < 65 || $rend > 100 ? 'warn' : 'up' }}">{{ number_format($rend, 1, ',', '.') }}%</span>@else - @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">Belum ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="card">
    <h3 style="margin-top:0">Riwayat penjualan ayam ecer</h3>
    <p class="muted">
        Dicatat pengawas, lalu dinota kasir lewat menu Ayam Ecer.
        Bulan ini: <strong>{{ $ekor($ecerBulan->e) }} ekor</strong> &middot; {{ $kg($ecerBulan->k) }} kg.
    </p>
    <table class="rtable">
        <thead><tr><th>Tanggal</th><th>Pelanggan</th><th class="right">Ekor</th><th class="right">Berat (kg)</th><th>Catatan</th><th>Nota</th></tr></thead>
        <tbody>
            @forelse ($ecer as $e)
                <tr>
                    <td data-label="Tanggal">{{ $tgl($e->sale_date) }}</td>
                    <td data-label="Pelanggan">{{ $e->customer_name }}</td>
                    <td class="right" data-label="Ekor">{{ $ekor($e->birds) }}</td>
                    <td class="right" data-label="Berat (kg)">{{ $kg($e->weight_kg) }}</td>
                    <td data-label="Catatan">{{ $e->notes ?: '-' }}</td>
                    <td data-label="Nota">
                        @if ($e->sale_number && ! $e->nota_batal)
                            <span class="chip up">{{ $e->sale_number }}</span>
                        @else
                            <span class="chip warn">Belum dinota</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Belum ada penjualan yang dicatat pengawas.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if (count($jual) > 0)
<div class="card">
    <h3 style="margin-top:0">Ayam dijual lewat kasir biasa (data lama)</h3>
    <p class="muted">
        Penjualan ayam hidup yang dulu dicatat kasir lewat halaman kasir biasa. Penjualan baru dicatat pengawas, lalu dinota kasir lewat menu Ayam Ecer, jadi tidak muncul di tabel ini.
        Bulan ini: <strong>{{ $ekor($jualBulan->e) }} ekor</strong> &middot; {{ $kg($jualBulan->k) }} kg.
    </p>
    <table class="rtable">
        <thead><tr><th>Tanggal</th><th>No. Nota</th><th>Pembeli</th><th class="right">Ekor</th><th class="right">Berat (kg)</th></tr></thead>
        <tbody>
            @foreach ($jual as $j)
                <tr>
                    <td data-label="Tanggal">{{ $tgl($j->sale_date) }}</td>
                    <td data-label="No. Nota">{{ $j->sale_number }}</td>
                    <td data-label="Pembeli">{{ $j->customer_name ?: '-' }}</td>
                    <td class="right" data-label="Ekor">{{ $ekor($j->quantity) }}</td>
                    <td class="right" data-label="Berat (kg)">{{ $j->weight_kg !== null ? $kg($j->weight_kg) : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection