<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wf_notification_rule_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_rule_id')->constrained('wf_notification_rules')->cascadeOnDelete();
            $table->unsignedBigInteger('role_id');
            $table->timestamps();

            $table->unique(['notification_rule_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wf_notification_rule_roles');
    }
};
