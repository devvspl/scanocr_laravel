<?php

namespace App\Models\Import;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImportTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'imp_import_templates';

    protected $fillable = [
        'company_id', 'name', 'data_type', 'source_type',
        'column_mapping', 'transform_rules', 'has_header_row',
        'delimiter', 'sheet_name', 'created_by', 'updated_by'
    ];

    protected $casts = [
        'column_mapping' => 'array',
        'transform_rules' => 'array',
        'has_header_row' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function jobs()
    {
        return $this->hasMany(ImportJob::class, 'template_id');
    }
}
