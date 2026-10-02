@extends('layouts.app')

@section('title', 'Kasir')

@section('content')
<link rel="stylesheet" href="{{ asset('css/kasir.css') }}?v={{ @filemtime(public_path('css/kasir.css')) }}">

<div class="kasir">
@if ($errors->any())
    <div class="galat">
        @foreach ($errors->all() as $e)
            <div class="err">{{ $e }}</div>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('kasir.store') }}" id="form-kasir" autocomplete="off">
    @csrf
    <input type="hidden" name="customer_type_id" id="customer_type_id" value="{{ old('customer_type_id', $defaultTypeId) }}">
    <input type="hidden" name="payment_method" id="payment_method" value="{{ old('payment_method', 'CASH') }}">
    <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id') }}">
    <input type="hidden" name="due_days" id="due_days" value="{{ old('due_days', $defaultTempo) }}">

    <div class="steps">
        <button type="button" class="stp" data-s="1">1 Pelanggan</button>
        <button type="button" class="stp" data-s="2">2 Barang</button>
        <button type="button" class="stp" data-s="3">3 Bayar</button>
    </div>

    <section class="langkah card" id="s1">
        <div id="slot1">
            <div id="blok-pel">
                <div class="lbl2">Nama pembeli</div>
                <div style="position:relative">
                    <input type="text" name="customer_name" id="pelanggan" value="{{ old('customer_name') }}" placeholder="Umum (kosongkan untuk pembeli biasa)">
                    <div class="saran" id="saran" style="display:none"></div>
                </div>
                @if (auth()->user()->canWrite('kasir'))
                    <div class="muted" style="margin-top:.3rem"><a href="{{ route('pelanggan.index') }}" target="_blank" rel="noopener">+ Pelanggan baru</a> &middot; nama baru otomatis jadi pelanggan saat nota disimpan.</div>
                @endif
                <div class="hutang-info" id="info-hutang" style="display:none"></div>
            </div>
        </div>
        <div class="lbl2">Jenis pelanggan</div>
        <div class="chips" id="types">
            @foreach ($types as $t)
                <button type="button" class="pill" data-id="{{ $t->id }}">{{ $t->name }}</button>
            @endforeach
        </div>
    </section>

    <section class="langkah card" id="s2">
        <input type="text" id="cari" placeholder="Cari barang&hellip;">
        <div id="daftar"></div>
        <div class="kosong" id="kosong" style="display:none">Tidak ada barang yang cocok.</div>
        <div class="hint"><kbd>F2</kbd> cari &nbsp; <kbd>Enter</kbd> baris berikutnya &nbsp; <kbd>F9</kbd> simpan</div>
    </section>

    <section class="langkah card" id="s3">
        <div class="total-box"><div class="lbl">TOTAL BELANJA</div><div class="big" id="t-total">Rp 0</div></div>
        <div class="row2"><span>Subtotal</span><strong id="t-subtotal">Rp 0</strong></div>
        <div class="row2" id="row-lama" style="display:none"><span>Hutang sebelumnya</span><strong id="t-lama" style="color:#c2410c">Rp 0</strong></div>
        <div class="row2" id="row-tagihan" style="display:none"><span>Total tagihan</span><strong id="t-tagihan">Rp 0</strong></div>

        <button type="button" class="tautan" id="btn-diskon">Beri diskon</button>
        <div class="row2" id="wrap-diskon" style="display:none">
            <span>Diskon (Rp)</span>
            <input type="number" min="0" step="1" name="discount" id="discount" value="{{ old('discount', 0) }}" style="width:130px;text-align:right">
        </div>

        <div class="chips metode">
            <button type="button" class="pill" data-method="CASH">Tunai</button>
            <button type="button" class="pill" data-method="TRANSFER">Transfer</button>
            <button type="button" class="pill hutang" data-method="HUTANG">Hutang</button>
        </div>
        <div id="ref-wrap" style="display:none;margin-top:.5rem">
            <input type="text" name="reference_number" value="{{ old('reference_number') }}" placeholder="No. referensi transfer (opsional)">
        </div>
        <div class="tempo-box" id="row-tempo" style="display:none">
            <div>Jatuh tempo: <strong id="t-tempo"></strong></div>
            <div class="chips" style="margin-top:.4rem" id="tempo-chips">
                <button type="button" class="pill kecil" data-hari="3">3 hari</button>
                <button type="button" class="pill kecil" data-hari="7">7 hari</button>
                <button type="button" class="pill kecil" data-hari="14">14 hari</button>
                <button type="button" class="pill kecil" data-hari="30">30 hari</button>
            </div>
        </div>

        <div id="slot3"></div>

        <label for="amount_paid">Uang dibayar (Rp) <kbd>F4</kbd></label>
        <input type="number" inputmode="numeric" min="0" step="1" name="amount_paid" id="amount_paid" value="{{ old('amount_paid') }}" style="font-size:1.3rem;padding:.6rem;text-align:right">
        <div class="quick" id="quick"></div>

        <div class="row2"><span>Sisa hutang</span><strong id="t-sisa">Rp 0</strong></div>
        <div class="row2"><span>Kembalian</span><strong class="kembali" id="t-kembali">Rp 0</strong></div>
    </section>

    <div class="mbar">
        <button type="button" class="btn-mundur" id="btn-mundur" aria-label="Kembali" style="display:none">&lsaquo;</button>
        <div class="ringkas"><small id="m-item">0 item</small><b id="m-total">Rp 0</b></div>
        <button type="button" class="btn-aksi" id="btn-aksi">Lanjut</button>
    </div>
