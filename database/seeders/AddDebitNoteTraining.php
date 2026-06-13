<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\DocumentType;

class AddDebitNoteTraining extends Seeder
{
    public function run(): void
    {
        $typeId = DocumentType::where('key', 'debit_note')->first()->id;

        $samples = [
            [
                'sample_text' => 'Debit Note No DRI25 Dated 30-Sep-25 Original Invoice No Against Bill Loading And Unloading Charges Cold Storage Raipur Consignee VNR Seeds Pvt Limited Corporate Centre Canal Road Crossing Raipur Chhattisgarh State Code 22 Buyer Bill to Amount Chargeable Bank Details Federal Bank Computer Generated Document',
                'keywords'    => 'debit note,debit note no,against bill,loading unloading,cold storage,consignee,amount chargeable,computer generated',
            ],
            [
                'sample_text' => 'DEBIT NOTE Debit Note Number DN-2025 Date Ref Invoice Against Original Invoice Reason for Debit Shortage Damage Rate Difference Overcharge Penalty Deduction from Payment Net Debit Amount Vendor Supplier Party Name Address GSTIN State Code',
                'keywords'    => 'debit note,debit note number,dn,reason for debit,shortage,damage,rate difference,overcharge,penalty,deduction from payment,net debit',
            ],
            [
                'sample_text' => 'Debit Note Cold Storage Charges Loading Unloading Labour Charges Warehousing Rent Godown Charges Against Bill Invoice Reference Consignee Ship To Bill To State Name Code Amount Chargeable Total Debit',
                'keywords'    => 'debit note,cold storage,loading,unloading,labour charges,warehousing,godown,against bill,total debit',
            ],
            [
                'sample_text' => 'DEBIT NOTE Jai Jawan Cold Storage Raipur Debit Note No Dated Khasra No Semaria Village Aiwar Taluk Durgh District CG State Name Chhattisgarh Code 22 Consignee VNR Seeds Buyer Bill to Description of Goods Loading And Unloading Charges Amount Total Amount Chargeable Bank Details Account Holder Name Bank Name Branch IFSC Code Computer Generated Document',
                'keywords'    => 'debit note,cold storage,khasra,semaria,village,taluk,district,loading and unloading,amount chargeable,computer generated document',
            ],
        ];

        foreach ($samples as $sample) {
            DB::table('document_training_data')->insert([
                'document_type_id' => $typeId,
                'sample_text'      => $sample['sample_text'],
                'keywords'         => $sample['keywords'],
                'status'           => 'active',
                'created_by'       => 1,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }

        $this->command->info('Added 4 additional debit note training samples.');
    }
}
