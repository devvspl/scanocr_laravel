<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreApiList extends Model
{
    protected $table = 'core_api_list';

    public $timestamps = false;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'updated';

    protected $fillable = [
        'remote_id',
        'api_end_point',
        'table_name',
        'description',
        'parameters',
        'sync_status',
        'last_synced_at',
    ];

    protected $casts = [
        'remote_id'      => 'integer',
        'last_synced_at' => 'datetime',
        'created'        => 'datetime',
        'updated'        => 'datetime',
    ];

    public function syncLogs()
    {
        return $this->hasMany(CoreApiSyncLog::class, 'core_api_list_id');
    }
}
