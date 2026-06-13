<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proforma_invoices', function (Blueprint $table) {
            $table->id();

            // ── Identity ──────────────────────────────────────────────────────
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('financial_year_id')->constrained('financial_years')->onDelete('restrict');
            $table->string('proforma_number', 50)->unique();
            $table->date('proforma_date');
            $table->date('due_date')->nullable();

            // ── Party ─────────────────────────────────────────────────────────
            $table->foreignId('party_id')->constrained('parties')->onDelete('restrict');
            $table->string('billing_address')->nullable();
            $table->string('shipping_address')->nullable();

            // ── Reference ─────────────────────────────────────────────────────
            $table->string('reference_number', 100)->nullable();
            $table->string('place_of_supply', 100)->nullable();

            // ── Amounts ───────────────────────────────────────────────────────
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('taxable_amount', 15, 2)->default(0);
            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);
            $table->decimal('igst_amount', 15, 2)->default(0);
            $table->decimal('cess_amount', 15, 2)->default(0);
            $table->decimal('total_tax', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('amount_due', 15, 2)->default(0);

            // ── GST ───────────────────────────────────────────────────────────
            $table->boolean('is_igst')->default(false);

            // ── Workflow ──────────────────────────────────────────────────────
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'cancelled'])
                  ->default('draft');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();

            // ── Conversion ────────────────────────────────────────────────────
            $table->boolean('is_converted')->default(false);
            $table->foreignId('converted_to_invoice_id')->nullable()->constrained('sale_invoices')->nullOnDelete();

            // ── Notes ─────────────────────────────────────────────────────────
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();

            // ── Audit ─────────────────────────────────────────────────────────
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'financial_year_id']);
            $table->index(['party_id']);
            $table->index(['status']);
            $table->index(['proforma_date']);
        });

        Schema::create('proforma_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proforma_invoice_id')->constrained('proforma_invoices')->onDelete('cascade');

            // ── Product reference (nullable — free-text items allowed) ─────────
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('description', 500);
            $table->string('hsn_sac', 20)->nullable();

            // ── Quantity & Pricing ────────────────────────────────────────────
            $table->decimal('qty', 15, 3)->default(1);
            $table->string('unit', 20)->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('taxable_amount', 15, 2)->default(0);

            // ── Tax ───────────────────────────────────────────────────────────
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('cgst_rate', 5, 2)->default(0);
            $table->decimal('sgst_rate', 5, 2)->default(0);
            $table->decimal('igst_rate', 5, 2)->default(0);
            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);
            $table->decimal('igst_amount', 15, 2)->default(0);
            $table->decimal('total_tax', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('proforma_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proforma_invoice_items');
        Schema::dropIfExists('proforma_invoices');
    }
};
