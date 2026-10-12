@php
    $uu = auth()->user();
    $bolehHapus = $uu->isAdmin() || ($uu->roleName() === \App\Models\User::ROLE_KASIR && \Illuminate\Support\Carbon::parse($sale->sale_date)->isToday());
@endphp
@if ($bolehHapus)
<div class="card" style="border:1px solid #fecdd3">
    <h3 style="margin-top:0;color:#e11d48">Hapus permanen</h3>
    <p class="muted">
        Hanya untuk data test atau salah input. Nota, item, pembayaran, dan catatan ayam ecernya hilang dan tidak bisa dikembalikan.
        Stok ikut kembali. Kalau nota ini melunasi hutang nota lama, hutang lama muncul lagi.
        Untuk transaksi asli yang salah, pakai Batalkan nota di atas.
    </p>
    <form method="POST" action="{{ route('kasir.ayamhidup.hapusnota', $sale->id) }}"
          onsubmit="return confirm({{ \Illuminate\Support\Js::from('HAPUS PERMANEN nota ' . $sale->sale_number . '? Tidak bisa diurungkan.') }})">
        @csrf
        @method('DELETE')
        @error('alasan')<div style="color:#e11d48;font-size:.85rem;margin-bottom:.4rem">{{ $message }}</div>@enderror
        <label>Alasan</label>
        <input type="text" name="alasan" maxlength="255" required>
        <label>Ketik HAPUS untuk konfirmasi</label>
        <input type="text" name="konfirmasi" autocomplete="off" required>
        <button type="submit" class="btn" style="background:#e11d48;margin-top:.5rem">Hapus permanen</button>
    </form>
</div>
@endif