<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryNoteItem extends Model
{
    protected $fillable = [
        'delivery_note_id', 'product_id', 'description', 'product_code', 'hsn_sac',
        'qty', 'unit', 'weight', 'remarks', 'sort_order',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
    ];

    public function deliveryNote(): BelongsTo { return $this->belongsTo(DeliveryNote::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
