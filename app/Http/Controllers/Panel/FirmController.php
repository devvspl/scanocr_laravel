<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\MasterFirm;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class FirmController extends Controller
{
    public function index()
    {
        return view('panel.settings.firms');
    }

    public function data(Request $request)
    {
        $query = MasterFirm::with('creator')->where('is_deleted', 'N');

        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(fn($q) => $q
                ->where('firm_name', 'like', "%{$search}%")
                ->orWhere('firm_code', 'like', "%{$search}%")
                ->orWhere('gst', 'like', "%{$search}%")
            );
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status === 'active' ? 'A' : 'D');
        }

        if ($request->filled('firm_type')) {
            $query->where('firm_type', $request->firm_type);
        }

        $total    = $query->count();
        $start    = (int) $request->get('start', 0);
        $length   = (int) $request->get('length', 25);
        $order    = $request->input('order', [['column' => 0, 'dir' => 'asc']]);
        $cols     = ['firm_id', 'firm_name', 'firm_code', 'firm_type', 'gst', 'status'];
        $col      = $cols[(int)($order[0]['column'] ?? 0)] ?? 'firm_id';
        $dir      = ($order[0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($col, $dir);

        $filtered = $query->count();
        $rows     = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows->map(fn($d) => [
                'firm_id'          => $d->firm_id,
                'firm_name'        => $d->firm_name,
                'firm_code'        => $d->firm_code,
                'firm_type'        => $d->firm_type,
                'city_name'        => $d->city_name,
                'gst'              => $d->gst,
                'status'           => $d->status,
                'created_by_name'  => $d->creator?->name ?? '—',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'firm_name'  => ['required', 'string', 'max:200'],
            'firm_code'  => ['nullable', 'string', 'max:60'],
            'firm_type'  => ['required', 'in:Company,Vendor,Farmer'],
            'country_id' => ['required', 'integer'],
            'state_id'   => ['required', 'integer'],
            'city_name'  => ['nullable', 'string', 'max:60'],
            'pin_code'   => ['nullable', 'string', 'max:10'],
            'address'    => ['nullable', 'string'],
            'gst'        => ['nullable', 'string', 'max:30'],
            'status'     => ['required', 'in:A,D'],
        ]);

        $data['focus_data']  = 'N';
        $data['created_by']  = auth()->id();
        $data['is_deleted']  = 'N';
        $data['Import_Flag'] = '0';

        $firm = MasterFirm::create($data);
        ActivityLogger::log('created', $firm, null, $firm->getAttributes());

        return response()->json(['success' => true, 'message' => 'Firm created successfully.', 'data' => ['id' => $firm->firm_id]]);
    }

    public function show(MasterFirm $firm)
    {
        return response()->json($firm);
    }

    public function update(Request $request, MasterFirm $firm)
    {
        $data = $request->validate([
            'firm_name'  => ['required', 'string', 'max:200'],
            'firm_code'  => ['nullable', 'string', 'max:60'],
            'firm_type'  => ['required', 'in:Company,Vendor,Farmer'],
            'country_id' => ['required', 'integer'],
            'state_id'   => ['required', 'integer'],
            'city_name'  => ['nullable', 'string', 'max:60'],
            'pin_code'   => ['nullable', 'string', 'max:10'],
            'address'    => ['nullable', 'string'],
            'gst'        => ['nullable', 'string', 'max:30'],
            'status'     => ['required', 'in:A,D'],
        ]);

        $data['updated_by'] = auth()->id();

        $old = $firm->getAttributes();
        $firm->update($data);
        ActivityLogger::log('updated', $firm, $old, $firm->getAttributes());

        return response()->json(['success' => true, 'message' => 'Firm updated successfully.']);
    }

    public function destroy(MasterFirm $firm)
    {
        $old = $firm->getAttributes();
        $firm->update(['is_deleted' => 'Y', 'updated_by' => auth()->id()]);
        ActivityLogger::log('deleted', $firm, $old, $firm->getAttributes());

        return response()->json(['success' => true, 'message' => 'Firm deleted successfully.']);
    }
}
