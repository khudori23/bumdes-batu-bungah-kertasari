<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel ini dibuat manual lewat SQL Editor di produksi; jangan dibuat ulang.
        if (Schema::hasTable('live_bird_sales')) {
            return;
        }

        Schema::create('live_bird_sales', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->date('sale_date')->index();
            $t->string('customer_name', 100);
            $t->integer('birds');
            $t->decimal('weight_kg', 10, 2);
            $t->string('notes', 255)->nullable();
            $t->decimal('price_per_kg', 12, 2)->nullable();
            $t->decimal('total_amount', 14, 2)->nullable();
            $t->foreignUuid('priced_by_id')->nullable()->constrained('users');
            $t->timestampTz('priced_at')->nullable();
            $t->foreignUuid('sale_id')->nullable()->constrained('sales'); // nota kasir yang memakai catatan ini
            $t->foreignUuid('created_by_id')->constrained('users');
            $t->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_bird_sales');
    }
};