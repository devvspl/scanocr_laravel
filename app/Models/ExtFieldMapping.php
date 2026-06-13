<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtFieldMapping extends Model
{
    protected $table = 'ext_field_mappings';

    protected $fillable = [
        'doctype_id',
        'temp_column',
        'input_type',
        'select_table',
        'relation_column',
        'relation_value',
        'punch_table',
        'punch_column',
        'has_Items_feild',
        'add_condition',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class, 'doctype_id');
    }

    public function apiControl()
    {
        return $this->belongsTo(ExtMasterApiControl::class, 'doctype_id', 'doctype_id');
    }
}
