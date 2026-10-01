<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WeighingRecord extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:2',
            'pieces' => 'integer',
        ];
    }

    public function batch()
    {
        return $this->belongsTo(ProductionBatch::class, 'batch_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'weighing_record_id');
    }
}