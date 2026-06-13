<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation migration for the legacy `sub_punchfile` table.
 * Only runs Schema::create when the table does not already exist.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sub_punchfile')) {
            Schema::create('sub_punchfile', function (Blueprint $table) {
                $table->bigIncrements('SubFileID');

                // FK to punchfile.FileID
                $table->unsignedBigInteger('FileID')->nullable();

                // Line-item fields
                $table->string('Particular', 100)->nullable();
                $table->decimal('Qty', 12, 3)->nullable()->default(0);
                $table->decimal('Rate', 12, 2)->nullable()->default(0);
                $table->decimal('Amount', 12, 2)->nullable()->default(0);
                $table->string('Comment', 200)->nullable();

                // No timestamps — legacy table has none
            });
        }
    }

    public function down(): void
    {
        // Intentionally left empty: this migration documents a pre-existing table.
    }
};
