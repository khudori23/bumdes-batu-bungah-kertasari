@extends('layouts.app')

@section('title', 'Kasir')

@section('content')
<style>
    main { max-width: 1280px; }
    .pos { display:grid; grid-template-columns: 1.3fr 1fr; gap:1rem; align-items:start; }
    @media (max-width: 900px) { .pos { grid-template-columns: 1fr; } }

    .chips { display:flex; gap:.5rem; flex-wrap:wrap; }
    .chip { padding:.45rem .95rem; border:1px solid #cbd5e1; border-radius:999px; background:#fff; cursor:pointer; font-size:.9rem; color:var(--navy); }
    .chip.on { background:var(--navy); color:#fff; border-color:var(--navy); }
    .chip.on.hutang { background:#c2410c; border-color:#c2410c; }
    .chip.kecil { padding:.25rem .7rem; font-size:.8rem; }

    #cari { font-size:1.15rem; padding:.8rem 1rem; border:2px solid var(--navy); border-radius:10px; width:100%; }
    .hint { color:#64748b; font-size:.8rem; margin-top:.35rem; }

    .tiles { display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:.6rem; margin-top:.9rem; }
    .tile { text-align:left; background:#fff; border:1px solid var(--line); border-radius:10px; padding:.7rem .8rem; cursor:pointer; display:flex; flex-direction:column; gap:.15rem; color:var(--navy); }
    .tile:hover { border-color:var(--teal); background:#f0fdfa; }
    .tile.first { outline:2px solid var(--teal); }
    .tile small { color:#64748b; font-size:.7rem; letter-spacing:.03em; }
    .tile b { font-size:.95rem; }
    .tile span { color:var(--teal); font-size:.85rem; font-weight:600; }

    .cart { position:sticky; top:1rem; }
    .line { display:grid; grid-template-columns:1fr 90px 105px 26px; gap:.5rem; align-items:center; padding:.55rem 0; border-bottom:1px solid var(--line); }
    .line .nm { font-weight:600; font-size:.95rem; }
    .line .hg { color:#64748b; font-size:.78rem; }
    .line .sub { text-align:right; font-weight:600; }
    .line input.qty { padding:.45rem; text-align:right; }
    .hapus { background:none; border:0; color:#e11d48; cursor:pointer; font-size:1rem; }
    .kosong { color:#94a3b8; text-align:center; padding:1.5rem 0; font-size:.9rem; }

    .total-box { background:var(--navy); color:#fff; border-radius:10px; padding:.9rem 1rem; margin-top:.9rem; }
    .total-box .lbl { font-size:.8rem; color:#94a3b8; }
    .total-box .big { font-size:2rem; font-weight:700; letter-spacing:-.02em; }
    .row2 { display:flex; justify-content:space-between; font-size:.9rem; margin-top:.4rem; }
    .quick { display:flex; gap:.4rem; margin-top:.4rem; flex-wrap:wrap; }
    .quick button { background:#fff; border:1px solid #cbd5e1; border-radius:8px; padding:.35rem .7rem; cursor:pointer; font-size:.82rem; }
    .btn-bayar { width:100%; margin-top:.9rem; background:var(--teal); color:#fff; border:0; padding:.95rem; border-radius:10px; font-size:1.05rem; cursor:pointer; font-weight:600; }
    kbd { background:#e2e8f0; border-radius:4px; padding:0 .35rem; font-size:.75rem; }

    .saran { position:absolute; left:0; right:0; top:100%; z-index:10; background:#fff; border:1px solid #cbd5e1; border-radius:8px; box-shadow:0 6px 18px rgba(15,23,42,.12); margin-top:2px; overflow:hidden; }
    .saran button { display:flex; justify-content:space-between; width:100%; text-align:left; background:#fff; border:0; border-bottom:1px solid var(--line); padding:.5rem .7rem; cursor:pointer; font-size:.9rem; color:var(--navy); }
    .saran button:hover, .saran button.aktif { background:#f0fdfa; }
    .saran .bon { color:#e11d48; font-size:.8rem; }
    .hutang-info { background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; border-radius:8px; padding:.45rem .7rem; font-size:.85rem; margin-top:.4rem; }
    .hutang-info.telat { background:#fff1f2; border-color:#fecdd3; color:#be123c; font-weight:600; }
    #pelanggan { width:100%; padding:.5rem .7rem; font-size:.95rem; border:1px solid #cbd5e1; border-radius:8px; }
    .merah { color:#e11d48; }
    .tempo-box { background:#fff7ed; border:1px solid #fed7aa; border-radius:8px; padding:.5rem .7rem; margin-top:.6rem; font-size:.85rem; }
</style>

@if ($errors->any())
    <div style="background:#ffe4e6;border:1px solid #fecdd3;padding:.6rem .9rem;border-radius:8px;margin-bottom:1rem">
        @foreach ($errors->all() as $e)
            <div class="err" style="margin:0">{{ $e }}</div>
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
                    <button type="button" class="chip" data-id="{{ $t->id }}">{{ $t->name }}</button>
                @endforeach
            </div>

            <div style="margin-top:1rem">
                <input type="text" id="cari" placeholder="Cari produk (nama / kode), tekan Enter untuk menambah…" autofocus>
                <div class="hint"><kbd>F2</kbd> cari &nbsp; <kbd>Enter</kbd> tambah produk pertama &nbsp; <kbd>F3</kbd> pelanggan &nbsp; <kbd>F4</kbd> uang dibayar &nbsp; <kbd>F9</kbd> simpan</div>
            </div>

            <div class="tiles" id="tiles"></div>
        </div>

        {{-- KANAN: keranjang & pembayaran --}}
        <div class="card cart">
            <strong>Keranjang</strong>

            <div style="position:relative;margin-top:.6rem">
                <input type="text" name="customer_name" id="pelanggan" value="{{ old('customer_name') }}" placeholder="Pelanggan (wajib untuk hutang) — ketik nama…">
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
                <button type="button" class="chip" data-method="CASH">Tunai</button>
                <button type="button" class="chip" data-method="TRANSFER">Transfer</button>
                <button type="button" class="chip hutang" data-method="HUTANG">Hutang</button>
            </div>
            <div id="ref-wrap" style="display:none;margin-top:.5rem">
                <input type="text" name="reference_number" value="{{ old('reference_number') }}" placeholder="No. referensi transfer (opsional)">
            </div>

            <div class="tempo-box" id="row-tempo" style="display:none">
                <div>Jatuh tempo hutang: <strong id="t-tempo"></strong></div>
                <div class="chips" style="margin-top:.4rem" id="tempo-chips">
                    <button type="button" class="chip kecil" data-hari="3">3 hari</button>
                    <button type="button" class="chip kecil" data-hari="7">7 hari</button>
                    <button type="button" class="chip kecil" data-hari="14">14 hari</button>
                    <button type="button" class="chip kecil" data-hari="30">30 hari</button>
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

            <button type="submit" class="btn-bayar">Simpan & Cetak Nota <kbd style="color:#0f172a">F9</kbd></button>
        </div>
    </div>
</form>

<script>
    const PRICES    = @json($prices);
    const PRODUCTS  = @json($products);
    const CUSTOMERS = @json($customers);
    const OLD       = @json(old('items', []));

    const $ = id => document.getElementById(id);
    const form = $('form-kasir'), cartEl = $('cart'), tilesEl = $('tiles'), kosongEl = $('kosong');
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
                <button type="button" class="hapus" title="Hapus">✕</button>`;
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
                ? 'TERLAMBAT ' + telatLama + ' hari — hutang sebelumnya ' + rupiah(saldoLama) + ' digabung di nota ini.'
                : 'Hutang sebelumnya ' + rupiah(saldoLama) + ' — otomatis digabung di nota ini.';
        } else {
            info.style.display = 'none';
        }
    }

    /* ---------- Jenis pelanggan & metode bayar ---------- */
    function setType(id) {
        $('customer_type_id').value = id;
        document.querySelectorAll('#types .chip').forEach(c => c.classList.toggle('on', c.dataset.id === id));

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
        document.querySelectorAll('#tempo-chips .chip').forEach(c => c.classList.toggle('on', c.dataset.hari === String(hari)));

        const d = new Date();
        d.setDate(d.getDate() + Number(hari));
        $('t-tempo').textContent = d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
            + ' (' + hari + ' hari)';
    }

    $('tempo-chips').addEventListener('click', e => {
        const c = e.target.closest('.chip');
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
                ${c.saldo > 0 ? `<span class="bon">hutang ${rupiah(c.saldo)}${c.telat > 0 ? ' · terlambat ' + c.telat + ' hari' : ''}</span>` : ''}
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
            document.querySelector(`#types .chip[data-id="${c.type}"]`)) {
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
    $('types').addEventListener('click', e => { const c = e.target.closest('.chip'); if (c) setType(c.dataset.id); });
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
    $('cari').focus();
    hitung();
</script>
@endsection