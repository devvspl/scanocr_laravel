<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wf_stage_action_map', function (Blueprint $table) {
            // Direct user assignment (in addition to roles)
            $table->json('notify_user_ids')->nullable()->after('notify_recipients');
            // Email template settings
            $table->string('email_subject', 500)->nullable()->after('notify_next_stage');
            $table->text('email_body')->nullable()->after('email_subject');
            $table->string('email_template', 100)->default('default')->after('email_body');
            // email_template: default, minimal, detailed, custom
        });
    }

    public function down(): void
    {
        Schema::table('wf_stage_action_map', function (Blueprint $table) {
            $table->dropColumn(['notify_user_ids', 'email_subject', 'email_body', 'email_template']);
        });
    }
};
