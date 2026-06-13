<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_prediction_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->enum('rule_type', ['doc_type', 'vendor_keyword', 'content_keyword']);
            // doc_type: if document type matches, assign this dept
            // vendor_keyword: if vendor/party name contains keyword
            // content_keyword: if document body contains keyword
            $table->string('pattern', 255);
            // The keyword or pattern to match (lowercase)
            $table->unsignedTinyInteger('weight')->default(50);
            // Weight 1-100: how strong this signal is
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['department_id', 'rule_type']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_prediction_rules');
    }
};
