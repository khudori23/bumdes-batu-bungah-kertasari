<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('live_bird_sales', function (Blueprint $t) {
            if (! Schema::hasColumn('live_bird_sales', 'diverifikasi_oleh')) {
                $t->string('diverifikasi_oleh', 36)->nullable();
                $t->timestamp('diverifikasi_at')->nullable();
                $t->unsignedInteger('ekor_sebelum')->nullable();
                $t->decimal('berat_sebelum', 10, 2)->nullable();
                $t->string('alasan_koreksi')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('live_bird_sales', function (Blueprint $t) {
            $t->dropColumn(['diverifikasi_oleh', 'diverifikasi_at', 'ekor_sebelum', 'berat_sebelum', 'alasan_koreksi']);
        });
    }
};