</form>
</div>

<script>
    const PRICES    = @json($prices);
    const PRODUCTS  = @json($products);
    const CUSTOMERS = @json($customers);
    const OLD       = @json(old('items', []));

    const $ = id => document.getElementById(id);
    const form = $('form-kasir'), daftar = $('daftar');
    const sentuh = window.matchMedia('(pointer: coarse)').matches;
    let langkah = 1, belanja = 0, saldoLama = 0, telatLama = 0, paidManual = false;

    const rupiah = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');
    const tersedia = () => PRICES[$('customer_type_id').value] || {};
    const norm = s => (s || '').trim().toLowerCase();
    const adaPelanggan = () => $('customer_id').value !== '' ||
        (norm($('pelanggan').value) !== '' && norm($('pelanggan').value) !== 'umum');
    const baris = () => [...daftar.querySelectorAll('.rw')];
    const qtyOf = r => parseFloat(r.querySelector('.qty').value) || 0;
    const terisi = () => baris().filter(r => r.dataset.ada === '1' && qtyOf(r) > 0);
    const terlihat = () => baris().filter(r => r.style.display !== 'none');

    /* ---------- Langkah ---------- */
    function updateAksi() {
        $('btn-aksi').textContent = ['', 'Lanjut', 'Lanjut ke bayar', 'Bayar & cetak nota'][langkah];
        $('btn-aksi').disabled = langkah === 2 && terisi().length === 0;
        $('btn-mundur').style.display = langkah > 1 ? '' : 'none';
    }

    function ke(n) {
        if (n === 3 && !terisi().length) { alert('Isi berat minimal satu barang dulu.'); n = 2; }
        langkah = n;
        document.querySelectorAll('.langkah').forEach(s => s.classList.toggle('on', s.id === 's' + n));
        document.querySelectorAll('.stp').forEach(b => {
            const k = Number(b.dataset.s);
            b.classList.toggle('on', k === n);
            b.classList.toggle('sudah', k < n);
        });
        updateAksi();
        window.scrollTo({ top: 0 });
        if (n === 2 && !sentuh) $('cari').focus();
    }

    document.querySelectorAll('.stp').forEach(b => b.addEventListener('click', () => ke(Number(b.dataset.s))));
    $('btn-mundur').addEventListener('click', () => ke(langkah - 1));
    $('btn-aksi').addEventListener('click', () => { if (langkah < 3) ke(langkah + 1); else form.requestSubmit(); });
    $('btn-diskon').addEventListener('click', () => { $('wrap-diskon').style.display = 'flex'; $('btn-diskon').style.display = 'none'; $('discount').focus(); $('discount').select(); });

    /* ---------- Daftar barang (barang dan kg dalam satu baris) ---------- */
    PRODUCTS.forEach((p, i) => {
        const r = document.createElement('div');
        r.className = 'rw';
        r.dataset.pid = p.id;
        r.innerHTML = `
            <div class="nm"><b>${p.name}</b><span class="hg"></span></div>
            <input type="hidden" name="items[${i}][product_id]" value="${p.id}">
            <input type="number" inputmode="decimal" enterkeyhint="next" step="0.01" min="0.01" name="items[${i}][quantity]" class="qty" placeholder="kg">
            <div class="sub"></div>`;
        daftar.appendChild(r);
    });

    function renderDaftar() {
        const q = $('cari').value.trim().toLowerCase(), av = tersedia();
        let n = 0;
        PRODUCTS.forEach((p, i) => {
            const r = daftar.children[i], h = av[p.id];
            r.dataset.ada = h !== undefined ? '1' : '0';
            if (h === undefined) { r.querySelector('.qty').value = ''; r.style.display = 'none'; return; }
            r.querySelector('.hg').textContent = rupiah(h) + '/kg';
            const cocok = !q || p.name.toLowerCase().includes(q) || p.code.toLowerCase().includes(q);
            r.style.display = cocok ? '' : 'none';
            if (cocok) n++;
        });
        $('kosong').style.display = n ? 'none' : 'block';
    }

    daftar.addEventListener('input', hitung);
    daftar.addEventListener('focusin', e => {
        if (!e.target.classList.contains('qty')) return;
        const r = e.target.closest('.rw');
        e.target.select();
        setTimeout(() => r.scrollIntoView({ block: 'center', behavior: 'smooth' }), sentuh ? 300 : 0);
    });
    $('cari').addEventListener('input', renderDaftar);

    function renderQuick() {
        const b = Math.round(belanja);
        if (b <= 0) { $('quick').innerHTML = ''; return; }
        const v = [b];
        [10000, 50000, 100000].forEach(u => { const x = Math.ceil(b / u) * u; if (x > b && !v.includes(x)) v.push(x); });
        let h = v.slice(0, 4).map((x, i) => `<button type="button" data-uang="${x}">${i ? rupiah(x) : 'Uang pas'}</button>`).join('');
        if (saldoLama > 0) h += `<button type="button" data-uang="${Math.round(belanja + saldoLama)}">Lunasi semua</button>`;
        $('quick').innerHTML = h;
    }

    function hitung() {
        const av = tersedia();
        let subtotal = 0, n = 0;
        baris().forEach(r => {
            const h = av[r.dataset.pid], q = qtyOf(r), ok = h !== undefined && q > 0;
            r.classList.toggle('on', ok);
            r.querySelector('.sub').textContent = ok ? rupiah(h * q) : '';
            if (ok) { subtotal += h * q; n++; }
        });

        const diskon = parseFloat($('discount').value) || 0;
        belanja = Math.max(subtotal - diskon, 0);

        if ($('payment_method').value === 'TRANSFER' && !paidManual) {
            $('amount_paid').value = belanja > 0 ? Math.round(belanja) : '';
        }

        const tagihan = belanja + saldoLama;
        const bayar = parseFloat($('amount_paid').value) || 0;
        const sisa = Math.max(tagihan - bayar, 0);

        $('t-subtotal').textContent = rupiah(subtotal);
        $('t-total').textContent = rupiah(belanja);
        $('m-total').textContent = rupiah(belanja);
        $('m-item').textContent = n + ' item';
        $('t-lama').textContent = rupiah(saldoLama);
        $('t-tagihan').textContent = rupiah(tagihan);
        $('row-lama').style.display = saldoLama > 0 ? 'flex' : 'none';
        $('row-tagihan').style.display = saldoLama > 0 ? 'flex' : 'none';
        $('t-sisa').textContent = rupiah(sisa);
        $('t-sisa').classList.toggle('merah', sisa > 0.5);
        $('t-kembali').textContent = rupiah(Math.max(bayar - tagihan, 0));
        renderQuick();
        updateAksi();

        const info = $('info-hutang');
        if (saldoLama > 0) {
            info.style.display = 'block';
            info.classList.toggle('telat', telatLama > 0);
            info.textContent = telatLama > 0
                ? 'TERLAMBAT ' + telatLama + ' hari \u2014 hutang sebelumnya ' + rupiah(saldoLama) + ' digabung di nota ini.'
                : 'Hutang sebelumnya ' + rupiah(saldoLama) + ' \u2014 otomatis digabung di nota ini.';
        } else {
            info.style.display = 'none';
        }
    }

    /* ---------- Jenis pelanggan & metode bayar ---------- */
    function setType(id) {
        $('customer_type_id').value = id;
        document.querySelectorAll('#types .pill').forEach(c => c.classList.toggle('on', c.dataset.id === id));
        renderDaftar();
        hitung();
    }

    function setMethod(m) {
        $('payment_method').value = m;
        paidManual = false;
        document.querySelectorAll('[data-method]').forEach(c => c.classList.toggle('on', c.dataset.method === m));
        $('ref-wrap').style.display = m === 'TRANSFER' ? 'block' : 'none';
        $('row-tempo').style.display = m === 'HUTANG' ? 'block' : 'none';
        // Hutang wajib atas nama: kolom pelanggan dipindah ke layar bayar
        $(m === 'HUTANG' ? 'slot3' : 'slot1').appendChild($('blok-pel'));
        hitung();
    }

    function setTempo(hari) {
        $('due_days').value = hari;
        document.querySelectorAll('#tempo-chips .pill').forEach(c => c.classList.toggle('on', c.dataset.hari === String(hari)));
        const d = new Date();
        d.setDate(d.getDate() + Number(hari));
        $('t-tempo').textContent = d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) + ' (' + hari + ' hari)';
    }

    $('tempo-chips').addEventListener('click', e => { const c = e.target.closest('.pill'); if (c) setTempo(c.dataset.hari); });
    $('types').addEventListener('click', e => { const c = e.target.closest('.pill'); if (c) setType(c.dataset.id); });
    document.querySelectorAll('[data-method]').forEach(c => c.addEventListener('click', () => setMethod(c.dataset.method)));

    /* ---------- Pelanggan ---------- */
    function cocok(nama) { return CUSTOMERS.find(c => norm(c.name) === norm(nama)); }
    function sembunyikanSaran() { $('saran').style.display = 'none'; }

    function renderSaran() {
        const q = norm($('pelanggan').value);
        if (!q) { sembunyikanSaran(); return []; }
        const list = CUSTOMERS.filter(c => norm(c.name).includes(q)).slice(0, 6);
        if (!list.length) { sembunyikanSaran(); return []; }
        $('saran').innerHTML = list.map((c, n) => `
            <button type="button" class="${n === 0 ? 'aktif' : ''}" data-id="${c.id}">
                <span>${c.name}</span>
                ${c.saldo > 0 ? `<span class="bon">hutang ${rupiah(c.saldo)}${c.telat > 0 ? ' \u00b7 terlambat ' + c.telat + ' hari' : ''}</span>` : ''}
            </button>`).join('');
        $('saran').style.display = 'block';
        return list;
    }

    function pilihPelanggan(c) {
        $('pelanggan').value = c.name;
        $('customer_id').value = c.id;
        saldoLama = c.saldo;
        telatLama = c.telat;
        sembunyikanSaran();
        if ($('customer_type_id').value !== c.type && document.querySelector(`#types .pill[data-id="${c.type}"]`)) setType(c.type);
        hitung();
    }

    $('pelanggan').addEventListener('input', () => {
        const c = cocok($('pelanggan').value);
        $('customer_id').value = c ? c.id : '';
        saldoLama = c ? c.saldo : 0;
        telatLama = c ? c.telat : 0;
        renderSaran();
        hitung();
    });
    $('saran').addEventListener('mousedown', e => {
        const b = e.target.closest('button');
        if (!b) return;
        e.preventDefault();
        pilihPelanggan(CUSTOMERS.find(c => c.id === b.dataset.id));
    });
    document.addEventListener('click', e => { if (!e.target.closest('#saran') && e.target.id !== 'pelanggan') sembunyikanSaran(); });

    /* ---------- Pembayaran ---------- */
    $('discount').addEventListener('input', hitung);
    $('amount_paid').addEventListener('input', () => { paidManual = true; hitung(); });
    $('quick').addEventListener('click', e => {
        const b = e.target.closest('button');
        if (!b) return;
        paidManual = true;
        $('amount_paid').value = b.dataset.uang;
        hitung();
    });

    /* ---------- Keyboard ---------- */
    form.addEventListener('keydown', e => {
        if (e.key !== 'Enter' || e.target.tagName === 'BUTTON') return;
        e.preventDefault();
        const id = e.target.id;
        if (e.target.classList.contains('qty')) {
            const v = terlihat(), i = v.indexOf(e.target.closest('.rw')), nx = v[i + 1];
            if (nx) nx.querySelector('.qty').focus(); else e.target.blur();
        } else if (id === 'cari') {
            const v = terlihat();
            if (v.length) v[0].querySelector('.qty').focus();
        } else if (id === 'pelanggan') {
            const list = renderSaran();
            if (list.length && $('saran').style.display !== 'none') pilihPelanggan(list[0]);
            if (langkah === 1) ke(2); else $('amount_paid').focus();
        } else if (id === 'amount_paid') {
            form.requestSubmit();
        }
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'F2') { e.preventDefault(); ke(2); $('cari').focus(); $('cari').select(); }
        else if (e.key === 'F3') { e.preventDefault(); ke(1); $('pelanggan').focus(); $('pelanggan').select(); }
        else if (e.key === 'F4') { e.preventDefault(); ke(3); $('amount_paid').focus(); $('amount_paid').select(); }
        else if (e.key === 'F9' || (e.ctrlKey && e.key === 'Enter')) { e.preventDefault(); form.requestSubmit(); }
        else if (e.key === 'Escape') { $('cari').value = ''; renderDaftar(); sembunyikanSaran(); }
    });

    /* ---------- Kirim ---------- */
    form.addEventListener('submit', e => {
        const salah = baris().find(r => r.dataset.ada === '1' && r.querySelector('.qty').value !== '' && !(qtyOf(r) > 0));
        if (salah) { e.preventDefault(); ke(2); alert('Berat harus lebih dari 0.'); salah.querySelector('.qty').focus(); return; }
        if (!terisi().length) { e.preventDefault(); ke(2); alert('Belum ada barang. Isi berat minimal satu barang.'); return; }

        const metode = $('payment_method').value;
        const bayar = parseFloat($('amount_paid').value) || 0;

        if (metode === 'HUTANG' && !adaPelanggan()) {
            e.preventDefault(); ke(3);
            alert('Hutang harus atas nama pelanggan. Isi nama pelanggan dulu.');
            $('pelanggan').focus();
            return;
        }
        if (metode !== 'HUTANG' && bayar < Math.round(belanja)) {
            e.preventDefault(); ke(3);
            alert('Uang dibayar kurang dari total belanja. Pilih "Hutang" kalau pelanggan berhutang.');
            $('amount_paid').focus();
            return;
        }

        // hanya barang yang terisi yang dikirim; tombol dikunci agar tidak tersimpan dua kali
        baris().forEach(r => { if (!(r.dataset.ada === '1' && qtyOf(r) > 0)) r.querySelectorAll('input').forEach(i => { i.disabled = true; }); });
        $('btn-aksi').disabled = true;
    });

    window.addEventListener('pageshow', () => {
        baris().forEach(r => r.querySelectorAll('input').forEach(i => { i.disabled = false; }));
        updateAksi();
    });

    /* ---------- Mulai ---------- */
    setType($('customer_type_id').value);
    setMethod($('payment_method').value);
    setTempo($('due_days').value);
    if (parseFloat($('discount').value) > 0) { $('wrap-diskon').style.display = 'flex'; $('btn-diskon').style.display = 'none'; }

    const c0 = CUSTOMERS.find(c => c.id === $('customer_id').value);
    if (c0) { saldoLama = c0.saldo; telatLama = c0.telat; }

    OLD.forEach(o => {
        const r = daftar.querySelector(`.rw[data-pid="${o.product_id}"]`);
        if (r) r.querySelector('.qty').value = o.quantity;
    });
    hitung();
    ke(OLD.length ? 3 : 1);
    if (!sentuh && langkah === 1) $('pelanggan').focus();
</script>
@endsection