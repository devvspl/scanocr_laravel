<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Ledger;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    public function index()
    {
        return view('panel.settings.ledgers');
    }

    public function data(Request $request)
    {
        $query = Ledger::with('creator')->notDeleted();

        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(fn($q) => $q
                ->where('ledger_name', 'like', "%{$search}%")
                ->orWhere('ledger_code', 'like', "%{$search}%")
                ->orWhere('ledger_head', 'like', "%{$search}%")
            );
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status === 'active' ? 'A' : 'D');
        }

        $total    = $query->count();
        $start    = (int) $request->get('start', 0);
        $length   = (int) $request->get('length', 25);
        $order    = $request->input('order', [['column' => 0, 'dir' => 'asc']]);
        $cols     = ['ledger_id', 'ledger_name', 'ledger_code', 'ledger_head', 'status'];
        $col      = $cols[(int)($order[0]['column'] ?? 0)] ?? 'ledger_id';
        $dir      = ($order[0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($col, $dir);

        $filtered = $query->count();
        $rows     = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows->map(fn($d) => [
                'ledger_id'        => $d->ledger_id,
                'ledger_name'      => $d->ledger_name,
                'ledger_code'      => $d->ledger_code,
                'ledger_head'      => $d->ledger_head,
                'status'           => $d->status,
                'created_by_name'  => $d->creator?->name ?? '—',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'ledger_name' => ['required', 'string', 'max:60'],
            'ledger_code' => ['required', 'string', 'max:20'],
            'ledger_head' => ['required', 'string', 'max:20'],
            'status'      => ['required', 'in:A,D'],
        ]);

        $data['created_by'] = auth()->id();
        $data['is_deleted'] = 'N';

        $ledger = Ledger::create($data);
        ActivityLogger::log('created', $ledger, null, $ledger->getAttributes());

        return response()->json(['success' => true, 'message' => 'Ledger created successfully.', 'data' => ['id' => $ledger->ledger_id]]);
    }

    public function show(Ledger $ledger)
    {
        return response()->json($ledger);
    }

    public function update(Request $request, Ledger $ledger)
    {
        $data = $request->validate([
            'ledger_name' => ['required', 'string', 'max:60'],
            'ledger_code' => ['required', 'string', 'max:20'],
            'ledger_head' => ['required', 'string', 'max:20'],
            'status'      => ['required', 'in:A,D'],
        ]);

        $data['updated_by'] = auth()->id();

        $old = $ledger->getAttributes();
        $ledger->update($data);
        ActivityLogger::log('updated', $ledger, $old, $ledger->getAttributes());

        return response()->json(['success' => true, 'message' => 'Ledger updated successfully.']);
    }

    public function destroy(Ledger $ledger)
    {
        $old = $ledger->getAttributes();
        $ledger->update(['is_deleted' => 'Y', 'updated_by' => auth()->id()]);
        ActivityLogger::log('deleted', $ledger, $old, $ledger->getAttributes());

        return response()->json(['success' => true, 'message' => 'Ledger deleted successfully.']);
    }
}
