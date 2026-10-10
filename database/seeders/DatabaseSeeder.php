<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Akun awal. GANTI semua password setelah login pertama.
        $akun = [
            ['username' => 'admin',    'name' => 'Administrator',    'role' => User::ROLE_ADMIN,    'password' => 'admin12345'],
            ['username' => 'manager',  'name' => 'Manager',          'role' => User::ROLE_MANAGER,  'password' => 'manager12345'],
            ['username' => 'pengawas', 'name' => 'Pengawas Kandang', 'role' => User::ROLE_PENGAWAS, 'password' => 'pengawas12345'],
            ['username' => 'kasir',    'name' => 'Kasir',            'role' => User::ROLE_KASIR,    'password' => 'kasir12345'],
        ];
        
        foreach ($akun as $a) {
            User::firstOrCreate(
                ['username' => $a['username']],
                [
                    'name' => $a['name'],
                    'email' => null,
                    'password' => $a['password'],
                    'role' => $a['role'],
                    'is_active' => true,
                ]
            );
        }

        // Data master: jenis pelanggan, produk, harga
        $this->call(MasterDataSeeder::class);
        $this->call(AyamHidupSeeder::class);
    }
}