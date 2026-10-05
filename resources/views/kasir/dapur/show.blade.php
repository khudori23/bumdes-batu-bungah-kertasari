@extends('layouts.app')

@section('title', 'Pesanan ' . $order->order_number)

@section('content')
<style>
    @media print {
        header, .noprint, .ok { display: none !important; }
        body { background: #fff; }
        main { margin: 0; max-width: 100%; padding: 0; }
        .card { border: 0; }
    }
    @media screen and (max-width:600px) { .noprint { flex-wrap: wrap; } .noprint .btn { flex: 1 1 auto; text-align: center; min-height: 44px; } }
</style>

@php
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $kg = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
    $batal = $order->status === 'VOIDED';
    $sisa = max(0, (float) $order->total_amount - $dibayar);
    $totalKg = (float) $order->lines->sum('quantity_kg');
@endphp

<div class="card" style="max-width:560px">
    @if ($batal)
        <div style="background:#fff1f2;border:1px solid #e11d48;color:#be123c;padding:.6rem;text-align:center;font-weight:700;margin-bottom:.8rem">
            PESANAN BATAL
            @if ($order->sale?->void_reason)<br><small style="font-weight:400">Alasan: {{ $order->sale->void_reason }}</small>@endif
        </div>
    @endif

    <h2 style="margin:0 0 .6rem">Pesanan Dapur</h2>
    <div style="font-size:.9rem">
        No. : {{ $order->order_number }}<br>
        Tgl : {{ $order->order_date->format('d/m/Y') }}<br>
        Ditagih ke : <strong>{{ $order->customer?->name }}</strong><br>
        @if ($order->sale)
            Nota tagihan : <a href="{{ route('kasir.show', $order->sale) }}">{{ $order->sale->sale_number }}</a><br>
        @endif
        Dicatat oleh : {{ $order->creator?->name }}
        @if ($order->notes)<br>Catatan : {{ $order->notes }}@endif
    </div>

    <table style="margin-top:.8rem;font-size:.9rem">
        <thead><tr><th>Dapur</th><th>Produk</th><th class="right">Kg</th></tr></thead>
        <tbody>
            @foreach ($order->lines as $l)
                <tr>
                    <td>{{ $l->kitchen_name }}</td>
                    <td>{{ $l->product?->name }}</td>
                    <td class="right">{{ $kg($l->quantity_kg) }}</td>
                </tr>
            @endforeach
            <tr style="font-weight:700"><td colspan="2">Total pesanan</td><td class="right">{{ $kg($totalKg) }}</td></tr>
        </tbody>
    </table>

    <hr style="border:0;border-top:1px dashed #94a3b8;margin:.8rem 0">

    <div style="display:flex;justify-content:space-between"><span>Bobot ayam hidup dipakai</span><span>{{ $kg($order->live_weight_kg) }} kg</span></div>
    <div style="display:flex;justify-content:space-between"><span>Harga per kg</span><span>{{ $rp($order->price_per_kg) }}</span></div>
    <div style="display:flex;justify-content:space-between;font-weight:700;font-size:1.05rem;margin-top:.3rem;{{ $batal ? 'text-decoration:line-through;color:#64748b' : '' }}"><span>Total tagihan</span><span>{{ $rp($order->total_amount) }}</span></div>

    @unless ($batal)
        <div style="display:flex;justify-content:space-between;margin-top:.3rem"><span>Sudah dibayar</span><span>{{ $rp($dibayar) }}</span></div>
        @if ($sisa > 0.004)
            <div style="display:flex;justify-content:space-between;font-weight:700;color:#e11d48"><span>Sisa tagihan</span><span>{{ $rp($sisa) }}</span></div>
        @else
            <div style="text-align:center;margin-top:.5rem;font-weight:700">LUNAS</div>
        @endif
    @endunless

    <div class="noprint" style="margin-top:1rem;display:flex;gap:.6rem">
        <button class="btn" style="margin:0" onclick="window.print()">Cetak</button>
        <a class="btn" style="margin:0;background:#fff;color:#0f172a;border:1px solid #cbd5e1;text-decoration:none" href="{{ route('kasir.dapur.index') }}">Daftar pesanan</a>
        @if ($order->sale && ! $batal)
            <a class="btn" style="margin:0;background:#fff;color:#0f172a;border:1px solid #cbd5e1;text-decoration:none" href="{{ route('piutang.show', $order->customer_id) }}">Bayar di Piutang</a>
        @endif
    </div>

    @if (! $batal && auth()->user()->isAdmin())
        <form method="POST" action="{{ route('kasir.dapur.void', $order) }}" class="noprint"
              style="margin-top:1rem;border-top:1px solid #e2e8f0;padding-top:.8rem"
              onsubmit="return confirm({{ \Illuminate\Support\Js::from('Batalkan pesanan ' . $order->order_number . '? Tindakan ini tidak bisa diurungkan.') }})">
            @csrf
            <label for="void_reason" style="font-size:.85rem">Alasan pembatalan</label>
            <input type="text" id="void_reason" name="void_reason" value="{{ old('void_reason') }}"
                   required minlength="5" maxlength="255" style="width:100%;margin:.3rem 0">
            @error('void_reason')
                <div style="color:#e11d48;font-size:.85rem;margin-bottom:.4rem">{{ $message }}</div>
            @enderror
            <button type="submit" class="btn" style="margin:0;background:#e11d48">Batalkan pesanan</button>
        </form>
    @endif
</div>
@endsection