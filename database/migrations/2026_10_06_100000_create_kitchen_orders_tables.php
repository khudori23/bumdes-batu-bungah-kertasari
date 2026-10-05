<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kitchen_orders', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('order_number', 50)->unique();
            $t->date('order_date')->index();
            $t->foreignUuid('customer_id')->constrained('customers'); // pihak yang ditagih (divisi usaha konvensional)
            $t->foreignUuid('production_batch_id')->nullable()->constrained('production_batches');
            $t->decimal('live_weight_kg', 10, 2)->nullable();          // bobot ayam hidup yang dipakai memenuhi pesanan
            $t->decimal('price_per_kg', 12, 2)->nullable();
            $t->decimal('total_amount', 14, 2)->nullable();
            $t->string('status', 20)->default('DRAFT');
            $t->string('notes', 255)->nullable();
            $t->foreignUuid('created_by_id')->constrained('users');
            $t->timestampsTz();
        });

        Schema::create('kitchen_order_lines', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('kitchen_order_id')->constrained('kitchen_orders')->cascadeOnDelete();
            $t->string('kitchen_name', 100);
            $t->foreignUuid('product_id')->constrained('products');
            $t->decimal('quantity_kg', 10, 2);
            $t->string('notes', 255)->nullable();
            $t->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kitchen_order_lines');
        Schema::dropIfExists('kitchen_orders');
    }
};