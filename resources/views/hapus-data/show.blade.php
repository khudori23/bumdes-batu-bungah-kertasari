@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <a href="{{ route('hapus-data.index') }}" class="btn btn-sm btn-outline-secondary mb-2">&larr; Kembali</a>
    <h4 class="mb-3">Hapus Data: {{ $label }}</h4>

    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('hapus-data.baris', $tabel) }}">
        @csrf
        @method('DELETE')

        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
                @php $kolom = $baris->count() ? array_slice(array_keys((array) $baris->first()), 0, 6) : []; @endphp
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
                        <tr><td colspan="7" class="text-center text-muted">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $baris->links() }}

        @if ($baris->count())
        <div class="card border-danger mt-3">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Password Anda</label>
                    <input type="password" name="password" class="form-control" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-danger w-100"
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
        document.querySelectorAll('.cek').forEach(c => c.checked = this.checked);
    });
</script>
@endsection