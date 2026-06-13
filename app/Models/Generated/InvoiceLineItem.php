<?php
namespace App\Models\Generated;
use Illuminate\Database\Eloquent\Model;
class InvoiceLineItem extends Model
{
    protected $table = 'gen_invoices_line_items';
    protected $fillable = ['gen_invoice_id', 'particular', 'hsn', 'qty', 'unit', 'mrp', 'dis_flat', 'dis_pct', 'dis_on', 'amt', 'cgst_pct', 'sgst_pct', 'igst_pct', 'cess_pct', 'total_amt'];
}
