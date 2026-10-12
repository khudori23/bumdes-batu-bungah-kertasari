@extends('layouts.app')

@section('title', 'Produksi')

@section('content')
@include('layouts._errors')
@php
    $u = auth()->user();
    $bolehFreezer = $u->isAdmin() || $u->isManager() || $u->isKasir();
@endphp

<div class="card">
    <h2 style="margin-top:0">{{ $bolehTulis ? 'Input hasil produksi' : 'Hasil produksi' }}</h2>
    <p class="muted">
        @if ($lihatAyam && $stok)
            Stok ayam hidup saat ini: <strong>{{ number_format($stok['ekor'], 0, ',', '.') }} ekor</strong>
            &middot; {{ number_format($stok['kg'], 1, ',', '.') }} kg.
        @endif
        @if ($bolehTulis)Isi hasil potong per item (kg) dan pilih freezer penyimpanannya. Boleh diisi beberapa kali dalam sehari.@endif
    </p>

    @if ($bolehTulis)
        @if (count($freezerOpsi) === 0)
            <p class="muted">
                <strong>Belum ada freezer.</strong>
                @if ($bolehFreezer)<a href="{{ route('freezer.index') }}">Tambahkan freezer dulu</a>@else Minta admin menambahkannya @endif
                supaya umur simpan produk bisa dilacak.
            </p>
        @endif

        <form method="POST" action="{{ route('produksi.store') }}">
            @csrf
            <div style="max-width:240px">
                <label>Tanggal produksi</label>
                <input type="date" name="production_date" value="{{ old('production_date', now()->toDateString()) }}" min="{{ \App\Support\Pencatatan::mulai() }}" max="{{ now()->toDateString() }}" required>
            </div>

            <h3 style="margin:1.2rem 0 .2rem">Hasil potong per item</h3>
            <p class="muted" style="margin:0 0 .6rem">Pilih produk, isi berat (kg), lalu pilih freezer.</p>
            <div id="rows"></div>
            <div class="btn-row">
                <button type="button" id="add" class="btn" style="background:#475569">+ Tambah baris</button>
                <button type="submit" class="btn">Simpan produksi</button>
            </div>
        </form>

        @if ($bolehFreezer)
            <div style="margin-top:1rem;padding:.8rem 1rem;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc">
                <a href="{{ route('freezer.index') }}">Kelola freezer &amp; batas simpan &rarr;</a>
            </div>
        @endif

        <script>
        (function () {
            var produk = @json($produkOpsi);
            var freezer = @json($freezerOpsi);
            var lama = @json(old('items', [[]]));
            var body = document.getElementById('rows');
            var n = 0;

            function esc(s) {
                return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            }

            function freezerTerakhir() {
                var s = body.querySelectorAll('select[name$="[storage_location_id]"]');
                return s.length ? s[s.length - 1].value : '';
            }

            function baris(v) {
                var i = n++;
                var d = document.createElement('div');
                d.className = 'ir two';
                d.style.cssText = 'display:flex;flex-wrap:wrap;gap:.5rem;align-items:center';

                var opsi = '<option value="">- pilih produk -</option>' + produk.map(function (p) {
                    return '<option value="' + esc(p.id) + '"' + (v.product_id === p.id ? ' selected' : '') + '>' + esc(p.name) + '</option>';
                }).join('');

                var opsiF = '<option value="">- pilih freezer -</option>' + freezer.map(function (f) {
                    return '<option value="' + esc(f.id) + '"' + (v.storage_location_id === f.id ? ' selected' : '') + '>' + esc(f.name) + '</option>';
                }).join('');

                d.innerHTML =
                    '<select name="items[' + i + '][product_id]" required style="flex:2 1 180px">' + opsi + '</select>' +
                    '<input type="number" inputmode="decimal" step="0.01" min="0.01" placeholder="Berat (kg)" name="items[' + i + '][weight_kg]" value="' + esc(v.weight_kg || '') + '" required style="flex:1 1 120px">' +
                    (freezer.length ? '<select name="items[' + i + '][storage_location_id]" required style="flex:1 1 150px">' + opsiF + '</select>' : '') +
                    '<button type="button" class="ir-x" title="Hapus baris" aria-label="Hapus baris">&times;</button>';
                d.querySelector('button').onclick = function () {
                    if (body.children.length > 1) d.remove();
                };
                body.appendChild(d);
            }

            (lama.length ? lama : [{}]).forEach(baris);
            document.getElementById('add').onclick = function () {
                baris({ storage_location_id: freezerTerakhir() });
            };
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
                <th>Rincian hasil</th><th>Oleh</th><th></th>
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
                            {{ $w->product?->name }}: <strong>{{ number_format($w->weight_kg, 2, ',', '.') }} kg</strong>@if ($w->storageLocation) <small class="muted">({{ $w->storageLocation->name }})</small>@endif
                            @if (auth()->user()->isAdmin() || (auth()->user()->roleName() === \App\Models\User::ROLE_PENGAWAS && (string) $b->created_by_id === (string) auth()->id() && $w->created_at?->isToday()))
                                <form method="POST" action="{{ route('produksi.hapus', $w) }}" onsubmit="return confirm('Hapus timbangan ini? Stok ikut dikoreksi.')" style="display:inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="padding:0 .4rem;font-size:.75rem;background:#e11d48;color:#fff;border:0;border-radius:4px;width:auto">Hapus</button>
                                </form>
                            @endif
                            @if (! $loop->last)<br>@endif
                        @endforeach
                    </span></td>
                    <td data-label="Oleh">{{ $b->createdBy?->name }}</td>
                    <td data-label="Aksi">
                        @if (auth()->user()->isAdmin() || (auth()->user()->roleName() === \App\Models\User::ROLE_PENGAWAS && (string) $b->created_by_id === (string) auth()->id() && $b->created_at?->isToday()))
                            <form method="POST" action="{{ route('produksi.batch.hapus', $b) }}"
                                  onsubmit="return confirm('Hapus batch {{ $b->batch_number }} beserta semua hasil timbangnya? Stok akan ikut dikoreksi dan tidak bisa dikembalikan.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit">Hapus</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">Belum ada data produksi.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection