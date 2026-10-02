@extends('layouts.app')

@section('title', 'Opname Hasil Produksi')

@section('content')
@include('layouts._errors')

<div class="card">
    <h2 style="margin-top:0">Opname stok hasil produksi</h2>
    <p class="muted">
        Timbang stok fisik tiap produk lalu isi kolom "Stok fisik". Produk yang dikosongkan tidak diopname.
        Kalau ada selisih, alasan wajib diisi.
    </p>

    @if ($bolehTulis)
        <form method="POST" action="{{ route('opnameproduk.store') }}">
            @csrf
            <div style="max-width:240px">
                <label>Tanggal opname</label>
                <input type="date" name="adjustment_date" value="{{ old('adjustment_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
            </div>

            <div style="margin-top:1rem">
                @forelse ($produk as $p)
                    <div class="op">
                        <div class="op-h">
                            <strong>{{ $p->name }}</strong>
                            <span class="muted">Sistem: {{ number_format($p->stok, 2, ',', '.') }} kg</span>
                        </div>
                        <div class="op-f">
                            <input type="number" inputmode="decimal" step="0.01" min="0" name="fisik[{{ $p->id }}]" value="{{ old('fisik.' . $p->id) }}" placeholder="Stok fisik (kg)" aria-label="Stok fisik {{ $p->name }}">
                            <input type="text" maxlength="255" name="alasan[{{ $p->id }}]" value="{{ old('alasan.' . $p->id) }}" placeholder="Alasan jika selisih" aria-label="Alasan {{ $p->name }}">
                        </div>
                    </div>
                @empty
                    <p class="muted">Belum ada produk aktif.</p>
                @endforelse
            </div>
            <button class="btn" type="submit" style="width:100%">Simpan opname</button>
        </form>
    @else
        <table class="rtable">
            <thead><tr><th>Produk</th><th class="right">Stok sistem (kg)</th></tr></thead>
            <tbody>
                @foreach ($produk as $p)
                    <tr><td data-label="Produk">{{ $p->name }}</td><td class="right" data-label="Stok sistem">{{ number_format($p->stok, 2, ',', '.') }}</td></tr>
                @endforeach
            </tbody>
        </table>
        <p class="muted">Anda hanya dapat melihat data ini.</p>
    @endif
</div>

<div class="card">
    <h3 style="margin-top:0">Riwayat opname</h3>
    <table class="rtable">
        <thead>
            <tr><th>Tanggal</th><th>Produk</th><th class="right">Sistem</th><th class="right">Fisik</th><th class="right">Selisih</th><th>Alasan</th><th>Oleh</th></tr>
        </thead>
        <tbody>
            @forelse ($riwayat as $r)
                <tr>
                    <td data-label="Tanggal">{{ $r->adjustment_date->format('d/m/Y') }}</td>
                    <td data-label="Produk"><strong>{{ $r->product?->name }}</strong></td>
                    <td class="right" data-label="Sistem">{{ number_format($r->system_qty, 2, ',', '.') }}</td>
                    <td class="right" data-label="Fisik">{{ number_format($r->physical_qty, 2, ',', '.') }}</td>
                    <td class="right" data-label="Selisih"><span class="chip {{ $r->difference_qty < 0 ? 'down' : ($r->difference_qty > 0 ? 'warn' : 'up') }}">{{ $r->difference_qty > 0 ? '+' : '' }}{{ number_format($r->difference_qty, 2, ',', '.') }}</span></td>
                    <td data-label="Alasan">{{ $r->reason ?: '-' }}</td>
                    <td data-label="Oleh">{{ $r->adjustedBy?->name }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">Belum ada opname.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection