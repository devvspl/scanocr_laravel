<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the year_id column to scan_file as a nullable FK to financial_years.id.
 *
 * This is a REAL (runnable) migration — it modifies the live table.
 *
 * The legacy scan_file table contains a `classified_date` column with
 * DEFAULT '0000-00-00 00:00:00', which MySQL strict mode (NO_ZERO_DATE /
 * STRICT_TRANS_TABLES) rejects during any ALTER TABLE on the table.
 * We temporarily relax the sql_mode for this session only, then restore it.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Save current sql_mode and drop the problematic strict flags
        $originalMode = DB::selectOne("SELECT @@SESSION.sql_mode AS mode")->mode;
        $relaxedMode  = implode(',', array_filter(
            explode(',', $originalMode),
            fn($flag) => !in_array(trim($flag), ['STRICT_TRANS_TABLES', 'NO_ZERO_DATE', 'NO_ZERO_IN_DATE'])
        ));
        DB::statement("SET SESSION sql_mode = '{$relaxedMode}'");

        try {
            Schema::table('scan_file', function (Blueprint $table) {
                $table->unsignedBigInteger('year_id')
                      ->nullable()
                      ->after('Year')
                      ->comment('FK to financial_years.id');

                $table->foreign('year_id')
                      ->references('id')
                      ->on('financial_years')
                      ->onDelete('set null');

                $table->index('year_id');
            });
        } finally {
            // Always restore the original sql_mode
            DB::statement("SET SESSION sql_mode = '{$originalMode}'");
        }
    }

    public function down(): void
    {
        $originalMode = DB::selectOne("SELECT @@SESSION.sql_mode AS mode")->mode;
        $relaxedMode  = implode(',', array_filter(
            explode(',', $originalMode),
            fn($flag) => !in_array(trim($flag), ['STRICT_TRANS_TABLES', 'NO_ZERO_DATE', 'NO_ZERO_IN_DATE'])
        ));
        DB::statement("SET SESSION sql_mode = '{$relaxedMode}'");

        try {
            Schema::table('scan_file', function (Blueprint $table) {
                $table->dropForeign(['year_id']);
                $table->dropIndex(['year_id']);
                $table->dropColumn('year_id');
            });
        } finally {
            DB::statement("SET SESSION sql_mode = '{$originalMode}'");
        }
    }
};
