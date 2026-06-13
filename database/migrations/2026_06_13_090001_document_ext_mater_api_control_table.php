<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentation migration for ext_mater_api_control.
 * Table already exists in the DB — only creates if absent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ext_mater_api_control')) return;

        Schema::create('ext_mater_api_control', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('doctype_id');
            $table->string('endpoint', 255);
            $table->text('description')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamp('created')->useCurrent();
            $table->timestamp('updated')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ext_mater_api_control');
    }
};
