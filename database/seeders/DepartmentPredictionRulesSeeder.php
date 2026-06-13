<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentPredictionRulesSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('department_prediction_rules')->count() > 0) {
            return;
        }

        $rules = [
            // Document Type → Department (weight: 80 = strongest signal)
            ['code' => 'FIN', 'type' => 'doc_type', 'patterns' => ['invoice', 'credit note', 'debit note', 'receipt', 'payment', 'bank statement', 'journal', 'contra'], 'weight' => 80],
            ['code' => 'ILP', 'type' => 'doc_type', 'patterns' => ['purchase order', 'purchase bill', 'grn', 'goods receipt'], 'weight' => 80],
            ['code' => 'SLS', 'type' => 'doc_type', 'patterns' => ['delivery note', 'delivery challan', 'proforma', 'sales invoice'], 'weight' => 80],

            // Vendor/Party Keywords → Department (weight: 60)
            ['code' => 'ADM', 'type' => 'vendor_keyword', 'patterns' => ['tour', 'travel', 'hotel', 'cab', 'taxi', 'courier', 'stationery', 'furniture', 'housekeeping', 'security', 'cleaning', 'pantry', 'canteen', 'rent', 'electricity'], 'weight' => 60],
            ['code' => 'IT',  'type' => 'vendor_keyword', 'patterns' => ['software', 'computer', 'tech', 'digital', 'cloud', 'hosting', 'server', 'network', 'infosys', 'wipro', 'tcs', 'microsoft', 'google', 'amazon web'], 'weight' => 60],
            ['code' => 'HR',  'type' => 'vendor_keyword', 'patterns' => ['manpower', 'staffing', 'recruitment', 'training institute', 'consultancy'], 'weight' => 60],
            ['code' => 'PDN', 'type' => 'vendor_keyword', 'patterns' => ['seed', 'seeds', 'agri', 'farm', 'crop', 'fertilizer', 'pesticide', 'plantation', 'nursery'], 'weight' => 60],
            ['code' => 'PRS', 'type' => 'vendor_keyword', 'patterns' => ['processing', 'grading', 'packaging', 'cold storage', 'warehouse', 'godown'], 'weight' => 60],
            ['code' => 'MKT', 'type' => 'vendor_keyword', 'patterns' => ['advertising', 'media', 'print', 'hoarding', 'banner', 'branding', 'event management'], 'weight' => 60],
            ['code' => 'LGL', 'type' => 'vendor_keyword', 'patterns' => ['advocate', 'lawyer', 'legal', 'court', 'notary', 'law firm'], 'weight' => 60],
            ['code' => 'QA',  'type' => 'vendor_keyword', 'patterns' => ['laboratory', 'lab', 'testing', 'calibration', 'inspection agency'], 'weight' => 60],
            ['code' => 'SLS', 'type' => 'vendor_keyword', 'patterns' => ['dealer', 'distributor', 'retailer', 'wholesale'], 'weight' => 60],

            // Content Keywords → Department (weight: 40 = weakest signal)
            ['code' => 'FIN', 'type' => 'content_keyword', 'patterns' => ['amount due', 'total amount', 'bank details', 'ifsc', 'account no', 'gst', 'igst', 'cgst', 'sgst', 'tds', 'ledger', 'neft', 'rtgs', 'cheque'], 'weight' => 40],
            ['code' => 'ILP', 'type' => 'content_keyword', 'patterns' => ['purchase order', 'po number', 'vendor code', 'supplier', 'delivery date', 'procurement'], 'weight' => 40],
            ['code' => 'SLS', 'type' => 'content_keyword', 'patterns' => ['customer', 'bill to', 'ship to', 'consignee', 'dispatch', 'sales order'], 'weight' => 40],
            ['code' => 'ADM', 'type' => 'content_keyword', 'patterns' => ['office expense', 'maintenance', 'repair', 'annual maintenance', 'amc'], 'weight' => 40],
            ['code' => 'PDN', 'type' => 'content_keyword', 'patterns' => ['production', 'batch no', 'lot no', 'yield', 'season', 'kharif', 'rabi', 'sowing'], 'weight' => 40],
            ['code' => 'HR',  'type' => 'content_keyword', 'patterns' => ['employee', 'salary', 'payroll', 'epf', 'esi', 'gratuity', 'attendance'], 'weight' => 40],
            ['code' => 'IT',  'type' => 'content_keyword', 'patterns' => ['license', 'subscription', 'domain', 'ssl', 'bandwidth', 'api', 'saas'], 'weight' => 40],
        ];

        foreach ($rules as $ruleGroup) {
            $dept = DB::table('departments')->where('department_code', $ruleGroup['code'])->first();
            if (!$dept) continue;

            foreach ($ruleGroup['patterns'] as $pattern) {
                DB::table('department_prediction_rules')->insert([
                    'department_id' => $dept->id,
                    'rule_type'     => $ruleGroup['type'],
                    'pattern'       => strtolower($pattern),
                    'weight'        => $ruleGroup['weight'],
                    'is_active'     => true,
                    'created_by'    => 1,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }

        $this->command->info('Seeded department prediction rules.');
    }
}
