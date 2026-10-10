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

    /** Catatan ecer pengawas yang belum jadi nota kasir (atau notanya dibatalkan). */
    private function ecerBelumDinota(?string $dari = null, ?string $sampai = null): object
    {
        return DB::table('live_bird_sales as l')
            ->leftJoin('sales as s', 's.id', '=', 'l.sale_id')
            ->where(fn ($q) => $q->whereNull('l.sale_id')->orWhere(fn ($v) => $v->where('s.is_voided', true)->where(fn ($z) => $z->whereNull('l.sumber')->orWhere('l.sumber', '!=', 'KASIR'))))
            ->when($dari && $sampai, fn ($q) => $q->whereBetween('l.sale_date', [$dari, $sampai]))
            ->selectRaw('coalesce(sum(l.birds),0) as e, coalesce(sum(l.weight_kg),0) as k')
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
        $ecer = $this->ecerBelumDinota();

        return [
            'ekor' => (int) round($beli->e - $mati->e - $prod->e - $jual->e - $ecer->e + $opn->e),
            'kg'   => round((float) ($beli->k - $mati->k - $prod->k - $jual->k - $ecer->k + $opn->k), 2),
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
        $ecer = $this->ecerBelumDinota($f, $t);

        return [
            'mati'     => ['e' => (int) $mati->e, 'k' => (float) $mati->k],
            'produksi' => ['e' => (int) $prod->e, 'k' => (float) $prod->k],
            'jual'     => ['e' => (int) ($jual->e + $ecer->e), 'k' => (float) ($jual->k + $ecer->k)],
        ];
    }

    public function opnameAyamTerakhir(int $n = 3): Collection
    {
        return DB::table('live_bird_opnames')
            ->orderByDesc('opname_date')->orderByDesc('created_at')->limit($n)->get();
    }

    /**
     * Status umur simpan: 'lewat' bila umur melebihi batas, 'dekat' pada 1 hari sebelum
     * batas sampai tepat di batas, selainnya 'aman'.
     */
    public function statusUmur(int $umur, int $batas): string
    {
        if ($umur > $batas) {
            return 'lewat';
        }

        return $umur >= max(1, $batas - 1) ? 'dekat' : 'aman';
    }

    /**
     * Stok hasil produksi per item (semua produk non-ayam-hidup, dalam kg).
     * 'hasil' = hasil produksi pada rentang tanggal; 'stok' = stok saat ini.
     *
     * Umur simpan dihitung FIFO: stok yang tersisa dianggap berasal dari hasil produksi
     * terbaru (yang lama sudah keluar duluan). 'lot' = rincian sisa stok per freezer dan
     * tanggal produksi, dari yang paling lama.
     */
    public function produk(Carbon $from, Carbon $to): Collection
    {
        $today = Carbon::today();

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

        $dapur = DB::table('kitchen_order_lines as l')
            ->join('kitchen_orders as o', 'o.id', '=', 'l.kitchen_order_id')
            ->where('o.status', '!=', 'VOIDED')
            ->selectRaw('l.product_id, sum(l.quantity_kg) as qty')->groupBy('l.product_id')->pluck('qty', 'product_id');

        $opname = DB::table('stock_adjustments')
            ->orderByDesc('adjustment_date')->orderByDesc('created_at')->get()
            ->unique('product_id')->keyBy('product_id');

        // Semua hasil timbang, terbaru dulu, dikelompokkan per produk.
        $lotRows = DB::table('weighing_records as w')
            ->leftJoin('production_batches as b', 'b.id', '=', 'w.batch_id')
            ->leftJoin('storage_locations as f', 'f.id', '=', 'w.storage_location_id')
            ->select('w.product_id', 'w.weight_kg', 'w.created_at', 'b.production_date', 'f.id as freezer_id', 'f.name as freezer_name')
            ->get()
            ->map(function ($r) {
                $r->tgl = $r->production_date
                    ? Carbon::parse(substr((string) $r->production_date, 0, 10))->startOfDay()
                    : Carbon::parse($r->created_at)->timezone(config('app.timezone'))->startOfDay();

                return $r;
            })
            ->sort(fn ($a, $b) => [$b->tgl->timestamp, (string) $b->created_at] <=> [$a->tgl->timestamp, (string) $a->created_at])
            ->groupBy('product_id');

        return Product::where('is_active', true)->where('is_live_bird', false)
            ->orderBy('name')->get(['id', 'code', 'name', 'max_storage_days'])
            ->map(function ($p) use ($hasilSemua, $hasilPeriode, $jual, $adj, $dapur, $opname, $lotRows, $today) {
                $stok = round((float) (($hasilSemua[$p->id] ?? 0) + ($adj[$p->id] ?? 0) - ($jual[$p->id] ?? 0) - ($dapur[$p->id] ?? 0)), 2);
                $batas = max(1, (int) ($p->max_storage_days ?: 3));

                $sisa = max(0.0, $stok);
                $lot = [];

                foreach ($lotRows->get($p->id, collect()) as $r) {
                    if ($sisa <= 0.004) {
                        break;
                    }

                    $kg = min($sisa, (float) $r->weight_kg);
                    $sisa = round($sisa - $kg, 2);
                    $umur = $r->tgl->lt($today) ? (int) abs($r->tgl->diffInDays($today)) : 0;
                    $kunci = ($r->freezer_id ?? '-') . '|' . $r->tgl->toDateString();

                    if (isset($lot[$kunci])) {
                        $lot[$kunci]->kg = round($lot[$kunci]->kg + $kg, 2);
                        continue;
                    }

                    $lot[$kunci] = (object) [
                        'produk'     => $p->name,
                        'batas'      => $batas,
                        'freezer_id' => $r->freezer_id,
                        'freezer'    => $r->freezer_name ?: 'Belum diatur',
                        'tgl'        => $r->tgl,
                        'kg'         => round($kg, 2),
                        'umur'       => $umur,
                        'status'     => $this->statusUmur($umur, $batas),
                    ];
                }

                $lot = collect(array_values($lot))->sortByDesc('umur')->values();

                $status = $lot->isEmpty()
                    ? 'kosong'
                    : ($lot->contains('status', 'lewat') ? 'lewat' : ($lot->contains('status', 'dekat') ? 'dekat' : 'aman'));

                return (object) [
                    'id'      => $p->id,
                    'name'    => $p->name,
                    'hasil'   => (float) ($hasilPeriode[$p->id] ?? 0),
                    'stok'    => $stok,
                    'opname'  => $opname[$p->id] ?? null,
                    'batas'   => $batas,
                    'lot'     => $lot,
                    'umur'    => $lot->isEmpty() ? null : (int) $lot->max('umur'),
                    'status'  => $status,
                    'freezer' => $lot->pluck('freezer')->unique()->implode(', ') ?: '-',
                ];
            });
    }
}