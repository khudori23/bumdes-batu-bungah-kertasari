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
.hd input[type=checkbox]{width:20px;height:20px}
.hd .warn{background:#fff1f2;border:1px solid #fecdd3;color:#9f1239;border-radius:10px;padding:10px 12px;font-size:.88rem;margin-bottom:12px}
.hd .ok{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:10px;padding:10px 12px;font-size:.9rem;margin-bottom:12px}
.hd .lbl{display:block;font-size:.85rem;color:#334155;margin:0 0 4px}
.hd .inp{width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:10px;font-size:16px;box-sizing:border-box;margin-bottom:10px}
.hd .btn-del{width:100%;padding:14px;border:0;border-radius:10px;background:#e11d48;color:#fff;font-size:1rem;font-weight:600;cursor:pointer}
.hd .pad{padding:14px}
.hd .pg{display:flex;justify-content:space-between;margin:0 0 14px}
</style>

<div class="hd">
    <a class="lnk" href="{{ route('hapus-data.index') }}">&larr; Kembali</a>
    <h1>Hapus Data: {{ $label }}</h1>

    @if (session('sukses'))
        <div class="ok">{{ session('sukses') }}</div>
    @endif
    @if ($errors->any())
        <div class="warn">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <form method="POST" action="{{ route('hapus-data.baris', $tabel) }}">
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
                        <th style="width:40px"><input type="checkbox" id="pilihSemua"></th>
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
                        <tr><td colspan="6">Tidak ada data.</td></tr>
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
                <div class="warn">Baris yang dihapus ikut menghapus data turunannya (misalnya nota ikut menghapus item dan pembayarannya).</div>
                <label class="lbl">Password Anda</label>
                <input type="password" name="password" class="inp" required autocomplete="current-password">
                <button type="submit" class="btn-del"
                        onclick="return confirm('Hapus PERMANEN baris yang dicentang? Tidak bisa dibatalkan.')">
                    Hapus Permanen Baris Terpilih
                </button>
            </div>
        </div>
        @endif
    </form>
</div>

<script>
    var semua = document.getElementById('pilihSemua');
    if (semua) semua.addEventListener('change', function () {
        document.querySelectorAll('.cek').forEach(function (c) { c.checked = semua.checked; });
    });
</script>
@endsection