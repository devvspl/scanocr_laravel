<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\WfStage;
use App\Models\WfStageAction;
use App\Models\WfWorkflow;
use App\Models\WfWorkflowLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class WorkflowController extends Controller
{
    // ── List all workflows ────────────────────────────────────────────────────

    public function index()
    {
        $workflows = WfWorkflow::with(['stages', 'createdBy', 'docType'])
            ->withCount('stages')
            ->latest()
            ->get();

        $docTypes = DocumentType::where('is_active', true)->orderBy('sort_order')->get();

        return view('workflow.index', compact('workflows', 'docTypes'));
    }

    // ── Create form ───────────────────────────────────────────────────────────

    public function create()
    {
        $docTypes = DocumentType::where('is_active', true)->orderBy('sort_order')->get();

        return view('workflow.create', compact('docTypes'));
    }

    // ── Store new workflow ────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:200'],
            'doc_type_id' => ['nullable', 'exists:document_types,id'],
            'description' => ['nullable', 'string'],
        ]);

        $workflow = WfWorkflow::create($data);

        return response()->json([
            'success'  => true,
            'message'  => 'Workflow created successfully.',
            'redirect' => route('master.workflow.designer', $workflow->id),
        ]);
    }

    // ── Visual designer page ──────────────────────────────────────────────────

    public function designer($id)
    {
        $workflow = WfWorkflow::with([
            'stages.actionMap.actionDefinition',
            'stages.subStages.actionMap.actionDefinition',
            'stages.subStages.page.fields',
            'stages.notificationRules',
            'routingRules',
        ])->findOrFail($id);

        $allRoles = Role::orderBy('name')->get(['id', 'name']);
        $docTypes = DocumentType::where('is_active', true)->orderBy('sort_order')->get(['id', 'label']);
        $allPages = \App\Models\Page::orderBy('page_name')->get(['id', 'page_name']);
        $allActions = \App\Models\WfActionDefinition::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'group', 'action_key', 'display_label', 'icon', 'button_style', 'logic_type']);
        $allUsers = \App\Models\User::orderBy('name')->get(['id', 'name', 'email']);
        $allEmailTemplates = \App\Models\WfEmailTemplate::where('is_active', true)
            ->orderBy('category')->orderBy('name')
            ->get(['id', 'name', 'slug', 'subject', 'category']);

        // Prepare workflow data for JavaScript
        $wfData = [
            'workflowId'   => $workflow->id,
            'stages'       => $workflow->stages->whereNull('parent_stage_id')->map(fn($s) => [
                'id'          => $s->id,
                'system_key'  => $s->system_key,
                'display_name'=> $s->display_name,
                'position'    => $s->position,
                'is_active'   => $s->is_active,
                'is_optional' => $s->is_optional,
                'icon'        => $s->icon,
                'color'       => $s->color,
                'config'      => $s->config ?: (object)[],
                'page_id'     => $s->page_id,
                'layout_template' => $s->layout_template ?? 'form_sidebar',
                'actions'     => $s->actionMap->map(fn($m) => [
                    'id'              => $m->id,
                    'action_key'      => $m->actionDefinition->action_key,
                    'display_label'   => $m->actionDefinition->display_label,
                    'button_style'    => $m->actionDefinition->button_style,
                    'logic_type'      => $m->actionDefinition->logic_type,
                    'is_active'       => $m->is_active,
                    'notify_enabled'  => $m->notify_enabled,
                    'notify_medium'   => $m->notify_medium,
                    'notify_recipients' => $m->notify_recipients,
                    'notify_user_ids'   => $m->notify_user_ids,
                    'notify_frequency'  => $m->notify_frequency,
                    'escalation_hours'  => $m->escalation_hours,
                    'notify_next_stage' => $m->notify_next_stage,
                    'email_template_id' => $m->email_template_id,
                ])->values(),
                'sub_stages'  => $s->subStages->map(fn($sub) => [
                    'id'          => $sub->id,
                    'system_key'  => $sub->system_key,
                    'display_name'=> $sub->display_name,
                    'position'    => $sub->position,
                    'is_active'   => $sub->is_active,
                    'icon'        => $sub->icon,
                    'color'       => $sub->color,
                    'page_id'     => $sub->page_id,
                    'layout_template' => $sub->layout_template ?? 'form_sidebar',
                    'actions'     => $sub->actionMap->map(fn($m) => [
                        'id'           => $m->id,
                        'action_key'   => $m->actionDefinition->action_key,
                        'display_label'=> $m->actionDefinition->display_label,
                        'button_style' => $m->actionDefinition->button_style,
                        'is_active'    => $m->is_active,
                    ])->values(),
                ])->values(),
            ])->values(),
            'routingRules' => $workflow->routingRules,
            'allRoles'     => $allRoles->map(fn($r) => ['id' => $r->id, 'name' => $r->name]),
            'allPages'     => $allPages->map(fn($p) => ['id' => $p->id, 'name' => $p->page_name]),
            'allActions'   => $allActions,
            'allUsers'     => $allUsers->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email]),
            'allEmailTemplates' => $allEmailTemplates,
            'routes' => [
                'stageStore'   => route('master.workflow.stage.store', $workflow->id),
                'stageUpdate'  => route('master.workflow.stage.update', [$workflow->id, '__STAGE__']),
                'stageDestroy' => route('master.workflow.stage.destroy', [$workflow->id, '__STAGE__']),
                'stageReorder' => route('master.workflow.stage.reorder', $workflow->id),
                'stageActions' => route('master.workflow.stage.actions', [$workflow->id, '__STAGE__']),
                'stageWidgets' => route('master.workflow.stage.widgets', [$workflow->id, '__STAGE__']),
                'stageWidgetsSave' => route('master.workflow.stage.widgets.save', [$workflow->id, '__STAGE__']),
                'actionMapUpdate' => route('master.workflow.stage-action.update', [$workflow->id, '__MAP__']),
                'actionToggle' => route('master.workflow.action.toggle', [$workflow->id, '__ACTION__']),
                'actionUpdate' => route('master.workflow.action.update', [$workflow->id, '__ACTION__']),
                'routingStore' => route('master.workflow.routing.store', $workflow->id),
                'pageFields'   => route('master.workflow.page-fields', '__PAGE__'),
                'publish'      => route('master.workflow.publish', $workflow->id),
            ],
            'csrfToken' => csrf_token(),
        ];

        return view('workflow.designer', compact('workflow', 'allRoles', 'docTypes', 'wfData'));
    }

    // ── Duplicate workflow with all relations ─────────────────────────────────

    public function duplicate($id)
    {
        $original = WfWorkflow::with([
            'stages.actions',
            'stages.roles',
            'routingRules',
        ])->findOrFail($id);

        $newWorkflow = null;

        DB::transaction(function () use ($original, &$newWorkflow) {
            $newWorkflow = WfWorkflow::create([
                'name'        => $original->name . ' (Copy)',
                'description' => $original->description,
                'doc_type_id' => $original->doc_type_id,
                'is_active'   => false,
                'is_default'  => false,
                'version'     => $original->version + 1,
            ]);

            foreach ($original->stages as $stage) {
                $newStage = WfStage::create([
                    'workflow_id'  => $newWorkflow->id,
                    'system_key'   => $stage->system_key,
                    'display_name' => $stage->display_name,
                    'position'     => $stage->position,
                    'is_active'    => $stage->is_active,
                    'is_optional'  => $stage->is_optional,
                    'icon'         => $stage->icon,
                    'color'        => $stage->color,
                    'description'  => $stage->description,
                    'config'       => $stage->getRawOriginal('config'),
                    'created_by'   => auth()->id(),
                ]);

                foreach ($stage->actions as $action) {
                    WfStageAction::create([
                        'stage_id'              => $newStage->id,
                        'action_key'            => $action->action_key,
                        'display_label'         => $action->display_label,
                        'is_active'             => $action->is_active,
                        'requires_remark'       => $action->requires_remark,
                        'remark_label'          => $action->remark_label,
                        'confirm_before_action' => $action->confirm_before_action,
                        'confirm_message'       => $action->confirm_message,
                        'position'              => $action->position,
                        'icon'                  => $action->icon,
                        'button_style'          => $action->button_style,
                        'next_stage_key'        => $action->next_stage_key,
                    ]);
                }

                foreach ($stage->roles as $role) {
                    \App\Models\WfStageRole::create([
                        'stage_id'    => $newStage->id,
                        'role_id'     => $role->role_id,
                        'can_view'    => $role->can_view,
                        'can_act'     => $role->can_act,
                        'is_notified' => $role->is_notified,
                    ]);
                }
            }

            foreach ($original->routingRules as $rule) {
                \App\Models\WfRoutingRule::create([
                    'workflow_id'        => $newWorkflow->id,
                    'from_stage_key'     => $rule->from_stage_key,
                    'action_key'         => $rule->action_key,
                    'condition_field'    => $rule->condition_field,
                    'condition_operator' => $rule->condition_operator,
                    'condition_value'    => $rule->condition_value,
                    'to_stage_key'       => $rule->to_stage_key,
                    'priority'           => $rule->priority,
                    'is_active'          => $rule->is_active,
                ]);
            }
        });

        return response()->json([
            'success'  => true,
            'message'  => 'Workflow duplicated successfully.',
            'redirect' => route('workflow.designer', $newWorkflow->id),
        ]);
    }

    // ── Publish workflow ──────────────────────────────────────────────────────

    public function publish($id)
    {
        $workflow = WfWorkflow::with([
            'stages.actionMap.actionDefinition',
            'stages.page',
        ])->findOrFail($id);

        $errors = [];

        if ($workflow->stages->isEmpty()) {
            $errors[] = 'Workflow must have at least one stage.';
        }

        $activeStages = $workflow->stages->where('is_active', true);
        if ($activeStages->isEmpty()) {
            $errors[] = 'Workflow must have at least one active stage.';
        }

        foreach ($activeStages as $stage) {
            if ($stage->actionMap->isEmpty()) {
                $errors[] = "Stage \"{$stage->display_name}\" has no actions assigned.";
            }
        }

        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot publish workflow.',
                'errors'  => $errors,
            ], 422);
        }

        DB::transaction(function () use ($workflow) {
            if ($workflow->doc_type_id) {
                WfWorkflow::where('doc_type_id', $workflow->doc_type_id)
                    ->where('id', '!=', $workflow->id)
                    ->update(['is_active' => false, 'is_default' => false]);
            } else {
                WfWorkflow::whereNull('doc_type_id')
                    ->where('id', '!=', $workflow->id)
                    ->update(['is_active' => false, 'is_default' => false]);
            }

            $workflow->update([
                'is_active'  => true,
                'is_default' => true,
                'version'    => $workflow->version + 1,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => "Workflow \"{$workflow->name}\" published as v{$workflow->version}.",
            'summary' => [
                'stages' => $activeStages->map(fn($s) => [
                    'name'    => $s->display_name,
                    'actions' => $s->actionMap->count(),
                    'form'    => $s->page?->page_name ?? 'None',
                ])->values(),
            ],
        ]);
    }

    // ── Activate workflow as default ──────────────────────────────────────────

    public function activate($id)
    {
        $workflow = WfWorkflow::findOrFail($id);

        DB::transaction(function () use ($workflow, $id) {
            if ($workflow->doc_type_id) {
                WfWorkflow::where('doc_type_id', $workflow->doc_type_id)
                    ->where('id', '!=', $id)
                    ->update(['is_default' => false]);
            } else {
                WfWorkflow::whereNull('doc_type_id')
                    ->where('id', '!=', $id)
                    ->update(['is_default' => false]);
            }

            $workflow->update(['is_active' => true, 'is_default' => true]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Workflow activated and set as default.',
        ]);
    }

    // ── Delete workflow ───────────────────────────────────────────────────────

    public function destroy($id)
    {
        $workflow = WfWorkflow::findOrFail($id);

        if ($workflow->is_default) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the active default workflow.',
            ], 422);
        }

        // Check for recent activity in logs
        $hasActivity = WfWorkflowLog::where('workflow_id', $id)->exists();
        if ($hasActivity) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a workflow that has activity logs. Deactivate it instead.',
            ], 422);
        }

        $workflow->delete();

        return response()->json([
            'success' => true,
            'message' => 'Workflow deleted.',
        ]);
    }

    // ── Run workflow (process page) ───────────────────────────────────────────

    public function run($id)
    {
        $workflow = WfWorkflow::with([
            'stages.actionMap.actionDefinition',
            'stages.page.fields',
            'stages.dashboardWidgets',
            'stages.subStages.actionMap.actionDefinition',
            'stages.subStages.page.fields',
        ])->where('is_active', true)->findOrFail($id);

        $stages = $workflow->stages->where('is_active', true)->sortBy('position')->values();

        // Allow switching between stages via query param
        $stageId = request()->query('stage');
        if ($stageId) {
            $currentStage = $stages->firstWhere('id', $stageId);
        }
        if (empty($currentStage)) {
            $currentStage = $stages->first();
        }

        return view('workflow.run', compact('workflow', 'stages', 'currentStage'));
    }

    // ── Page fields JSON for workflow designer preview ─────────────────────────

    public function pageFields($pageId)
    {
        $page = \App\Models\Page::with('fields')->findOrFail($pageId);

        return response()->json([
            'success' => true,
            'page'    => ['id' => $page->id, 'name' => $page->page_name],
            'fields'  => $page->fields->map(fn($f) => [
                'id'              => $f->id,
                'field_name'      => $f->field_name,
                'field_type'      => $f->field_type,
                'label'           => $f->label ?? $f->field_name,
                'is_required'     => $f->is_required,
                'placeholder'     => $f->placeholder,
                'default_value'   => $f->default_value,
                'col_span'        => $f->col_span,
                'options'         => $f->options,
                'repeater_columns'=> $f->repeater_columns,
            ])->values(),
        ]);
    }
}
