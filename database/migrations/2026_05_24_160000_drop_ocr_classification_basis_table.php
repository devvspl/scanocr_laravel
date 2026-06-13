<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old tables that had FK references to ocr_classification_basis
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('tbl_document_predictions');
        Schema::dropIfExists('tbl_document_training_data');
        Schema::dropIfExists('ocr_classification_basis');
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Not recreating — table is no longer used
    }
};
