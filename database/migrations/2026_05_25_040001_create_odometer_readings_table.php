<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odometer_readings', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename', 255);
            $table->string('stored_filename', 255);
            $table->string('file_extension', 10);
            $table->enum('odometer_type', ['digital', 'analog', 'unknown'])->default('unknown');
            $table->enum('source_type', ['dashboard_photo', 'scanned_document'])->default('dashboard_photo');
            $table->text('raw_ocr_text')->nullable();
            $table->decimal('reading_value', 10, 1)->nullable();
            $table->string('reading_unit', 10)->nullable();
            $table->decimal('ocr_confidence', 5, 2)->nullable();
            $table->decimal('extraction_confidence', 5, 2)->nullable();
            $table->boolean('is_valid_range')->nullable();
            $table->string('validation_message', 300)->nullable();
            $table->json('bounding_box')->nullable();
            $table->string('cropped_filename', 255)->nullable();
            $table->decimal('confirmed_reading', 10, 1)->nullable();
            $table->string('confirmed_unit', 10)->nullable();
            $table->enum('status', ['pending', 'extracted', 'confirmed', 'corrected', 'failed'])->default('pending');
            $table->boolean('added_to_training')->default(false);
            $table->text('user_remark')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('created_by');
            $table->index('reading_value');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odometer_readings');
    }
};
