<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\TaxRate;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class TaxRateController extends Controller
{
    public function index()
    {
        return view('panel.tax.tax-rates');
    }

    public function data(Request $request)
    {
        $query = TaxRate::with('creator');

        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(fn($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('type', 'like', "%{$search}%")
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
        $cols   = ['name', 'code', 'type', 'rate', 'is_active'];
        $col    = $cols[(int)($order[0]['column'] ?? 0)] ?? 'name';
        $dir    = ($order[0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy(in_array($col, $cols) ? $col : 'name', $dir);

        $filtered = $query->count();
        $rows     = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows->map(fn($t) => [
                'id'             => $t->id,
                'name'           => $t->name,
                'code'           => $t->code,
                'type'           => $t->type,
                'rate'           => number_format($t->rate, 2) . '%',
                'cgst'           => number_format($t->cgst, 2) . '%',
                'sgst'           => number_format($t->sgst, 2) . '%',
                'igst'           => number_format($t->igst, 2) . '%',
                'is_active'      => $t->is_active,
                'created_by_name'=> $t->creator?->name ?? '—',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:tax_rates,name'],
            'code'        => ['nullable', 'string', 'max:30'],
            'type'        => ['required', 'in:gst,igst,cess,tds,tcs,other'],
            'rate'        => ['required', 'numeric', 'min:0', 'max:100'],
            'cgst'        => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sgst'        => ['nullable', 'numeric', 'min:0', 'max:100'],
            'igst'        => ['nullable', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $data['is_active']  = $request->boolean('is_active', true);
        $data['created_by'] = auth()->id();

        // Auto-calculate CGST/SGST/IGST if not provided
        if (in_array($data['type'], ['gst'])) {
            $half = round($data['rate'] / 2, 2);
            $data['cgst'] = $data['cgst'] ?? $half;
            $data['sgst'] = $data['sgst'] ?? $half;
            $data['igst'] = $data['igst'] ?? $data['rate'];
        }

        $tax = TaxRate::create($data);
        ActivityLogger::log('created', $tax, null, $tax->getAttributes());

        return response()->json(['success' => true, 'message' => 'Tax rate created.', 'data' => ['id' => $tax->id, 'name' => $tax->name]]);
    }

    public function show(TaxRate $taxRate)
    {
        return response()->json([
            'id'          => $taxRate->id,
            'name'        => $taxRate->name,
            'code'        => $taxRate->code,
            'type'        => $taxRate->type,
            'rate'        => $taxRate->rate,
            'cgst'        => $taxRate->cgst,
            'sgst'        => $taxRate->sgst,
            'igst'        => $taxRate->igst,
            'description' => $taxRate->description,
            'is_active'   => $taxRate->is_active,
        ]);
    }

    public function update(Request $request, TaxRate $taxRate)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:tax_rates,name,' . $taxRate->id],
            'code'        => ['nullable', 'string', 'max:30'],
            'type'        => ['required', 'in:gst,igst,cess,tds,tcs,other'],
            'rate'        => ['required', 'numeric', 'min:0', 'max:100'],
            'cgst'        => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sgst'        => ['nullable', 'numeric', 'min:0', 'max:100'],
            'igst'        => ['nullable', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $old = $taxRate->getAttributes();
        $taxRate->update($data);
        ActivityLogger::log('updated', $taxRate, $old, $taxRate->getAttributes());

        return response()->json(['success' => true, 'message' => 'Tax rate updated.']);
    }

    public function destroy(TaxRate $taxRate)
    {
        if ($taxRate->hsnCodes()->exists()) {
            return response()->json(['success' => false, 'message' => 'Cannot delete a tax rate linked to HSN/SAC codes.'], 422);
        }
        $snapshot = $taxRate->getAttributes();
        $taxRate->delete();
        ActivityLogger::log('deleted', $taxRate, $snapshot, null);

        return response()->json(['success' => true, 'message' => 'Tax rate deleted.']);
    }
}
