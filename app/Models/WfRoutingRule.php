<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WfRoutingRule extends Model
{
    protected $table = 'wf_routing_rules';

    protected $fillable = [
        'workflow_id',
        'from_stage_key',
        'action_key',
        'condition_field',
        'condition_operator',
        'condition_value',
        'to_stage_key',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(WfWorkflow::class, 'workflow_id');
    }

    // ── Evaluate condition against a document data array ─────────────────────

    public function evaluates(array $documentData): bool
    {
        if (empty($this->condition_field)) {
            return true; // no condition = always matches
        }

        $fieldValue = data_get($documentData, $this->condition_field);
        $ruleValue  = $this->condition_value;

        return match ($this->condition_operator) {
            'eq'       => $fieldValue == $ruleValue,
            'gt'       => $fieldValue >  $ruleValue,
            'lt'       => $fieldValue <  $ruleValue,
            'gte'      => $fieldValue >= $ruleValue,
            'lte'      => $fieldValue <= $ruleValue,
            'in'       => in_array($fieldValue, explode(',', $ruleValue)),
            'not_in'   => !in_array($fieldValue, explode(',', $ruleValue)),
            'is_null'  => is_null($fieldValue),
            'not_null' => !is_null($fieldValue),
            default    => false,
        };
    }
}
