@php
    $set = \App\Models\Setting::semua();
    $rp  = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $num = fn ($n, $d = 0) => number_format((float) $n, $d, ',', '.');
    $pct = fn ($n) => $n === null ? '-' : number_format($n, 1, ',', '.') . '%';
    $banding = function ($now, $prev) {
        if ($prev == 0) return 'tidak tersedia pembanding pada periode sebelumnya';
        $x = ($now - $prev) / abs($prev) * 100;
        return ($x >= 0 ? 'naik ' : 'turun ') . number_format(abs($x), 1, ',', '.') . '% dibandingkan periode sebelumnya';
    };
    $r = $d['ringkas'];
    $namaJenis = strtolower($jenisList[$jenis]);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan {{ $jenisList[$jenis] }} {{ $from->format('d-m-Y') }}</title>
    <style>
        @page { size: A4 portrait; margin: 14mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #e2e8f0; font-family: "Segoe UI", system-ui, sans-serif; color: #0f172a; font-size: 12px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .bar { position: sticky; top: 0; z-index: 5; background: {{ $set['warna_header'] }}; color: #fff; padding: .6rem 1rem; display: flex; gap: .6rem; align-items: center; justify-content: space-between; flex-wrap: wrap; }
        .bar form { display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; }
        .bar select, .bar input { padding: .35rem .5rem; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px; }
        .bar a, .bar button { color: #fff; background: transparent; border: 1px solid rgba(255,255,255,.5); padding: .35rem .9rem; border-radius: 6px; text-decoration: none; cursor: pointer; font-size: 13px; }
        .page { background: #fff; max-width: 210mm; margin: 12px auto; padding: 14mm; box-shadow: 0 2px 10px rgba(15,23,42,.15); }
        .kop { display: flex; gap: 12px; align-items: center; border-bottom: 3px double {{ $set['warna_header'] }}; padding-bottom: 8px; }
        .kop img { max-height: 54px; max-width: 100px; }
        .kop .nm { font-size: 17px; font-weight: 700; } .kop .sm { font-size: 11px; color: #475569; }
        .judul { text-align: center; margin: 14px 0 2px; font-size: 15px; font-weight: 800; letter-spacing: .1em; }
        .sub { text-align: center; color: #475569; margin-bottom: 4px; }
        .meta { text-align: center; color: #64748b; font-size: 10.5px; margin-bottom: 12px; }
        h3 { margin: 16px 0 6px; font-size: 12.5px; text-transform: uppercase; letter-spacing: .05em; background: #f1f5f9; padding: 4px 8px; border-left: 4px solid {{ $set['warna_header'] }}; }
        p { line-height: 1.55; margin: 4px 0; text-align: justify; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th, td { border: .5pt solid #cbd5e1; padding: 4px 7px; text-align: left; vertical-align: top; }
        th { background: #e2e8f0; font-size: 11px; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        td.r, th.r { text-align: right; white-space: nowrap; }
        tfoot td { font-weight: 700; background: #e2e8f0; }
        .kpi { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; margin: 8px 0; }
        .kpi div { border: .5pt solid #cbd5e1; border-radius: 4px; padding: 6px 8px; }
        .kpi small { display: block; color: #64748b; font-size: 10px; text-transform: uppercase; letter-spacing: .04em; }
        .kpi strong { font-size: 14px; }
        .neg { color: #be123c; } .pos { color: #0f766e; }
        .warn { background: #fef3c7; border: .5pt solid #fcd34d; color: #92400e; padding: 4px 8px; margin-top: 6px; font-weight: 600; }
        .catatan { color: #64748b; font-size: 10.5px; margin-top: 4px; }
        .ttd { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; text-align: center; margin-top: 26px; page-break-inside: avoid; }
        .ttd .sp { height: 60px; }
        .ttd .nama { border-top: 1px solid #0f172a; margin: 0 14px; padding-top: 3px; font-weight: 700; min-height: 18px; }
        .ttd .jab { color: #64748b; font-size: 10.5px; }
        h3, table, .kpi { page-break-inside: avoid; }
        @media print { body { background: #fff; } .noprint { display: none !important; } .page { margin: 0; padding: 0; box-shadow: none; max-width: none; } }
    </style>
</head>
<body>
    <div class="bar noprint">
        <form method="GET" action="{{ route('laporan.index') }}">
            <select name="jenis">
                @foreach ($jenisList as $k => $v)
                    <option value="{{ $k }}" @selected($jenis === $k)>{{ $v }}</option>
                @endforeach
            </select>
            <input type="date" name="tanggal" value="{{ $tanggal }}">
            <button type="submit">Tampilkan</button>
        </form>
        <span style="display:flex;gap:.5rem">
            <a href="{{ route('dashboard') }}">Kembali</a>
            <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
        </span>
    </div>

    <div class="page">
        <div class="kop">
            @if ($set['logo_path'])<img src="{{ asset('storage/' . $set['logo_path']) }}" alt="">@endif
            <div>
                <div class="nm">{{ $set['app_name'] }}</div>
                @if ($set['address'])<div class="sm">{{ $set['address'] }}</div>@endif
                @if ($set['phone'])<div class="sm">Telp. {{ $set['phone'] }}</div>@endif
            </div>
        </div>

        <div class="judul">LAPORAN {{ strtoupper($jenisList[$jenis]) }} USAHA PEMOTONGAN AYAM</div>
        <div class="sub">Periode: {{ $label }}</div>
        <div class="meta">Nomor: {{ $nomor }} &middot; Disusun: {{ $dibuat }} &middot; Oleh: {{ auth()->user()->name }}</div>

        <h3>1. Ringkasan Eksekutif</h3>
        <div class="kpi">
            <div><small>Omzet</small><strong>{{ $rp($r['omzet']) }}</strong></div>
            <div><small>Pembelian ayam</small><strong>{{ $rp($r['pembelian']) }}</strong></div>
            <div><small>Pengeluaran</small><strong>{{ $rp($r['pengeluaran']) }}</strong></div>
            <div><small>Laba (kas sederhana)</small><strong class="{{ $r['laba'] < 0 ? 'neg' : 'pos' }}">{{ $rp($r['laba']) }}</strong></div>
        </div>
        <p>
            Pada laporan {{ $namaJenis }} periode {{ $label }}, tercatat {{ $num($r['nota']) }} nota penjualan dengan omzet
            sebesar {{ $rp($r['omzet']) }} ({{ $banding($r['omzet'], $prev['omzet']) }}, yaitu {{ $rp($prev['omzet']) }}).
            Pembelian ayam hidup sebesar {{ $rp($r['pembelian']) }} dan pengeluaran operasional sebesar {{ $rp($r['pengeluaran']) }}.
            Dengan demikian laba berdasarkan hitungan kas sederhana adalah {{ $rp($r['laba']) }}
            ({{ $banding($r['laba'], $prev['laba']) }}, yaitu {{ $rp($prev['laba']) }}).
        </p>
        <div class="catatan">Laba = omzet &minus; pembelian ayam &minus; pengeluaran (termasuk gaji yang dibayar). Stok yang belum terjual belum diperhitungkan.</div>

        <h3>2. Penjualan dan Penerimaan Kas</h3>
        <table>
            <tbody>
                <tr><td>Jumlah nota (tidak termasuk yang dibatalkan)</td><td class="r">{{ $num($r['nota']) }}</td></tr>
                <tr><td>Omzet</td><td class="r">{{ $rp($r['omzet']) }}</td></tr>
                <tr><td>Diskon yang diberikan</td><td class="r">{{ $rp($r['diskon']) }}</td></tr>
                <tr><td>Penerimaan tunai</td><td class="r">{{ $rp($d['kas']['tunai']) }}</td></tr>
                <tr><td>Penerimaan transfer</td><td class="r">{{ $rp($d['kas']['transfer']) }}</td></tr>
            </tbody>
            <tfoot><tr><td>Total penerimaan kas pada periode</td><td class="r">{{ $rp($d['kas']['total']) }}</td></tr></tfoot>
        </table>
        <div class="catatan">Penerimaan kas dihitung berdasarkan tanggal uang diterima, termasuk pelunasan hutang nota lama.</div>

        @if ($d['produkJual']->isNotEmpty())
            <table style="margin-top:8px">
                <thead><tr><th>Produk terjual</th><th class="r">Jumlah</th><th class="r">Nilai (sebelum diskon)</th></tr></thead>
                <tbody>
                    @foreach ($d['produkJual'] as $p)
                        <tr><td>{{ $p->name }}</td><td class="r">{{ $num($p->qty, 2) }} {{ strtolower($p->unit) }}</td><td class="r">{{ $rp($p->nilai) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <h3>3. Piutang Pelanggan</h3>
        <table>
            <tbody>
                <tr><td>Hutang baru dari nota pada periode ini</td><td class="r">{{ $rp($d['piutang']['baru']) }}</td></tr>
                <tr><td>Pelunasan hutang nota sebelum periode ini</td><td class="r">{{ $rp($d['piutang']['pelunasan']) }}</td></tr>
                <tr><td>Sisa piutang seluruh pelanggan (per saat laporan dibuka)</td><td class="r">{{ $rp($d['piutang']['sisa']) }}</td></tr>
                <tr><td>Pelanggan melewati jatuh tempo</td><td class="r">{{ $num($d['piutang']['telatN']) }} pelanggan, {{ $rp($d['piutang']['telatJumlah']) }}</td></tr>
            </tbody>
        </table>

        <h3>4. Pembelian Ayam Hidup</h3>
        <table>
            <tbody>
                <tr><td>Jumlah transaksi pembelian</td><td class="r">{{ $num($d['beli']->n) }}</td></tr>
                <tr><td>Jumlah ayam dibeli</td><td class="r">{{ $num($d['beli']->ekor) }} ekor</td></tr>
                <tr><td>Berat total</td><td class="r">{{ $num($d['beli']->kg, 2) }} kg</td></tr>
                <tr><td>Nilai pembelian</td><td class="r">{{ $rp($d['beli']->biaya) }}</td></tr>
            </tbody>
        </table>

        <h3>5. Ayam Mati</h3>
        <table>
            <tbody>
                <tr><td>Jumlah ayam mati</td><td class="r">{{ $num($d['mati']->ekor) }} ekor</td></tr>
                <tr><td>Berat ayam mati</td><td class="r">{{ $num($d['mati']->kg, 2) }} kg</td></tr>
                <tr><td>Persentase terhadap ayam dibeli pada periode yang sama</td><td class="r">{{ $pct($d['matiPersen']) }}</td></tr>
            </tbody>
        </table>

        <h3>6. Produksi dan Rendemen</h3>
        <table>
            <tbody>
                <tr><td>Jumlah batch produksi</td><td class="r">{{ $num($d['prod']->batch) }}</td></tr>
                <tr><td>Ayam dipotong</td><td class="r">{{ $num($d['prod']->ekor) }} ekor ({{ $num($d['prod']->kg, 2) }} kg hidup)</td></tr>
                <tr><td>Total hasil produksi</td><td class="r">{{ $num($d['hasilKg'], 2) }} kg</td></tr>
            </tbody>
            <tfoot><tr><td>Rendemen (hasil &divide; berat ayam hidup)</td><td class="r">{{ $pct($d['rendemen']) }}</td></tr></tfoot>
        </table>
        @if ($d['rendemen'] !== null && $d['rendemen'] < 65)
            <div class="warn">PERHATIAN: Rendemen di bawah 65% (kisaran normal 68-75%). Perlu pemeriksaan penyusutan dan penimbangan.</div>
        @endif
        @if ($d['hasilProduk']->isNotEmpty())
            <table style="margin-top:8px">
                <thead><tr><th>Hasil per produk</th><th class="r">Berat (kg)</th></tr></thead>
                <tbody>
                    @foreach ($d['hasilProduk'] as $p)
                        <tr><td>{{ $p->name }}</td><td class="r">{{ $num($p->kg, 2) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <h3>7. Pengeluaran Operasional</h3>
        <table>
            <thead><tr><th>Kategori</th><th class="r">Catatan</th><th class="r">Jumlah</th></tr></thead>
            <tbody>
                @forelse ($d['exp'] as $e)
                    <tr><td>{{ $e->category }}</td><td class="r">{{ $num($e->n) }}</td><td class="r">{{ $rp($e->total) }}</td></tr>
                @empty
                    <tr><td colspan="3">Tidak ada pengeluaran pada periode ini.</td></tr>
                @endforelse
            </tbody>
            <tfoot><tr><td colspan="2">Total pengeluaran</td><td class="r">{{ $rp($r['pengeluaran']) }}</td></tr></tfoot>
        </table>

        @if ($d['gaji'])
            <h3>8. Penggajian</h3>
            <table>
                <tbody>
                    <tr><td>Jumlah slip gaji</td><td class="r">{{ $num($d['gaji']['slip']) }}</td></tr>
                    <tr><td>Total gaji kotor</td><td class="r">{{ $rp($d['gaji']['bruto']) }}</td></tr>
                    <tr><td>Total gaji bersih</td><td class="r">{{ $rp($d['gaji']['bersih']) }}</td></tr>
                    <tr><td>Sudah dibayar</td><td class="r">{{ $rp($d['gaji']['dibayar']) }}</td></tr>
                    <tr><td>Diterbitkan, belum dibayar</td><td class="r">{{ $rp($d['gaji']['belum']) }}</td></tr>
                </tbody>
            </table>
        @endif

        <div class="ttd">
            <div>Dibuat oleh,<div class="sp"></div><div class="nama">{{ auth()->user()->name }}</div><div class="jab">{{ auth()->user()->isAdmin() ? 'Admin Operasional' : 'Manager' }}</div></div>
            <div>Diperiksa oleh,<div class="sp"></div><div class="nama">&nbsp;</div><div class="jab">Manager</div></div>
            <div>Disetujui oleh,<div class="sp"></div><div class="nama">{{ $set['slip_manager_name'] ?? '' }}</div><div class="jab">{{ $set['slip_manager_title'] ?? 'Direktur' }}</div></div>
        </div>
    </div>
</body>
</html>