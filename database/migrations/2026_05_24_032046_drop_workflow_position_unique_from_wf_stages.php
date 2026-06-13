<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the unique index that prevents sub-stages from sharing position numbers
        Schema::table('wf_stages', function (Blueprint $table) {
            $table->dropUnique('wf_stages_workflow_id_position_unique');
        });
    }

    public function down(): void
    {
        Schema::table('wf_stages', function (Blueprint $table) {
            $table->unique(['workflow_id', 'position'], 'wf_stages_workflow_id_position_unique');
        });
    }
};
