<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation migration for the legacy `punchfile2` table.
 * Only runs Schema::create when the table does not already exist.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('punchfile2')) {
            Schema::create('punchfile2', function (Blueprint $table) {
                $table->bigIncrements('FileID');

                // Grouping / ownership
                $table->unsignedInteger('Group_Id');
                $table->unsignedInteger('Scan_Id')->nullable();

                // Document type
                $table->string('DocType', 50)->nullable();
                $table->unsignedSmallInteger('DocTypeId')->nullable();

                // Temporary file location
                $table->string('FileLoc_Temp', 100)->nullable();
                $table->string('FileLoc', 100)->nullable();

                // From / To
                $table->string('FromName', 50)->nullable();
                $table->string('ToName', 50)->nullable();
                $table->dateTime('FromDateTime')->nullable();
                $table->dateTime('ToDateTime')->nullable();
                $table->string('From_Location', 200)->nullable();
                $table->string('To_Location', 200)->nullable();

                // Core document fields
                $table->date('File_Date')->nullable();
                $table->string('File_Type', 50)->nullable();
                $table->string('File_No', 50)->nullable();
                $table->string('Related_Person', 50)->nullable();
                $table->string('Related_Address', 50)->nullable();

                // Amount
                $table->decimal('TotalAmount', 12, 2)->nullable()->default(0);

                // Month / year strings
                $table->string('DocMonth', 20)->nullable();
                $table->string('DocYear', 20)->nullable();

                // Vehicle fields
                $table->string('VehicleNo', 50)->nullable();
                $table->string('VehicleType', 50)->nullable();
                $table->string('VehicleCompany', 50)->nullable();
                $table->date('RegPurDate')->nullable();

                // Registration info
                $table->string('Registered', 255);
                $table->date('ClearanceDate')->nullable();

                // Customer / loan details
                $table->string('CustomerName', 50)->nullable();
                $table->string('Hypothecation', 50)->nullable();
                $table->string('BankName', 50)->nullable();
                $table->string('BankIfscCode', 50)->nullable();
                $table->string('BankAccountNo', 50)->nullable();
                $table->string('BankAddress', 50)->nullable();
                $table->string('PeriodDuration', 50)->nullable();

                // Company / vendor
                $table->string('Company', 50)->nullable();
                $table->unsignedInteger('CompanyID')->nullable();
                $table->string('Vendor', 255)->nullable();
                $table->unsignedInteger('VendorID')->nullable();

                // Location / department
                $table->string('Location', 255)->nullable();
                $table->string('Department', 255);
                $table->unsignedInteger('DepartmentID');

                // Certificate / compliance
                $table->string('FinYear', 50)->nullable();
                $table->string('CertiType', 50)->nullable();
                $table->string('ComRecType', 50)->nullable();
                $table->string('AuditorName', 50)->nullable();
                $table->string('DateofSign', 50)->nullable();
                $table->string('FinancialYear', 50)->nullable();
                $table->string('CertiNo', 50)->nullable();
                $table->date('ValidFrom')->nullable();
                $table->date('Validto')->nullable();

                // Trade / export fields
                $table->string('CompanyType', 50)->nullable();
                $table->string('PackingList', 50)->nullable();
                $table->string('LcAdvance', 50)->nullable();
                $table->string('PartyName', 50)->nullable();
                $table->string('DateOfConfirm', 50)->nullable();

                // Agricultural fields
                $table->string('CropDetails', 50)->nullable();
                $table->string('VerietyDetails', 50)->nullable();
                $table->string('ProblemIssue', 50)->nullable();
                $table->string('Remedy', 50)->nullable();

                // Medical fields
                $table->string('Doctor', 50)->nullable();
                $table->string('Hospital', 50)->nullable();
                $table->string('Medicine', 50)->nullable();
                $table->string('TreatmentTaken', 50)->nullable();
                $table->string('PaymentHead', 50)->nullable();
                $table->string('TRRN', 50)->nullable();
                $table->string('CRN', 50)->nullable();

                // Land / property fields
                $table->string('KhasraNo', 50)->nullable();
                $table->string('TotalArea', 50)->nullable();
                $table->string('Unit', 50)->nullable();
                $table->string('KHNo', 50)->nullable();
                $table->string('PHNo', 50)->nullable();
                $table->string('RNM_Ward', 50)->nullable();
                $table->decimal('MarketValue', 12, 2)->nullable();
                $table->string('RinPushtikaNo', 50)->nullable();
                $table->decimal('ExtraCharge', 12, 2)->nullable();

                // Document status fields (stored as strings in legacy schema)
                $table->string('Stamp_Duty')->nullable();
                $table->text('Diversion_Paper')->nullable();
                $table->string('Map_Approval')->nullable();
                $table->string('Additional_Exposure')->nullable();

                // Due date
                $table->date('DueDate')->nullable();

                // Rating / remark
                $table->string('Rating', 50);
                $table->string('Remark', 200)->nullable();

                // Audit
                $table->unsignedInteger('Created_By');
                $table->dateTime('Created_Date');

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
