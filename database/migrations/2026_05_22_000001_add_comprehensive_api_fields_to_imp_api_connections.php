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
        Schema::table('imp_api_connections', function (Blueprint $table) {
            // API Configuration
            $table->string('api_type')->default('rest')->after('provider'); // rest, graphql, soap, json-rpc, webhook, custom
            $table->string('http_method')->default('GET')->after('api_type'); // GET, POST, PUT, PATCH, DELETE
            $table->text('endpoint')->nullable()->after('base_url'); // Full endpoint URL
            
            // Request Configuration
            $table->json('query_params')->nullable()->after('headers'); // URL query parameters
            $table->text('request_body')->nullable()->after('query_params'); // Request body for POST/PUT/PATCH
            
            // Response Configuration
            $table->string('response_format')->default('json')->after('request_body'); // json, xml, csv, text
            $table->string('data_path')->nullable()->after('response_format'); // Path to data array in response
            
            // Pagination Configuration
            $table->string('pagination_type')->default('none')->after('data_path'); // none, offset, page, cursor, link
            $table->json('pagination_config')->nullable()->after('pagination_type'); // Pagination parameters
            
            // Advanced Options
            $table->integer('timeout')->default(30)->after('pagination_config'); // Request timeout in seconds
            $table->boolean('verify_ssl')->default(true)->after('timeout'); // SSL certificate verification
            
            // Target Table
            $table->string('target_table')->nullable()->after('data_type'); // Table to import data into
            $table->boolean('create_table')->default(false)->after('target_table'); // Whether to create new table
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('imp_api_connections', function (Blueprint $table) {
            $table->dropColumn([
                'api_type',
                'http_method',
                'endpoint',
                'query_params',
                'request_body',
                'response_format',
                'data_path',
                'pagination_type',
                'pagination_config',
                'timeout',
                'verify_ssl',
                'target_table',
                'create_table',
            ]);
        });
    }
};
