<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::table('gen_scanners', function (Blueprint $table) {
            $table->string('title')->nullable();
            $table->string('document_no')->nullable();
            $table->dateTime('document_date')->nullable();
            $table->string('document_type')->nullable();
            $table->text('remarks')->nullable();
            $table->json('upload_scan_copy')->nullable();
            $table->dropColumn('f_ile_name');
        });
    }
    public function down(): void
    {
        Schema::table('gen_scanners', function (Blueprint $table) {
            $table->string('f_ile_name')->nullable();
            $table->dropColumn('title');
            $table->dropColumn('document_no');
            $table->dropColumn('document_date');
            $table->dropColumn('document_type');
            $table->dropColumn('remarks');
            $table->dropColumn('upload_scan_copy');
        });
    }
};
