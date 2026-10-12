@extends('layouts.app')

@section('content')
<style>
.hd{max-width:960px;margin:0 auto;padding:12px}
.hd h1{font-size:1.25rem;margin:8px 0 12px;color:#0f172a}
.hd a.lnk{color:#0f766e;font-size:.9rem;text-decoration:none}
.hd .box{background:#fff;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:14px;overflow:hidden}
.hd .scroll{overflow-x:auto}
.hd table{width:100%;border-collapse:collapse;font-size:.85rem}
.hd th{background:#0f172a;color:#fff;text-align:left;padding:8px 10px;white-space:nowrap}
.hd td{padding:8px 10px;border-top:1px solid #f1f5f9;white-space:nowrap}
.hd tbody tr{cursor:pointer}
.hd tbody tr.sel td{background:#fff1f2}
.hd input[type=checkbox]{width:20px;height:20px}
.hd .warn{background:#fff1f2;border:1px solid #fecdd3;color:#9f1239;border-radius:10px;padding:10px 12px;font-size:.88rem;margin-bottom:12px}
.hd .ok{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:10px;padding:10px 12px;font-size:.9rem;margin-bottom:12px}
.hd .lbl{display:block;font-size:.85rem;color:#334155;margin:0 0 4px}
.hd .inp{width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:10px;font-size:16px;box-sizing:border-box;margin-bottom:10px;background:#fff}
.hd .btn-del{width:100%;padding:14px;border:0;border-radius:10px;background:#e11d48;color:#fff;font-size:1rem;font-weight:600;cursor:pointer}
.hd .btn-del:disabled{opacity:.4;cursor:not-allowed}
.hd .btn-f{padding:11px 20px;border:0;border-radius:10px;background:#0f766e;color:#fff;font-size:.95rem;font-weight:600;cursor:pointer}
.hd .pad{padding:14px}
.hd .pg{display:flex;justify-content:space-between;margin:0 0 14px}
.hd .fg{display:grid;grid-template-columns:1fr 1fr;gap:0 10px}
.hd .fg .wide{grid-column:1/-1}
.hd .fb{display:flex;align-items:center;gap:14px;flex-wrap:wrap}
.hd .info{margin-left:auto;color:#64748b;font-size:.85rem}
.hd .cnt{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;font-size:.92rem;color:#0f172a}
.hd .cnt b{font-size:1.1rem;color:#e11d48}

/* perbaikan lebar di HP */
.app-body,main{min-width:0;max-width:100%}
.hd{box-sizing:border-box;width:100%;max-width:100%;min-width:0}
.hd form,.hd .box{min-width:0;max-width:100%}
.hd .scroll{max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}
.hd .fg{grid-template-columns:minmax(0,1fr) minmax(0,1fr)}
.hd .fg input,.hd .fg select{min-width:0;max-width:100%}
.hd .box-h,.hd .row{flex-wrap:wrap}</style>

<div class="hd">
    <a class="lnk" href="{{ route('hapus-data.index') }}">&larr; Kembali</a>
    <h1>Hapus Data: {{ $label }}</h1>

    @if (session('sukses'))
        <div class="ok">{{ session('sukses') }}</div>
    @endif
    @if ($errors->any())
        <div class="warn">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <form method="GET" action="{{ route('hapus-data.show', $tabel) }}" class="box pad">
        <div class="fg">
            @if ($kolomTgl)
                <div>
                    <label class="lbl">Dari tanggal</label>
                    <input type="date" name="dari" value="{{ $dari }}" class="inp">
                </div>
                <div>
                    <label class="lbl">Sampai tanggal</label>
                    <input type="date" name="sampai" value="{{ $sampai }}" class="inp">
                </div>
            @endif
            <div class="wide">
                <label class="lbl">Cari (nama, nomor, catatan, dll)</label>
                <input type="search" name="q" value="{{ $q }}" class="inp" placeholder="mis. INV-20261010 atau nama pelanggan">
            </div>
            <div>
                <label class="lbl">Tampil per halaman</label>
                <select name="per" class="inp">
                    @foreach ([25, 50, 100, 200] as $n)
                        <option value="{{ $n }}" @selected($per === $n)>{{ $n }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="fb">
            <button class="btn-f" type="submit">Terapkan</button>
            <a class="lnk" href="{{ route('hapus-data.show', $tabel) }}">Reset</a>
            <span class="info">{{ $total }} data cocok @if ($kolomTgl) &middot; tanggal: {{ $kolomTgl }} @endif</span>
        </div>
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

        <div class="box scroll">
            <table>
                <thead>
                    <tr>
                        <th style="width:40px"><input type="checkbox" id="pilihSemua" title="Pilih semua di halaman ini"></th>
                        @foreach ($kolom as $k)<th>{{ $k }}</th>@endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($baris as $b)
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="{{ $b->id }}" class="cek"></td>
                            @foreach ($kolom as $k)
                                <td>{{ \Illuminate\Support\Str::limit((string) ($b->$k ?? ''), 40) }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($kolom) + 1 }}">Tidak ada data yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pg">
            <span>@if ($baris->previousPageUrl())<a class="lnk" href="{{ $baris->previousPageUrl() }}">&larr; Sebelumnya</a>@endif</span>
            <span>@if ($baris->nextPageUrl())<a class="lnk" href="{{ $baris->nextPageUrl() }}">Berikutnya &rarr;</a>@endif</span>
        </div>

        @if ($baris->count())
        <div class="box">
            <div class="pad">
                <div class="cnt"><span>Baris dipilih di halaman ini</span><b id="jml">0</b></div>
                <div class="warn">
                    Baris yang dihapus ikut menghapus data turunannya
                    @if (count($turunan))
                        : <strong>{{ implode(', ', $turunan) }}</strong>.
                    @else
                        (tabel ini tidak punya data turunan).
                    @endif
                    Centang hanya baris yang benar-benar mau dihapus.
                </div>
                <label class="lbl">Password Anda</label>
                <input type="password" name="password" class="inp" required autocomplete="current-password">
                <button type="submit" class="btn-del" id="btnDel" disabled>Hapus Permanen 0 Baris Terpilih</button>
            </div>
        </div>
        @endif
    </form>
</div>

<script>
(function () {
    var cek = document.querySelectorAll('.cek');
    var semua = document.getElementById('pilihSemua');
    var jml = document.getElementById('jml');
    var btn = document.getElementById('btnDel');
    var form = document.getElementById('formHapus');

    function hitung() {
        var n = 0;
        cek.forEach(function (c) {
            if (c.checked) n++;
            c.closest('tr').classList.toggle('sel', c.checked);
        });
        if (jml) jml.textContent = n;
        if (btn) { btn.disabled = n === 0; btn.textContent = 'Hapus Permanen ' + n + ' Baris Terpilih'; }
        if (semua) semua.checked = n > 0 && n === cek.length;
        return n;
    }

    if (semua) semua.addEventListener('change', function () {
        cek.forEach(function (c) { c.checked = semua.checked; });
        hitung();
    });
    cek.forEach(function (c) { c.addEventListener('change', hitung); });
    document.querySelectorAll('tbody tr').forEach(function (tr) {
        tr.addEventListener('click', function (e) {
            if (e.target.tagName === 'INPUT') return;
            var c = tr.querySelector('.cek');
            if (c) { c.checked = !c.checked; hitung(); }
        });
    });
    if (form) form.addEventListener('submit', function (e) {
        var n = hitung();
        if (n === 0 || !confirm('Hapus PERMANEN ' + n + ' baris dari ' + form.dataset.label + '? Tidak bisa dibatalkan.')) {
            e.preventDefault();
        }
    });
    hitung();
})();
</script>
@endsection