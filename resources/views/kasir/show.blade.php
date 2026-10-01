@extends('layouts.app')

@section('title', 'Nota ' . $sale->sale_number)

@section('content')
<style>
    @media print {
        header, .noprint, .ok { display: none !important; }
        body { background: #fff; }
        main { margin: 0; max-width: 100%; padding: 0; }
        .card { border: 0; }
    }
</style>

@php
    $rp = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $tagihan = $sale->total_amount + $sale->previous_balance;
    $batal = (bool) $sale->is_voided;
    $metode = $dibayar > 0
        ? ($sale->settlementPayments->first()?->payment_method === 'TRANSFER' ? 'Transfer' : 'Tunai')
        : 'Hutang';
    $waktuBatal = $sale->voided_at
        ? \Illuminate\Support\Carbon::parse($sale->voided_at)->timezone(config('app.timezone'))->format('d/m/Y H:i')
        : null;
@endphp

<div class="card" style="max-width:480px">
    @if ($batal)
        <div style="background:#fff1f2;border:1px solid #e11d48;color:#be123c;padding:.6rem;text-align:center;font-weight:700;margin-bottom:.8rem">
            NOTA BATAL
            @if ($waktuBatal)
                <small style="font-weight:400"> &middot; {{ $waktuBatal }}</small>
            @endif
            <br>
            <small style="font-weight:400">Alasan: {{ $sale->void_reason }}</small>
        </div>
    @endif

    <div style="text-align:center">
        <strong>BUMDes Batu Bungah Kertasari</strong><br>
        <small>Nota Penjualan</small>
    </div>
    <hr style="border:0;border-top:1px dashed #94a3b8;margin:.8rem 0">

    <div style="font-size:.9rem">
        No. : {{ $sale->sale_number }}<br>
        Tgl : {{ $sale->sale_date->format('d/m/Y') }}<br>
        Pelanggan : {{ $sale->customer_name }} ({{ $sale->customerType?->name }})<br>
        Kasir : {{ $sale->cashier?->name }}
    </div>

    <table style="margin-top:.8rem;font-size:.9rem">
        <thead>
            <tr><th>Produk</th><th class="right">Kg</th><th class="right">Harga</th><th class="right">Jumlah</th></tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $it)
                <tr>
                    <td>{{ $it->product?->name }}</td>
                    <td class="right">{{ rtrim(rtrim(number_format($it->quantity, 2, ',', '.'), '0'), ',') }}</td>
                    <td class="right">{{ number_format($it->unit_price, 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($it->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <hr style="border:0;border-top:1px dashed #94a3b8;margin:.8rem 0">

    <div style="display:flex;justify-content:space-between"><span>Subtotal</span><span>{{ $rp($sale->subtotal) }}</span></div>
    <div style="display:flex;justify-content:space-between"><span>Diskon</span><span>{{ $rp($sale->discount) }}</span></div>
    <div style="display:flex;justify-content:space-between;font-weight:700"><span>Total belanja</span><span>{{ $rp($sale->total_amount) }}</span></div>

    @if ($sale->previous_balance > 0)
        <div style="display:flex;justify-content:space-between;margin-top:.4rem"><span>Hutang sebelumnya</span><span>{{ $rp($sale->previous_balance) }}</span></div>
        <div style="display:flex;justify-content:space-between;font-weight:700;font-size:1.05rem"><span>Total tagihan</span><span>{{ $rp($tagihan) }}</span></div>
    @endif

    <div style="display:flex;justify-content:space-between;margin-top:.4rem;{{ $batal ? 'text-decoration:line-through;color:#64748b' : '' }}">
        <span>Dibayar ({{ $metode }})</span><span>{{ $rp($dibayar) }}</span>
    </div>

    {{-- Status hutang hanya relevan untuk nota yang masih berlaku --}}
    @unless ($batal)
        @if ($sale->balance_after > 0.004)
            <div style="display:flex;justify-content:space-between;font-weight:700;font-size:1.1rem;color:#e11d48;margin-top:.4rem">
                <span>SISA HUTANG</span><span>{{ $rp($sale->balance_after) }}</span>
            </div>
            @if ($sale->balance_due_date)
                <div style="text-align:right;font-size:.85rem">
                    Jatuh tempo: <strong>{{ \Illuminate\Support\Carbon::parse($sale->balance_due_date)->format('d/m/Y') }}</strong>
                </div>
            @endif
        @else
            <div style="text-align:center;margin-top:.5rem;font-weight:700">LUNAS</div>
        @endif
    @endunless

    <p style="text-align:center;margin-top:1rem;font-size:.85rem">Terima kasih</p>

    <div class="noprint" style="margin-top:1rem;display:flex;gap:.6rem">
        <button class="btn" style="margin:0" onclick="window.print()">Cetak</button>
        <a class="btn" style="margin:0;background:#fff;color:#0f172a;border:1px solid #cbd5e1;text-decoration:none" href="{{ route('kasir.create') }}">Transaksi baru</a>
        <a class="btn" style="margin:0;background:#fff;color:#0f172a;border:1px solid #cbd5e1;text-decoration:none" href="{{ route('kasir.index') }}">Riwayat</a>
    </div>

    {{-- Pembatalan: khusus admin, hanya untuk nota yang belum batal --}}
    @if (! $batal && auth()->user()->isAdmin())
        <form method="POST" action="{{ route('kasir.void', $sale) }}" class="noprint"
              style="margin-top:1rem;border-top:1px solid #e2e8f0;padding-top:.8rem"
              onsubmit="return confirm({{ \Illuminate\Support\Js::from('Batalkan nota ' . $sale->sale_number . '? Tindakan ini tidak bisa diurungkan.') }})">
            @csrf
            <label for="void_reason" style="font-size:.85rem">Alasan pembatalan</label>
            <input type="text" id="void_reason" name="void_reason" value="{{ old('void_reason') }}"
                   required minlength="5" maxlength="255" style="width:100%;margin:.3rem 0">
            @error('void_reason')
                <div style="color:#e11d48;font-size:.85rem;margin-bottom:.4rem">{{ $message }}</div>
            @enderror
            <button type="submit" class="btn" style="margin:0;background:#e11d48">Batalkan nota</button>
        </form>
    @endif
</div>
@endsection