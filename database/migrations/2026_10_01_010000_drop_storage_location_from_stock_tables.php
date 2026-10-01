<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['weighing_records', 'stock_movements', 'stock_adjustments'] as $tableName) {
            if (Schema::hasColumn($tableName, 'storage_location_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('storage_location_id');
                });
            }
        }

        // Buang index lama (kalau ada), lalu buat ulang tanpa kolom lokasi simpan
        DB::statement('DROP INDEX IF EXISTS idx_movements_fifo');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_movements_fifo ON stock_movements (product_id, entry_date)');
    }

    public function down(): void
    {
        // Sengaja kosong: konsep lokasi simpan sudah dihapus dari aplikasi.
    }
};