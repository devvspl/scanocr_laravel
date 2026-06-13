<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old items table first (FK dependency)
        Schema::dropIfExists('delivery_note_items');
        Schema::dropIfExists('delivery_notes');

        // ── Delivery Notes (header) ──────────────────────────────────────────
        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('financial_year_id')->constrained('financial_years')->onDelete('restrict');
            $table->string('delivery_number', 50)->unique();
            $table->date('dispatch_date');

            // Customer / Receiver
            $table->foreignId('party_id')->constrained('parties')->onDelete('restrict');
            $table->string('receiver_name', 200)->nullable();
            $table->string('receiver_phone', 30)->nullable();
            $table->text('delivery_address')->nullable();

            // Document references
            $table->string('order_number', 100)->nullable();  // PO / Order reference
            $table->foreignId('sale_invoice_id')->nullable()->constrained('sale_invoices')->nullOnDelete();
            $table->foreignId('proforma_invoice_id')->nullable()->constrained('proforma_invoices')->nullOnDelete();

            // Transport / Carrier details
            $table->string('transport_mode', 50)->nullable();       // Road, Rail, Air, Sea, Courier
            $table->string('transporter_name', 200)->nullable();
            $table->string('vehicle_number', 50)->nullable();
            $table->string('driver_name', 100)->nullable();
            $table->string('driver_phone', 30)->nullable();
            $table->string('tracking_number', 100)->nullable();     // AWB / LR / Docket no.
            $table->unsignedInteger('total_packages')->nullable();
            $table->string('total_weight', 50)->nullable();         // e.g. "25 Kg"

            // Workflow / Approval
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'cancelled', 'delivered'])->default('draft');
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

            // Sign-off / Proof of delivery
            $table->string('received_by', 200)->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('receiver_remarks')->nullable();

            // Notes
            $table->text('notes')->nullable();
            $table->text('narration')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'financial_year_id']);
            $table->index(['party_id']);
            $table->index(['status']);
            $table->index(['dispatch_date']);
        });

        // ── Delivery Note Items ──────────────────────────────────────────────
        Schema::create('delivery_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_note_id')->constrained('delivery_notes')->onDelete('cascade');

            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('description', 500);
            $table->string('product_code', 50)->nullable();
            $table->string('hsn_sac', 20)->nullable();

            $table->decimal('qty', 15, 3)->default(1);
            $table->string('unit', 20)->nullable();
            $table->string('weight', 50)->nullable();           // e.g. "5 Kg", "200 gm"
            $table->text('remarks')->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('delivery_note_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_note_items');
        Schema::dropIfExists('delivery_notes');
    }
};
