<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wf_notification_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stage_id')->constrained('wf_stages')->cascadeOnDelete();
            $table->string('trigger_event', 50);
            // on_entry | on_action | on_rejection | on_approval | on_skip
            $table->boolean('notify_uploader')->default(true);
            $table->boolean('notify_assigned_roles')->default(true);
            $table->text('message_template')->nullable();
            // supports: {document_name} {stage_name} {action_by} {action_key}
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wf_notification_rules');
    }
};
