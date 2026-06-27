<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Company;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $companies = Company::where('is_active', true)->orderBy('name')->get();
        return view('panel.settings.departments', compact('companies'));
    }

    public function data(Request $request)
    {
        $query = Department::with(['creator', 'company'])->notDeleted();

        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(fn($q) => $q
                ->where('department_name', 'like', "%{$search}%")
                ->orWhere('department_code', 'like', "%{$search}%")
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
        $cols     = ['department_id', 'department_name', 'department_code', 'company_id', 'status'];
        $col      = $cols[(int)($order[0]['column'] ?? 0)] ?? 'department_id';
        $dir      = ($order[0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($col, $dir);

        $filtered = $query->count();
        $rows     = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows->map(fn($d) => [
                'department_id'    => $d->department_id,
                'department_name'  => $d->department_name,
                'department_code'  => $d->department_code,
                'company_name'     => $d->company?->name ?? '—',
                'status'           => $d->status,
                'created_by_name'  => $d->creator?->name ?? '—',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'department_name' => ['required', 'string', 'max:60'],
            'department_code' => ['required', 'string', 'max:20'],
            'company_id'      => ['required', 'integer', 'exists:companies,id'],
            'status'          => ['required', 'in:A,D'],
        ]);

        $data['created_by'] = auth()->id();
        $data['is_deleted'] = 'N';

        $department = Department::create($data);
        ActivityLogger::log('created', $department, null, $department->getAttributes());

        return response()->json(['success' => true, 'message' => 'Department created successfully.', 'data' => ['id' => $department->department_id]]);
    }

    public function show(Department $department)
    {
        return response()->json($department);
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'department_name' => ['required', 'string', 'max:60'],
            'department_code' => ['required', 'string', 'max:20'],
            'company_id'      => ['required', 'integer', 'exists:companies,id'],
            'status'          => ['required', 'in:A,D'],
        ]);

        $data['updated_by'] = auth()->id();

        $old = $department->getAttributes();
        $department->update($data);
        ActivityLogger::log('updated', $department, $old, $department->getAttributes());

        return response()->json(['success' => true, 'message' => 'Department updated successfully.']);
    }

    public function destroy(Department $department)
    {
        $old = $department->getAttributes();
        $department->update(['is_deleted' => 'Y', 'updated_by' => auth()->id()]);
        ActivityLogger::log('deleted', $department, $old, $department->getAttributes());

        return response()->json(['success' => true, 'message' => 'Department deleted successfully.']);
    }
}
