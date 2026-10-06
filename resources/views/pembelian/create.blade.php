@extends('layouts.app')

@section('title', 'Catat Pembelian')

@section('content')
<style>
    .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:0 1rem; }
    @media (max-width: 700px) { .form-grid { grid-template-columns:1fr; } }
    .total-box { background:var(--navy); color:#fff; border-radius:10px; padding:.9rem 1rem; margin-top:1.2rem; }
    .total-box .lbl { font-size:.8rem; color:#94a3b8; }
    .total-box .big { font-size:1.8rem; font-weight:700; letter-spacing:-.02em; }
    .total-box .rata { font-size:.85rem; color:#cbd5e1; margin-top:.2rem; }
/* hp-2c */ @media (max-width: 600px) { .total-box .big { font-size: 1.5rem; } #btn-simpan { width: 100%; min-height: 44px; } #form-beli > a { display: block; margin: .9rem 0 0 !important; text-align: center; } }
</style>

<div class="card" style="max-width:640px">
    <h2 style="margin-top:0">Catat Pembelian Ayam Hidup</h2>

    @if ($errors->any())
        <div style="background:#ffe4e6;border:1px solid #fecdd3;padding:.6rem .9rem;border-radius:8px;margin-bottom:1rem">
            @foreach ($errors->all() as $e)
                <div class="err" style="margin:0">{{ $e }}</div>
            @endforeach
        </div>
    @endif

    @if ($suppliers->isEmpty())
        <div style="background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;padding:.6rem .9rem;border-radius:8px">
            Belum ada pemasok aktif. <a href="{{ route('pemasok.create') }}">Tambah pemasok</a> dulu.
        </div>
    @else
        <form method="POST" action="{{ route('pembelian.store') }}" id="form-beli" autocomplete="off">
            @csrf

            <div class="form-grid">
                <div>
                    <label for="supplier_id">Pemasok</label>
                    <select id="supplier_id" name="supplier_id" required autofocus>
                        <option value="">Pilih pemasok...</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}" @selected(old('supplier_id') === $s->id)>{{ $s->name }} ({{ $s->code }})</option>
                        @endforeach
                    </select>
                    <div style="font-size:.8rem;margin-top:.3rem"><a href="{{ route('pemasok.create') }}">+ Pemasok baru</a></div>
                </div>
                <div>
                    <label for="purchase_date">Tanggal pembelian</label>
                    <input type="date" id="purchase_date" name="purchase_date"
                           value="{{ old('purchase_date', now()->toDateString()) }}" min="{{ \App\Support\Pencatatan::mulai() }}" max="{{ now()->toDateString() }}" required>
                </div>
                <div>
                    <label for="total_birds">Jumlah ayam (ekor)</label>
                    <input type="number" id="total_birds" name="total_birds" min="1" step="1" value="{{ old('total_birds') }}" required>
                </div>
                <div>
                    <label for="total_weight_kg">Berat total (kg)</label>
                    <input type="number" id="total_weight_kg" name="total_weight_kg" min="0.01" step="any" value="{{ old('total_weight_kg') }}" required>
                </div>
                <div>
                    <label for="unit_price_per_kg">Harga per kg (Rp)</label>
                    <input type="number" id="unit_price_per_kg" name="unit_price_per_kg" min="1" step="any" value="{{ old('unit_price_per_kg') }}" required>
                </div>
            </div>

            <label for="notes">Catatan (opsional)</label>
            <input type="text" id="notes" name="notes" value="{{ old('notes') }}" maxlength="255">

            <div class="total-box">
                <div class="lbl">TOTAL BIAYA</div>
                <div class="big" id="t-total">Rp 0</div>
                <div class="rata">Rata-rata berat: <span id="t-rata">-</span></div>
            </div>

            <button type="submit" class="btn" id="btn-simpan">Simpan Pembelian</button>
            <a href="{{ route('pembelian.index') }}" style="margin-left:.8rem">Batal</a>
        </form>
    @endif
</div>

<script>
    const $ = id => document.getElementById(id);
    const rupiah = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');

    function hitung() {
        const ekor  = parseFloat($('total_birds').value) || 0;
        const kg    = parseFloat($('total_weight_kg').value) || 0;
        const harga = parseFloat($('unit_price_per_kg').value) || 0;

        $('t-total').textContent = rupiah(kg * harga);
        $('t-rata').textContent  = (ekor > 0 && kg > 0)
            ? (kg / ekor).toFixed(2).replace('.', ',') + ' kg/ekor'
            : '-';
    }

    const form = $('form-beli');
    if (form) {
        ['total_birds', 'total_weight_kg', 'unit_price_per_kg'].forEach(id => $(id).addEventListener('input', hitung));
        // Cegah tersimpan dua kali kalau tombol ditekan berulang
        form.addEventListener('submit', () => { $('btn-simpan').disabled = true; });
        hitung();
    }
</script>
@endsection