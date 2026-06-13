<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WfEntry extends Model
{
    protected $table = 'wf_entries';

    protected $fillable = [
        'workflow_id',
        'current_stage_id',
        'status',
        'form_data',
        'files',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'form_data' => 'array',
        'files'     => 'array',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(WfWorkflow::class, 'workflow_id');
    }

    public function currentStage(): BelongsTo
    {
        return $this->belongsTo(WfStage::class, 'current_stage_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
