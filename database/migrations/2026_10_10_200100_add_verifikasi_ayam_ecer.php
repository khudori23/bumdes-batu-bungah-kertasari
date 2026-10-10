<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $kolom = [
        'sumber', 'ekor_pengawas', 'berat_pengawas', 'catatan_verifikasi',
    ];

    public function up(): void
    {
        Schema::table('live_bird_sales', function (Blueprint $t) {
            if (! Schema::hasColumn('live_bird_sales', 'sumber')) {
                $t->string('sumber', 10)->nullable();            // KASIR = dicatat langsung oleh kasir
            }
            if (! Schema::hasColumn('live_bird_sales', 'diverifikasi_oleh')) {
                $t->string('diverifikasi_oleh', 36)->nullable();
            }
            if (! Schema::hasColumn('live_bird_sales', 'diverifikasi_at')) {
                $t->timestampTz('diverifikasi_at')->nullable();
            }
            if (! Schema::hasColumn('live_bird_sales', 'ekor_pengawas')) {
                $t->unsignedInteger('ekor_pengawas')->nullable();   // hasil hitung pengawas bila beda
            }
            if (! Schema::hasColumn('live_bird_sales', 'berat_pengawas')) {
                $t->decimal('berat_pengawas', 10, 2)->nullable();
            }
            if (! Schema::hasColumn('live_bird_sales', 'catatan_verifikasi')) {
                $t->string('catatan_verifikasi', 255)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('live_bird_sales', function (Blueprint $t) {
            $ada = array_values(array_filter($this->kolom, fn ($k) => Schema::hasColumn('live_bird_sales', $k)));
            if ($ada) {
                $t->dropColumn($ada);
            }
        });
    }
};