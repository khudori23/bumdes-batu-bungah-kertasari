<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('code', 20)->unique();
            $t->string('name', 100);
            $t->string('type', 10)->default('KARYAWAN'); // KARYAWAN | RELAWAN
            $t->string('position', 60)->nullable();
            $t->string('phone', 30)->nullable();
            $t->decimal('daily_rate', 12, 2)->default(0);
            $t->decimal('per_kg_rate', 12, 2)->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestampsTz();
        });

        Schema::create('attendances', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('employee_id')->constrained('employees');
            $t->date('work_date');
            $t->string('status', 10); // HADIR | SETENGAH | IZIN | SAKIT | ALFA
            $t->decimal('kg_cut', 10, 2)->default(0);
            $t->string('notes', 255)->nullable();
            $t->foreignUuid('recorded_by_id')->constrained('users');
            $t->timestampsTz();
            $t->unique(['employee_id', 'work_date']);
            $t->index('work_date');
        });

        Schema::create('employee_loans', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('employee_id')->constrained('employees');
            $t->date('loan_date');
            $t->decimal('amount', 12, 2);
            $t->string('notes', 255)->nullable();
            $t->foreignUuid('recorded_by_id')->constrained('users');
            $t->timestampsTz();
        });

        Schema::create('payrolls', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('payroll_number', 30)->nullable()->unique();
            $t->foreignUuid('employee_id')->constrained('employees');
            $t->smallInteger('period_year');
            $t->smallInteger('period_month');
            $t->decimal('days_present', 5, 1)->default(0);
            $t->decimal('daily_rate', 12, 2)->default(0);
            $t->decimal('base_amount', 14, 2)->default(0);
            $t->decimal('kg_total', 10, 2)->default(0);
            $t->decimal('per_kg_rate', 12, 2)->default(0);
            $t->decimal('kg_amount', 14, 2)->default(0);
            $t->decimal('bonus_amount', 14, 2)->default(0);
            $t->string('bonus_note', 150)->nullable();
            $t->decimal('other_deduction_amount', 14, 2)->default(0);
            $t->string('other_deduction_note', 150)->nullable();
            $t->decimal('loan_deduction_amount', 14, 2)->default(0);
            $t->decimal('gross_amount', 14, 2)->default(0);
            $t->decimal('net_amount', 14, 2)->default(0);
            $t->json('attendance_counts')->nullable();
            $t->string('status', 10)->default('DRAFT'); // DRAFT | FINAL | PAID | VOID
            $t->timestampTz('finalized_at')->nullable();
            $t->foreignUuid('finalized_by_id')->nullable()->constrained('users');
            $t->timestampTz('paid_at')->nullable();
            $t->foreignUuid('paid_by_id')->nullable()->constrained('users');
            $t->timestampTz('voided_at')->nullable();
            $t->foreignUuid('voided_by_id')->nullable()->constrained('users');
            $t->string('void_reason', 255)->nullable();
            $t->foreignUuid('created_by_id')->constrained('users');
            $t->timestampsTz();
            $t->index(['period_year', 'period_month']);
        });

        // Satu slip aktif per karyawan per bulan (slip batal tidak dihitung)
        DB::statement("CREATE UNIQUE INDEX payrolls_emp_period_active ON payrolls (employee_id, period_year, period_month) WHERE status <> 'VOID'");

        Schema::create('loan_repayments', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('employee_id')->constrained('employees');
            $t->foreignUuid('payroll_id')->constrained('payrolls')->cascadeOnDelete();
            $t->decimal('amount', 12, 2);
            $t->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_repayments');
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('employee_loans');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('employees');
    }
};