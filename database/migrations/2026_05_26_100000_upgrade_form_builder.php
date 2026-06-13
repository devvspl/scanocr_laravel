<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add form-level settings to pages table
        Schema::table('pages', function (Blueprint $table) {
            $table->json('settings')->nullable()->after('is_generated');
        });

        // Add new columns to page_fields for advanced features
        Schema::table('page_fields', function (Blueprint $table) {
            $table->string('field_key', 100)->nullable()->after('field_name');
            $table->json('formula')->nullable()->after('options');
            $table->json('visibility_rules')->nullable()->after('formula');
            $table->json('validation_rules')->nullable()->after('visibility_rules');
            $table->json('auto_fill')->nullable()->after('validation_rules');
            $table->json('summary_config')->nullable()->after('auto_fill');
            $table->json('tax_config')->nullable()->after('summary_config');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('settings');
        });

        Schema::table('page_fields', function (Blueprint $table) {
            $table->dropColumn([
                'field_key', 'formula', 'visibility_rules',
                'validation_rules', 'auto_fill', 'summary_config', 'tax_config',
            ]);
        });
    }
};
