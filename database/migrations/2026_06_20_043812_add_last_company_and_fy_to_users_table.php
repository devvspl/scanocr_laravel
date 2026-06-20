<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('last_company_id')
                  ->nullable()
                  ->after('remember_token')
                  ->constrained('companies')
                  ->nullOnDelete();

            $table->foreignId('last_fy_id')
                  ->nullable()
                  ->after('last_company_id')
                  ->constrained('financial_years')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['last_company_id']);
            $table->dropForeign(['last_fy_id']);
            $table->dropColumn(['last_company_id', 'last_fy_id']);
        });
    }
};
