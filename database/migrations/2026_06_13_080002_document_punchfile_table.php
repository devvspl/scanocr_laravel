<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation migration for the legacy `punchfile` table.
 * Only runs Schema::create when the table does not already exist.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('punchfile')) {
            Schema::create('punchfile', function (Blueprint $table) {
                $table->bigIncrements('FileID');

                // Link to scan_file
                $table->unsignedInteger('Scan_Id')->nullable();
                $table->unsignedInteger('Group_Id');

                // Document type
                $table->string('DocType', 50)->nullable()->default('');
                $table->unsignedSmallInteger('DocTypeId')->nullable()->default(0);

                // Party / company
                $table->string('Company', 50)->nullable();
                $table->unsignedInteger('CompanyID')->nullable();

                // From / To parties
                $table->unsignedInteger('From_ID')->nullable();
                $table->unsignedInteger('To_ID')->nullable();
                $table->string('FromName', 50)->nullable();
                $table->string('ToName', 50)->nullable();

                // Core document dates & references
                $table->date('File_Date')->nullable();
                $table->string('File_Type', 50)->nullable();
                $table->string('File_No', 50)->nullable();
                $table->string('Related_Person', 50)->nullable();
                $table->string('Related_Address', 100)->nullable();

                // Bill fields
                $table->date('BillDate')->nullable();
                $table->string('BillMonth', 20)->nullable();
                $table->string('BillYear', 20)->nullable();
                $table->string('ReferenceNo', 50)->nullable();

                // Location
                $table->string('Loc_Name', 50)->nullable();
                $table->string('Loc_Add', 100)->nullable();

                // Travel date/time range
                $table->dateTime('FromDateTime')->nullable();
                $table->dateTime('ToDateTime')->nullable();

                // Amounts
                $table->decimal('SubTotal', 12, 2)->nullable()->default(0);
                $table->decimal('Total_Amount', 12, 2)->nullable()->default(0);
                $table->decimal('Total_Discount', 12, 2)->nullable()->default(0);
                $table->decimal('Grand_Total', 12, 2)->nullable()->default(0);

                // Payment details
                $table->string('NatureOfPayment', 50)->nullable();
                $table->date('DateOf_SanctApp')->nullable();

                // Tax amounts
                $table->decimal('GST_IGST_Amount', 12, 2)->nullable()->default(0);
                $table->decimal('SGST_Amount', 12, 2)->nullable()->default(0);
                $table->decimal('CGST_Amount', 12, 2)->nullable()->default(0);
                $table->decimal('Cess', 12, 2)->nullable()->default(0);
                $table->decimal('TCS', 12, 2)->nullable()->default(0);

                // Department / ledger
                $table->string('Department', 100)->nullable();
                $table->unsignedInteger('DepartmentID')->nullable();
                $table->string('Ledger', 100)->nullable();
                $table->string('Category', 100)->nullable();

                // File name
                $table->string('FileName', 100)->nullable();

                // Travel fields
                $table->string('Section', 50)->nullable();
                $table->string('TravelMode', 50)->nullable();
                $table->string('TravelQuota', 50)->nullable();
                $table->string('TravelClass', 50)->nullable();
                $table->date('BookingDate')->nullable();
                $table->string('PassengerDetail', 200)->nullable();
                $table->string('BookingStatus', 50)->nullable();
                $table->string('TravelInsurance', 50)->nullable();
                $table->string('TypeOfLoanDoc', 50)->nullable();

                // Bank details
                $table->string('BankName', 50)->nullable();
                $table->string('BankIfscCode', 50)->nullable();
                $table->string('BankAccountNo', 50)->nullable();
                $table->string('BankAddress', 100)->nullable();

                // Renewal / due dates
                $table->date('DueDate')->nullable();
                $table->date('RenewalDate')->nullable();

                // Vehicle / trip fields
                $table->string('Period', 50)->nullable();
                $table->string('PaperSubmitted', 50)->nullable();
                $table->string('Vehicle_Type', 50)->nullable();
                $table->string('VehicleRs_PerKM', 50)->nullable();
                $table->string('TripStarted', 50)->nullable();
                $table->string('TripEnded', 50)->nullable();
                $table->string('VehicleRegNo', 50)->nullable();
                $table->string('OpeningKm', 50)->nullable();
                $table->string('ClosingKm', 50)->nullable();
                $table->string('TotalRunKM', 50)->nullable();

                // Agricultural / dealer fields
                $table->string('ChequeNo', 50)->nullable();
                $table->string('NoOfFarmers', 50)->nullable();
                $table->string('Dealers_TradePartners', 50)->nullable();
                $table->string('CropDetails', 50)->nullable();
                $table->string('VerietyDetails', 50)->nullable();

                // Event / meeting amounts
                $table->decimal('MealsAmount', 12, 2)->nullable()->default(0);
                $table->decimal('HallTent_Amount', 12, 2)->nullable()->default(0);
                $table->decimal('Gift_Amount', 12, 2)->nullable()->default(0);
                $table->decimal('AVTent_Amount', 12, 2)->nullable()->default(0);
                $table->decimal('HiredVehicle_Amount', 12, 2)->nullable()->default(0);
                $table->decimal('Snacks_Amount', 12, 2)->nullable()->default(0);
                $table->decimal('OthCharge_Amount', 12, 2)->nullable()->default(0);

                // Utility / meter fields
                $table->string('MeterNumber', 50)->nullable();
                $table->string('PreviousReading', 50)->nullable();
                $table->string('CurrentReading', 50)->nullable();
                $table->string('UnitsConsumed', 50)->nullable();
                $table->date('LastDateOfPayment')->nullable();

                // FDR / deposit fields
                $table->string('ServiceNo', 50)->nullable()->index();
                $table->string('FDRNo', 50)->nullable();
                $table->string('Depositer', 50)->nullable();
                $table->string('DepositAccNo', 50)->nullable();
                $table->decimal('MaturityAmount', 12, 2)->nullable();
                $table->date('MaturityDate')->nullable();
                $table->decimal('RateOfInterest', 12, 2)->nullable();
                $table->string('JointHolderName', 50)->nullable();
                $table->string('DepositedFrom', 50)->nullable();
                $table->string('ChallanPurpose', 50)->nullable();
                $table->string('BankBSRCode', 50)->nullable();
                $table->string('PaymentHead', 50)->nullable();

                // Agency / vehicle misc
                $table->string('AgencyAddress', 100)->nullable();
                $table->string('VehicleClass', 50)->nullable();
                $table->string('RegNo', 50)->nullable();
                $table->string('BSRCode', 50)->nullable();
                $table->string('MobileNo', 50)->nullable();
                $table->string('BillingCycle', 50)->nullable();
                $table->decimal('DriverCharges', 12, 2)->nullable();
                $table->decimal('TariffPlan', 12, 2)->nullable();
                $table->decimal('PreviousBalance', 12, 2)->nullable();
                $table->decimal('LastPayement', 12, 2)->nullable();

                // Insurance fields
                $table->string('NomineeDetails', 50)->nullable();
                $table->string('SumAssured', 50)->nullable();
                $table->date('PremiumDate')->nullable();
                $table->string('Coverage', 50)->nullable();
                $table->string('AgentName', 50)->nullable();
                $table->string('PropertyArea', 50)->nullable();
                $table->string('OtherSpecif', 50)->nullable();

                // Financial year string (legacy)
                $table->string('Financial_Year', 50)->nullable();

                // General remark
                $table->text('Remark')->nullable();

                // GST / compliance
                $table->string('CPIN', 50)->default('');
                $table->string('CIN', 50)->default('');
                $table->string('GSTIN', 50)->default('');
                $table->string('Email', 50)->default('');

                // Employee fields
                $table->string('EmployeeID', 5)->nullable();
                $table->string('EmployeeCode', 10)->nullable();
                $table->string('Employee_Name', 100)->nullable();
                $table->string('Cal_By', 20)->nullable();
                $table->unsignedTinyInteger('Month')->nullable();
                $table->string('MonthName', 20)->nullable();

                // Hotel / accommodation
                $table->unsignedInteger('Hotel')->nullable();
                $table->string('Hotel_Name', 250)->nullable();
                $table->text('Hotel_Address')->nullable();

                // Particulars
                $table->text('Particular')->nullable();

                // Airline / flight fields
                $table->string('Airline', 60)->nullable();
                $table->string('Base_Fare', 10)->nullable();
                $table->string('Surcharge', 20)->nullable();
                $table->string('Cute_Charge', 20)->nullable();
                $table->string('Extra_Luggage', 20)->nullable();

                // Credit note
                $table->string('CreditNo', 60)->nullable();
                $table->date('CreditDate')->nullable();

                // Audit
                $table->unsignedInteger('Created_By');
                $table->dateTime('Created_Date');

                // Additional dates
                $table->date('file_punch_date')->nullable();

                // Business entity / headquarter
                $table->unsignedInteger('business_entity_id')->nullable();
                $table->unsignedInteger('headquarter_id')->nullable();

                // Document reference
                $table->string('document_number', 50)->nullable();
                $table->text('narration')->nullable();

                // TDS fields
                $table->string('tdsApplicable')->nullable();
                $table->string('TDS_JV_no')->nullable();
                $table->string('TDS_section')->nullable();
                $table->string('TDS_percentage')->nullable();
                $table->string('TDS_amount')->nullable();

                // Payment
                $table->unsignedInteger('Payment_Amount')->nullable();

                // Application
                $table->string('account_group', 200)->nullable();
                $table->string('account', 200)->nullable();
                $table->string('favouring', 200)->nullable();

                // Finance punch
                $table->date('finance_punch_date')->nullable();
                $table->unsignedInteger('finance_total_Amount')->nullable();
                $table->unsignedInteger('Finance_Punch_By')->nullable();

                // Only created_at; no updated_at
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Intentionally left empty: this migration documents a pre-existing table.
    }
};
