@extends('layouts.app')

@section('title', 'Riwayat Penjualan')

@section('content')
@php
    $rp = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');

    // Ringkasan hanya menghitung nota yang masih berlaku
    $aktif = $sales->where('is_voided', false);
    $totalAktif = $aktif->sum('total_amount');
    $totalHutang = $aktif->sum(fn ($s) => max(0, $s->total_amount - ($s->dibayar ?? 0)));
    $jumlahBatal = $sales->where('is_voided', true)->count();
@endphp

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.6rem">
        <h2 style="margin:0">Riwayat Penjualan <small style="font-weight:400;color:#64748b">(50 terakhir)</small></h2>
        <a class="btn" style="margin:0;text-decoration:none" href="{{ route('kasir.create') }}">Transaksi baru</a>
    </div>

    @if ($sales->isNotEmpty())
        <div style="display:flex;gap:1.5rem;flex-wrap:wrap;margin:1rem 0;font-size:.9rem">
            <div>Total penjualan<br><strong>{{ $rp($totalAktif) }}</strong></div>
            <div>Hutang belum lunas<br><strong style="color:{{ $totalHutang > 0.004 ? '#e11d48' : 'inherit' }}">{{ $rp($totalHutang) }}</strong></div>
            @if ($jumlahBatal > 0)
                <div>Nota batal<br><strong>{{ $jumlahBatal }}</strong></div>
            @endif
        </div>
    @endif

    <div style="overflow-x:auto">
        <table>
            <thead>
                <tr>
                    <th>No. Nota</th>
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th>Jenis</th>
                    <th class="right">Total</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sales as $s)
                    @php
                        $batal = (bool) $s->is_voided;
                        $sisa = $s->total_amount - ($s->dibayar ?? 0);
                    @endphp
                    <tr style="{{ $batal ? 'color:#94a3b8' : '' }}">
                        <td style="{{ $batal ? 'text-decoration:line-through' : '' }}">{{ $s->sale_number }}</td>
                        <td>{{ $s->sale_date->format('d/m/Y') }}</td>
                        <td>{{ $s->customer_name }}</td>
                        <td>{{ $s->customerType?->name }}</td>
                        <td class="right" style="{{ $batal ? 'text-decoration:line-through' : '' }}">{{ $rp($s->total_amount) }}</td>
                        <td>
                            @if ($batal)
                                <span style="color:#be123c;font-weight:700" @if ($s->void_reason) title="{{ $s->void_reason }}" @endif>BATAL</span>
                            @elseif ($sisa > 0.004)
                                <span style="color:#e11d48">Hutang {{ $rp($sisa) }}</span>
                            @else
                                <span style="color:#0d9488">Lunas</span>
                            @endif
                        </td>
                        <td><a href="{{ route('kasir.show', $s) }}">Nota</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7">Belum ada transaksi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection