<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\FinancialYear;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['code' => 'WBDEMO'],
            [
            // ── Identity ──────────────────────────────────────────────────────
            'name'                  => 'ScanOCR Demo Pvt. Ltd.',
            'legal_name'            => 'ScanOCR Demo Private Limited',
            'display_name'          => 'ScanOCR Demo',
            'code'                  => 'WBDEMO',
            'type'                  => 'private_limited',
            'industry'              => 'Technology',
            'website'               => 'https://ScanOCR.in',
            'email'                 => 'accounts@ScanOCR.in',
            'phone'                 => '044-12345678',
            'mobile'                => '9876543210',

            // ── Address ───────────────────────────────────────────────────────
            'address_line1'         => '42, Anna Salai',
            'address_line2'         => 'Nandanam',
            'city'                  => 'Chennai',
            'state'                 => 'Tamil Nadu',
            'country'               => 'India',
            'pincode'               => '600035',

            // ── Tax & Compliance ──────────────────────────────────────────────
            'gstin'                 => '33AABCW1234A1Z5',
            'pan'                   => 'AABCW1234A',
            'tan'                   => 'CHEW12345A',
            'gst_registration_type' => 'regular',
            'gst_registration_date' => '2017-07-01',

            // ── Bank Details ──────────────────────────────────────────────────
            'bank_name'             => 'HDFC Bank',
            'bank_branch'           => 'Anna Salai Branch',
            'bank_account_number'   => '50100123456789',
            'bank_ifsc'             => 'HDFC0001234',
            'bank_account_type'     => 'Current',

            // ── Locale / Financial ────────────────────────────────────────────
            'fy_start_month'        => '04',
            'currency_code'         => 'INR',
            'currency_symbol'       => '₹',
            'date_format'           => 'DD/MM/YYYY',
            'timezone'              => 'Asia/Kolkata',

            // ── Status ────────────────────────────────────────────────────────
            'is_active'             => true,
            'is_default'            => true,
        ]);

        // ── Financial Year: FY 2025-26 ────────────────────────────────────────
        FinancialYear::firstOrCreate(
            ['company_id' => $company->id, 'label' => 'FY 2025-26'],
            [
                'start_date' => '2025-04-01',
                'end_date'   => '2026-03-31',
                'is_current' => true,
                'is_locked'  => false,
                'notes'      => 'Current financial year',
            ]
        );
    }
}
