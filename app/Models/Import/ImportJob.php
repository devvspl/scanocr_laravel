<?php

namespace App\Models\Import;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ImportJob extends Model
{
    protected $table = 'imp_import_jobs';

    protected $fillable = [
        'company_id', 'template_id', 'job_uuid', 'data_type',
        'source_type', 'source_identifier', 'status',
        'total_rows', 'processed_rows', 'success_rows',
        'failed_rows', 'skipped_rows', 'options', 'column_aliases',
        'notes', 'started_at', 'completed_at', 'created_by'
    ];

    protected $casts = [
        'options' => 'array',
        'column_aliases' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function rows()
    {
        return $this->hasMany(ImportRow::class, 'import_job_id');
    }

    public function template()
    {
        return $this->belongsTo(ImportTemplate::class, 'template_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getProgressPercentAttribute(): int
    {
        if ($this->total_rows === 0) return 0;
        return (int) round(($this->processed_rows / $this->total_rows) * 100);
    }
}
