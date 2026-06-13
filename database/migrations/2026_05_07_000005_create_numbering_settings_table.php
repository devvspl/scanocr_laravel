<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('numbering_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('document_type', 50);            
            $table->string('prefix', 20)->default('');
            $table->string('suffix', 20)->default('');
            $table->unsignedInteger('next_number')->default(1);
            $table->unsignedTinyInteger('pad_length')->default(4); 
            $table->enum('reset_frequency', ['never', 'yearly', 'monthly'])->default('yearly');
            $table->boolean('include_date')->default(false); 
            $table->string('date_format', 20)->default('YYYY-MM');
            $table->string('separator', 5)->default('/');
            $table->string('preview')->nullable();          
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('numbering_settings');
    }
};
