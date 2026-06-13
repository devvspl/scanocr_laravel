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
        Schema::create('imp_import_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('template_id')->nullable()->constrained('imp_import_templates')->nullOnDelete();
            $table->string('job_uuid')->unique();           // UUID for tracking
            $table->string('data_type');                    // what entity is being imported
            $table->string('source_type');                  // excel, csv, sql, api
            $table->string('source_identifier')->nullable();// filename or API endpoint
            $table->string('status')->default('pending');   // pending, processing, completed, failed, partial
            $table->integer('total_rows')->default(0);
            $table->integer('processed_rows')->default(0);
            $table->integer('success_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->integer('skipped_rows')->default(0);
            $table->json('options')->nullable();            // dedup strategy, on_conflict, etc.
            $table->text('notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'data_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imp_import_jobs');
    }
};
