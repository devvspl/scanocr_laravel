<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_compression_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by');

            // Original file info
            $table->string('original_filename');
            $table->string('original_stored_name');
            $table->unsignedBigInteger('original_size');       // bytes
            $table->unsignedSmallInteger('original_pages')->default(0);

            // Compression settings
            $table->enum('engine', ['ghostscript', 'pikepdf', 'pymupdf', 'auto'])->default('auto');
            $table->enum('quality', ['screen', 'ebook', 'printer', 'prepress'])->default('ebook');

            // Result
            $table->string('compressed_stored_name')->nullable();
            $table->unsignedBigInteger('compressed_size')->nullable(); // bytes
            $table->float('compression_ratio')->nullable();            // e.g. 0.62 = 62% of original
            $table->float('processing_time')->nullable();              // seconds
            $table->string('engine_used')->nullable();                 // which engine actually ran

            $table->enum('status', ['pending', 'processing', 'done', 'failed'])->default('pending');
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['created_by', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_compression_jobs');
    }
};
