<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    protected $fillable = [
        'name', 'code', 'type', 'rate', 'cgst', 'sgst', 'igst',
        'description', 'is_active', 'created_by',
    ];

    protected $casts = [
        'rate'      => 'float',
        'cgst'      => 'float',
        'sgst'      => 'float',
        'igst'      => 'float',
        'is_active' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hsnCodes()
    {
        return $this->hasMany(HsnCode::class, 'tax_rate_id');
    }
}
