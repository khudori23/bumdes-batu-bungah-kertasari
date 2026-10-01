<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LiveBirdOpname extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'opname_date'   => 'date',
            'system_birds'  => 'integer',
            'actual_birds'  => 'integer',
            'diff_birds'    => 'integer',
            'system_kg'     => 'decimal:2',
            'actual_kg'     => 'decimal:2',
            'diff_kg'       => 'decimal:2',
        ];
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}