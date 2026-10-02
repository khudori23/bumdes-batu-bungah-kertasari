@extends('layouts.app')

@section('title', 'Presensi')

@section('content')
@include('gaji._nav')
@php
    $hariIni = now()->toDateString();
    $kemarin = $tgl->copy()->subDay()->toDateString();
    $besok   = $tgl->copy()->addDay()->toDateString();
    $rp      = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $tutup   = $hari !== null;
    $bag     = $est['bagian'];
    $perPoin = $est['total_poin'] > 0 ? $est['pool_rp'] / $est['total_poin'] : 0;
    $terkunciHari = $tutup && $pesertaTerkunci->isNotEmpty();
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

    <div style="margin:.8rem 0;display:flex;gap:.4rem;flex-wrap:wrap">
        @if ((float) $prod->k > 0)
            <span class="chip">Ayam masuk produksi: {{ number_format($prod->e, 0, ',', '.') }} ekor &middot; {{ number_format($prod->k, 1, ',', '.') }} kg</span>
        @else
            <span class="chip">Belum ada data produksi pada tanggal ini</span>
        @endif
        <span class="chip warn">Hasil produksi: {{ number_format($est['pool_kg'], 1, ',', '.') }} kg &rarr; pool {{ $rp($est['pool_rp']) }} (Rp {{ number_format($est['tarif'], 0, ',', '.') }}/kg)</span>
        <span class="chip">{{ $est['peserta'] }} orang hadir &middot; {{ number_format($est['total_poin'], 1, ',', '.') }} poin &middot; &asymp; {{ $rp($perPoin) }}/poin</span>
        @if ($tutup)
            <span class="chip">Sudah dibagi {{ $hari->closed_at->timezone(config('app.timezone'))->format('d/m H:i') }}</span>
        @else
            <span class="chip">Belum dibagi &middot; angka di tabel adalah estimasi dari presensi yang tersimpan</span>
        @endif
        @if ($terkunciHari)
            <span class="chip warn">Terkunci: slip {{ $pesertaTerkunci->implode(', ') }} sudah terbit</span>
        @endif
    </div>

    @if ($est['pool_kg'] <= 0)
        <div class="muted" style="margin-bottom:.6rem">Hasil produksi tanggal ini belum diinput, jadi pembagian per kg belum bisa dihitung.</div>
    @endif

    <form method="POST" action="{{ route('presensi.store') }}" id="f-presensi">
        @csrf
        <input type="hidden" name="tanggal" value="{{ $tgl->toDateString() }}">
        <div style="overflow-x:auto">
        <table>
            <thead><tr><th>Nama</th><th style="min-width:150px">Status</th><th style="text-align:right">Poin</th><th style="text-align:right">Bagian per kg{{ $tutup ? '' : ' (estimasi)' }}</th><th>Keterangan</th></tr></thead>
            <tbody>
                @forelse ($karyawan as $x)
                    @php
                        $a = $ada->get($x->id);
                        $kunci = $terkunci->has($x->id) || $terkunciHari;
                        if ($tutup) { $poin = (float) ($a?->poin ?? 0); $bg = (float) ($a?->bagian_rp ?? 0); }
                        else { $poin = (float) ($bag[$x->id]['poin'] ?? 0); $bg = (float) ($bag[$x->id]['rp'] ?? 0); }
                    @endphp
                    <tr>
                        <td><strong>{{ $x->name }}</strong> <span class="chip">{{ $x->type === 'RELAWAN' ? 'Relawan' : 'Karyawan' }}</span>
                            @if ($terkunci->has($x->id)) <span class="chip warn">Slip terbit</span> @endif
                            @if ($a?->clock_in_at) <span class="chip {{ $a->clock_out_at ? '' : 'warn' }}">Masuk {{ $a->clock_in_at->timezone(config('app.timezone'))->format('H:i') }}@if ($a->clock_out_at) &middot; Pulang {{ $a->clock_out_at->timezone(config('app.timezone'))->format('H:i') }}@else &middot; belum pulang, isi status manual @endif</span> @endif</td>
                        <td>
                            <select name="rows[{{ $x->id }}][status]" data-status @disabled($kunci)>
                                <option value="">&mdash; belum diisi &mdash;</option>
                                @foreach ($status as $k => $v)
                                    <option value="{{ $k }}" @selected(($a?->status) === $k)>{{ $v }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td style="text-align:right">{{ $poin > 0 ? rtrim(rtrim(number_format($poin, 2, ',', '.'), '0'), ',') : '-' }}</td>
                        <td style="text-align:right">{{ $bg > 0 ? $rp($bg) : '-' }}</td>
                        <td><input type="text" name="rows[{{ $x->id }}][notes]" maxlength="255" value="{{ $a?->notes }}" @disabled($kunci) style="width:100%;padding:.5rem;border:1px solid #cbd5e1;border-radius:8px"></td>
                    </tr>
                @empty
                    <tr><td colspan="5">Belum ada karyawan aktif. Tambahkan dulu di tab Karyawan.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <button type="submit" class="btn" @disabled($terkunciHari)>Simpan Presensi</button>
        <button type="button" id="semua-hadir" class="btn" style="background:#fff;color:#0f172a;border:1px solid #cbd5e1;margin-left:.4rem">Tandai semua hadir</button>
    </form>

    <form method="POST" action="{{ route('presensi.tutup') }}" style="margin-top:.8rem">
        @csrf
        <input type="hidden" name="tanggal" value="{{ $tgl->toDateString() }}">
        <button type="submit" class="btn" style="margin:0" @disabled($terkunciHari)>{{ $tutup ? 'Hitung ulang pembagian' : 'Bagi per kg &amp; tutup hari' }}</button>
        <span class="muted" style="margin-left:.5rem">Simpan presensi dulu, baru bagi. Bagian per kg masuk ke slip gaji setelah hari ditutup.</span>
    </form>
</div>

<script>
(function () {
    var form = document.getElementById('f-presensi');
    document.getElementById('semua-hadir').addEventListener('click', function () {
        form.querySelectorAll('select[data-status]:not(:disabled)').forEach(function (s) { if (!s.value) s.value = 'HADIR'; });
    });
})();
</script>
@endsection