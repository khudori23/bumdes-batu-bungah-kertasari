@extends('layouts.app')

@section('title', 'Opname Hasil Produksi')

@section('content')
@include('layouts._errors')
@php
    $fm = fn ($v, $d = 2) => number_format((float) $v, $d, ',', '.');
    $stLabel = ['lewat' => 'Lewat batas', 'dekat' => 'Hampir batas', 'aman' => 'Aman'];
    $stChip = ['lewat' => 'down', 'dekat' => 'warn', 'aman' => 'up'];
    $semuaLot = $produk->flatMap(fn ($p) => $p->lot);
    $jmlLewat = $semuaLot->where('status', 'lewat')->count();
    $jmlDekat = $semuaLot->where('status', 'dekat')->count();
    $totalKg = $produk->sum(fn ($p) => (float) $p->stok);
    $terakhir = $riwayat->first()?->adjustment_date;
    $u = auth()->user();
    $bolehFreezer = $u->isAdmin() || $u->isManager() || $u->isKasir();
@endphp

<style>
.opn{--line:rgba(127,127,127,.25);--soft:rgba(127,127,127,.08);--ok:#15803d;--warn:#b45309;--bad:#b91c1c;--acc:#0f766e}
.opn *{box-sizing:border-box}
.opn-head{display:flex;justify-content:space-between;align-items:flex-start;gap:.75rem;flex-wrap:wrap;margin-bottom:1rem}
.opn-head h2{margin:0 0 .2rem;font-size:1.25rem}
.opn-head p{margin:0;opacity:.7;font-size:.9rem}
.opn-link{font-size:.9rem;text-decoration:none;color:var(--acc);font-weight:600;white-space:nowrap}
.opn-stats{display:grid;grid-template-columns:repeat(2,1fr);gap:.6rem;margin-bottom:1rem}
.opn-stat{border:1px solid var(--line);border-radius:12px;padding:.7rem .85rem;background:var(--soft)}
.opn-stat span{display:block;font-size:.75rem;opacity:.7;text-transform:uppercase;letter-spacing:.03em}
.opn-stat strong{display:block;font-size:1.3rem;margin-top:.15rem}
.opn-stat small{opacity:.65;font-size:.78rem}
.opn-alert{border:1px solid var(--bad);color:var(--bad);border-radius:12px;padding:.65rem .85rem;margin-bottom:1rem;font-size:.9rem;font-weight:600}
.opn-tools{display:grid;gap:.6rem;grid-template-columns:1fr;margin-bottom:1rem}
.opn-tools label{display:block;font-size:.78rem;opacity:.7;margin-bottom:.25rem}
.opn-tools input{width:100%}
.opn-list{display:grid;gap:.75rem;grid-template-columns:1fr}
.opn-item{border:1px solid var(--line);border-radius:14px;padding:.9rem;background:Canvas;color:CanvasText}
.opn-item.is-diff{border-color:var(--warn);box-shadow:0 0 0 1px var(--warn) inset}
.opn-item.is-ok{border-color:var(--ok);box-shadow:0 0 0 1px var(--ok) inset}
.opn-top{display:flex;justify-content:space-between;gap:.75rem;align-items:flex-start}
.opn-name{font-weight:700;font-size:1.02rem;line-height:1.25}
.opn-sub{font-size:.8rem;opacity:.65;margin-top:.2rem;display:flex;gap:.4rem;align-items:center;flex-wrap:wrap}
.opn-sys{text-align:right;white-space:nowrap}
.opn-sys span{display:block;font-size:.72rem;opacity:.65;text-transform:uppercase}
.opn-sys strong{font-size:1.2rem}
.opn-lot{margin-top:.6rem;font-size:.85rem}
.opn-lot summary{cursor:pointer;opacity:.75;padding:.2rem 0}
.opn-lot-row{display:flex;justify-content:space-between;gap:.5rem;flex-wrap:wrap;padding:.4rem 0;border-top:1px dashed var(--line)}
.opn-lot-row small{opacity:.65}
.opn-input{margin-top:.75rem;display:grid;grid-template-columns:1fr auto;gap:.5rem;align-items:center}
.opn-input input{width:100%;font-size:1.05rem}
.opn-diff{font-size:.85rem;font-weight:700;min-width:96px;text-align:right}
.is-diff .opn-diff{color:var(--warn)}
.is-ok .opn-diff{color:var(--ok)}
.opn-reason{margin-top:.5rem}
.opn-reason[hidden]{display:none}
.opn-reason input{width:100%}
.opn-bar{position:sticky;bottom:.75rem;z-index:5;margin-top:1rem;display:flex;justify-content:space-between;align-items:center;gap:.75rem;padding:.65rem .85rem;border:1px solid var(--line);border-radius:14px;background:Canvas;color:CanvasText;box-shadow:0 6px 20px rgba(0,0,0,.15)}
.opn-bar .btn{white-space:nowrap}
.opn-note,.opn-empty{opacity:.7;font-size:.9rem}
.opn-sec{margin-top:1.75rem}
.opn-sec h3{margin:0 0 .6rem;font-size:1.05rem}
.opn-hist{max-height:520px;overflow-y:auto;border:1px solid var(--line);border-radius:14px}
.opn-h{display:flex;justify-content:space-between;gap:.75rem;padding:.7rem .85rem;border-bottom:1px solid var(--line)}
.opn-h:last-child{border-bottom:0}
.opn-h-main{display:flex;flex-direction:column;gap:.1rem;min-width:0}
.opn-h-meta{font-size:.78rem;opacity:.65}
.opn-h-num{text-align:right;display:flex;flex-direction:column;align-items:flex-end;gap:.25rem;font-size:.82rem;white-space:nowrap}
@media (min-width:720px){
.opn-stats{grid-template-columns:repeat(4,1fr)}
.opn-tools{grid-template-columns:220px 1fr}
.opn-list{grid-template-columns:repeat(2,1fr)}
}
@media (min-width:1100px){.opn-list{grid-template-columns:repeat(3,1fr)}}
</style>

<div class="opn">
    <div class="opn-head">
        <div>
            <h2>Opname stok hasil produksi</h2>
            <p>Cocokkan stok fisik dengan catatan sistem. Isi hanya produk yang Anda hitung.</p>
        </div>
        @if ($bolehFreezer)
            <a class="opn-link" href="{{ route('freezer.index') }}">Kelola freezer &rarr;</a>
        @endif
    </div>

    <div class="opn-stats">
        <div class="opn-stat"><span>Produk aktif</span><strong>{{ $produk->count() }}</strong></div>
        <div class="opn-stat"><span>Total stok</span><strong>{{ $fm($totalKg) }}</strong><small>kg</small></div>
        <div class="opn-stat"><span>Perlu perhatian</span><strong>{{ $jmlLewat + $jmlDekat }}</strong><small>{{ $jmlLewat }} lewat &middot; {{ $jmlDekat }} hampir</small></div>
        <div class="opn-stat"><span>Opname terakhir</span><strong style="font-size:1.05rem">{{ $terakhir ? $terakhir->format('d/m/Y') : '-' }}</strong></div>
    </div>

    @if ($jmlLewat > 0)
        <div class="opn-alert">{{ $jmlLewat }} stok sudah lewat batas simpan. Periksa kondisinya saat opname.</div>
    @endif

    @if ($bolehTulis)
    <form method="POST" action="{{ route('opnameproduk.store') }}">
        @csrf
        <div class="opn-tools">
            <div>
                <label for="opnTgl">Tanggal opname</label>
                <input id="opnTgl" type="date" name="adjustment_date" value="{{ old('adjustment_date', now()->toDateString()) }}" min="{{ \App\Support\Pencatatan::mulai() }}" max="{{ now()->toDateString() }}" required>
            </div>
            <div>
                <label for="opnCari">Cari produk</label>
                <input id="opnCari" type="search" placeholder="Ketik nama produk..." autocomplete="off">
            </div>
        </div>
    @endif

        <div class="opn-list">
            @forelse ($produk as $p)
                <div class="opn-item" data-nama="{{ mb_strtolower($p->name) }}" data-stok="{{ number_format((float) $p->stok, 2, '.', '') }}">
                    <div class="opn-top">
                        <div>
                            <div class="opn-name">{{ $p->name }}</div>
                            <div class="opn-sub">
                                <span>Batas simpan {{ $p->batas }} hari</span>
                                @if (isset($stLabel[$p->status]))
                                    <span class="chip {{ $stChip[$p->status] }}">{{ $stLabel[$p->status] }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="opn-sys"><span>Sistem</span><strong>{{ $fm($p->stok) }}</strong> kg</div>
                    </div>

                    @if ($p->lot->isNotEmpty())
                        <details class="opn-lot">
                            <summary>Rincian freezer ({{ $p->lot->count() }})</summary>
                            @foreach ($p->lot as $l)
                                <div class="opn-lot-row">
                                    <span>{{ $l->freezer }}: <strong>{{ $fm($l->kg) }} kg</strong> <small>masuk {{ $l->tgl->format('d/m') }} &middot; {{ $l->umur }} hari</small></span>
                                    <span class="chip {{ $stChip[$l->status] }}">{{ $stLabel[$l->status] }}</span>
                                </div>
                            @endforeach
                        </details>
                    @else
                        <div class="opn-sub" style="margin-top:.5rem">Tidak ada stok tersimpan</div>
                    @endif

                    @if ($bolehTulis)
                        <div class="opn-input">
                            <input type="number" inputmode="decimal" step="0.01" min="0" name="fisik[{{ $p->id }}]" value="{{ old('fisik.' . $p->id) }}" placeholder="Stok fisik (kg)" aria-label="Stok fisik {{ $p->name }}">
                            <div class="opn-diff"></div>
                        </div>
                        <div class="opn-reason" hidden>
                            <input type="text" maxlength="255" name="alasan[{{ $p->id }}]" value="{{ old('alasan.' . $p->id) }}" placeholder="Alasan selisih (min. 5 huruf)" aria-label="Alasan {{ $p->name }}">
                        </div>
                    @endif
                </div>
            @empty
                <p class="opn-empty">Belum ada produk aktif.</p>
            @endforelse
        </div>

    @if ($bolehTulis)
        <div class="opn-bar">
            <div><strong id="opnCount">0</strong> produk diisi</div>
            <button class="btn" type="submit">Simpan opname</button>
        </div>
    </form>
    @else
        <p class="opn-note">Anda hanya dapat melihat data ini.</p>
    @endif

    <div class="opn-sec">
        <h3>Riwayat opname</h3>
        <div class="opn-hist">
            @forelse ($riwayat as $r)
                <div class="opn-h">
                    <div class="opn-h-main">
                        <strong>{{ $r->product?->name }}</strong>
                        <span class="opn-h-meta">{{ $r->adjustment_date->format('d/m/Y') }} &middot; {{ $r->adjustedBy?->name }}</span>
                        @if ($r->reason && $r->reason !== 'Sesuai')
                            <span class="opn-h-meta">&ldquo;{{ $r->reason }}&rdquo;</span>
                        @endif
                    </div>
                    <div class="opn-h-num">
                        <span>{{ number_format($r->system_qty, 2, ',', '.') }} &rarr; {{ number_format($r->physical_qty, 2, ',', '.') }} kg</span>
                        <span class="chip {{ $r->difference_qty < 0 ? 'down' : ($r->difference_qty > 0 ? 'warn' : 'up') }}">{{ $r->difference_qty > 0 ? '+' : '' }}{{ number_format($r->difference_qty, 2, ',', '.') }}</span>
                    </div>
                </div>
            @empty
                <div class="opn-h"><span class="opn-h-meta">Belum ada opname.</span></div>
            @endforelse
        </div>
    </div>
</div>

@if ($bolehTulis)
<script>
(function () {
    var items = document.querySelectorAll('.opn-item');
    var counter = document.getElementById('opnCount');
    var cari = document.getElementById('opnCari');

    function fmt(n) {
        return (n > 0 ? '+' : '') + n.toFixed(2).replace('.', ',');
    }

    function upd(it) {
        var inp = it.querySelector('input[name^="fisik"]');
        if (!inp) { return false; }
        var diff = it.querySelector('.opn-diff');
        var rs = it.querySelector('.opn-reason');
        it.classList.remove('is-ok', 'is-diff');
        if (inp.value === '' || isNaN(parseFloat(inp.value))) {
            diff.textContent = '';
            rs.hidden = true;
            return false;
        }
        var d = Math.round((parseFloat(inp.value) - parseFloat(it.dataset.stok)) * 100) / 100;
        if (Math.abs(d) < 0.005) {
            diff.textContent = 'Sesuai';
            it.classList.add('is-ok');
            rs.hidden = true;
        } else {
            diff.textContent = fmt(d) + ' kg';
            it.classList.add('is-diff');
            rs.hidden = false;
        }
        return true;
    }

    function semua() {
        var n = 0;
        items.forEach(function (it) { if (upd(it)) { n++; } });
        counter.textContent = n;
    }

    document.addEventListener('input', function (e) {
        if (e.target.name && e.target.name.indexOf('fisik') === 0) { semua(); }
    });

    if (cari) {
        cari.addEventListener('input', function () {
            var q = cari.value.toLowerCase().trim();
            items.forEach(function (it) {
                it.style.display = (q === '' || it.dataset.nama.indexOf(q) !== -1) ? '' : 'none';
            });
        });
    }

    semua();
})();
</script>
@endif
@endsection