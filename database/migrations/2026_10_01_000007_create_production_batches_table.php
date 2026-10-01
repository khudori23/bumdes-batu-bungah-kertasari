<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('batch_number', 50)->unique();
            $table->date('production_date');
            $table->integer('input_birds');
            $table->decimal('input_weight_kg', 10, 2);
            $table->string('status', 20)->default('PLANNED');
            $table->string('notes', 255)->nullable();
            $table->foreignUuid('created_by_id')->constrained('users');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_batches');
    }
};
