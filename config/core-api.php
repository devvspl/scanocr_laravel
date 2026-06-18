<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Core API Base URL
    |--------------------------------------------------------------------------
    | The base URL for the central core server API.
    | Example: https://core.vnrin.in/api
    */
    'base_url' => env('CORE_API_BASE_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Core API Key
    |--------------------------------------------------------------------------
    | The API key sent as the "api-key" header on every request.
    */
    'api_key' => env('CORE_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Local Table Prefix
    |--------------------------------------------------------------------------
    | All dynamic tables created by the Core API Sync module will be prefixed
    | with this value to avoid collisions with existing application tables.
    | Example: "core_" → "core_regions", "core_departments"
    */
    'table_prefix' => env('CORE_API_TABLE_PREFIX', 'core_'),

];
