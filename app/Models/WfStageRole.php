<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WfStageRole extends Model
{
    protected $table = 'wf_stage_roles';

    protected $fillable = [
        'stage_id',
        'role_id',
        'can_view',
        'can_act',
        'is_notified',
    ];

    protected $casts = [
        'can_view'    => 'boolean',
        'can_act'     => 'boolean',
        'is_notified' => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function stage(): BelongsTo
    {
        return $this->belongsTo(WfStage::class, 'stage_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class, 'role_id');
    }
}
