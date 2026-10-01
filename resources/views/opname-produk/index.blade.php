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

            <div style="overflow-x:auto;margin-top:.8rem">
                <table>
                    <thead>
                        <tr><th>Produk</th><th class="right">Stok sistem (kg)</th><th>Stok fisik (kg)</th><th>Alasan jika selisih</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($produk as $p)
                            <tr>
                                <td>{{ $p->name }}</td>
                                <td class="right">{{ number_format($p->stok, 2, ',', '.') }}</td>
                                <td style="min-width:130px">
                                    <input type="number" step="0.01" min="0" name="fisik[{{ $p->id }}]" value="{{ old('fisik.' . $p->id) }}">
                                </td>
                                <td style="min-width:200px">
                                    <input type="text" maxlength="255" name="alasan[{{ $p->id }}]" value="{{ old('alasan.' . $p->id) }}">
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="muted">Belum ada produk aktif.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <button class="btn" type="submit">Simpan opname</button>
        </form>
    @else
        <div style="overflow-x:auto">
            <table>
                <thead><tr><th>Produk</th><th class="right">Stok sistem (kg)</th></tr></thead>
                <tbody>
                    @foreach ($produk as $p)
                        <tr><td>{{ $p->name }}</td><td class="right">{{ number_format($p->stok, 2, ',', '.') }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="muted">Anda hanya dapat melihat data ini.</p>
    @endif
</div>

<div class="card">
    <h3 style="margin-top:0">Riwayat opname</h3>
    <div style="overflow-x:auto">
        <table>
            <thead>
                <tr><th>Tanggal</th><th>Produk</th><th class="right">Sistem</th><th class="right">Fisik</th><th class="right">Selisih</th><th>Alasan</th><th>Oleh</th></tr>
            </thead>
            <tbody>
                @forelse ($riwayat as $r)
                    <tr>
                        <td>{{ $r->adjustment_date->format('d/m/Y') }}</td>
                        <td>{{ $r->product?->name }}</td>
                        <td class="right">{{ number_format($r->system_qty, 2, ',', '.') }}</td>
                        <td class="right">{{ number_format($r->physical_qty, 2, ',', '.') }}</td>
                        <td class="right">
                            <span class="chip {{ $r->difference_qty < 0 ? 'down' : ($r->difference_qty > 0 ? 'warn' : 'up') }}">{{ $r->difference_qty > 0 ? '+' : '' }}{{ number_format($r->difference_qty, 2, ',', '.') }}</span>
                        </td>
                        <td>{{ $r->reason }}</td>
                        <td>{{ $r->adjustedBy?->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">Belum ada opname.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection