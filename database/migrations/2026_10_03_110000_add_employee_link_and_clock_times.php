<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->foreignUuid('employee_id')->nullable()->unique()->constrained('employees')->nullOnDelete();
        });

        Schema::table('attendances', function (Blueprint $t) {
            $t->timestampTz('clock_in_at')->nullable();
            $t->timestampTz('clock_out_at')->nullable();
        });

        // Baris absen yang belum pulang belum punya status.
        DB::statement('ALTER TABLE attendances ALTER COLUMN status DROP NOT NULL');
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $t) {
            $t->dropColumn(['clock_in_at', 'clock_out_at']);
        });
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('employee_id');
        });
    }
};