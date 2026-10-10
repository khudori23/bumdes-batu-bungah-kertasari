@extends('layouts.app')

@section('title', 'Ayam Keluar')

@section('content')
@include('layouts._errors')
@php
    $ekor = fn ($n) => number_format((float) $n, 0, ',', '.');
    $kg   = fn ($n) => number_format((float) $n, 2, ',', '.');
    $tgl  = fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('d/m/Y');
@endphp

<div class="card">
    <h2 style="margin-top:0">Ayam masuk produksi</h2>
    <p class="muted">
        Stok ayam hidup saat ini: <strong>{{ $ekor($stok['ekor']) }} ekor</strong> &middot; {{ number_format($stok['kg'], 1, ',', '.') }} kg.
        Catat ayam yang diserahkan ke pemotongan. Kalau diisi lebih dari sekali pada tanggal yang sama, jumlahnya digabung.
    </p>

    @if ($bolehTulis)
        <form method="POST" action="{{ route('ayamkeluar.store') }}">
            @csrf
            <div class="fgrid">
                <div>
                    <label>Tanggal produksi</label>
                    <input type="date" name="production_date" value="{{ old('production_date', now()->toDateString()) }}" min="{{ \App\Support\Pencatatan::mulai() }}" max="{{ now()->toDateString() }}" required>
                </div>
                <div>
                    <label>Ayam masuk produksi (ekor)</label>
                    <input type="number" inputmode="numeric" name="input_birds" min="1" step="1" value="{{ old('input_birds') }}" required>
                </div>
                <div>
                    <label>Berat ayam hidup (kg)</label>
                    <input type="number" inputmode="decimal" name="input_weight_kg" min="0.01" step="0.01" value="{{ old('input_weight_kg') }}" required>
                </div>
            </div>
            <label>Catatan (opsional)</label>
            <input type="text" name="notes" maxlength="255" value="{{ old('notes') }}">
            <button type="submit" class="btn" style="width:100%">Simpan ayam masuk produksi</button>
        </form>
    @else
        <p class="muted">Anda hanya dapat melihat data ini.</p>
    @endif
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
    <h3 style="margin-top:0">Ayam dijual per ekor</h3>
    <p class="muted">Dicatat Kasir saat transaksi, ditampilkan di sini untuk memantau stok. Bulan ini: <strong>{{ $ekor($jualBulan->e) }} ekor</strong> &middot; {{ $kg($jualBulan->k) }} kg.</p>
    <table class="rtable">
        <thead><tr><th>Tanggal</th><th>No. Nota</th><th>Pembeli</th><th class="right">Ekor</th><th class="right">Berat (kg)</th></tr></thead>
        <tbody>
            @forelse ($jual as $j)
                <tr>
                    <td data-label="Tanggal">{{ $tgl($j->sale_date) }}</td>
                    <td data-label="No. Nota">{{ $j->sale_number }}</td>
                    <td data-label="Pembeli">{{ $j->customer_name ?: '-' }}</td>
                    <td class="right" data-label="Ekor">{{ $ekor($j->quantity) }}</td>
                    <td class="right" data-label="Berat (kg)">{{ $j->weight_kg !== null ? $kg($j->weight_kg) : '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Belum ada ayam yang dijual per ekor.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@include('ayam-keluar._jual-ecer')
@endsection