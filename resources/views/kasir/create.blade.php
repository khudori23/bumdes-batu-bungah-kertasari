@extends('layouts.app')

@section('title', 'Kasir')

@section('content')
<link rel="stylesheet" href="{{ asset('css/kasir.css') }}?v={{ @filemtime(public_path('css/kasir.css')) }}">

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

    <div class="pos">
        {{-- KIRI: pencarian & produk --}}
        <div class="card">
            <div class="chips" id="types">
                @foreach ($types as $t)
                    <button type="button" class="pill" data-id="{{ $t->id }}">{{ $t->name }}</button>
                @endforeach
            </div>

            <div style="margin-top:1rem">
                <input type="text" id="cari" placeholder="Cari produk (nama / kode), tekan Enter untuk menambah&hellip;">
                <div class="hint"><kbd>F2</kbd> cari &nbsp; <kbd>Enter</kbd> tambah produk pertama &nbsp; <kbd>F3</kbd> pelanggan &nbsp; <kbd>F4</kbd> uang dibayar &nbsp; <kbd>F9</kbd> simpan</div>
            </div>

            <div class="tiles" id="tiles"></div>
        </div>

        {{-- KANAN: keranjang & pembayaran --}}
        <div class="card cart" id="kartu-keranjang">
            <strong>Keranjang</strong>

            <div style="position:relative;margin-top:.6rem">
                <input type="text" name="customer_name" id="pelanggan" value="{{ old('customer_name') }}" placeholder="Pelanggan (wajib untuk hutang) &mdash; ketik nama&hellip;">
                <div class="saran" id="saran" style="display:none"></div>
            </div>
            @if (auth()->user()->canWrite('kasir'))
            <div style="margin-top:.35rem;font-size:.82rem">
                <a href="{{ route('pelanggan.index') }}" target="_blank" rel="noopener">+ Pelanggan baru (lengkap dengan telepon &amp; alamat)</a>
                <div style="color:#64748b">Nama baru yang diketik di kolom ini juga otomatis jadi pelanggan saat nota disimpan.</div>
            </div>
            @endif
            <div class="hutang-info" id="info-hutang" style="display:none"></div>

            <div id="cart"></div>
            <div class="kosong" id="kosong">Belum ada produk. Ketik di kolom cari atau klik produk.</div>

            <div class="total-box">
                <div class="lbl">TOTAL BELANJA</div>
                <div class="big" id="t-total">Rp 0</div>
            </div>

            <div class="row2"><span>Subtotal</span><strong id="t-subtotal">Rp 0</strong></div>
            <div class="row2" style="align-items:center">
                <span>Diskon (Rp)</span>
                <input type="number" min="0" step="1" name="discount" id="discount" value="{{ old('discount', 0) }}" style="width:120px;padding:.35rem .5rem;text-align:right">
            </div>

            <div class="row2" id="row-lama" style="display:none"><span>Hutang sebelumnya</span><strong id="t-lama" style="color:#c2410c">Rp 0</strong></div>
            <div class="row2" id="row-tagihan" style="display:none;font-size:1.05rem"><span>Total tagihan</span><strong id="t-tagihan">Rp 0</strong></div>

            <div class="chips" style="margin-top:.8rem">
                <button type="button" class="pill" data-method="CASH">Tunai</button>
                <button type="button" class="pill" data-method="TRANSFER">Transfer</button>
                <button type="button" class="pill hutang" data-method="HUTANG">Hutang</button>
            </div>
            <div id="ref-wrap" style="display:none;margin-top:.5rem">
                <input type="text" name="reference_number" value="{{ old('reference_number') }}" placeholder="No. referensi transfer (opsional)">
            </div>

            <div class="tempo-box" id="row-tempo" style="display:none">
                <div>Jatuh tempo hutang: <strong id="t-tempo"></strong></div>
                <div class="chips" style="margin-top:.4rem" id="tempo-chips">
                    <button type="button" class="pill kecil" data-hari="3">3 hari</button>
                    <button type="button" class="pill kecil" data-hari="7">7 hari</button>
                    <button type="button" class="pill kecil" data-hari="14">14 hari</button>
                    <button type="button" class="pill kecil" data-hari="30">30 hari</button>
                </div>
            </div>

            <label for="amount_paid" style="margin-top:.8rem">Uang dibayar (Rp) <kbd>F4</kbd></label>
            <input type="number" min="0" step="1" name="amount_paid" id="amount_paid" value="{{ old('amount_paid') }}" style="font-size:1.2rem;padding:.6rem;text-align:right">
            <div class="quick">
                <button type="button" data-quick="belanja">Pas belanja</button>
                <button type="button" data-quick="semua" id="btn-semua" style="display:none">Lunasi semua</button>
                <button type="button" data-quick="50000">50.000</button>
                <button type="button" data-quick="100000">100.000</button>
            </div>

            <div class="row2" style="font-size:1.05rem"><span>Sisa hutang</span><strong id="t-sisa">Rp 0</strong></div>
            <div class="row2" style="font-size:1.05rem"><span>Kembalian</span><strong id="t-kembali">Rp 0</strong></div>

            <button type="submit" class="btn-bayar">Simpan &amp; Cetak Nota <kbd style="color:#0f172a">F9</kbd></button>
        </div>
    </div>
