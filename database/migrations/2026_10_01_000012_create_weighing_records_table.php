<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weighing_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('receipt_number', 50)->unique();
            $table->foreignUuid('batch_id')->nullable()->constrained('production_batches');
            $table->foreignUuid('product_id')->constrained('products');
            $table->foreignUuid('storage_location_id')->constrained('storage_locations');
            $table->decimal('weight_kg', 10, 2);
            $table->integer('pieces')->nullable();
            $table->foreignUuid('operator_id')->constrained('users');
            $table->string('notes', 255)->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weighing_records');
    }
};
