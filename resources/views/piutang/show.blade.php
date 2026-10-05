@extends('layouts.app')

@section('title', 'Hutang ' . $customer->name)

@section('content')
@php
    $rp = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $tz = config('app.timezone');
    $punyaHutang = $saldo > 0.004;
    $terlambat = $notaTerbuka->where('terlambat_hari', '>', 0);
@endphp

<a href="{{ route('piutang.index') }}" style="font-size:.9rem">← Semua hutang</a>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:1rem;margin-top:.8rem;align-items:start">
    <div class="card">
        <h2 style="margin-top:0">{{ $customer->name }}</h2>
        <div style="color:#64748b;font-size:.9rem">{{ $customer->phone ?: 'Telepon belum diisi' }}</div>

        <div style="margin-top:.8rem;font-size:.85rem;color:#64748b">SISA HUTANG</div>
        <div style="font-size:2rem;font-weight:700;color:{{ $punyaHutang ? '#e11d48' : '#0d9488' }}">{{ $rp($saldo) }}</div>

        @if ($terlambat->isNotEmpty())
            <div style="margin-top:.3rem;font-size:.85rem;color:#e11d48;font-weight:600">
                {{ $terlambat->count() }} nota lewat jatuh tempo ({{ $rp($terlambat->sum('sisa')) }}),
                tertua {{ $terlambat->max('terlambat_hari') }} hari
            </div>
        @elseif ($punyaHutang && strtoupper((string) auth()->user()->role) !== 'MANAGER')
            <div style="margin-top:.3rem;font-size:.85rem;color:#64748b">Belum ada nota yang lewat jatuh tempo.</div>
        @else
            <div style="margin-top:.3rem;font-size:.85rem;color:#0d9488;font-weight:600">Semua nota lunas.</div>
        @endif

        @if ($punyaHutang)
            <form method="POST" action="{{ route('piutang.bayar', $customer) }}" style="margin-top:1rem"
                  onsubmit="var b=this.querySelector('button[type=submit]');b.disabled=true;b.textContent='Menyimpan...'">
                @csrf
                <label for="amount">Jumlah dibayar (Rp)</label>
                <input type="number" inputmode="decimal" min="0.01" max="{{ number_format($saldo, 2, '.', '') }}" step="0.01"
                       name="amount" id="amount" value="{{ old('amount') }}" required style="font-size:1.1rem;text-align:right">
                @error('amount') <div class="err">{{ $message }}</div> @enderror

                <div style="margin-top:.4rem">
                    <button type="button" onclick="document.getElementById('amount').value='{{ number_format($saldo, 2, '.', '') }}'"
                            style="background:#fff;border:1px solid #cbd5e1;border-radius:8px;padding:.3rem .7rem;cursor:pointer">Lunasi semua</button>
                </div>

                @php $metode = old('payment_method', 'CASH'); @endphp
                <div style="margin-top:.8rem">
                    <label style="display:inline;margin-right:1rem"><input type="radio" name="payment_method" value="CASH" {{ $metode === 'CASH' ? 'checked' : '' }}> Tunai</label>
                    <label style="display:inline"><input type="radio" name="payment_method" value="TRANSFER" {{ $metode === 'TRANSFER' ? 'checked' : '' }}> Transfer</label>
                </div>
                @error('payment_method') <div class="err">{{ $message }}</div> @enderror

                <label for="reference_number">No. referensi (opsional)</label>
                <input type="text" name="reference_number" id="reference_number" maxlength="100" value="{{ old('reference_number') }}">
                @error('reference_number') <div class="err">{{ $message }}</div> @enderror

                <button class="btn" type="submit">Catat pembayaran</button>
                <div style="font-size:.78rem;color:#64748b;margin-top:.4rem">Pembayaran melunasi nota yang paling lama lebih dulu.</div>
            </form>
        @endif
    </div>

    <div class="card">
        <h3 style="margin-top:0">Nota yang belum lunas</h3>
        <div style="overflow-x:auto">
            <table>
                <thead><tr><th>Nota</th><th>Jatuh tempo</th><th class="right">Sisa</th></tr></thead>
                <tbody>
                    @forelse ($notaTerbuka as $n)
                        <tr>
                            <td>
                                <a href="{{ route('kasir.show', $n->id) }}">{{ $n->sale_number }}</a>
                                <div style="font-size:.78rem;color:#64748b">{{ \Illuminate\Support\Carbon::parse($n->sale_date)->format('d/m/Y') }}</div>
                            </td>
                            <td>
                                {{ \Illuminate\Support\Carbon::parse($n->jatuh_tempo)->format('d/m/Y') }}
                                @if ($n->terlambat_hari > 0)
                                    <div style="font-size:.78rem;color:#e11d48;font-weight:600">Terlambat {{ $n->terlambat_hari }} hari</div>
                                @endif
                            </td>
                            <td class="right">{{ number_format($n->sisa, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3">Semua nota lunas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <h3>Riwayat pembayaran <small style="font-weight:400;color:#64748b">(30 terakhir)</small></h3>
        <div style="overflow-x:auto">
            <table>
                <thead><tr><th>Waktu</th><th>Nota</th><th>Metode</th><th class="right">Jumlah</th></tr></thead>
                <tbody>
                    @forelse ($riwayat as $p)
                        @php $batal = ! is_null($p->voided_at); @endphp
                        <tr style="{{ $batal ? 'color:#94a3b8' : '' }}">
                            <td>{{ $p->created_at->timezone($tz)->format('d/m/Y H:i') }}</td>
                            <td>{{ $p->sale?->sale_number }}</td>
                            <td>
                                {{ $p->payment_method === 'TRANSFER' ? 'Transfer' : 'Tunai' }}
                                @if ($p->reference_number)
                                    <div style="font-size:.78rem;color:#64748b">{{ $p->reference_number }}</div>
                                @endif
                                @if ($batal)
                                    <div style="font-size:.78rem;color:#be123c;font-weight:600">Dibatalkan</div>
                                @endif
                            </td>
                            <td class="right" style="{{ $batal ? 'text-decoration:line-through' : '' }}">{{ number_format($p->amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">Belum ada pembayaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection