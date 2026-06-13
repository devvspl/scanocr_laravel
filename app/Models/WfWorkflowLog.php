<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WfWorkflowLog extends Model
{
    protected $table = 'wf_workflow_logs';

    // Read-only — disable update/delete at model level
    public static bool $readOnly = true;

    // No auto updated_at — this table is append-only
    const UPDATED_AT = null;

    protected $fillable = [
        'workflow_id',
        'stage_id',
        'system_key',
        'action_key',
        'document_ref',
        'doc_type_id',
        'performed_by',
        'from_stage_key',
        'to_stage_key',
        'remark',
        'metadata',
        'performed_at',
    ];

    protected $casts = [
        'metadata'     => 'array',
        'performed_at' => 'datetime',
    ];

    // ── Boot — enforce read-only ──────────────────────────────────────────────

    protected static function booted(): void
    {
        static::updating(fn() => throw new \LogicException('WfWorkflowLog is read-only and cannot be updated.'));
        static::deleting(fn() => throw new \LogicException('WfWorkflowLog is read-only and cannot be deleted.'));
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(WfWorkflow::class, 'workflow_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    // ── Static helper to write a log entry ───────────────────────────────────

    public static function record(array $data): self
    {
        return static::create(array_merge($data, [
            'performed_by' => $data['performed_by'] ?? auth()->id(),
            'performed_at' => now(),
        ]));
    }
}
