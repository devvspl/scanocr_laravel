<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('core_api_list')) return;

        Schema::create('core_api_list', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('remote_id')->unique();
            $table->string('api_end_point', 255);
            $table->string('table_name', 255);
            $table->text('description')->nullable();
            $table->string('sync_status', 50)->default('pending');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('created')->useCurrent();
            $table->timestamp('updated')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_api_list');
    }
};
