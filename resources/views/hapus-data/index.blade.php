@extends('layouts.app')

@section('content')
<style>
.hd{max-width:760px;margin:0 auto;padding:12px}
.hd h1{font-size:1.25rem;margin:0 0 4px;color:#0f172a}
.hd .sub{color:#64748b;font-size:.9rem;margin:0 0 12px}
.hd .box{background:#fff;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:14px;overflow:hidden}
.hd .box-h{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:12px 14px;background:#0f172a;color:#fff;font-weight:600}
.hd .box-h label{font-weight:400;font-size:.85rem;display:flex;gap:6px;align-items:center}
.hd .row{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-top:1px solid #f1f5f9}
.hd .row label{display:flex;align-items:center;gap:10px;flex:1;min-width:0}
.hd .n{background:#e2e8f0;color:#334155;border-radius:999px;padding:1px 9px;font-size:.78rem;white-space:nowrap}
.hd a.lnk{color:#0f766e;font-size:.85rem;white-space:nowrap;text-decoration:none}
.hd input[type=checkbox]{width:20px;height:20px;flex:none}
.hd .warn{background:#fff1f2;border:1px solid #fecdd3;color:#9f1239;border-radius:10px;padding:10px 12px;font-size:.88rem;margin-bottom:12px}
.hd .ok{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:10px;padding:10px 12px;font-size:.9rem;margin-bottom:12px}
.hd .lbl{display:block;font-size:.85rem;color:#334155;margin:0 0 4px}
.hd .inp{width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:10px;font-size:16px;box-sizing:border-box;margin-bottom:10px}
.hd .btn-del{width:100%;padding:14px;border:0;border-radius:10px;background:#e11d48;color:#fff;font-size:1rem;font-weight:600;cursor:pointer}
.hd .pad{padding:14px}
</style>

<div class="hd">
    <h1>Hapus Data</h1>
    <p class="sub">Menghapus data transaksi yang diinput kasir dan pengawas. Data induk (produk, pelanggan, pegawai, pemasok, pengaturan) tidak ikut terhapus.</p>

    <div class="warn"><strong>Permanen, tidak bisa dikembalikan.</strong> Backup database dulu. Mutasi stok memengaruhi saldo stok produk, jadi hapus bersama data penjualan/produksi agar stok tetap sesuai.</div>

    @if (session('sukses'))
        <div class="ok">{{ session('sukses') }}</div>
    @endif
    @if ($errors->any())
        <div class="warn">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <form method="POST" action="{{ route('hapus-data.tabel') }}">
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
                        <label>
                            <input type="checkbox" name="tabel[]" value="{{ $nama }}" class="cek g-{{ $kode }}">
                            <span>{{ $d['label'] }}</span>
                            <span class="n">{{ $d['jumlah'] }} data</span>
                        </label>
                        <a class="lnk" href="{{ route('hapus-data.show', $nama) }}">Pilih baris</a>
                    </div>
                @empty
                    <div class="row">Tidak ada tabel.</div>
                @endforelse
            </div>
        @endforeach

        <div class="box">
            <div class="pad">
                <label class="lbl">Password Anda</label>
                <input type="password" name="password" class="inp" required autocomplete="current-password">
                <label class="lbl">Ketik <strong>HAPUS</strong> untuk konfirmasi</label>
                <input type="text" name="konfirmasi" class="inp" required autocomplete="off">
                <button type="submit" class="btn-del"
                        onclick="return confirm('Yakin hapus PERMANEN data yang dipilih? Tidak bisa dibatalkan.')">
                    Hapus Permanen Data Terpilih
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    document.querySelectorAll('.all').forEach(function (a) {
        a.addEventListener('change', function () {
            document.querySelectorAll('.g-' + a.dataset.g).forEach(function (c) { c.checked = a.checked; });
        });
    });
</script>
@endsection