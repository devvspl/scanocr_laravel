<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WfLayoutWidget extends Model
{
    protected $table = 'wf_layout_widgets';

    protected $fillable = [
        'stage_id',
        'widget_type',
        'title',
        'position',
        'col_span',
        'config',
        'is_active',
    ];

    protected $casts = [
        'config'    => 'array',
        'is_active' => 'boolean',
    ];

    const TYPE_COUNTER       = 'counter';
    const TYPE_CHART         = 'chart';
    const TYPE_TABLE         = 'table';
    const TYPE_ENTRY_FORM    = 'entry_form';
    const TYPE_FILE_UPLOAD   = 'file_upload';
    const TYPE_RECENT_ENTRIES = 'recent_entries';

    const TYPES = [
        self::TYPE_COUNTER       => 'Counter Card',
        self::TYPE_CHART         => 'Chart',
        self::TYPE_TABLE         => 'Data Table',
        self::TYPE_ENTRY_FORM    => 'Entry Form',
        self::TYPE_FILE_UPLOAD   => 'File Upload',
        self::TYPE_RECENT_ENTRIES => 'Recent Entries',
    ];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(WfStage::class, 'stage_id');
    }
}
