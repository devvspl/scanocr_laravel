<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wf_action_definitions', function (Blueprint $table) {
            $table->text('icon')->nullable()->change();
            $table->string('button_color', 30)->nullable()->after('button_style');
        });
    }

    public function down(): void
    {
        Schema::table('wf_action_definitions', function (Blueprint $table) {
            $table->string('icon', 100)->nullable()->change();
            $table->dropColumn('button_color');
        });
    }
};
