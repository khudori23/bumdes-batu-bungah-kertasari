<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class KitchenOrderLine extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function order()
    {
        return $this->belongsTo(KitchenOrder::class, 'kitchen_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}