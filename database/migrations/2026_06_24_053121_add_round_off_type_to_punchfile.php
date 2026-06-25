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
        Schema::table('punchfile', function (Blueprint $table) {
            $table->enum('Round_Off_Type', ['upper', 'lower', 'none'])->default('none')->after('Total_Discount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('punchfile', function (Blueprint $table) {
            $table->dropColumn('Round_Off_Type');
        });
    }
};
