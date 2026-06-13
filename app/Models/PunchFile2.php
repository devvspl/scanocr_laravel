<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PunchFile2 extends Model
{
    protected $table      = 'punchfile2';
    protected $primaryKey = 'FileID';

    /**
     * The table has created_at but no updated_at.
     */
    public $timestamps = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'Group_Id',
        'Scan_Id',
        'DocType',
        'DocTypeId',
        'FileLoc_Temp',
        'FileLoc',
        'FromName',
        'ToName',
        'FromDateTime',
        'ToDateTime',
        'From_Location',
        'To_Location',
        'File_Date',
        'File_Type',
        'File_No',
        'Related_Person',
        'Related_Address',
        'TotalAmount',
        'DocMonth',
        'DocYear',
        'VehicleNo',
        'VehicleType',
        'VehicleCompany',
        'RegPurDate',
        'Registered',
        'ClearanceDate',
        'CustomerName',
        'Hypothecation',
        'BankName',
        'BankIfscCode',
        'BankAccountNo',
        'BankAddress',
        'PeriodDuration',
        'Company',
        'CompanyID',
        'Vendor',
        'VendorID',
        'Location',
        'Department',
        'DepartmentID',
        'FinYear',
        'CertiType',
        'ComRecType',
        'AuditorName',
        'DateofSign',
        'FinancialYear',
        'CertiNo',
        'ValidFrom',
        'Validto',
        'CompanyType',
        'PackingList',
        'LcAdvance',
        'PartyName',
        'DateOfConfirm',
        'CropDetails',
        'VerietyDetails',
        'ProblemIssue',
        'Remedy',
        'Doctor',
        'Hospital',
        'Medicine',
        'TreatmentTaken',
        'PaymentHead',
        'TRRN',
        'CRN',
        'KhasraNo',
        'TotalArea',
        'Unit',
        'KHNo',
        'PHNo',
        'RNM_Ward',
        'MarketValue',
        'RinPushtikaNo',
        'ExtraCharge',
        'Stamp_Duty',
        'Diversion_Paper',
        'Map_Approval',
        'Additional_Exposure',
        'DueDate',
        'Rating',
        'Remark',
        'Created_By',
        'Created_Date',
        'created_at',
    ];

    protected $casts = [
        // DateTime columns
        'FromDateTime' => 'datetime',
        'ToDateTime'   => 'datetime',
        'Created_Date' => 'datetime',
        'created_at'   => 'datetime',

        // Date columns
        'File_Date'     => 'date',
        'RegPurDate'    => 'date',
        'ClearanceDate' => 'date',
        'ValidFrom'     => 'date',
        'Validto'       => 'date',
        'DueDate'       => 'date',

        // Decimal
        'TotalAmount'  => 'decimal:2',
        'MarketValue'  => 'decimal:2',
        'ExtraCharge'  => 'decimal:2',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The scan_file record this secondary punch entry belongs to.
     * FK: punchfile2.Scan_Id → scan_file.Scan_Id
     */
    public function scanFile(): BelongsTo
    {
        return $this->belongsTo(ScanFile::class, 'Scan_Id', 'Scan_Id');
    }
}
