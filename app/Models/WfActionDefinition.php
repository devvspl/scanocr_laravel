<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WfActionDefinition extends Model
{
    protected $table = 'wf_action_definitions';

    protected $fillable = [
        'group',
        'action_key',
        'display_label',
        'icon',
        'button_style',
        'button_color',
        'logic_type',
        'logic_config',
        'requires_remark',
        'requires_confirmation',
        'confirm_message',
        'is_system',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'logic_config'          => 'array',
        'requires_remark'       => 'boolean',
        'requires_confirmation' => 'boolean',
        'is_system'             => 'boolean',
        'is_active'             => 'boolean',
    ];

    // ── Logic Types ──────────────────────────────────────────────────────────

    const LOGIC_STATUS_CHANGE  = 'status_change';
    const LOGIC_STAGE_MOVE     = 'stage_move';
    const LOGIC_API_CALL       = 'api_call';
    const LOGIC_NOTIFICATION   = 'notification';
    const LOGIC_FILE_OPERATION = 'file_operation';
    const LOGIC_LEDGER_POST    = 'ledger_post';
    const LOGIC_VALIDATION     = 'validation';
    const LOGIC_EXPORT         = 'export';

    // ── Groups ───────────────────────────────────────────────────────────────

    const GROUP_ENTRY       = 'Entry Level';
    const GROUP_VERIFICATION = 'Verification / Punching';
    const GROUP_APPROVAL    = 'Approval Workflow';
    const GROUP_STATUS      = 'Status Actions';
    const GROUP_AUDIT       = 'Audit / Tracking';
    const GROUP_DMS         = 'DMS / Document';
    const GROUP_FINANCE     = 'Finance-Specific';

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }
}
