<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function customerType()
    {
        return $this->belongsTo(CustomerType::class);
    }
}