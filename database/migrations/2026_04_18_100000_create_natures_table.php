<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('natures', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color')->default('blue');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default natures
        DB::table('natures')->insert([
            ['name' => 'Assets', 'slug' => 'assets', 'color' => 'blue', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Liabilities', 'slug' => 'liabilities', 'color' => 'orange', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Income', 'slug' => 'income', 'color' => 'green', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Expense', 'slug' => 'expense', 'color' => 'red', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('natures');
    }
};
