<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('gen_scanners')) {
            Schema::create('gen_scanners', function (Blueprint $table) {
                $table->id();
            $table->json('upload_document')->nullable();
                $table->timestamps();
            });
        }
    }
    public function down(): void {
        // Drop repeater tables first
        Schema::dropIfExists('gen_scanners');
    }
};
