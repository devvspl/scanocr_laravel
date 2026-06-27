<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index()
    {
        return view('panel.settings.items');
    }

    public function data(Request $request)
    {
        $query = Item::with('creator')->notDeleted();

        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(fn($q) => $q
                ->where('item_name', 'like', "%{$search}%")
                ->orWhere('item_code', 'like', "%{$search}%")
            );
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status === 'active' ? 'A' : 'D');
        }

        $total    = $query->count();
        $start    = (int) $request->get('start', 0);
        $length   = (int) $request->get('length', 25);
        $order    = $request->input('order', [['column' => 0, 'dir' => 'asc']]);
        $cols     = ['item_id', 'item_name', 'item_code', 'focus_data', 'status'];
        $col      = $cols[(int)($order[0]['column'] ?? 0)] ?? 'item_id';
        $dir      = ($order[0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($col, $dir);

        $filtered = $query->count();
        $rows     = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows->map(fn($d) => [
                'item_id'          => $d->item_id,
                'item_name'        => $d->item_name,
                'item_code'        => $d->item_code,
                'focus_data'       => $d->focus_data,
                'status'           => $d->status,
                'created_by_name'  => $d->creator?->name ?? '—',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'item_name'  => ['required', 'string', 'max:255'],
            'item_code'  => ['required', 'string', 'max:60'],
            'focus_data' => ['required', 'in:N,Y'],
            'status'     => ['required', 'in:A,D'],
        ]);

        $data['created_by']  = auth()->id();
        $data['is_deleted']  = 'N';
        $data['Import_Flag'] = '0';

        $item = Item::create($data);
        ActivityLogger::log('created', $item, null, $item->getAttributes());

        return response()->json(['success' => true, 'message' => 'Item created successfully.', 'data' => ['id' => $item->item_id]]);
    }

    public function show(Item $item)
    {
        return response()->json($item);
    }

    public function update(Request $request, Item $item)
    {
        $data = $request->validate([
            'item_name'  => ['required', 'string', 'max:255'],
            'item_code'  => ['required', 'string', 'max:60'],
            'focus_data' => ['required', 'in:N,Y'],
            'status'     => ['required', 'in:A,D'],
        ]);

        $data['updated_by'] = auth()->id();

        $old = $item->getAttributes();
        $item->update($data);
        ActivityLogger::log('updated', $item, $old, $item->getAttributes());

        return response()->json(['success' => true, 'message' => 'Item updated successfully.']);
    }

    public function destroy(Item $item)
    {
        $old = $item->getAttributes();
        $item->update(['is_deleted' => 'Y', 'updated_by' => auth()->id()]);
        ActivityLogger::log('deleted', $item, $old, $item->getAttributes());

        return response()->json(['success' => true, 'message' => 'Item deleted successfully.']);
    }
}
