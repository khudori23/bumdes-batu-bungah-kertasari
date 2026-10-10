@extends('layouts.app')

@section('title', 'Jual Ayam Ecer')

@section('content')
@include('layouts._errors')

<div class="card">
    <h2 style="margin-top:0">Jual ayam ecer (langsung)</h2>
    <p class="muted">
        Isi ekor, berat, harga, dan pembayaran di sini. Pengawas memverifikasinya sesudahnya di menu Riwayat Ayam.
        Stok ayam hidup saat ini: <strong>{{ number_format($sisaStok['ekor'], 0, ',', '.') }} ekor</strong>.
    </p>

    <form method="POST" action="{{ route('kasir.ayamhidup.langsung.store') }}" id="f" autocomplete="off">
        @csrf
        <div class="fgrid">
            <div>
                <label>Tanggal</label>
                <input type="date" name="sale_date" value="{{ old('sale_date', now()->toDateString()) }}" min="{{ \App\Support\Pencatatan::mulai() }}" max="{{ now()->toDateString() }}" required>
            </div>
            <div>
                <label>Nama pelanggan (tulis "Umum" untuk pembeli biasa)</label>
                <input type="text" name="customer_name" id="nama" list="dl-pel" maxlength="100" value="{{ old('customer_name', 'Umum') }}" required>
                <datalist id="dl-pel">
                    @foreach ($pelanggan as $p)
                        <option value="{{ $p['name'] }}"></option>
                    @endforeach
                </datalist>
            </div>
            <div>
                <label>Jumlah (ekor)</label>
                <input type="number" inputmode="numeric" name="birds" id="birds" min="1" step="1" value="{{ old('birds') }}" required>
            </div>
            <div>
                <label>Berat total (kg)</label>
                <input type="number" inputmode="decimal" name="weight_kg" min="0.01" step="0.01" value="{{ old('weight_kg') }}" required>
            </div>
            <div>
                <label>Jenis pelanggan (menentukan harga)</label>
                <select name="customer_type_id" id="tipe">
                    @foreach ($types as $t)
                        <option value="{{ $t->id }}" @selected(old('customer_type_id', $defaultTypeId) == $t->id)>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Pembayaran</label>
                <select name="payment_method" id="metode">
                    <option value="CASH" @selected(old('payment_method', 'CASH') === 'CASH')>Tunai</option>
                    <option value="TRANSFER" @selected(old('payment_method') === 'TRANSFER')>Transfer</option>
                    <option value="HUTANG" @selected(old('payment_method') === 'HUTANG')>Hutang</option>
                </select>
            </div>
            <div id="row-tempo" style="display:none">
                <label>Jatuh tempo (hari)</label>
                <select name="due_days">
                    @foreach ([3, 7, 14, 30] as $h)
                        <option value="{{ $h }}" @selected((int) old('due_days', $defaultTempo) === $h)>{{ $h }} hari</option>
                    @endforeach
                </select>
            </div>
            <div id="row-ref" style="display:none">
                <label>No. referensi transfer (opsional)</label>
                <input type="text" name="reference_number" maxlength="100" value="{{ old('reference_number') }}">
            </div>
            <div>
                <label>Diskon (Rp)</label>
                <input type="number" min="0" step="1" name="discount" id="discount" value="{{ old('discount', 0) }}">
            </div>
        </div>

        <label>Catatan (opsional)</label>
        <input type="text" name="notes" maxlength="255" value="{{ old('notes') }}">

        <p style="margin:.8rem 0 .2rem">Harga per ekor: <strong id="t-harga">-</strong></p>
        <p style="margin:.2rem 0">Total nota: <strong id="t-total">Rp 0</strong></p>
        <p class="muted" id="t-saldo" style="margin:.2rem 0;display:none"></p>
        <p id="t-stok" style="margin:.2rem 0;color:#e11d48;display:none">Jumlah melebihi stok ayam hidup.</p>

        <label>Uang dibayar (Rp)</label>
        <input type="number" inputmode="numeric" min="0" step="1" name="amount_paid" id="amount_paid" value="{{ old('amount_paid') }}">
        <p style="margin:.2rem 0"><button type="button" class="btn" id="pas" style="width:auto">Uang pas</button></p>
        <p style="margin:.2rem 0">Sisa hutang: <strong id="t-sisa">Rp 0</strong> &middot; Kembalian: <strong id="t-kembali">Rp 0</strong></p>

        <button type="submit" class="btn" id="simpan" style="width:100%">Simpan penjualan</button>
    </form>
</div>

<script>
    const HARGA = @json($harga);
    const PEL   = @json($pelanggan);
    const STOK  = {{ (int) $sisaStok['ekor'] }};
    const $ = id => document.getElementById(id);
    const rp = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');
    let total = 0;

    function cari() {
        const n = $('nama').value.trim().toLowerCase();
        return PEL.find(p => p.name.toLowerCase() === n) || null;
    }

    function hitung() {
        const p = cari();
        const saldo = p ? p.saldo : 0;
        const ekor = parseInt($('birds').value) || 0;
        const h = parseFloat(HARGA[$('tipe').value]) || 0;
        const diskon = parseFloat($('discount').value) || 0;
        total = Math.max(h * ekor - diskon, 0);
        const metode = $('metode').value;
        const tagihan = total + saldo;
        const bayar = parseFloat($('amount_paid').value) || 0;

        $('t-harga').textContent = h > 0 ? rp(h) : 'belum diatur';
        $('t-total').textContent = rp(total);
        $('t-sisa').textContent = rp(Math.max(tagihan - bayar, 0));
        $('t-kembali').textContent = rp(Math.max(bayar - tagihan, 0));
        $('t-saldo').style.display = saldo > 0 ? '' : 'none';
        $('t-saldo').textContent = 'Hutang sebelumnya ' + rp(saldo) + ' otomatis digabung di nota ini.';
        $('t-stok').style.display = ekor > STOK ? '' : 'none';
        $('row-tempo').style.display = metode === 'HUTANG' ? '' : 'none';
        $('row-ref').style.display = metode === 'TRANSFER' ? '' : 'none';
    }

    $('nama').addEventListener('input', () => {
        const p = cari();
        if (p && p.type) { $('tipe').value = p.type; }
        hitung();
    });
    ['birds', 'tipe', 'metode', 'discount', 'amount_paid'].forEach(id => $(id).addEventListener('input', hitung));
    $('pas').addEventListener('click', () => { $('amount_paid').value = Math.round(total); hitung(); });
    $('f').addEventListener('submit', () => { $('simpan').disabled = true; });
    window.addEventListener('pageshow', () => { $('simpan').disabled = false; });
    hitung();
</script>
@endsection