<?php
namespace App\Http\Controllers\Generated;
use App\Http\Controllers\Controller;
use App\Models\Generated\Scanner;
use App\Exports\Generated\ScannerExport;
use App\Models\ExportLog;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
class ScannerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $scanners = Scanner::query()->when($search, fn($q) => $q->where(array_key_first((new Scanner)->getFillable() ? array_flip((new Scanner)->getFillable()) : []), 'like', "%{$search}%"))->latest()->paginate(15)->withQueryString();
        $exportLogs = ExportLog::where('model', 'Scanner')->latest()->take(20)->get();
        return view('generated/scanners.index', compact('scanners', 'search', 'exportLogs'));
    }
    public function export()
    {
        $data = Scanner::orderBy('id')->get();
        $hash = md5($data->toJson());
        $existing = ExportLog::where('model', 'Scanner')->where('data_hash', $hash)->latest()->first();
        if ($existing && Storage::disk('public')->exists($existing->file_path)) {
            return Storage::disk('public')->download($existing->file_path, $existing->file_name);
        }
        $fileName = 'scanners_' . now()->format('Ymd_His') . '.xlsx';
        $filePath = 'exports/' . $fileName;
        Excel::store(new ScannerExport, $filePath, 'public');
        ExportLog::create(['model' => 'Scanner', 'file_name' => $fileName, 'file_path' => $filePath, 'row_count' => $data->count(), 'data_hash' => $hash, 'user_id' => Auth::id()]);
        return Storage::disk('public')->download($filePath, $fileName);
    }
    public function exportDownload(ExportLog $exportLog)
    {
        abort_if($exportLog->model !== 'Scanner', 403);
        abort_unless(Storage::disk('public')->exists($exportLog->file_path), 404);
        return Storage::disk('public')->download($exportLog->file_path, $exportLog->file_name);
    }
    public function create()
    {
        $dynamicData = [];
        $dynamicData['document_type_options'] = \Illuminate\Support\Facades\DB::table('departments')->pluck('department_name', 'id');
        return view('generated/scanners.create', $dynamicData);
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string'],
            'document_no' => ['required', 'string'],
            'document_date' => ['required', 'date'],
            'document_type' => ['required', 'string'],
            'remarks' => ['nullable', 'string'],
            'upload_scan_copy' => ['nullable', 'array'],
            'other' => ['nullable', 'string'],
        ]);
        unset($data['upload_scan_copy']);
        $data['upload_scan_copy'] = [];
        if ($request->hasFile('upload_scan_copy')) {
            foreach ($request->file('upload_scan_copy') as $__f) {
                $data['upload_scan_copy'][] = $__f->store('uploads/scanners', 'public');
            }
        }
        $scanner = Scanner::create($data);
        return redirect()->route('generated.scanners.index')->with('success', 'Record created.');
    }
    public function show(Scanner $scanner) { return view('generated/scanners.show', compact('scanner')); }
    public function edit(Scanner $scanner)
    {
        $dynamicData = [];
        $dynamicData['document_type_options'] = \Illuminate\Support\Facades\DB::table('departments')->pluck('department_name', 'id');
        return view('generated/scanners.edit', array_merge(compact('scanner'), $dynamicData));
    }
    public function update(Request $request, Scanner $scanner)
    {
        $data = $request->validate([
            'title' => ['required', 'string'],
            'document_no' => ['required', 'string'],
            'document_date' => ['required', 'date'],
            'document_type' => ['required', 'string'],
            'remarks' => ['nullable', 'string'],
            'upload_scan_copy' => ['nullable', 'array'],
            'other' => ['nullable', 'string'],
        ]);
        unset($data['upload_scan_copy']);
        $__toRemove_upload_scan_copy = $request->input('upload_scan_copy_remove', []);
        $__existing_upload_scan_copy = array_values(array_filter($request->input('upload_scan_copy_keep', []), fn($p) => !in_array($p, $__toRemove_upload_scan_copy)));
        $__new_upload_scan_copy = [];
        if ($request->hasFile('upload_scan_copy')) {
            foreach ($request->file('upload_scan_copy') as $__f) {
                $__new_upload_scan_copy[] = $__f->store('uploads/scanners', 'public');
            }
        }
        $data['upload_scan_copy'] = array_merge($__existing_upload_scan_copy, $__new_upload_scan_copy);
        $scanner->update($data);
        return redirect()->route('generated.scanners.index')->with('success', 'Record updated.');
    }
    public function destroy(Scanner $scanner)
    {
        $scanner->delete();
        return redirect()->route('generated.scanners.index')->with('success', 'Record deleted.');
    }
}
