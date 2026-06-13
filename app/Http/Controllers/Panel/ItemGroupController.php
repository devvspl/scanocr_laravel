<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\ItemGroup;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class ItemGroupController extends Controller
{
    public function index()
    {
        return view('panel.items.item-groups');
    }

    public function data(Request $request)
    {
        $query = ItemGroup::with('creator');

        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(fn($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
            );
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $total  = $query->count();
        $start  = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 10);
        $order  = $request->input('order', [['column' => 0, 'dir' => 'asc']]);
        $cols   = ['name', 'description', 'is_active'];
        $col    = $cols[(int)($order[0]['column'] ?? 0)] ?? 'name';
        $dir    = ($order[0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $query->orderBy(in_array($col, ['name', 'description', 'is_active']) ? $col : 'name', $dir);

        $filtered = $query->count();
        $rows     = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows->map(fn($g) => [
                'id'             => $g->id,
                'name'           => $g->name,
                'description'    => $g->description,
                'is_active'      => $g->is_active,
                'created_by_name'=> $g->creator?->name ?? '—',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:item_groups,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active'   => ['nullable', 'boolean'],
        ]);
        $data['is_active']  = $request->boolean('is_active', true);
        $data['created_by'] = auth()->id();

        $group = ItemGroup::create($data);
        ActivityLogger::log('created', $group, null, $group->getAttributes());

        return response()->json(['success' => true, 'message' => 'Item group created.', 'data' => ['id' => $group->id, 'name' => $group->name]]);
    }

    public function show(ItemGroup $itemGroup)
    {
        return response()->json([
            'id'          => $itemGroup->id,
            'name'        => $itemGroup->name,
            'description' => $itemGroup->description,
            'is_active'   => $itemGroup->is_active,
        ]);
    }

    public function update(Request $request, ItemGroup $itemGroup)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:item_groups,name,' . $itemGroup->id],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active'   => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        $old = $itemGroup->getAttributes();
        $itemGroup->update($data);
        ActivityLogger::log('updated', $itemGroup, $old, $itemGroup->getAttributes());

        return response()->json(['success' => true, 'message' => 'Item group updated.']);
    }

    public function destroy(ItemGroup $itemGroup)
    {
        if ($itemGroup->products()->exists()) {
            return response()->json(['success' => false, 'message' => 'Cannot delete a group that has products.'], 422);
        }
        $snapshot = $itemGroup->getAttributes();
        $itemGroup->delete();
        ActivityLogger::log('deleted', $itemGroup, $snapshot, null);

        return response()->json(['success' => true, 'message' => 'Item group deleted.']);
    }
}
