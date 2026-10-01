@extends('layouts.app')

@section('title', 'Ayam Mati')

@section('content')
@include('layouts._errors')

<div class="card">
    <h2 style="margin-top:0">Ayam mati / afkir</h2>
    <p class="muted">
        Stok ayam hidup saat ini:
        <strong>{{ number_format($stok['ekor'], 0, ',', '.') }} ekor</strong>
        &middot; {{ number_format($stok['kg'], 1, ',', '.') }} kg.
        Total tercatat mati: {{ number_format($total->e, 0, ',', '.') }} ekor
        ({{ number_format($total->k, 1, ',', '.') }} kg).
    </p>

    @if ($bolehTulis)
        <form method="POST" action="{{ route('mortalitas.store') }}">
            @csrf
            <div class="fgrid">
                <div>
                    <label>Tanggal</label>
                    <input type="date" name="record_date" value="{{ old('record_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                </div>
                <div>
                    <label>Jumlah (ekor)</label>
                    <input type="number" name="birds" min="1" step="1" value="{{ old('birds') }}" required>
                </div>
                <div>
                    <label>Perkiraan berat total (kg)</label>
                    <input type="number" name="weight_kg" min="0" step="0.01" value="{{ old('weight_kg') }}" required>
                </div>
            </div>
            <label>Penyebab (mis. Sakit, Mati di jalan, Suhu panas)</label>
            <input type="text" name="cause" maxlength="100" value="{{ old('cause') }}">
            <label>Catatan (opsional)</label>
            <input type="text" name="notes" maxlength="255" value="{{ old('notes') }}">
            <button class="btn" type="submit">Simpan</button>
        </form>
    @else
        <p class="muted">Anda hanya dapat melihat data ini.</p>
    @endif
</div>

<div class="card">
    <h3 style="margin-top:0">Riwayat</h3>
    <div style="overflow-x:auto">
        <table>
            <thead><tr><th>Tanggal</th><th class="right">Ekor</th><th class="right">Berat (kg)</th><th>Penyebab</th><th>Dicatat oleh</th></tr></thead>
            <tbody>
                @forelse ($items as $m)
                    <tr>
                        <td>{{ $m->record_date->format('d/m/Y') }}</td>
                        <td class="right">{{ number_format($m->birds, 0, ',', '.') }}</td>
                        <td class="right">{{ number_format($m->weight_kg, 2, ',', '.') }}</td>
                        <td>{{ $m->cause ?: '-' }}@if ($m->notes)<div class="muted">{{ $m->notes }}</div>@endif</td>
                        <td>{{ $m->createdBy?->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">Belum ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:.8rem">{{ $items->links() }}</div>
</div>
@endsection