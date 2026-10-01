<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EmployeeLoan extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['loan_date' => 'date', 'amount' => 'decimal:2'];
    }

    public function employee() { return $this->belongsTo(Employee::class); }
}