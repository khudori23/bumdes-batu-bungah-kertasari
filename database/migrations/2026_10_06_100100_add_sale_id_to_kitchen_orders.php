<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kitchen_orders', function (Blueprint $t) {
            $t->foreignUuid('sale_id')->nullable()->constrained('sales'); // nota tagihan di tabel penjualan
        });
    }

    public function down(): void
    {
        Schema::table('kitchen_orders', function (Blueprint $t) {
            $t->dropConstrainedForeignId('sale_id');
        });
    }
};