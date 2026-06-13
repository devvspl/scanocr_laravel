<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\WfRoutingRule;
use App\Models\WfWorkflow;
use Illuminate\Http\Request;

class WorkflowRoutingController extends Controller
{
    // ── List routing rules (JSON for AJAX) ────────────────────────────────────

    public function index($workflowId)
    {
        $workflow = WfWorkflow::findOrFail($workflowId);
        $rules    = WfRoutingRule::where('workflow_id', $workflowId)
            ->orderBy('priority')
            ->get();

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'rules' => $rules]);
        }

        return view('workflow.routing', compact('workflow', 'rules'));
    }

    // ── Create routing rule ───────────────────────────────────────────────────

    public function store($workflowId, Request $request)
    {
        WfWorkflow::findOrFail($workflowId);

        $data = $request->validate([
            'from_stage_key'     => ['required', 'string', 'max:50'],
            'action_key'         => ['required', 'string', 'max:50'],
            'condition_field'    => ['nullable', 'string', 'max:100'],
            'condition_operator' => ['nullable', 'in:gt,lt,eq,gte,lte,in,not_in,is_null,not_null'],
            'condition_value'    => ['nullable', 'string', 'max:200'],
            'to_stage_key'       => ['required', 'string', 'max:50'],
            'priority'           => ['nullable', 'integer', 'min:0'],
            'is_active'          => ['nullable', 'boolean'],
        ]);

        $data['workflow_id'] = $workflowId;
        $data['priority']    = $data['priority'] ?? 0;
        $data['is_active']   = $data['is_active'] ?? true;

        $rule = WfRoutingRule::create($data);

        return response()->json([
            'success' => true,
            'rule'    => $rule,
        ]);
    }

    // ── Update routing rule ───────────────────────────────────────────────────

    public function update($workflowId, $ruleId, Request $request)
    {
        $rule = WfRoutingRule::where('workflow_id', $workflowId)->findOrFail($ruleId);

        $data = $request->validate([
            'from_stage_key'     => ['nullable', 'string', 'max:50'],
            'action_key'         => ['nullable', 'string', 'max:50'],
            'condition_field'    => ['nullable', 'string', 'max:100'],
            'condition_operator' => ['nullable', 'in:gt,lt,eq,gte,lte,in,not_in,is_null,not_null'],
            'condition_value'    => ['nullable', 'string', 'max:200'],
            'to_stage_key'       => ['nullable', 'string', 'max:50'],
            'priority'           => ['nullable', 'integer', 'min:0'],
            'is_active'          => ['nullable', 'boolean'],
        ]);

        $rule->update(array_filter($data, fn($v) => !is_null($v)));

        return response()->json([
            'success' => true,
            'rule'    => $rule->fresh(),
        ]);
    }

    // ── Delete routing rule ───────────────────────────────────────────────────

    public function destroy($workflowId, $ruleId)
    {
        $rule = WfRoutingRule::where('workflow_id', $workflowId)->findOrFail($ruleId);
        $rule->delete();

        return response()->json(['success' => true]);
    }
}
