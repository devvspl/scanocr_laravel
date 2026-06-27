<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\ScanFile;
use App\Services\UserAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PunchApprovalController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────────────

    public function index()
    {
        return view('panel.workflow.punch-approval.index');
    }

    // ── Manual pagination data (ultra-optimized for 10 lakh+ records) ────────

    public function data(Request $request)
    {
        $userId       = Auth::id();
        $user         = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $allowedIds   = UserAccessService::allowedCompanies($userId, $isSuperAdmin)->pluck('id')->toArray();

        // Pagination parameters
        $draw   = (int) $request->input('draw', 1);
        $start  = (int) $request->input('start', 0);
        $length = min((int) $request->input('length', 10), 100); // Cap at 100
        $search = $request->input('search.value', '');
        
        // Order parameters
        $orderColumnIndex = (int) $request->input('order.0.column', 5);
        $orderDir         = $request->input('order.0.dir', 'desc');
        $orderColumns     = ['s.Scan_Id', 'c.name', 'l.location_name', 'dt.label', 's.File', 's.Punch_Date', 'puncher.name', 's.Scan_Date'];
        $orderBy          = $orderColumns[$orderColumnIndex] ?? 's.Punch_Date';

        // Build cache key for count
        $tab = $request->input('tab', 'pending');
        $cacheKey = 'pa_count_' . md5(json_encode([
            'uid' => $userId,
            'cids' => $allowedIds,
            'tab' => $tab,
            'filters' => $request->only(['company_id', 'fy_id', 'location_id', 'doc_type_id', 'from_date', 'to_date'])
        ]));

        // Determine which covering index to use based on tab
        $forceIndex = match($tab) {
            'pending'  => 'idx_pending_covering',
            'approved' => 'idx_approved_covering',
            'rejected' => 'idx_rejected_covering',
            default    => 'idx_pending_covering'
        };

        // Build base query WITHOUT joins initially - FORCE INDEX for correct execution plan
        $query = DB::table(DB::raw("scan_file as s FORCE INDEX ({$forceIndex})"))
            ->whereIn('s.Group_Id', $allowedIds)
            ->where('s.Is_Deleted', 'N')
            ->where('s.File_Punched', 'Y');   // Uses covering index

        // Tab filter (uses covering index)
        switch ($tab) {
            case 'pending':
                $query->where('s.File_Approved', 'N')
                      ->where(function($q) {
                          $q->whereNull('s.Is_Rejected')->orWhere('s.Is_Rejected', 'N');
                      });
                break;
            case 'approved':
                $query->where('s.File_Approved', 'Y');
                break;
            case 'rejected':
                $query->where('s.Is_Rejected', 'Y');
                break;
        }

        // Optional filters (uses idx_approval_filters when filtering)
        if ($request->filled('company_id'))  $query->where('s.Group_Id',  $request->input('company_id'));
        if ($request->filled('fy_id'))       $query->where('s.year_id',   $request->input('fy_id'));
        if ($request->filled('location_id')) $query->where('s.Location',  $request->input('location_id'));
        if ($request->filled('doc_type_id')) $query->where('s.DocType_Id',$request->input('doc_type_id'));
        if ($request->filled('from_date'))   $query->whereDate('s.Punch_Date', '>=', $request->input('from_date'));
        if ($request->filled('to_date'))     $query->whereDate('s.Punch_Date', '<=', $request->input('to_date'));

        // Handle search separately
        $searchActive = !empty($search);
        if ($searchActive) {
            $query->leftJoin('companies as c', 'c.id', '=', 's.Group_Id')
                  ->leftJoin('master_work_location as l', 'l.location_id', '=', 's.Location')
                  ->leftJoin('document_types as dt', 'dt.id', '=', 's.DocType_Id')
                  ->leftJoin('users as puncher', 'puncher.id', '=', 's.Punch_By')
                  ->leftJoin('punchfile as pf', 'pf.Scan_Id', '=', 's.Scan_Id');
            
            $query->where(function($q) use ($search) {
                $q->where('c.name', 'like', "%{$search}%")
                  ->orWhere('l.location_name', 'like', "%{$search}%")
                  ->orWhere('dt.label', 'like', "%{$search}%")
                  ->orWhere('s.File', 'like', "%{$search}%")
                  ->orWhere('puncher.name', 'like', "%{$search}%")
                  ->orWhere('pf.File_No', 'like', "%{$search}%");
            });
        }

        // SKIP COUNT for first page load - use cached value or estimate
        if ($start === 0 && !$searchActive) {
            // Try to get cached count (5 min cache) - Build fresh query for count
            $totalRecords = \Cache::remember($cacheKey, 300, function() use ($tab, $allowedIds, $forceIndex, $request) {
                $countQuery = DB::table(DB::raw("scan_file as s FORCE INDEX ({$forceIndex})"))
                    ->whereIn('s.Group_Id', $allowedIds)
                    ->where('s.Is_Deleted', 'N')
                    ->where('s.File_Punched', 'Y');
                
                // Re-apply tab filter
                switch ($tab) {
                    case 'pending':
                        $countQuery->where('s.File_Approved', 'N')
                                  ->where(function($q) {
                                      $q->whereNull('s.Is_Rejected')->orWhere('s.Is_Rejected', 'N');
                                  });
                        break;
                    case 'approved':
                        $countQuery->where('s.File_Approved', 'Y');
                        break;
                    case 'rejected':
                        $countQuery->where('s.Is_Rejected', 'Y');
                        break;
                }
                
                // Apply filters to count
                if ($request->filled('company_id'))  $countQuery->where('s.Group_Id',  $request->input('company_id'));
                if ($request->filled('fy_id'))       $countQuery->where('s.year_id',   $request->input('fy_id'));
                if ($request->filled('location_id')) $countQuery->where('s.Location',  $request->input('location_id'));
                if ($request->filled('doc_type_id')) $countQuery->where('s.DocType_Id',$request->input('doc_type_id'));
                
                return $countQuery->count('s.Scan_Id');
            });
        } else if ($searchActive) {
            // For search, do a quick count (unavoidable)
            $totalRecords = (clone $query)->count('s.Scan_Id');
        } else {
            // For pagination beyond page 1, estimate total
            $totalRecords = $start + $length + 1000; // Estimate more pages exist
        }

        // Fetch data with minimal JOINs for display
        $baseSelect = clone $query;
        
        if (!$searchActive) {
            // Add JOINs only for data fetch (not for count)
            $baseSelect = DB::table(DB::raw("scan_file as s FORCE INDEX ({$forceIndex})"))
                      ->whereIn('s.Group_Id', $allowedIds)
                      ->where('s.Is_Deleted', 'N')
                      ->where('s.File_Punched', 'Y');
            
            // Re-apply tab filter
            switch ($tab) {
                case 'pending':
                    $baseSelect->where('s.File_Approved', 'N')
                          ->where(function($q) {
                              $q->whereNull('s.Is_Rejected')->orWhere('s.Is_Rejected', 'N');
                          });
                    break;
                case 'approved':
                    $baseSelect->where('s.File_Approved', 'Y');
                    break;
                case 'rejected':
                    $baseSelect->where('s.Is_Rejected', 'Y');
                    break;
            }
            
            // Re-apply filters
            if ($request->filled('company_id'))  $baseSelect->where('s.Group_Id',  $request->input('company_id'));
            if ($request->filled('fy_id'))       $baseSelect->where('s.year_id',   $request->input('fy_id'));
            if ($request->filled('location_id')) $baseSelect->where('s.Location',  $request->input('location_id'));
            if ($request->filled('doc_type_id')) $baseSelect->where('s.DocType_Id',$request->input('doc_type_id'));
            if ($request->filled('from_date'))   $baseSelect->whereDate('s.Punch_Date', '>=', $request->input('from_date'));
            if ($request->filled('to_date'))     $baseSelect->whereDate('s.Punch_Date', '<=', $request->input('to_date'));
            
            // Now add JOINs
            $baseSelect->leftJoin('companies as c', 'c.id', '=', 's.Group_Id')
                      ->leftJoin('master_work_location as l', 'l.location_id', '=', 's.Location')
                      ->leftJoin('document_types as dt', 'dt.id', '=', 's.DocType_Id')
                      ->leftJoin('users as puncher', 'puncher.id', '=', 's.Punch_By');
        }

        $records = $baseSelect
            ->select([
                's.Scan_Id', 
                's.File',
                's.Punch_Date',
                's.File_Approved',
                's.Is_Rejected',
                DB::raw("IF(s.Temp_Scan='Y', s.Temp_Scan_Date, s.Scan_Date) as scan_date"),
                'c.name as company_name',
                'l.location_name',
                'dt.label as doc_type_label',
                'puncher.name as punched_by_name',
            ])
            ->orderBy($orderBy, $orderDir)
            ->offset($start)
            ->limit($length)
            ->get();

        // Fast data formatting without Carbon
        $data = [];
        foreach ($records as $i => $row) {
            $data[] = [
                'DT_RowIndex'        => $start + $i + 1,
                'Scan_Id'            => $row->Scan_Id,
                'company_name'       => $row->company_name ?? '—',
                'location_name'      => $row->location_name ?? '—',
                'doc_type_label'     => $row->doc_type_label ?? '—',
                'File'               => $row->File ?: '—',
                'Punch_Date' => $row->Punch_Date ? date('d M Y', strtotime($row->Punch_Date)) : '—',
                'punched_by_name'    => $row->punched_by_name ?? '—',
                'scan_date'          => $row->scan_date ? date('d M Y', strtotime($row->scan_date)) : '—',
                'status_badge'       => $row->Is_Rejected === 'Y' 
                    ? '<span class="badge-rejected">Rejected</span>'
                    : ($row->File_Approved === 'Y' 
                        ? '<span class="badge-approved">Approved</span>' 
                        : '<span class="badge-pending">Pending</span>'),
            ];
        }

        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data'            => $data,
        ]);
    }

    // ── Tab counts (cached for 5 minutes) ────────────────────────────────────

    public function tabCounts(Request $request)
    {
        $userId       = Auth::id();
        $user         = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $allowedIds   = UserAccessService::allowedCompanies($userId, $isSuperAdmin)->pluck('id')->toArray();

        // Build cache key based on filters
        $cacheKey = 'punch_approval_counts_' . md5(json_encode([
            'user' => $userId,
            'companies' => $allowedIds,
            'filters' => $request->only(['company_id', 'fy_id', 'location_id', 'doc_type_id', 'from_date', 'to_date'])
        ]));

        // Cache for 5 minutes (300 seconds)
        $counts = \Cache::remember($cacheKey, 300, function() use ($request, $allowedIds) {
            // Single query with conditional aggregation for better performance
            $query = DB::table('scan_file')
                ->selectRaw('
                    COUNT(*) as all_count,
                    SUM(CASE WHEN File_Approved = "N" AND (Is_Rejected IS NULL OR Is_Rejected = "N") THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN File_Approved = "Y" THEN 1 ELSE 0 END) as approved_count,
                    SUM(CASE WHEN Is_Rejected = "Y" THEN 1 ELSE 0 END) as rejected_count
                ')
                ->whereIn('Group_Id', $allowedIds)
                ->where('Is_Deleted', 'N')
                ->where('File_Punched', 'Y');

            // Apply optional filters
            if ($request->filled('company_id'))  $query->where('Group_Id', $request->input('company_id'));
            if ($request->filled('fy_id'))       $query->where('year_id',  $request->input('fy_id'));
            if ($request->filled('location_id')) $query->where('Location', $request->input('location_id'));
            if ($request->filled('doc_type_id')) $query->where('DocType_Id', $request->input('doc_type_id'));
            if ($request->filled('from_date'))   $query->whereDate('Punch_Date', '>=', $request->input('from_date'));
            if ($request->filled('to_date'))     $query->whereDate('Punch_Date', '<=', $request->input('to_date'));

            $result = $query->first();

            return [
                'all'      => (int) $result->all_count,
                'pending'  => (int) $result->pending_count,
                'approved' => (int) $result->approved_count,
                'rejected' => (int) $result->rejected_count,
            ];
        });

        return response()->json($counts);
    }

    // ── Detail (AJAX) ─────────────────────────────────────────────────────────

    public function scanDetail(ScanFile $scan)
    {
        $detail = DB::table('scan_file as s')
            ->leftJoin('master_work_location as l', 'l.location_id', '=', 's.Location')
            ->leftJoin('companies as c',             'c.id',          '=', 's.Group_Id')
            ->leftJoin('document_types as dt',       'dt.id',         '=', 's.DocType_Id')
            ->leftJoin('financial_years as fy',      'fy.id',         '=', 's.year_id')
            ->leftJoin('users as puncher',           'puncher.id',    '=', 's.Punch_By')
            ->leftJoin('users as approver',          'approver.id',   '=', 's.Approve_By')
            ->leftJoin('punchfile as pf',            'pf.Scan_Id',    '=', 's.Scan_Id')
            ->where('s.Scan_Id', $scan->Scan_Id)
            ->select([
                's.Scan_Id', 'c.name as company_name', 'l.location_name',
                'dt.label as doc_type_label', 'fy.label as fy_label',
                's.File', 's.File_Location', 's.File_Ext', 's.Document_name',
                DB::raw("IF(s.Temp_Scan='Y', s.Temp_Scan_Date, s.Scan_Date) as scan_date"),
                's.Punch_Date',
                's.File_Approved', 's.Approve_Date',
                's.Is_Rejected',   's.Reject_Remark', 's.Reject_Date',
                's.Edit_Permission',
                'puncher.name  as punched_by_name',
                'approver.name as approved_by_name',
                'pf.Grand_Total', 'pf.Total_Amount', 'pf.Remark as punch_remark',
                'pf.FromName as vendor_name', 'pf.BillDate as bill_date',
                'pf.File_No as bill_no',
            ])
            ->first();

        return response()->json(['data' => $detail]);
    }

    // ── Support files (AJAX) ─────────────────────────────────────────────────

    public function supportList(ScanFile $scan)
    {
        $files = DB::table('support_file as sf')
            ->leftJoin('supp_document_type_master as dt', 'dt.DocTypeId', '=', 'sf.DocTypeId')
            ->where('sf.Scan_Id', $scan->Scan_Id)
            ->select(['sf.Support_Id', 'sf.File', 'sf.File_Ext', 'sf.File_Location',
                      'dt.DocTypeName as doc_type_name'])
            ->get();

        return response()->json(['data' => $files]);
    }

    // ── Approve ───────────────────────────────────────────────────────────────

    public function approve(Request $request, ScanFile $scan)
    {
        $request->validate(['remark' => 'nullable|string|max:500']);

        $userId = Auth::id();

        DB::table('scan_file')->where('Scan_Id', $scan->Scan_Id)->update([
            'File_Approved'  => 'Y',
            'Approve_By'     => $userId,
            'Approve_Date'   => now()->toDateString(),
            'Is_Rejected'    => 'N',
            'Edit_Permission'=> $request->boolean('edit_permission') ? 'Y' : 'N',
        ]);

        // Clear all punch approval cache keys
        $this->clearPunchApprovalCache($userId);

        // Sync to secondary DB
        app(\App\Http\Controllers\AgrisoftController::class)->sendForAccounting($scan->Scan_Id);

        return response()->json(['success' => true, 'message' => 'File Approved Successfully.']);
    }

    // ── Reject — matches old CI reject_record() exactly ──────────────────────

    public function reject(Request $request, ScanFile $scan)
    {
        $request->validate(['remark' => 'required|string|max:500']);

        $userId = Auth::id();

        $result = DB::table('scan_file')->where('Scan_Id', $scan->Scan_Id)->update([
            'Is_Rejected'    => 'Y',
            'Approve_By'     => $userId,
            'Reject_Remark'  => $request->input('remark'),
            'Reject_Date'    => now()->toDateString(),
            'Edit_Permission'=> $request->boolean('edit_permission') ? 'Y' : 'N',
        ]);

        // Clear all punch approval cache keys
        $this->clearPunchApprovalCache($userId);

        if ($result) {
            return response()->json(['success' => true,  'message' => 'File Rejected Successfully.']);
        }

        return response()->json(['success' => false, 'message' => 'Something went wrong. Please try again.'], 400);
    }

    // ── Select2 endpoints ────────────────────────────────────────────────────

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
        $results = $filtered->slice(($page - 1) * $per, $per)
            ->map(fn($c) => ['id' => $c->id, 'text' => $c->name])->values();

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    public function financialYearsSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $query = \App\Models\FinancialYear::orderByDesc('start_date');
        if ($q !== '') $query->where('label', 'like', "%{$q}%");

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)->get(['id', 'label as text']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    public function locationsSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $query = \App\Models\Location::active()->orderBy('location_name');
        if ($q !== '') $query->where('location_name', 'like', "%{$q}%");

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)
            ->get(['location_id as id', 'location_name as text']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    public function docTypesSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $query = DB::table('document_types')->where('is_active', 1)->orderBy('label');
        if ($q !== '') $query->where('label', 'like', "%{$q}%");

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)->get(['id', 'label as text']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    // ── Helper: Clear all cache keys for punch approval ──────────────────────

    private function clearPunchApprovalCache($userId)
    {
        // More aggressive cache clearing - use Cache::flush() or clear by prefix
        // Since we can't easily clear by pattern in default cache driver,
        // we'll use a timestamp-based approach or clear all cache
        
        // Option 1: Clear all application cache (nuclear option but guaranteed to work)
        \Cache::flush();
        
        // Log for debugging
        \Log::info('Cleared all cache after punch approval action', ['user_id' => $userId]);
    }
}
