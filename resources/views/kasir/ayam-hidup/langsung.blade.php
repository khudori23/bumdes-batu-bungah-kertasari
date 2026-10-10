<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Kasir Ayam Hidup</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
<style>
@verbatim
:root{--bg:#060a13;--s1:rgba(255,255,255,.045);--s2:rgba(255,255,255,.08);--bd:rgba(255,255,255,.09);--tx:#e8eefb;--mu:#7f8ca6;--ac:#22d3ee;--ac2:#34d399;--vi:#8b5cf6;--bad:#fb7185;--warn:#fbbf24;--r:18px}
*{box-sizing:border-box;margin:0;-webkit-tap-highlight-color:transparent}
html{color-scheme:dark}
body{font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:var(--bg);color:var(--tx);min-height:100vh;padding-bottom:calc(120px + env(safe-area-inset-bottom));
background-image:radial-gradient(900px 500px at 85% -10%,rgba(34,211,238,.14),transparent 60%),radial-gradient(700px 500px at -10% 20%,rgba(139,92,246,.14),transparent 60%),linear-gradient(rgba(255,255,255,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.025) 1px,transparent 1px);background-size:auto,auto,36px 36px,36px 36px;background-attachment:fixed}
.mono{font-family:"JetBrains Mono",ui-monospace,Menlo,monospace;font-variant-numeric:tabular-nums}
.wrap{max-width:1080px;margin:0 auto;padding:calc(14px + env(safe-area-inset-top)) 16px 0}
.top{display:flex;align-items:center;gap:12px;margin-bottom:18px}
.back{width:42px;height:42px;border-radius:14px;background:var(--s1);border:1px solid var(--bd);display:grid;place-items:center;color:var(--tx);text-decoration:none;font-size:18px}
.top h1{font-size:20px;font-weight:700;letter-spacing:-.01em}
.top small{display:block;color:var(--mu);font-size:12px;margin-top:2px}
.live{margin-left:auto;display:flex;align-items:center;gap:8px;font-size:12px;color:var(--ac2);background:rgba(52,211,153,.08);border:1px solid rgba(52,211,153,.25);padding:7px 12px;border-radius:999px}
.live i{width:7px;height:7px;border-radius:50%;background:var(--ac2);box-shadow:0 0 10px var(--ac2);animation:p 1.8s infinite}
@keyframes p{50%{opacity:.35}}
.grid{display:grid;gap:16px}
@media(min-width:900px){.grid{grid-template-columns:1.15fr .85fr;align-items:start}.side{position:sticky;top:16px}}
.card{background:var(--s1);border:1px solid var(--bd);border-radius:var(--r);padding:18px;backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);margin-bottom:16px;box-shadow:0 10px 40px rgba(0,0,0,.35)}
.card h2{font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:var(--mu);font-weight:600;margin-bottom:14px;display:flex;align-items:center;gap:8px}
.card h2::before{content:"";width:16px;height:2px;background:linear-gradient(90deg,var(--ac),transparent);border-radius:2px}
label.f{display:block;font-size:12px;color:var(--mu);margin:0 0 6px;font-weight:500}
.in{width:100%;background:rgba(0,0,0,.35);border:1px solid var(--bd);color:var(--tx);border-radius:14px;padding:13px 14px;font-size:16px;font-family:inherit;outline:0;transition:.18s}
.in:focus{border-color:var(--ac);box-shadow:0 0 0 3px rgba(34,211,238,.16)}
.in.big{font-size:26px;font-weight:700;padding:14px 16px}
.row{display:grid;gap:12px}.row.c2{grid-template-columns:1fr 1fr}
.sp{height:14px}
.chips{display:flex;gap:8px;flex-wrap:wrap}
.chip{position:relative;flex:1 1 140px}
.chip input{position:absolute;opacity:0;inset:0;cursor:pointer}
.chip span{display:block;padding:11px 14px;border-radius:14px;background:rgba(0,0,0,.3);border:1px solid var(--bd);transition:.18s}
.chip b{display:block;font-size:14px;font-weight:600}
.chip small{display:block;font-size:12px;color:var(--mu);margin-top:2px}
.chip input:checked+span{border-color:var(--ac);background:linear-gradient(135deg,rgba(34,211,238,.16),rgba(139,92,246,.12));box-shadow:0 0 22px rgba(34,211,238,.18)}
.chip input:checked+span small{color:var(--ac)}
.chip.off span{opacity:.5}
.step{display:flex;gap:8px}
.step button{width:52px;border-radius:14px;border:1px solid var(--bd);background:var(--s2);color:var(--tx);font-size:22px;cursor:pointer}
.step button:active{transform:scale(.94)}
.hint{font-size:12px;color:var(--mu);margin-top:7px;min-height:16px}
.hint.bad{color:var(--bad)}.hint.ok{color:var(--ac2)}.hint.warn{color:var(--warn)}
.tag{display:inline-block;font-size:11px;padding:3px 9px;border-radius:999px;background:rgba(251,191,36,.12);color:var(--warn);border:1px solid rgba(251,191,36,.3);margin-top:8px}
.seg{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
.seg label{position:relative}
.seg input{position:absolute;opacity:0;inset:0;cursor:pointer}
.seg span{display:block;text-align:center;padding:12px 6px;border-radius:14px;background:rgba(0,0,0,.3);border:1px solid var(--bd);font-weight:600;font-size:14px;transition:.18s}
.seg input:checked+span{background:linear-gradient(135deg,var(--ac),var(--vi));border-color:transparent;color:#04101a;box-shadow:0 6px 22px rgba(34,211,238,.28)}
.quick{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
.quick button{flex:1 1 auto;padding:9px 12px;border-radius:12px;border:1px solid var(--bd);background:var(--s2);color:var(--tx);font-size:13px;font-weight:600;cursor:pointer}
.quick button:active{transform:scale(.96)}
.sum .ln{display:flex;justify-content:space-between;align-items:baseline;padding:9px 0;border-bottom:1px dashed var(--bd);font-size:14px;color:var(--mu)}
.sum .ln b{color:var(--tx);font-weight:600}
.sum .rumus{font-size:12px;color:var(--ac);margin:-4px 0 6px;text-align:right}
.total{margin-top:14px;padding:16px;border-radius:16px;background:linear-gradient(135deg,rgba(34,211,238,.14),rgba(139,92,246,.14));border:1px solid rgba(34,211,238,.3)}
.total small{font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:var(--mu)}
.total .v{font-size:34px;font-weight:700;margin-top:4px;letter-spacing:-.02em}
.stat{margin-top:12px;padding:12px 14px;border-radius:14px;font-size:14px;font-weight:600;background:rgba(0,0,0,.3);border:1px solid var(--bd)}
.stat.ok{color:var(--ac2);border-color:rgba(52,211,153,.35)}.stat.bad{color:var(--bad);border-color:rgba(251,113,133,.35)}.stat.warn{color:var(--warn);border-color:rgba(251,191,36,.35)}
.err{background:rgba(251,113,133,.1);border:1px solid rgba(251,113,133,.4);color:#ffd3da;padding:12px 14px;border-radius:14px;margin-bottom:16px;font-size:14px}
.err li{margin-left:18px}
.ok-msg{background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.4);color:#c9fbe6;padding:12px 14px;border-radius:14px;margin-bottom:16px;font-size:14px}
.bar{position:fixed;left:0;right:0;bottom:0;z-index:20;padding:12px 16px calc(12px + env(safe-area-inset-bottom));background:rgba(6,10,19,.82);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);border-top:1px solid var(--bd)}
.bar .in2{max-width:1080px;margin:0 auto;display:flex;align-items:center;gap:14px}
.bar .t small{display:block;font-size:11px;color:var(--mu);letter-spacing:.1em;text-transform:uppercase}
.bar .t b{font-size:22px}
.go{margin-left:auto;flex:1;max-width:340px;border:0;border-radius:16px;padding:16px 20px;font-size:16px;font-weight:700;color:#04101a;cursor:pointer;background:linear-gradient(135deg,var(--ac),var(--ac2));box-shadow:0 10px 30px rgba(34,211,238,.35);transition:.18s}
.go:active{transform:scale(.97)}
.go:disabled{opacity:.35;box-shadow:none;cursor:not-allowed;filter:grayscale(.6)}
.hide{display:none!important}
.lama{display:inline-block;margin-top:2px;font-size:12px;color:var(--warn);text-decoration:none}
@endverbatim
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <a class="back" href="{{ route('kasir.create') }}" aria-label="Kembali">&#8592;</a>
    <div>
      <h1>Kasir Ayam Hidup</h1>
      <small>Ekor, berat, dan bayar dalam satu layar</small>
    </div>
    <div class="live"><i></i><span class="mono" id="stokTxt">{{ number_format($sisaStok['ekor'], 0, ',', '.') }} ekor</span></div>
  </div>

  @if (session('status'))<div class="ok-msg">{{ session('status') }}</div>@endif
  @if ($errors->any())
    <div class="err"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif
  @if ($antrian > 0)
    <div class="card" style="padding:12px 16px">
      <span style="font-size:13px;color:var(--mu)">Ada {{ $antrian }} catatan lama pengawas belum dinota (tidak perlu ditunggu).</span>
      <a class="lama" href="{{ route('kasir.ayamhidup.index') }}">Buka daftar &rarr;</a>
    </div>
  @endif

  <form id="f" method="POST" action="{{ route('kasir.ayamhidup.langsung.store') }}" autocomplete="off">
    @csrf
    <div class="grid">
      <div>
        <div class="card">
          <h2>Pelanggan</h2>
          <div class="row c2">
            <div>
              <label class="f" for="nama">Nama pelanggan</label>
              <input class="in" id="nama" name="customer_name" list="plg" maxlength="100" required value="{{ old('customer_name', 'Umum') }}" placeholder="Umum">
              <datalist id="plg">
                <option value="Umum">
                @foreach ($pelanggan as $p)<option value="{{ $p['name'] }}">@endforeach
              </datalist>
              <div class="hint" id="saldoTxt"></div>
            </div>
            <div>
              <label class="f" for="tgl">Tanggal</label>
              <input class="in" id="tgl" type="date" name="sale_date" value="{{ old('sale_date', now()->toDateString()) }}" min="2026-10-01">
            </div>
          </div>
          <div class="sp"></div>
          <label class="f">Jenis pelanggan &middot; harga per kg</label>
          <div class="chips" id="tipe">
            @foreach ($types as $t)
              <label class="chip" data-id="{{ $t->id }}">
                <input type="radio" name="customer_type_id" value="{{ $t->id }}" @checked((string) old('customer_type_id', $defaultTypeId) === (string) $t->id)>
                <span><b>{{ $t->name }}</b><small class="hg"></small></span>
              </label>
            @endforeach
          </div>
        </div>

        <div class="card">
          <h2>Ayam</h2>
          <div class="row c2">
            <div>
              <label class="f" for="ekor">Jumlah (ekor)</label>
              <div class="step">
                <button type="button" data-d="-1">&minus;</button>
                <input class="in big mono" id="ekor" type="number" name="birds" min="1" max="99999" inputmode="numeric" required value="{{ old('birds') }}" placeholder="0" style="text-align:center">
                <button type="button" data-d="1">+</button>
              </div>
              <div class="hint" id="ekorHint"></div>
            </div>
            <div>
              <label class="f" for="kg">Berat total (kg)</label>
              <input class="in big mono" id="kg" type="number" name="weight_kg" min="0.01" max="99999" step="0.01" inputmode="decimal" required value="{{ old('weight_kg') }}" placeholder="0,00">
              <div class="hint" id="rata"></div>
            </div>
          </div>
          <div class="sp"></div>
          <label class="f" for="cat">Catatan (opsional)</label>
          <input class="in" id="cat" name="notes" maxlength="255" value="{{ old('notes') }}" placeholder="mis. ayam jantan, dikirim sore">
        </div>

        <div class="card">
          <h2>Pembayaran</h2>
          <div class="seg">
            @foreach (['CASH' => 'Tunai', 'TRANSFER' => 'Transfer', 'HUTANG' => 'Hutang'] as $k => $v)
              <label><input type="radio" name="payment_method" value="{{ $k }}" @checked(old('payment_method', 'CASH') === $k)><span>{{ $v }}</span></label>
            @endforeach
          </div>
          <div class="sp"></div>
          <label class="f" for="bayar" id="bayarLbl">Uang diterima</label>
          <input class="in big mono" id="bayar" type="number" name="amount_paid" min="0" inputmode="numeric" value="{{ old('amount_paid') }}" placeholder="0">
          <div class="quick" id="quick"></div>
          <div class="row c2 hide" id="refBox" style="margin-top:12px">
            <div style="grid-column:1/-1">
              <label class="f" for="ref">No. referensi transfer</label>
              <input class="in" id="ref" name="reference_number" maxlength="100" value="{{ old('reference_number') }}">
            </div>
          </div>
          <div class="hide" id="tempoBox" style="margin-top:12px">
            <label class="f" for="tempo">Jatuh tempo (hari)</label>
            <input class="in" id="tempo" type="number" name="due_days" min="0" max="365" inputmode="numeric" value="{{ old('due_days', $defaultTempo) }}">
          </div>
          <div class="stat" id="stat">&nbsp;</div>
        </div>
      </div>

      <div class="side">
        <div class="card sum">
          <h2>Ringkasan</h2>
          <div class="ln"><span>Harga per kg</span><b class="mono" id="sHarga">-</b></div>
          <div class="ln"><span>Berat total</span><b class="mono" id="sKg">0 kg</b></div>
          <div class="rumus mono" id="rumus"></div>
          <div class="ln"><span>Subtotal</span><b class="mono" id="sSub">Rp 0</b></div>
          <div class="ln" style="align-items:center"><span>Diskon</span>
            <input class="in mono" id="disc" type="number" name="discount" min="0" inputmode="numeric" value="{{ old('discount') }}" placeholder="0" style="width:150px;padding:8px 10px;font-size:15px;text-align:right">
          </div>
          <div class="ln hide" id="lamaRow"><span>Hutang lama</span><b class="mono" id="sLama">Rp 0</b></div>
          <div class="total">
            <small>Total belanja</small>
            <div class="v mono" id="sTotal">Rp 0</div>
          </div>
          <div class="ln hide" id="tagRow" style="margin-top:10px"><span>Total tagihan + hutang lama</span><b class="mono" id="sTag">Rp 0</b></div>
        </div>
      </div>
    </div>

    <div class="bar">
      <div class="in2">
        <div class="t"><small>Total</small><b class="mono" id="barTotal">Rp 0</b></div>
        <button class="go" id="go" type="submit" disabled>Simpan &amp; Buat Nota</button>
      </div>
    </div>
  </form>
</div>

<script type="application/json" id="d">@json(['harga' => $harga, 'plg' => $pelanggan, 'stok' => $sisaStok])</script>
<script>
(function(){
  var D = JSON.parse(document.getElementById('d').textContent);
  var $ = function(s){ return document.querySelector(s); };
  var fmt = new Intl.NumberFormat('id-ID');
  var rp = function(n){ return 'Rp ' + fmt.format(Math.round(n)); };
  var kgf = function(n){ return new Intl.NumberFormat('id-ID',{minimumFractionDigits:0,maximumFractionDigits:2}).format(n); };
  var nama = $('#nama'), ekor = $('#ekor'), kg = $('#kg'), bayar = $('#bayar'), disc = $('#disc'), go = $('#go');
  var d = {};

  function tipeId(){ var r = document.querySelector('input[name=customer_type_id]:checked'); return r ? r.value : null; }
  function metode(){ var r = document.querySelector('input[name=payment_method]:checked'); return r ? r.value : 'CASH'; }
  function cari(){
    var n = nama.value.trim().toLowerCase();
    if (!n) return null;
    for (var i = 0; i < D.plg.length; i++) if (D.plg[i].name.toLowerCase() === n) return D.plg[i];
    return null;
  }
  function setStat(cls, txt){ var s = $('#stat'); s.className = 'stat ' + cls; s.innerHTML = txt; }

  document.querySelectorAll('#tipe .chip').forEach(function(c){
    var h = +D.harga[c.dataset.id] || 0;
    c.querySelector('.hg').textContent = h > 0 ? rp(h) + ' / kg' : 'harga belum diatur';
    if (h <= 0) c.classList.add('off');
  });

  function hitung(){
    var harga = +D.harga[tipeId()] || 0;
    var e = parseInt(ekor.value, 10) || 0;
    var k = parseFloat(kg.value) || 0;
    var sub = Math.round(harga * k);
    var dc = Math.min(Math.max(parseFloat(disc.value) || 0, 0), sub);
    var total = sub - dc;
    var plg = cari(), nm = nama.value.trim().toLowerCase();
    var lama = plg ? (+plg.saldo || 0) : 0;
    var tag = total + lama;
    var m = metode();
    var paid = parseFloat(bayar.value) || 0;
    d = {harga:harga, e:e, k:k, sub:sub, total:total, lama:lama, tag:tag, m:m, paid:paid};

    $('#sHarga').textContent = harga > 0 ? rp(harga) + ' / kg' : 'belum diatur';
    $('#sKg').textContent = kgf(k) + ' kg';
    $('#rumus').textContent = (harga > 0 && k > 0) ? rp(harga) + ' \u00d7 ' + kgf(k) + ' kg' : '';
    $('#sSub').textContent = rp(sub);
    $('#sTotal').textContent = rp(total);
    $('#barTotal').textContent = rp(total);
    $('#lamaRow').classList.toggle('hide', lama <= 0);
    $('#tagRow').classList.toggle('hide', lama <= 0);
    $('#sLama').textContent = rp(lama);
    $('#sTag').textContent = rp(tag);

    $('#saldoTxt').innerHTML = plg ? (lama > 0 ? '<span class="tag">Hutang berjalan ' + rp(lama) + '</span>' : '<span style="color:var(--ac2)">Pelanggan terdaftar, tanpa hutang</span>') : '';
    $('#rata').textContent = (e > 0 && k > 0) ? 'Rata-rata ' + kgf(k / e) + ' kg / ekor' : '';

    var stokOk = e <= D.stok.ekor, eh = $('#ekorHint');
    if (!stokOk){ eh.className = 'hint bad'; eh.textContent = 'Melebihi stok (' + fmt.format(D.stok.ekor) + ' ekor)'; }
    else { eh.className = 'hint'; eh.textContent = 'Stok tersedia ' + fmt.format(D.stok.ekor) + ' ekor \u00b7 ' + kgf(D.stok.kg) + ' kg'; }

    $('#refBox').classList.toggle('hide', m !== 'TRANSFER');
    $('#tempoBox').classList.toggle('hide', m !== 'HUTANG');
    $('#bayarLbl').textContent = m === 'HUTANG' ? 'Uang muka (opsional)' : (m === 'TRANSFER' ? 'Jumlah transfer' : 'Uang diterima');

    var q = [];
    if (m !== 'HUTANG'){
      q.push(['Uang pas', total]);
      if (lama > 0) q.push(['Lunas + hutang lama', tag]);
      [50000,100000,200000].forEach(function(n){ if (m === 'CASH') q.push([fmt.format(n), n]); });
    } else { q.push(['Tanpa uang muka', 0]); }
    $('#quick').innerHTML = '';
    q.forEach(function(x){
      var b = document.createElement('button'); b.type = 'button'; b.textContent = x[0];
      b.onclick = function(){ bayar.value = x[1] || ''; hitung(); };
      $('#quick').appendChild(b);
    });

    var valid = harga > 0 && e > 0 && k > 0 && stokOk && total >= 0;
    if (harga <= 0){ setStat('bad', 'Harga ayam hidup untuk jenis pelanggan ini belum diatur'); valid = false; }
    else if (m === 'HUTANG'){
      if (!plg && (nm === '' || nm === 'umum')){ setStat('bad', 'Hutang wajib atas nama pelanggan (bukan Umum)'); valid = false; }
      else { setStat('warn', 'Sisa hutang setelah transaksi: ' + rp(Math.max(0, tag - Math.min(paid, tag)))); }
    } else if (paid > 0 && paid < total){ setStat('bad', 'Kurang ' + rp(total - paid)); }
    else if (m === 'TRANSFER' && paid > tag + 0.004){ setStat('bad', 'Transfer melebihi total tagihan'); valid = false; }
    else if (paid >= total && total > 0){
      var kb = Math.max(0, paid - tag);
      setStat('ok', m === 'CASH' ? 'Kembalian ' + rp(kb) : 'Lunas');
    } else { setStat('', total > 0 ? 'Isi uang diterima, atau tekan Uang pas' : '\u00a0'); }
    go.disabled = !valid;
  }

  nama.addEventListener('change', function(){
    var p = cari();
    if (p && p.type){ var r = document.querySelector('input[name=customer_type_id][value="' + p.type + '"]'); if (r) r.checked = true; }
    hitung();
  });
  document.querySelectorAll('.step button').forEach(function(b){
    b.onclick = function(){ ekor.value = Math.max(1, (parseInt(ekor.value, 10) || 0) + parseInt(b.dataset.d, 10)); hitung(); };
  });
  ['input','change'].forEach(function(ev){ document.getElementById('f').addEventListener(ev, hitung); });

  document.getElementById('f').addEventListener('submit', function(ev){
    if (go.disabled){ ev.preventDefault(); return; }
    if (d.m !== 'HUTANG' && bayar.value === '') bayar.value = d.total;
    go.disabled = true; go.textContent = 'Menyimpan...';
  });
  hitung();
})();
</script>
</body>
</html>