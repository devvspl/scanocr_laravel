<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_location_access', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('location_id');   // master_work_location.location_id is INT(11)
            $table->boolean('has_access')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'location_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('location_id')->references('location_id')->on('master_work_location')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_location_access');
    }
};
