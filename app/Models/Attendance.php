<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'clock_in_at'  => 'datetime',
            'clock_out_at' => 'datetime',
            'kg_cut'    => 'decimal:2',
            'poin'      => 'decimal:2',
            'kg_bagian' => 'decimal:3',
            'bagian_rp' => 'decimal:2',
        ];
    }

    public function employee() { return $this->belongsTo(Employee::class); }
}