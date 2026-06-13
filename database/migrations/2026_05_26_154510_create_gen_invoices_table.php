<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('gen_invoices')) {
            Schema::create('gen_invoices', function (Blueprint $table) {
                $table->id();
            $table->string('invoice_no')->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('purchase_order_no')->nullable();
            $table->date('purchase_order_date')->nullable();
            $table->string('buyer')->nullable();
            $table->string('vendor')->nullable();
            $table->text('buyer_address')->nullable();
            $table->text('vendor_address')->nullable();
            $table->string('dispatch_through')->nullable();
            $table->date('dispatch_date')->nullable();
            $table->string('subtotal')->nullable();
            $table->decimal('additional_discount', 15, 2)->nullable()->default('0');
            $table->string('round_off')->nullable();
            $table->string('grand_total')->nullable();
            $table->string('invoice_summary')->nullable();
            $table->text('remark')->nullable();
            $table->string('auto_approve')->nullable()->default('no');
                $table->timestamps();
            });
        }
        Schema::create('gen_invoices_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gen_invoice_id')->constrained('gen_invoices')->onDelete('cascade');
            $table->string('particular')->nullable();
            $table->string('hsn')->nullable();
            $table->integer('qty')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('mrp', 15, 2)->nullable();
            $table->decimal('dis_flat', 15, 2)->nullable();
            $table->decimal('dis_pct', 15, 2)->nullable();
            $table->string('dis_on')->nullable();
            $table->string('amt')->nullable();
            $table->decimal('cgst_pct', 15, 2)->nullable();
            $table->decimal('sgst_pct', 15, 2)->nullable();
            $table->decimal('igst_pct', 15, 2)->nullable();
            $table->decimal('cess_pct', 15, 2)->nullable();
            $table->string('total_amt')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        // Drop repeater tables first
        Schema::dropIfExists('gen_invoices_line_items');
        Schema::dropIfExists('gen_invoices');
    }
};
