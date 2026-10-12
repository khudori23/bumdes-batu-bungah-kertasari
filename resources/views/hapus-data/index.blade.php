@extends('layouts.app')
@section('title', 'Hapus Data')

@section('content')
<style>
.hd{box-sizing:border-box;width:100%;max-width:760px;margin:0 auto;padding:12px;min-width:0;--bar:0px}
.hd *{box-sizing:border-box;min-width:0}
.hd h1{font-size:1.3rem;margin:4px 0 4px;color:#0f172a}
.hd .sub{color:#64748b;font-size:.9rem;margin:0 0 12px;line-height:1.45}
.hd .warn{background:#fff1f2;border:1px solid #fecdd3;color:#9f1239;border-radius:12px;padding:10px 12px;font-size:.88rem;margin-bottom:12px;line-height:1.45}
.hd .ok{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:12px;padding:10px 12px;font-size:.9rem;margin-bottom:12px}
.hd .box{background:#fff;border:1px solid #e2e8f0;border-radius:14px;margin-bottom:14px;overflow:hidden}
.hd .box-h{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:12px 14px;background:#0f172a;color:#fff;font-weight:600}
.hd .box-h label{font-weight:400;font-size:.85rem;display:flex;gap:8px;align-items:center;cursor:pointer;white-space:nowrap}
.hd .row{display:flex;align-items:center;gap:10px;padding:0 14px 0 0;border-top:1px solid #f1f5f9}
.hd .row label.pick{display:flex;align-items:center;gap:12px;flex:1;padding:14px 0 14px 14px;cursor:pointer}
.hd .row:has(.cek:checked){background:#fff1f2}
.hd .nm{flex:1;line-height:1.3}
.hd .n{background:#e2e8f0;color:#334155;border-radius:999px;padding:2px 10px;font-size:.78rem;white-space:nowrap;font-variant-numeric:tabular-nums}
.hd .n.zero{opacity:.5}
.hd a.lnk{color:#0f766e;font-size:.85rem;white-space:nowrap;text-decoration:none;padding:10px 4px;font-weight:600}
.hd input[type=checkbox]{width:22px;height:22px;flex:none;accent-color:#e11d48}
.hd .bar{position:sticky;bottom:var(--bar);z-index:20;display:flex;align-items:center;justify-content:space-between;gap:10px;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:10px 12px;box-shadow:0 -4px 18px rgba(15,23,42,.12)}
.hd .bar b{color:#e11d48;font-size:1.1rem}
.hd .btn{border:0;border-radius:10px;padding:12px 16px;font-size:.95rem;font-weight:600;cursor:pointer}
.hd .btn-del{background:#e11d48;color:#fff}
.hd .btn-del:disabled{opacity:.4;cursor:not-allowed}
.hd .btn-x{background:#e2e8f0;color:#334155}
.hd .lbl{display:block;font-size:.85rem;color:#334155;margin:0 0 4px}
.hd .inp{width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:10px;font-size:16px;margin-bottom:10px;background:#fff}
.hd dialog{border:0;border-radius:16px;padding:0;width:min(92vw,420px);box-shadow:0 20px 50px rgba(0,0,0,.35)}
.hd dialog::backdrop{background:rgba(15,23,42,.6)}
.hd .dlg{padding:18px}
.hd .dlg h2{margin:0 0 6px;font-size:1.1rem;color:#9f1239}
.hd .dlg ul{margin:6px 0 12px;padding-left:18px;font-size:.88rem;color:#334155;max-height:140px;overflow:auto}
.hd .dlg .acts{display:flex;gap:10px}
.hd .dlg .acts .btn{flex:1}
@media (max-width:900px){.hd{--bar:68px}}
</style>

<div class="hd">
    <h1>Hapus Data</h1>
    <p class="sub">Menghapus data transaksi hasil input kasir dan pengawas. Data induk (produk, pelanggan, pegawai, pemasok, pengaturan) tidak ikut terhapus.</p>

    <div class="warn"><strong>Permanen, tidak bisa dikembalikan.</strong> Backup database dulu. Mutasi stok memengaruhi saldo stok, jadi hapus bersama data penjualan atau produksi agar stok tetap sesuai.</div>

    @if (session('sukses'))<div class="ok">{{ session('sukses') }}</div>@endif
    @if ($errors->any())
        <div class="warn">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <form method="POST" action="{{ route('hapus-data.tabel') }}" id="formHapus">
        @csrf
        @method('DELETE')

        @foreach ($kelompok as $kode => $k)
            <div class="box">
                <div class="box-h">
                    <span>{{ $k['judul'] }}</span>
                    <label><input type="checkbox" class="all" data-g="{{ $kode }}"> Pilih semua</label>
                </div>
                @forelse ($k['tabel'] as $nama => $d)
                    <div class="row">
                        <label class="pick">
                            <input type="checkbox" name="tabel[]" value="{{ $nama }}" class="cek g-{{ $kode }}" data-label="{{ $d['label'] }}" data-n="{{ $d['jumlah'] }}">
                            <span class="nm">{{ $d['label'] }}</span>
                            <span class="n {{ $d['jumlah'] ? '' : 'zero' }}">{{ number_format($d['jumlah'], 0, ',', '.') }}</span>
                        </label>
                        <a class="lnk" href="{{ route('hapus-data.show', $nama) }}">Pilih baris &rsaquo;</a>
                    </div>
                @empty
                    <div class="row"><div class="pick" style="padding:14px">Tidak ada tabel.</div></div>
                @endforelse
            </div>
        @endforeach

        <div class="bar">
            <span><b id="jml">0</b> data dipilih</span>
            <button type="button" class="btn btn-del" id="btnBuka" disabled>Hapus&hellip;</button>
        </div>

        <dialog id="dlg">
            <div class="dlg">
                <h2>Hapus permanen?</h2>
                <div style="font-size:.88rem;color:#475569">Seluruh isi data berikut akan dihapus, termasuk data turunannya:</div>
                <ul id="daftar"></ul>
                <label class="lbl">Password Anda</label>
                <input type="password" name="password" id="pw" class="inp" required autocomplete="current-password">
                <label class="lbl">Ketik <strong>HAPUS</strong> untuk konfirmasi</label>
                <input type="text" name="konfirmasi" id="kf" class="inp" required autocomplete="off" autocapitalize="characters">
                <div class="acts">
                    <button type="button" class="btn btn-x" id="btnBatal">Batal</button>
                    <button type="submit" class="btn btn-del" id="btnOk" disabled>Hapus Permanen</button>
                </div>
            </div>
        </dialog>
    </form>
</div>

<script>
(function () {
    var cek = [].slice.call(document.querySelectorAll('.cek'));
    var all = [].slice.call(document.querySelectorAll('.all'));
    var jml = document.getElementById('jml'), buka = document.getElementById('btnBuka');
    var dlg = document.getElementById('dlg'), daftar = document.getElementById('daftar');
    var pw = document.getElementById('pw'), kf = document.getElementById('kf'), ok = document.getElementById('btnOk');

    function hitung() {
        var n = cek.filter(function (c) { return c.checked; }).length;
        jml.textContent = n; buka.disabled = n === 0;
        all.forEach(function (a) {
            var g = cek.filter(function (c) { return c.classList.contains('g-' + a.dataset.g); });
            var on = g.filter(function (c) { return c.checked; }).length;
            a.checked = g.length > 0 && on === g.length;
            a.indeterminate = on > 0 && on < g.length;
        });
        return n;
    }
    function siap() { ok.disabled = !(pw.value.length && kf.value === 'HAPUS'); }

    all.forEach(function (a) {
        a.addEventListener('change', function () {
            cek.forEach(function (c) { if (c.classList.contains('g-' + a.dataset.g)) c.checked = a.checked; });
            hitung();
        });
    });
    cek.forEach(function (c) { c.addEventListener('change', hitung); });

    buka.addEventListener('click', function () {
        daftar.innerHTML = '';
        cek.filter(function (c) { return c.checked; }).forEach(function (c) {
            var li = document.createElement('li');
            li.textContent = c.dataset.label + ' (' + c.dataset.n + ' data)';
            daftar.appendChild(li);
        });
        pw.value = ''; kf.value = ''; siap();
        dlg.showModal();
    });
    document.getElementById('btnBatal').addEventListener('click', function () { dlg.close(); });
    pw.addEventListener('input', siap); kf.addEventListener('input', siap);
    hitung();
})();
</script>
@endsection