<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wf_stages', function (Blueprint $table) {
            $table->string('layout_template', 50)->default('form_sidebar')->after('page_id');
            // layout_template: form_sidebar, split_panel, full_dashboard
        });
    }

    public function down(): void
    {
        Schema::table('wf_stages', function (Blueprint $table) {
            $table->dropColumn('layout_template');
        });
    }
};
