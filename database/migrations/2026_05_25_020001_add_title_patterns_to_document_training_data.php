<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_training_data', function (Blueprint $table) {
            $table->text('title_patterns')->nullable()->after('keywords');
            // Comma-separated patterns that identify this doc type in header
            // e.g. "debit note,debit memo,DN No"
        });
    }

    public function down(): void
    {
        Schema::table('document_training_data', function (Blueprint $table) {
            $table->dropColumn('title_patterns');
        });
    }
};
