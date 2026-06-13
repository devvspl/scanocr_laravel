<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_fields', function (Blueprint $table) {
            $table->dropColumn('width');
        });
    }

    public function down(): void
    {
        Schema::table('page_fields', function (Blueprint $table) {
            $table->string('width')->default('100%')->after('default_value');
        });
    }
};
