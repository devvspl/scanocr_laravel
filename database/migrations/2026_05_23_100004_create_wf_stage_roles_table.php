<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wf_stage_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stage_id')->constrained('wf_stages')->cascadeOnDelete();
            $table->unsignedBigInteger('role_id');
            // FK to Spatie roles table
            $table->boolean('can_view')->default(true);
            $table->boolean('can_act')->default(true);
            $table->boolean('is_notified')->default(true);
            $table->timestamps();

            $table->unique(['stage_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wf_stage_roles');
    }
};
