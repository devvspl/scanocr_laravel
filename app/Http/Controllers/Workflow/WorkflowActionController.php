<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\WfStageAction;
use Illuminate\Http\Request;

class WorkflowActionController extends Controller
{
    // ── Toggle action active state ────────────────────────────────────────────

    public function toggle($workflowId, $actionId, Request $request)
    {
        $action = WfStageAction::whereHas('stage', function ($q) use ($workflowId) {
            $q->where('workflow_id', $workflowId);
        })->findOrFail($actionId);

        $action->update(['is_active' => !$action->is_active]);

        return response()->json([
            'success'   => true,
            'is_active' => $action->is_active,
        ]);
    }

    // ── Update action settings ────────────────────────────────────────────────

    public function update($workflowId, $actionId, Request $request)
    {
        $action = WfStageAction::whereHas('stage', function ($q) use ($workflowId) {
            $q->where('workflow_id', $workflowId);
        })->findOrFail($actionId);

        $request->validate([
            'display_label'         => ['nullable', 'string', 'max:200'],
            'button_style'          => ['nullable', 'in:primary,success,danger,warning,secondary'],
            'requires_remark'       => ['nullable', 'boolean'],
            'remark_label'          => ['nullable', 'string', 'max:100'],
            'confirm_before_action' => ['nullable', 'boolean'],
            'confirm_message'       => ['nullable', 'string', 'max:300'],
            'next_stage_key'        => ['nullable', 'string', 'max:50'],
        ]);

        $action->update(array_filter(
            $request->only([
                'display_label',
                'button_style',
                'requires_remark',
                'remark_label',
                'confirm_before_action',
                'confirm_message',
                'next_stage_key',
            ]),
            fn($v) => !is_null($v)
        ));

        return response()->json([
            'success' => true,
            'action'  => $action->fresh(),
        ]);
    }
}
