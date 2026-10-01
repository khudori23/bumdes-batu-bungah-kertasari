<?php

namespace Database\Seeders;

use App\Models\CustomerType;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // Jenis pelanggan: kode => nama
        $customerTypes = [
            'MASYARAKAT_UMUM' => 'Masyarakat Umum',
            'KARYAWAN'        => 'Karyawan',
            'UMKM'            => 'UMKM',
        ];

        // Produk: kode => [nama, satuan, harga per jenis pelanggan]
        $products = [
            'AYAM-POTONG' => ['Ayam Potong', 'KG', [
                'MASYARAKAT_UMUM' => 42000, 'KARYAWAN' => 40000,
            ]],
            'AYAM-FILLET' => ['Ayam Fillet', 'KG', [
                'MASYARAKAT_UMUM' => 55000, 'KARYAWAN' => 53000,
            ]],
            'FILLET-DADA' => ['Fillet Dada', 'KG', [
                'MASYARAKAT_UMUM' => 56000, 'KARYAWAN' => 54000,
            ]],
            'FILLET-PAHA' => ['Fillet Paha', 'KG', [
                'MASYARAKAT_UMUM' => 54000, 'KARYAWAN' => 52000,
            ]],
            'SAYAP' => ['Sayap', 'KG', [
                'MASYARAKAT_UMUM' => 38000, 'KARYAWAN' => 35000,
            ]],
            'ATI-AMPELA' => ['Ati Ampela', 'KG', [
                'MASYARAKAT_UMUM' => 20000, 'KARYAWAN' => 16000, 'UMKM' => 15000,
            ]],
            'CEKER' => ['Ceker', 'KG', [
                'MASYARAKAT_UMUM' => 17000, 'KARYAWAN' => 14000, 'UMKM' => 13000,
            ]],
            'KEPALA' => ['Kepala', 'KG', [
                'MASYARAKAT_UMUM' => 7000, 'KARYAWAN' => 6000, 'UMKM' => 5000,
            ]],
            'TULANG-TONGGONG' => ['Tulang Tonggong', 'KG', [
                'MASYARAKAT_UMUM' => 17000, 'KARYAWAN' => 14000, 'UMKM' => 13000,
            ]],
            'TUNGIR-KULIT' => ['Tungir Kulit', 'KG', [
                'MASYARAKAT_UMUM' => 16000, 'KARYAWAN' => 14000, 'UMKM' => 13000,
            ]],
            'USUS' => ['Usus', 'KG', [
                'MASYARAKAT_UMUM' => 7000, 'KARYAWAN' => 5000, 'UMKM' => 3000,
            ]],
        ];

        // 1. Jenis pelanggan
        $typeIds = [];
        foreach ($customerTypes as $code => $name) {
            $type = CustomerType::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'is_active' => true]
            );
            $typeIds[$code] = $type->id;
        }

        // 2. Produk + 3. Harga per jenis pelanggan
        foreach ($products as $code => [$name, $unit, $prices]) {
            $product = Product::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'primary_unit' => $unit, 'is_active' => true]
            );

            foreach ($prices as $typeCode => $price) {
                ProductPrice::updateOrCreate(
                    [
                        'product_id'       => $product->id,
                        'customer_type_id' => $typeIds[$typeCode],
                    ],
                    ['price' => $price, 'is_active' => true]
                );
            }
        }
    }
}