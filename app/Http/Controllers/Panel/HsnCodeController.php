<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\HsnCode;
use App\Models\TaxRate;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class HsnCodeController extends Controller
{
    public function index()
    {
        $taxRates = TaxRate::where('is_active', true)->orderBy('name')->get(['id', 'name', 'rate']);
        return view('panel.tax.hsn-codes', compact('taxRates'));
    }

    public function data(Request $request)
    {
        $query = HsnCode::with(['creator', 'taxRate']);

        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(fn($q) => $q
                ->where('code', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
            );
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $total  = $query->count();
        $start  = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 15);
        $order  = $request->input('order', [['column' => 0, 'dir' => 'asc']]);
        $cols   = ['code', 'type', 'description', 'is_active'];
        $col    = $cols[(int)($order[0]['column'] ?? 0)] ?? 'code';
        $dir    = ($order[0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy(in_array($col, $cols) ? $col : 'code', $dir);

        $filtered = $query->count();
        $rows     = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows->map(fn($h) => [
                'id'             => $h->id,
                'code'           => $h->code,
                'type'           => $h->type,
                'description'    => $h->description,
                'tax_rate_name'  => $h->taxRate ? $h->taxRate->name . ' (' . number_format($h->taxRate->rate, 0) . '%)' : '—',
                'is_active'      => $h->is_active,
                'created_by_name'=> $h->creator?->name ?? '—',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'        => ['required', 'string', 'max:20', 'unique:hsn_codes,code'],
            'type'        => ['required', 'in:hsn,sac'],
            'description' => ['required', 'string', 'max:500'],
            'tax_rate_id' => ['nullable', 'exists:tax_rates,id'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $data['is_active']  = $request->boolean('is_active', true);
        $data['created_by'] = auth()->id();

        $hsn = HsnCode::create($data);
        ActivityLogger::log('created', $hsn, null, $hsn->getAttributes());

        return response()->json(['success' => true, 'message' => 'HSN/SAC code created.', 'data' => ['id' => $hsn->id, 'code' => $hsn->code]]);
    }

    public function show(HsnCode $hsnCode)
    {
        return response()->json([
            'id'          => $hsnCode->id,
            'code'        => $hsnCode->code,
            'type'        => $hsnCode->type,
            'description' => $hsnCode->description,
            'tax_rate_id' => $hsnCode->tax_rate_id,
            'is_active'   => $hsnCode->is_active,
        ]);
    }

    public function update(Request $request, HsnCode $hsnCode)
    {
        $data = $request->validate([
            'code'        => ['required', 'string', 'max:20', 'unique:hsn_codes,code,' . $hsnCode->id],
            'type'        => ['required', 'in:hsn,sac'],
            'description' => ['required', 'string', 'max:500'],
            'tax_rate_id' => ['nullable', 'exists:tax_rates,id'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $old = $hsnCode->getAttributes();
        $hsnCode->update($data);
        ActivityLogger::log('updated', $hsnCode, $old, $hsnCode->getAttributes());

        return response()->json(['success' => true, 'message' => 'HSN/SAC code updated.']);
    }

    public function destroy(HsnCode $hsnCode)
    {
        $snapshot = $hsnCode->getAttributes();
        $hsnCode->delete();
        ActivityLogger::log('deleted', $hsnCode, $snapshot, null);

        return response()->json(['success' => true, 'message' => 'HSN/SAC code deleted.']);
    }
}
