<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('action', 20);
            $table->string('table_name', 50);
            $table->string('record_id', 100);
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->string('reason', 255)->nullable();
            $table->foreignUuid('user_id')->nullable()->constrained('users');
            $table->timestampTz('timestamp')->useCurrent();

            $table->index(['table_name', 'record_id'], 'idx_audit_tbl_rec');
            $table->index('timestamp', 'idx_audit_timestamp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
