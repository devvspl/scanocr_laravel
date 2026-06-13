<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('wf_action_definitions', 'button_color')) {
            Schema::table('wf_action_definitions', function (Blueprint $table) {
                $table->string('button_color', 30)->nullable()->after('button_style');
            });
        }
    }

    public function down(): void
    {
        Schema::table('wf_action_definitions', function (Blueprint $table) {
            $table->dropColumn('button_color');
        });
    }
};
