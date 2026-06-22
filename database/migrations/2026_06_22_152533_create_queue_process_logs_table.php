<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('queue_process_logs')) {
            Schema::create('queue_process_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('queue_id')->index();
                $table->unsignedBigInteger('scan_id')->index();
                $table->unsignedInteger('type_id')->default(0);
                $table->string('status', 20)->default('started');
                $table->text('api_endpoint')->nullable();
                $table->text('file_url')->nullable();
                $table->unsignedSmallInteger('response_code')->nullable();
                $table->text('message')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_process_logs');
    }
};
