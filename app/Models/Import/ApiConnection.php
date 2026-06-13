<?php

namespace App\Models\Import;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApiConnection extends Model
{
    use SoftDeletes;

    protected $table = 'imp_api_connections';

    protected $fillable = [
        'company_id', 'name', 'provider', 'api_type', 'http_method',
        'base_url', 'endpoint', 'auth_type', 'auth_config', 'headers',
        'query_params', 'request_body', 'response_format', 'data_path',
        'pagination_type', 'pagination_config', 'timeout', 'verify_ssl',
        'data_type', 'target_table', 'create_table', 'field_mapping',
        'sync_frequency', 'last_synced_at', 'last_sync_status',
        'is_active', 'created_by'
    ];

    protected $casts = [
        'auth_config' => 'array',           // store as plain JSON (encrypted fields break json_valid constraint)
        'headers' => 'array',
        'query_params' => 'array',
        'pagination_config' => 'array',
        'field_mapping' => 'array',
        'is_active' => 'boolean',
        'verify_ssl' => 'boolean',
        'create_table' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
