@extends('layouts.app')

@section('title', 'Gaji ' . $payroll->employee->name)

@section('content')
@include('gaji._nav')
@php
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
    $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
    $e = $payroll->employee;
    $draft = $payroll->status === 'DRAFT';
    $c = $payroll->attendance_counts ?? [];
    $stat = ['DRAFT' => ['Draft', 'warn'], 'FINAL' => ['Diterbitkan', 'up'], 'PAID' => ['Dibayar', 'up'], 'VOID' => ['Dibatalkan', 'down']][$payroll->status];
@endphp

<div class="card" style="max-width:780px">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:.6rem">
        <div>
            <h2 style="margin:0">{{ $e->name }} <span class="chip {{ $stat[1] }}">{{ $stat[0] }}</span></h2>
            <div class="muted">{{ $e->code }} &middot; {{ $e->type === 'RELAWAN' ? 'Relawan' : 'Karyawan' }} &middot; {{ $labelBulan }}
                @if ($payroll->payroll_number) &middot; No. {{ $payroll->payroll_number }} @endif</div>
        </div>
        <a href="{{ route('gaji.slip', $payroll) }}" target="_blank" class="btn" style="margin:0;text-decoration:none">Lihat / Cetak Slip</a>
    </div>

    @if ($draft && $basi)
        <div style="background:#fef3c7;border:1px solid #fde68a;padding:.6rem .9rem;border-radius:8px;margin-top:1rem">
            Presensi bulan ini berubah sejak draft dibuat. Klik <strong>Hitung ulang</strong> sebelum menerbitkan.
        </div>
    @endif
    @if ($payroll->status === 'VOID')
        <div style="background:#ffe4e6;border:1px solid #fecdd3;padding:.6rem .9rem;border-radius:8px;margin-top:1rem">
            Dibatalkan: {{ $payroll->void_reason }}
        </div>
    @endif

    <table style="margin-top:1rem">
        <tbody>
            <tr><td>Kehadiran</td><td class="muted">Hadir {{ $c['hadir'] ?? 0 }} &middot; Setengah {{ $c['setengah'] ?? 0 }} &middot; Izin {{ $c['izin'] ?? 0 }} &middot; Sakit {{ $c['sakit'] ?? 0 }} &middot; Alfa {{ $c['alfa'] ?? 0 }}</td><td></td></tr>
            <tr><td>Gaji harian</td><td class="muted">{{ $fmt($payroll->days_present) }} hari &times; {{ $rp($payroll->daily_rate) }}</td><td class="right">{{ $rp($payroll->base_amount) }}</td></tr>
            <tr><td>Borongan</td><td class="muted">{{ $fmt($payroll->kg_total) }} kg &times; {{ $rp($payroll->per_kg_rate) }}</td><td class="right">{{ $rp($payroll->kg_amount) }}</td></tr>
            @if ((float) $payroll->rendemen_bonus_amount > 0)
            <tr><td>Bonus rendemen</td><td class="muted">{{ $fmt($payroll->rendemen_bonus_kg) }} kg &times; {{ $rp((float) $payroll->rendemen_bonus_kg > 0 ? (float) $payroll->rendemen_bonus_amount / (float) $payroll->rendemen_bonus_kg : 0) }}</td><td class="right">{{ $rp($payroll->rendemen_bonus_amount) }}</td></tr>
            @endif
            <tr><td>Bonus</td><td class="muted">{{ $payroll->bonus_note ?: '-' }}</td><td class="right">{{ $rp($payroll->bonus_amount) }}</td></tr>
            <tr><td><strong>Pendapatan kotor</strong></td><td></td><td class="right"><strong>{{ $rp($payroll->gross_amount) }}</strong></td></tr>
            <tr><td>Potongan lain</td><td class="muted">{{ $payroll->other_deduction_note ?: '-' }}</td><td class="right">&minus; {{ $rp($payroll->other_deduction_amount) }}</td></tr>
            <tr><td>Potongan kasbon</td><td class="muted"></td><td class="right">&minus; {{ $rp($payroll->loan_deduction_amount) }}</td></tr>
            <tr><td><strong>Gaji diterima</strong></td><td></td><td class="right" style="font-size:1.2rem"><strong>{{ $rp($payroll->net_amount) }}</strong></td></tr>
        </tbody>
    </table>
