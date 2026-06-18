<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreApiSyncLog extends Model
{
    protected $table = 'core_api_sync_logs';

    public $timestamps = false;

    const CREATED_AT = 'created';

    protected $fillable = [
        'core_api_list_id',
        'api_end_point',
        'params_used',
        'table_name',
        'added',
        'updated',
        'removed',
        'status',
        'message',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'core_api_list_id' => 'integer',
        'added'            => 'integer',
        'updated'          => 'integer',
        'removed'          => 'integer',
        'started_at'       => 'datetime',
        'ended_at'         => 'datetime',
        'created'          => 'datetime',
    ];

    public function coreApi()
    {
        return $this->belongsTo(CoreApiList::class, 'core_api_list_id');
    }
}
