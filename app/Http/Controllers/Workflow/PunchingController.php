<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\ScanFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PunchingController extends Controller
{
    /**
     * GET /workflow/punching
     */
    public function index()
    {
        return view('panel.workflow.punching.index');
    }

    /**
     * GET /workflow/punching/data  (AJAX — DataTables server-side)
     * 3 tabs: pending, my-punching, rejected
     */
    public function data(Request $request)
    {
        $userId    = Auth::id();
        $user      = Auth::user();
        $companyId = Company::currentId();
        $fyId      = FinancialYear::currentId();
        $tab       = $request->input('tab', 'pending');

        // Get user's allowed document type IDs
        $allowedDocTypes = $user->allowedDocumentTypeIds();

        $query = DB::table('scan_file as s')
            ->leftJoin('master_work_location as l', 'l.location_id', '=', 's.Location')
            ->leftJoin('users as u', 'u.id', '=', DB::raw("IF(s.Temp_Scan = 'Y', s.Temp_Scan_By, s.Scan_By)"))
            ->leftJoin('document_types as dt', 'dt.id', '=', 's.DocType_Id')
            ->leftJoin('master_firm as mf', 'mf.firm_id', '=', 's.firm_id')
            ->leftJoin('users as apv', 'apv.id', '=', 's.Bill_Approver')
            ->where('s.Group_Id', $companyId)
            ->where('s.year_id', $fyId)
            ->where('s.Is_Deleted', 'N');

        switch ($tab) {
            case 'pending':
                $query->join('tbl_queues as q', function ($j) {
                        $j->on('q.scan_id', '=', 's.Scan_Id')->where('q.status', '=', 'completed');
                    })
                    ->where('s.File_Punched', 'N')
                    ->where('s.Scan_Resend', 'N')
                    ->where('s.Final_Submit', 'Y')
                    ->where('s.at_finance', 'P')
                    ->where('s.temp_scan_reject', 'N')
                    ->where('s.Document_Name', '!=', '')
                    ->where('s.is_extract', 'Y')
                    ->whereRaw("((s.Location IS NOT NULL AND s.Bill_Approved = 'Y') OR s.Location IS NULL)");
                // Filter by user's document type permission
                if (!empty($allowedDocTypes)) {
                    $query->whereIn('s.DocType_Id', $allowedDocTypes);
                }
                break;

            case 'my-punching':
                $query->where('s.Punch_By', $userId)
                      ->where('s.File_Punched', 'Y');
                break;

            case 'rejected':
                // Default: show only Edit_Permission = Y for this user
                // If include_no_edit = 1, also show records without edit permission
                if ($request->input('include_no_edit') == '1') {
                    $query->where('s.File_Punched', 'N')
                          ->where('s.Punch_By', $userId);
                } else {
                    $query->where('s.Edit_Permission', 'Y')
                          ->where('s.File_Punched', 'N')
                          ->where('s.Punch_By', $userId);
                }
                break;
        }

        // Filters
        if ($request->filled('scanned_by')) {
            $query->where(DB::raw("IF(s.Temp_Scan = 'Y', s.Temp_Scan_By, s.Scan_By)"), $request->input('scanned_by'));
        }
        if ($request->filled('approver')) {
            $query->where('s.Bill_Approver', $request->input('approver'));
        }
        if ($request->filled('doc_type_id')) {
            $query->where('s.DocType_Id', $request->input('doc_type_id'));
        }
        if ($request->filled('location_id')) {
            $query->where('s.Location', $request->input('location_id'));
        }
        if ($request->filled('from_date')) {
            $query->whereDate(DB::raw("IF(s.Temp_Scan = 'Y', s.Temp_Scan_Date, s.Scan_Date)"), '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate(DB::raw("IF(s.Temp_Scan = 'Y', s.Temp_Scan_Date, s.Scan_Date)"), '<=', $request->input('to_date'));
        }

        $query->select([
            's.Scan_Id', 'l.location_name', 's.File', 's.File_Location', 's.File_Ext',
            DB::raw("IF(s.Temp_Scan = 'Y', s.Temp_Scan_Date, s.Scan_Date) as scan_date"),
            's.Document_name', 'u.name as scanned_by', 'dt.label as doc_type_name',
            'mf.firm_name as vendor_name', 's.bill_voucher_date', 's.bill_no_voucher_no',
            's.File_Punched', 's.Punch_Date', 's.Edit_Permission',
            'apv.name as approver_name',
        ]);

        // Date filter
        if ($request->filled('from_date')) {
            $query->whereDate(DB::raw("IF(s.Temp_Scan = 'Y', s.Temp_Scan_Date, s.Scan_Date)"), '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate(DB::raw("IF(s.Temp_Scan = 'Y', s.Temp_Scan_Date, s.Scan_Date)"), '<=', $request->input('to_date'));
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('scan_date', fn($r) => $r->scan_date ? \Carbon\Carbon::parse($r->scan_date)->format('d M Y') : '—')
            ->editColumn('bill_voucher_date', fn($r) => $r->bill_voucher_date ? \Carbon\Carbon::parse($r->bill_voucher_date)->format('d M Y') : '—')
            ->editColumn('Punch_Date', fn($r) => $r->Punch_Date && $r->Punch_Date !== '0000-00-00 00:00:00' ? \Carbon\Carbon::parse($r->Punch_Date)->format('d M Y') : '—')
            ->addColumn('status_badge', function ($r) {
                if ($r->File_Punched === 'Y') return '<span class="badge-approved">Punched</span>';
                if ($r->Edit_Permission === 'Y') return '<span class="badge-rejected">Edit Required</span>';
                return '<span class="badge-pending">Pending</span>';
            })
            ->filterColumn('location_name', fn($q, $k) => $q->where('l.location_name', 'like', "%{$k}%"))
            ->filterColumn('scanned_by', fn($q, $k) => $q->where('u.name', 'like', "%{$k}%"))
            ->filterColumn('doc_type_name', fn($q, $k) => $q->where('dt.label', 'like', "%{$k}%"))
            ->filterColumn('vendor_name', fn($q, $k) => $q->where('mf.firm_name', 'like', "%{$k}%"))
            ->rawColumns(['status_badge'])
            ->make(true);
    }

    /**
     * GET /workflow/punching/tab-counts  (AJAX JSON)
     */
    public function tabCounts()
    {
        $userId    = Auth::id();
        $companyId = Company::currentId();
        $fyId      = FinancialYear::currentId();

        // Pending count
        $pendingCount = DB::table('scan_file as s')
            ->join('tbl_queues as q', function ($j) {
                $j->on('q.scan_id', '=', 's.Scan_Id')->where('q.status', '=', 'completed');
            })
            ->where('s.Group_Id', $companyId)
            ->where('s.year_id', $fyId)
            ->where('s.Is_Deleted', 'N')
            ->where('s.File_Punched', 'N')
            ->where('s.Scan_Resend', 'N')
            ->where('s.Final_Submit', 'Y')
            ->where('s.at_finance', 'P')
            ->where('s.temp_scan_reject', 'N')
            ->where('s.Document_Name', '!=', '')
            ->where('s.is_extract', 'Y')
            ->whereRaw("((s.Location IS NOT NULL AND s.Bill_Approved = 'Y') OR s.Location IS NULL)")
            ->count();

        // My punching count
        $myCount = DB::table('scan_file')
            ->where('Group_Id', $companyId)
            ->where('year_id', $fyId)
            ->where('Is_Deleted', 'N')
            ->where('Punch_By', $userId)
            ->where('File_Punched', 'Y')
            ->count();

        // Rejected with edit permission
        $rejectedCount = DB::table('scan_file')
            ->where('Group_Id', $companyId)
            ->where('year_id', $fyId)
            ->where('Is_Deleted', 'N')
            ->where('Edit_Permission', 'Y')
            ->where('File_Punched', 'N')
            ->where('Punch_By', $userId)
            ->count();

        return response()->json([
            'pending'  => $pendingCount,
            'my'       => $myCount,
            'rejected' => $rejectedCount,
        ]);
    }

    /**
     * GET /workflow/punching/{scan}/detail  (AJAX JSON)
     */
    public function scanDetail($scan)
    {
        $scanId = is_object($scan) ? $scan->Scan_Id : (int) $scan;

        $detail = DB::table('scan_file as s')
            ->leftJoin('master_work_location as l', 'l.location_id', '=', 's.Location')
            ->leftJoin('users as u1', 'u1.id', '=', 's.Temp_Scan_By')
            ->leftJoin('users as u2', 'u2.id', '=', 's.Scan_By')
            ->leftJoin('companies as c', 'c.id', '=', 's.Group_Id')
            ->leftJoin('document_types as dt', 'dt.id', '=', 's.DocType_Id')
            ->leftJoin('master_firm as mf', 'mf.firm_id', '=', 's.firm_id')
            ->leftJoin('financial_years as fy', 'fy.id', '=', 's.year_id')
            ->where('s.Scan_Id', $scanId)
            ->select([
                's.Scan_Id', 'c.name as company_name', 'l.location_name',
                's.File', 's.File_Location', 's.File_Ext',
                DB::raw("IF(s.Temp_Scan = 'Y', s.Temp_Scan_Date, s.Scan_Date) as scan_date"),
                DB::raw("IF(s.Temp_Scan = 'Y', u1.name, u2.name) as scanned_by"),
                's.Document_name', 'fy.label as fy_label',
                'dt.label as doc_type_name', 'mf.firm_name as vendor_name',
                's.bill_voucher_date', 's.bill_no_voucher_no',
                's.File_Punched', 's.Punch_Date', 's.Edit_Permission',
                's.Scan_Resend_Remark',
            ])
            ->first();

        return response()->json(['data' => $detail]);
    }

    /**
     * GET /workflow/punching/{scan}/support-list  (AJAX JSON)
     */
    public function supportList($scan)
    {
        $scanId = is_object($scan) ? $scan->Scan_Id : (int) $scan;

        $files = DB::table('support_file as sf')
            ->leftJoin('supp_document_type_master as sdt', 'sdt.DocTypeId', '=', 'sf.DocTypeId')
            ->where('sf.Scan_Id', $scanId)
            ->select(['sf.Support_Id', 'sf.File', 'sf.File_Ext', 'sf.File_Location', 'sdt.DocTypeName as doc_type_name'])
            ->get();

        return response()->json(['data' => $files]);
    }

    /**
     * POST /workflow/punching/{scan}/mark-punched  (AJAX JSON)
     * Mark a scan as punched by current user.
     */
    public function markPunched(ScanFile $scan)
    {
        $scan->update([
            'File_Punched' => 'Y',
            'Punch_By'     => Auth::id(),
            'Punch_Date'   => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Marked as punched.']);
    }

    /**
     * GET /workflow/punching/scanners  (Select2 AJAX)
     * Distinct scanners from scan_file for current company/FY.
     */
    public function scannersSelect(Request $request)
    {
        $companyId = Company::currentId();
        $fyId      = FinancialYear::currentId();
        $q         = $request->query('q', '');
        $page      = max(1, (int) $request->query('page', 1));
        $per       = 20;

        $scannerIds = DB::table('scan_file')
            ->where('Group_Id', $companyId)
            ->where('year_id', $fyId)
            ->where('Is_Deleted', 'N')
            ->selectRaw("DISTINCT IF(Temp_Scan = 'Y', Temp_Scan_By, Scan_By) as uid")
            ->whereRaw("IF(Temp_Scan = 'Y', Temp_Scan_By, Scan_By) IS NOT NULL")
            ->pluck('uid')->toArray();

        $query = \App\Models\User::whereIn('id', $scannerIds)->orderBy('name');
        if ($q !== '') $query->where('name', 'like', "%{$q}%");

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)->get(['id', 'name as text']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    /**
     * GET /workflow/punching/approvers  (Select2 AJAX)
     * Distinct bill approvers from scan_file for current company/FY.
     */
    public function approversSelect(Request $request)
    {
        $companyId = Company::currentId();
        $fyId      = FinancialYear::currentId();
        $q         = $request->query('q', '');
        $page      = max(1, (int) $request->query('page', 1));
        $per       = 20;

        $approverIds = DB::table('scan_file')
            ->where('Group_Id', $companyId)
            ->where('year_id', $fyId)
            ->where('Is_Deleted', 'N')
            ->where('Bill_Approver', '>', 0)
            ->distinct()->pluck('Bill_Approver')->toArray();

        $query = \App\Models\User::whereIn('id', $approverIds)->orderBy('name');
        if ($q !== '') $query->where('name', 'like', "%{$q}%");

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)->get(['id', 'name as text']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    /**
     * GET /workflow/punching/doc-types  (Select2 AJAX)
     * Returns only document types assigned to the current user.
     */
    public function docTypesSelect(Request $request)
    {
        $user = Auth::user();
        $allowedIds = $user->allowedDocumentTypeIds();
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $query = \App\Models\DocumentType::where('is_active', true)->orderBy('label');
        if (!empty($allowedIds)) {
            $query->whereIn('id', $allowedIds);
        }
        if ($q !== '') $query->where('label', 'like', "%{$q}%");

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)->get(['id', 'label as text']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    /**
     * GET /workflow/punching/locations  (Select2 AJAX)
     */
    public function locationsSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $query = \App\Models\Location::active()->orderBy('location_name');
        if ($q !== '') $query->where(fn($qb) => $qb->where('location_name', 'like', "%{$q}%")->orWhere('location_code', 'like', "%{$q}%"));

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)->get(['location_id as id', 'location_name as text']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }
}
