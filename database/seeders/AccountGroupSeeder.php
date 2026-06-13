<?php

namespace Database\Seeders;

use App\Models\AccountGroup;
use Illuminate\Database\Seeder;

class AccountGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            // Assets
            ['name' => 'Assets',              'nature' => 'assets',      'parent_id' => null],
            ['name' => 'Current Assets',      'nature' => 'assets',      'parent_id' => 'Assets'],
            ['name' => 'Fixed Assets',        'nature' => 'assets',      'parent_id' => 'Assets'],
            ['name' => 'Cash & Bank',         'nature' => 'assets',      'parent_id' => 'Current Assets'],
            ['name' => 'Accounts Receivable', 'nature' => 'assets',      'parent_id' => 'Current Assets'],
            ['name' => 'Inventory',           'nature' => 'assets',      'parent_id' => 'Current Assets'],

            // Liabilities
            ['name' => 'Liabilities',         'nature' => 'liabilities', 'parent_id' => null],
            ['name' => 'Current Liabilities', 'nature' => 'liabilities', 'parent_id' => 'Liabilities'],
            ['name' => 'Long-term Liabilities','nature' => 'liabilities', 'parent_id' => 'Liabilities'],
            ['name' => 'Accounts Payable',    'nature' => 'liabilities', 'parent_id' => 'Current Liabilities'],
            ['name' => 'Tax Payable',         'nature' => 'liabilities', 'parent_id' => 'Current Liabilities'],

            // Income
            ['name' => 'Income',              'nature' => 'income',      'parent_id' => null],
            ['name' => 'Sales Revenue',       'nature' => 'income',      'parent_id' => 'Income'],
            ['name' => 'Other Income',        'nature' => 'income',      'parent_id' => 'Income'],

            // Expense
            ['name' => 'Expenses',            'nature' => 'expense',     'parent_id' => null],
            ['name' => 'Operating Expenses',  'nature' => 'expense',     'parent_id' => 'Expenses'],
            ['name' => 'Administrative Expenses', 'nature' => 'expense', 'parent_id' => 'Expenses'],
            ['name' => 'Cost of Goods Sold',  'nature' => 'expense',     'parent_id' => 'Expenses'],
        ];

        // First pass: create root groups
        $created = [];
        foreach ($groups as $data) {
            if ($data['parent_id'] === null) {
                $group = AccountGroup::create([
                    'name'      => $data['name'],
                    'nature'    => $data['nature'],
                    'parent_id' => null,
                    'is_active' => true,
                    'created_by' => 1,
                ]);
                $created[$data['name']] = $group->id;
            }
        }

        // Second pass: create children (up to 2 levels deep)
        foreach ([1, 2] as $_) {
            foreach ($groups as $data) {
                if ($data['parent_id'] !== null && !isset($created[$data['name']]) && isset($created[$data['parent_id']])) {
                    $group = AccountGroup::create([
                        'name'      => $data['name'],
                        'nature'    => $data['nature'],
                        'parent_id' => $created[$data['parent_id']],
                        'is_active' => true,
                        'created_by' => 1,
                    ]);
                    $created[$data['name']] = $group->id;
                }
            }
        }
    }
}
