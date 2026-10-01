<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    /** Nilai bawaan kalau belum pernah diatur. */
    public const DEFAULT = [
        'app_name'          => 'BUMDes Batu Bungah Kertasari',
        'address'           => '',
        'phone'             => '',
        'logo_path'         => '',
        'favicon_path'      => '',
        'receipt_title'     => 'Nota Penjualan',
        'receipt_thanks'    => 'Terima kasih',
        'receipt_footer'    => '',
        'receipt_show_logo' => '1',
        'warna_header'      => '#0f172a',
        'warna_aksen'       => '#0d9488',
    ];

    /** Semua pengaturan (digabung dengan nilai bawaan). Aman walau tabel belum ada. */
    public static function semua(): array
    {
        try {
            $db = Cache::rememberForever('app_settings', fn () => static::query()->pluck('value', 'key')->all());
        } catch (\Throwable $e) {
            $db = [];
        }

        $out = array_merge(self::DEFAULT, array_map(fn ($v) => (string) $v, $db));
        $out['receipt_show_logo'] = $out['receipt_show_logo'] === '1';

        return $out;
    }

    public static function simpan(array $nilai): void
    {
        DB::transaction(function () use ($nilai) {
            foreach ($nilai as $k => $v) {
                static::updateOrCreate(['key' => $k], ['value' => $v]);
            }
        });

        Cache::forget('app_settings');
    }
}