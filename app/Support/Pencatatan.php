<?php

namespace App\Support;

use App\Services\AuditService;
use Illuminate\Support\Carbon;

/**
 * Aturan tanggal input manual untuk mengejar pencatatan yang tertinggal.
 * Atur lewat env: PENCATATAN_TANGGAL_MANUAL (true/false) dan PENCATATAN_MULAI (Y-m-d).
 */
class Pencatatan
{
    public static function manual(): bool
    {
        return (bool) config('pencatatan.tanggal_manual', true);
    }

    /** Tanggal paling awal yang boleh dipilih. Saat fitur dimatikan: hari ini. */
    public static function mulai(): string
    {
        $hariIni = now()->toDateString();

        if (! self::manual()) {
            return $hariIni;
        }

        $mulai = (string) config('pencatatan.mulai', '2026-10-01');

        return $mulai > $hariIni ? $hariIni : $mulai;
    }

    public static function aturan(bool $wajib = true): array
    {
        return [
            $wajib ? 'required' : 'nullable',
            'date',
            'before_or_equal:today',
            'after_or_equal:' . self::mulai(),
        ];
    }

    public static function tanggal(?string $input): Carbon
    {
        if (! self::manual() || ! $input) {
            return Carbon::today();
        }

        return Carbon::parse($input)->startOfDay();
    }

    /** Waktu simpan untuk transaksi bertanggal mundur (jam diambil dari saat input). */
    public static function waktu(?Carbon $tanggal, $tidakSebelum = null): Carbon
    {
        if (! $tanggal) {
            return now();
        }

        if ($tidakSebelum) {
            $min = Carbon::parse($tidakSebelum)->startOfDay();
            if ($tanggal->lt($min)) {
                $tanggal = $min->copy();
            }
        }

        if ($tanggal->toDateString() >= now()->toDateString()) {
            return now();
        }

        return $tanggal->copy()->setTimeFrom(now());
    }

    public static function catatMundur(string $tabel, string $id, Carbon $tanggal, array $info = []): void
    {
        if ($tanggal->toDateString() >= now()->toDateString()) {
            return;
        }

        AuditService::catat('CREATE', $tabel, $id, null, $info + [
            'catatan'           => 'Input tanggal mundur',
            'tanggal_transaksi' => $tanggal->toDateString(),
            'diinput_pada'      => now()->toDateTimeString(),
        ]);
    }
}