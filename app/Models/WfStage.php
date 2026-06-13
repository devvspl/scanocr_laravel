<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WfStage extends Model
{
    protected $table = 'wf_stages';

    const SYSTEM_KEYS = [
        'scanner',
        'extraction',
        'doc_classifier',
        'dms_punching',
        'punching_approval',
        'bill_approver',
        'finance_punching',
        'punch_approver',
        'focus_export',
    ];

    const DEFAULT_CONFIGS = [
        'scanner' => [
            'allow_supporting_files' => true,
            'max_file_size_mb'       => 10,
            'allowed_extensions'     => ['pdf', 'jpg', 'png'],
            'auto_final_submit'      => false,
            'extraction_enabled'     => false,
        ],
        'extraction' => [
            'api_endpoint'   => '',
            'api_key_header' => '',
            'api_key_value'  => '',
            'timeout_seconds' => 30,
            'on_failure'     => 'skip',
        ],
        'doc_classifier' => [
            'require_document_received_date' => true,
            'allow_reclassify'               => true,
            'auto_classify'                  => false,
        ],
        'dms_punching' => [
            'allow_skip_bill_approval'           => false,
            'skip_bill_approval_reason_required' => true,
            'allow_repunch'                      => true,
        ],
        'punching_approval' => [
            'approval_levels' => 1,
            'level_1_role_id' => null,
            'level_2_role_id' => null,
            'level_3_role_id' => null,
            'bypass_enabled'  => false,
            'bypass_role_id'  => null,
        ],
        'bill_approver' => [
            'max_levels'    => 3,
            'level_configs' => [
                ['level' => 1, 'role_id' => null, 'amount_limit' => null],
                ['level' => 2, 'role_id' => null, 'amount_limit' => null],
                ['level' => 3, 'role_id' => null, 'amount_limit' => null],
            ],
            'rejection_returns_to' => 'finance_punching',
        ],
        'finance_punching' => [
            'allow_additional_punch' => true,
            'rejection_returns_to'   => 'bill_approver',
            'allow_repunch'          => true,
        ],
        'punch_approver' => [
            'role_id'              => null,
            'rejection_returns_to' => 'finance_punching',
        ],
        'focus_export' => [
            'export_format'  => 'csv',
            'auto_export'    => false,
            'export_trigger' => 'approved',
        ],
    ];

    protected $fillable = [
        'workflow_id',
        'parent_stage_id',
        'system_key',
        'display_name',
        'position',
        'is_active',
        'is_optional',
        'icon',
        'color',
        'description',
        'config',
        'page_id',
        'layout_template',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'is_optional' => 'boolean',
        'config'      => 'array',
    ];

    // ── Config accessor — merges stored values with defaults ─────────────────

    public function getConfigAttribute($value): array
    {
        $raw      = $value ? json_decode($value, true) : [];
        $defaults = self::DEFAULT_CONFIGS[$this->system_key] ?? [];

        // Ensure we always return an associative array (object), never a list
        $merged = array_merge($defaults, is_array($raw) && !array_is_list($raw) ? $raw : []);

        return $merged;
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(WfWorkflow::class, 'workflow_id');
    }

    public function parentStage(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_stage_id');
    }

    public function subStages(): HasMany
    {
        return $this->hasMany(self::class, 'parent_stage_id')->orderBy('position');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(WfStageAction::class, 'stage_id')->orderBy('position');
    }

    public function actionMap(): HasMany
    {
        return $this->hasMany(WfStageActionMap::class, 'stage_id')->orderBy('position');
    }

    public function activeActions(): HasMany
    {
        return $this->hasMany(WfStageAction::class, 'stage_id')
            ->where('is_active', true)
            ->orderBy('position');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(WfStageRole::class, 'stage_id');
    }

    public function notificationRules(): HasMany
    {
        return $this->hasMany(WfNotificationRule::class, 'stage_id');
    }

    public function dashboardWidgets(): HasMany
    {
        return $this->hasMany(WfLayoutWidget::class, 'stage_id')->orderBy('position');
    }
}
