<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation migration for the legacy `scan_file` table.
 *
 * The table already exists in the database. This migration uses
 * Schema::hasTable() so it is safe to run on both fresh and existing
 * databases: it will create the table only when it is absent.
 *
 * NOTE: year_id is intentionally omitted here; it is added by the
 *       separate migration 2026_06_13_080005_add_year_id_to_scan_file.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('scan_file')) {
            Schema::create('scan_file', function (Blueprint $table) {
                // Primary key (legacy column Scan_Id)
                $table->bigIncrements('Scan_Id');

                // Grouping / ownership
                $table->unsignedInteger('Group_Id')->index();
                $table->unsignedInteger('Location')->nullable();

                // Document classification
                $table->string('Doc_Type', 60)->nullable()->index();
                $table->unsignedInteger('DocType_Id');

                // Temporary scan workflow
                $table->char('Temp_Scan', 1)->nullable();              // Y/N
                $table->dateTime('Temp_Scan_Date')->nullable();
                $table->unsignedInteger('Temp_Scan_By')->nullable();
                $table->char('Scan_Complete', 1)->nullable();
                $table->unsignedInteger('Scan_By')->nullable();

                // Document verification
                $table->char('document_verified', 1)->default('N');
                $table->unsignedInteger('document_verified_by')->nullable();
                $table->date('document_verified_date')->nullable();
                $table->date('document_received_date')->nullable();

                // Temp scan rejection
                $table->char('temp_scan_reject', 1)->default('N');
                $table->text('temp_scan_reject_remark')->nullable();
                $table->unsignedInteger('temp_scan_reject_by')->nullable();
                $table->date('temp_scan_reject_date')->nullable();

                // File identity
                $table->string('Document_Name', 255)->nullable()->index();
                $table->string('File', 255);
                $table->string('File_Ext', 10);
                $table->text('File_Location');
                $table->text('File_Location1')->nullable();
                $table->char('Main_File', 1)->default('Y');
                $table->dateTime('Scan_Date')->nullable();

                // Year (legacy 4-digit year stored as YEAR / small int)
                $table->unsignedSmallInteger('Year');

                // Submission & punching flags
                $table->char('Final_Submit', 1)->default('N');
                $table->char('File_Punched', 1)->default('N');

                // Soft delete
                $table->char('Is_Deleted', 1)->default('N');
                $table->dateTime('Delete_Date')->nullable();
                $table->unsignedInteger('Deleted_By')->nullable();

                // Punch audit
                $table->unsignedInteger('Punch_By')->nullable();
                $table->dateTime('Punch_Date')->nullable();

                // File approval
                $table->char('File_Approved', 1)->default('N');
                $table->unsignedInteger('Approve_By')->nullable();
                $table->dateTime('Approve_Date')->nullable();

                // Admin approval
                $table->char('Admin_Approve', 1)->default('N');

                // Resend workflow
                $table->char('Scan_Resend', 1)->default('N');
                $table->text('Scan_Resend_Remark')->nullable();
                $table->unsignedInteger('Scan_Resend_By')->nullable();
                $table->date('Scan_Resend_Date')->nullable();

                // Rejection workflow
                $table->char('Is_Rejected', 1)->default('N');
                $table->text('Reject_Remark')->nullable();
                $table->date('Reject_Date')->nullable();

                // Edit permission
                $table->char('Edit_Permission', 1)->default('N');

                // Index fields
                $table->string('Index_Name1', 255)->nullable();
                $table->string('Index_Name2', 255)->nullable();
                $table->string('Index_Name3', 255)->nullable();

                // Entry confirmation
                $table->char('Entry_Confirm', 1)->default('N');
                $table->dateTime('Confirm_Date')->nullable();

                // Bill approval (Y / N / R)
                $table->char('Bill_Approved', 1)->default('N');
                $table->unsignedInteger('Bill_Approver')->default(0);
                $table->date('Bill_Approver_Date')->nullable();
                $table->text('Bill_Approver_Remark')->nullable();

                // Scan document-type reference
                $table->unsignedInteger('scan_doctype_id')->nullable();

                // Organisational context
                $table->unsignedInteger('department_id')->default(0);
                $table->unsignedInteger('firm_id')->default(0);

                // Voucher / bill fields
                $table->date('bill_voucher_date')->nullable();
                $table->string('bill_no_voucher_no', 150)->default('0');

                // Finance workflow
                $table->char('at_finance', 1)->default('P');
                $table->unsignedSmallInteger('Finance_Punch_By')->nullable();
                $table->date('finance_punch_date')->nullable();
                $table->text('Finance_Resend_Remark')->nullable();
                $table->unsignedInteger('Finance_Resend_By')->nullable();
                $table->date('Finance_Resend_Date')->nullable();
                $table->char('Is_Finance_Rejected', 1)->nullable()->default('N');

                // Extraction status
                $table->char('is_extract', 1)->default('P');
                $table->unsignedInteger('classified_by')->nullable()->default(0);
                $table->dateTime('classified_date')->nullable();
                $table->char('extract_status', 1)->nullable()->default('P');

                // No created_at / updated_at — legacy table has none
            });
        }
    }

    public function down(): void
    {
        // Intentionally left empty: this migration documents a pre-existing
        // table and should not drop it on rollback.
    }
};
