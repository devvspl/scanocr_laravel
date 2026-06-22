<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('document_types', 'is_punch')) {
            Schema::table('document_types', function (Blueprint $table) {
                $table->boolean('is_punch')->default(false)->after('is_system');
            });
        }

        // Set is_punch = true for specified IDs
        DB::table('document_types')
            ->whereIn('id', [1, 6, 7, 13, 17, 20, 22, 23, 27, 28, 29, 31, 42, 43, 44, 46, 47, 48, 50, 51, 52, 54, 55, 56])
            ->update(['is_punch' => true]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('document_types', 'is_punch')) {
            Schema::table('document_types', function (Blueprint $table) {
                $table->dropColumn('is_punch');
            });
        }
    }
};
