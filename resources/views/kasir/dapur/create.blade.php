@extends('layouts.app')

@section('title', 'Pesanan Dapur Baru')

@section('content')
@php
    $baris = old('lines', [['kitchen_name' => '', 'product_id' => '', 'quantity_kg' => '']]);
@endphp

<style>
    .dp-grid { display: grid; gap: .8rem; grid-template-columns: 1fr; }
    @media (min-width: 640px) { .dp-grid { grid-template-columns: 1fr 1fr; } }
    .dp-grid label { display: block; font-size: .85rem; margin-bottom: .2rem; }
    .dp-grid input, .dp-grid select, .baris input, .baris select { width: 100%; }
    .baris { display: grid; gap: .5rem; grid-template-columns: 1fr; padding: .6rem 0; border-bottom: 1px dashed #cbd5e1; }
    @media (min-width: 640px) { .baris { grid-template-columns: 1.2fr 1.2fr .8fr auto; align-items: end; } }
    .baris label { display: block; font-size: .8rem; color: #64748b; margin-bottom: .15rem; }
    .dp-err { background: #fff1f2; border: 1px solid #e11d48; color: #be123c; padding: .6rem; margin-bottom: .8rem; }
</style>

<div class="card" style="max-width:820px">
    <h2 style="margin-top:0">Pesanan Dapur Baru</h2>

    @if ($errors->any())
        <div class="dp-err">
            @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('kasir.dapur.store') }}">
        @csrf

        <div class="dp-grid">
            <div>
                <label for="order_date">Tanggal</label>
                <input type="date" id="order_date" name="order_date" value="{{ old('order_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
            </div>
            <div>
                <label for="customer_id">Ditagih ke</label>
                <select id="customer_id" name="customer_id" required>
                    <option value="">Pilih pelanggan...</option>
                    @foreach ($customers as $c)
                        <option value="{{ $c->id }}" @selected(old('customer_id') == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="live_weight_kg">Bobot ayam hidup yang dipakai (kg)</label>
                <input type="number" step="0.01" min="0.01" inputmode="decimal" id="live_weight_kg" name="live_weight_kg" value="{{ old('live_weight_kg', $saranBerat) }}" required>
                @if ($saranBerat)<small style="color:#64748b">Saran: bobot ayam masuk produksi hari ini. Ubah kalau hari ini ada ayam untuk umum.</small>@endif
            </div>
            <div>
                <label for="price_per_kg">Harga per kg (Rp)</label>
                <input type="number" step="1" min="0" inputmode="numeric" id="price_per_kg" name="price_per_kg" value="{{ old('price_per_kg', $saranHarga !== null ? (int) $saranHarga : '') }}" required>
                @if ($saranHarga !== null)<small style="color:#64748b">Saran: harga pesanan dapur terakhir.</small>@endif
            </div>
        </div>

        <h3 style="margin:1.2rem 0 .2rem">Pesanan per dapur</h3>
        <div id="baris">
            @foreach ($baris as $i => $b)
                <div class="baris">
                    <div>
                        <label>Dapur</label>
                        <input type="text" name="lines[{{ $i }}][kitchen_name]" value="{{ $b['kitchen_name'] ?? '' }}" maxlength="100" placeholder="mis. Dapur A" required>
                    </div>
                    <div>
                        <label>Produk</label>
                        <select name="lines[{{ $i }}][product_id]" required>
                            <option value="">Pilih produk...</option>
                            @foreach ($produk as $p)
                                <option value="{{ $p->id }}" @selected(($b['product_id'] ?? '') == $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Jumlah (kg)</label>
                        <input type="number" step="0.01" min="0.01" inputmode="decimal" name="lines[{{ $i }}][quantity_kg]" value="{{ $b['quantity_kg'] ?? '' }}" required>
                    </div>
                    <button type="button" class="btn" data-hapus style="margin:0;background:#fff;color:#e11d48;border:1px solid #cbd5e1">Hapus</button>
                </div>
            @endforeach
        </div>

        <template id="tplBaris">
            <div>
                <label>Dapur</label>
                <input type="text" name="lines[__I__][kitchen_name]" maxlength="100" placeholder="mis. Dapur B" required>
            </div>
            <div>
                <label>Produk</label>
                <select name="lines[__I__][product_id]" required>
                    <option value="">Pilih produk...</option>
                    @foreach ($produk as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Jumlah (kg)</label>
                <input type="number" step="0.01" min="0.01" inputmode="decimal" name="lines[__I__][quantity_kg]" required>
            </div>
            <button type="button" class="btn" data-hapus style="margin:0;background:#fff;color:#e11d48;border:1px solid #cbd5e1">Hapus</button>
        </template>

        <button type="button" id="tambah" class="btn" style="margin:.8rem 0 0;background:#fff;color:#0f172a;border:1px solid #cbd5e1">+ Tambah dapur</button>

        <div style="margin-top:1rem">
            <label for="notes" style="display:block;font-size:.85rem;margin-bottom:.2rem">Catatan (opsional)</label>
            <input type="text" id="notes" name="notes" maxlength="255" value="{{ old('notes') }}" style="width:100%">
        </div>

        <div style="margin-top:1rem;padding:.8rem;background:#f1f5f9;display:flex;justify-content:space-between;font-weight:700">
            <span>Total tagihan</span><span id="totalTagihan">Rp 0</span>
        </div>

        <div style="margin-top:1rem;display:flex;gap:.6rem">
            <button type="submit" class="btn" style="margin:0">Simpan pesanan</button>
            <a class="btn" style="margin:0;background:#fff;color:#0f172a;border:1px solid #cbd5e1;text-decoration:none" href="{{ route('kasir.dapur.index') }}">Batal</a>
        </div>
    </form>
</div>

<script>
(function () {
    var wrap = document.getElementById('baris');
    var tpl = document.getElementById('tplBaris').innerHTML;
    var n = {{ count($baris) }};
    var berat = document.getElementById('live_weight_kg');
    var harga = document.getElementById('price_per_kg');
    var out = document.getElementById('totalTagihan');

    function hitung() {
        var t = Math.round((parseFloat(berat.value) || 0) * (parseFloat(harga.value) || 0));
        out.textContent = 'Rp ' + t.toLocaleString('id-ID');
    }

    document.getElementById('tambah').addEventListener('click', function () {
        var d = document.createElement('div');
        d.className = 'baris';
        d.innerHTML = tpl.replace(/__I__/g, n++);
        wrap.appendChild(d);
    });

    wrap.addEventListener('click', function (e) {
        if (e.target.hasAttribute('data-hapus') && wrap.querySelectorAll('.baris').length > 1) {
            e.target.closest('.baris').remove();
        }
    });

    berat.addEventListener('input', hitung);
    harga.addEventListener('input', hitung);
    hitung();
})();
</script>
@endsection