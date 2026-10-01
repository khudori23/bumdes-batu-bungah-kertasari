@extends('layouts.app')

@section('title', 'Opname Ayam Hidup')

@section('content')
@include('layouts._errors')

<div class="card">
    <h2 style="margin-top:0">Opname ayam hidup</h2>
    <p class="muted">
        Stok menurut sistem saat ini:
        <strong>{{ number_format($stok['ekor'], 0, ',', '.') }} ekor</strong>
        &middot; {{ number_format($stok['kg'], 2, ',', '.') }} kg.
        Hitung ayam di kandang, lalu isi jumlah fisiknya. Selisih akan menyesuaikan stok sistem.
    </p>

    @if ($bolehTulis)
        <form method="POST" action="{{ route('opnameayam.store') }}">
            @csrf
            <div class="fgrid">
                <div>
                    <label>Tanggal opname</label>
                    <input type="date" name="opname_date" value="{{ old('opname_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                </div>
                <div>
                    <label>Jumlah fisik (ekor)</label>
                    <input type="number" name="actual_birds" min="0" step="1" value="{{ old('actual_birds') }}" required>
                </div>
                <div>
                    <label>Berat fisik total (kg)</label>
                    <input type="number" name="actual_kg" min="0" step="0.01" value="{{ old('actual_kg') }}" required>
                </div>
            </div>
            <label>Alasan selisih (wajib diisi jika ada selisih)</label>
            <input type="text" name="reason" maxlength="255" value="{{ old('reason') }}">
            <button class="btn" type="submit">Simpan opname</button>
        </form>
    @else
        <p class="muted">Anda hanya dapat melihat data ini.</p>
    @endif
</div>

<div class="card">
    <h3 style="margin-top:0">Riwayat opname</h3>
    <div style="overflow-x:auto">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th class="right">Sistem (ekor)</th><th class="right">Fisik (ekor)</th><th class="right">Selisih</th>
                    <th class="right">Selisih (kg)</th><th>Alasan</th><th>Oleh</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $o)
                    <tr>
                        <td>{{ $o->opname_date->format('d/m/Y') }}</td>
                        <td class="right">{{ number_format($o->system_birds, 0, ',', '.') }}</td>
                        <td class="right">{{ number_format($o->actual_birds, 0, ',', '.') }}</td>
                        <td class="right">
                            <span class="chip {{ $o->diff_birds < 0 ? 'down' : ($o->diff_birds > 0 ? 'warn' : 'up') }}">{{ $o->diff_birds > 0 ? '+' : '' }}{{ $o->diff_birds }}</span>
                        </td>
                        <td class="right">{{ $o->diff_kg > 0 ? '+' : '' }}{{ number_format($o->diff_kg, 2, ',', '.') }}</td>
                        <td>{{ $o->reason ?: '-' }}</td>
                        <td>{{ $o->createdBy?->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">Belum ada opname.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:.8rem">{{ $items->links() }}</div>
</div>
@endsection