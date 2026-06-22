<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\DocumentType;
use App\Models\FinancialYear;
use App\Models\QueueItem;
use App\Services\UserAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;


class ClassificationController extends Controller
{
    /**
     * GET /workflow/classification
     */
    public function index()
    {
        return view('panel.workflow.classification.index');
    }

    /**
     * GET /workflow/classification/data  (AJAX — DataTables server-side)
     * 3 tabs: pending, auto, completed
     */
    public function data(Request $request)
    {
        $user         = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $allowedIds   = UserAccessService::allowedCompanies($user->id, $isSuperAdmin)->pluck('id')->toArray();

        $tab = $request->input('tab', 'pending');

        $query = DB::table('scan_file as s')
            ->leftJoin('master_work_location as l', 'l.location_id', '=', 's.Location')
            ->leftJoin('users as u', 'u.id', '=', DB::raw("IF(s.Temp_Scan = 'Y', s.Temp_Scan_By, s.Scan_By)"))
            ->leftJoin('companies as c', 'c.id', '=', 's.Group_Id')
            ->leftJoin('financial_years as fy', 'fy.id', '=', 's.year_id')
            ->leftJoin('users as apv', 'apv.id', '=', 's.Bill_Approver')
            ->leftJoin('document_types as dt', 'dt.id', '=', 's.DocType_Id')
            ->whereIn('s.Group_Id', $allowedIds)
            ->where('s.Is_Deleted', 'N');

        // Exclude rows already in tbl_queues with status=pending (for pending tab)
        $pendingQueueIds = DB::table('tbl_queues')->where('status', 'pending')->pluck('scan_id')->toArray();

        switch ($tab) {
            case 'pending':
                $query->where('s.Document_Name', '!=', '')
                      ->whereNotNull('s.Document_Name')
                      ->where('s.is_extract', 'N')
                      ->where('s.Bill_Approved', 'Y')
                      ->where('s.is_autoclassified', 'N');
                if (!empty($pendingQueueIds)) {
                    $query->whereNotIn('s.Scan_Id', $pendingQueueIds);
                }
                break;
            case 'auto':
                $query->where('s.is_autoclassified', 'Y')
                      ->where('s.is_extract', 'N');
                break;
            case 'completed':
                $query->where('s.is_extract', 'Y');
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
        if ($request->filled('approver')) {
            $query->where('s.Bill_Approver', $request->input('approver'));
        }
        if ($request->filled('from_date')) {
            $query->whereDate(DB::raw("IF(s.Temp_Scan = 'Y', s.Temp_Scan_Date, s.Scan_Date)"), '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate(DB::raw("IF(s.Temp_Scan = 'Y', s.Temp_Scan_Date, s.Scan_Date)"), '<=', $request->input('to_date'));
        }

        $query->select([
            's.Scan_Id', 'c.name as company_name', 'l.location_name',
            's.File', 's.File_Location', 's.File_Ext',
            DB::raw("IF(s.Temp_Scan = 'Y', s.Temp_Scan_Date, s.Scan_Date) as scan_date"),
            's.Document_name', 'u.name as scanned_by',
            'apv.name as approver_name', 'fy.label as fy_label',
            's.DocType_Id', 'dt.label as doc_type_name',
            's.is_extract', 's.is_autoclassified',
        ]);

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('scan_date', fn($r) => $r->scan_date ? \Carbon\Carbon::parse($r->scan_date)->format('d M Y') : '—')
            ->addColumn('status_badge', function ($r) {
                if ($r->is_extract === 'Y') return '<span class="badge-approved">Classified</span>';
                if ($r->is_autoclassified === 'Y') return '<span class="badge-pending">Auto</span>';
                return '<span class="badge-pending">Pending</span>';
            })
            ->filterColumn('company_name', fn($q, $k) => $q->where('c.name', 'like', "%{$k}%"))
            ->filterColumn('location_name', fn($q, $k) => $q->where('l.location_name', 'like', "%{$k}%"))
            ->filterColumn('scanned_by', fn($q, $k) => $q->where('u.name', 'like', "%{$k}%"))
            ->rawColumns(['status_badge'])
            ->make(true);
    }

    /**
     * GET /workflow/classification/tab-counts  (AJAX JSON)
     */
    public function tabCounts(Request $request)
    {
        $user         = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $allowedIds   = UserAccessService::allowedCompanies($user->id, $isSuperAdmin)->pluck('id')->toArray();

        $base = DB::table('scan_file')
            ->whereIn('Group_Id', $allowedIds)
            ->where('Is_Deleted', 'N');

        if ($request->filled('company_id')) $base->where('Group_Id', $request->input('company_id'));
        if ($request->filled('fy_id'))      $base->where('year_id', $request->input('fy_id'));

        $pendingQueueIds = DB::table('tbl_queues')->where('status', 'pending')->pluck('scan_id')->toArray();

        $pendingQuery = (clone $base)
            ->where('Document_Name', '!=', '')
            ->whereNotNull('Document_Name')
            ->where('is_extract', 'N')
            ->where('Bill_Approved', 'Y')
            ->where('is_autoclassified', 'N');
        if (!empty($pendingQueueIds)) {
            $pendingQuery->whereNotIn('Scan_Id', $pendingQueueIds);
        }

        return response()->json([
            'pending'   => $pendingQuery->count(),
            'auto'      => (clone $base)->where('is_autoclassified', 'Y')->where('is_extract', 'N')->count(),
            'completed' => (clone $base)->where('is_extract', 'Y')->count(),
        ]);
    }

    /**
     * POST /workflow/classification/classify  (AJAX JSON)
     * Classify a scan: add to queue + update scan_file.
     */
    public function classify(Request $request)
    {
        $request->validate([
            'scan_id' => 'required|integer|exists:scan_file,Scan_Id',
            'type_id' => 'required|integer|exists:document_types,id',
        ]);

        $scanId = $request->input('scan_id');
        $typeId = $request->input('type_id');

        // Check if already in queue
        $existing = DB::table('tbl_queues')
            ->where('scan_id', $scanId)
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            return response()->json(['success' => false, 'message' => 'This scan is already in the processing queue.'], 422);
        }

        // Get doc type
        $doctype = DocumentType::find($typeId);

        // Add to queue
        DB::table('tbl_queues')->insert([
            'scan_id'    => $scanId,
            'type_id'    => $typeId,
            'status'     => 'pending',
            'created_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update scan_file
        DB::table('scan_file')
            ->where('Scan_Id', $scanId)
            ->update([
                'DocType_Id'       => $typeId,
                'Doc_Type'         => $doctype->key ?? null,
                'is_extract'       => 'Y',
                'classified_by'    => Auth::id(),
                'classified_date'  => now(),
            ]);

        return response()->json(['success' => true, 'message' => 'Document classified successfully.']);
    }

    /**
     * GET /workflow/classification/{scan}/detail  (AJAX JSON)
     */
public function scanDetail($scan)
{
    $scanId = is_object($scan) ? $scan->Scan_Id : (int) $scan;

    $detail = DB::table('scan_file as s')
        ->leftJoin('master_work_location as l', 'l.location_id', '=', 's.Location')
        ->leftJoin('users as u1', 'u1.id', '=', 's.Temp_Scan_By')
        ->leftJoin('users as u2', 'u2.id', '=', 's.Scan_By')
        ->leftJoin('companies as c', 'c.id', '=', 's.Group_Id')
        ->leftJoin('financial_years as fy', 'fy.id', '=', 's.year_id')
        ->leftJoin('users as apv', 'apv.id', '=', 's.Bill_Approver')
        ->where('s.Scan_Id', $scanId)
        ->select([
            's.Scan_Id', 'c.name as company_name', 'l.location_name',
            's.firm_id', 's.bill_voucher_date', 's.bill_no_voucher_no',
            's.File', 's.File_Location', 's.File_Ext',
            's.Temp_Scan_Date', 's.Scan_Date', 's.Temp_Scan',
            's.Document_name', 'fy.label as fy_label',
            'apv.name as approver_name',
            'u1.name as temp_scanned_by', 'u2.name as direct_scanned_by',
            's.DocType_Id', 's.is_extract', 's.is_autoclassified',
        ])
        ->first();

    if (!$detail) {
        return response()->json(['data' => null]);
    }

    $detail->scan_date = ($detail->Temp_Scan === 'Y') ? $detail->Temp_Scan_Date : $detail->Scan_Date;
    $detail->scanned_by = ($detail->Temp_Scan === 'Y') ? $detail->temp_scanned_by : $detail->direct_scanned_by;

    $detail->vendor_name = null;
    if ($detail->firm_id) {
        $detail->vendor_name = DB::table('master_firm')
            ->where('firm_id', $detail->firm_id)
            ->value('firm_name');
    }

    $detail->doc_type_name = null;
    if ($detail->DocType_Id) {
        $detail->doc_type_name = DB::table('document_types')
            ->where('id', $detail->DocType_Id)
            ->value('label');
    }

    return response()->json(['data' => $detail]);
}
    /**
     * GET /workflow/classification/{scan}/support-list  (AJAX JSON)
     */
    public function supportList($scan)
    {
        $scanId = is_object($scan) ? $scan->Scan_Id : (int) $scan;

        $files = DB::table('support_file as sf')
            ->leftJoin('supp_document_type_master as dt', 'dt.DocTypeId', '=', 'sf.DocTypeId')
            ->where('sf.Scan_Id', $scanId)
            ->select(['sf.Support_Id', 'sf.File', 'sf.File_Ext', 'sf.File_Location', 'dt.DocTypeName as doc_type_name'])
            ->get();

        return response()->json(['data' => $files]);
    }

    /**
     * GET /workflow/classification/doc-types  (Select2 AJAX)
     */
    public function docTypesSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $query = DocumentType::where('is_active', true)->where('is_punch', true)->orderBy('label');
        if ($q !== '') $query->where('label', 'like', "%{$q}%");

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)->get(['id', 'label as text']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    /**
     * GET /workflow/classification/companies  (Select2 AJAX)
     */
    public function companiesSelect(Request $request)
    {
        $user         = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $allowed      = UserAccessService::allowedCompanies($user->id, $isSuperAdmin);

        $q = $request->query('q', '');
        $filtered = $q !== '' ? $allowed->filter(fn($c) => str_contains(strtolower($c->name), strtolower($q))) : $allowed;

        return response()->json(['results' => $filtered->map(fn($c) => ['id' => $c->id, 'text' => $c->name])->values(), 'pagination' => ['more' => false]]);
    }

    /**
     * GET /workflow/classification/financial-years  (Select2 AJAX)
     */
    public function financialYearsSelect(Request $request)
    {
        $q = $request->query('q', '');
        $query = FinancialYear::orderByDesc('start_date');
        if ($q !== '') $query->where('label', 'like', "%{$q}%");

        return response()->json(['results' => $query->get(['id', 'label as text']), 'pagination' => ['more' => false]]);
    }

    /**
     * GET /workflow/classification/locations  (Select2 AJAX)
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

    /**
     * GET /workflow/classification/users  (Select2 AJAX — scanners from scan_file)
     */
    public function usersSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $user         = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $allowedIds   = UserAccessService::allowedCompanies($user->id, $isSuperAdmin)->pluck('id')->toArray();

        // Get distinct scanner IDs from scan_file
        $scannerIds = DB::table('scan_file')
            ->whereIn('Group_Id', $allowedIds)
            ->where('Is_Deleted', 'N')
            ->selectRaw("DISTINCT IF(Temp_Scan = 'Y', Temp_Scan_By, Scan_By) as uid")
            ->whereRaw("IF(Temp_Scan = 'Y', Temp_Scan_By, Scan_By) IS NOT NULL")
            ->pluck('uid')
            ->toArray();

        $query = \App\Models\User::whereIn('id', $scannerIds)->orderBy('name');
        if ($q !== '') $query->where('name', 'like', "%{$q}%");

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)->get(['id', 'name as text']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    /**
     * GET /workflow/classification/approvers  (Select2 AJAX — approvers from scan_file)
     */
    public function approversSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $user         = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $allowedIds   = UserAccessService::allowedCompanies($user->id, $isSuperAdmin)->pluck('id')->toArray();

        // Get distinct Bill_Approver IDs from scan_file
        $approverIds = DB::table('scan_file')
            ->whereIn('Group_Id', $allowedIds)
            ->where('Is_Deleted', 'N')
            ->where('Bill_Approver', '>', 0)
            ->distinct()
            ->pluck('Bill_Approver')
            ->toArray();

        $query = \App\Models\User::whereIn('id', $approverIds)->orderBy('name');
        if ($q !== '') $query->where('name', 'like', "%{$q}%");

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)->get(['id', 'name as text']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }
}
