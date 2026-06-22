<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueItem extends Model
{
    protected $table = 'tbl_queues';

    protected $fillable = [
        'scan_id',
        'type_id',
        'status',
        'created_by',
    ];

    public function scan(): BelongsTo
    {
        return $this->belongsTo(ScanFile::class, 'scan_id', 'Scan_Id');
    }

    public function doctype(): BelongsTo
    {
        return $this->belongsTo(MasterDoctype::class, 'type_id', 'type_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
