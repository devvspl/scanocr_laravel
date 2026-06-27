<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\MasterFile;
use App\Models\Company;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class FileController extends Controller
{
    public function index()
    {
        $companies = Company::where('is_active', true)->orderBy('name')->get();
        return view('panel.settings.files', compact('companies'));
    }

    public function data(Request $request)
    {
        $query = MasterFile::with(['creator', 'company'])->notDeleted();

        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(fn($q) => $q
                ->where('file_name', 'like', "%{$search}%")
                ->orWhere('file_code', 'like', "%{$search}%")
                ->orWhereHas('company', fn($cq) => $cq->where('name', 'like', "%{$search}%"))
            );
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status === 'active' ? 'A' : 'D');
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        $total    = $query->count();
        $start    = (int) $request->get('start', 0);
        $length   = (int) $request->get('length', 25);
        $order    = $request->input('order', [['column' => 0, 'dir' => 'asc']]);
        $cols     = ['file_id', 'file_name', 'file_code', 'company_id', 'status'];
        $col      = $cols[(int)($order[0]['column'] ?? 0)] ?? 'file_id';
        $dir      = ($order[0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($col, $dir);

        $filtered = $query->count();
        $rows     = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows->map(fn($d) => [
                'file_id'          => $d->file_id,
                'file_name'        => $d->file_name,
                'file_code'        => $d->file_code,
                'company_name'     => $d->company?->name ?? '—',
                'status'           => $d->status,
                'created_by_name'  => $d->creator?->name ?? '—',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'file_name'  => ['required', 'string', 'max:60'],
            'file_code'  => ['required', 'string', 'max:20'],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'status'     => ['required', 'in:A,D'],
        ]);

        $data['created_by'] = auth()->id();
        $data['is_deleted'] = 'N';

        $file = MasterFile::create($data);
        ActivityLogger::log('created', $file, null, $file->getAttributes());

        return response()->json(['success' => true, 'message' => 'File created successfully.', 'data' => ['id' => $file->file_id]]);
    }

    public function show(MasterFile $file)
    {
        return response()->json($file);
    }

    public function update(Request $request, MasterFile $file)
    {
        $data = $request->validate([
            'file_name'  => ['required', 'string', 'max:60'],
            'file_code'  => ['required', 'string', 'max:20'],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'status'     => ['required', 'in:A,D'],
        ]);

        $data['updated_by'] = auth()->id();

        $old = $file->getAttributes();
        $file->update($data);
        ActivityLogger::log('updated', $file, $old, $file->getAttributes());

        return response()->json(['success' => true, 'message' => 'File updated successfully.']);
    }

    public function destroy(MasterFile $file)
    {
        $old = $file->getAttributes();
        $file->update(['is_deleted' => 'Y', 'updated_by' => auth()->id()]);
        ActivityLogger::log('deleted', $file, $old, $file->getAttributes());

        return response()->json(['success' => true, 'message' => 'File deleted successfully.']);
    }
}
