<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = [
        'department_name',
        'department_code',
        'numeric_code',
        'effective_date',
        'is_active',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'effective_date'  => 'date',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
