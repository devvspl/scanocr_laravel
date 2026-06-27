<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_action_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('scan_id')->index();
            $table->string('action', 100)->index();         // e.g. 'temp_scan_uploaded', 'bill_approved', 'classified', 'punched', 'punch_approved'
            $table->string('action_label', 255)->nullable(); // Human-readable label e.g. "Bill Approved"
            $table->unsignedInteger('performed_by')->nullable();
            $table->string('performer_name', 100)->nullable();
            $table->text('remark')->nullable();              // Optional remark/reason
            $table->json('metadata')->nullable();            // Extra data (old values, new values, etc.)
            $table->timestamp('performed_at')->useCurrent();

            $table->index(['scan_id', 'performed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_action_logs');
    }
};
