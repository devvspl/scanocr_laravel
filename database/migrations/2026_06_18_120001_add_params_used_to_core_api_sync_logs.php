<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('core_api_sync_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('core_api_sync_logs', 'params_used')) {
                $table->text('params_used')->nullable()->after('api_end_point');
            }
        });
    }

    public function down(): void
    {
        Schema::table('core_api_sync_logs', function (Blueprint $table) {
            $table->dropColumn('params_used');
        });
    }
};
