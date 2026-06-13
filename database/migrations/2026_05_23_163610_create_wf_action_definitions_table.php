<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wf_action_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('group', 100)->index();
            $table->string('action_key', 100)->unique();
            $table->string('display_label', 200);
            $table->string('icon', 100)->nullable();
            $table->string('button_style', 30)->default('primary'); // primary, success, danger, warning, info
            $table->string('logic_type', 50)->default('status_change');
            // logic_type: status_change, stage_move, api_call, notification, file_operation, ledger_post, validation, export
            $table->json('logic_config')->nullable();
            // logic_config stores: next_stage_key, api_endpoint, validation_rules, export_format, etc.
            $table->boolean('requires_remark')->default(false);
            $table->boolean('requires_confirmation')->default(false);
            $table->string('confirm_message', 500)->nullable();
            $table->boolean('is_system')->default(false); // system actions can't be deleted
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wf_action_definitions');
    }
};
