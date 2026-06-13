<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountGroup;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $g = fn(string $name) => AccountGroup::where('name', $name)->value('id');

        $accounts = [
            // Cash & Bank
            ['code' => '1101', 'name' => 'Cash on Hand',          'group' => 'Cash & Bank',             'balance_type' => 'debit'],
            ['code' => '1102', 'name' => 'Main Bank Account',      'group' => 'Cash & Bank',             'balance_type' => 'debit'],
            ['code' => '1103', 'name' => 'Petty Cash',             'group' => 'Cash & Bank',             'balance_type' => 'debit'],

            // Accounts Receivable
            ['code' => '1201', 'name' => 'Trade Receivables',      'group' => 'Accounts Receivable',     'balance_type' => 'debit'],
            ['code' => '1202', 'name' => 'Other Receivables',      'group' => 'Accounts Receivable',     'balance_type' => 'debit'],

            // Inventory
            ['code' => '1301', 'name' => 'Raw Materials',          'group' => 'Inventory',               'balance_type' => 'debit'],
            ['code' => '1302', 'name' => 'Finished Goods',         'group' => 'Inventory',               'balance_type' => 'debit'],

            // Fixed Assets
            ['code' => '1501', 'name' => 'Land & Building',        'group' => 'Fixed Assets',            'balance_type' => 'debit'],
            ['code' => '1502', 'name' => 'Machinery & Equipment',  'group' => 'Fixed Assets',            'balance_type' => 'debit'],
            ['code' => '1503', 'name' => 'Vehicles',               'group' => 'Fixed Assets',            'balance_type' => 'debit'],

            // Accounts Payable
            ['code' => '2101', 'name' => 'Trade Payables',         'group' => 'Accounts Payable',        'balance_type' => 'credit'],
            ['code' => '2102', 'name' => 'Accrued Expenses',       'group' => 'Accounts Payable',        'balance_type' => 'credit'],

            // Tax Payable
            ['code' => '2201', 'name' => 'Sales Tax Payable',      'group' => 'Tax Payable',             'balance_type' => 'credit'],
            ['code' => '2202', 'name' => 'Income Tax Payable',     'group' => 'Tax Payable',             'balance_type' => 'credit'],

            // Long-term Liabilities
            ['code' => '2501', 'name' => 'Bank Loan',              'group' => 'Long-term Liabilities',   'balance_type' => 'credit'],

            // Sales Revenue
            ['code' => '4001', 'name' => 'Product Sales',          'group' => 'Sales Revenue',           'balance_type' => 'credit'],
            ['code' => '4002', 'name' => 'Service Revenue',        'group' => 'Sales Revenue',           'balance_type' => 'credit'],

            // Other Income
            ['code' => '4101', 'name' => 'Interest Income',        'group' => 'Other Income',            'balance_type' => 'credit'],
            ['code' => '4102', 'name' => 'Miscellaneous Income',   'group' => 'Other Income',            'balance_type' => 'credit'],

            // Cost of Goods Sold
            ['code' => '5001', 'name' => 'Cost of Goods Sold',     'group' => 'Cost of Goods Sold',      'balance_type' => 'debit'],

            // Operating Expenses
            ['code' => '5101', 'name' => 'Salaries & Wages',       'group' => 'Operating Expenses',      'balance_type' => 'debit'],
            ['code' => '5102', 'name' => 'Rent Expense',           'group' => 'Operating Expenses',      'balance_type' => 'debit'],
            ['code' => '5103', 'name' => 'Utilities Expense',      'group' => 'Operating Expenses',      'balance_type' => 'debit'],

            // Administrative Expenses
            ['code' => '5201', 'name' => 'Office Supplies',        'group' => 'Administrative Expenses', 'balance_type' => 'debit'],
            ['code' => '5202', 'name' => 'Depreciation Expense',   'group' => 'Administrative Expenses', 'balance_type' => 'debit'],
        ];

        foreach ($accounts as $data) {
            Account::create([
                'code'             => $data['code'],
                'name'             => $data['name'],
                'account_group_id' => $g($data['group']),
                'opening_balance'  => 0,
                'balance_type'     => $data['balance_type'],
                'is_active'        => true,
                'created_by'       => 1,
            ]);
        }
    }
}
