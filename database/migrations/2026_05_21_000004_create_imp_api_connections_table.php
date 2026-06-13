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
        Schema::create('imp_api_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('name');                         // "Tally Sync", "Shopify Orders", etc.
            $table->string('provider');                     // tally, custom, rest, graphql
            $table->string('base_url');
            $table->string('auth_type')->default('none');   // none, api_key, bearer, basic, oauth2
            $table->json('auth_config')->nullable();        // encrypted credentials
            $table->json('headers')->nullable();
            $table->string('data_type');                    // what this connection imports
            $table->json('field_mapping')->nullable();
            $table->string('sync_frequency')->default('manual'); // manual, hourly, daily, weekly
            $table->timestamp('last_synced_at')->nullable();
            $table->string('last_sync_status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imp_api_connections');
    }
};
