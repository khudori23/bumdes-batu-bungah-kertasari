<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('live_bird_mortalities')) {
            Schema::create('live_bird_mortalities', function (Blueprint $t) {
                $t->uuid('id')->primary();
                $t->date('record_date')->index();
                $t->integer('birds');
                $t->decimal('weight_kg', 10, 2)->default(0);
                $t->string('cause', 100)->nullable();
                $t->string('notes', 255)->nullable();
                $t->foreignUuid('created_by_id')->constrained('users');
                $t->timestampsTz();
            });
        }

        if (! Schema::hasTable('live_bird_opnames')) {
            Schema::create('live_bird_opnames', function (Blueprint $t) {
                $t->uuid('id')->primary();
                $t->date('opname_date')->index();
                $t->integer('system_birds');
                $t->integer('actual_birds');
                $t->integer('diff_birds');
                $t->decimal('system_kg', 10, 2);
                $t->decimal('actual_kg', 10, 2);
                $t->decimal('diff_kg', 10, 2);
                $t->string('reason', 255)->nullable();
                $t->foreignUuid('created_by_id')->constrained('users');
                $t->timestampsTz();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('live_bird_opnames');
        Schema::dropIfExists('live_bird_mortalities');
    }
};