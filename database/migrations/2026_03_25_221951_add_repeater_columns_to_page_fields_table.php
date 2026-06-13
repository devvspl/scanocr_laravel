<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_fields', function (Blueprint $table) {
            // Stores JSON array of sub-column definitions for repeater fields
            // e.g. [{"key":"item","label":"Item","type":"text","required":true,"default":""}]
            $table->json('repeater_columns')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('page_fields', function (Blueprint $table) {
            $table->dropColumn('repeater_columns');
        });
    }
};
