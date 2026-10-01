<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('adjustment_number', 50)->unique();
            $table->date('adjustment_date');
            $table->foreignUuid('product_id')->constrained('products');
            $table->foreignUuid('storage_location_id')->constrained('storage_locations');
            $table->decimal('system_qty', 10, 2);
            $table->decimal('physical_qty', 10, 2);
            $table->decimal('difference_qty', 10, 2);
            $table->string('reason', 255);
            $table->foreignUuid('adjusted_by_id')->constrained('users');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
