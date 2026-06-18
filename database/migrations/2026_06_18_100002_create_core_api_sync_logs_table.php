<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('core_api_sync_logs')) return;

        Schema::create('core_api_sync_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('core_api_list_id')->nullable()->index();
            $table->string('api_end_point', 255);
            $table->string('table_name', 255);
            $table->unsignedInteger('added')->default(0);
            $table->unsignedInteger('updated')->default(0);
            $table->unsignedInteger('removed')->default(0);
            $table->string('status', 50)->default('success');
            $table->text('message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('created')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_api_sync_logs');
    }
};
