<?php

namespace App\Http\Controllers;

use App\Models\LiveBirdSale;
use App\Models\Payment;
use App\Models\Sale;
use App\Services\AuditService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Hapus permanen data test / salah input. Khusus ADMIN.
 * Untuk transaksi asli yang salah, pakai "Batalkan nota" (jejak tetap tersimpan).
 */
class HapusDataController extends Controller
{
    private function kunci(string $kunci): void
    {
        DB::select('select pg_advisory_xact_lock(?)', [(int) crc32($kunci)]);
    }

    private function aturan(): array
    {
        return [
            'alasan'    => ['required', 'string', 'min:5', 'max:255'],
            'konfirmasi' => ['required', 'in:HAPUS'],
        ];
    }

    public function nota(Request $request, string $sale)
    {
        abort_unless(Auth::user()->isAdmin(), 403, 'Hanya admin yang boleh menghapus data.');

        $d = $request->validate($this->aturan(), [
            'konfirmasi.in' => 'Ketik HAPUS (huruf besar) untuk konfirmasi.',
        ]);

        try {
            $nomor = DB::transaction(function () use ($sale, $d) {
                $s = Sale::whereKey($sale)->firstOrFail();

                if ($s->customer_id) {
                    $this->kunci('cust_' . $s->customer_id);
                }

                $s = Sale::whereKey($sale)->lockForUpdate()->firstOrFail();

                // Nota ini dibayar lewat transaksi lain -> uangnya milik nota lain
                $dibayarTerpisah = Payment::where('sale_id', $s->id)
                    ->whereNull('voided_at')
                    ->where(fn ($q) => $q->whereNull('settlement_sale_id')->orWhere('settlement_sale_id', '!=', $s->id))
                    ->exists();

                if ($dibayarTerpisah) {
                    throw ValidationException::withMessages([
                        'alasan' => 'Nota ini sudah dibayar lewat transaksi lain, tidak bisa dihapus. Selesaikan pembayarannya dulu.',
                    ]);
                }

                if (DB::table('kitchen_orders')->where('sale_id', $s->id)->exists()) {
                    throw ValidationException::withMessages([
                        'alasan' => 'Nota ini terhubung ke pesanan dapur, tidak bisa dihapus. Gunakan Batalkan nota.',
                    ]);
                }

                $lama = [
                    'sale_number' => $s->sale_number,
                    'sale_date'   => (string) $s->sale_date,
                    'customer'    => $s->customer_name,
                    'total'       => (float) $s->total_amount,
                    'voided'      => (bool) $s->is_voided,
                    'items'       => $s->items()->get(['product_id', 'quantity', 'weight_kg', 'unit_price'])->toArray(),
                ];

                // Uang yang diterima di nota ini (termasuk yang melunasi nota lama) dan yang masuk ke nota ini
                Payment::where('settlement_sale_id', $s->id)->delete();
                Payment::where('sale_id', $s->id)->delete();

                // Catatan ayam ecer: yang dibuat kasir ikut dihapus; catatan pengawas lama dilepas (kembali "menunggu nota")
                LiveBirdSale::where('sale_id', $s->id)->where('sumber', 'KASIR')->delete();
                LiveBirdSale::where('sale_id', $s->id)->update(['sale_id' => null]);

                $s->items()->delete();
                $s->delete();

                AuditService::catat('DELETE', 'sales', $s->id, $lama, ['alasan' => $d['alasan'], 'hapus_permanen' => true]);

                return $lama['sale_number'];
            });
        } catch (QueryException $e) {
            report($e);

            return back()->withErrors(['alasan' => 'Tidak bisa dihapus karena masih dipakai data lain. Gunakan Batalkan nota.']);
        }

        return redirect()->route('kasir.ayamhidup.index')->with('status', "Nota {$nomor} dihapus permanen.");
    }

    public function catatan(Request $request, string $rec)
    {
        abort_unless(Auth::user()->isAdmin(), 403, 'Hanya admin yang boleh menghapus data.');

        $d = $request->validate($this->aturan(), [
            'konfirmasi.in' => 'Ketik HAPUS (huruf besar) untuk konfirmasi.',
        ]);

        DB::transaction(function () use ($rec, $d) {
            $r = LiveBirdSale::whereKey($rec)->lockForUpdate()->firstOrFail();

            if ($r->sale_id) {
                throw ValidationException::withMessages([
                    'alasan' => 'Catatan ini sudah punya nota. Hapus lewat halaman nota.',
                ]);
            }

            $lama = ['tanggal' => (string) $r->sale_date, 'pelanggan' => $r->customer_name, 'ekor' => (int) $r->birds, 'kg' => (float) $r->weight_kg];
            $r->delete();

            AuditService::catat('DELETE', 'live_bird_sales', $rec, $lama, ['alasan' => $d['alasan'], 'hapus_permanen' => true]);
        });

        return back()->with('status', 'Catatan dihapus permanen.');
    }
}