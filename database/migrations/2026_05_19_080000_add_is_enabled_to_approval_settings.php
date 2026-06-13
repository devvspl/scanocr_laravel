<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_settings', function (Blueprint $table) {
            $table->boolean('is_enabled')->default(false)->after('document_type');
        });

        // Change levels_count from tinyint to smallint to allow more levels
        Schema::table('approval_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('levels_count')->default(1)->change();
        });

        // Enable existing records that already have approval configured
        \DB::table('approval_settings')
            ->where('approval_mode', '!=', 'no_approval')
            ->update(['is_enabled' => true]);
    }

    public function down(): void
    {
        Schema::table('approval_settings', function (Blueprint $table) {
            $table->dropColumn('is_enabled');
            $table->unsignedTinyInteger('levels_count')->default(1)->change();
        });
    }
};
