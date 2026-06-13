<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();        // invoice, bill, receipt …
            $table->string('label', 100);               // Sales Invoice
            $table->string('default_prefix', 20);       // INV
            $table->string('icon_path')->nullable();    // SVG path d="…"
            $table->string('module', 50)->nullable();   // sales, purchase, journal …
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(true); // system types can't be deleted
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
