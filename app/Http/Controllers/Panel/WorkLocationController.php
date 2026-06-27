<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\WorkLocation;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class WorkLocationController extends Controller
{
    public function index()
    {
        return view('panel.settings.work-locations');
    }

    public function data(Request $request)
    {
        $query = WorkLocation::with('creator')->notDeleted();

        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(fn($q) => $q
                ->where('location_name', 'like', "%{$search}%")
                ->orWhere('location_code', 'like', "%{$search}%")
            );
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status === 'active' ? 'A' : 'D');
        }

        $total    = $query->count();
        $start    = (int) $request->get('start', 0);
        $length   = (int) $request->get('length', 25);
        $order    = $request->input('order', [['column' => 0, 'dir' => 'asc']]);
        $cols     = ['location_id', 'location_name', 'location_code', 'status'];
        $col      = $cols[(int)($order[0]['column'] ?? 0)] ?? 'location_id';
        $dir      = ($order[0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($col, $dir);

        $filtered = $query->count();
        $rows     = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows->map(fn($d) => [
                'location_id'      => $d->location_id,
                'location_name'    => $d->location_name,
                'location_code'    => $d->location_code,
                'status'           => $d->status,
                'created_by_name'  => $d->creator?->name ?? '—',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'location_name' => ['required', 'string', 'max:60'],
            'location_code' => ['required', 'string', 'max:50'],
            'status'        => ['required', 'in:A,D'],
        ]);

        $data['created_by'] = auth()->id();
        $data['is_deleted'] = 'N';

        $location = WorkLocation::create($data);
        ActivityLogger::log('created', $location, null, $location->getAttributes());

        return response()->json(['success' => true, 'message' => 'Work location created successfully.', 'data' => ['id' => $location->location_id]]);
    }

    public function show(WorkLocation $workLocation)
    {
        return response()->json($workLocation);
    }

    public function update(Request $request, WorkLocation $workLocation)
    {
        $data = $request->validate([
            'location_name' => ['required', 'string', 'max:60'],
            'location_code' => ['required', 'string', 'max:50'],
            'status'        => ['required', 'in:A,D'],
        ]);

        $data['updated_by'] = auth()->id();

        $old = $workLocation->getAttributes();
        $workLocation->update($data);
        ActivityLogger::log('updated', $workLocation, $old, $workLocation->getAttributes());

        return response()->json(['success' => true, 'message' => 'Work location updated successfully.']);
    }

    public function destroy(WorkLocation $workLocation)
    {
        $old = $workLocation->getAttributes();
        $workLocation->update(['is_deleted' => 'Y', 'updated_by' => auth()->id()]);
        ActivityLogger::log('deleted', $workLocation, $old, $workLocation->getAttributes());

        return response()->json(['success' => true, 'message' => 'Work location deleted successfully.']);
    }
}
