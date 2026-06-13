<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wf_stages', function (Blueprint $table) {
            $table->dropUnique('wf_stages_workflow_id_system_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('wf_stages', function (Blueprint $table) {
            $table->unique(['workflow_id', 'system_key'], 'wf_stages_workflow_id_system_key_unique');
        });
    }
};
