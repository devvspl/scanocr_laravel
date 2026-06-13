<?php

namespace App\Models\Import;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FieldMapping extends Model
{
    protected $table = 'imp_field_mappings';

    protected $fillable = [
        'company_id', 'name', 'data_type', 'source_type',
        'mapping', 'is_default', 'created_by'
    ];

    protected $casts = [
        'mapping' => 'array',
        'is_default' => 'boolean',
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
