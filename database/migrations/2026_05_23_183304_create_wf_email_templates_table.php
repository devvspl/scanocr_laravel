<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wf_email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('slug', 200)->unique();
            $table->string('subject', 500);
            $table->text('body_html');
            $table->string('category', 100)->default('general');
            // category: approval, notification, escalation, reminder, general
            $table->json('variables')->nullable();
            // available variables for this template
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        // Add template_id to wf_stage_action_map
        Schema::table('wf_stage_action_map', function (Blueprint $table) {
            $table->unsignedBigInteger('email_template_id')->nullable()->after('email_template');
            $table->foreign('email_template_id')->references('id')->on('wf_email_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wf_stage_action_map', function (Blueprint $table) {
            $table->dropForeign(['email_template_id']);
            $table->dropColumn('email_template_id');
        });
        Schema::dropIfExists('wf_email_templates');
    }
};
