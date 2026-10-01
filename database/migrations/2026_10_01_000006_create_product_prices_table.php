<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products');
            $table->foreignUuid('customer_type_id')->constrained('customer_types');
            $table->decimal('price', 12, 2);
            $table->timestampTz('effective_date')->useCurrent();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->index(['product_id', 'customer_type_id', 'is_active'], 'idx_prices_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
