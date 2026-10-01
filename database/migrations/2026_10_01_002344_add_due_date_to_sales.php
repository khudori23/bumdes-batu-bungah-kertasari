<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $t) {
            $t->date('due_date')->nullable();          // jatuh tempo nota ini
            $t->date('balance_due_date')->nullable();  // jatuh tempo hutang tertua saat nota dibuat
        });

        // Nota lama milik pelanggan diberi tempo bawaan
        $tempo = (int) config('kasir.tempo_hari', 7);
        DB::statement("UPDATE sales SET due_date = sale_date + {$tempo} WHERE customer_id IS NOT NULL AND due_date IS NULL");

        DB::statement('CREATE INDEX IF NOT EXISTS idx_sales_due_date ON sales (due_date)');
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $t) {
            $t->dropColumn(['due_date', 'balance_due_date']);
        });
    }
};