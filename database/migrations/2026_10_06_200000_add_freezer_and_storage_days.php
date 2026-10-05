<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('weighing_records', 'storage_location_id')) {
            Schema::table('weighing_records', function (Blueprint $table) {
                $table->foreignUuid('storage_location_id')->nullable()->constrained('storage_locations')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('products', 'max_storage_days')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedSmallInteger('max_storage_days')->default(3);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('weighing_records', 'storage_location_id')) {
            Schema::table('weighing_records', function (Blueprint $table) {
                $table->dropConstrainedForeignId('storage_location_id');
            });
        }

        if (Schema::hasColumn('products', 'max_storage_days')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('max_storage_days');
            });
        }
    }
};