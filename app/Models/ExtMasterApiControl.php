<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtMasterApiControl extends Model
{
    protected $table = 'ext_mater_api_control';

    // Table uses `created` and `updated` — not standard Laravel timestamps
    public $timestamps = false;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'updated';

    protected $fillable = [
        'doctype_id',
        'endpoint',
        'description',
        'status',
    ];

    protected $casts = [
        'status'  => 'integer',
        'created' => 'datetime',
        'updated' => 'datetime',
    ];

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class, 'doctype_id');
    }

    public function fieldMappings()
    {
        return $this->hasMany(ExtFieldMapping::class, 'doctype_id', 'doctype_id');
    }
}
