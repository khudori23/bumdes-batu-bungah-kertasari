<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['daily_rate' => 'decimal:2', 'per_kg_rate' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function loans() { return $this->hasMany(EmployeeLoan::class); }
    public function repayments() { return $this->hasMany(LoanRepayment::class); }
    public function payrolls() { return $this->hasMany(Payroll::class); }

    public function saldoKasbon(): float
    {
        return round((float) $this->loans()->sum('amount') - (float) $this->repayments()->sum('amount'), 2);
    }
}