<?php

namespace App\Http\Controllers\Workflow;

use App\Helpers\BillDateValidator;
use App\Http\Controllers\Controller;
use App\Models\ScanActionLog;
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

class TempScannerController extends Controller
{
    private const S3_TEMP_FOLDER = 'uploads/temp';

    /**
     * GET /workflow/temp-scan
     * Single wizard view (step 1 = upload, step 2 = supporting).
     */
    public function index()
    {
        return view('panel.workflow.temp-scan.index');
    }

    /**
     * GET /workflow/temp-scan/locations?q=&page=
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
     * GET /workflow/temp-scan/bill-approvers?location_id=&q=&page=
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
     * GET /workflow/temp-scan/doc-types?q=&page=
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
     * GET /workflow/temp-scan/data  (AJAX — DataTables server-side)
     * Returns the user's own pending temp scans.
     */
    public function data(Request $request)
    {
        $userId = Auth::id();

        $query = DB::table('scan_file as s')
            ->leftJoin('master_work_location as l', 'l.location_id', '=', 's.Location')
            ->leftJoin('users as u', 'u.id', '=', 's.Bill_Approver')
            ->where('s.Group_Id', Company::currentId())
            ->where('s.year_id', FinancialYear::currentId())
            ->where('s.Temp_Scan', 'Y')
            ->where('s.Temp_Scan_By', $userId)
            ->where('s.Is_Deleted', 'N');

        $query->select([
            's.Scan_Id',
            's.Location',
            's.Bill_Approver',
            'l.location_name',
            's.File',
            's.File_Location',
            's.Temp_Scan_Date',
            's.Final_Submit',
            's.Bill_Approved',
            's.temp_scan_reject',
            'u.name as approver_name',
            's.Bill_Approver_Remark',
        ]);



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



        if ($request->filled('from_date')) {
            $query->whereDate('s.Temp_Scan_Date', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('s.Temp_Scan_Date', '<=', $request->input('to_date'));
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn(
                'Temp_Scan_Date',
                fn($r) =>
                $r->Temp_Scan_Date
                ? \Carbon\Carbon::parse($r->Temp_Scan_Date)->format('d M Y')
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
     * GET /workflow/temp-scan/tab-counts  (AJAX JSON)
     * Returns counts for each tab.
     */
    public function tabCounts()
    {
        $userId = Auth::id();

        $baseQuery = DB::table('scan_file')
            ->where('Group_Id', Company::currentId())
            ->where('year_id', FinancialYear::currentId())
            ->where('Temp_Scan', 'Y')
            ->where('Temp_Scan_By', $userId)
            ->where('Is_Deleted', 'N');

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
     * GET /workflow/temp-scan/{scan}/support-list  (AJAX JSON)
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
     * POST /workflow/temp-scan  (AJAX JSON)
     * Upload main file → S3, insert scan_file row.
     */
    public function store(Request $request, S3Service $s3)
    {
        $request->validate(array_merge([
            'location'     => 'required|integer|exists:master_work_location,location_id',
            'bill_approver'=> 'required|integer|exists:users,id',
            'main_file'    => 'required|file|mimes:jpg,jpeg,png,pdf|max:15360',
        ], BillDateValidator::rules()));

        $user = Auth::user();
        $file = $request->file('main_file');
        $ext = $file->getClientOriginalExtension();
        $newName = time() . '.' . $ext;

        $result = $s3->upload($file, self::S3_TEMP_FOLDER, $newName);

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => 'S3 Upload Error: ' . $result['error']], 422);
        }

        $scan = ScanFile::create([
            'Group_Id' => Company::currentId(),
            'year_id' => FinancialYear::currentId(),
            'Location'          => $request->input('location'),
            'Bill_Approver'     => $request->input('bill_approver'),
            'bill_voucher_date' => $request->input('bill_date'),
            'bill_date' => $request->input('bill_date'),
            'Temp_Scan_By'      => $user->id,
            'Temp_Scan'         => 'Y',
            'Scan_Complete'     => 'N',
            'DocType_Id'        => 0,
            'department_id'     => 0,
            'File'              => $newName,
            'File_Ext'          => $ext,
            'File_Location'     => $result['url'],
            'File_Location1'    => $result['key'],
            'Year'              => date('Y'),
            'Temp_Scan_Date'    => now(),
        ]);

        ScanActionLog::log($scan->Scan_Id, 'temp_scan_uploaded', 'Temp Scan Uploaded');

        return response()->json([
            'success' => true,
            'scan' => [
                'id' => $scan->Scan_Id,
                'file' => $scan->File,
                'file_url' => $scan->File_Location,
                'scan_date' => \Carbon\Carbon::parse($scan->Temp_Scan_Date)->format('d M Y H:i'),
            ],
        ]);
    }

    /**
     * POST /workflow/temp-scan/{scan}/supporting  (AJAX JSON)
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

        $result = $s3->upload($file, self::S3_TEMP_FOLDER, $newName);

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
     * POST /workflow/temp-scan/{scan}/final-submit  (AJAX JSON)
     */
    public function finalSubmit(ScanFile $scan)
    {
        $this->authorizeOwner($scan);
        $scan->update(['Final_Submit' => 'Y']);
        ScanActionLog::log($scan->Scan_Id, 'final_submitted', 'Final Submitted');
        return response()->json(['success' => true]);
    }

    /**
     * POST /workflow/temp-scan/{scan}/resubmit  (AJAX JSON)
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
        ScanActionLog::log($scan->Scan_Id, 'resubmitted', 'Resubmitted for Approval');
        return response()->json(['success' => true, 'message' => 'Scan resubmitted for approval']);
    }

    /**
     * DELETE /workflow/temp-scan/{scan}  (AJAX JSON)
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
        ScanActionLog::log($scan->Scan_Id, 'scan_deleted', 'Scan Deleted');

        return response()->json(['success' => true]);
    }

    /**
     * DELETE /workflow/temp-scan/{scan}/support/{supportId}  (AJAX JSON)
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
     * POST /workflow/temp-scan/{scan}/replace  (AJAX JSON)
     */
    public function replaceFile(Request $request, ScanFile $scan, S3Service $s3)
    {
        $this->authorizeOwner($scan);

        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png,pdf|max:15360',
        ]);

        $file = $request->file('image');
        $ext = $file->getClientOriginalExtension();
        $newName = time() . '.' . $ext;

        $result = $s3->upload($file, self::S3_TEMP_FOLDER, $newName);

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['error']], 422);
        }

        $scan->update([
            'File' => $newName,
            'File_Ext' => $ext,
            'File_Location' => $result['url'],
            'File_Location1' => $result['key'],
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Shared query for exports — mirrors the data() filters exactly.
     */
    private function exportQuery(): \Illuminate\Support\Collection
    {
        $userId = Auth::id();

        return DB::table('scan_file as s')
            ->leftJoin('master_work_location as l', 'l.location_id', '=', 's.Location')
            ->leftJoin('users as u', 'u.id', '=', 's.Bill_Approver')
            ->where('s.Group_Id', Company::currentId())
            ->where('s.year_id', FinancialYear::currentId())
            ->where('s.Temp_Scan', 'Y')
            ->where('s.Temp_Scan_By', $userId)
            ->where('s.Is_Deleted', 'N')
            ->orderByDesc('s.Temp_Scan_Date')
            ->select([
                's.Scan_Id',
                'l.location_name',
                's.File',
                's.File_Location',
                's.Temp_Scan_Date',
                's.Final_Submit',
                's.Bill_Approved',
                's.temp_scan_reject',
                'u.name as approver_name',
                's.Bill_Approver_Remark',
            ])
            ->get();
    }

    /**
     * GET /workflow/temp-scan/export/excel
     */
    public function exportExcel(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $rows = $this->exportQuery();
        $fileName = 'temp-scan-' . now()->format('Ymd-His') . '.xlsx';
        $hash = md5($rows->toJson());



        $existing = ExportLog::where('model', 'TempScan')
            ->where('user_id', Auth::id())
            ->where('data_hash', $hash)
            ->where('created_at', '>=', now()->subSeconds(60))
            ->first();

        if (!$existing) {
            ExportLog::create([
                'model' => 'TempScan',
                'file_name' => $fileName,
                'file_path' => 'exports/temp-scan/' . $fileName,
                'row_count' => $rows->count(),
                'data_hash' => $hash,
                'user_id' => Auth::id(),
            ]);
        }

        return Excel::download(new TempScanExport($rows), $fileName);
    }

    /**
     * GET /workflow/temp-scan/export/pdf
     */
    public function exportPdf(): \Illuminate\Http\Response
    {
        $rows = $this->exportQuery();
        $fileName = 'temp-scan-' . now()->format('Ymd-His') . '.pdf';
        $hash = md5($rows->toJson());

        if (
            !ExportLog::where('model', 'TempScan')->where('user_id', Auth::id())
                ->where('data_hash', $hash)->where('created_at', '>=', now()->subSeconds(60))->exists()
        ) {
            ExportLog::create([
                'model' => 'TempScan',
                'file_name' => $fileName,
                'file_path' => 'exports/temp-scan/' . $fileName,
                'row_count' => $rows->count(),
                'data_hash' => $hash,
                'user_id' => Auth::id(),
            ]);
        }

        $pdf = Pdf::loadView('exports.temp-scan-pdf', [
            'rows' => $rows,
            'exportedBy' => Auth::user()->name,
            'exportedAt' => now()->format('d M Y H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($fileName);
    }

    /**
     * GET /workflow/temp-scan/export/logs  (AJAX JSON)
     * Returns the user's own export history.
     */
    public function exportLogs(): \Illuminate\Http\JsonResponse
    {
        $logs = ExportLog::where('model', 'TempScan')
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'file_name', 'row_count', 'created_at']);

        return response()->json(['data' => $logs]);
    }

    /**
     */
    private function authorizeOwner(ScanFile $scan): void
    {
        $user = Auth::user();
        if (
            $scan->Temp_Scan_By !== $user->id
            && !$user->hasAnyRole(['Super Admin', 'Classification'])
        ) {
            abort(403);
        }
    }
}
