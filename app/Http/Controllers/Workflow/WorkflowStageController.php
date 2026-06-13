<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\WfStage;
use App\Models\WfStageRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkflowStageController extends Controller
{
    // ── Update stage settings ─────────────────────────────────────────────────

    public function update($workflowId, $stageId, Request $request)
    {
        $stage = WfStage::where('workflow_id', $workflowId)->findOrFail($stageId);

        $request->validate([
            'display_name'    => ['nullable', 'string', 'max:200'],
            'is_active'       => ['nullable', 'boolean'],
            'is_optional'     => ['nullable', 'boolean'],
            'color'           => ['nullable', 'string', 'max:20'],
            'icon'            => ['nullable', 'string', 'max:100'],
            'page_id'         => ['nullable', 'integer', 'exists:pages,id'],
            'layout_template' => ['nullable', 'string', 'in:form_sidebar,split_panel,full_dashboard'],
            'config'          => ['nullable', 'array'],
        ]);

        // Update scalar fields (filter out nulls except page_id which can be explicitly null)
        $fields = array_filter(
            $request->only(['display_name', 'is_active', 'is_optional', 'color', 'icon', 'layout_template']),
            fn($v) => !is_null($v)
        );

        // page_id can be explicitly set to null (unlink form)
        if ($request->has('page_id')) {
            $fields['page_id'] = $request->input('page_id');
        }

        if (!empty($fields)) {
            $stage->update($fields);
        }

        // Merge config — preserve existing keys not in the request
        if ($request->has('config')) {
            $currentConfig = $stage->getRawOriginal('config')
                ? json_decode($stage->getRawOriginal('config'), true)
                : [];
            $merged = array_merge($currentConfig ?? [], $request->input('config'));
            $stage->update(['config' => $merged]);
        }

        return response()->json([
            'success' => true,
            'stage'   => $stage->fresh(),
        ]);
    }

    // ── Reorder stages ────────────────────────────────────────────────────────

    public function reorder($workflowId, Request $request)
    {
        $request->validate([
            'order'            => ['required', 'array'],
            'order.*.id'       => ['required', 'integer'],
            'order.*.position' => ['required', 'integer'],
        ]);

        DB::transaction(function () use ($workflowId, $request) {
            foreach ($request->input('order') as $item) {
                WfStage::where('workflow_id', $workflowId)
                    ->where('id', $item['id'])
                    ->update(['position' => $item['position']]);
            }
        });

        return response()->json(['success' => true]);
    }

    // ── Sync roles for a stage ────────────────────────────────────────────────

    public function roles($workflowId, $stageId, Request $request)
    {
        $stage = WfStage::where('workflow_id', $workflowId)->findOrFail($stageId);

        $request->validate([
            'roles'              => ['nullable', 'array'],
            'roles.*.role_id'    => ['required', 'integer'],
            'roles.*.can_view'   => ['nullable', 'boolean'],
            'roles.*.can_act'    => ['nullable', 'boolean'],
            'roles.*.is_notified'=> ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($stage, $request) {
            WfStageRole::where('stage_id', $stage->id)->delete();

            foreach ($request->input('roles', []) as $roleData) {
                WfStageRole::create([
                    'stage_id'    => $stage->id,
                    'role_id'     => $roleData['role_id'],
                    'can_view'    => $roleData['can_view']    ?? true,
                    'can_act'     => $roleData['can_act']     ?? true,
                    'is_notified' => $roleData['is_notified'] ?? true,
                ]);
            }
        });

        $stage->load('roles.role');

        return response()->json([
            'success' => true,
            'roles'   => $stage->roles,
        ]);
    }

    // ── Sync actions for a stage ──────────────────────────────────────────────

    public function syncActions($workflowId, $stageId, Request $request)
    {
        $stage = WfStage::where('workflow_id', $workflowId)->findOrFail($stageId);

        $request->validate([
            'action_keys'   => ['nullable', 'array'],
            'action_keys.*' => ['string', 'max:100'],
        ]);

        $actionKeys = $request->input('action_keys', []);

        DB::transaction(function () use ($stage, $actionKeys) {
            // Remove existing mappings
            \App\Models\WfStageActionMap::where('stage_id', $stage->id)->delete();

            // Re-create from definitions
            foreach ($actionKeys as $pos => $key) {
                $def = \App\Models\WfActionDefinition::where('action_key', $key)->first();
                if ($def) {
                    \App\Models\WfStageActionMap::create([
                        'stage_id'             => $stage->id,
                        'action_definition_id' => $def->id,
                        'position'             => $pos + 1,
                        'is_active'            => true,
                    ]);
                }
            }
        });

        $stage->load('actionMap.actionDefinition');

        return response()->json([
            'success' => true,
            'actions' => $stage->actionMap->map(fn($m) => [
                'id'              => $m->id,
                'action_key'      => $m->actionDefinition->action_key,
                'display_label'   => $m->actionDefinition->display_label,
                'button_style'    => $m->actionDefinition->button_style,
                'logic_type'      => $m->actionDefinition->logic_type,
                'is_active'       => $m->is_active,
                'notify_enabled'  => $m->notify_enabled,
                'notify_medium'   => $m->notify_medium,
                'notify_recipients' => $m->notify_recipients,
                'notify_frequency'  => $m->notify_frequency,
                'escalation_hours'  => $m->escalation_hours,
                'notify_next_stage' => $m->notify_next_stage,
            ])->values(),
        ]);
    }

    // ── Update individual action notification config ──────────────────────────

    public function updateActionConfig($workflowId, $mapId, Request $request)
    {
        $map = \App\Models\WfStageActionMap::whereHas('stage', fn($q) => $q->where('workflow_id', $workflowId))
            ->findOrFail($mapId);

        $request->validate([
            'notify_enabled'    => ['nullable', 'boolean'],
            'notify_medium'     => ['nullable', 'string', 'in:email,sms,slack,teams,webhook'],
            'notify_recipients' => ['nullable', 'array'],
            'notify_user_ids'   => ['nullable', 'array'],
            'notify_frequency'  => ['nullable', 'string', 'in:once,daily,on_escalation'],
            'escalation_hours'  => ['nullable', 'integer', 'min:1', 'max:720'],
            'notify_next_stage' => ['nullable', 'boolean'],
            'email_template_id' => ['nullable', 'integer', 'exists:wf_email_templates,id'],
        ]);

        $map->update($request->only([
            'notify_enabled',
            'notify_medium',
            'notify_recipients',
            'notify_user_ids',
            'notify_frequency',
            'escalation_hours',
            'notify_next_stage',
            'email_template_id',
        ]));

        return response()->json([
            'success' => true,
            'map'     => $map->fresh(),
        ]);
    }

    // ── Get dashboard widgets for a stage ─────────────────────────────────────

    public function getWidgets($workflowId, $stageId)
    {
        $stage = WfStage::where('workflow_id', $workflowId)->findOrFail($stageId);

        return response()->json([
            'success' => true,
            'widgets' => $stage->dashboardWidgets,
        ]);
    }

    // ── Save dashboard widgets for a stage ────────────────────────────────────

    public function saveWidgets($workflowId, $stageId, Request $request)
    {
        $stage = WfStage::where('workflow_id', $workflowId)->findOrFail($stageId);

        $request->validate([
            'widgets'              => ['present', 'array'],
            'widgets.*.widget_type'=> ['required', 'string', 'in:counter,chart,table,entry_form,file_upload,recent_entries'],
            'widgets.*.title'      => ['required', 'string', 'max:200'],
            'widgets.*.col_span'   => ['nullable', 'integer', 'min:1', 'max:3'],
            'widgets.*.config'     => ['nullable', 'array'],
        ]);

        DB::transaction(function () use ($stage, $request) {
            \App\Models\WfLayoutWidget::where('stage_id', $stage->id)->delete();

            foreach ($request->input('widgets') as $pos => $widget) {
                \App\Models\WfLayoutWidget::create([
                    'stage_id'    => $stage->id,
                    'widget_type' => $widget['widget_type'],
                    'title'       => $widget['title'],
                    'position'    => $pos,
                    'col_span'    => $widget['col_span'] ?? 1,
                    'config'      => $widget['config'] ?? [],
                    'is_active'   => true,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'widgets' => $stage->fresh()->dashboardWidgets,
        ]);
    }

    // ── Create new stage ──────────────────────────────────────────────────────

    public function store($workflowId, Request $request)
    {
        $data = $request->validate([
            'system_key'      => ['required', 'string', 'max:100'],
            'display_name'    => ['required', 'string', 'max:200'],
            'icon'            => ['nullable', 'string', 'max:100'],
            'color'           => ['nullable', 'string', 'max:20'],
            'is_optional'     => ['nullable', 'boolean'],
            'parent_stage_id' => ['nullable', 'integer', 'exists:wf_stages,id'],
        ]);

        // Get the max position (within parent or top-level)
        $query = WfStage::where('workflow_id', $workflowId);
        if (!empty($data['parent_stage_id'])) {
            $query->where('parent_stage_id', $data['parent_stage_id']);
        } else {
            $query->whereNull('parent_stage_id');
        }
        $maxPosition = $query->max('position') ?? 0;

        $stage = WfStage::create([
            'workflow_id'     => $workflowId,
            'parent_stage_id' => $data['parent_stage_id'] ?? null,
            'system_key'      => $data['system_key'],
            'display_name'    => $data['display_name'],
            'icon'            => $data['icon'] ?? 'fa-solid fa-circle',
            'color'           => $data['color'] ?? '#64748b',
            'position'        => $maxPosition + 1,
            'is_active'       => true,
            'is_optional'     => $data['is_optional'] ?? false,
            'config'          => (object) [],
            'created_by'      => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stage created successfully.',
            'stage'   => $stage->load('actions', 'roles.role'),
        ]);
    }

    // ── Delete stage ──────────────────────────────────────────────────────────

    public function destroy($workflowId, $stageId)
    {
        $stage = WfStage::where('workflow_id', $workflowId)->findOrFail($stageId);

        // Check if this is the only stage
        $stageCount = WfStage::where('workflow_id', $workflowId)->count();
        if ($stageCount <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the last stage in the workflow.',
            ], 422);
        }

        DB::transaction(function () use ($stage, $workflowId) {
            $position = $stage->position;
            $stage->delete();

            // Reorder remaining stages
            WfStage::where('workflow_id', $workflowId)
                ->where('position', '>', $position)
                ->decrement('position');
        });

        return response()->json([
            'success' => true,
            'message' => 'Stage deleted successfully.',
        ]);
    }
}
