<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'code', 'name', 'description', 'type',
        'item_group_id', 'unit_id', 'hsn_sac',
        'sale_price', 'purchase_price', 'tax_rate',
        'opening_stock', 'reorder_level', 'track_inventory',
        'is_active', 'created_by',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'track_inventory' => 'boolean',
        'sale_price'      => 'decimal:2',
        'purchase_price'  => 'decimal:2',
        'tax_rate'        => 'decimal:2',
        'opening_stock'   => 'decimal:3',
        'reorder_level'   => 'decimal:3',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ItemGroup::class, 'item_group_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
