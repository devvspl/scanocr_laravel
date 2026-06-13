<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WfWorkflow extends Model
{
    protected $table = 'wf_workflows';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'doc_type_id',
        'is_active',
        'is_default',
        'version',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_default' => 'boolean',
    ];

    // ── Boot — auto-slug and audit columns ───────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name) . '-' . time();
            }
            $model->created_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function stages(): HasMany
    {
        return $this->hasMany(WfStage::class, 'workflow_id')->whereNull('parent_stage_id')->orderBy('position');
    }

    public function activeStages(): HasMany
    {
        return $this->hasMany(WfStage::class, 'workflow_id')
            ->where('is_active', true)
            ->orderBy('position');
    }

    public function routingRules(): HasMany
    {
        return $this->hasMany(WfRoutingRule::class, 'workflow_id')->orderBy('priority');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WfWorkflowLog::class, 'workflow_id')->latest('performed_at');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function docType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'doc_type_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function stagesCount(): int
    {
        return $this->stages()->where('is_active', true)->count();
    }
}
