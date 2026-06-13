<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_document_type_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('document_type_id')->constrained('document_types')->onDelete('cascade');
            $table->boolean('can_view')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'document_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_document_type_access');
    }
};
