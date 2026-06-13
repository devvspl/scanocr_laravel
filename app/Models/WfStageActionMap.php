<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WfStageActionMap extends Model
{
    protected $table = 'wf_stage_action_map';

    protected $fillable = [
        'stage_id',
        'action_definition_id',
        'position',
        'is_active',
        'notify_enabled',
        'notify_medium',
        'notify_recipients',
        'notify_user_ids',
        'notify_frequency',
        'escalation_hours',
        'notify_next_stage',
        'email_template_id',
    ];

    protected $casts = [
        'is_active'         => 'boolean',
        'notify_enabled'    => 'boolean',
        'notify_recipients' => 'array',
        'notify_user_ids'   => 'array',
        'notify_next_stage' => 'boolean',
    ];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(WfStage::class, 'stage_id');
    }

    public function actionDefinition(): BelongsTo
    {
        return $this->belongsTo(WfActionDefinition::class, 'action_definition_id');
    }

    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(WfEmailTemplate::class, 'email_template_id');
    }
}
