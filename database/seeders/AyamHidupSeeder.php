<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AyamHidupSeeder extends Seeder
{
    /**
     * Harga jual ayam hidup per EKOR (Rp), dipakai untuk semua jenis pelanggan.
     * Isi angka lalu jalankan seeder ini. Kalau 0, harga tidak dibuat
     * dan harus diisi manual di tabel product_prices.
     */
    private const HARGA_PER_EKOR = 0;

    public function run(): void
    {
        $produk = Product::whereRaw('lower(name) like ?', ['%ayam hidup%'])->first();

        if ($produk) {
            $produk->update(['is_live_bird' => true]);
        } else {
            $produk = Product::create([
                'code'         => 'AYAM_HIDUP',
                'name'         => 'Ayam Hidup',
                'primary_unit' => 'EKOR',
                'description'  => 'Dijual per ekor',
                'is_active'    => true,
                'is_live_bird' => true,
            ]);
        }

        if (self::HARGA_PER_EKOR > 0) {
            $now = now();

            foreach (DB::table('customer_types')->where('is_active', true)->pluck('id') as $typeId) {
                $ada = DB::table('product_prices')
                    ->where('product_id', $produk->id)->where('customer_type_id', $typeId)->first();

                if ($ada) {
                    DB::table('product_prices')->where('id', $ada->id)
                        ->update(['price' => self::HARGA_PER_EKOR, 'is_active' => true, 'updated_at' => $now]);
                } else {
                    DB::table('product_prices')->insert([
                        'id'               => (string) Str::uuid(),
                        'product_id'       => $produk->id,
                        'customer_type_id' => $typeId,
                        'price'            => self::HARGA_PER_EKOR,
                        'is_active'        => true,
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ]);
                }
            }
        }
    }
}