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
                    <input type="date" name="opname_date" value="{{ old('opname_date', now()->toDateString()) }}" min="{{ \App\Support\Pencatatan::mulai() }}" max="{{ now()->toDateString() }}" required>
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
<div class="card" id="ayam-mati">
    <h2 style="margin-top:0">Ayam mati / afkir</h2>
    <p class="muted">
        Total tercatat mati: {{ number_format($totalMati->e, 0, ',', '.') }} ekor
        ({{ number_format($totalMati->k, 1, ',', '.') }} kg). Pencatatan ayam mati mengurangi stok ayam hidup.
    </p>

    @if ($bolehTulis)
        <form method="POST" action="{{ route('mortalitas.store') }}">
            @csrf
            <div class="fgrid">
                <div>
                    <label>Tanggal</label>
                    <input type="date" name="record_date" value="{{ old('record_date', now()->toDateString()) }}" min="{{ \App\Support\Pencatatan::mulai() }}" max="{{ now()->toDateString() }}" required>
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
            <button class="btn" type="submit">Simpan ayam mati</button>
        </form>
    @endif
</div>

<div class="card">
    <h3 style="margin-top:0">Riwayat ayam mati</h3>
    <div style="overflow-x:auto">
        <table>
            <thead><tr><th>Tanggal</th><th class="right">Ekor</th><th class="right">Berat (kg)</th><th>Penyebab</th><th>Dicatat oleh</th></tr></thead>
            <tbody>
                @forelse ($mati as $m)
                    <tr>
                        <td>{{ $m->record_date->format('d/m/Y') }}</td>
                        <td class="right">{{ number_format($m->birds, 0, ',', '.') }}</td>
                        <td class="right">{{ number_format($m->weight_kg, 2, ',', '.') }}</td>
                        <td>{{ $m->cause ?: '-' }}@if ($m->notes)<div class="muted">{{ $m->notes }}</div>@endif</td>
                        <td>{{ $m->createdBy?->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">Belum ada data ayam mati.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:.8rem">{{ $mati->links() }}</div>
</div>
@endsection