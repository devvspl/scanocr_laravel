<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            // ── Identity ──────────────────────────────────────────────────────
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('display_name')->nullable();
            $table->string('code', 20)->nullable()->unique();   // short code / alias
            $table->enum('type', ['private_limited', 'public_limited', 'llp', 'partnership', 'proprietorship', 'trust', 'ngo', 'other'])->default('private_limited');
            $table->string('industry')->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('fax', 20)->nullable();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();                 // file path

            // ── Address ───────────────────────────────────────────────────────
            $table->text('address_line1')->nullable();
            $table->text('address_line2')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->default('India');
            $table->string('pincode', 20)->nullable();

            // ── Tax & Compliance ──────────────────────────────────────────────
            $table->string('gstin', 20)->nullable();
            $table->string('pan', 20)->nullable();
            $table->string('tan', 20)->nullable();
            $table->string('cin', 30)->nullable();              // Company Identification Number
            $table->string('msme_number', 30)->nullable();
            $table->enum('gst_registration_type', ['regular', 'composition', 'unregistered', 'sez', 'overseas'])->default('regular');
            $table->date('gst_registration_date')->nullable();

            // ── Bank Details ──────────────────────────────────────────────────
            $table->string('bank_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('bank_account_number', 30)->nullable();
            $table->string('bank_ifsc', 15)->nullable();
            $table->string('bank_swift', 15)->nullable();
            $table->string('bank_account_type', 30)->nullable();

            // ── Financial Year ────────────────────────────────────────────────
            $table->enum('fy_start_month', ['01','02','03','04','05','06','07','08','09','10','11','12'])->default('04');
            $table->string('currency_code', 10)->default('INR');
            $table->string('currency_symbol', 5)->default('₹');
            $table->enum('date_format', ['DD/MM/YYYY', 'MM/DD/YYYY', 'YYYY-MM-DD'])->default('DD/MM/YYYY');
            $table->string('timezone')->default('Asia/Kolkata');

            // ── Status ────────────────────────────────────────────────────────
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('is_active');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
