<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_days', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->date('work_date')->unique();
            $t->decimal('pool_kg', 10, 2)->default(0);
            $t->decimal('rate_per_kg', 12, 2)->default(0);
            $t->decimal('pool_amount', 14, 2)->default(0);
            $t->decimal('total_poin', 8, 2)->default(0);
            $t->unsignedSmallInteger('participants')->default(0);
            $t->timestampTz('closed_at')->nullable();
            $t->foreignUuid('closed_by_id')->nullable()->constrained('users');
            $t->timestampsTz();
        });

        Schema::table('attendances', function (Blueprint $t) {
            $t->decimal('poin', 5, 2)->default(0);
            $t->decimal('kg_bagian', 10, 3)->default(0);
            $t->decimal('bagian_rp', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $t) {
            $t->dropColumn(['poin', 'kg_bagian', 'bagian_rp']);
        });
        Schema::dropIfExists('attendance_days');
    }
};