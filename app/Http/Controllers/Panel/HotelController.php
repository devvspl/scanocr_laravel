<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class HotelController extends Controller
{
    public function index()
    {
        return view('panel.settings.hotels');
    }

    public function data(Request $request)
    {
        $query = Hotel::with('creator')->notDeleted();

        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(fn($q) => $q
                ->where('hotel_name', 'like', "%{$search}%")
                ->orWhere('city_name', 'like', "%{$search}%")
                ->orWhere('address', 'like', "%{$search}%")
            );
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status === 'active' ? 'A' : 'D');
        }

        $total    = $query->count();
        $start    = (int) $request->get('start', 0);
        $length   = (int) $request->get('length', 25);
        $order    = $request->input('order', [['column' => 0, 'dir' => 'asc']]);
        $cols     = ['hotel_id', 'hotel_name', 'city_name', 'state_id', 'status'];
        $col      = $cols[(int)($order[0]['column'] ?? 0)] ?? 'hotel_id';
        $dir      = ($order[0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($col, $dir);

        $filtered = $query->count();
        $rows     = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows->map(fn($d) => [
                'hotel_id'         => $d->hotel_id,
                'hotel_name'       => $d->hotel_name,
                'city_name'        => $d->city_name,
                'address'          => $d->address,
                'state_id'         => $d->state_id,
                'status'           => $d->status,
                'created_by_name'  => $d->creator?->name ?? '—',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'hotel_name' => ['required', 'string', 'max:100'],
            'city_name'  => ['required', 'string', 'max:70'],
            'state_id'   => ['nullable', 'integer'],
            'address'    => ['nullable', 'string'],
            'status'     => ['required', 'in:A,D'],
        ]);

        $data['created_by'] = auth()->id();
        $data['is_deleted'] = 'N';

        $hotel = Hotel::create($data);
        ActivityLogger::log('created', $hotel, null, $hotel->getAttributes());

        return response()->json(['success' => true, 'message' => 'Hotel created successfully.', 'data' => ['id' => $hotel->hotel_id]]);
    }

    public function show(Hotel $hotel)
    {
        return response()->json($hotel);
    }

    public function update(Request $request, Hotel $hotel)
    {
        $data = $request->validate([
            'hotel_name' => ['required', 'string', 'max:100'],
            'city_name'  => ['required', 'string', 'max:70'],
            'state_id'   => ['nullable', 'integer'],
            'address'    => ['nullable', 'string'],
            'status'     => ['required', 'in:A,D'],
        ]);

        $data['updated_by'] = auth()->id();

        $old = $hotel->getAttributes();
        $hotel->update($data);
        ActivityLogger::log('updated', $hotel, $old, $hotel->getAttributes());

        return response()->json(['success' => true, 'message' => 'Hotel updated successfully.']);
    }

    public function destroy(Hotel $hotel)
    {
        $old = $hotel->getAttributes();
        $hotel->update(['is_deleted' => 'Y', 'updated_by' => auth()->id()]);
        ActivityLogger::log('deleted', $hotel, $old, $hotel->getAttributes());

        return response()->json(['success' => true, 'message' => 'Hotel deleted successfully.']);
    }
}
