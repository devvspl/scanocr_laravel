<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wf_stage_action_map', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stage_id');
            $table->unsignedBigInteger('action_definition_id');
            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('stage_id')->references('id')->on('wf_stages')->cascadeOnDelete();
            $table->foreign('action_definition_id')->references('id')->on('wf_action_definitions')->cascadeOnDelete();
            $table->unique(['stage_id', 'action_definition_id'], 'stage_action_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wf_stage_action_map');
    }
};
