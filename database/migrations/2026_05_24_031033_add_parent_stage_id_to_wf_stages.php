<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wf_stages', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_stage_id')->nullable()->after('workflow_id');
            $table->foreign('parent_stage_id')->references('id')->on('wf_stages')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wf_stages', function (Blueprint $table) {
            $table->dropForeign(['parent_stage_id']);
            $table->dropColumn('parent_stage_id');
        });
    }
};
