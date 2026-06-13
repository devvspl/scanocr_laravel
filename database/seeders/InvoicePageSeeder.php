<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\PageField;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class InvoicePageSeeder extends Seeder
{
    public function run(): void
    {
        // Get first user or use ID 1
        $userId = \App\Models\User::first()?->id ?? 1;

        $page = Page::create([
            'user_id' => $userId,
            'page_name' => 'Invoice',
            'is_generated' => false,
            'settings' => [
                'currency' => '₹',
                'locale' => 'en-IN',
                'decimal_precision' => 2,
                'round_off_rule' => 'round', // none, round, floor, ceil
                'auto_save_draft' => false,
                'allow_edit_after_submit' => false,
                'title' => 'Purchase Invoice',
                'description' => 'Invoice entry with line items, discount, tax and totals',
            ],
        ]);

        $sort = 0;

        // ═══════════════════════════════════════════════════════════════
        // HEADER FIELDS
        // ═══════════════════════════════════════════════════════════════

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Invoice No',
            'field_key' => 'invoice_no',
            'field_type' => 'title',
            'sort_order' => $sort++,
            'col_span' => 1,
            'label' => 'Invoice No.',
            'is_required' => true,
            'placeholder' => 'INV-001',
        ]);

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Invoice Date',
            'field_key' => 'invoice_date',
            'field_type' => 'date',
            'sort_order' => $sort++,
            'col_span' => 1,
            'label' => 'Invoice Date',
            'is_required' => true,
            'options' => ['use_current_date' => true],
        ]);

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Purchase Order No',
            'field_key' => 'po_no',
            'field_type' => 'title',
            'sort_order' => $sort++,
            'col_span' => 1,
            'label' => 'Purchase Order No.',
            'placeholder' => 'PO-001',
        ]);

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Purchase Order Date',
            'field_key' => 'po_date',
            'field_type' => 'date',
            'sort_order' => $sort++,
            'col_span' => 1,
            'label' => 'Purchase Order Date',
        ]);

        // ── Buyer & Vendor ──

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Buyer',
            'field_key' => 'buyer',
            'field_type' => 'select',
            'sort_order' => $sort++,
            'col_span' => 1,
            'label' => 'Buyer',
            'is_required' => true,
            'options' => [
                'dynamic' => [
                    'enabled' => true,
                    'table' => 'companies',
                    'label_col' => 'name',
                    'value_col' => 'id',
                ],
                'static' => [],
            ],
            'auto_fill' => [
                'enabled' => true,
                'source_table' => 'companies',
                'mappings' => [
                    ['source_column' => 'address', 'target_field_key' => 'buyer_address'],
                ],
            ],
        ]);

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Vendor',
            'field_key' => 'vendor',
            'field_type' => 'select',
            'sort_order' => $sort++,
            'col_span' => 1,
            'label' => 'Vendor',
            'is_required' => true,
            'options' => [
                'dynamic' => [
                    'enabled' => true,
                    'table' => 'vendors',
                    'label_col' => 'name',
                    'value_col' => 'id',
                ],
                'static' => [],
            ],
            'auto_fill' => [
                'enabled' => true,
                'source_table' => 'vendors',
                'mappings' => [
                    ['source_column' => 'address', 'target_field_key' => 'vendor_address'],
                    ['source_column' => 'gstin', 'target_field_key' => 'vendor_gstin'],
                    ['source_column' => 'city', 'target_field_key' => 'vendor_city'],
                ],
            ],
        ]);

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Buyer Address',
            'field_key' => 'buyer_address',
            'field_type' => 'content',
            'sort_order' => $sort++,
            'col_span' => 1,
            'label' => 'Buyer Address',
            'placeholder' => 'Address',
        ]);

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Vendor Address',
            'field_key' => 'vendor_address',
            'field_type' => 'content',
            'sort_order' => $sort++,
            'col_span' => 1,
            'label' => 'Vendor Address',
            'placeholder' => 'Address',
        ]);

        // ── Dispatch Details ──

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Dispatch Through',
            'field_key' => 'dispatch_through',
            'field_type' => 'title',
            'sort_order' => $sort++,
            'col_span' => 1,
            'label' => 'Dispatch Through',
            'placeholder' => 'Transport name',
        ]);

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Dispatch Date',
            'field_key' => 'dispatch_date',
            'field_type' => 'date',
            'sort_order' => $sort++,
            'col_span' => 1,
            'label' => 'Dispatch Date',
        ]);

        // ═══════════════════════════════════════════════════════════════
        // LINE ITEMS TABLE (Repeater with formula columns)
        // ═══════════════════════════════════════════════════════════════

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Line Items',
            'field_key' => 'items',
            'field_type' => 'repeater',
            'sort_order' => $sort++,
            'col_span' => 3,
            'label' => 'Line Items',
            'repeater_columns' => [
                [
                    'key' => 'particular',
                    'label' => 'Particular',
                    'type' => 'text',
                    'required' => true,
                    'default' => '',
                ],
                [
                    'key' => 'hsn',
                    'label' => 'HSN',
                    'type' => 'text',
                    'required' => false,
                    'default' => '',
                ],
                [
                    'key' => 'qty',
                    'label' => 'Qty',
                    'type' => 'number',
                    'required' => true,
                    'default' => '1',
                ],
                [
                    'key' => 'unit',
                    'label' => 'Unit',
                    'type' => 'select',
                    'required' => false,
                    'default' => 'PCS',
                    'options' => [
                        ['label' => 'PCS', 'value' => 'PCS'],
                        ['label' => 'PACKS', 'value' => 'PACKS'],
                        ['label' => 'KG', 'value' => 'KG'],
                        ['label' => 'LTR', 'value' => 'LTR'],
                        ['label' => 'MTR', 'value' => 'MTR'],
                        ['label' => 'BOX', 'value' => 'BOX'],
                        ['label' => 'NOS', 'value' => 'NOS'],
                    ],
                ],
                [
                    'key' => 'mrp',
                    'label' => 'MRP',
                    'type' => 'decimal',
                    'required' => true,
                    'default' => '0',
                ],
                [
                    'key' => 'dis_flat',
                    'label' => 'Dis. (₹)',
                    'type' => 'decimal',
                    'required' => false,
                    'default' => '0',
                ],
                [
                    'key' => 'dis_pct',
                    'label' => 'Dis. (%)',
                    'type' => 'decimal',
                    'required' => false,
                    'default' => '0',
                ],
                [
                    'key' => 'dis_on',
                    'label' => 'Dis. On',
                    'type' => 'select',
                    'required' => false,
                    'default' => 'before_tax',
                    'options' => [
                        ['label' => 'Before Tax', 'value' => 'before_tax'],
                        ['label' => 'On MRP', 'value' => 'on_mrp'],
                        ['label' => 'After Tax', 'value' => 'after_tax'],
                    ],
                ],
                [
                    'key' => 'amt',
                    'label' => 'Amt',
                    'type' => 'formula',
                    'required' => false,
                    'default' => '',
                    // Conditional discount:
                    // "before_tax": Amt = qty*mrp - dis_flat - (qty*mrp*dis_pct/100)
                    // "on_mrp": Amt = qty * (mrp - dis_flat - (mrp*dis_pct/100))
                    // "after_tax": Amt = qty*mrp (discount applied later on total_amt)
                    'formula' => 'IF({dis_on} == "after_tax", {qty} * {mrp}, IF({dis_on} == "on_mrp", {qty} * ({mrp} - {dis_flat} - ({mrp} * {dis_pct} / 100)), {qty} * {mrp} - {dis_flat} - ({qty} * {mrp} * {dis_pct} / 100)))',
                    'show_summary' => true,
                ],
                [
                    'key' => 'cgst_pct',
                    'label' => 'CGST %',
                    'type' => 'decimal',
                    'required' => false,
                    'default' => '0',
                ],
                [
                    'key' => 'sgst_pct',
                    'label' => 'SGST %',
                    'type' => 'decimal',
                    'required' => false,
                    'default' => '0',
                ],
                [
                    'key' => 'igst_pct',
                    'label' => 'IGST %',
                    'type' => 'decimal',
                    'required' => false,
                    'default' => '0',
                ],
                [
                    'key' => 'cess_pct',
                    'label' => 'Cess %',
                    'type' => 'decimal',
                    'required' => false,
                    'default' => '0',
                ],
                [
                    'key' => 'total_amt',
                    'label' => 'Total Amt',
                    'type' => 'formula',
                    'required' => false,
                    'default' => '',
                    // Total with tax, then apply discount if "after_tax":
                    // after_tax: (amt + tax) - dis_flat - ((amt + tax) * dis_pct / 100)
                    // otherwise: amt + (amt * tax_rates / 100)
                    'formula' => 'IF({dis_on} == "after_tax", ({amt} + ({amt} * ({cgst_pct} + {sgst_pct} + {igst_pct} + {cess_pct}) / 100)) - {dis_flat} - (({amt} + ({amt} * ({cgst_pct} + {sgst_pct} + {igst_pct} + {cess_pct}) / 100)) * {dis_pct} / 100), {amt} + ({amt} * ({cgst_pct} + {sgst_pct} + {igst_pct} + {cess_pct}) / 100))',
                    'show_summary' => true,
                ],
            ],
        ]);

        // ═══════════════════════════════════════════════════════════════
        // TOTALS / SUMMARY (Formula fields)
        // ═══════════════════════════════════════════════════════════════

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Subtotal',
            'field_key' => 'subtotal',
            'field_type' => 'formula',
            'sort_order' => $sort++,
            'col_span' => 1,
            'label' => 'Total',
            'formula' => [
                'expression' => 'SUM({items.total_amt})',
                'format' => 'currency',
            ],
        ]);

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Additional Discount',
            'field_key' => 'additional_discount',
            'field_type' => 'decimal',
            'sort_order' => $sort++,
            'col_span' => 1,
            'label' => 'Additional Discount',
            'default_value' => '0',
        ]);

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Round Off',
            'field_key' => 'round_off',
            'field_type' => 'formula',
            'sort_order' => $sort++,
            'col_span' => 1,
            'label' => 'Round Off',
            'formula' => [
                'expression' => 'ROUND({subtotal} - {additional_discount}, 0) - ({subtotal} - {additional_discount})',
                'format' => 'currency',
            ],
        ]);

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Grand Total',
            'field_key' => 'grand_total',
            'field_type' => 'formula',
            'sort_order' => $sort++,
            'col_span' => 1,
            'label' => 'Grand Total',
            'formula' => [
                'expression' => 'ROUND({subtotal} - {additional_discount}, 0)',
                'format' => 'currency',
            ],
        ]);

        // ═══════════════════════════════════════════════════════════════
        // SUMMARY BLOCK (styled invoice totals)
        // ═══════════════════════════════════════════════════════════════

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Invoice Summary',
            'field_key' => 'invoice_summary',
            'field_type' => 'summary',
            'sort_order' => $sort++,
            'col_span' => 3,
            'label' => 'Invoice Summary',
            'summary_config' => [
                'lines' => [
                    ['label' => 'Subtotal', 'formula' => 'SUM({items.amt})', 'style' => 'normal'],
                    ['label' => 'CGST', 'formula' => 'SUM({items.amt}) * AVG({items.cgst_pct}) / 100', 'style' => 'normal'],
                    ['label' => 'SGST', 'formula' => 'SUM({items.amt}) * AVG({items.sgst_pct}) / 100', 'style' => 'normal'],
                    ['label' => 'IGST', 'formula' => 'SUM({items.amt}) * AVG({items.igst_pct}) / 100', 'style' => 'normal'],
                    ['label' => 'Cess', 'formula' => 'SUM({items.amt}) * AVG({items.cess_pct}) / 100', 'style' => 'normal'],
                    ['label' => 'Additional Discount', 'formula' => '{additional_discount}', 'style' => 'normal'],
                    ['label' => 'Round Off', 'formula' => '{round_off}', 'style' => 'small'],
                    ['label' => 'Grand Total', 'formula' => '{grand_total}', 'style' => 'bold'],
                ],
                'alignment' => 'right',
            ],
        ]);

        // ═══════════════════════════════════════════════════════════════
        // REMARKS & OPTIONS
        // ═══════════════════════════════════════════════════════════════

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Remark',
            'field_key' => 'remark',
            'field_type' => 'content',
            'sort_order' => $sort++,
            'col_span' => 3,
            'label' => 'Remark / Comment',
            'placeholder' => 'Enter remarks...',
        ]);

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Auto Approve',
            'field_key' => 'auto_approve',
            'field_type' => 'radio',
            'sort_order' => $sort++,
            'col_span' => 1,
            'label' => 'Auto Approve',
            'default_value' => 'no',
            'options' => [
                'static' => [
                    ['label' => 'Yes', 'value' => 'yes'],
                    ['label' => 'No', 'value' => 'no'],
                ],
                'dynamic' => ['enabled' => false],
            ],
        ]);

        $this->command->info("✓ Invoice page created with {$sort} fields (ID: {$page->id})");
        $this->command->info("  → Header: Invoice No, Date, PO No, PO Date, Buyer, Vendor, Addresses, Dispatch");
        $this->command->info("  → Line Items: Particular, HSN, Qty, Unit, MRP, Dis(₹), Dis(%), Dis On, Amt(formula), CGST%, SGST%, IGST%, Cess%, Total Amt(formula)");
        $this->command->info("  → Totals: Subtotal(formula), Additional Discount, Round Off(formula), Grand Total(formula)");
        $this->command->info("  → Summary Block: Tax breakup + Grand Total");
        $this->command->info("  → Footer: Remark, Auto Approve");
    }
}
