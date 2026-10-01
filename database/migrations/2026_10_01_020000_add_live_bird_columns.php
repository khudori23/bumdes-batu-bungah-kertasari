<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'is_live_bird')) {
            Schema::table('products', function (Blueprint $t) {
                $t->boolean('is_live_bird')->default(false);
            });
        }

        if (! Schema::hasColumn('sale_items', 'weight_kg')) {
            Schema::table('sale_items', function (Blueprint $t) {
                $t->decimal('weight_kg', 10, 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'is_live_bird')) {
            Schema::table('products', fn (Blueprint $t) => $t->dropColumn('is_live_bird'));
        }
        if (Schema::hasColumn('sale_items', 'weight_kg')) {
            Schema::table('sale_items', fn (Blueprint $t) => $t->dropColumn('weight_kg'));
        }
    }
};