@php
    $bolehVerif = auth()->user()->canWrite('kandang');
@endphp
@if (count($verif) > 0)
<div class="card">
    <h3 style="margin-top:0">Verifikasi penjualan ayam ecer (dicatat kasir)</h3>
    <p class="muted">
        Cocokkan dengan ayam yang benar-benar keluar kandang. Kalau angkanya sama, tekan Verifikasi.
        Kalau berbeda, ubah ekor/berat dan isi alasan. Selisih hanya dicatat: nota dan stok tidak berubah otomatis.
    </p>
    <table class="rtable">
        <thead><tr><th>Tanggal</th><th>Pelanggan</th><th>Nota</th><th class="right">Ekor</th><th class="right">Berat (kg)</th><th>Verifikasi</th></tr></thead>
        <tbody>
            @foreach ($verif as $v)
                <tr>
                    <td data-label="Tanggal">{{ $tgl($v->sale_date) }}</td>
                    <td data-label="Pelanggan">{{ $v->customer_name }}</td>
                    <td data-label="Nota"><a href="{{ route('kasir.show', $v->sale_id) }}">{{ $v->sale_number }}</a></td>
                    <td class="right" data-label="Ekor">{{ $ekor($v->birds) }}</td>
                    <td class="right" data-label="Berat (kg)">{{ $kg($v->weight_kg) }}</td>
                    <td data-label="Verifikasi">
                        @if ($v->diverifikasi_at)
                            <span class="chip warn">
                                Selisih: pengawas {{ $ekor($v->ekor_pengawas) }} ekor / {{ $kg($v->berat_pengawas) }} kg
                                &mdash; {{ $v->catatan_verifikasi }}
                            </span>
                        @elseif ($bolehVerif)
                            <form method="POST" action="{{ route('riwayatayam.verifikasi', $v->id) }}" style="display:flex;gap:.3rem;flex-wrap:wrap;align-items:center">
                                @csrf
                                <input type="number" name="birds" value="{{ $v->birds }}" min="0" step="1" style="width:5rem" required>
                                <input type="number" name="weight_kg" value="{{ $v->weight_kg }}" min="0" step="0.01" style="width:6rem" required>
                                <input type="text" name="catatan" maxlength="255" placeholder="Alasan (jika beda)">
                                <button type="submit" class="btn" style="width:auto">Verifikasi</button>
                            </form>
                        @else
                            <span class="chip warn">Belum diverifikasi</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif