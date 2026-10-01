<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'is_voided' => 'boolean',
        ];
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    // Pembayaran yang dialokasikan ke nota ini
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Uang yang diterima saat nota ini dibuat (bisa melunasi nota lama)
    public function settlementPayments()
    {
        return $this->hasMany(Payment::class, 'settlement_sale_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function customerType()
    {
        return $this->belongsTo(CustomerType::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }
}