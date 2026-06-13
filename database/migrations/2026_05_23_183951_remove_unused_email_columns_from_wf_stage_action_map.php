<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wf_stage_action_map', function (Blueprint $table) {
            $table->dropColumn(['email_subject', 'email_body', 'email_template']);
        });
    }

    public function down(): void
    {
        Schema::table('wf_stage_action_map', function (Blueprint $table) {
            $table->string('email_subject', 500)->nullable();
            $table->text('email_body')->nullable();
            $table->string('email_template', 100)->default('default');
        });
    }
};
