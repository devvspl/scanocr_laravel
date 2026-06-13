<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_invoices', function (Blueprint $table) {
            $table->id();

            // ── Identity ──────────────────────────────────────────────────────
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('financial_year_id')->constrained('financial_years')->onDelete('restrict');
            $table->string('invoice_number', 50)->unique();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();

            // ── Party ─────────────────────────────────────────────────────────
            $table->foreignId('party_id')->constrained('parties')->onDelete('restrict');
            $table->string('billing_address')->nullable();
            $table->string('shipping_address')->nullable();

            // ── Reference ─────────────────────────────────────────────────────
            $table->string('reference_number', 100)->nullable();   // PO / SO ref
            $table->string('place_of_supply', 100)->nullable();    // state for GST

            // ── Amounts ───────────────────────────────────────────────────────
            $table->decimal('subtotal', 15, 2)->default(0);        // sum of line totals before tax
            $table->decimal('discount_amount', 15, 2)->default(0); // invoice-level discount
            $table->decimal('taxable_amount', 15, 2)->default(0);  // subtotal - discount
            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);
            $table->decimal('igst_amount', 15, 2)->default(0);
            $table->decimal('cess_amount', 15, 2)->default(0);
            $table->decimal('total_tax', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('amount_due', 15, 2)->default(0);

            // ── GST ───────────────────────────────────────────────────────────
            $table->boolean('is_igst')->default(false);            // inter-state = IGST

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

            // ── Notes ─────────────────────────────────────────────────────────
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();            // internal remark, not printed

            // ── Audit ─────────────────────────────────────────────────────────
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'financial_year_id']);
            $table->index(['party_id']);
            $table->index(['status']);
            $table->index(['invoice_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_invoices');
    }
};
