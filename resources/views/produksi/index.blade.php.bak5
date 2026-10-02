@extends('layouts.app')

@section('title', 'Produksi')

@section('content')
@include('layouts._errors')

<div class="card">
    <h2 style="margin-top:0">Input hasil produksi</h2>
    <p class="muted">
        Stok ayam hidup saat ini: <strong>{{ number_format($stok['ekor'], 0, ',', '.') }} ekor</strong>
        &middot; {{ number_format($stok['kg'], 1, ',', '.') }} kg.
        Catat ayam yang diserahkan ke pemotongan beserta hasil potong per item (kg).
    </p>

    @if ($bolehTulis)
        <form method="POST" action="{{ route('produksi.store') }}">
            @csrf
            <div class="fgrid">
                <div>
                    <label>Tanggal produksi</label>
                    <input type="date" name="production_date" value="{{ old('production_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                </div>
                <div>
                    <label>Ayam masuk produksi (ekor)</label>
                    <input type="number" name="input_birds" min="1" step="1" value="{{ old('input_birds') }}" required>
                </div>
                <div>
                    <label>Berat ayam hidup (kg)</label>
                    <input type="number" name="input_weight_kg" min="0.01" step="0.01" value="{{ old('input_weight_kg') }}" required>
                </div>
            </div>
            <label>Catatan (opsional)</label>
            <input type="text" name="notes" maxlength="255" value="{{ old('notes') }}">

            <h3 style="margin:1.2rem 0 .2rem">Hasil potong per item</h3>
            <table>
                <thead><tr><th>Produk</th><th>Berat (kg)</th><th>Jumlah potong (opsional)</th><th></th></tr></thead>
                <tbody id="rows"></tbody>
            </table>
            <button type="button" id="add" class="btn" style="background:#475569">+ Tambah baris</button>
            <button type="submit" class="btn">Simpan produksi</button>
        </form>

        <script>
        (function () {
            var produk = @json($produkOpsi);
            var lama = @json(old('items', [[]]));
            var body = document.getElementById('rows');
            var n = 0;

            function baris(v) {
                var i = n++;
                var tr = document.createElement('tr');
                var opsi = '<option value="">- pilih -</option>' + produk.map(function (p) {
                    return '<option value="' + p.id + '"' + (v.product_id === p.id ? ' selected' : '') + '>' + p.name + '</option>';
                }).join('');
                tr.innerHTML =
                    '<td><select name="items[' + i + '][product_id]" required>' + opsi + '</select></td>' +
                    '<td><input type="number" step="0.01" min="0.01" name="items[' + i + '][weight_kg]" value="' + (v.weight_kg || '') + '" required></td>' +
                    '<td><input type="number" step="1" min="0" name="items[' + i + '][pieces]" value="' + (v.pieces || '') + '"></td>' +
                    '<td><button type="button" style="border:0;background:none;color:#e11d48;cursor:pointer;font-size:1.1rem" title="Hapus">&times;</button></td>';
                tr.querySelector('button').onclick = function () {
                    if (body.children.length > 1) tr.remove();
                };
                body.appendChild(tr);
            }

            (lama.length ? lama : [{}]).forEach(baris);
            document.getElementById('add').onclick = function () { baris({}); };
        })();
        </script>
    @else
        <p class="muted">Anda hanya dapat melihat data ini.</p>
    @endif
</div>

<div class="card">
    <h3 style="margin-top:0">Riwayat produksi</h3>
    <div style="overflow-x:auto">
        <table>
            <thead>
                <tr>
                    <th>Tanggal / No.</th><th class="right">Masuk</th><th class="right">Hasil (kg)</th>
                    <th class="right">Rendemen</th><th>Rincian hasil</th><th>Oleh</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($batches as $b)
                    @php
                        $hasil = (float) $b->weighingRecords->sum('weight_kg');
                        $rend  = $b->input_weight_kg > 0 ? $hasil / (float) $b->input_weight_kg * 100 : 0;
                    @endphp
                    <tr>
                        <td>{{ $b->production_date->format('d/m/Y') }}<div class="muted">{{ $b->batch_number }}</div></td>
                        <td class="right">{{ number_format($b->input_birds, 0, ',', '.') }} ekor<div class="muted">{{ number_format($b->input_weight_kg, 2, ',', '.') }} kg</div></td>
                        <td class="right">{{ number_format($hasil, 2, ',', '.') }}</td>
                        <td class="right"><span class="chip {{ $rend < 65 ? 'warn' : 'up' }}">{{ number_format($rend, 1, ',', '.') }}%</span></td>
                        <td>
                            @foreach ($b->weighingRecords as $w)
                                <div>{{ $w->product?->name }}: <strong>{{ number_format($w->weight_kg, 2, ',', '.') }} kg</strong></div>
                            @endforeach
                        </td>
                        <td>{{ $b->createdBy?->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">Belum ada data produksi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection