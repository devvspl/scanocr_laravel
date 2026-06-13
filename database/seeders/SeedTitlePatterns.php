<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeedTitlePatterns extends Seeder
{
    public function run(): void
    {
        $patterns = [
            'debit_note'     => 'debit note,debit memo,DN No',
            'credit_note'    => 'credit note,credit memo,CN No',
            'purchase_order' => 'purchase order,po number,po no',
            'delivery_note'  => 'delivery note,delivery challan,dispatch challan,dc no',
            'receipt'        => 'payment receipt,money receipt,receipt no',
            'proforma'       => 'proforma invoice,pro-forma,proforma no',
            'grn'            => 'goods receipt note,grn no,goods received',
            'journal'        => 'journal voucher,journal entry,jv no',
            'contra'         => 'contra voucher,contra entry',
            'payment'        => 'payment voucher,payment advice,vendor payment',
            'bill'           => 'purchase bill,vendor invoice,supplier invoice',
            'invoice'        => 'tax invoice,sales invoice',
        ];

        foreach ($patterns as $key => $tp) {
            $type = DB::table('document_types')->where('key', $key)->first();
            if ($type) {
                DB::table('document_training_data')
                    ->where('document_type_id', $type->id)
                    ->whereNull('title_patterns')
                    ->update(['title_patterns' => $tp]);
            }
        }

        $this->command->info('Title patterns seeded for all document types.');
    }
}
