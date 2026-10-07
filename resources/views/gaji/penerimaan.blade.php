<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Daftar Penerimaan Gaji {{ $label }}</title>
<style>
  @page { size: A4 landscape; margin: 12mm; }
  * { box-sizing: border-box; }
  body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #111; margin: 0; }
  h1 { font-size: 15px; margin: 0; text-align: center; text-transform: uppercase; }
  .sub { text-align: center; margin: 2px 0 10px; }
  table { width: 100%; border-collapse: collapse; }
  th, td { border: 0.5pt solid #444; padding: 4px 6px; }
  th { background: #e5e7eb; text-align: center; }
  td.r { text-align: right; white-space: nowrap; }
  td.c { text-align: center; }
  tr { page-break-inside: avoid; }
  td.ttd { height: 30px; width: 130px; }
  tfoot td { font-weight: bold; background: #f3f4f6; }
  .ttd-box { margin-top: 18px; width: 100%; page-break-inside: avoid; }
  .ttd-box td { border: 0; text-align: center; width: 50%; }
  .noprint { margin-bottom: 10px; }
  @media print { .noprint { display: none; } }
</style>
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#15803d">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
</head>
<body>
<div class="noprint"><button onclick="window.print()">Cetak</button></div>

<h1>Daftar Penerimaan Gaji</h1>
<div class="sub">{{ $set['app_name'] }} &middot; Periode {{ $label }}</div>

<table>
  <thead>
    <tr>
      <th style="width:28px">No</th>
      <th>No. Slip</th>
      <th>Nama</th>
      <th>Jenis</th>
      <th>Hari</th>
      <th>Kg Potong</th>
      <th>Gaji Kotor (Rp)</th>
      <th>Potongan (Rp)</th>
      <th>Diterima (Rp)</th>
      <th>Tanda Tangan Penerima</th>
    </tr>
  </thead>
  <tbody>
    @php $tK = $tP = $tN = 0; @endphp
    @foreach ($list as $i => $p)
      @php
        $pot = (float) $p->other_deduction_amount + (float) $p->loan_deduction_amount;
        $tK += (float) $p->gross_amount; $tP += $pot; $tN += (float) $p->net_amount;
      @endphp
      <tr>
        <td class="c">{{ $i + 1 }}</td>
        <td>{{ $p->payroll_number }}</td>
        <td>{{ $p->employee->name }}</td>
        <td class="c">{{ $p->employee->type }}</td>
        <td class="c">{{ rtrim(rtrim(number_format((float) $p->days_present, 1, ',', '.'), '0'), ',') }}</td>
        <td class="r">{{ number_format((float) $p->kg_total, 2, ',', '.') }}</td>
        <td class="r">{{ number_format((float) $p->gross_amount, 0, ',', '.') }}</td>
        <td class="r">{{ number_format($pot, 0, ',', '.') }}</td>
        <td class="r"><strong>{{ number_format((float) $p->net_amount, 0, ',', '.') }}</strong></td>
        <td class="ttd"></td>
      </tr>
    @endforeach
  </tbody>
  <tfoot>
    <tr>
      <td colspan="6" class="r">TOTAL</td>
      <td class="r">{{ number_format($tK, 0, ',', '.') }}</td>
      <td class="r">{{ number_format($tP, 0, ',', '.') }}</td>
      <td class="r">{{ number_format($tN, 0, ',', '.') }}</td>
      <td></td>
    </tr>
  </tfoot>
</table>

<table class="ttd-box">
  <tr>
    <td>Dibuat oleh,<br><br><br><br>( ______________________ )<br>Admin</td>
    <td>Disetujui oleh,<br><br><br><br>
      <strong>{{ $set['slip_manager_name'] ?? '' }}</strong><br>{{ $set['slip_manager_title'] ?? 'Manager' }}</td>
  </tr>
</table>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () { navigator.serviceWorker.register('/sw.js'); });
        }
    </script>
</body>
</html>