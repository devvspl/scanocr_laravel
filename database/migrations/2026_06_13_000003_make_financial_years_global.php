<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Keep only one row per unique (label / start_date) — deduplicate before dropping company_id.
        // We'll keep the row that has is_current = true, or just the first one otherwise.
        // Collect duplicate sets grouped by start_date + end_date
        $duplicateGroups = DB::table('financial_years')
            ->select('start_date', 'end_date')
            ->groupBy('start_date', 'end_date')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            $rows = DB::table('financial_years')
                ->where('start_date', $group->start_date)
                ->where('end_date', $group->end_date)
                ->orderByDesc('is_current')
                ->orderBy('id')
                ->get();

            // Keep the first (is_current preferred), delete the rest
            $keep = $rows->shift();
            DB::table('financial_years')
                ->whereIn('id', $rows->pluck('id')->toArray())
                ->delete();
        }

        Schema::table('financial_years', function (Blueprint $table) {
            // Drop the FK constraint and column
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');

            // Drop the old composite index and add a simple one
            $table->index(['is_current']);
        });
    }

    public function down(): void
    {
        Schema::table('financial_years', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->index(['company_id', 'is_current']);
        });
    }
};
