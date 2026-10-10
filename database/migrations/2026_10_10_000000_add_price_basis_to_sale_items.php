<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            // UNIT = harga x jumlah (produk biasa dan nota lama), KG = harga per kg x berat timbang
            $table->string('price_basis', 10)->default('UNIT');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('price_basis');
        });
    }
};