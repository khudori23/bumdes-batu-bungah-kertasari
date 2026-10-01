<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ProductionBatch extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'production_date' => 'date',
            'input_birds' => 'integer',
            'input_weight_kg' => 'decimal:2',
        ];
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function weighingRecords()
    {
        return $this->hasMany(WeighingRecord::class, 'batch_id');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'batch_id');
    }
}