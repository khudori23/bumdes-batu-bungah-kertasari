<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LiveBirdSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pengawas memverifikasi penjualan ayam ecer yang dicatat kasir.
 * Kalau angkanya beda, selisih dicatat (angka pengawas + alasan). Nota dan stok TIDAK diubah otomatis:
 * selisih fisik diselesaikan lewat Opname Ayam, selisih uang oleh admin.
 */
class VerifikasiAyamController extends Controller
{
    public function store(Request $request, string $rec)
    {
        abort_unless(Auth::user()->canWrite('kandang'), 403);

        $d = $request->validate([
            'birds'     => ['required', 'integer', 'min:0', 'max:99999'],
            'weight_kg' => ['required', 'numeric', 'min:0', 'max:99999'],
            'catatan'   => ['nullable', 'string', 'max:255'],
        ]);

        $selisih = DB::transaction(function () use ($rec, $d) {
            $r = LiveBirdSale::whereKey($rec)->lockForUpdate()->firstOrFail();

            if ($r->sumber !== 'KASIR') {
                throw ValidationException::withMessages(['birds' => 'Hanya penjualan yang dicatat kasir yang diverifikasi di sini.']);
            }
            if ($r->diverifikasi_at) {
                throw ValidationException::withMessages(['birds' => 'Catatan ini sudah diverifikasi.']);
            }
            if ((string) $r->created_by_id === (string) Auth::id() && ! Auth::user()->isAdmin()) {
                throw ValidationException::withMessages(['birds' => 'Anda tidak boleh memverifikasi catatan yang Anda buat sendiri.']);
            }

            $ekor  = (int) $d['birds'];
            $berat = round((float) $d['weight_kg'], 2);
            $sama  = $ekor === (int) $r->birds && abs($berat - (float) $r->weight_kg) < 0.005;
            $alasan = trim((string) ($d['catatan'] ?? ''));

            if (! $sama && $alasan === '') {
                throw ValidationException::withMessages(['catatan' => 'Angka berbeda dari catatan kasir. Isi alasannya.']);
            }

            $r->diverifikasi_oleh = (string) Auth::id();
            $r->diverifikasi_at   = now();
            if (! $sama) {
                $r->ekor_pengawas       = $ekor;
                $r->berat_pengawas      = $berat;
                $r->catatan_verifikasi  = $alasan;
            }
            $r->save();

            AuditLog::create([
                'action'     => 'UPDATE',
                'table_name' => 'live_bird_sales',
                'record_id'  => $r->id,
                'new_values' => $sama
                    ? ['verifikasi' => 'sesuai']
                    : ['verifikasi' => 'selisih', 'kasir' => ['birds' => (int) $r->birds, 'kg' => (float) $r->weight_kg], 'pengawas' => ['birds' => $ekor, 'kg' => $berat], 'alasan' => $alasan],
                'user_id'    => Auth::id(),
            ]);

            return ! $sama;
        });

        return back()->with('status', $selisih
            ? 'Selisih dicatat. Stok dan nota tidak diubah otomatis: selesaikan lewat Opname Ayam atau minta admin memeriksa nota.'
            : 'Penjualan terverifikasi.');
    }

    /** Form pengawas lama ditutup: ayam ecer sekarang dicatat kasir. */
    public function ditutup()
    {
        abort(410, 'Penjualan ayam ecer sekarang dicatat kasir lewat menu Ayam Ecer. Pengawas memverifikasi di Riwayat Ayam.');
    }
}