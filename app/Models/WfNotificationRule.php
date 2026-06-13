<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WfNotificationRule extends Model
{
    protected $table = 'wf_notification_rules';

    protected $fillable = [
        'stage_id',
        'trigger_event',
        'notify_uploader',
        'notify_assigned_roles',
        'message_template',
        'is_active',
    ];

    protected $casts = [
        'is_active'             => 'boolean',
        'notify_uploader'       => 'boolean',
        'notify_assigned_roles' => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function stage(): BelongsTo
    {
        return $this->belongsTo(WfStage::class, 'stage_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            \Spatie\Permission\Models\Role::class,
            'wf_notification_rule_roles',
            'notification_rule_id',
            'role_id'
        );
    }
}
