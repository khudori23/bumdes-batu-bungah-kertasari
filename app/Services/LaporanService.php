<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LaporanService
{
    public const JENIS = ['harian' => 'Harian', 'mingguan' => 'Mingguan', 'bulanan' => 'Bulanan'];

    /** Rentang [dari, sampai] untuk jenis laporan: hari, Senin-Minggu, atau bulan kalender. */
    public function rentang(string $jenis, Carbon $tgl): array
    {
        $tgl = $tgl->copy()->startOfDay();

        return match ($jenis) {
            'mingguan' => [
                $tgl->copy()->startOfWeek(Carbon::MONDAY),
                $tgl->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay(),
            ],
            'bulanan' => [$tgl->copy()->startOfMonth(), $tgl->copy()->endOfMonth()->startOfDay()],
            default   => [$tgl, $tgl->copy()],
        };
    }

    public function sebelumnya(string $jenis, Carbon $from): array
    {
        return $this->rentang($jenis, match ($jenis) {
            'mingguan' => $from->copy()->subWeek(),
            'bulanan'  => $from->copy()->subMonthNoOverflow(),
            default    => $from->copy()->subDay(),
        });
    }

    /** Angka pokok untuk perbandingan antar periode. */
    public function ringkas(Carbon $from, Carbon $to): array
    {
        $f = $from->toDateString();
        $t = $to->toDateString();

        $jual = DB::table('sales')->where('is_voided', false)->whereBetween('sale_date', [$f, $t])
            ->selectRaw('count(*) as n, coalesce(sum(total_amount),0) as omzet, coalesce(sum(discount),0) as diskon')->first();
        $beli = (float) DB::table('purchase_transactions')->whereBetween('purchase_date', [$f, $t])->sum('total_cost');
        $exp  = (float) DB::table('expenses')->whereBetween('expense_date', [$f, $t])->sum('amount');

        $omzet = (float) $jual->omzet;

        return [
            'nota'        => (int) $jual->n,
            'omzet'       => $omzet,
            'diskon'      => (float) $jual->diskon,
            'pembelian'   => $beli,
            'pengeluaran' => $exp,
            'laba'        => $omzet - $beli - $exp,
        ];
    }

    private function kasQuery(string $f, string $t)
    {
        $tz = str_replace("'", '', (string) config('app.timezone', 'UTC'));

        return DB::table('payments as p')
            ->join('sales as s', 's.id', '=', 'p.sale_id')
            ->where('s.is_voided', false)
            ->whereNull('p.voided_at')
            ->whereRaw("(p.created_at AT TIME ZONE '{$tz}')::date between ?::date and ?::date", [$f, $t]);
    }

    public function data(Carbon $from, Carbon $to, string $jenis): array
    {
        $f = $from->toDateString();
        $t = $to->toDateString();

        // Penjualan
        $ringkas = $this->ringkas($from, $to);

        $produkJual = DB::table('sale_items as i')
            ->join('sales as s', 's.id', '=', 'i.sale_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->where('s.is_voided', false)
            ->whereBetween('s.sale_date', [$f, $t])
            ->selectRaw('p.name, sum(i.quantity) as qty, max(i.unit) as unit, sum(i.subtotal) as nilai')
            ->groupBy('p.name')->orderByDesc('nilai')->get();

        // Penerimaan kas (pembayaran yang masih berlaku, per tanggal diterima)
        $kasRows = $this->kasQuery($f, $t)
            ->selectRaw('p.payment_method as m, sum(p.amount) as total')
            ->groupBy('p.payment_method')->pluck('total', 'm');
        $tunai    = (float) ($kasRows['CASH'] ?? 0);
        $transfer = (float) ($kasRows['TRANSFER'] ?? 0);
        $kasTotal = (float) $kasRows->sum();

        // Piutang
        $dibayar = DB::table('payments')->whereNull('voided_at')
            ->selectRaw('sale_id, sum(amount) as paid')->groupBy('sale_id');
        $hutangBaru = (float) DB::table('sales as s')
            ->leftJoinSub($dibayar, 'p', 'p.sale_id', '=', 's.id')
            ->where('s.is_voided', false)
            ->whereBetween('s.sale_date', [$f, $t])
            ->selectRaw('coalesce(sum(greatest(s.total_amount - coalesce(p.paid,0), 0)),0) as v')
            ->value('v');
        $pelunasan = (float) $this->kasQuery($f, $t)->where('s.sale_date', '<', $f)->sum('p.amount');

        $ringkasanHutang = app(PiutangService::class)->ringkasanSemua();
        $sisaTotal = 0.0; $telatN = 0; $telatJumlah = 0.0;
        foreach ($ringkasanHutang as $r) {
            $sisaTotal += $r['saldo'];
            if ($r['terlambat_hari'] > 0) {
                $telatN++;
                $telatJumlah += $r['terlambat_jumlah'];
            }
        }

        // Pembelian ayam hidup
        $beli = DB::table('purchase_transactions')->whereBetween('purchase_date', [$f, $t])
            ->selectRaw('count(*) as n, coalesce(sum(total_birds),0) as ekor, coalesce(sum(total_weight_kg),0) as kg, coalesce(sum(total_cost),0) as biaya')->first();

        // Ayam mati
        $mati = DB::table('live_bird_mortalities')->whereBetween('record_date', [$f, $t])
            ->selectRaw('coalesce(sum(birds),0) as ekor, coalesce(sum(weight_kg),0) as kg')->first();
        $matiPersen = (int) $beli->ekor > 0 ? round($mati->ekor / $beli->ekor * 100, 1) : null;

        // Produksi dan rendemen
        $prod = DB::table('production_batches')->whereBetween('production_date', [$f, $t])
            ->selectRaw('count(*) as batch, coalesce(sum(input_birds),0) as ekor, coalesce(sum(input_weight_kg),0) as kg')->first();
        $hasilKg = (float) DB::table('weighing_records as w')
            ->join('production_batches as b', 'b.id', '=', 'w.batch_id')
            ->whereBetween('b.production_date', [$f, $t])->sum('w.weight_kg');
        $rendemen = (float) $prod->kg > 0 ? round($hasilKg / (float) $prod->kg * 100, 1) : null;
        $hasilProduk = DB::table('weighing_records as w')
            ->join('production_batches as b', 'b.id', '=', 'w.batch_id')
            ->join('products as p', 'p.id', '=', 'w.product_id')
            ->whereBetween('b.production_date', [$f, $t])
            ->selectRaw('p.name, sum(w.weight_kg) as kg')->groupBy('p.name')->orderByDesc('kg')->get();

        // Pengeluaran per kategori
        $exp = DB::table('expenses')->whereBetween('expense_date', [$f, $t])
            ->selectRaw('category, count(*) as n, sum(amount) as total')
            ->groupBy('category')->orderByDesc('total')->get();

        // Gaji (hanya laporan bulanan)
        $gaji = null;
        if ($jenis === 'bulanan') {
            $q = fn () => DB::table('payrolls')->where('period_year', $from->year)
                ->where('period_month', $from->month)->whereNull('voided_at');
            $gaji = [
                'slip'        => (int) $q()->count(),
                'bruto'       => (float) $q()->sum('gross_amount'),
                'bersih'      => (float) $q()->sum('net_amount'),
                'dibayar'     => (float) $q()->whereNotNull('paid_at')->sum('net_amount'),
                'belum'       => (float) $q()->whereNotNull('finalized_at')->whereNull('paid_at')->sum('net_amount'),
            ];
        }

        return [
            'ringkas' => $ringkas, 'produkJual' => $produkJual,
            'kas' => ['tunai' => $tunai, 'transfer' => $transfer, 'total' => $kasTotal],
            'piutang' => ['baru' => $hutangBaru, 'pelunasan' => $pelunasan, 'sisa' => $sisaTotal, 'telatN' => $telatN, 'telatJumlah' => $telatJumlah],
            'beli' => $beli, 'mati' => $mati, 'matiPersen' => $matiPersen,
            'prod' => $prod, 'hasilKg' => $hasilKg, 'rendemen' => $rendemen, 'hasilProduk' => $hasilProduk,
            'exp' => $exp, 'gaji' => $gaji,
        ];
    }
}