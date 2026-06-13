<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\UnitOfMeasure;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class UnitOfMeasureController extends Controller
{
    public function index()
    {
        return view('panel.items.units');
    }

    public function data(Request $request)
    {
        $query = UnitOfMeasure::with('creator');

        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(fn($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('symbol', 'like', "%{$search}%")
                ->orWhere('type', 'like', "%{$search}%")
            );
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $total  = $query->count();
        $start  = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 10);
        $order  = $request->input('order', [['column' => 0, 'dir' => 'asc']]);
        $cols   = ['name', 'symbol', 'type', 'is_active'];
        $col    = $cols[(int)($order[0]['column'] ?? 0)] ?? 'name';
        $dir    = ($order[0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $query->orderBy(in_array($col, ['name', 'symbol', 'type', 'is_active']) ? $col : 'name', $dir);

        $filtered = $query->count();
        $rows     = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows->map(fn($u) => [
                'id'             => $u->id,
                'name'           => $u->name,
                'symbol'         => $u->symbol,
                'type'           => $u->type,
                'is_active'      => $u->is_active,
                'created_by_name'=> $u->creator?->name ?? '—',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255', 'unique:units_of_measure,name'],
            'symbol'    => ['required', 'string', 'max:20'],
            'type'      => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active']  = $request->boolean('is_active', true);
        $data['created_by'] = auth()->id();

        $unit = UnitOfMeasure::create($data);
        ActivityLogger::log('created', $unit, null, $unit->getAttributes());

        return response()->json(['success' => true, 'message' => 'Unit created.', 'data' => ['id' => $unit->id, 'name' => $unit->name, 'symbol' => $unit->symbol]]);
    }

    public function show(UnitOfMeasure $unit)
    {
        return response()->json([
            'id'        => $unit->id,
            'name'      => $unit->name,
            'symbol'    => $unit->symbol,
            'type'      => $unit->type,
            'is_active' => $unit->is_active,
        ]);
    }

    public function update(Request $request, UnitOfMeasure $unit)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255', 'unique:units_of_measure,name,' . $unit->id],
            'symbol'    => ['required', 'string', 'max:20'],
            'type'      => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        $old = $unit->getAttributes();
        $unit->update($data);
        ActivityLogger::log('updated', $unit, $old, $unit->getAttributes());

        return response()->json(['success' => true, 'message' => 'Unit updated.']);
    }

    public function destroy(UnitOfMeasure $unit)
    {
        if ($unit->products()->exists()) {
            return response()->json(['success' => false, 'message' => 'Cannot delete a unit that is used by products.'], 422);
        }
        $snapshot = $unit->getAttributes();
        $unit->delete();
        ActivityLogger::log('deleted', $unit, $snapshot, null);

        return response()->json(['success' => true, 'message' => 'Unit deleted.']);
    }
}
