<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('financial_year_id')->constrained('financial_years')->onDelete('restrict');
            $table->string('receipt_number', 50)->unique();
            $table->date('receipt_date');

            // Payer (customer)
            $table->foreignId('party_id')->constrained('parties')->onDelete('restrict');

            // Payment details
            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'cheque', 'upi', 'card', 'other'])->default('cash');
            $table->string('payment_reference', 100)->nullable(); // cheque no, UTR, transaction ID
            $table->date('payment_date')->nullable(); // date of actual payment (may differ from receipt date)

            // Bank details (for bank transfer/cheque)
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account', 50)->nullable();

            // Against invoice(s)
            $table->foreignId('sale_invoice_id')->nullable()->constrained('sale_invoices')->nullOnDelete();

            // Purpose / description
            $table->text('description')->nullable();
            $table->text('narration')->nullable();

            // Workflow
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'cancelled'])->default('draft');
            $table->unsignedTinyInteger('current_approval_level')->default(0);
            $table->unsignedTinyInteger('max_approval_level')->default(0);
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

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'financial_year_id']);
            $table->index(['party_id']);
            $table->index(['status']);
            $table->index(['receipt_date']);
            $table->index(['sale_invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
