<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::table('gen_scanners', function (Blueprint $table) {
            $table->string('f_ile_name')->nullable();
            $table->string('file_type')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('gen_scanners', function (Blueprint $table) {
            $table->dropColumn('f_ile_name');
            $table->dropColumn('file_type');
        });
    }
};
