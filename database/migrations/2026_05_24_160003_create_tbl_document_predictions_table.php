<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_predictions', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename', 255);
            $table->string('stored_filename', 255);
            $table->string('file_extension', 10);
            $table->longText('ocr_text')->nullable();
            $table->foreignId('predicted_type_id')->nullable()
                  ->constrained('document_types')
                  ->nullOnDelete();
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->foreignId('confirmed_type_id')->nullable()
                  ->constrained('document_types')
                  ->nullOnDelete();
            $table->enum('status', ['pending', 'predicted', 'confirmed', 'corrected', 'saved'])->default('pending');
            $table->text('user_remark')->nullable();
            $table->integer('ocr_page_count')->default(1);
            $table->json('ocr_page_texts')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('predicted_type_id');
            $table->index('confirmed_type_id');
            $table->index('status');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_predictions');
    }
};
