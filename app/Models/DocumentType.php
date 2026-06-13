<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $fillable = [
        'key', 'label', 'default_prefix', 'icon_path',
        'module', 'sort_order', 'is_active', 'digital_approval', 'is_system', 'created_by',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'is_system'        => 'boolean',
        'digital_approval' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function numberingSettings()
    {
        return $this->hasMany(NumberingSetting::class, 'document_type', 'key');
    }

    public function trainingData()
    {
        return $this->hasMany(DocumentTrainingData::class, 'document_type_id');
    }

    public function activeTrainingData()
    {
        return $this->hasMany(DocumentTrainingData::class, 'document_type_id')
                    ->where('status', 'active');
    }

    public static function systemTypes(): array
    {
        return [
            ['key' => 'invoice',       'label' => 'Sales Invoice',    'default_prefix' => 'INV',  'module' => 'sales',    'sort_order' => 1,  'icon_path' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
            ['key' => 'proforma',      'label' => 'Proforma Invoice',  'default_prefix' => 'PRO',  'module' => 'sales',    'sort_order' => 2,  'icon_path' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
            ['key' => 'credit_note',   'label' => 'Credit Note',       'default_prefix' => 'CN',   'module' => 'sales',    'sort_order' => 3,  'icon_path' => 'M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['key' => 'delivery_note', 'label' => 'Delivery Note',     'default_prefix' => 'DN',   'module' => 'sales',    'sort_order' => 4,  'icon_path' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4'],
            ['key' => 'receipt',       'label' => 'Receipt',           'default_prefix' => 'RCP',  'module' => 'sales',    'sort_order' => 5,  'icon_path' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
            ['key' => 'purchase_order','label' => 'Purchase Order',    'default_prefix' => 'PO',   'module' => 'purchase', 'sort_order' => 6,  'icon_path' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z'],
            ['key' => 'bill',          'label' => 'Purchase Bill',     'default_prefix' => 'BILL', 'module' => 'purchase', 'sort_order' => 7,  'icon_path' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
            ['key' => 'debit_note',    'label' => 'Debit Note',        'default_prefix' => 'DBN',  'module' => 'purchase', 'sort_order' => 8,  'icon_path' => 'M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['key' => 'payment',       'label' => 'Payment',           'default_prefix' => 'PAY',  'module' => 'purchase', 'sort_order' => 9,  'icon_path' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
            ['key' => 'journal',       'label' => 'Journal Voucher',   'default_prefix' => 'JV',   'module' => 'journal',  'sort_order' => 10, 'icon_path' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
            ['key' => 'contra',        'label' => 'Contra Voucher',    'default_prefix' => 'CV',   'module' => 'journal',  'sort_order' => 11, 'icon_path' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'],
            ['key' => 'grn',           'label' => 'GRN',               'default_prefix' => 'GRN',  'module' => 'purchase', 'sort_order' => 12, 'icon_path' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
        ];
    }
}
