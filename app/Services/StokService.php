<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Semua stok dihitung dari catatan sumbernya, bukan disimpan sebagai angka terpisah.
 * Jadi void nota, koreksi, dan opname otomatis ikut terhitung.
 */
class StokService
{
    /** Kunci antar-transaksi PostgreSQL (lepas otomatis saat transaksi selesai). */
    public function kunci(string $kunci): void
    {
        DB::select('select pg_advisory_xact_lock(?)', [(int) crc32($kunci)]);
    }

    /** Ayam hidup terjual per ekor. Tanpa tanggal = seluruh waktu. */
    private function jualEkor(?string $dari = null, ?string $sampai = null): object
    {
        return DB::table('sale_items as i')
            ->join('sales as s', 's.id', '=', 'i.sale_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->where('s.is_voided', false)
            ->where('p.is_live_bird', true)
            ->when($dari && $sampai, fn ($q) => $q->whereBetween('s.sale_date', [$dari, $sampai]))
            ->selectRaw('coalesce(sum(i.quantity),0) as e, coalesce(sum(i.weight_kg),0) as k')
            ->first();
    }

    /** Stok ayam hidup saat ini: ['ekor' => int, 'kg' => float]. */
    public function ayamHidup(): array
    {
        $beli = DB::table('purchase_transactions')
            ->selectRaw('coalesce(sum(total_birds),0) as e, coalesce(sum(total_weight_kg),0) as k')->first();
        $mati = DB::table('live_bird_mortalities')
            ->selectRaw('coalesce(sum(birds),0) as e, coalesce(sum(weight_kg),0) as k')->first();
        $prod = DB::table('production_batches')
            ->selectRaw('coalesce(sum(input_birds),0) as e, coalesce(sum(input_weight_kg),0) as k')->first();
        $opn = DB::table('live_bird_opnames')
            ->selectRaw('coalesce(sum(diff_birds),0) as e, coalesce(sum(diff_kg),0) as k')->first();
        $jual = $this->jualEkor();

        return [
            'ekor' => (int) round($beli->e - $mati->e - $prod->e - $jual->e + $opn->e),
            'kg'   => round((float) ($beli->k - $mati->k - $prod->k - $jual->k + $opn->k), 2),
        ];
    }

    /** Pergerakan ayam hidup pada rentang tanggal. */
    public function arus(Carbon $from, Carbon $to): array
    {
        $f = $from->toDateString();
        $t = $to->toDateString();

        $mati = DB::table('live_bird_mortalities')->whereBetween('record_date', [$f, $t])
            ->selectRaw('coalesce(sum(birds),0) as e, coalesce(sum(weight_kg),0) as k')->first();
        $prod = DB::table('production_batches')->whereBetween('production_date', [$f, $t])
            ->selectRaw('coalesce(sum(input_birds),0) as e, coalesce(sum(input_weight_kg),0) as k')->first();
        $jual = $this->jualEkor($f, $t);

        return [
            'mati'     => ['e' => (int) $mati->e, 'k' => (float) $mati->k],
            'produksi' => ['e' => (int) $prod->e, 'k' => (float) $prod->k],
            'jual'     => ['e' => (int) $jual->e, 'k' => (float) $jual->k],
        ];
    }

    public function opnameAyamTerakhir(int $n = 3): Collection
    {
        return DB::table('live_bird_opnames')
            ->orderByDesc('opname_date')->orderByDesc('created_at')->limit($n)->get();
    }

    /**
     * Stok hasil produksi per item (semua produk non-ayam-hidup, dalam kg).
     * 'hasil' = hasil produksi pada rentang tanggal; 'stok' = stok saat ini.
     */
    public function produk(Carbon $from, Carbon $to): Collection
    {
        $hasilSemua = DB::table('weighing_records')
            ->selectRaw('product_id, sum(weight_kg) as kg')->groupBy('product_id')->pluck('kg', 'product_id');

        $hasilPeriode = DB::table('weighing_records as w')
            ->join('production_batches as b', 'b.id', '=', 'w.batch_id')
            ->whereBetween('b.production_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('w.product_id, sum(w.weight_kg) as kg')->groupBy('w.product_id')->pluck('kg', 'product_id');

        $jual = DB::table('sale_items as i')
            ->join('sales as s', 's.id', '=', 'i.sale_id')
            ->where('s.is_voided', false)
            ->selectRaw('i.product_id, sum(i.quantity) as qty')->groupBy('i.product_id')->pluck('qty', 'product_id');

        $adj = DB::table('stock_adjustments')
            ->selectRaw('product_id, sum(difference_qty) as d')->groupBy('product_id')->pluck('d', 'product_id');

        $opname = DB::table('stock_adjustments')
            ->orderByDesc('adjustment_date')->orderByDesc('created_at')->get()
            ->unique('product_id')->keyBy('product_id');

        return Product::where('is_active', true)->where('is_live_bird', false)
            ->orderBy('name')->get(['id', 'code', 'name'])
            ->map(fn ($p) => (object) [
                'id'     => $p->id,
                'name'   => $p->name,
                'hasil'  => (float) ($hasilPeriode[$p->id] ?? 0),
                'stok'   => round((float) (($hasilSemua[$p->id] ?? 0) + ($adj[$p->id] ?? 0) - ($jual[$p->id] ?? 0)), 2),
                'opname' => $opname[$p->id] ?? null,
            ]);
    }
}