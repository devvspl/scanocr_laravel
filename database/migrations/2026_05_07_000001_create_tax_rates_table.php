<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // e.g. GST 18%
            $table->string('code', 30)->nullable();          // e.g. GST18
            $table->enum('type', ['gst', 'igst', 'cess', 'tds', 'tcs', 'other'])->default('gst');
            $table->decimal('rate', 5, 2)->default(0);       // e.g. 18.00
            $table->decimal('cgst', 5, 2)->default(0);       // half of rate for intra-state
            $table->decimal('sgst', 5, 2)->default(0);       // half of rate for intra-state
            $table->decimal('igst', 5, 2)->default(0);       // full rate for inter-state
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
