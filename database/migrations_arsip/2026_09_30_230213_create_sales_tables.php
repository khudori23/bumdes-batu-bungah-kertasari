<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $t) {
                $t->uuid('id')->primary();
                $t->string('name', 100);
                $t->string('phone', 30)->nullable();
                $t->string('address', 255)->nullable();
                $t->foreignUuid('customer_type_id')->constrained('customer_types');
                $t->boolean('is_active')->default(true);
                $t->timestampsTz();
            });
        }

        if (! Schema::hasTable('sales')) {
            Schema::create('sales', function (Blueprint $t) {
                $t->uuid('id')->primary();
                $t->string('sale_number', 50)->unique();
                $t->date('sale_date')->index();
                $t->foreignUuid('customer_id')->nullable()->constrained('customers');
                $t->string('customer_name', 100);
                $t->foreignUuid('customer_type_id')->constrained('customer_types');
                $t->decimal('subtotal', 14, 2);
                $t->decimal('discount', 14, 2)->default(0);
                $t->decimal('total_amount', 14, 2);
                $t->string('status', 20)->default('COMPLETED');
                $t->boolean('is_voided')->default(false);
                $t->string('void_reason', 255)->nullable();
                $t->timestampTz('voided_at')->nullable();
                $t->foreignUuid('voided_by_id')->nullable()->constrained('users');
                $t->foreignUuid('cashier_id')->constrained('users');
                $t->timestampsTz();
            });
        }

        if (! Schema::hasTable('sale_items')) {
            Schema::create('sale_items', function (Blueprint $t) {
                $t->uuid('id')->primary();
                $t->foreignUuid('sale_id')->constrained('sales')->cascadeOnDelete();
                $t->foreignUuid('product_id')->constrained('products');
                $t->decimal('quantity', 10, 2);
                $t->string('unit', 10)->default('KG');
                $t->decimal('unit_price', 12, 2);
                $t->decimal('subtotal', 14, 2);
                $t->timestampsTz();
            });
        }

        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $t) {
                $t->uuid('id')->primary();
                $t->foreignUuid('sale_id')->constrained('sales');
                $t->string('payment_method', 20)->default('CASH');
                $t->decimal('amount', 14, 2);
                $t->string('reference_number', 100)->nullable();
                $t->foreignUuid('cashier_id')->constrained('users');
                $t->timestampsTz();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('customers');
    }
};