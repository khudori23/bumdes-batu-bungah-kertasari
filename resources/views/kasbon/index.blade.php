@extends('layouts.app')

@section('title', 'Kasbon')

@section('content')
@include('gaji._nav')
@php $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); @endphp

<div class="card">
    <h2 style="margin-top:0">Catat Kasbon</h2>
    <form method="POST" action="{{ route('kasbon.store') }}">
        @csrf
        <div class="fgrid">
            <div>
                <label for="employee_id">Karyawan / relawan</label>
                <select id="employee_id" name="employee_id" required>
                    <option value="">— pilih —</option>
                    @foreach ($karyawan as $x)
                        <option value="{{ $x->id }}" @selected(old('employee_id') === $x->id)>{{ $x->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="loan_date">Tanggal</label>
                <input type="date" id="loan_date" name="loan_date" value="{{ old('loan_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
            </div>
            <div>
                <label for="amount">Jumlah (Rp)</label>
                <input type="number" id="amount" name="amount" value="{{ old('amount') }}" min="1" step="any" required>
            </div>
        </div>
        <label for="notes">Keterangan (opsional)</label>
        <input type="text" id="notes" name="notes" value="{{ old('notes') }}" maxlength="255">
        <button type="submit" class="btn">Simpan Kasbon</button>
    </form>
</div>

<div class="card">
    <h3 style="margin-top:0">Saldo kasbon berjalan</h3>
    @php $punya = $karyawan->filter(fn ($x) => ((float) $x->kasbon - (float) $x->cicilan) > 0.004); @endphp
    @if ($punya->isEmpty())
        <div class="muted">Tidak ada kasbon yang belum lunas.</div>
    @else
        <div style="display:flex;flex-wrap:wrap;gap:.4rem">
            @foreach ($punya as $x)
                <span class="chip warn">{{ $x->name }}: {{ $rp((float) $x->kasbon - (float) $x->cicilan) }}</span>
            @endforeach
        </div>
    @endif
    <div class="muted" style="margin-top:.6rem">Kasbon dipotong lewat draft gaji. Potongan baru tercatat saat slip diterbitkan.</div>
</div>

<div class="card">
    <h3 style="margin-top:0">Riwayat kasbon (100 terakhir)</h3>
    <div style="overflow-x:auto">
    <table>
        <thead><tr><th>Tanggal</th><th>Nama</th><th class="right">Jumlah</th><th>Keterangan</th><th></th></tr></thead>
        <tbody>
            @forelse ($riwayat as $l)
                <tr>
                    <td>{{ $l->loan_date->format('d/m/Y') }}</td>
                    <td>{{ $l->employee?->name }}</td>
                    <td class="right">{{ $rp($l->amount) }}</td>
                    <td>{{ $l->notes ?: '-' }}</td>
                    <td>
                        <form method="POST" action="{{ route('kasbon.destroy', $l) }}" onsubmit="return confirm('Hapus catatan kasbon ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" style="background:none;border:0;color:#e11d48;cursor:pointer">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Belum ada kasbon.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection