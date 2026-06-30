<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\ScanActionLog;
use App\Models\RejectionReason;
use App\Models\ScanFile;
use App\Services\UserAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class BillApprovalController extends Controller
{
    /**
     * GET /workflow/bill-approval
     */
    public function index()
    {
        $user         = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $companies    = UserAccessService::allowedCompanies($user->id, $isSuperAdmin);
        $fys          = FinancialYear::orderByDesc('start_date')->get(['id', 'label']);

        return view('panel.workflow.bill-approval.index', compact('companies', 'fys'));
    }

    /**
     * GET /workflow/bill-approval/data  (AJAX — DataTables server-side)
     * Shows ALL bills assigned to this approver across all accessible companies/FYs.
     */
    public function data(Request $request)
    {
        $userId       = Auth::id();
        $user         = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $allowedIds   = UserAccessService::allowedCompanies($userId, $isSuperAdmin)->pluck('id')->toArray();

        $query = DB::table('scan_file as s')
            ->leftJoin('master_work_location as l', 'l.location_id', '=', 's.Location')
            ->leftJoin('users as u', 'u.id', '=', DB::raw("IF(s.Temp_Scan = 'Y', s.Temp_Scan_By, s.Scan_By)"))
            ->leftJoin('companies as c', 'c.id', '=', 's.Group_Id')
            ->leftJoin('master_firm as mf', 'mf.firm_id', '=', 's.firm_id')
            ->leftJoin('financial_years as fy', 'fy.id', '=', 's.year_id')
            ->whereIn('s.Group_Id', $allowedIds)
            ->where('s.Bill_Approver', $userId)
            ->where('s.Is_Deleted', 'N')
            ->where('s.Final_Submit', 'Y');

        $query->select([
            's.Scan_Id', 's.Location', 'l.location_name',
            'c.name as company_name', 's.File', 's.File_Location', 's.File_Ext',
            DB::raw("IF(s.Temp_Scan = 'Y', s.Temp_Scan_Date, s.Scan_Date) as scan_date"),
            's.bill_voucher_date', 's.bill_no_voucher_no',
            'mf.firm_name as vendor_name', 's.Document_name',
            's.Bill_Approved', 's.Bill_Approver_Date', 's.Bill_Approver_Remark',
            'u.name as scanned_by', 's.Temp_Scan', 'fy.label as fy_label',
        ]);

        // Tab filtering
        $tab = $request->input('tab', 'pending');
        switch ($tab) {
            case 'pending':
                $query->where('s.Bill_Approved', 'N');
                break;
            case 'approved':
                $query->where('s.Bill_Approved', 'Y');
                break;
            case 'rejected':
                $query->where('s.Bill_Approved', 'R');
                break;
        }

        // Filters
        if ($request->filled('company_id')) {
            $query->where('s.Group_Id', $request->input('company_id'));
        }
        if ($request->filled('fy_id')) {
            $query->where('s.year_id', $request->input('fy_id'));
        }
        if ($request->filled('location_id')) {
            $query->where('s.Location', $request->input('location_id'));
        }
        if ($request->filled('scanned_by')) {
            $query->where(DB::raw("IF(s.Temp_Scan = 'Y', s.Temp_Scan_By, s.Scan_By)"), $request->input('scanned_by'));
        }
        if ($request->filled('from_date')) {
            $query->whereDate('s.bill_voucher_date', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('s.bill_voucher_date', '<=', $request->input('to_date'));
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('scan_date', fn($r) => $r->scan_date ? \Carbon\Carbon::parse($r->scan_date)->format('d M Y') : '—')
            ->editColumn('bill_voucher_date', fn($r) => $r->bill_voucher_date ? \Carbon\Carbon::parse($r->bill_voucher_date)->format('d M Y') : '—')
            ->addColumn('status_badge', function ($r) {
                if ($r->Bill_Approved === 'R') return '<span class="badge-rejected">Rejected</span>';
                return match ($r->Bill_Approved) {
                    'Y' => '<span class="badge-approved">Approved</span>',
                    default => '<span class="badge-pending">Pending</span>',
                };
            })
            ->addColumn('actions', fn($r) =>
                '<div class="dt-actions" data-id="' . $r->Scan_Id . '" '
                . 'data-file="' . e($r->File) . '" '
                . 'data-url="' . e($r->File_Location) . '" '
                . 'data-status="' . e($r->Bill_Approved) . '"></div>')
            ->filterColumn('location_name', fn($q, $k) => $q->where('l.location_name', 'like', "%{$k}%"))
            ->filterColumn('company_name', fn($q, $k) => $q->where('c.name', 'like', "%{$k}%"))
            ->filterColumn('scanned_by', fn($q, $k) => $q->where('u.name', 'like', "%{$k}%"))
            ->filterColumn('vendor_name', fn($q, $k) => $q->where('mf.firm_name', 'like', "%{$k}%"))
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    /**
     * GET /workflow/bill-approval/tab-counts  (AJAX JSON)
     */
    public function tabCounts(Request $request)
    {
        $userId       = Auth::id();
        $user         = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $allowedIds   = UserAccessService::allowedCompanies($userId, $isSuperAdmin)->pluck('id')->toArray();

        $base = DB::table('scan_file')
            ->whereIn('Group_Id', $allowedIds)
            ->where('Bill_Approver', $userId)
            ->where('Is_Deleted', 'N')
            ->where('Final_Submit', 'Y');

        if ($request->filled('company_id')) $base->where('Group_Id', $request->input('company_id'));
        if ($request->filled('fy_id'))      $base->where('year_id', $request->input('fy_id'));
        if ($request->filled('location_id'))$base->where('Location', $request->input('location_id'));
        if ($request->filled('scanned_by')) $base->where(DB::raw("IF(Temp_Scan = 'Y', Temp_Scan_By, Scan_By)"), $request->input('scanned_by'));
        if ($request->filled('from_date'))  $base->whereDate('bill_voucher_date', '>=', $request->input('from_date'));
        if ($request->filled('to_date'))    $base->whereDate('bill_voucher_date', '<=', $request->input('to_date'));

        return response()->json([
            'all'      => (clone $base)->count(),
            'pending'  => (clone $base)->where('Bill_Approved', 'N')->count(),
            'approved' => (clone $base)->where('Bill_Approved', 'Y')->count(),
            'rejected' => (clone $base)->where('Bill_Approved', 'R')->count(),
        ]);
    }

    /**
     * GET /workflow/bill-approval/locations  (Select2 AJAX)
     */
    public function locationsSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $query = \App\Models\Location::active()->orderBy('location_name');
        if ($q !== '') {
            $query->where(fn($qb) => $qb->where('location_name', 'like', "%{$q}%")->orWhere('location_code', 'like', "%{$q}%"));
        }

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)->get(['location_id as id', 'location_name as text']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    /**
     * GET /workflow/bill-approval/users  (Select2 AJAX)
     * Returns distinct scanners from scan_file assigned to this approver.
     */
    public function usersSelect(Request $request)
    {
        $userId       = Auth::id();
        $user         = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $allowedIds   = UserAccessService::allowedCompanies($userId, $isSuperAdmin)->pluck('id')->toArray();

        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        // Get distinct scanner user IDs from scan_file assigned to this approver
        $subQuery = DB::table('scan_file')
            ->whereIn('Group_Id', $allowedIds)
            ->where('Bill_Approver', $userId)
            ->where('Is_Deleted', 'N')
            ->where('Final_Submit', 'Y')
            ->selectRaw("DISTINCT IF(Temp_Scan = 'Y', Temp_Scan_By, Scan_By) as scanner_id")
            ->whereRaw("IF(Temp_Scan = 'Y', Temp_Scan_By, Scan_By) IS NOT NULL");

        $query = DB::table('users')
            ->joinSub($subQuery, 'scanners', fn($j) => $j->on('users.id', '=', 'scanners.scanner_id'))
            ->where('users.is_active', true)
            ->orderBy('users.name');

        if ($q !== '') {
            $query->where('users.name', 'like', "%{$q}%");
        }

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)->get(['users.id', 'users.name as text']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    /**
     * GET /workflow/bill-approval/companies  (Select2 AJAX)
     */
    public function companiesSelect(Request $request)
    {
        $user         = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $allowed      = UserAccessService::allowedCompanies($user->id, $isSuperAdmin);

        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $filtered = $q !== ''
            ? $allowed->filter(fn($c) => str_contains(strtolower($c->name), strtolower($q)))
            : $allowed;

        $total   = $filtered->count();
        $results = $filtered->slice(($page - 1) * $per, $per)->map(fn($c) => ['id' => $c->id, 'text' => $c->name])->values();

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    /**
     * GET /workflow/bill-approval/financial-years  (Select2 AJAX)
     */
    public function financialYearsSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $query = FinancialYear::orderByDesc('start_date');
        if ($q !== '') $query->where('label', 'like', "%{$q}%");

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)->get(['id', 'label as text']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    /**
     * GET /workflow/bill-approval/{scan}/detail  (AJAX JSON)
     * Returns full scan details for the approval modal.
     */
    public function scanDetail(ScanFile $scan)
    {
        $this->authorizeApprover($scan);

        $detail = DB::table('scan_file as s')
            ->leftJoin('master_work_location as l', 'l.location_id', '=', 's.Location')
            ->leftJoin('users as u', 'u.id', '=', DB::raw("IF(s.Temp_Scan = 'Y', s.Temp_Scan_By, s.Scan_By)"))
            ->leftJoin('companies as c', 'c.id', '=', 's.Group_Id')
            ->leftJoin('master_firm as mf', 'mf.firm_id', '=', 's.firm_id')
            ->leftJoin('financial_years as fy', 'fy.id', '=', 's.year_id')
            ->where('s.Scan_Id', $scan->Scan_Id)
            ->select([
                's.Scan_Id', 'c.name as company_name', 'l.location_name',
                'mf.firm_name as vendor_name', 's.bill_voucher_date', 's.bill_no_voucher_no',
                's.File', 's.File_Location', 's.File_Ext',
                DB::raw("IF(s.Temp_Scan = 'Y', s.Temp_Scan_Date, s.Scan_Date) as scan_date"),
                's.Document_name', 'u.name as scanned_by', 'fy.label as fy_label',
                's.Bill_Approved', 's.Bill_Approver_Remark', 's.Bill_Approver_Date',
            ])
            ->first();

        return response()->json(['data' => $detail]);
    }

    /**
     * POST /workflow/bill-approval/{scan}/approve  (AJAX JSON)
     */
    public function approve(Request $request, ScanFile $scan)
    {
        $this->authorizeApprover($scan);

        $scan->update([
            'is_extract' => 'N',
            'extract_status' => 'N',
            'Bill_Approved'        => 'Y',
            'Bill_Approver_Date'   => now()->toDateString(),
            'Bill_Approver_Remark' => $request->input('remark', null),
        ]);

        ScanActionLog::log($scan->Scan_Id, 'bill_approved', 'Bill Approved', $request->input('remark'));

        return response()->json(['success' => true, 'message' => 'Bill approved successfully.']);
    }

    /**
     * POST /workflow/bill-approval/{scan}/reject  (AJAX JSON)
     */
    public function reject(Request $request, ScanFile $scan)
    {
        $this->authorizeApprover($scan);

        $request->validate(['reason' => 'required|string|max:500']);

        $scan->update([
            'Bill_Approved'           => 'R',
            'Bill_Approver_Date'      => now()->toDateString(),
            'Bill_Approver_Remark'    => $request->input('reason'),
        ]);

        ScanActionLog::log($scan->Scan_Id, 'bill_rejected', 'Bill Rejected', $request->input('reason'));

        return response()->json(['success' => true, 'message' => 'Bill rejected.']);
    }

    /**
     * GET /workflow/bill-approval/{scan}/support-list  (AJAX JSON)
     */
    public function supportList(ScanFile $scan)
    {
        $this->authorizeApprover($scan);

        $files = DB::table('support_file as sf')
            ->leftJoin('supp_document_type_master as dt', 'dt.DocTypeId', '=', 'sf.DocTypeId')
            ->where('sf.Scan_Id', $scan->Scan_Id)
            ->select(['sf.Support_Id', 'sf.File', 'sf.File_Ext', 'sf.File_Location', 'dt.DocTypeName as doc_type_name'])
            ->get();

        return response()->json(['data' => $files]);
    }

    /**
     * GET /workflow/bill-approval/rejection-reasons  (Select2 AJAX)
     */
    public function rejectionReasons(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $query = RejectionReason::active()->forModule('bill_approval')->orderBy('reason');
        if ($q !== '') $query->where('reason', 'like', "%{$q}%");

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)->get(['id', 'reason as text']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    /**
     * POST /workflow/bill-approval/rejection-reasons  (Create new)
     */
    public function storeRejectionReason(Request $request)
    {
        $request->validate(['reason' => 'required|string|max:255|unique:rejection_reasons,reason']);

        $reason = RejectionReason::create([
            'reason'     => $request->input('reason'),
            'module'     => 'bill_approval',
            'is_active'  => true,
            'created_by' => Auth::id(),
        ]);

        return response()->json(['success' => true, 'reason' => ['id' => $reason->id, 'text' => $reason->reason]]);
    }

    /**
     * GET /workflow/bill-approval/export/logs  (AJAX JSON)
     */
    public function exportLogs()
    {
        $logs = \App\Models\ExportLog::where('model', 'BillApproval')
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'file_name', 'row_count', 'created_at']);

        return response()->json(['data' => $logs]);
    }

    /**
     * GET /workflow/bill-approval/export/excel
     */
    public function exportExcel(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $rows     = $this->exportQuery($request);
        $fileName = 'bill-approval-' . now()->format('Ymd-His') . '.xlsx';
        $hash     = md5($rows->toJson());

        if (
            ! \App\Models\ExportLog::where('model', 'BillApproval')
                ->where('user_id', Auth::id())
                ->where('data_hash', $hash)
                ->where('created_at', '>=', now()->subSeconds(60))
                ->exists()
        ) {
            \App\Models\ExportLog::create([
                'model'     => 'BillApproval',
                'file_name' => $fileName,
                'file_path' => 'exports/bill-approval/' . $fileName,
                'row_count' => $rows->count(),
                'data_hash' => $hash,
                'user_id'   => Auth::id(),
            ]);
        }

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\BillApprovalExport($rows), $fileName);
    }

    /**
     * GET /workflow/bill-approval/export/pdf
     */
    public function exportPdf(Request $request): \Illuminate\Http\Response
    {
        $rows     = $this->exportQuery($request);
        $fileName = 'bill-approval-' . now()->format('Ymd-His') . '.pdf';
        $hash     = md5($rows->toJson());

        if (
            ! \App\Models\ExportLog::where('model', 'BillApproval')
                ->where('user_id', Auth::id())
                ->where('data_hash', $hash)
                ->where('created_at', '>=', now()->subSeconds(60))
                ->exists()
        ) {
            \App\Models\ExportLog::create([
                'model'     => 'BillApproval',
                'file_name' => $fileName,
                'file_path' => 'exports/bill-approval/' . $fileName,
                'row_count' => $rows->count(),
                'data_hash' => $hash,
                'user_id'   => Auth::id(),
            ]);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.bill-approval-pdf', [
            'rows'       => $rows,
            'exportedBy' => Auth::user()->name,
            'exportedAt' => now()->format('d M Y H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($fileName);
    }

    /**
     * Shared export query — returns collection matching current filters.
     */
    private function exportQuery(Request $request): \Illuminate\Support\Collection
    {
        $userId       = Auth::id();
        $user         = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $allowedIds   = UserAccessService::allowedCompanies($userId, $isSuperAdmin)->pluck('id')->toArray();

        $query = DB::table('scan_file as s')
            ->leftJoin('master_work_location as l', 'l.location_id', '=', 's.Location')
            ->leftJoin('users as u', 'u.id', '=', DB::raw("IF(s.Temp_Scan = 'Y', s.Temp_Scan_By, s.Scan_By)"))
            ->leftJoin('companies as c', 'c.id', '=', 's.Group_Id')
            ->leftJoin('master_firm as mf', 'mf.firm_id', '=', 's.firm_id')
            ->whereIn('s.Group_Id', $allowedIds)
            ->where('s.Bill_Approver', $userId)
            ->where('s.Is_Deleted', 'N')
            ->where('s.Final_Submit', 'Y');

        // Apply same filters
        $tab = $request->input('tab', 'all');
        switch ($tab) {
            case 'pending':
                $query->where('s.Bill_Approved', 'N');
                break;
            case 'approved':
                $query->where('s.Bill_Approved', 'Y');
                break;
            case 'rejected':
                $query->where('s.Bill_Approved', 'R');
                break;
        }

        if ($request->filled('company_id')) $query->where('s.Group_Id', $request->input('company_id'));
        if ($request->filled('fy_id'))      $query->where('s.year_id', $request->input('fy_id'));
        if ($request->filled('location_id'))$query->where('s.Location', $request->input('location_id'));
        if ($request->filled('scanned_by')) $query->where(DB::raw("IF(s.Temp_Scan = 'Y', s.Temp_Scan_By, s.Scan_By)"), $request->input('scanned_by'));
        if ($request->filled('from_date'))  $query->whereDate('s.bill_voucher_date', '>=', $request->input('from_date'));
        if ($request->filled('to_date'))    $query->whereDate('s.bill_voucher_date', '<=', $request->input('to_date'));

        return $query->select([
            's.Scan_Id', 'c.name as company_name', 'l.location_name',
            's.File', 'mf.firm_name as vendor_name',
            's.bill_voucher_date', 's.bill_no_voucher_no',
            DB::raw("IF(s.Temp_Scan = 'Y', s.Temp_Scan_Date, s.Scan_Date) as scan_date"),
            'u.name as scanned_by', 's.Bill_Approved', 's.Bill_Approver_Remark',
        ])->orderByDesc(DB::raw("IF(s.Temp_Scan = 'Y', s.Temp_Scan_Date, s.Scan_Date)"))->get();
    }

    private function authorizeApprover(ScanFile $scan): void
    {
        if ((int) $scan->Bill_Approver !== Auth::id()) {
            abort(403, 'You are not the assigned approver for this scan.');
        }
    }
}
