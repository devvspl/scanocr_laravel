<?php

namespace App\Models\Import;

use Illuminate\Database\Eloquent\Model;

class ImportRow extends Model
{
    protected $table = 'imp_import_rows';

    protected $fillable = [
        'import_job_id', 'row_number', 'raw_data', 'mapped_data',
        'status', 'error_message', 'entity_id', 'action_taken'
    ];

    protected $casts = [
        'raw_data' => 'array',
        'mapped_data' => 'array',
    ];

    public function job()
    {
        return $this->belongsTo(ImportJob::class, 'import_job_id');
    }
}
