<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ScanFile extends Model
{
    protected $table      = 'scan_file';
    protected $primaryKey = 'Scan_Id';

    /**
     * Legacy table has no created_at / updated_at columns.
     */
    public $timestamps = false;

    protected $fillable = [
        'Group_Id',
        'Location',
        'Doc_Type',
        'DocType_Id',
        'Temp_Scan',
        'Temp_Scan_Date',
        'Temp_Scan_By',
        'Scan_Complete',
        'Scan_By',
        'document_verified',
        'document_verified_by',
        'document_verified_date',
        'document_received_date',
        'temp_scan_reject',
        'temp_scan_reject_remark',
        'temp_scan_reject_by',
        'temp_scan_reject_date',
        'Document_Name',
        'File',
        'File_Ext',
        'File_Location',
        'File_Location1',
        'Main_File',
        'Scan_Date',
        'Year',
        'year_id',
        'Final_Submit',
        'File_Punched',
        'Is_Deleted',
        'Delete_Date',
        'Deleted_By',
        'Punch_By',
        'Punch_Date',
        'File_Approved',
        'Approve_By',
        'Approve_Date',
        'Admin_Approve',
        'Scan_Resend',
        'Scan_Resend_Remark',
        'Scan_Resend_By',
        'Scan_Resend_Date',
        'Is_Rejected',
        'Reject_Remark',
        'Reject_Date',
        'Edit_Permission',
        'Index_Name1',
        'Index_Name2',
        'Index_Name3',
        'Entry_Confirm',
        'Confirm_Date',
        'Bill_Approved',
        'Bill_Approver',
        'Bill_Approver_Date',
        'Bill_Approver_Remark',
        'scan_doctype_id',
        'department_id',
        'firm_id',
        'bill_voucher_date',
        'bill_no_voucher_no',
        'at_finance',
        'Finance_Punch_By',
        'finance_punch_date',
        'Finance_Resend_Remark',
        'Finance_Resend_By',
        'Finance_Resend_Date',
        'Is_Finance_Rejected',
        'is_extract',
        'classified_by',
        'classified_date',
        'extract_status',
    ];

    protected $casts = [
        // DateTime columns
        'Temp_Scan_Date'          => 'datetime',
        'Scan_Date'               => 'datetime',
        'Delete_Date'             => 'datetime',
        'Punch_Date'              => 'datetime',
        'Approve_Date'            => 'datetime',
        'Confirm_Date'            => 'datetime',
        'classified_date'         => 'datetime',

        // Date columns
        'document_verified_date'  => 'date',
        'document_received_date'  => 'date',
        'temp_scan_reject_date'   => 'date',
        'Scan_Resend_Date'        => 'date',
        'Reject_Date'             => 'date',
        'Bill_Approver_Date'      => 'date',
        'bill_voucher_date'       => 'date',
        'finance_punch_date'      => 'date',
        'Finance_Resend_Date'     => 'date',

        // Y/N char(1) fields — kept as string (not cast to bool) to preserve Y/N values
        // Use explicit comparisons: $record->Is_Deleted === 'Y'
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The financial year this scan record belongs to.
     * FK: year_id → financial_years.id
     */
    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class, 'year_id');
    }

    /**
     * The punch-file entry linked to this scan.
     * FK: punchfile.Scan_Id → scan_file.Scan_Id
     */
    public function punchFile(): HasOne
    {
        return $this->hasOne(PunchFile::class, 'Scan_Id', 'Scan_Id');
    }

    /**
     * The secondary punch-file entry linked to this scan.
     * FK: punchfile2.Scan_Id → scan_file.Scan_Id
     */
    public function punchFile2(): HasOne
    {
        return $this->hasOne(PunchFile2::class, 'Scan_Id', 'Scan_Id');
    }

    /**
     * The document type for this scan.
     *
     * NOTE: DocType_Id is a legacy integer from the old master system.
     * It does NOT directly map to document_types.id in this application;
     * use this relationship only after confirming the IDs have been reconciled
     * via the import migration (2026_06_13_000005_import_master_doctype_to_document_types).
     *
     * FK: DocType_Id → document_types.id
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'DocType_Id');
    }
}
