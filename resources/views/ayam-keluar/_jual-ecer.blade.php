<div class="card">
    <h3 style="margin-top:0">Jual ayam hidup (dicatat Pengawas)</h3>
    <p class="muted">
        Catat ayam hidup yang dijual langsung dari kandang. Stok ayam hidup otomatis berkurang. Harga diisi Kasir.
        Bulan ini: <strong>{{ $ekor($ecerBulan->e) }} ekor</strong> &middot; {{ $kg($ecerBulan->k) }} kg.
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
            <button type="submit" class="btn" style="width:100%">Simpan penjualan ayam hidup</button>
        </form>
    @else
        <p class="muted">Anda hanya dapat melihat data ini.</p>
    @endif

    <table class="rtable" style="margin-top:1rem">
        <thead><tr><th>Tanggal</th><th>Pelanggan</th><th class="right">Ekor</th><th class="right">Berat (kg)</th><th>Catatan</th><th>Nota</th></tr></thead>
        <tbody>
            @forelse ($ecer as $e)
                <tr>
                    <td data-label="Tanggal">{{ $tgl($e->sale_date) }}</td>
                    <td data-label="Pelanggan">{{ $e->customer_name }}</td>
                    <td class="right" data-label="Ekor">{{ $ekor($e->birds) }}</td>
                    <td class="right" data-label="Berat (kg)">{{ $kg($e->weight_kg) }}</td>
                    <td data-label="Catatan">{{ $e->notes ?: '-' }}</td>
                    <td data-label="Nota">
                        @if ($e->sale_number && ! $e->nota_batal)
                            <span class="chip up">{{ $e->sale_number }}</span>
                        @else
                            <span class="chip warn">Belum dinota</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Belum ada penjualan yang dicatat pengawas.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>