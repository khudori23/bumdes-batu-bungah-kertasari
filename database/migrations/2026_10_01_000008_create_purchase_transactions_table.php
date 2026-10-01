<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('transaction_number', 50)->unique();
            $table->date('purchase_date');
            $table->foreignUuid('supplier_id')->constrained('suppliers');
            $table->integer('total_birds');
            $table->decimal('total_weight_kg', 10, 2);
            $table->decimal('unit_price_per_kg', 12, 2);
            $table->decimal('total_cost', 14, 2);
            $table->string('notes', 255)->nullable();
            $table->foreignUuid('received_by_id')->constrained('users');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_transactions');
    }
};
