<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\PageField;
use Illuminate\Database\Seeder;

class ConveyancePageSeeder extends Seeder
{
    /**
     * Conveyance / Travel Expense form with conditional formulas.
     * Demonstrates: mode-based calculation, per-km rate, date-based entries.
     */
    public function run(): void
    {
        $userId = \App\Models\User::first()?->id ?? 1;

        $page = Page::create([
            'user_id' => $userId,
            'page_name' => 'Conveyance',
            'is_generated' => false,
            'settings' => [
                'currency' => '₹',
                'locale' => 'en-IN',
                'decimal_precision' => 2,
                'round_off_rule' => 'round',
                'title' => 'Local Conveyance Claim',
                'description' => 'Travel expense claim with conditional calculation based on mode',
            ],
        ]);

        $sort = 0;

        // ── Header ──

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Voucher No',
            'field_key' => 'voucher_no',
            'field_type' => 'title',
            'sort_order' => $sort++,
            'col_span' => 1,
            'label' => 'Voucher No.',
            'is_required' => true,
        ]);

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Voucher Date',
            'field_key' => 'voucher_date',
            'field_type' => 'date',
            'sort_order' => $sort++,
            'col_span' => 1,
            'label' => 'Voucher Date',
            'is_required' => true,
            'options' => ['use_current_date' => true],
        ]);

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Employee Name',
            'field_key' => 'employee_name',
            'field_type' => 'title',
            'sort_order' => $sort++,
            'col_span' => 1,
            'label' => 'Employee Name',
            'is_required' => true,
        ]);

        // ── Mode & Rate (conditional) ──

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Mode',
            'field_key' => 'mode',
            'field_type' => 'select',
            'sort_order' => $sort++,
            'col_span' => 1,
            'label' => 'Mode',
            'is_required' => true,
            'options' => [
                'static' => [
                    ['label' => 'Own Vehicle (Bike)', 'value' => 'bike'],
                    ['label' => 'Own Vehicle (Car)', 'value' => 'car'],
                    ['label' => 'Auto/Taxi', 'value' => 'auto'],
                    ['label' => 'Bus', 'value' => 'bus'],
                    ['label' => 'Train', 'value' => 'train'],
                    ['label' => 'Flight', 'value' => 'flight'],
                ],
                'dynamic' => ['enabled' => false],
            ],
        ]);

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Calculation Base',
            'field_key' => 'calc_base',
            'field_type' => 'select',
            'sort_order' => $sort++,
            'col_span' => 1,
            'label' => 'Calculation Base',
            'is_required' => true,
            'options' => [
                'static' => [
                    ['label' => 'K.M. Base (Per KM Rate)', 'value' => 'km_base'],
                    ['label' => 'Fixed Amount', 'value' => 'fixed'],
                    ['label' => 'Actual Bill', 'value' => 'actual'],
                ],
                'dynamic' => ['enabled' => false],
            ],
        ]);

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Per KM Rate',
            'field_key' => 'per_km_rate',
            'field_type' => 'decimal',
            'sort_order' => $sort++,
            'col_span' => 1,
            'label' => 'Per KM Rate',
            'is_required' => true,
            'default_value' => '5',
            // Visibility: only show when calc_base == 'km_base'
            'visibility_rules' => [
                'logic' => 'AND',
                'rules' => [
                    ['field' => 'calc_base', 'operator' => '==', 'value' => 'km_base'],
                ],
            ],
        ]);

        // ── Travel Entries Table ──

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Travel Entries',
            'field_key' => 'entries',
            'field_type' => 'repeater',
            'sort_order' => $sort++,
            'col_span' => 3,
            'label' => 'Travel Entries',
            'repeater_columns' => [
                [
                    'key' => 'date',
                    'label' => 'Date',
                    'type' => 'date',
                    'required' => true,
                    'default' => '',
                ],
                [
                    'key' => 'from_place',
                    'label' => 'From',
                    'type' => 'text',
                    'required' => true,
                    'default' => '',
                ],
                [
                    'key' => 'to_place',
                    'label' => 'To',
                    'type' => 'text',
                    'required' => true,
                    'default' => '',
                ],
                [
                    'key' => 'opening_km',
                    'label' => 'Opening KM',
                    'type' => 'decimal',
                    'required' => false,
                    'default' => '0',
                ],
                [
                    'key' => 'closing_km',
                    'label' => 'Closing KM',
                    'type' => 'decimal',
                    'required' => false,
                    'default' => '0',
                ],
                [
                    'key' => 'total_km',
                    'label' => 'Total KM',
                    'type' => 'formula',
                    'required' => false,
                    'default' => '',
                    'formula' => '{closing_km} - {opening_km}',
                    'show_summary' => true,
                ],
                [
                    'key' => 'amount',
                    'label' => 'Amount',
                    'type' => 'formula',
                    'required' => false,
                    'default' => '',
                    // Conditional: if calc_base is km_base, use total_km * per_km_rate
                    // Otherwise use the manual amount (which would be 0 for formula)
                    'formula' => '{total_km} * {per_km_rate}',
                    'show_summary' => true,
                ],
            ],
        ]);

        // ── Totals ──

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Total KM',
            'field_key' => 'total_km_all',
            'field_type' => 'formula',
            'sort_order' => $sort++,
            'col_span' => 1,
            'label' => 'Total KM',
            'formula' => [
                'expression' => 'SUM({entries.total_km})',
                'format' => 'number',
            ],
        ]);

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Total Amount',
            'field_key' => 'total_amount',
            'field_type' => 'formula',
            'sort_order' => $sort++,
            'col_span' => 1,
            'label' => 'Total Amount',
            'formula' => [
                'expression' => 'SUM({entries.amount})',
                'format' => 'currency',
            ],
        ]);

        // ── Remark ──

        PageField::create([
            'page_id' => $page->id,
            'field_name' => 'Remark',
            'field_key' => 'remark',
            'field_type' => 'content',
            'sort_order' => $sort++,
            'col_span' => 3,
            'label' => 'Remark / Comment',
            'placeholder' => 'Being conveyance expenses for travel...',
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

        $this->command->info("✓ Conveyance page created (ID: {$page->id})");
        $this->command->info("  Demonstrates: Conditional formula (KM-based vs Fixed), visibility rules, table with formula columns");
    }
}
