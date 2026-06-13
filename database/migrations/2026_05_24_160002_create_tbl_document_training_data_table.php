<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_training_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_id')
                  ->constrained('document_types')
                  ->onDelete('cascade');
            $table->text('sample_text');
            $table->text('keywords')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('document_type_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_training_data');
    }
};
