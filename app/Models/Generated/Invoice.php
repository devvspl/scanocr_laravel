<?php
namespace App\Models\Generated;
use Illuminate\Database\Eloquent\Model;
class Invoice extends Model
{
    protected $table = 'gen_invoices';
    protected $fillable = ['invoice_no', 'invoice_date', 'purchase_order_no', 'purchase_order_date', 'buyer', 'vendor', 'buyer_address', 'vendor_address', 'dispatch_through', 'dispatch_date', 'subtotal', 'additional_discount', 'round_off', 'grand_total', 'invoice_summary', 'remark', 'auto_approve'];
    protected $casts = [
        'additional_discount' => 'float',
    ];

    public function line_items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InvoiceLineItem::class, 'gen_invoice_id');
    }
}
