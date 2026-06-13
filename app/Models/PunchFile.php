<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PunchFile extends Model
{
    protected $table      = 'punchfile';
    protected $primaryKey = 'FileID';

    /**
     * The table has created_at but no updated_at.
     * Setting UPDATED_AT to null tells Eloquent to skip updating it.
     */
    public $timestamps = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'Scan_Id',
        'Group_Id',
        'DocType',
        'DocTypeId',
        'Company',
        'CompanyID',
        'From_ID',
        'To_ID',
        'FromName',
        'ToName',
        'File_Date',
        'File_Type',
        'File_No',
        'Related_Person',
        'Related_Address',
        'BillDate',
        'BillMonth',
        'BillYear',
        'ReferenceNo',
        'Loc_Name',
        'Loc_Add',
        'FromDateTime',
        'ToDateTime',
        'SubTotal',
        'Total_Amount',
        'Total_Discount',
        'Grand_Total',
        'NatureOfPayment',
        'DateOf_SanctApp',
        'GST_IGST_Amount',
        'SGST_Amount',
        'CGST_Amount',
        'Cess',
        'TCS',
        'Department',
        'DepartmentID',
        'Ledger',
        'Category',
        'FileName',
        'Section',
        'TravelMode',
        'TravelQuota',
        'TravelClass',
        'BookingDate',
        'PassengerDetail',
        'BookingStatus',
        'TravelInsurance',
        'TypeOfLoanDoc',
        'BankName',
        'BankIfscCode',
        'BankAccountNo',
        'BankAddress',
        'DueDate',
        'RenewalDate',
        'Period',
        'PaperSubmitted',
        'Vehicle_Type',
        'VehicleRs_PerKM',
        'TripStarted',
        'TripEnded',
        'VehicleRegNo',
        'OpeningKm',
        'ClosingKm',
        'TotalRunKM',
        'ChequeNo',
        'NoOfFarmers',
        'Dealers_TradePartners',
        'CropDetails',
        'VerietyDetails',
        'MealsAmount',
        'HallTent_Amount',
        'Gift_Amount',
        'AVTent_Amount',
        'HiredVehicle_Amount',
        'Snacks_Amount',
        'OthCharge_Amount',
        'MeterNumber',
        'PreviousReading',
        'CurrentReading',
        'UnitsConsumed',
        'LastDateOfPayment',
        'ServiceNo',
        'FDRNo',
        'Depositer',
        'DepositAccNo',
        'MaturityAmount',
        'MaturityDate',
        'RateOfInterest',
        'JointHolderName',
        'DepositedFrom',
        'ChallanPurpose',
        'BankBSRCode',
        'PaymentHead',
        'AgencyAddress',
        'VehicleClass',
        'RegNo',
        'BSRCode',
        'MobileNo',
        'BillingCycle',
        'DriverCharges',
        'TariffPlan',
        'PreviousBalance',
        'LastPayement',
        'NomineeDetails',
        'SumAssured',
        'PremiumDate',
        'Coverage',
        'AgentName',
        'PropertyArea',
        'OtherSpecif',
        'Financial_Year',
        'Remark',
        'CPIN',
        'CIN',
        'GSTIN',
        'Email',
        'EmployeeID',
        'EmployeeCode',
        'Employee_Name',
        'Cal_By',
        'Month',
        'MonthName',
        'Hotel',
        'Hotel_Name',
        'Hotel_Address',
        'Particular',
        'Airline',
        'Base_Fare',
        'Surcharge',
        'Cute_Charge',
        'Extra_Luggage',
        'CreditNo',
        'CreditDate',
        'Created_By',
        'Created_Date',
        'file_punch_date',
        'business_entity_id',
        'headquarter_id',
        'document_number',
        'narration',
        'tdsApplicable',
        'TDS_JV_no',
        'TDS_section',
        'TDS_percentage',
        'TDS_amount',
        'Payment_Amount',
        'account_group',
        'account',
        'favouring',
        'finance_punch_date',
        'finance_total_Amount',
        'Finance_Punch_By',
        'created_at',
    ];

    protected $casts = [
        // DateTime columns
        'FromDateTime'    => 'datetime',
        'ToDateTime'      => 'datetime',
        'Created_Date'    => 'datetime',
        'created_at'      => 'datetime',

        // Date columns
        'File_Date'           => 'date',
        'BillDate'            => 'date',
        'BookingDate'         => 'date',
        'DueDate'             => 'date',
        'RenewalDate'         => 'date',
        'LastDateOfPayment'   => 'date',
        'MaturityDate'        => 'date',
        'PremiumDate'         => 'date',
        'DateOf_SanctApp'     => 'date',
        'CreditDate'          => 'date',
        'file_punch_date'     => 'date',
        'finance_punch_date'  => 'date',

        // Decimal amounts
        'SubTotal'            => 'decimal:2',
        'Total_Amount'        => 'decimal:2',
        'Total_Discount'      => 'decimal:2',
        'Grand_Total'         => 'decimal:2',
        'GST_IGST_Amount'     => 'decimal:2',
        'SGST_Amount'         => 'decimal:2',
        'CGST_Amount'         => 'decimal:2',
        'Cess'                => 'decimal:2',
        'TCS'                 => 'decimal:2',
        'MealsAmount'         => 'decimal:2',
        'HallTent_Amount'     => 'decimal:2',
        'Gift_Amount'         => 'decimal:2',
        'AVTent_Amount'       => 'decimal:2',
        'HiredVehicle_Amount' => 'decimal:2',
        'Snacks_Amount'       => 'decimal:2',
        'OthCharge_Amount'    => 'decimal:2',
        'MaturityAmount'      => 'decimal:2',
        'RateOfInterest'      => 'decimal:2',
        'DriverCharges'       => 'decimal:2',
        'TariffPlan'          => 'decimal:2',
        'PreviousBalance'     => 'decimal:2',
        'LastPayement'        => 'decimal:2',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The scan_file record this punch entry belongs to.
     * FK: punchfile.Scan_Id → scan_file.Scan_Id
     */
    public function scanFile(): BelongsTo
    {
        return $this->belongsTo(ScanFile::class, 'Scan_Id', 'Scan_Id');
    }

    /**
     * Line items belonging to this punch entry.
     * FK: sub_punchfile.FileID → punchfile.FileID
     */
    public function subItems(): HasMany
    {
        return $this->hasMany(SubPunchFile::class, 'FileID', 'FileID');
    }
}
