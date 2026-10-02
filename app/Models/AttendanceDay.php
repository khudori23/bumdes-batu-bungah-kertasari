<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AttendanceDay extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'work_date'   => 'date',
            'closed_at'   => 'datetime',
            'pool_kg'     => 'decimal:2',
            'rate_per_kg' => 'decimal:2',
            'pool_amount' => 'decimal:2',
            'total_poin'  => 'decimal:2',
        ];
    }
}