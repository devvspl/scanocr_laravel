<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('imp_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_job_id')->constrained('imp_import_jobs')->onDelete('cascade');
            $table->integer('row_number');
            $table->json('raw_data');                       // original imported row data
            $table->json('mapped_data')->nullable();        // after column mapping applied
            $table->string('status')->default('pending');   // pending, success, failed, skipped, duplicate
            $table->text('error_message')->nullable();
            $table->string('entity_id')->nullable();        // ID of created/updated record
            $table->string('action_taken')->nullable();     // created, updated, skipped
            $table->timestamps();
            $table->index(['import_job_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imp_import_rows');
    }
};
