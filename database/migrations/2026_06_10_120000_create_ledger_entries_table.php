<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            
            // ── Core Identification ───────────────────────────────────────────
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('financial_year_id')->constrained('financial_years')->onDelete('restrict');
            $table->date('entry_date');
            $table->string('voucher_type', 50); // 'sales_invoice', 'receipt', 'credit_note', 'payment', 'journal', etc.
            $table->string('voucher_number', 100);
            
            // ── Reference to source document ──────────────────────────────────
            $table->unsignedBigInteger('document_id')->nullable(); // ID of source document
            $table->string('document_type', 50)->nullable(); // Polymorphic reference
            
            // ── Account Details ───────────────────────────────────────────────
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('restrict');
            $table->foreignId('party_id')->nullable()->constrained('parties')->onDelete('restrict');
            $table->string('account_name', 255); // Denormalized for performance
            
            // ── Debit / Credit ────────────────────────────────────────────────
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            
            // ── Additional Details ────────────────────────────────────────────
            $table->text('narration')->nullable();
            $table->text('description')->nullable();
            
            // ── Reconciliation ────────────────────────────────────────────────
            $table->boolean('is_reconciled')->default(false);
            $table->date('reconciled_date')->nullable();
            
            // ── Audit ─────────────────────────────────────────────────────────
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            // ── Indexes ───────────────────────────────────────────────────────
            $table->index(['company_id', 'financial_year_id', 'entry_date']);
            $table->index(['account_id', 'entry_date']);
            $table->index(['party_id', 'entry_date']);
            $table->index(['voucher_type', 'voucher_number']);
            $table->index(['document_type', 'document_id']);
            $table->index('entry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
