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
        return ['work_date' => 'date', 'kg_cut' => 'decimal:2'];
    }

    public function employee() { return $this->belongsTo(Employee::class); }
}