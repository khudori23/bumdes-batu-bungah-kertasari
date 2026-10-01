<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'days_present'           => 'decimal:1',
            'daily_rate'             => 'decimal:2',
            'base_amount'            => 'decimal:2',
            'kg_total'               => 'decimal:2',
            'per_kg_rate'            => 'decimal:2',
            'kg_amount'              => 'decimal:2',
            'bonus_amount'           => 'decimal:2',
            'rendemen_bonus_kg'      => 'decimal:2',
            'rendemen_bonus_amount'  => 'decimal:2',
            'other_deduction_amount' => 'decimal:2',
            'loan_deduction_amount'  => 'decimal:2',
            'gross_amount'           => 'decimal:2',
            'net_amount'             => 'decimal:2',
            'attendance_counts'      => 'array',
            'finalized_at'           => 'datetime',
            'paid_at'                => 'datetime',
            'voided_at'              => 'datetime',
        ];
    }

    public function employee() { return $this->belongsTo(Employee::class); }
    public function finalizedBy() { return $this->belongsTo(User::class, 'finalized_by_id'); }
}