<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('movement_number', 50)->unique();
            $table->string('movement_type', 30);
            $table->foreignUuid('product_id')->constrained('products');
            $table->foreignUuid('storage_location_id')->constrained('storage_locations');
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 10)->default('KG');
            $table->foreignUuid('batch_id')->nullable()->constrained('production_batches');
            $table->foreignUuid('weighing_record_id')->nullable()->constrained('weighing_records');
            $table->string('reference_id', 100)->nullable();
            $table->timestampTz('entry_date');
            $table->foreignUuid('created_by_id')->constrained('users');
            $table->string('notes', 255)->nullable();
            $table->timestampsTz();

            $table->index(['product_id', 'storage_location_id', 'entry_date'], 'idx_movements_fifo');
            $table->index(['movement_type', 'product_id'], 'idx_movements_type_prod');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
