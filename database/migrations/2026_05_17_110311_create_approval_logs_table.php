<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_logs', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 50);
            $table->unsignedBigInteger('document_id'); // polymorphic-style: sale_invoices.id, etc.
            $table->unsignedTinyInteger('level'); // 1, 2, 3
            $table->string('level_name', 100)->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('action', ['approved', 'rejected', 'escalated', 'pending']);
            $table->text('remarks')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->index(['document_type', 'document_id']);
            $table->index(['user_id', 'action']);
        });

        // Add current_approval_level to sale_invoices
        Schema::table('sale_invoices', function (Blueprint $table) {
            $table->unsignedTinyInteger('current_approval_level')->default(0)->after('status');
            $table->unsignedTinyInteger('max_approval_level')->default(0)->after('current_approval_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_logs');

        Schema::table('sale_invoices', function (Blueprint $table) {
            $table->dropColumn(['current_approval_level', 'max_approval_level']);
        });
    }
};
