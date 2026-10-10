@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <h4 class="mb-1">Hapus Data</h4>
    <p class="text-muted">Data yang dihapus <strong>permanen</strong> dan tidak bisa dikembalikan. Disarankan backup database dulu.</p>

    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('hapus-data.tabel') }}" id="formHapus">
        @csrf
        @method('DELETE')

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Pilih data yang ingin dihapus semuanya</span>
                <label class="mb-0 small"><input type="checkbox" id="pilihSemua"> Pilih semua</label>
            </div>
            <ul class="list-group list-group-flush">
                @foreach ($daftar as $nama => $d)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <label class="mb-0 flex-grow-1">
                            <input type="checkbox" name="tabel[]" value="{{ $nama }}" class="cek">
                            {{ $d['label'] }}
                            <span class="badge bg-secondary ms-1">{{ $d['jumlah'] }} data</span>
                        </label>
                        <a href="{{ route('hapus-data.show', $nama) }}" class="btn btn-sm btn-outline-primary">
                            Pilih per baris
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="card border-danger">
            <div class="card-body">
                <div class="mb-2">
                    <label class="form-label">Password Anda</label>
                    <input type="password" name="password" class="form-control" required autocomplete="current-password">
                </div>
                <div class="mb-3">
                    <label class="form-label">Ketik <code>HAPUS</code> untuk konfirmasi</label>
                    <input type="text" name="konfirmasi" class="form-control" required autocomplete="off">
                </div>
                <button type="submit" class="btn btn-danger w-100"
                        onclick="return confirm('Yakin hapus PERMANEN data yang dipilih? Tidak bisa dibatalkan.')">
                    Hapus Permanen Data Terpilih
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    document.getElementById('pilihSemua').addEventListener('change', function () {
        document.querySelectorAll('.cek').forEach(c => c.checked = this.checked);
    });
</script>
@endsection