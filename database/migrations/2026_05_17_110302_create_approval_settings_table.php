<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('document_type', 50); // matches numbering_settings.document_type
            $table->enum('approval_mode', ['required', 'auto_approved', 'no_approval'])->default('no_approval');
            $table->unsignedTinyInteger('levels_count')->default(1); // 1-3
            $table->json('levels')->nullable(); // JSON array of level configs
            /*
             * levels JSON structure:
             * [
             *   {
             *     "name": "Manager Approval",
             *     "approver_ids": [1, 2, 3],
             *     "approval_type": "any_one" | "all_must",
             *     "notify_via": "email" | "sms" | "both",
             *     "outstanding_hours": 24,
             *     "auto_reject_days": 7,
             *     "escalation_enabled": true,
             *     "escalation_hours": 48
             *   }
             * ]
             */
            $table->timestamps();

            $table->unique(['company_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_settings');
    }
};
