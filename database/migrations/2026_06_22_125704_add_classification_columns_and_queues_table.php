<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add is_autoclassified to scan_file
        if (!Schema::hasColumn('scan_file', 'is_autoclassified')) {
            Schema::table('scan_file', function (Blueprint $table) {
                $table->char('is_autoclassified', 1)->default('N')->after('extract_status');
            });
        }

        // Add is_punch to master_doctype
        if (Schema::hasTable('master_doctype') && !Schema::hasColumn('master_doctype', 'is_punch')) {
            Schema::table('master_doctype', function (Blueprint $table) {
                $table->char('is_punch', 1)->default('N')->after('status');
            });
        }
    }

    public function down(): void
    {
        Schema::table('scan_file', function (Blueprint $table) {
            if (Schema::hasColumn('scan_file', 'is_autoclassified')) {
                $table->dropColumn('is_autoclassified');
            }
        });

        if (Schema::hasTable('master_doctype') && Schema::hasColumn('master_doctype', 'is_punch')) {
            Schema::table('master_doctype', function (Blueprint $table) {
                $table->dropColumn('is_punch');
            });
        }
    }
};
