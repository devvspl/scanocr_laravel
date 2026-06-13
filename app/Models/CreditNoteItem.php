<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNoteItem extends Model
{
    protected $fillable = [
        'credit_note_id', 'product_id', 'description', 'hsn_sac',
        'qty', 'unit', 'unit_price', 'discount_pct', 'discount_amount', 'taxable_amount',
        'tax_rate', 'cgst_rate', 'sgst_rate', 'igst_rate',
        'cgst_amount', 'sgst_amount', 'igst_amount', 'total_tax', 'line_total',
        'sort_order',
    ];

    protected $casts = [
        'qty' => 'decimal:3', 'unit_price' => 'decimal:2',
        'discount_pct' => 'decimal:2', 'discount_amount' => 'decimal:2',
        'taxable_amount' => 'decimal:2', 'tax_rate' => 'decimal:2',
        'cgst_rate' => 'decimal:2', 'sgst_rate' => 'decimal:2', 'igst_rate' => 'decimal:2',
        'cgst_amount' => 'decimal:2', 'sgst_amount' => 'decimal:2', 'igst_amount' => 'decimal:2',
        'total_tax' => 'decimal:2', 'line_total' => 'decimal:2',
    ];

    public function creditNote(): BelongsTo { return $this->belongsTo(CreditNote::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
