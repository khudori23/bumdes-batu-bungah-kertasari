<div class="card">
    <h3 style="margin-top:0">Jual ayam ecer (dicatat Pengawas)</h3>
    <p class="muted">
        Catat ayam hidup yang dijual langsung dari kandang. Stok ayam hidup otomatis berkurang. Harga diisi Kasir.
        Bulan ini: <strong>{{ $ekor($ecerBulan->e) }} ekor</strong> &middot; {{ $kg($ecerBulan->k) }} kg.
        Daftar penjualan dan status notanya ada di menu <a href="{{ route('riwayatayam.index') }}">Riwayat Ayam</a>.
    </p>

    @if ($bolehTulis)
        <form method="POST" action="{{ route('ayamkeluar.jual') }}">
            @csrf
            <div class="fgrid">
                <div>
                    <label>Tanggal</label>
                    <input type="date" name="sale_date" value="{{ old('sale_date', now()->toDateString()) }}" min="{{ \App\Support\Pencatatan::mulai() }}" max="{{ now()->toDateString() }}" required>
                </div>
                <div>
                    <label>Nama pelanggan</label>
                    <input type="text" name="customer_name" maxlength="100" value="{{ old('customer_name') }}" required>
                </div>
                <div>
                    <label>Jumlah (ekor)</label>
                    <input type="number" inputmode="numeric" name="birds" min="1" step="1" value="{{ old('birds') }}" required>
                </div>
                <div>
                    <label>Berat total (kg)</label>
                    <input type="number" inputmode="decimal" name="weight_kg" min="0.01" step="0.01" value="{{ old('weight_kg') }}" required>
                </div>
            </div>
            <label>Catatan (opsional)</label>
            <input type="text" name="sale_notes" maxlength="255" value="{{ old('sale_notes') }}">
            <button type="submit" class="btn" style="width:100%">Simpan penjualan ayam ecer</button>
        </form>
    @else
        <p class="muted">Anda hanya dapat melihat data ini.</p>
    @endif
</div>