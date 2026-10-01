<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $t) {
            $t->decimal('rendemen_bonus_kg', 10, 2)->default(0);
            $t->decimal('rendemen_bonus_amount', 14, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $t) {
            $t->dropColumn(['rendemen_bonus_kg', 'rendemen_bonus_amount']);
        });
    }
};