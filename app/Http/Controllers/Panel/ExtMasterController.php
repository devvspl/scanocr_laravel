<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\ExtFieldMapping;
use App\Models\ExtMasterApiControl;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class ExtMasterController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // API Control
    // ─────────────────────────────────────────────────────────────────────────

    public function apiIndex()
    {
        $documentTypes = DocumentType::where('is_active', true)->orderBy('label')->get(['id', 'label', 'key']);
        return view('panel.settings.ext-master-api', compact('documentTypes'));
    }

    public function apiData(Request $request)
    {
        $query = ExtMasterApiControl::with('documentType');

        // Search
        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(fn($q) => $q
                ->where('endpoint', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('documentType', fn($q2) => $q2->where('label', 'like', "%{$search}%"))
            );
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status === 'active' ? 1 : 0);
        }
        if ($request->filled('doctype_id')) {
            $query->where('doctype_id', $request->doctype_id);
        }

        $total    = $query->count();
        $start    = (int) $request->get('start', 0);
        $length   = (int) $request->get('length', 25);
        $order    = $request->input('order', [['column' => 0, 'dir' => 'asc']]);
        $cols     = ['id', 'doctype_id', 'endpoint', 'status', 'created'];
        $col      = $cols[(int)($order[0]['column'] ?? 0)] ?? 'id';
        $dir      = ($order[0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($col, $dir);

        $filtered = $query->count();
        $rows     = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows->map(fn($r) => [
                'id'           => $r->id,
                'doctype_id'   => $r->doctype_id,
                'doctype_label'=> $r->documentType?->label ?? '—',
                'endpoint'     => $r->endpoint,
                'description'  => $r->description,
                'status'       => $r->status,
                'created'      => $r->created?->format('d M Y'),
            ]),
        ]);
    }

    public function apiStore(Request $request)
    {
        $data = $request->validate([
            'doctype_id'  => ['required', 'integer', 'exists:document_types,id'],
            'endpoint'    => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status'      => ['nullable', 'boolean'],
        ]);
        $data['status'] = $request->boolean('status', true) ? 1 : 0;

        $record = ExtMasterApiControl::create($data);
        ActivityLogger::log('created', $record, null, $record->getAttributes());

        return response()->json(['success' => true, 'message' => 'API control created.', 'data' => ['id' => $record->id]]);
    }

    public function apiShow(ExtMasterApiControl $extMasterApiControl)
    {
        return response()->json($extMasterApiControl);
    }

    public function apiUpdate(Request $request, ExtMasterApiControl $extMasterApiControl)
    {
        $data = $request->validate([
            'doctype_id'  => ['required', 'integer', 'exists:document_types,id'],
            'endpoint'    => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status'      => ['nullable', 'boolean'],
        ]);
        $data['status'] = $request->boolean('status', true) ? 1 : 0;

        $old = $extMasterApiControl->getAttributes();
        $extMasterApiControl->update($data);
        ActivityLogger::log('updated', $extMasterApiControl, $old, $extMasterApiControl->getAttributes());

        return response()->json(['success' => true, 'message' => 'API control updated.']);
    }

    public function apiDestroy(ExtMasterApiControl $extMasterApiControl)
    {
        $snapshot = $extMasterApiControl->getAttributes();
        $extMasterApiControl->delete();
        ActivityLogger::log('deleted', $extMasterApiControl, $snapshot, null);

        return response()->json(['success' => true, 'message' => 'API control deleted.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Field Mappings
    // ─────────────────────────────────────────────────────────────────────────

    public function fieldIndex()
    {
        $documentTypes = DocumentType::where('is_active', true)->orderBy('label')->get(['id', 'label', 'key']);
        return view('panel.settings.ext-field-mappings', compact('documentTypes'));
    }

    public function fieldData(Request $request)
    {
        $query = ExtFieldMapping::with('documentType');

        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(fn($q) => $q
                ->where('temp_column', 'like', "%{$search}%")
                ->orWhere('punch_column', 'like', "%{$search}%")
                ->orWhere('punch_table', 'like', "%{$search}%")
                ->orWhereHas('documentType', fn($q2) => $q2->where('label', 'like', "%{$search}%"))
            );
        }

        if ($request->filled('doctype_id')) {
            $query->where('doctype_id', $request->doctype_id);
        }
        if ($request->filled('punch_table')) {
            $query->where('punch_table', $request->punch_table);
        }
        if ($request->filled('input_type')) {
            $query->where('input_type', $request->input_type);
        }
        if ($request->filled('has_items')) {
            $query->where('has_Items_feild', $request->has_items);
        }

        $total    = $query->count();
        $start    = (int) $request->get('start', 0);
        $length   = (int) $request->get('length', 25);
        $order    = $request->input('order', [['column' => 0, 'dir' => 'asc']]);
        $cols     = ['id', 'doctype_id', 'temp_column', 'punch_table', 'punch_column', 'input_type'];
        $col      = $cols[(int)($order[0]['column'] ?? 0)] ?? 'id';
        $dir      = ($order[0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($col, $dir);

        $filtered = $query->count();
        $rows     = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows->map(fn($r) => [
                'id'              => $r->id,
                'doctype_id'      => $r->doctype_id,
                'doctype_label'   => $r->documentType?->label ?? '—',
                'temp_column'     => $r->temp_column,
                'input_type'      => $r->input_type,
                'select_table'    => $r->select_table,
                'relation_column' => $r->relation_column,
                'relation_value'  => $r->relation_value,
                'punch_table'     => $r->punch_table,
                'punch_column'    => $r->punch_column,
                'has_Items_feild' => $r->has_Items_feild,
                'add_condition'   => $r->add_condition,
            ]),
        ]);
    }

    public function fieldStore(Request $request)
    {
        $data = $request->validate([
            'doctype_id'      => ['required', 'integer', 'exists:document_types,id'],
            'temp_column'     => ['required', 'string', 'max:255'],
            'input_type'      => ['required', 'in:input,select'],
            'select_table'    => ['nullable', 'string', 'max:255'],
            'relation_column' => ['nullable', 'string', 'max:255'],
            'relation_value'  => ['nullable', 'string', 'max:255'],
            'punch_table'     => ['required', 'string', 'max:100'],
            'punch_column'    => ['nullable', 'string', 'max:255'],
            'has_Items_feild' => ['nullable', 'in:Y,N'],
            'add_condition'   => ['nullable', 'string'],
        ]);
        $data['has_Items_feild'] = $data['has_Items_feild'] ?? 'N';

        $record = ExtFieldMapping::create($data);
        ActivityLogger::log('created', $record, null, $record->getAttributes());

        return response()->json(['success' => true, 'message' => 'Field mapping created.', 'data' => ['id' => $record->id]]);
    }

    public function fieldShow(ExtFieldMapping $extFieldMapping)
    {
        return response()->json($extFieldMapping);
    }

    public function fieldUpdate(Request $request, ExtFieldMapping $extFieldMapping)
    {
        $data = $request->validate([
            'doctype_id'      => ['required', 'integer', 'exists:document_types,id'],
            'temp_column'     => ['required', 'string', 'max:255'],
            'input_type'      => ['required', 'in:input,select'],
            'select_table'    => ['nullable', 'string', 'max:255'],
            'relation_column' => ['nullable', 'string', 'max:255'],
            'relation_value'  => ['nullable', 'string', 'max:255'],
            'punch_table'     => ['required', 'string', 'max:100'],
            'punch_column'    => ['nullable', 'string', 'max:255'],
            'has_Items_feild' => ['nullable', 'in:Y,N'],
            'add_condition'   => ['nullable', 'string'],
        ]);
        $data['has_Items_feild'] = $data['has_Items_feild'] ?? 'N';

        $old = $extFieldMapping->getAttributes();
        $extFieldMapping->update($data);
        ActivityLogger::log('updated', $extFieldMapping, $old, $extFieldMapping->getAttributes());

        return response()->json(['success' => true, 'message' => 'Field mapping updated.']);
    }

    public function fieldDestroy(ExtFieldMapping $extFieldMapping)
    {
        $snapshot = $extFieldMapping->getAttributes();
        $extFieldMapping->delete();
        ActivityLogger::log('deleted', $extFieldMapping, $snapshot, null);

        return response()->json(['success' => true, 'message' => 'Field mapping deleted.']);
    }
}
