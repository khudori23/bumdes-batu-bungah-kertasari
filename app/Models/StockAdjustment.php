<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'adjustment_date' => 'date',
            'system_qty' => 'decimal:2',
            'physical_qty' => 'decimal:2',
            'difference_qty' => 'decimal:2',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function adjustedBy()
    {
        return $this->belongsTo(User::class, 'adjusted_by_id');
    }
}