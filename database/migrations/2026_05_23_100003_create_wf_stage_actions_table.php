<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wf_stage_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stage_id')->constrained('wf_stages')->cascadeOnDelete();
            $table->string('action_key', 50);
            // Valid values per stage:
            // Scanner:      upload_file | upload_supporting | final_submit | edit_rejected | delete_scan
            // Extraction:   trigger_extraction
            // DocClass:     reject_scan | classify_document | fill_received_date | reclassify_document
            // DMSPunch:     reject_classify | punch_document | skip_bill_approval | repunch_document
            // PunchAppr:    approve_punch | reject_punch | bypass_approval
            // BillAppr:     approve_bill | reject_bill
            // FinPunch:     additional_punch | reject_to_bill | repunch_finance
            // PunchApprFin: approve_final | reject_final
            // FocusExport:  export_csv
            $table->string('display_label', 200);
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_remark')->default(false);
            $table->string('remark_label', 100)->default('Remark');
            $table->boolean('confirm_before_action')->default(false);
            $table->string('confirm_message', 300)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('icon', 100)->nullable();
            $table->string('button_style', 50)->default('primary');
            // primary | success | danger | warning | secondary
            $table->string('next_stage_key', 50)->nullable();
            // override routing — null = follow default pipeline order
            $table->timestamps();

            $table->unique(['stage_id', 'action_key']);
            $table->index(['stage_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wf_stage_actions');
    }
};
