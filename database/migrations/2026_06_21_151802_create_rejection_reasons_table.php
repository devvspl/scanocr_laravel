<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rejection_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('reason', 255);
            $table->string('module', 50)->default('bill_approval'); // bill_approval, temp_scan, etc.
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['module', 'is_active']);
        });

        // Seed common rejection reasons
        DB::table('rejection_reasons')->insert([
            ['reason' => 'Duplicate Bill', 'module' => 'bill_approval', 'is_active' => true, 'created_by' => null, 'created_at' => now(), 'updated_at' => now()],
            ['reason' => 'Incorrect Amount', 'module' => 'bill_approval', 'is_active' => true, 'created_by' => null, 'created_at' => now(), 'updated_at' => now()],
            ['reason' => 'Missing Supporting Documents', 'module' => 'bill_approval', 'is_active' => true, 'created_by' => null, 'created_at' => now(), 'updated_at' => now()],
            ['reason' => 'Wrong Vendor Details', 'module' => 'bill_approval', 'is_active' => true, 'created_by' => null, 'created_at' => now(), 'updated_at' => now()],
            ['reason' => 'Expired Bill Date', 'module' => 'bill_approval', 'is_active' => true, 'created_by' => null, 'created_at' => now(), 'updated_at' => now()],
            ['reason' => 'Unauthorized Purchase', 'module' => 'bill_approval', 'is_active' => true, 'created_by' => null, 'created_at' => now(), 'updated_at' => now()],
            ['reason' => 'Quality Issue', 'module' => 'bill_approval', 'is_active' => true, 'created_by' => null, 'created_at' => now(), 'updated_at' => now()],
            ['reason' => 'Budget Exceeded', 'module' => 'bill_approval', 'is_active' => true, 'created_by' => null, 'created_at' => now(), 'updated_at' => now()],
            ['reason' => 'Incorrect Bill Number', 'module' => 'bill_approval', 'is_active' => true, 'created_by' => null, 'created_at' => now(), 'updated_at' => now()],
            ['reason' => 'Other', 'module' => 'bill_approval', 'is_active' => true, 'created_by' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('rejection_reasons');
    }
};
