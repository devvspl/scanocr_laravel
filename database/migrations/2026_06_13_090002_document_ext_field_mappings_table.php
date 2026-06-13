<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation migration for ext_field_mappings.
 * Table already exists in the DB — only creates if absent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ext_field_mappings')) return;

        Schema::create('ext_field_mappings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('doctype_id');
            $table->string('temp_column', 255);
            $table->string('input_type', 10)->default('input'); // input | select
            $table->string('select_table', 255)->nullable();
            $table->string('relation_column', 255)->nullable();
            $table->string('relation_value', 255)->nullable();
            $table->string('punch_table', 100);
            $table->string('punch_column', 255)->nullable();
            $table->char('has_Items_feild', 1)->default('N'); // Y/N
            $table->text('add_condition')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ext_field_mappings');
    }
};
