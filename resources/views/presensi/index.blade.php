@extends('layouts.app')

@section('title', 'Presensi')

@section('content')
@include('gaji._nav')
@php
    $hariIni = now()->toDateString();
    $kemarin = $tgl->copy()->subDay()->toDateString();
    $besok   = $tgl->copy()->addDay()->toDateString();
@endphp

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.6rem">
        <div>
            <h2 style="margin:0">Presensi Harian</h2>
            <div class="muted">{{ $tgl->locale('id')->translatedFormat('l, d F Y') }}</div>
        </div>
        <div style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap">
            <a href="{{ route('presensi.index', ['tanggal' => $kemarin]) }}" class="chip" style="text-decoration:none">&laquo; Sebelumnya</a>
            <form method="GET" action="{{ route('presensi.index') }}" style="display:flex;gap:.4rem">
                <input type="date" name="tanggal" value="{{ $tgl->toDateString() }}" max="{{ $hariIni }}" style="padding:.4rem;border:1px solid #cbd5e1;border-radius:8px">
                <button type="submit" class="btn" style="margin:0;padding:.45rem .9rem">Buka</button>
            </form>
            @if ($tgl->toDateString() < $hariIni)
                <a href="{{ route('presensi.index', ['tanggal' => $besok]) }}" class="chip" style="text-decoration:none">Berikutnya &raquo;</a>
            @endif
        </div>
    </div>

    <div style="margin:.8rem 0">
        @if ((float) $prod->k > 0)
            <span class="chip warn">Produksi hari ini: {{ number_format($prod->e, 0, ',', '.') }} ekor &middot; {{ number_format($prod->k, 1, ',', '.') }} kg ayam hidup</span>
        @else
            <span class="chip">Belum ada data produksi pada tanggal ini</span>
        @endif
        <span class="chip">Total kg potong diinput: <span id="total-kg">0</span></span>
    </div>

    <form method="POST" action="{{ route('presensi.store') }}" id="f-presensi">
        @csrf
        <input type="hidden" name="tanggal" value="{{ $tgl->toDateString() }}">
        <div style="overflow-x:auto">
        <table>
            <thead><tr><th>Nama</th><th style="min-width:150px">Status</th><th style="min-width:110px">Kg dipotong</th><th>Keterangan</th></tr></thead>
            <tbody>
                @forelse ($karyawan as $x)
                    @php $a = $ada->get($x->id); $kunci = $terkunci->has($x->id); @endphp
                    <tr>
                        <td><strong>{{ $x->name }}</strong> <span class="chip">{{ $x->type === 'RELAWAN' ? 'Relawan' : 'Karyawan' }}</span>
                            @if ($kunci) <span class="chip warn">Slip terbit</span> @endif</td>
                        <td>
                            <select name="rows[{{ $x->id }}][status]" data-status @disabled($kunci)>
                                <option value="">— belum diisi —</option>
                                @foreach ($status as $k => $v)
                                    <option value="{{ $k }}" @selected(($a?->status) === $k)>{{ $v }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            @if ((float) $x->per_kg_rate > 0)
                                <input type="number" name="rows[{{ $x->id }}][kg_cut]" data-kg min="0" step="0.01" value="{{ $a && (float) $a->kg_cut > 0 ? (float) $a->kg_cut : '' }}" @disabled($kunci)>
                            @else
                                <span class="muted">-</span>
                            @endif
                        </td>
                        <td><input type="text" name="rows[{{ $x->id }}][notes]" maxlength="255" value="{{ $a?->notes }}" @disabled($kunci) style="width:100%;padding:.5rem;border:1px solid #cbd5e1;border-radius:8px"></td>
                    </tr>
                @empty
                    <tr><td colspan="4">Belum ada karyawan aktif. Tambahkan dulu di tab Karyawan.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <button type="submit" class="btn">Simpan Presensi</button>
        <button type="button" id="semua-hadir" class="btn" style="background:#fff;color:#0f172a;border:1px solid #cbd5e1;margin-left:.4rem">Tandai semua hadir</button>
    </form>
</div>

<script>
(function () {
    var form = document.getElementById('f-presensi');
    function total() {
        var t = 0;
        form.querySelectorAll('input[data-kg]:not(:disabled)').forEach(function (i) { t += parseFloat(i.value) || 0; });
        document.getElementById('total-kg').textContent = t.toLocaleString('id-ID', { maximumFractionDigits: 2 });
    }
    document.getElementById('semua-hadir').addEventListener('click', function () {
        form.querySelectorAll('select[data-status]:not(:disabled)').forEach(function (s) { if (!s.value) s.value = 'HADIR'; });
    });
    form.addEventListener('input', total);
    total();
})();
</script>
@endsection