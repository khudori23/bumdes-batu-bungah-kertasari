<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PiutangService
{
    private function tempoBawaan(): int
    {
        return (int) config('kasir.tempo_hari', 7);
    }

    /**
     * Nota aktif (tidak di-void) milik pelanggan, lengkap dengan jumlah yang sudah dibayar.
     * Pembayaran yang sudah dibatalkan (voided_at terisi) tidak dihitung.
     */
    private function dasar()
    {
        return DB::table('sales as s')
            ->leftJoinSub(
                DB::table('payments')
                    ->whereNull('voided_at')
                    ->selectRaw('sale_id, sum(amount) as paid')
                    ->groupBy('sale_id'),
                'p',
                'p.sale_id',
                '=',
                's.id'
            )
            ->where('s.is_voided', false)
            ->whereNotNull('s.customer_id');
    }

    /** Sisa hutang semua pelanggan: [customer_id => saldo] */
    public function saldoSemua(): array
    {
        return $this->dasar()
            ->groupBy('s.customer_id')
            ->selectRaw('s.customer_id, coalesce(sum(s.total_amount - coalesce(p.paid, 0)), 0) as saldo')
            ->pluck('saldo', 'customer_id')
            ->map(fn ($v) => round((float) $v, 2))
            ->all();
    }

    /** Sisa hutang satu pelanggan. */
    public function saldo(string $customerId): float
    {
        $v = $this->dasar()
            ->where('s.customer_id', $customerId)
            ->selectRaw('coalesce(sum(s.total_amount - coalesce(p.paid, 0)), 0) as saldo')
            ->value('saldo');

        return round((float) $v, 2);
    }

    /** Semua nota yang masih ada sisanya, urut dari yang paling lama. Opsional: untuk satu pelanggan. */
    public function notaTerbukaSemua(?string $customerId = null): Collection
    {
        $tempo = $this->tempoBawaan();

        return $this->dasar()
            ->when($customerId, fn ($q) => $q->where('s.customer_id', $customerId))
            ->whereRaw('(s.total_amount - coalesce(p.paid, 0)) > 0.004')
            ->orderBy('s.sale_date')
            ->orderBy('s.created_at')
            ->orderBy('s.sale_number')
            ->selectRaw("s.id, s.customer_id, s.customer_name, s.sale_number, s.sale_date, s.total_amount, (s.total_amount - coalesce(p.paid, 0)) as sisa, coalesce(s.due_date, s.sale_date + {$tempo}) as jatuh_tempo")
            ->get();
    }

    /** Nota satu pelanggan yang masih ada sisanya, urut dari yang paling lama. */
    public function notaTerbuka(string $customerId): Collection
    {
        return $this->notaTerbukaSemua($customerId);
    }

    /**
     * Ringkasan hutang per pelanggan yang masih punya hutang:
     * [customer_id => [saldo, jatuh_tempo (tertua), terlambat_hari, terlambat_jumlah]]
     */
    public function ringkasanSemua(): array
    {
        $hariIni = Carbon::today();
        $hasil = [];

        foreach ($this->notaTerbukaSemua() as $r) {
            $id = $r->customer_id;

            $hasil[$id] ??= [
                'saldo'            => 0.0,
                'jatuh_tempo'      => null,
                'terlambat_hari'   => 0,
                'terlambat_jumlah' => 0.0,
            ];

            $hasil[$id]['saldo'] = round($hasil[$id]['saldo'] + (float) $r->sisa, 2);

            if ($hasil[$id]['jatuh_tempo'] === null || $r->jatuh_tempo < $hasil[$id]['jatuh_tempo']) {
                $hasil[$id]['jatuh_tempo'] = $r->jatuh_tempo;
            }

            $due = Carbon::parse($r->jatuh_tempo)->startOfDay();
            if ($due->lt($hariIni)) {
                $hasil[$id]['terlambat_jumlah'] = round($hasil[$id]['terlambat_jumlah'] + (float) $r->sisa, 2);
                $hasil[$id]['terlambat_hari'] = max($hasil[$id]['terlambat_hari'], (int) abs($due->diffInDays($hariIni)));
            }
        }

        return $hasil;
    }

    /**
     * Terima pembayaran dan lunasi nota tertua dulu.
     * Mengembalikan jumlah yang benar-benar teralokasi.
     *
     * Berjalan dalam transaksi dengan kunci per pelanggan, jadi aman dipanggil
     * dari halaman Piutang maupun dari kasir, bahkan saat dua kasir bekerja bersamaan.
     */
    public function bayar(
        string $customerId,
        float $jumlah,
        string $metode,
        ?string $referensi,
        string $kasirId,
        ?string $notaPenerimaId = null,
        ?Carbon $tanggal = null
    ): float {
        $sisaUang = round($jumlah, 2);

        if ($sisaUang <= 0.004) {
            return 0.0;
        }

        return DB::transaction(function () use ($customerId, $sisaUang, $metode, $referensi, $kasirId, $notaPenerimaId, $tanggal) {
            // Kunci yang sama dengan KasirController (boleh diambil ulang dalam sesi yang sama)
            DB::select('select pg_advisory_xact_lock(?)', [(int) crc32('cust_' . $customerId)]);

            $teralokasi = 0.0;

            foreach ($this->notaTerbuka($customerId) as $nota) {
                if ($sisaUang <= 0.004) {
                    break;
                }

                $bagian = round(min($sisaUang, (float) $nota->sisa), 2);

                Payment::create([
                    'sale_id'            => $nota->id,
                    'payment_method'     => $metode,
                    'amount'             => $bagian,
                    'reference_number'   => $referensi,
                    'cashier_id'         => $kasirId,
                    'settlement_sale_id' => $notaPenerimaId,
                    'created_at'         => \App\Support\Pencatatan::waktu($tanggal, $nota->sale_date),
                ]);

                $sisaUang   = round($sisaUang - $bagian, 2);
                $teralokasi = round($teralokasi + $bagian, 2);
            }

            return $teralokasi;
        });
    }
}