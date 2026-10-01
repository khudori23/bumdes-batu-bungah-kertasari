<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pembayaran dicatat per nota lama (alokasi), tapi diterima di nota yang mana
        Schema::table('payments', function (Blueprint $t) {
            $t->foreignUuid('settlement_sale_id')->nullable()->constrained('sales');
        });

        // Gambaran hutang saat nota dibuat, supaya cetak ulang nota tetap akurat
        Schema::table('sales', function (Blueprint $t) {
            $t->decimal('previous_balance', 14, 2)->default(0);
            $t->decimal('balance_after', 14, 2)->default(0);
        });

        DB::statement('CREATE INDEX IF NOT EXISTS idx_payments_sale ON payments (sale_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_sales_customer ON sales (customer_id)');
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $t) {
            $t->dropColumn(['previous_balance', 'balance_after']);
        });
        Schema::table('payments', function (Blueprint $t) {
            $t->dropConstrainedForeignId('settlement_sale_id');
        });
    }
};