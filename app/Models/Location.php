<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'name',
        'code',
        'state_name',
        'state_code',
        'is_group',
        'is_active',
    ];

    protected $casts = [
        'is_group'  => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeNonGroup($query)
    {
        return $query->where('is_group', false);
    }
}
