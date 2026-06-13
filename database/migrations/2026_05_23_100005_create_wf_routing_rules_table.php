<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wf_routing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('wf_workflows')->cascadeOnDelete();
            $table->string('from_stage_key', 50);
            $table->string('action_key', 50);
            $table->string('condition_field', 100)->nullable();
            // document field to evaluate e.g. 'bill_amount'
            $table->string('condition_operator', 20)->nullable();
            // gt | lt | eq | gte | lte | in | not_in | is_null | not_null
            $table->string('condition_value', 200)->nullable();
            $table->string('to_stage_key', 50);
            $table->unsignedSmallInteger('priority')->default(0);
            // lower number = evaluated first
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['workflow_id', 'from_stage_key', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wf_routing_rules');
    }
};
