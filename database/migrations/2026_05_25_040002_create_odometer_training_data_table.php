<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odometer_training_data', function (Blueprint $table) {
            $table->id();
            $table->enum('odometer_type', ['digital', 'analog'])->default('digital');
            $table->enum('source_type', ['dashboard_photo', 'scanned_document']);
            $table->decimal('true_reading', 10, 1);
            $table->string('true_unit', 10)->default('km');
            $table->text('ocr_raw_text')->nullable();
            $table->string('matched_pattern', 100)->nullable();
            $table->json('keywords_found')->nullable();
            $table->string('training_image_filename', 255)->nullable();
            $table->enum('difficulty_level', ['easy', 'normal', 'hard'])->default('normal');
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('odometer_type');
            $table->index('status');
            $table->index('difficulty_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odometer_training_data');
    }
};
