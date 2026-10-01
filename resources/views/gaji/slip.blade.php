@php $set = $slips->first()['set']; @endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Slip Gaji{{ $slips->count() === 1 ? ' ' . ($slips->first()['p']->payroll_number ?: 'Draft') : '' }}</title>
    @if ($set['favicon_path'])<link rel="icon" href="{{ asset('storage/' . $set['favicon_path']) }}">@endif
    <style>
        @page { size: A5 portrait; margin: 9mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #e2e8f0; font-family: system-ui,-apple-system,"Segoe UI",sans-serif; color: #0f172a; font-size: 12px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .bar { position: sticky; top: 0; z-index: 5; background: {{ $set['warna_header'] }}; color: #fff; padding: .6rem 1rem; display: flex; gap: .6rem; align-items: center; justify-content: space-between; }
        .bar a, .bar button { color: #fff; background: transparent; border: 1px solid rgba(255,255,255,.5); padding: .35rem .9rem; border-radius: 6px; text-decoration: none; cursor: pointer; font-size: 13px; }
        .slip { position: relative; background: #fff; width: 148mm; min-height: 210mm; margin: 12px auto; padding: 9mm; box-shadow: 0 2px 10px rgba(15,23,42,.15); page-break-after: always; break-after: page; overflow: hidden; }
        .wm { position: absolute; top: 45%; left: 50%; transform: translate(-50%,-50%) rotate(-25deg); font-size: 44px; font-weight: 800; letter-spacing: .1em; color: rgba(225,29,72,.13); pointer-events: none; white-space: nowrap; }
        .kop { display: flex; gap: 10px; align-items: center; border-bottom: 2px solid {{ $set['warna_header'] }}; padding-bottom: 8px; }
        .kop img { max-height: 46px; max-width: 90px; }
        .nm { font-size: 15px; font-weight: 700; } .sm { font-size: 10.5px; color: #475569; }
        .judul { text-align: center; font-size: 15px; font-weight: 800; letter-spacing: .12em; margin-top: 10px; }
        .sub { text-align: center; color: #475569; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        .id td { padding: 2px 0; } .id td:first-child { width: 70px; color: #64748b; }
        .dua { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 8px; }
        .dua h4 { margin: 0 0 4px; padding: 3px 6px; background: #f1f5f9; font-size: 11px; text-transform: uppercase; letter-spacing: .06em; }
        .rinci td { padding: 3px 2px; vertical-align: top; border-bottom: 1px solid #eef2f6; }
        .rinci td.r { text-align: right; white-space: nowrap; }
        .rinci small { display: block; color: #64748b; font-size: 10px; }
        .tot { display: flex; justify-content: space-between; font-weight: 700; padding: 4px 2px; border-top: 1px solid #94a3b8; margin-top: 2px; }
        .net { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; padding: 8px 10px; background: {{ $set['warna_header'] }}; color: #fff; border-radius: 6px; }
        .net strong { font-size: 17px; }
        .tb { margin-top: 6px; font-size: 11px; } .hadir { margin-top: 6px; color: #475569; font-size: 10.5px; }
        .ttd { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; text-align: center; margin-top: 14px; font-size: 11px; }
        .ttd .sp { height: 54px; display: flex; align-items: center; justify-content: center; } .ttd img { max-height: 54px; max-width: 100%; }
        .ttd .nama { font-weight: 700; border-top: 1px solid #0f172a; padding-top: 2px; margin: 0 6px; } .ttd .jab { color: #64748b; font-size: 10px; }
        .ft { margin-top: 12px; padding-top: 6px; border-top: 1px dashed #94a3b8; font-size: 9.5px; color: #64748b; text-align: center; }
        @media print {
            body { background: #fff; } .noprint { display: none !important; }
            .slip { margin: 0; box-shadow: none; width: auto; min-height: 0; padding: 0; }
            .slip:last-child { page-break-after: auto; break-after: auto; }
        }
    </style>
</head>
<body>
    <div class="bar noprint">
        <span>{{ $slips->count() > 1 ? $slips->count() . ' slip siap cetak' : 'Pratinjau slip' }}</span>
        <span style="display:flex;gap:.5rem">
            <a href="javascript:history.back()">Kembali</a>
            <button onclick="window.print()">Cetak</button>
        </span>
    </div>
    @foreach ($slips as $d)
        @include('gaji._slip', ['d' => $d])
    @endforeach
</body>
</html>