<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wf_stage_action_map', function (Blueprint $table) {
            $table->boolean('notify_enabled')->default(false)->after('is_active');
            $table->string('notify_medium', 50)->default('email')->after('notify_enabled');
            // notify_medium: email, sms, slack, teams, webhook
            $table->json('notify_recipients')->nullable()->after('notify_medium');
            // notify_recipients: array of user_ids or role_ids e.g. {"type":"roles","ids":[1,2]}
            $table->string('notify_frequency', 30)->default('once')->after('notify_recipients');
            // notify_frequency: once, daily, on_escalation
            $table->integer('escalation_hours')->nullable()->after('notify_frequency');
            // escalation_hours: hours before escalation triggers (for approval/edit type actions)
            $table->boolean('notify_next_stage')->default(false)->after('escalation_hours');
            // notify_next_stage: should the next stage assignees be notified when this action completes
        });
    }

    public function down(): void
    {
        Schema::table('wf_stage_action_map', function (Blueprint $table) {
            $table->dropColumn([
                'notify_enabled',
                'notify_medium',
                'notify_recipients',
                'notify_frequency',
                'escalation_hours',
                'notify_next_stage',
            ]);
        });
    }
};
