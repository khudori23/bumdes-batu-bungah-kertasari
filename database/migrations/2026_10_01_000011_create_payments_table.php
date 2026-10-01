<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sale_id')->constrained('sales');
            $table->string('payment_method', 20)->default('CASH');
            $table->decimal('amount', 14, 2);
            $table->string('reference_number', 100)->nullable();
            $table->foreignUuid('cashier_id')->constrained('users');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
