<?php
namespace App\Http\Controllers\Generated;
use App\Http\Controllers\Controller;
use App\Models\Generated\Invoice;
use App\Exports\Generated\InvoiceExport;
use App\Models\ExportLog;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $invoices = Invoice::query()->with(['line_items'])->when($search, fn($q) => $q->where(array_key_first((new Invoice)->getFillable() ? array_flip((new Invoice)->getFillable()) : []), 'like', "%{$search}%"))->latest()->paginate(15)->withQueryString();
        $exportLogs = ExportLog::where('model', 'Invoice')->latest()->take(20)->get();
        return view('generated/invoices.index', compact('invoices', 'search', 'exportLogs'));
    }
    public function export()
    {
        $data = Invoice::orderBy('id')->get();
        $hash = md5($data->toJson());
        $existing = ExportLog::where('model', 'Invoice')->where('data_hash', $hash)->latest()->first();
        if ($existing && Storage::disk('public')->exists($existing->file_path)) {
            return Storage::disk('public')->download($existing->file_path, $existing->file_name);
        }
        $fileName = 'invoices_' . now()->format('Ymd_His') . '.xlsx';
        $filePath = 'exports/' . $fileName;
        Excel::store(new InvoiceExport, $filePath, 'public');
        ExportLog::create(['model' => 'Invoice', 'file_name' => $fileName, 'file_path' => $filePath, 'row_count' => $data->count(), 'data_hash' => $hash, 'user_id' => Auth::id()]);
        return Storage::disk('public')->download($filePath, $fileName);
    }
    public function exportDownload(ExportLog $exportLog)
    {
        abort_if($exportLog->model !== 'Invoice', 403);
        abort_unless(Storage::disk('public')->exists($exportLog->file_path), 404);
        return Storage::disk('public')->download($exportLog->file_path, $exportLog->file_name);
    }
    public function create()
    {
        $dynamicData = [];
        $dynamicData['buyer_options'] = \Illuminate\Support\Facades\DB::table('companies')->pluck('name', 'id');
        $dynamicData['vendor_options'] = \Illuminate\Support\Facades\DB::table('companies')->pluck('legal_name', 'id');
        return view('generated/invoices.create', $dynamicData);
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_no' => ['required', 'string'],
            'invoice_date' => ['required', 'date'],
            'purchase_order_no' => ['nullable', 'string'],
            'purchase_order_date' => ['nullable', 'date'],
            'buyer' => ['required', 'string'],
            'vendor' => ['required', 'string'],
            'buyer_address' => ['nullable', 'string'],
            'vendor_address' => ['nullable', 'string'],
            'dispatch_through' => ['nullable', 'string'],
            'dispatch_date' => ['nullable', 'date'],
            'line_items' => ['nullable', 'array'],
            'subtotal' => ['nullable', 'string'],
            'additional_discount' => ['nullable', 'numeric'],
            'round_off' => ['nullable', 'string'],
            'grand_total' => ['nullable', 'string'],
            'invoice_summary' => ['nullable', 'string'],
            'remark' => ['nullable', 'string'],
            'auto_approve' => ['nullable', 'string'],
        ]);
        $invoice = Invoice::create($data);
        if ($request->has('line_items')) {
            $invoice->line_items()->delete();
            $rows = collect($request->input('line_items'))->filter(function($row) {
                return !empty(array_filter($row, fn($v) => !is_null($v) && $v !== ''));
            });
            if ($rows->isNotEmpty()) $invoice->line_items()->createMany($rows->toArray());
        }
        return redirect()->route('generated.invoices.index')->with('success', 'Record created.');
    }
    public function show(Invoice $invoice) { return view('generated/invoices.show', compact('invoice')); }
    public function edit(Invoice $invoice)
    {
        $dynamicData = [];
        $dynamicData['buyer_options'] = \Illuminate\Support\Facades\DB::table('companies')->pluck('name', 'id');
        $dynamicData['vendor_options'] = \Illuminate\Support\Facades\DB::table('companies')->pluck('legal_name', 'id');
        return view('generated/invoices.edit', array_merge(compact('invoice'), $dynamicData));
    }
    public function update(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'invoice_no' => ['required', 'string'],
            'invoice_date' => ['required', 'date'],
            'purchase_order_no' => ['nullable', 'string'],
            'purchase_order_date' => ['nullable', 'date'],
            'buyer' => ['required', 'string'],
            'vendor' => ['required', 'string'],
            'buyer_address' => ['nullable', 'string'],
            'vendor_address' => ['nullable', 'string'],
            'dispatch_through' => ['nullable', 'string'],
            'dispatch_date' => ['nullable', 'date'],
            'line_items' => ['nullable', 'array'],
            'subtotal' => ['nullable', 'string'],
            'additional_discount' => ['nullable', 'numeric'],
            'round_off' => ['nullable', 'string'],
            'grand_total' => ['nullable', 'string'],
            'invoice_summary' => ['nullable', 'string'],
            'remark' => ['nullable', 'string'],
            'auto_approve' => ['nullable', 'string'],
        ]);
        $invoice->update($data);
        if ($request->has('line_items')) {
            $invoice->line_items()->delete();
            $rows = collect($request->input('line_items'))->filter(function($row) {
                return !empty(array_filter($row, fn($v) => !is_null($v) && $v !== ''));
            });
            if ($rows->isNotEmpty()) $invoice->line_items()->createMany($rows->toArray());
        }
        return redirect()->route('generated.invoices.index')->with('success', 'Record updated.');
    }
    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return redirect()->route('generated.invoices.index')->with('success', 'Record deleted.');
    }
}