</form>

<div class="mbar">
    <div><small id="m-item">0 item</small><b id="m-total">Rp 0</b></div>
    <a href="#kartu-keranjang">Bayar</a>
</div>

<script>
    const PRICES    = @json($prices);
    const PRODUCTS  = @json($products);
    const CUSTOMERS = @json($customers);
    const OLD       = @json(old('items', []));

    const $ = id => document.getElementById(id);
    const form = $('form-kasir'), cartEl = $('cart'), tilesEl = $('tiles'), kosongEl = $('kosong');
    const sentuh = window.matchMedia('(pointer: coarse)').matches;
    let idx = 0, belanja = 0, saldoLama = 0, telatLama = 0, paidManual = false;

    const rupiah = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');
    const tersedia = () => PRICES[$('customer_type_id').value] || {};
    const norm = s => (s || '').trim().toLowerCase();
    const adaPelanggan = () => $('customer_id').value !== '' ||
        (norm($('pelanggan').value) !== '' && norm($('pelanggan').value) !== 'umum');

    /* ---------- Produk (kartu) ---------- */
    function renderTiles() {
        const q = $('cari').value.trim().toLowerCase();
        const av = tersedia();
        const list = PRODUCTS.filter(p => av[p.id] !== undefined &&
            (!q || p.name.toLowerCase().includes(q) || p.code.toLowerCase().includes(q)));

        tilesEl.innerHTML = list.length
            ? list.map((p, n) => `
                <button type="button" class="tile ${n === 0 && q ? 'first' : ''}" data-id="${p.id}">
                    <small>${p.code}</small><b>${p.name}</b><span>${rupiah(av[p.id])}/kg</span>
                </button>`).join('')
            : '<div class="kosong" style="grid-column:1/-1">Tidak ada produk yang cocok.</div>';
        return list;
    }

    /* ---------- Keranjang ---------- */
    function isiHarga(tr) {
        const h = tersedia()[tr.dataset.pid];
        tr.querySelector('.hg').textContent = h !== undefined ? rupiah(h) + '/kg' : '';
    }

    function tambah(pid, qty = '') {
        let tr = cartEl.querySelector(`[data-pid="${pid}"]`);
        if (!tr) {
            const p = PRODUCTS.find(x => x.id === pid);
            const i = idx++;
            tr = document.createElement('div');
            tr.className = 'line';
            tr.dataset.pid = pid;
            tr.innerHTML = `
                <div><div class="nm">${p.name}</div><div class="hg"></div></div>
                <input type="hidden" name="items[${i}][product_id]" value="${pid}">
                <input type="number" step="0.01" min="0.01" name="items[${i}][quantity]" class="qty" placeholder="kg" value="${qty}">
                <div class="sub">Rp 0</div>
                <button type="button" class="hapus" title="Hapus">&times;</button>`;
            cartEl.appendChild(tr);
            isiHarga(tr);
        }
        const q = tr.querySelector('.qty');
        q.focus();
        q.select();
        hitung();
    }

    function hitung() {
        const av = tersedia();
        const lines = cartEl.querySelectorAll('.line');
        let subtotal = 0;

        lines.forEach(tr => {
            const harga = av[tr.dataset.pid] || 0;
            const qty = parseFloat(tr.querySelector('.qty').value) || 0;
            const sub = harga * qty;
            tr.querySelector('.sub').textContent = rupiah(sub);
            subtotal += sub;
        });

        kosongEl.style.display = lines.length ? 'none' : 'block';

        const diskon = parseFloat($('discount').value) || 0;
        belanja = Math.max(subtotal - diskon, 0);

        // Transfer: isi otomatis sebesar belanja, selama belum diubah manual
        if ($('payment_method').value === 'TRANSFER' && !paidManual) {
            $('amount_paid').value = belanja > 0 ? Math.round(belanja) : '';
        }

        const tagihan = belanja + saldoLama;
        const bayar = parseFloat($('amount_paid').value) || 0;
        const sisa = Math.max(tagihan - bayar, 0);

        $('t-subtotal').textContent = rupiah(subtotal);
        $('t-total').textContent = rupiah(belanja);
        $('m-total').textContent = rupiah(belanja);
        $('m-item').textContent = lines.length + ' item';
        $('t-lama').textContent = rupiah(saldoLama);
        $('t-tagihan').textContent = rupiah(tagihan);
        $('row-lama').style.display = saldoLama > 0 ? 'flex' : 'none';
        $('row-tagihan').style.display = saldoLama > 0 ? 'flex' : 'none';
        $('btn-semua').style.display = saldoLama > 0 ? '' : 'none';

        $('t-sisa').textContent = rupiah(sisa);
        $('t-sisa').classList.toggle('merah', sisa > 0.5);
        $('t-kembali').textContent = rupiah(Math.max(bayar - tagihan, 0));

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

        // produk yang tidak punya harga untuk jenis ini dikeluarkan dari keranjang
        const av = tersedia();
        cartEl.querySelectorAll('.line').forEach(tr => {
            if (av[tr.dataset.pid] === undefined) tr.remove();
            else isiHarga(tr);
        });
        renderTiles();
        hitung();
    }

    function setMethod(m) {
        $('payment_method').value = m;
        paidManual = false;
        document.querySelectorAll('[data-method]').forEach(c => c.classList.toggle('on', c.dataset.method === m));
        $('ref-wrap').style.display = m === 'TRANSFER' ? 'block' : 'none';
        $('row-tempo').style.display = m === 'HUTANG' ? 'block' : 'none';
        hitung();
    }

    /* ---------- Jatuh tempo ---------- */
    function setTempo(hari) {
        $('due_days').value = hari;
        document.querySelectorAll('#tempo-chips .pill').forEach(c => c.classList.toggle('on', c.dataset.hari === String(hari)));

        const d = new Date();
        d.setDate(d.getDate() + Number(hari));
        $('t-tempo').textContent = d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
            + ' (' + hari + ' hari)';
    }

    $('tempo-chips').addEventListener('click', e => {
        const c = e.target.closest('.pill');
        if (c) setTempo(c.dataset.hari);
    });

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
        // samakan jenis pelanggan (harga) dengan pelanggan terpilih
        if ($('customer_type_id').value !== c.type &&
            document.querySelector(`#types .pill[data-id="${c.type}"]`)) {
            setType(c.type);
        }
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

    document.addEventListener('click', e => {
        if (!e.target.closest('#saran') && e.target.id !== 'pelanggan') sembunyikanSaran();
    });

    /* ---------- Event ---------- */
    $('types').addEventListener('click', e => { const c = e.target.closest('.pill'); if (c) setType(c.dataset.id); });
    document.querySelectorAll('[data-method]').forEach(c => c.addEventListener('click', () => setMethod(c.dataset.method)));

    tilesEl.addEventListener('click', e => { const t = e.target.closest('.tile'); if (t) tambah(t.dataset.id); });

    cartEl.addEventListener('input', hitung);
    cartEl.addEventListener('click', e => {
        if (e.target.classList.contains('hapus')) { e.target.closest('.line').remove(); hitung(); $('cari').focus(); }
    });

    $('cari').addEventListener('input', renderTiles);
    $('cari').addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const list = renderTiles();
            if (list.length) { $('cari').value = ''; tambah(list[0].id); renderTiles(); }
        }
    });

    $('discount').addEventListener('input', hitung);
    $('amount_paid').addEventListener('input', () => { paidManual = true; hitung(); });

    document.querySelectorAll('[data-quick]').forEach(b => b.addEventListener('click', () => {
        const k = b.dataset.quick;
        paidManual = true;
        $('amount_paid').value = k === 'belanja' ? Math.round(belanja)
                               : k === 'semua' ? Math.round(belanja + saldoLama)
                               : k;
        hitung();
    }));

    // Enter: berat -> kembali ke pencarian; pelanggan -> pilih saran pertama; uang dibayar -> simpan
    form.addEventListener('keydown', e => {
        if (e.key !== 'Enter' || e.target.tagName === 'BUTTON') return;
        if (e.target.id === 'cari') return;
        e.preventDefault();

        if (e.target.classList.contains('qty')) {
            $('cari').focus();
        } else if (e.target.id === 'pelanggan') {
            const list = renderSaran();
            if (list.length && $('saran').style.display !== 'none') pilihPelanggan(list[0]);
            $('cari').focus();
        } else if (e.target.id === 'amount_paid') {
            form.requestSubmit();
        }
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'F2') { e.preventDefault(); $('cari').focus(); $('cari').select(); }
        else if (e.key === 'F3') { e.preventDefault(); $('pelanggan').focus(); $('pelanggan').select(); }
        else if (e.key === 'F4') { e.preventDefault(); $('amount_paid').focus(); $('amount_paid').select(); }
        else if (e.key === 'F9' || (e.ctrlKey && e.key === 'Enter')) { e.preventDefault(); form.requestSubmit(); }
        else if (e.key === 'Escape') { $('cari').value = ''; renderTiles(); sembunyikanSaran(); $('cari').focus(); }
    });

    form.addEventListener('submit', e => {
        const lines = [...cartEl.querySelectorAll('.line')];
        if (!lines.length) { e.preventDefault(); alert('Belum ada produk di keranjang.'); $('cari').focus(); return; }

        const kosong = lines.find(l => !(parseFloat(l.querySelector('.qty').value) > 0));
        if (kosong) { e.preventDefault(); alert('Isi berat produk dulu.'); kosong.querySelector('.qty').focus(); return; }

        const metode = $('payment_method').value;
        const bayar = parseFloat($('amount_paid').value) || 0;

        if (metode === 'HUTANG' && !adaPelanggan()) {
            e.preventDefault();
            alert('Hutang harus atas nama pelanggan. Isi nama pelanggan dulu.');
            $('pelanggan').focus();
            return;
        }
        if (metode !== 'HUTANG' && bayar < Math.round(belanja)) {
            e.preventDefault();
            alert('Uang dibayar kurang dari total belanja. Pilih "Hutang" kalau pelanggan berhutang.');
            $('amount_paid').focus();
        }
    });

    /* ---------- Mulai ---------- */
    setType($('customer_type_id').value);
    setMethod($('payment_method').value);
    setTempo($('due_days').value);

    const c0 = CUSTOMERS.find(c => c.id === $('customer_id').value);
    if (c0) { saldoLama = c0.saldo; telatLama = c0.telat; }

    OLD.forEach(o => tambah(o.product_id, o.quantity));
    if (! sentuh) $('cari').focus();
    hitung();
</script>
@endsection