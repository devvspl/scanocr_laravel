<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_fields', function (Blueprint $table) {
            $table->string('label')->nullable()->after('field_name');
            $table->string('column_name')->nullable()->after('label');
            $table->string('placeholder')->nullable()->after('column_name');
            $table->string('default_value')->nullable()->after('placeholder');
            $table->string('width')->default('100%')->after('default_value');
            $table->boolean('is_required')->default(false)->after('width');
            $table->boolean('is_unique')->default(false)->after('is_required');
            $table->boolean('is_nullable')->default(true)->after('is_unique');
            $table->unsignedInteger('column_length')->nullable()->after('is_nullable');
            $table->string('description')->nullable()->after('column_length');
        });
    }

    public function down(): void
    {
        Schema::table('page_fields', function (Blueprint $table) {
            $table->dropColumn(['label','column_name','placeholder','default_value','width','is_required','is_unique','is_nullable','column_length','description']);
        });
    }
};