</div>

@if ($draft)
<div class="card" style="max-width:780px">
    <h3 style="margin-top:0">Bonus dan potongan</h3>
    <form method="POST" action="{{ route('gaji.update', $payroll) }}">
        @csrf @method('PUT')
        <div class="fgrid">
            <div>
                <label for="bonus_amount">Bonus / tunjangan (Rp)</label>
                <input type="number" id="bonus_amount" name="bonus_amount" min="0" step="any" value="{{ old('bonus_amount', $num($payroll->bonus_amount)) }}">
            </div>
            <div>
                <label for="bonus_note">Keterangan bonus</label>
                <input type="text" id="bonus_note" name="bonus_note" maxlength="150" value="{{ old('bonus_note', $payroll->bonus_note) }}">
            </div>
            <div>
                <label for="other_deduction_amount">Potongan lain (Rp)</label>
                <input type="number" id="other_deduction_amount" name="other_deduction_amount" min="0" step="any" value="{{ old('other_deduction_amount', $num($payroll->other_deduction_amount)) }}">
            </div>
            <div>
                <label for="other_deduction_note">Keterangan potongan</label>
                <input type="text" id="other_deduction_note" name="other_deduction_note" maxlength="150" value="{{ old('other_deduction_note', $payroll->other_deduction_note) }}">
            </div>
            <div>
                <label for="loan_deduction_amount">Potong kasbon (Rp)</label>
                <input type="number" id="loan_deduction_amount" name="loan_deduction_amount" min="0" max="{{ $maks }}" step="any" value="{{ old('loan_deduction_amount', $num($payroll->loan_deduction_amount)) }}">
                <div class="muted">Saldo kasbon {{ $rp($saldo) }} &middot; maksimal {{ $rp($maks) }}</div>
            </div>
        </div>
        <button type="submit" class="btn">Simpan Draft</button>
    </form>

    <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:1rem;border-top:1px solid var(--line);padding-top:1rem">
        <form method="POST" action="{{ route('gaji.hitung', $payroll) }}">@csrf
            <button type="submit" class="btn" style="margin:0;background:#fff;color:#0f172a;border:1px solid #cbd5e1">Hitung ulang dari presensi</button>
        </form>
        <form method="POST" action="{{ route('gaji.terbitkan', $payroll) }}" onsubmit="return confirm('Terbitkan slip ini? Setelah terbit, data terkunci dan kasbon dipotong.')">@csrf
            <button type="submit" class="btn" style="margin:0">Terbitkan slip</button>
        </form>
        <form method="POST" action="{{ route('gaji.hapus', $payroll) }}" onsubmit="return confirm('Hapus draft ini?')">@csrf @method('DELETE')
            <button type="submit" class="btn" style="margin:0;background:#e11d48">Hapus draft</button>
        </form>
    </div>
</div>
@endif

@if ($payroll->status === 'FINAL')
<div class="card" style="max-width:780px">
    <h3 style="margin-top:0">Pembayaran</h3>
    <form method="POST" action="{{ route('gaji.bayar', $payroll) }}" onsubmit="return confirm('Tandai gaji ini sudah dibayar?')">@csrf
        <button type="submit" class="btn" style="margin:0">Tandai sudah dibayar</button>
    </form>
</div>
@endif

@if (in_array($payroll->status, ['FINAL', 'PAID']))
<div class="card" style="max-width:780px">
    <h3 style="margin-top:0">Batalkan slip</h3>
    <form method="POST" action="{{ route('gaji.batal', $payroll) }}" onsubmit="return confirm('Batalkan slip ini? Kasbon dikembalikan dan presensi terbuka lagi.')">
        @csrf
        <label for="void_reason">Alasan pembatalan</label>
        <input type="text" id="void_reason" name="void_reason" minlength="5" maxlength="255" required>
        <button type="submit" class="btn" style="background:#e11d48">Batalkan slip</button>
    </form>
</div>
@endif
@endsection