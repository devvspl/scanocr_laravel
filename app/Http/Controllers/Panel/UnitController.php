<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        return view('panel.settings.units');
    }

    public function data(Request $request)
    {
        $query = Unit::with('creator')->notDeleted();

        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(fn($q) => $q
                ->where('unit_name', 'like', "%{$search}%")
                ->orWhere('unit_code', 'like', "%{$search}%")
            );
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status === 'active' ? 'A' : 'D');
        }

        $total    = $query->count();
        $start    = (int) $request->get('start', 0);
        $length   = (int) $request->get('length', 25);
        $order    = $request->input('order', [['column' => 0, 'dir' => 'asc']]);
        $cols     = ['unit_id', 'unit_name', 'unit_code', 'status'];
        $col      = $cols[(int)($order[0]['column'] ?? 0)] ?? 'unit_id';
        $dir      = ($order[0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($col, $dir);

        $filtered = $query->count();
        $rows     = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows->map(fn($d) => [
                'unit_id'          => $d->unit_id,
                'unit_name'        => $d->unit_name,
                'unit_code'        => $d->unit_code,
                'status'           => $d->status,
                'created_by_name'  => $d->creator?->name ?? '—',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'unit_name' => ['required', 'string', 'max:40'],
            'unit_code' => ['required', 'string', 'max:10'],
            'status'    => ['required', 'in:A,D'],
        ]);

        $data['created_by'] = auth()->id();
        $data['is_deleted'] = 'N';

        $unit = Unit::create($data);
        ActivityLogger::log('created', $unit, null, $unit->getAttributes());

        return response()->json(['success' => true, 'message' => 'Unit created successfully.', 'data' => ['id' => $unit->unit_id]]);
    }

    public function show(Unit $unit)
    {
        return response()->json($unit);
    }

    public function update(Request $request, Unit $unit)
    {
        $data = $request->validate([
            'unit_name' => ['required', 'string', 'max:40'],
            'unit_code' => ['required', 'string', 'max:10'],
            'status'    => ['required', 'in:A,D'],
        ]);

        $data['updated_by'] = auth()->id();

        $old = $unit->getAttributes();
        $unit->update($data);
        ActivityLogger::log('updated', $unit, $old, $unit->getAttributes());

        return response()->json(['success' => true, 'message' => 'Unit updated successfully.']);
    }

    public function destroy(Unit $unit)
    {
        $old = $unit->getAttributes();
        $unit->update(['is_deleted' => 'Y', 'updated_by' => auth()->id()]);
        ActivityLogger::log('deleted', $unit, $old, $unit->getAttributes());

        return response()->json(['success' => true, 'message' => 'Unit deleted successfully.']);
    }
}
