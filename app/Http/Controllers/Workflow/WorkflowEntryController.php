<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Helpers\PageFieldHelper;
use App\Models\WfEntry;
use App\Models\WfStage;
use App\Models\WfWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WorkflowEntryController extends Controller
{
    // ── Execute an action (save draft, submit, etc.) ──────────────────────────

    public function executeAction($workflowId, Request $request)
    {
        $workflow = WfWorkflow::with('stages.page')->findOrFail($workflowId);

        $request->validate([
            'action_key'  => ['required', 'string'],
            'stage_id'    => ['required', 'integer'],
            'entry_id'    => ['nullable', 'integer'],
        ]);

        $actionKey = $request->input('action_key');
        $stageId   = $request->input('stage_id');
        $entryId   = $request->input('entry_id');

        // Get the stage and its linked page
        $stage = WfStage::with('page.fields')->findOrFail($stageId);
        $page  = $stage->page;

        // Determine the gen_ table name from the page
        $genTableName = null;
        $genRecordId  = null;

        // Determine if this is a stage-move action on an existing entry (no form data needed)
        $isExistingEntryAction = $request->filled('wf_entry_id');
        $actionDef = \App\Models\WfActionDefinition::where('action_key', $actionKey)->first();
        $hasMoveDirective = $actionDef && !empty($actionDef->logic_config['move_to']);
        $isStageMoveOnly = $isExistingEntryAction && $hasMoveDirective;

        if ($page && $page->is_generated) {
            $genTableName = 'gen_' . Str::snake(Str::plural($page->page_name));

            if (Schema::hasTable($genTableName)) {
                if ($isStageMoveOnly) {
                    // Stage move on existing entry — don't touch the data, just move it
                    $genRecordId = $entryId;
                } elseif ($isExistingEntryAction) {
                    // Editing an existing entry from a non-first stage — only update fields that have values
                    // Don't validate (the entry already has its required data from creation)
                    $genRecordId = $entryId;
                    $rowData = [];
                    foreach ($page->fields as $field) {
                        $col = PageFieldHelper::columnName($field);
                        $inputName = PageFieldHelper::inputName($field);

                        if (in_array($field->field_type, ['file', 'image'])) {
                            $files = $request->file($inputName) ?? $request->file($col);
                            if (!$files) {
                                $allFiles = $request->allFiles();
                                $files = $allFiles[$inputName] ?? $allFiles[$col] ?? null;
                            }
                            if ($files) {
                                $paths = [];
                                $fileList = is_array($files) ? $files : [$files];
                                foreach ($fileList as $file) {
                                    if ($file && $file->isValid()) {
                                        $paths[] = $file->store('uploads/' . Str::slug($page->page_name), 'public');
                                    }
                                }
                                if (!empty($paths)) {
                                    $rowData[$col] = json_encode($paths);
                                }
                            }
                        } elseif ($field->field_type === 'checkbox' || $field->field_type === 'toggle') {
                            if ($request->has($inputName) || $request->has($col)) {
                                $rowData[$col] = 1;
                            }
                        } elseif ($field->field_type !== 'repeater') {
                            $value = $request->input($inputName) ?? $request->input($col);
                            if ($value !== null && $value !== '') {
                                $rowData[$col] = $value;
                            }
                        }
                    }
                    // Only update if there's actual data to save
                    if (!empty($rowData) && $genRecordId) {
                        // Check column exists before updating
                        $existingCols = Schema::getColumnListing($genTableName);
                        $rowData = array_intersect_key($rowData, array_flip($existingCols));
                        if (!empty($rowData)) {
                            DB::table($genTableName)->where('id', $genRecordId)->update($rowData);
                        }
                    }
                } else {
                    // New entry creation (first stage) — validate and insert
                    $validationRules = PageFieldHelper::validationRules($page->fields, $genTableName, $entryId);
                    if (!empty($validationRules)) {
                        $request->validate($validationRules);
                    }

                    // Collect form data from fields
                    $rowData = [];
                    foreach ($page->fields as $field) {
                        $col = PageFieldHelper::columnName($field);
                        $inputName = PageFieldHelper::inputName($field);

                        if (in_array($field->field_type, ['file', 'image'])) {
                            $files = $request->file($inputName) ?? $request->file($col);
                            if (!$files) {
                                $allFiles = $request->allFiles();
                                $files = $allFiles[$inputName] ?? $allFiles[$col] ?? null;
                            }
                            if ($files) {
                                $paths = [];
                                $fileList = is_array($files) ? $files : [$files];
                                foreach ($fileList as $file) {
                                    if ($file && $file->isValid()) {
                                        $paths[] = $file->store('uploads/' . Str::slug($page->page_name), 'public');
                                    }
                                }
                                if (!empty($paths)) {
                                    $rowData[$col] = json_encode($paths);
                                }
                            }
                        } elseif ($field->field_type === 'checkbox' || $field->field_type === 'toggle') {
                            $rowData[$col] = $request->has($inputName) || $request->has($col) ? 1 : 0;
                        } elseif ($field->field_type !== 'repeater') {
                            $value = $request->input($inputName) ?? $request->input($col);
                            if ($value !== null && $value !== '') {
                                $rowData[$col] = $value;
                            }
                        }
                    }

                    if (!empty($rowData) && $entryId) {
                        DB::table($genTableName)->where('id', $entryId)->update($rowData);
                        $genRecordId = $entryId;
                    } elseif (!empty($rowData)) {
                        $genRecordId = DB::table($genTableName)->insertGetId(
                            array_merge($rowData, ['created_at' => now(), 'updated_at' => now()])
                        );
                    }
                }
            }
        }

        // Determine status from action definition's logic_config
        $status = $actionDef && !empty($actionDef->logic_config['set_status'])
            ? $actionDef->logic_config['set_status']
            : $this->resolveStatus($actionKey);

        // ── Resolve stage transition ──
        $nextStageId = $stageId;
        $toStageKey = $stage->system_key;
        $moveDirection = $actionDef ? ($actionDef->logic_config['move_to'] ?? null) : null;

        if ($moveDirection) {
            // Check routing rules first
            $routingRules = \App\Models\WfRoutingRule::where('workflow_id', $workflowId)
                ->where('from_stage_key', $stage->system_key)
                ->where('action_key', $actionKey)
                ->where('is_active', true)
                ->orderBy('priority')
                ->get();

            $matchedRule = null;
            $documentData = $rowData ?? [];
            foreach ($routingRules as $rule) {
                if ($rule->evaluates($documentData)) {
                    $matchedRule = $rule;
                    break;
                }
            }

            if ($matchedRule) {
                $targetStage = WfStage::where('workflow_id', $workflowId)
                    ->where('system_key', $matchedRule->to_stage_key)
                    ->first();
                if ($targetStage) {
                    $nextStageId = $targetStage->id;
                    $toStageKey = $targetStage->system_key;
                }
            } elseif ($moveDirection === 'next') {
                $nextStage = WfStage::where('workflow_id', $workflowId)
                    ->whereNull('parent_stage_id')
                    ->where('is_active', true)
                    ->where('position', '>', $stage->position)
                    ->orderBy('position')
                    ->first();
                if ($nextStage) {
                    $nextStageId = $nextStage->id;
                    $toStageKey = $nextStage->system_key;
                }
            } elseif ($moveDirection === 'previous') {
                $prevStage = WfStage::where('workflow_id', $workflowId)
                    ->whereNull('parent_stage_id')
                    ->where('is_active', true)
                    ->where('position', '<', $stage->position)
                    ->orderByDesc('position')
                    ->first();
                if ($prevStage) {
                    $nextStageId = $prevStage->id;
                    $toStageKey = $prevStage->system_key;
                }
            }
        }

        // Also track in wf_entries for workflow state management
        if ($entryId && $request->input('wf_entry_id')) {
            $wfEntry = WfEntry::find($request->input('wf_entry_id'));
            if ($wfEntry) {
                $wfEntry->update([
                    'status'           => $status,
                    'current_stage_id' => $nextStageId,
                    'form_data'        => ['gen_table' => $genTableName, 'gen_record_id' => $genRecordId],
                    'updated_by'       => auth()->id(),
                ]);
            }
        } else {
            $wfEntry = WfEntry::create([
                'workflow_id'      => $workflowId,
                'current_stage_id' => $nextStageId,
                'status'           => $status,
                'form_data'        => ['gen_table' => $genTableName, 'gen_record_id' => $genRecordId],
                'created_by'       => auth()->id(),
            ]);
        }

        // Write workflow log
        \App\Models\WfWorkflowLog::record([
            'workflow_id'    => $workflowId,
            'stage_id'       => $nextStageId,
            'system_key'     => $stage->system_key,
            'action_key'     => $actionKey,
            'document_ref'   => $genRecordId ? "#{$genRecordId}" : "entry#{$wfEntry->id}",
            'from_stage_key' => $stage->system_key,
            'to_stage_key'   => $toStageKey,
            'remark'         => $request->input('remark'),
            'metadata'       => [
                'status'        => $status,
                'gen_table'     => $genTableName,
                'gen_record_id' => $genRecordId,
                'entry_id'      => $wfEntry->id,
                'moved_stage'   => $nextStageId !== $stageId,
            ],
        ]);

        return response()->json([
            'success'       => true,
            'message'       => $this->getActionMessage($actionKey),
            'entry_id'      => $genRecordId,
            'wf_entry_id'   => $wfEntry->id,
            'status'        => $status,
            'stage_moved'   => $nextStageId !== $stageId,
            'new_stage_id'  => $nextStageId,
        ]);
    }

    // ── List entries for a workflow ───────────────────────────────────────────

    public function list($workflowId, Request $request)
    {
        $workflow = WfWorkflow::with('stages.page.fields')->findOrFail($workflowId);

        $perPage = (int) $request->input('per_page', 10);
        $page = (int) $request->input('page', 1);
        $search = $request->input('search', '');

        // Get field columns for the table
        $firstStage = $workflow->stages->first();
        $pageModel = $firstStage && $firstStage->page ? $firstStage->page : null;
        $fields = $pageModel ? $pageModel->fields : collect();
        $columns = $fields->filter(fn($f) => !in_array($f->field_type, ['file', 'image', 'repeater', 'password', 'json']))
            ->take(4)
            ->map(fn($f) => [
                'key' => PageFieldHelper::columnName($f),
                'label' => $f->label ?? $f->field_name,
            ])->values()->toArray();

        // All columns for detail view
        $allColumns = $fields->filter(fn($f) => $f->field_type !== 'repeater')
            ->map(fn($f) => [
                'key' => PageFieldHelper::columnName($f),
                'label' => $f->label ?? $f->field_name,
                'type' => $f->field_type,
            ])->values()->toArray();

        // Determine gen_ table
        $genTableName = null;
        if ($pageModel && $pageModel->is_generated) {
            $genTableName = 'gen_' . Str::snake(Str::plural($pageModel->page_name));
        }

        // Build query with search on gen_ table if available
        $query = WfEntry::where('workflow_id', $workflowId)
            ->where('status', '!=', 'deleted');

        // Filter by stage if provided
        $stageFilter = $request->input('stage_id');
        if ($stageFilter) {
            $query->where('current_stage_id', $stageFilter);
        }

        // Get total counts (unfiltered)
        $total = (clone $query)->count();
        $pending = WfEntry::where('workflow_id', $workflowId)->where('status', 'draft')->count();
        $completed = WfEntry::where('workflow_id', $workflowId)->whereIn('status', ['completed', 'approved', 'final_approved'])->count();

        // Fetch entries with pagination
        $wfEntries = $query->with('currentStage:id,display_name,color')
            ->latest()
            ->get();

        // Enrich entries with gen_ table data and apply search filter
        $enrichedEntries = $wfEntries->map(function ($entry) use ($genTableName) {
            $data = $entry->toArray();
            $formData = $entry->form_data ?? [];
            $genTable = $formData['gen_table'] ?? $genTableName;
            $genRecordId = $formData['gen_record_id'] ?? null;

            $data['record_data'] = null;
            if ($genTable && $genRecordId && Schema::hasTable($genTable)) {
                $record = DB::table($genTable)->where('id', $genRecordId)->first();
                if ($record) {
                    $data['record_data'] = (array) $record;
                }
            }

            return $data;
        });

        // Apply search filter on record_data
        if ($search) {
            $searchLower = strtolower($search);
            $enrichedEntries = $enrichedEntries->filter(function ($entry) use ($searchLower, $columns) {
                // Search in status
                if (str_contains(strtolower($entry['status'] ?? ''), $searchLower)) return true;
                // Search in record data
                $rd = $entry['record_data'] ?? [];
                foreach ($columns as $col) {
                    $val = $rd[$col['key']] ?? '';
                    if (is_string($val) && str_contains(strtolower($val), $searchLower)) return true;
                    if (is_numeric($val) && str_contains((string)$val, $search)) return true;
                }
                return false;
            })->values();
        }

        $filteredTotal = $enrichedEntries->count();

        // Paginate
        $paginatedEntries = $enrichedEntries->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'success'        => true,
            'total'          => $total,
            'filtered_total' => $filteredTotal,
            'pending'        => $pending,
            'completed'      => $completed,
            'columns'        => $columns,
            'all_columns'    => $allColumns,
            'entries'        => $paginatedEntries,
            'pagination'     => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total_pages'  => (int) ceil($filteredTotal / $perPage),
                'total'        => $filteredTotal,
            ],
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function resolveStatus(string $actionKey): string
    {
        $map = [
            'save_draft'       => 'draft',
            'create_entry'     => 'draft',
            'edit_entry'       => 'in_progress',
            'delete_entry'     => 'deleted',
            'send_for_approval'=> 'pending_approval',
            'approve'          => 'approved',
            'reject'           => 'rejected',
            'hold'             => 'on_hold',
            'send_back'        => 'returned',
            'mark_punched'     => 'punched',
            'verify_entry'     => 'verified',
            'mark_completed'   => 'completed',
            'mark_cancelled'   => 'cancelled',
            'final_approve'    => 'final_approved',
        ];

        return $map[$actionKey] ?? 'in_progress';
    }

    private function getActionMessage(string $actionKey): string
    {
        $messages = [
            'save_draft'       => 'Entry saved as draft.',
            'create_entry'     => 'Entry created.',
            'delete_entry'     => 'Entry deleted.',
            'send_for_approval'=> 'Sent for approval.',
            'approve'          => 'Entry approved.',
            'reject'           => 'Entry rejected.',
            'hold'             => 'Entry put on hold.',
            'mark_completed'   => 'Entry completed.',
        ];

        return $messages[$actionKey] ?? 'Action executed successfully.';
    }
}
