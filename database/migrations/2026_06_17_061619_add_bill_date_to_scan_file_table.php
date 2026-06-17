<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('scan_file', function (Blueprint $table) {
            $table->date('bill_date')->nullable()->after('Scan_Date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scan_file', function (Blueprint $table) {
            $table->dropColumn('bill_date');
        });
    }
};
