<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Keep only one row per document_type (prefer the default company's row, else the first)
        $defaultCompanyId = DB::table('companies')
            ->where('is_default', true)
            ->where('is_active', true)
            ->value('id')
            ?? DB::table('companies')->where('is_active', true)->value('id');

        $documentTypes = DB::table('numbering_settings')
            ->select('document_type')
            ->distinct()
            ->pluck('document_type');

        foreach ($documentTypes as $docType) {
            $rows = DB::table('numbering_settings')
                ->where('document_type', $docType)
                ->orderByRaw("CASE WHEN company_id = ? THEN 0 ELSE 1 END", [$defaultCompanyId])
                ->orderBy('id')
                ->get();

            if ($rows->count() > 1) {
                $keep = $rows->first();
                $idsToDelete = $rows->skip(1)->pluck('id')->toArray();
                DB::table('numbering_settings')->whereIn('id', $idsToDelete)->delete();
            }
        }

        Schema::table('numbering_settings', function (Blueprint $table) {
            // Must drop FK first, then the unique index that uses it
            $table->dropForeign(['company_id']);
            $table->dropUnique(['company_id', 'document_type']);
            $table->dropColumn('company_id');

            // Ensure uniqueness per document_type globally
            $table->unique(['document_type']);
        });
    }

    public function down(): void
    {
        Schema::table('numbering_settings', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->dropUnique(['document_type']);
            $table->unique(['company_id', 'document_type']);
        });
    }
};
