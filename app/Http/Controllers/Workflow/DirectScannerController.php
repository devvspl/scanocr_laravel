<?php

namespace App\Http\Controllers\Workflow;

use App\Helpers\BillDateValidator;
use App\Http\Controllers\Controller;
use App\Models\ScanFile;
use App\Models\User;
use App\Models\Location;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Services\S3Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Exports\TempScanExport;
use App\Models\ExportLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class DirectScannerController extends Controller
{
    private const S3_DIRECT_FOLDER = 'uploads/direct';

    /**
     * GET /workflow/direct-scan
     * Single wizard view (step 1 = upload, step 2 = supporting).
     */
    public function index()
    {
        return view('panel.workflow.direct-scan.index');
    }

    /**
     * GET /workflow/direct-scan/locations?q=&page=
     * Paginated, searchable location list for Select2.
     */
    public function locationsSelect(Request $request)
    {
        $q = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per = 20;
        $query = Location::active()
            ->orderBy('location_name');

        if ($q !== '') {
            $query->where(function ($qb) use ($q) {
                $qb->where('location_name', 'like', "%{$q}%")
                    ->orWhere('location_code', 'like', "%{$q}%");
            });
        }

        $total = $query->count();
        $results = $query->offset(($page - 1) * $per)
            ->limit($per)
            ->get(['location_id as id', 'location_name as text']);

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => ($page * $per) < $total],
        ]);
    }

    /**
     * GET /workflow/direct-scan/bill-approvers?location_id=&q=&page=
     * Paginated, searchable bill-approver list for Select2.
     */
    public function getBillApproversForLocation(Request $request)
    {
        $locationId = (int) $request->query('location_id', 0);
        $q = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per = 20;

        $query = User::role('Bill Approval')
            ->where('is_active', true)
            ->orderBy('name');

        if ($locationId) {
            $query->where(function ($qb) use ($locationId) {
                $qb->whereHas('locationAccess', fn($la) =>
                    $la->where('location_id', $locationId)->where('has_access', true))
                    ->orWhereDoesntHave('locationAccess');
            });
        }

        if ($q !== '') {
            $query->where('name', 'like', "%{$q}%");
        }

        $total = $query->count();
        $results = $query->offset(($page - 1) * $per)
            ->limit($per)
            ->get(['id', 'name as text']);

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => ($page * $per) < $total],
        ]);
    }

    /**
     * GET /workflow/direct-scan/doc-types?q=&page=
     * Paginated, searchable doc-type list for Select2.
     */
    public function docTypesSelect(Request $request)
    {
        $q = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per = 20;

        $query = DB::table('supp_document_type_master')
            ->where('IsActive', 1)
            ->orderBy('DocTypeName');

        if ($q !== '') {
            $query->where('DocTypeName', 'like', "%{$q}%");
        }

        $total = $query->count();
        $results = $query->offset(($page - 1) * $per)
            ->limit($per)
            ->get(['DocTypeId as id', 'DocTypeName as text']);

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => ($page * $per) < $total],
        ]);
    }

    /**
     * GET /workflow/direct-scan/companies?q=&page=
     * Paginated, searchable company list for Select2.
     */
    public function companiesSelect(Request $request)
    {
        $q = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per = 20;

        $query = Company::where('is_active', true)
            ->orderBy('name');

        if ($q !== '') {
            $query->where('name', 'like', "%{$q}%");
        }

        $total = $query->count();
        $results = $query->offset(($page - 1) * $per)
            ->limit($per)
            ->get(['id', 'name as text']);

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => ($page * $per) < $total],
        ]);
    }

    /**
     * GET /workflow/direct-scan/financial-years?q=&page=
     * Paginated, searchable financial year list for Select2.
     */
    public function financialYearsSelect(Request $request)
    {
        $q = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per = 20;

        $query = FinancialYear::orderByDesc('start_date');

        if ($q !== '') {
            $query->where('label', 'like', "%{$q}%");
        }

        $total = $query->count();
        $results = $query->offset(($page - 1) * $per)
            ->limit($per)
            ->get(['id', 'label as text']);

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => ($page * $per) < $total],
        ]);
    }

    /**
     * GET /workflow/direct-scan/vendors?q=&page=
     * Paginated, searchable vendor list from master_firm table for Select2.
     */
    public function vendorsSelect(Request $request)
    {
        $q = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per = 20;

        $query = \App\Models\MasterFirm::active()
            ->vendors()
            ->orderBy('firm_name');

        if ($q !== '') {
            $query->where(function ($subQuery) use ($q) {
                $subQuery->where('firm_name', 'like', "%{$q}%")
                         ->orWhere('firm_code', 'like', "%{$q}%");
            });
        }

        $total = $query->count();
        $results = $query->offset(($page - 1) * $per)
            ->limit($per)
            ->get(['firm_id as id', 'firm_name', 'firm_code'])
            ->map(function ($firm) {
                return [
                    'id' => $firm->id,
                    'text' => $firm->firm_code 
                        ? "{$firm->firm_name} ({$firm->firm_code})" 
                        : $firm->firm_name,
                    'firm_name_clean' => preg_replace('/[^A-Za-z0-9 ]/', '', strtoupper($firm->firm_name))
                ];
            });

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => ($page * $per) < $total],
        ]);
    }

    /**
     * GET /workflow/direct-scan/data  (AJAX — DataTables server-side)
     * Returns the user's own direct scans.
     */
    public function data(Request $request)
    {
        $userId = Auth::id();

        $query = DB::table('scan_file as s')
            ->leftJoin('master_work_location as l', 'l.location_id', '=', 's.Location')
            ->leftJoin('users as u', 'u.id', '=', 's.Bill_Approver')
            ->where('s.Group_Id', Company::currentId())
            ->where('s.year_id', FinancialYear::currentId())
            ->where('s.Scan_By', $userId)
            ->where('s.Scan_Complete', 'Y')
            ->where('s.Is_Deleted', 'N')
            ->whereNull('s.Temp_Scan'); // Direct scans don't have Temp_Scan set

        $query->select([
            's.Scan_Id',
            's.Location',
            's.Bill_Approver',
            'l.location_name',
            's.File',
            's.File_Location',
            's.Document_name',
            's.Scan_Date',
            's.Final_Submit',
            's.Bill_Approved',
            's.temp_scan_reject',
            'u.name as approver_name',
            's.Bill_Approver_Remark',
        ]);

        // Tab filtering
        $tab = $request->input('tab', 'all');
        switch ($tab) {
            case 'pending':
                $query->where('s.Bill_Approved', 'N');
                break;
            case 'approved':
                $query->where('s.Bill_Approved', 'Y');
                break;
            case 'rejected':
                $query->where(function ($q) {
                    $q->where('s.Bill_Approved', 'R')
                        ->orWhere('s.temp_scan_reject', 'Y');
                });
                break;
        }

        // Date range filtering
        if ($request->filled('from_date')) {
            $query->whereDate('s.Scan_Date', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('s.Scan_Date', '<=', $request->input('to_date'));
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn(
                'Scan_Date',
                fn($r) =>
                $r->Scan_Date
                ? \Carbon\Carbon::parse($r->Scan_Date)->format('d M Y')
                : '—'
            )
            ->addColumn(
                'final_submit_badge',
                fn($r) =>
                $r->Final_Submit === 'Y'
                ? '<span class="badge-yes">Yes</span>'
                : '<span class="badge-no">No</span>'
            )
            ->addColumn('bill_approved_badge', function ($r) {
                if ($r->temp_scan_reject === 'Y' || $r->Bill_Approved === 'R') {
                    return '<span class="badge-rejected">Rejected</span>';
                }

                return match ($r->Bill_Approved) {
                    'Y' => '<span class="badge-approved">Approved</span>',
                    'N' => '<span class="badge-pending">Pending</span>',
                    default => '<span class="badge-pending">Pending</span>',
                };
            })
            ->addColumn(
                'actions',
                fn($r) =>
                '<div class="dt-actions" data-id="' . $r->Scan_Id . '" '
                . 'data-final="' . $r->Final_Submit . '" '
                . 'data-file="' . e($r->File) . '" '
                . 'data-url="' . e($r->File_Location) . '"></div>'
            )
            ->rawColumns(['final_submit_badge', 'bill_approved_badge', 'actions'])
            ->make(true);
    }

    /**
     * GET /workflow/direct-scan/tab-counts  (AJAX JSON)
     * Returns counts for each tab.
     */
    public function tabCounts()
    {
        $userId = Auth::id();

        $baseQuery = DB::table('scan_file')
            ->where('Group_Id', Company::currentId())
            ->where('year_id', FinancialYear::currentId())
            ->where('Scan_By', $userId)
            ->where('Scan_Complete', 'Y')
            ->where('Is_Deleted', 'N')
            ->whereNull('Temp_Scan'); // Direct scans don't have Temp_Scan set

        return response()->json([
            'all' => (clone $baseQuery)->count(),

            'pending' => (clone $baseQuery)
                ->where('Bill_Approved', 'N')
                ->count(),

            'approved' => (clone $baseQuery)
                ->where('Bill_Approved', 'Y')
                ->count(),

            'rejected' => (clone $baseQuery)
                ->where(function ($q) {
                    $q->where('Bill_Approved', 'R')
                        ->orWhere('temp_scan_reject', 'Y');
                })
                ->count(),
        ]);
    }

    /**
     * GET /workflow/direct-scan/{scan}/support-list  (AJAX JSON)
     * Returns the list of supporting files for a scan.
     */
    public function supportList(ScanFile $scan)
    {
        $this->authorizeOwner($scan);

        $files = DB::table('support_file as sf')
            ->leftJoin('supp_document_type_master as dt', 'dt.DocTypeId', '=', 'sf.DocTypeId')
            ->where('sf.Scan_Id', $scan->Scan_Id)
            ->select(['sf.Support_Id', 'sf.File', 'sf.File_Ext', 'sf.File_Location', 'dt.DocTypeName as doc_type_name'])
            ->get();

        return response()->json(['data' => $files]);
    }

    /**
     * POST /workflow/direct-scan  (AJAX JSON)
     * Upload main file → S3, insert scan_file row.
     */
    public function store(Request $request, S3Service $s3)
    {
        $request->validate(array_merge([
            'location' => 'required|integer|exists:master_work_location,location_id',
            'bill_approver' => 'required|integer|exists:users,id',
            'vendor_id' => 'required|integer|exists:master_firm,firm_id',
            'bill_no' => 'required|string|max:100',
            'document_name' => 'required|string|max:255',
            'main_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:15360',
        ], BillDateValidator::rules()));

        $user = Auth::user();
        $file = $request->file('main_file');
        $ext = $file->getClientOriginalExtension();
        $newName = time() . '.' . $ext;

        $result = $s3->upload($file, self::S3_DIRECT_FOLDER, $newName);

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => 'S3 Upload Error: ' . $result['error']], 422);
        }

        $scan = ScanFile::create([
            'Group_Id' => Company::currentId(),
            'year_id' => FinancialYear::currentId(),
            'Location' => $request->input('location'),
            'Bill_Approver' => $request->input('bill_approver'),
            'Scan_By' => $user->id,
            'bill_voucher_date' => $request->input('bill_date'),
            'firm_id' => $request->input('vendor_id'),
            'bill_no_voucher_no' => $request->input('bill_no'),
            'Document_name' => $request->input('document_name'),
            'Scan_Complete' => 'Y',
            'DocType_Id' => 0,
            'department_id' => 0,
            'File' => $newName,
            'File_Ext' => $ext,
            'File_Location' => $result['url'],
            'File_Location1' => $result['key'],
            'Year' => date('Y'),
            'Scan_Date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'scan' => [
                'id' => $scan->Scan_Id,
                'file' => $scan->File,
                'file_url' => $scan->File_Location,
                'document_name' => $scan->Document_name,
                'scan_date' => \Carbon\Carbon::parse($scan->Scan_Date)->format('d M Y H:i'),
            ],
        ]);
    }

    /**
     * POST /workflow/direct-scan/{scan}/supporting  (AJAX JSON)
     * Upload one supporting file → S3, insert support_file row.
     */
    public function storeSupporting(Request $request, ScanFile $scan, S3Service $s3)
    {
        $this->authorizeOwner($scan);

        $request->validate([
            'support_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:15360',
            'doc_type_id' => 'nullable|integer',
        ]);

        $file = $request->file('support_file');
        $ext = $file->getClientOriginalExtension();
        $newName = time() . '.' . $ext;

        $result = $s3->upload($file, self::S3_DIRECT_FOLDER, $newName);

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => 'S3 Upload Error: ' . $result['error']], 422);
        }

        $docTypeName = null;
        $docTypeId = $request->input('doc_type_id');
        if ($docTypeId) {
            $docTypeName = DB::table('supp_document_type_master')->where('DocTypeId', $docTypeId)->value('DocTypeName');
        }

        $supportId = DB::table('support_file')->insertGetId([
            'Scan_Id' => $scan->Scan_Id,
            'File' => $newName,
            'File_Ext' => $ext,
            'File_Location' => $result['url'],
            'File_Location1' => $result['key'],
            'DocTypeId' => $docTypeId,
        ]);

        return response()->json([
            'success' => true,
            'file' => [
                'Support_Id' => $supportId,
                'File' => $newName,
                'File_Ext' => $ext,
                'File_Location' => $result['url'],
                'doc_type_name' => $docTypeName,
            ],
        ]);
    }

    /**
     * POST /workflow/direct-scan/{scan}/final-submit  (AJAX JSON)
     */
    public function finalSubmit(ScanFile $scan)
    {
        $this->authorizeOwner($scan);
        $scan->update(['Final_Submit' => 'Y']);
        return response()->json(['success' => true]);
    }

    /**
     * POST /workflow/direct-scan/{scan}/resubmit  (AJAX JSON)
     * Resubmit a rejected scan for approval.
     */
    public function resubmit(Request $request, ScanFile $scan)
    {
        $this->authorizeOwner($scan);

        $updates = [
            'Bill_Approved' => 'N',
            'temp_scan_reject' => 'N',
            'Bill_Approver_Remark' => null,
        ];

        if ($request->filled('bill_approver')) {
            $updates['Bill_Approver'] = $request->input('bill_approver');
        }

        $scan->update($updates);

        return response()->json(['success' => true, 'message' => 'Scan resubmitted for approval']);
    }

    /**
     * DELETE /workflow/direct-scan/{scan}  (AJAX JSON)
     */
    public function destroy(ScanFile $scan)
    {
        $this->authorizeOwner($scan);

        DB::transaction(function () use ($scan) {
            DB::table('support_file')->where('Scan_Id', $scan->Scan_Id)->delete();
            $scan->update([
                'Is_Deleted' => 'Y',
                'Delete_Date' => now(),
                'Deleted_By' => Auth::id(),
            ]);
        });

        return response()->json(['success' => true]);
    }

    /**
     * DELETE /workflow/direct-scan/{scan}/support/{supportId}  (AJAX JSON)
     */
    public function destroySupport(ScanFile $scan, int $supportId)
    {
        $this->authorizeOwner($scan);

        DB::table('support_file')
            ->where('Support_Id', $supportId)
            ->where('Scan_Id', $scan->Scan_Id)
            ->delete();

        return response()->json(['success' => true]);
    }

    /**
     * GET /workflow/direct-scan/export/logs  (AJAX JSON)
     * Returns the user's own export history.
     */
    public function exportLogs(): \Illuminate\Http\JsonResponse
    {
        $logs = ExportLog::where('model', 'DirectScan')
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'file_name', 'row_count', 'created_at']);

        return response()->json(['data' => $logs]);
    }

    /**
     * GET /workflow/direct-scan/export/excel
     * Export direct scans to Excel.
     */
    public function exportExcel()
    {
        $userId = Auth::id();
        $fileName = "direct_scans_" . date('Y_m_d_H_i_s') . ".xlsx";

        // Log the export
        ExportLog::create([
            'model' => 'DirectScan',
            'user_id' => $userId,
            'file_name' => $fileName,
            'row_count' => 0, // Will be updated by the export class if needed
        ]);

        return Excel::download(new TempScanExport($userId, 'direct'), $fileName);
    }

    /**
     * GET /workflow/direct-scan/export/pdf
     * Export direct scans to PDF.
     */
    public function exportPdf()
    {
        $userId = Auth::id();
        
        $scans = DB::table('scan_file as s')
            ->leftJoin('master_work_location as l', 'l.location_id', '=', 's.Location')
            ->leftJoin('users as u', 'u.id', '=', 's.Bill_Approver')
            ->where('s.Group_Id', Company::currentId())
            ->where('s.year_id', FinancialYear::currentId())
            ->where('s.Scan_By', $userId)
            ->where('s.Scan_Complete', 'Y')
            ->where('s.Is_Deleted', 'N')
            ->whereNull('s.Temp_Scan')
            ->select([
                's.Scan_Id',
                'l.location_name',
                's.Document_name',
                's.File',
                's.Scan_Date',
                's.Final_Submit',
                's.Bill_Approved',
                'u.name as approver_name',
                's.Bill_Approver_Remark',
            ])
            ->orderByDesc('s.Scan_Date')
            ->get();

        $fileName = "direct_scans_" . date('Y_m_d_H_i_s') . ".pdf";

        // Log the export
        ExportLog::create([
            'model' => 'DirectScan',
            'user_id' => $userId,
            'file_name' => $fileName,
            'row_count' => $scans->count(),
        ]);

        $pdf = Pdf::loadView('exports.direct-scan-pdf', compact('scans'));
        return $pdf->download($fileName);
    }

    /**
     * POST /workflow/direct-scan/{scan}/replace (AJAX JSON)
     * Replace the main scan file.
     */
    public function replaceFile(Request $request, ScanFile $scan, S3Service $s3)
    {
        $this->authorizeOwner($scan);

        $request->validate([
            'replacement_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:15360',
        ]);

        $file = $request->file('replacement_file');
        $ext = $file->getClientOriginalExtension();
        $newName = time() . '.' . $ext;

        $result = $s3->upload($file, self::S3_DIRECT_FOLDER, $newName);

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => 'S3 Upload Error: ' . $result['error']], 422);
        }

        // Update the scan with new file details
        $scan->update([
            'File' => $newName,
            'File_Ext' => $ext,
            'File_Location' => $result['url'],
            'File_Location1' => $result['key'],
        ]);

        return response()->json([
            'success' => true,
            'file' => [
                'name' => $newName,
                'url' => $result['url'],
            ],
        ]);
    }

    /**
     * Authorization check
     */
    private function authorizeOwner(ScanFile $scan): void
    {
        $user = Auth::user();
        if (
            $scan->Scan_By !== $user->id
            && !$user->hasAnyRole(['Super Admin', 'Classification'])
        ) {
            abort(403);
        }
    }
}
