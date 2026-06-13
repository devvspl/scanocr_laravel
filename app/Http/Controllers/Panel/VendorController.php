<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\AccountGroup;
use App\Models\Party;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index()
    {
        $groups = AccountGroup::orderBy('name')->get();
        return view('panel.parties.vendors', compact('groups'));
    }

    public function data(Request $request)
    {
        $query = Party::vendors()->with('group', 'creator');

        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(fn($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
            );
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $total  = $query->count();
        $start  = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 10);
        $order  = $request->input('order', [['column' => 0, 'dir' => 'asc']]);
        $cols   = ['code', 'name', 'phone', 'email', 'opening_balance', 'created_by_name', 'is_active'];
        $colIdx = (int) ($order[0]['column'] ?? 0);
        $col    = $cols[$colIdx] ?? 'name';
        $dir    = ($order[0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $sortable = ['code', 'name', 'phone', 'email', 'opening_balance', 'is_active'];
        $query->orderBy(in_array($col, $sortable) ? $col : 'name', $dir);

        $filtered = $query->count();
        $rows     = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows->map(fn($p) => $this->row($p)),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['type']       = 'vendor';
        $data['created_by'] = auth()->id();

        $party = Party::create($data);
        ActivityLogger::log('created', $party, null, $party->getAttributes());

        return response()->json(['success' => true, 'message' => 'Vendor created successfully.']);
    }

    public function show(Party $vendor)
    {
        return response()->json($this->row($vendor));
    }

    public function update(Request $request, Party $vendor)
    {
        $data = $this->validated($request, $vendor->id);
        $old  = $vendor->getAttributes();
        $vendor->update($data);
        ActivityLogger::log('updated', $vendor, $old, $vendor->getAttributes());

        return response()->json(['success' => true, 'message' => 'Vendor updated successfully.']);
    }

    public function destroy(Party $vendor)
    {
        $snapshot = $vendor->getAttributes();
        $vendor->delete();
        ActivityLogger::log('deleted', $vendor, $snapshot, null);

        return response()->json(['success' => true, 'message' => 'Vendor deleted.']);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'code'             => ['required', 'string', 'max:50', 'unique:parties,code,' . ($ignoreId ?? 'NULL') . ',id'],
            'name'             => ['required', 'string', 'max:255'],
            'display_name'     => ['nullable', 'string', 'max:255'],
            'email'            => ['nullable', 'email', 'max:255'],
            'phone'            => ['nullable', 'string', 'max:30'],
            'mobile'           => ['nullable', 'string', 'max:30'],
            'gstin'            => ['nullable', 'string', 'max:20'],
            'pan'              => ['nullable', 'string', 'max:20'],
            'billing_address'  => ['nullable', 'string', 'max:500'],
            'shipping_address' => ['nullable', 'string', 'max:500'],
            'city'             => ['nullable', 'string', 'max:100'],
            'state'            => ['nullable', 'string', 'max:100'],
            'country'          => ['nullable', 'string', 'max:100'],
            'pincode'          => ['nullable', 'string', 'max:20'],
            'opening_balance'  => ['nullable', 'numeric'],
            'balance_type'     => ['required', 'in:debit,credit'],
            'credit_limit'     => ['nullable', 'string', 'max:50'],
            'credit_days'      => ['nullable', 'integer', 'min:0'],
            'account_group_id' => ['nullable', 'exists:account_groups,id'],
            'is_active'        => ['nullable', 'boolean'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ]);

        $data['is_active']       = $request->boolean('is_active', true);
        $data['opening_balance'] = $data['opening_balance'] ?? 0;

        return $data;
    }

    private function row(Party $p): array
    {
        return [
            'id'               => $p->id,
            'code'             => $p->code,
            'name'             => $p->name,
            'display_name'     => $p->display_name,
            'email'            => $p->email,
            'phone'            => $p->phone,
            'mobile'           => $p->mobile,
            'gstin'            => $p->gstin,
            'pan'              => $p->pan,
            'billing_address'  => $p->billing_address,
            'shipping_address' => $p->shipping_address,
            'city'             => $p->city,
            'state'            => $p->state,
            'country'          => $p->country,
            'pincode'          => $p->pincode,
            'opening_balance'  => number_format((float) $p->opening_balance, 2),
            'balance_type'     => $p->balance_type,
            'credit_limit'     => $p->credit_limit,
            'credit_days'      => $p->credit_days,
            'account_group_id' => $p->account_group_id,
            'is_active'        => $p->is_active,
            'notes'            => $p->notes,
            'created_by_name'  => $p->creator?->name ?? '—',
        ];
    }
}
