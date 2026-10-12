@extends('layouts.app')
@section('title', 'Hapus Data')

@section('content')
<style>
.hd{box-sizing:border-box;width:100%;max-width:980px;margin:0 auto;padding:12px;min-width:0;--bar:0px}
.hd *{box-sizing:border-box;min-width:0}
.hd h1{font-size:1.25rem;margin:8px 0 12px;color:#0f172a}
.hd a.lnk{color:#0f766e;font-size:.9rem;text-decoration:none;font-weight:600}
.hd .warn{background:#fff1f2;border:1px solid #fecdd3;color:#9f1239;border-radius:12px;padding:10px 12px;font-size:.88rem;margin-bottom:12px;line-height:1.45}
.hd .ok{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:12px;padding:10px 12px;font-size:.9rem;margin-bottom:12px}
.hd .box{background:#fff;border:1px solid #e2e8f0;border-radius:14px;margin-bottom:14px;overflow:hidden}
.hd .pad{padding:14px}
.hd details.fl>summary{list-style:none;cursor:pointer;padding:13px 14px;font-weight:600;display:flex;justify-content:space-between;align-items:center;color:#0f172a}
.hd details.fl>summary::-webkit-details-marker{display:none}
.hd details.fl>summary small{font-weight:400;color:#64748b}
.hd details.fl[open]>summary{border-bottom:1px solid #f1f5f9}
.hd .fg{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:0 10px}
.hd .fg .wide{grid-column:1/-1}
.hd .lbl{display:block;font-size:.85rem;color:#334155;margin:0 0 4px}
.hd .inp{width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:10px;font-size:16px;margin-bottom:10px;background:#fff;max-width:100%}
.hd .fb{display:flex;align-items:center;gap:14px;flex-wrap:wrap}
.hd .btn{border:0;border-radius:10px;padding:12px 18px;font-size:.95rem;font-weight:600;cursor:pointer}
.hd .btn-f{background:#0f766e;color:#fff}
.hd .btn-del{background:#e11d48;color:#fff}
.hd .btn-del:disabled{opacity:.4;cursor:not-allowed}
.hd .btn-x{background:#e2e8f0;color:#334155}
.hd .info{color:#64748b;font-size:.85rem}
.hd .tools{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:10px;font-size:.9rem;color:#334155}
.hd .tools label{display:flex;align-items:center;gap:8px;cursor:pointer}
.hd input[type=checkbox]{width:22px;height:22px;flex:none;accent-color:#e11d48}
.hd table{width:100%;border-collapse:collapse;font-size:.88rem}
.hd th{background:#0f172a;color:#fff;text-align:left;padding:10px;white-space:nowrap;text-transform:capitalize}
.hd td{padding:10px;border-top:1px solid #f1f5f9;vertical-align:top}
.hd tbody tr{cursor:pointer}
.hd tbody tr:hover td{background:#f8fafc}
.hd tbody tr.sel td{background:#fff1f2}
.hd .pg{display:flex;justify-content:space-between;margin:0 0 14px}
.hd .bar{position:sticky;bottom:var(--bar);z-index:20;display:flex;align-items:center;justify-content:space-between;gap:10px;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:10px 12px;box-shadow:0 -4px 18px rgba(15,23,42,.12)}
.hd .bar b{color:#e11d48;font-size:1.1rem}
.hd dialog{border:0;border-radius:16px;padding:0;width:min(92vw,420px);box-shadow:0 20px 50px rgba(0,0,0,.35)}
.hd dialog::backdrop{background:rgba(15,23,42,.6)}
.hd .dlg{padding:18px}
.hd .dlg h2{margin:0 0 6px;font-size:1.1rem;color:#9f1239}
.hd .dlg .acts{display:flex;gap:10px}
.hd .dlg .acts .btn{flex:1}
@media (max-width:700px){
  .hd{--bar:68px}
  .hd table,.hd tbody,.hd tr,.hd td{display:block;width:100%}
  .hd thead{display:none}
  .hd tr{position:relative;border-top:1px solid #e2e8f0;padding:10px 12px 10px 48px}
  .hd tr.kosong{padding:14px}
  .hd td{border:0;padding:3px 0;white-space:normal;word-break:break-word;font-size:.9rem}
  .hd td.c{position:absolute;left:12px;top:12px;width:auto}
  .hd td[data-l]::before{content:attr(data-l);display:block;font-size:.7rem;color:#64748b;text-transform:capitalize}
  .hd tbody tr:hover td{background:transparent}
  .hd tbody tr.sel{background:#fff1f2}
  .hd tbody tr.sel td{background:transparent}
  .hd .info{width:100%}
}
</style>

@php $aktif = $dari !== '' || $sampai !== '' || $q !== ''; @endphp
<div class="hd">
    <a class="lnk" href="{{ route('hapus-data.index') }}">&larr; Kembali ke Hapus Data</a>
    <h1>{{ $label }}</h1>

    @if (session('sukses'))<div class="ok">{{ session('sukses') }}</div>@endif
    @if ($errors->any())
        <div class="warn">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <form method="GET" action="{{ route('hapus-data.show', $tabel) }}" class="box">
        <details class="fl" @if ($aktif) open @endif>
            <summary><span>Filter &amp; pencarian</span><small>{{ $total }} data{{ $aktif ? ' (terfilter)' : '' }}</small></summary>
            <div class="pad">
                <div class="fg">
                    @if ($kolomTgl)
                        <div><label class="lbl">Dari tanggal</label><input type="date" name="dari" value="{{ $dari }}" class="inp"></div>
                        <div><label class="lbl">Sampai tanggal</label><input type="date" name="sampai" value="{{ $sampai }}" class="inp"></div>
                    @endif
                    <div class="wide"><label class="lbl">Cari (nama, nomor, catatan, dll)</label>
                        <input type="search" name="q" value="{{ $q }}" class="inp" placeholder="mis. INV-20261010 atau nama pelanggan"></div>
                    <div class="wide"><label class="lbl">Tampil per halaman</label>
                        <select name="per" class="inp">
                            @foreach ([25, 50, 100, 200] as $n)<option value="{{ $n }}" @selected($per === $n)>{{ $n }}</option>@endforeach
                        </select></div>
                </div>
                <div class="fb">
                    <button class="btn btn-f" type="submit">Terapkan</button>
                    <a class="lnk" href="{{ route('hapus-data.show', $tabel) }}">Reset</a>
                </div>
            </div>
        </details>
    </form>

    <form method="POST" action="{{ route('hapus-data.baris', $tabel) }}" id="formHapus" data-label="{{ $label }}">
        @csrf
        @method('DELETE')

        @php
            $kolom = [];
            if ($baris->count()) {
                $kolom = array_slice(array_values(array_filter(
                    array_keys((array) $baris->first()),
                    fn ($k) => $k !== 'id' && !str_ends_with($k, '_id') && !in_array($k, ['updated_at', 'deleted_at'])
                )), 0, 5);
            }
        @endphp

        <div class="tools">
            <label><input type="checkbox" id="pilihSemua"> Pilih semua di halaman ini</label>
            <span class="info">{{ $baris->count() }} baris ditampilkan</span>
        </div>

        <div class="box">
            <table>
                <thead>
                    <tr>
                        <th style="width:44px"></th>
                        @foreach ($kolom as $k)<th>{{ str_replace('_', ' ', $k) }}</th>@endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($baris as $b)
                        <tr>
                            <td class="c"><input type="checkbox" name="ids[]" value="{{ $b->id }}" class="cek"></td>
                            @foreach ($kolom as $k)
                                <td data-l="{{ str_replace('_', ' ', $k) }}">{{ \Illuminate\Support\Str::limit((string) ($b->$k ?? ''), 60) }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr class="kosong"><td colspan="{{ count($kolom) + 1 }}">Tidak ada data yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pg">
            <span>@if ($baris->previousPageUrl())<a class="lnk" href="{{ $baris->previousPageUrl() }}">&larr; Sebelumnya</a>@endif</span>
            <span>@if ($baris->nextPageUrl())<a class="lnk" href="{{ $baris->nextPageUrl() }}">Berikutnya &rarr;</a>@endif</span>
        </div>

        @if ($baris->count())
            <div class="warn">
                Baris yang dihapus ikut menghapus data turunannya
                @if (count($turunan)) : <strong>{{ implode(', ', $turunan) }}</strong>.@else (tabel ini tidak punya data turunan).@endif
                Centang hanya baris yang benar-benar mau dihapus.
            </div>

            <div class="bar">
                <span><b id="jml">0</b> baris dipilih</span>
                <button type="button" class="btn btn-del" id="btnBuka" disabled>Hapus&hellip;</button>
            </div>

            <dialog id="dlg">
                <div class="dlg">
                    <h2>Hapus permanen?</h2>
                    <p style="font-size:.9rem;color:#475569;margin:0 0 12px"><b id="jml2">0</b> baris dari <b>{{ $label }}</b> akan dihapus beserta data turunannya. Tidak bisa dibatalkan.</p>
                    <label class="lbl">Password Anda</label>
                    <input type="password" name="password" id="pw" class="inp" required autocomplete="current-password">
                    <div class="acts">
                        <button type="button" class="btn btn-x" id="btnBatal">Batal</button>
                        <button type="submit" class="btn btn-del" id="btnOk" disabled>Hapus Permanen</button>
                    </div>
                </div>
            </dialog>
        @endif
    </form>
</div>

<script>
(function () {
    var cek = [].slice.call(document.querySelectorAll('.cek'));
    var semua = document.getElementById('pilihSemua');
    var jml = document.getElementById('jml'), jml2 = document.getElementById('jml2');
    var buka = document.getElementById('btnBuka'), dlg = document.getElementById('dlg');
    var pw = document.getElementById('pw'), ok = document.getElementById('btnOk');

    function hitung() {
        var n = 0;
        cek.forEach(function (c) { if (c.checked) n++; c.closest('tr').classList.toggle('sel', c.checked); });
        if (jml) jml.textContent = n;
        if (jml2) jml2.textContent = n;
        if (buka) buka.disabled = n === 0;
        if (semua) { semua.checked = n > 0 && n === cek.length; semua.indeterminate = n > 0 && n < cek.length; }
        return n;
    }
    if (semua) semua.addEventListener('change', function () { cek.forEach(function (c) { c.checked = semua.checked; }); hitung(); });
    cek.forEach(function (c) { c.addEventListener('change', hitung); });
    document.querySelectorAll('tbody tr').forEach(function (tr) {
        tr.addEventListener('click', function (e) {
            if (e.target.tagName === 'INPUT') return;
            var c = tr.querySelector('.cek');
            if (c) { c.checked = !c.checked; hitung(); }
        });
    });
    if (buka) buka.addEventListener('click', function () { pw.value = ''; ok.disabled = true; dlg.showModal(); });
    if (pw) pw.addEventListener('input', function () { ok.disabled = !pw.value.length; });
    var bt = document.getElementById('btnBatal'); if (bt) bt.addEventListener('click', function () { dlg.close(); });
    hitung();
})();
</script>
@endsection