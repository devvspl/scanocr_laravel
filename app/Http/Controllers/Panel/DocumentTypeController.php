<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class DocumentTypeController extends Controller
{
    public function index()
    {
        return view('panel.settings.document-types');
    }

    public function data(Request $request)
    {
        $query = DocumentType::with('creator');

        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(fn($q) => $q
                ->where('label', 'like', "%{$search}%")
                ->orWhere('key', 'like', "%{$search}%")
                ->orWhere('default_prefix', 'like', "%{$search}%")
                ->orWhere('module', 'like', "%{$search}%")
            );
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('approval')) {
            $query->where('digital_approval', $request->approval === 'enabled');
        }

        $total    = $query->count();
        $start    = (int) $request->get('start', 0);
        $length   = (int) $request->get('length', 25);
        $order    = $request->input('order', [['column' => 0, 'dir' => 'asc']]);
        $cols     = ['sort_order', 'label', 'key', 'default_prefix', 'module', 'is_active'];
        $col      = $cols[(int)($order[0]['column'] ?? 0)] ?? 'sort_order';
        $dir      = ($order[0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($col, $dir);

        $filtered = $query->count();
        $rows     = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows->map(fn($d) => [
                'id'               => $d->id,
                'key'              => $d->key,
                'label'            => $d->label,
                'default_prefix'   => $d->default_prefix,
                'module'           => $d->module,
                'sort_order'       => $d->sort_order,
                'is_system'        => $d->is_system,
                'is_active'        => $d->is_active,
                'digital_approval' => $d->digital_approval,
                'created_by_name'  => $d->creator?->name ?? '—',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'key'              => ['required', 'string', 'max:50', 'unique:document_types,key', 'regex:/^[a-z0-9_]+$/'],
            'label'            => ['required', 'string', 'max:100'],
            'default_prefix'   => ['required', 'string', 'max:20'],
            'module'           => ['nullable', 'string', 'max:50'],
            'sort_order'       => ['nullable', 'integer', 'min:0'],
            'is_active'        => ['nullable', 'boolean'],
            'digital_approval' => ['nullable', 'boolean'],
        ]);

        $data['is_active']        = $request->boolean('is_active', true);
        $data['digital_approval'] = $request->boolean('digital_approval', false);
        $data['is_system']        = false;
        $data['created_by']       = auth()->id();
        $data['sort_order']       = $data['sort_order'] ?? (DocumentType::max('sort_order') + 1);

        $dt = DocumentType::create($data);
        ActivityLogger::log('created', $dt, null, $dt->getAttributes());

        return response()->json(['success' => true, 'message' => 'Document type created.', 'data' => ['id' => $dt->id]]);
    }

    public function show(DocumentType $documentType)
    {
        return response()->json($documentType);
    }

    public function update(Request $request, DocumentType $documentType)
    {
        $data = $request->validate([
            'label'            => ['required', 'string', 'max:100'],
            'default_prefix'   => ['required', 'string', 'max:20'],
            'module'           => ['nullable', 'string', 'max:50'],
            'sort_order'       => ['nullable', 'integer', 'min:0'],
            'is_active'        => ['nullable', 'boolean'],
            'digital_approval' => ['nullable', 'boolean'],
        ]);

        $data['is_active']        = $request->boolean('is_active', true);
        $data['digital_approval'] = $request->boolean('digital_approval', false);

        $old = $documentType->getAttributes();
        $documentType->update($data);
        ActivityLogger::log('updated', $documentType, $old, $documentType->getAttributes());

        return response()->json(['success' => true, 'message' => 'Document type updated.']);
    }

    public function destroy(DocumentType $documentType)
    {
        if ($documentType->is_system) {
            return response()->json(['success' => false, 'message' => 'System document types cannot be deleted.'], 422);
        }

        $snapshot = $documentType->getAttributes();
        $documentType->delete();
        ActivityLogger::log('deleted', $documentType, $snapshot, null);

        return response()->json(['success' => true, 'message' => 'Document type deleted.']);
    }
}
