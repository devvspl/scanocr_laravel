<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->index();
            $table->unsignedInteger('scan_id')->nullable()->index();
            $table->string('type', 50)->index();           // bill_assigned, bill_approved, bill_rejected, etc.
            $table->string('title', 255);
            $table->text('body');
            $table->string('fcm_token', 500)->nullable();
            $table->enum('status', ['sent', 'failed', 'no_token'])->default('sent');
            $table->text('error_message')->nullable();
            $table->json('data')->nullable();               // payload data (scan_id, type, etc.)
            $table->integer('response_code')->nullable();
            $table->timestamp('sent_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
