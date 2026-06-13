<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_invoice_id')->constrained('sale_invoices')->onDelete('cascade');

            // ── Product reference (nullable — free-text items allowed) ─────────
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('description', 500);
            $table->string('hsn_sac', 20)->nullable();

            // ── Quantity & Pricing ────────────────────────────────────────────
            $table->decimal('qty', 15, 3)->default(1);
            $table->string('unit', 20)->nullable();              // unit symbol
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount_pct', 5, 2)->default(0);  // % discount on line
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('taxable_amount', 15, 2)->default(0); // (qty * unit_price) - discount

            // ── Tax ───────────────────────────────────────────────────────────
            $table->decimal('tax_rate', 5, 2)->default(0);      // total GST %
            $table->decimal('cgst_rate', 5, 2)->default(0);
            $table->decimal('sgst_rate', 5, 2)->default(0);
            $table->decimal('igst_rate', 5, 2)->default(0);
            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);
            $table->decimal('igst_amount', 15, 2)->default(0);
            $table->decimal('total_tax', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);   // taxable + tax

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('sale_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_invoice_items');
    }
};
