<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Immutable audit log — never update/delete rows here
        Schema::create('wf_workflow_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('wf_workflows');
            $table->unsignedBigInteger('stage_id')->nullable();
            // nullable — stage may be deleted later
            $table->string('system_key', 50);
            $table->string('action_key', 50);
            $table->string('document_ref', 100);
            // scan_id or document identifier
            $table->unsignedBigInteger('doc_type_id')->nullable();
            $table->unsignedBigInteger('performed_by');
            // FK to users table
            $table->string('from_stage_key', 50)->nullable();
            $table->string('to_stage_key', 50)->nullable();
            $table->text('remark')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('performed_at')->useCurrent();
            // No updated_at — this table is append-only
            $table->timestamp('created_at')->useCurrent();

            $table->index('document_ref');
            $table->index(['performed_by', 'performed_at']);
            $table->index(['workflow_id', 'system_key']);
            $table->index('performed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wf_workflow_logs');
    }
};
