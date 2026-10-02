@extends('layouts.app')

@section('title', 'Produksi')

@section('content')
@include('layouts._errors')

<div class="card">
    <h2 style="margin-top:0">Input hasil produksi</h2>
    <p class="muted">
        @if ($lihatAyam && $stok)
            Stok ayam hidup saat ini: <strong>{{ number_format($stok['ekor'], 0, ',', '.') }} ekor</strong>
            &middot; {{ number_format($stok['kg'], 1, ',', '.') }} kg.
        @endif
        Isi hasil potong per item (kg). Boleh diisi beberapa kali dalam sehari.
    </p>

    @if ($bolehTulis)
        <form method="POST" action="{{ route('produksi.store') }}">
            @csrf
            <div style="max-width:240px">
                <label>Tanggal produksi</label>
                <input type="date" name="production_date" value="{{ old('production_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
            </div>

            <h3 style="margin:1.2rem 0 .2rem">Hasil potong per item</h3>
            <p class="muted" style="margin:0 0 .6rem">Pilih produk dan isi berat (kg).</p>
            <div id="rows"></div>
            <div class="btn-row">
                <button type="button" id="add" class="btn" style="background:#475569">+ Tambah baris</button>
                <button type="submit" class="btn">Simpan produksi</button>
            </div>
        </form>

        <script>
        (function () {
            var produk = @json($produkOpsi);
            var lama = @json(old('items', [[]]));
            var body = document.getElementById('rows');
            var n = 0;

            function baris(v) {
                var i = n++;
                var d = document.createElement('div');
                d.className = 'ir two';
                var opsi = '<option value="">- pilih produk -</option>' + produk.map(function (p) {
                    return '<option value="' + p.id + '"' + (v.product_id === p.id ? ' selected' : '') + '>' + p.name + '</option>';
                }).join('');
                d.innerHTML =
                    '<select name="items[' + i + '][product_id]" required>' + opsi + '</select>' +
                    '<input type="number" inputmode="decimal" step="0.01" min="0.01" placeholder="Berat (kg)" name="items[' + i + '][weight_kg]" value="' + (v.weight_kg || '') + '" required>' +
                    '<button type="button" class="ir-x" title="Hapus baris" aria-label="Hapus baris">&times;</button>';
                d.querySelector('button').onclick = function () {
                    if (body.children.length > 1) d.remove();
                };
                body.appendChild(d);
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
    <table class="rtable">
        <thead>
            <tr>
                <th>Tanggal / No.</th>
                @if ($lihatAyam)<th class="right">Masuk</th>@endif
                <th class="right">Hasil (kg)</th>
                @if ($lihatAyam)<th class="right">Rendemen</th>@endif
                <th>Rincian hasil</th><th>Oleh</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($batches as $b)
                @php
                    $hasil = (float) $b->weighingRecords->sum('weight_kg');
                    $rend  = $b->input_weight_kg > 0 ? $hasil / (float) $b->input_weight_kg * 100 : null;
                @endphp
                <tr>
                    <td data-label="Tanggal"><span>{{ $b->production_date->format('d/m/Y') }}<br><small class="muted">{{ $b->batch_number }}</small></span></td>
                    @if ($lihatAyam)
                        <td class="right" data-label="Masuk">
                            @if ($b->input_birds > 0)<span>{{ number_format($b->input_birds, 0, ',', '.') }} ekor<br><small class="muted">{{ number_format($b->input_weight_kg, 2, ',', '.') }} kg</small></span>@else - @endif
                        </td>
                    @endif
                    <td class="right" data-label="Hasil (kg)"><strong>{{ number_format($hasil, 2, ',', '.') }}</strong></td>
                    @if ($lihatAyam)
                        <td class="right" data-label="Rendemen">
                            @if ($rend !== null)<span class="chip {{ $rend < 65 || $rend > 100 ? 'warn' : 'up' }}">{{ number_format($rend, 1, ',', '.') }}%</span>@else - @endif
                        </td>
                    @endif
                    <td data-label="Rincian"><span>
                        @foreach ($b->weighingRecords as $w)
                            {{ $w->product?->name }}: <strong>{{ number_format($w->weight_kg, 2, ',', '.') }} kg</strong>@if (! $loop->last)<br>@endif
                        @endforeach
                    </span></td>
                    <td data-label="Oleh">{{ $b->createdBy?->name }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Belum ada data produksi.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection