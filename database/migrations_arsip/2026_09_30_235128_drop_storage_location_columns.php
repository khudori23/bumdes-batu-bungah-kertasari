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

        // Buang index lama (kalau masih ada), lalu buat ulang tanpa kolom lokasi simpan
        DB::statement('DROP INDEX IF EXISTS idx_movements_fifo');

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index(['product_id', 'entry_date'], 'idx_movements_fifo');
        });
    }

    public function down(): void
    {
        // Sengaja kosong: konsep lokasi simpan sudah dihapus dari aplikasi.
    }
};