<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imp_import_jobs', function (Blueprint $table) {
            $table->json('column_aliases')->nullable()->after('options');
        });
    }

    public function down(): void
    {
        Schema::table('imp_import_jobs', function (Blueprint $table) {
            $table->dropColumn('column_aliases');
        });
    }
};
