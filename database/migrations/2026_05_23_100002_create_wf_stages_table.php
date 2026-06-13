<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wf_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('wf_workflows')->cascadeOnDelete();
            $table->string('system_key', 50);
            // Valid values: scanner | extraction | doc_classifier | dms_punching |
            //               punching_approval | bill_approver | finance_punching |
            //               punch_approver | focus_export
            $table->string('display_name', 200);
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_optional')->default(false);
            $table->string('icon', 100)->nullable();
            $table->string('color', 20)->nullable();
            $table->text('description')->nullable();
            $table->json('config')->nullable();
            // Stage-specific JSON config (schema defined in WfStage::DEFAULT_CONFIGS)
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['workflow_id', 'system_key']);
            $table->unique(['workflow_id', 'position']);
            $table->index(['workflow_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wf_stages');
    }
};
