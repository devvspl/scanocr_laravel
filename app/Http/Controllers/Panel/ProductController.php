<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\ItemGroup;
use App\Models\Product;
use App\Models\UnitOfMeasure;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $groups = ItemGroup::where('is_active', true)->orderBy('name')->get();
        $units  = UnitOfMeasure::where('is_active', true)->orderBy('name')->get();
        return view('panel.items.products', compact('groups', 'units'));
    }

    public function data(Request $request)
    {
        $query = Product::with('group', 'unit', 'creator');

        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(fn($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('hsn_sac', 'like', "%{$search}%")
            );
        }

        if ($request->filled('group')) {
            $query->where('item_group_id', $request->group);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('unit')) {
            $query->where('unit_id', $request->unit);
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $total  = $query->count();
        $start  = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 10);
        $order  = $request->input('order', [['column' => 0, 'dir' => 'asc']]);
        $cols   = ['code', 'name', 'type', 'sale_price', 'purchase_price', 'tax_rate', 'is_active'];
        $col    = $cols[(int)($order[0]['column'] ?? 0)] ?? 'name';
        $dir    = ($order[0]['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $sortable = ['code', 'name', 'type', 'sale_price', 'purchase_price', 'tax_rate', 'is_active'];
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
        $data['created_by'] = auth()->id();

        $product = Product::create($data);
        ActivityLogger::log('created', $product, null, $product->getAttributes());

        return response()->json(['success' => true, 'message' => 'Product created successfully.']);
    }

    public function show(Product $product)
    {
        return response()->json($this->row($product));
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request, $product->id);
        $old  = $product->getAttributes();
        $product->update($data);
        ActivityLogger::log('updated', $product, $old, $product->getAttributes());

        return response()->json(['success' => true, 'message' => 'Product updated successfully.']);
    }

    public function destroy(Product $product)
    {
        $snapshot = $product->getAttributes();
        $product->delete();
        ActivityLogger::log('deleted', $product, $snapshot, null);

        return response()->json(['success' => true, 'message' => 'Product deleted.']);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'code'            => ['required', 'string', 'max:50', 'unique:products,code,' . ($ignoreId ?? 'NULL') . ',id'],
            'name'            => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string', 'max:500'],
            'type'            => ['required', 'in:goods,service'],
            'item_group_id'   => ['nullable', 'exists:item_groups,id'],
            'unit_id'         => ['nullable', 'exists:units_of_measure,id'],
            'hsn_sac'         => ['nullable', 'string', 'max:20'],
            'sale_price'      => ['nullable', 'numeric', 'min:0'],
            'purchase_price'  => ['nullable', 'numeric', 'min:0'],
            'tax_rate'        => ['nullable', 'numeric', 'min:0', 'max:100'],
            'opening_stock'   => ['nullable', 'numeric', 'min:0'],
            'reorder_level'   => ['nullable', 'numeric', 'min:0'],
            'track_inventory' => ['nullable', 'boolean'],
            'is_active'       => ['nullable', 'boolean'],
        ]);

        $data['is_active']       = $request->boolean('is_active', true);
        $data['track_inventory'] = $request->boolean('track_inventory', true);
        $data['sale_price']      = $data['sale_price']     ?? 0;
        $data['purchase_price']  = $data['purchase_price'] ?? 0;
        $data['tax_rate']        = $data['tax_rate']       ?? 0;
        $data['opening_stock']   = $data['opening_stock']  ?? 0;
        $data['reorder_level']   = $data['reorder_level']  ?? 0;

        return $data;
    }

    private function row(Product $p): array
    {
        return [
            'id'              => $p->id,
            'code'            => $p->code,
            'name'            => $p->name,
            'description'     => $p->description,
            'type'            => $p->type,
            'item_group_id'   => $p->item_group_id,
            'group_name'      => $p->group?->name ?? '—',
            'unit_id'         => $p->unit_id,
            'unit_symbol'     => $p->unit?->symbol ?? '—',
            'hsn_sac'         => $p->hsn_sac,
            'sale_price'      => number_format((float) $p->sale_price, 2),
            'purchase_price'  => number_format((float) $p->purchase_price, 2),
            'tax_rate'        => number_format((float) $p->tax_rate, 2),
            'opening_stock'   => $p->opening_stock,
            'reorder_level'   => $p->reorder_level,
            'track_inventory' => $p->track_inventory,
            'is_active'       => $p->is_active,
            'created_by_name' => $p->creator?->name ?? '—',
        ];
    }
}
