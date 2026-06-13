<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\WfWorkflow;
use App\Models\WfWorkflowLog;
use Illuminate\Http\Request;

class WorkflowLogController extends Controller
{
    // ── Paginated log viewer ──────────────────────────────────────────────────

    public function index($workflowId, Request $request)
    {
        $workflow = WfWorkflow::findOrFail($workflowId);

        $logs = WfWorkflowLog::where('workflow_id', $workflowId)
            ->with('performedBy')
            ->when($request->filled('document_ref'), fn($q) => $q->where('document_ref', $request->document_ref))
            ->when($request->filled('action_key'),   fn($q) => $q->where('action_key', $request->action_key))
            ->when($request->filled('system_key'),   fn($q) => $q->where('system_key', $request->system_key))
            ->when($request->filled('date_from'),    fn($q) => $q->whereDate('performed_at', '>=', $request->date_from))
            ->when($request->filled('date_to'),      fn($q) => $q->whereDate('performed_at', '<=', $request->date_to))
            ->latest('performed_at')
            ->paginate(50);

        if ($request->expectsJson()) {
            return response()->json($logs);
        }

        return view('workflow.log', compact('workflow', 'logs'));
    }
}
