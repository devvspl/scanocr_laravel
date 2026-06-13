<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Disable FK checks so we can truncate referenced tables cleanly
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Clear AI training data — tied to old document_types IDs (new system), now obsolete
        DB::table('document_training_data')->truncate();

        // Clear document_predictions (0 rows, but clean FK references)
        DB::table('document_predictions')->truncate();

        // Clear numbering_settings — keys are new-system keys; controller will auto-recreate on first visit
        DB::table('numbering_settings')->truncate();

        // Wipe existing document_types rows
        DB::table('document_types')->truncate();

        // Re-enable FK checks before inserting
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Map old master_doctype → document_types
        // file_type → label, alias → key, type_id → id, status A→active, D→inactive
        // Generate a short default_prefix from the alias (uppercase, max 6 chars)
        $oldRows = DB::table('master_doctype')->orderBy('type_id')->get();

        $now = now();

        foreach ($oldRows as $row) {
            // Build a prefix: uppercase alias, remove underscores, max 6 chars
            $prefix = strtoupper(str_replace('_', '', $row->alias));
            $prefix = substr($prefix, 0, 6);

            DB::table('document_types')->insert([
                'id'             => $row->type_id,
                'key'            => $row->alias,
                'label'          => $row->file_type,
                'default_prefix' => $prefix,
                'icon_path'      => null,
                'module'         => null,
                'sort_order'     => $row->type_id,
                'is_active'      => $row->status === 'A' ? 1 : 0,
                'is_system'      => 0,
                'created_by'     => null,
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }

        // Reset AUTO_INCREMENT to one above the max id inserted (58 → 59)
        DB::statement('ALTER TABLE document_types AUTO_INCREMENT = 59');
    }

    public function down(): void
    {
        // Restore original 12 document_types (new system)
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('document_types')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $now = now();
        $originals = [
            [1,  'invoice',        'Sales Invoice',    'INV',  1],
            [2,  'proforma',       'Proforma Invoice', 'PRO',  2],
            [3,  'credit_note',    'Credit Note',      'CN',   3],
            [4,  'delivery_note',  'Delivery Note',    'DN',   4],
            [5,  'receipt',        'Receipt',          'RCP',  5],
            [6,  'purchase_order', 'Purchase Order',   'PO',   6],
            [7,  'bill',           'Purchase Bill',    'BILL', 7],
            [8,  'debit_note',     'Debit Note',       'DBN',  8],
            [9,  'payment',        'Payment',          'PAY',  9],
            [10, 'journal',        'Journal Voucher',  'JV',   10],
            [11, 'contra',         'Contra Voucher',   'CV',   11],
            [12, 'grn',            'GRN',              'GRN',  12],
        ];

        foreach ($originals as [$id, $key, $label, $prefix, $sort]) {
            DB::table('document_types')->insert([
                'id'             => $id,
                'key'            => $key,
                'label'          => $label,
                'default_prefix' => $prefix,
                'icon_path'      => null,
                'module'         => null,
                'sort_order'     => $sort,
                'is_active'      => 1,
                'is_system'      => 1,
                'created_by'     => null,
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }

        DB::statement('ALTER TABLE document_types AUTO_INCREMENT = 13');
    }
};
