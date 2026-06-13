<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Store signature on each approval action
        Schema::table('approval_logs', function (Blueprint $table) {
            $table->string('signature_path', 500)->nullable()->after('remarks');
            $table->string('ip_address', 45)->nullable()->after('signature_path');
            $table->string('user_agent', 500)->nullable()->after('ip_address');
            $table->timestamp('signed_at')->nullable()->after('user_agent');
        });

        // Store user's default/uploaded signature
        Schema::table('users', function (Blueprint $table) {
            $table->string('signature_path', 500)->nullable()->after('department');
        });
    }

    public function down(): void
    {
        Schema::table('approval_logs', function (Blueprint $table) {
            $table->dropColumn(['signature_path', 'ip_address', 'user_agent', 'signed_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('signature_path');
        });
    }
};
