<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('expense_date');
            $table->string('category', 50);
            $table->decimal('amount', 14, 2);
            $table->string('description', 255)->nullable();
            $table->foreignUuid('recorded_by_id')->constrained('users');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
