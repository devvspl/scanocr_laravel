<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('imp_import_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('name');                         // e.g. "Customer Import Template"
            $table->string('data_type');                    // customers, vendors, products, accounts, etc.
            $table->string('source_type');                  // excel, csv, sql, api
            $table->json('column_mapping')->nullable();     // user-defined column map
            $table->json('transform_rules')->nullable();    // custom field transformations
            $table->boolean('has_header_row')->default(true);
            $table->string('delimiter')->default(',');      // for CSV
            $table->string('sheet_name')->nullable();       // for Excel
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imp_import_templates');
    }
};
