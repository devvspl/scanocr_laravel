<?php

namespace App\Http\Controllers\Workflow;

use App\Helpers\BillDateValidator;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Location;
use App\Models\User;
use App\Models\ScanFile;
use App\Services\UserAccessService;
use App\Services\S3Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class SuperScannerController extends Controller
{
    private const S3_DIRECT_FOLDER = 'uploads/direct';

    /**
     * GET /workflow/super-scanner
     */
    public function index()
    {
        return view('panel.workflow.super-scanner.index');
    }

    /**
     * GET /workflow/super-scanner/{company}
     * Company-wise detailed scanning management view.
     */
    public function companyView(Company $company)
    {
        $user         = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $companies    = UserAccessService::allowedCompanies($user->id, $isSuperAdmin);

        if (!$companies->contains('id', $company->id)) {
            abort(403);
        }

        return view('panel.workflow.super-scanner.company', compact('company'));
    }

    /**
     * GET /workflow/super-scanner/{company}/scans-data  (AJAX DataTables)
     * All scans for this company with tab + filter support.
     */
    public function companyScansData(Request $request, Company $company)
    {
        $this->authorizeCompany($company);

        $fyId = FinancialYear::currentId();
        $tab  = $request->input('tab', 'all');

        $query = DB::table('scan_file as s')
            ->leftJoin('master_work_location as l', 'l.location_id', '=', 's.Location')
            ->leftJoin('users as u',   'u.id',  '=', 's.Temp_Scan_By')
            ->leftJoin('users as apv', 'apv.id', '=', 's.Bill_Approver')
            ->where('s.Group_Id', $company->id)
            ->where('s.Temp_Scan', 'Y')
            ->where('s.Is_Deleted', 'N')
            ->when($fyId, fn($q) => $q->where('s.year_id', $fyId))
            ->select([
                's.Scan_Id',
                'l.location_name',
                's.File',
                's.File_Location',
                's.File_Ext',
                's.Temp_Scan_Date',
                's.Scan_Complete',
                's.document_verified',
                's.Final_Submit',
                's.Bill_Approved',
                's.temp_scan_reject',
                'u.name   as scanned_by',
                'apv.name as approver_name',
                's.Bill_Approver_Remark',
            ]);

        // Tab filter
        switch ($tab) {
            case 'pending':
                $query->where('s.Bill_Approved', 'N')
                      ->where(fn($q) => $q->whereNull('s.temp_scan_reject')->orWhere('s.temp_scan_reject', 'N'));
                break;
            case 'approved':
                $query->where('s.Bill_Approved', 'Y');
                break;
            case 'rejected':
                $query->where(fn($q) => $q->where('s.Bill_Approved', 'R')->orWhere('s.temp_scan_reject', 'Y'));
                break;
        }

        // Scanned by filter
        if ($request->filled('scanned_by')) {
            $query->where('s.Temp_Scan_By', $request->input('scanned_by'));
        }

        // Date range filters
        if ($request->filled('from_date')) {
            $query->whereDate('s.Temp_Scan_Date', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('s.Temp_Scan_Date', '<=', $request->input('to_date'));
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('Temp_Scan_Date', fn($r) => $r->Temp_Scan_Date
                ? \Carbon\Carbon::parse($r->Temp_Scan_Date)->format('d M Y')
                : '—')
            ->addColumn('status_badge', function ($r) {
                if ($r->temp_scan_reject === 'Y' || $r->Bill_Approved === 'R') {
                    return '<span class="badge-rejected">Rejected</span>';
                }
                return match ($r->Bill_Approved) {
                    'Y' => '<span class="badge-approved">Approved</span>',
                    default => '<span class="badge-pending">Pending</span>',
                };
            })
            ->addColumn('actions', fn($r) =>
                '<div class="dt-actions" data-id="' . $r->Scan_Id . '" data-file="' . e($r->File) . '" data-url="' . e($r->File_Location) . '"></div>'
            )
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    /**
     * GET /workflow/super-scanner/{company}/pending-naming  (AJAX DataTables)
     * Temp scanned, not yet named (Scan_Complete = N).
     */
    public function companyPendingNamingData(Request $request, Company $company)
    {
        $this->authorizeCompany($company);

        $fyId = FinancialYear::currentId();

        $query = DB::table('scan_file as s')
            ->leftJoin('master_work_location as l', 'l.location_id', '=', 's.Location')
            ->leftJoin('users as u', 'u.id', '=', 's.Temp_Scan_By')
            ->where('s.Group_Id', $company->id)
            ->where('s.Temp_Scan', 'Y')
            ->where('s.Scan_Complete', 'N')
            ->where(fn($q) => $q->whereNull('s.temp_scan_reject')->orWhere('s.temp_scan_reject', 'N'))
            ->where('s.Is_Deleted', 'N')
            ->when($fyId, fn($q) => $q->where('s.year_id', $fyId));

        // Date filter
        if ($request->filled('from_date')) {
            $query->whereDate('s.Temp_Scan_Date', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('s.Temp_Scan_Date', '<=', $request->input('to_date'));
        }

        $query->select([
            's.Scan_Id',
            'l.location_name',
            's.File',
            's.File_Location',
            's.File_Ext',
            's.Temp_Scan_Date',
            'u.name as scanned_by',
        ]);

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('Temp_Scan_Date', fn($r) => $r->Temp_Scan_Date
                ? \Carbon\Carbon::parse($r->Temp_Scan_Date)->format('d M Y')
                : '—')
            ->addColumn('actions', fn($r) =>
                '<div class="dt-actions" data-id="' . $r->Scan_Id . '" data-file="' . e($r->File) . '" data-url="' . e($r->File_Location) . '"></div>'
            )
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * GET /workflow/super-scanner/{company}/pending-verify  (AJAX DataTables)
     * Temp scanned, named but not yet verified (document_verified = N).
     */
    public function companyPendingVerifyData(Request $request, Company $company)
    {
        $this->authorizeCompany($company);

        $fyId = FinancialYear::currentId();

        $query = DB::table('scan_file as s')
            ->leftJoin('master_work_location as l', 'l.location_id', '=', 's.Location')
            ->leftJoin('users as u', 'u.id', '=', 's.Temp_Scan_By')
            ->where('s.Group_Id', $company->id)
            ->where('s.Temp_Scan', 'Y')
            ->where('s.Scan_Complete', 'Y')
            ->where(fn($q) => $q->whereNull('s.temp_scan_reject')->orWhere('s.temp_scan_reject', 'N'))
            ->where(fn($q) => $q->whereNull('s.document_verified')->orWhere('s.document_verified', 'N'))
            ->where('s.Is_Deleted', 'N')
            ->when($fyId, fn($q) => $q->where('s.year_id', $fyId));

        // Date filter
        if ($request->filled('from_date')) {
            $query->whereDate('s.Temp_Scan_Date', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('s.Temp_Scan_Date', '<=', $request->input('to_date'));
        }

        $query->select([
            's.Scan_Id',
            'l.location_name',
            's.File',
            's.File_Location',
            's.File_Ext',
            's.Temp_Scan_Date',
            's.Document_name',
            'u.name as scanned_by',
        ]);

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('Temp_Scan_Date', fn($r) => $r->Temp_Scan_Date
                ? \Carbon\Carbon::parse($r->Temp_Scan_Date)->format('d M Y')
                : '—')
            ->addColumn('actions', fn($r) =>
                '<div class="dt-actions" data-id="' . $r->Scan_Id . '" data-file="' . e($r->File) . '" data-url="' . e($r->File_Location) . '"></div>'
            )
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * GET /workflow/super-scanner/{company}/tab-counts  (AJAX JSON)
     */
    public function companyTabCounts(Request $request, Company $company)
    {
        $this->authorizeCompany($company);

        $fyId = FinancialYear::currentId();

        $base = DB::table('scan_file')
            ->where('Group_Id', $company->id)
            ->where('Temp_Scan', 'Y')
            ->where('Is_Deleted', 'N')
            ->when($fyId, fn($q) => $q->where('year_id', $fyId))
            ->when($request->filled('from_date'), fn($q) => $q->whereDate('Temp_Scan_Date', '>=', $request->input('from_date')))
            ->when($request->filled('to_date'),   fn($q) => $q->whereDate('Temp_Scan_Date', '<=', $request->input('to_date')));

        return response()->json([
            'all'              => (clone $base)->count(),
            'pending'          => (clone $base)->where('Bill_Approved', 'N')->where(fn($q) => $q->whereNull('temp_scan_reject')->orWhere('temp_scan_reject', 'N'))->count(),
            'approved'         => (clone $base)->where('Bill_Approved', 'Y')->count(),
            'rejected'         => (clone $base)->where(fn($q) => $q->where('Bill_Approved', 'R')->orWhere('temp_scan_reject', 'Y'))->count(),
            'pending_naming'   => (clone $base)->where('Scan_Complete', 'N')->where(fn($q) => $q->whereNull('temp_scan_reject')->orWhere('temp_scan_reject', 'N'))->count(),
            'pending_verify'   => (clone $base)->where('Scan_Complete', 'Y')->where(fn($q) => $q->whereNull('temp_scan_reject')->orWhere('temp_scan_reject', 'N'))->where(fn($q) => $q->whereNull('document_verified')->orWhere('document_verified', 'N'))->count(),
        ]);
    }

    /**
     * POST /workflow/super-scanner/{company}/scan  (AJAX JSON)
     * Super Admin directly scans a document for a company.
     */
    public function companyScan(Request $request, Company $company, S3Service $s3)
    {
        $this->authorizeCompany($company);

        $request->validate(array_merge([
            'location'      => 'required|integer|exists:master_work_location,location_id',
            'bill_approver' => 'required|integer|exists:users,id',
            'vendor_id'     => 'required|integer|exists:master_firm,firm_id',
            'bill_no'       => 'required|string|max:100',
            'document_name' => 'required|string|max:255',
            'main_file'     => 'required|file|mimes:jpg,jpeg,png,pdf|max:15360',
        ], BillDateValidator::rules()));

        $file    = $request->file('main_file');
        $ext     = $file->getClientOriginalExtension();
        $newName = time() . '.' . $ext;

        $result = $s3->upload($file, self::S3_DIRECT_FOLDER, $newName);

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => 'S3 Upload Error: ' . $result['error']], 422);
        }

        $scan = ScanFile::create([
            'Group_Id'            => $company->id,
            'year_id'             => FinancialYear::currentId(),
            'Location'            => $request->input('location'),
            'Bill_Approver'       => $request->input('bill_approver'),
            'Scan_By'             => Auth::id(),
            'Temp_Scan_By'        => Auth::id(),
            'Temp_Scan'           => 'Y',
            'bill_voucher_date'   => $request->input('bill_date'),
            'firm_id'             => $request->input('vendor_id'),
            'bill_no_voucher_no'  => $request->input('bill_no'),
            'Document_name'       => $request->input('document_name'),
            'Scan_Complete'       => 'N',
            'DocType_Id'          => 0,
            'department_id'       => 0,
            'File'                => $newName,
            'File_Ext'            => $ext,
            'File_Location'       => $result['url'],
            'File_Location1'      => $result['key'],
            'Year'                => date('Y'),
            'Scan_Date'           => now(),
            'Temp_Scan_Date'      => now(),
        ]);

        return response()->json([
            'success' => true,
            'scan'    => [
                'id'            => $scan->Scan_Id,
                'file'          => $scan->File,
                'file_url'      => $scan->File_Location,
                'document_name' => $scan->Document_name,
                'scan_date'     => \Carbon\Carbon::parse($scan->Temp_Scan_Date)->format('d M Y H:i'),
            ],
        ]);
    }

    /**
     * POST /workflow/super-scanner/{company}/verify-document  (AJAX JSON)
     * Verify a scanned document and set received date.
     */
    public function verifyDocument(Request $request, Company $company)
    {
        $this->authorizeCompany($company);

        $request->validate([
            'scan_id'                => 'required|integer|exists:scan_file,Scan_Id',
            'document_received_date' => 'required|date',
        ]);

        $scanId               = $request->input('scan_id');
        $documentReceivedDate = $request->input('document_received_date');
        $userId               = Auth::id();

        // Ensure scan belongs to this company
        $scan = DB::table('scan_file')
            ->where('Scan_Id', $scanId)
            ->where('Group_Id', $company->id)
            ->first();

        if (!$scan) {
            abort(403);
        }

        DB::table('scan_file')
            ->where('Scan_Id', $scanId)
            ->update([
                'document_verified'      => 'Y',
                'document_verified_by'   => $userId,
                'document_verified_date' => date('Y-m-d'),
                'document_received_date' => $documentReceivedDate,
            ]);

        return response()->json(['success' => true, 'message' => 'Document verified successfully.']);
    }

    // ── Select2 endpoints ─────────────────────────────────────────────────────

    public function locationsSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $query = Location::active()->orderBy('location_name');
        if ($q !== '') {
            $query->where(fn($qb) => $qb->where('location_name', 'like', "%{$q}%")->orWhere('location_code', 'like', "%{$q}%"));
        }

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)->get(['location_id as id', 'location_name as text']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    public function billApproversSelect(Request $request)
    {
        $locationId = (int) $request->query('location_id', 0);
        $q          = $request->query('q', '');
        $page       = max(1, (int) $request->query('page', 1));
        $per        = 20;

        $query = User::role('Bill Approval')->where('is_active', true)->orderBy('name');

        if ($locationId) {
            $query->where(fn($qb) =>
                $qb->whereHas('locationAccess', fn($la) => $la->where('location_id', $locationId)->where('has_access', true))
                   ->orWhereDoesntHave('locationAccess')
            );
        }

        if ($q !== '') $query->where('name', 'like', "%{$q}%");

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)->get(['id', 'name as text']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    public function vendorsSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $query = \App\Models\MasterFirm::active()->vendors()->orderBy('firm_name');
        if ($q !== '') {
            $query->where(fn($sub) => $sub->where('firm_name', 'like', "%{$q}%")->orWhere('firm_code', 'like', "%{$q}%"));
        }

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)->get(['firm_id as id', 'firm_name', 'firm_code'])
            ->map(fn($f) => [
                'id'             => $f->id,
                'text'           => $f->firm_code ? "{$f->firm_name} ({$f->firm_code})" : $f->firm_name,
                'firm_name_clean' => preg_replace('/[^A-Za-z0-9 ]/', '', strtoupper($f->firm_name)),
            ]);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    public function usersSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $query = User::where('is_active', true)->orderBy('name');
        
        if ($q !== '') {
            $query->where('name', 'like', "%{$q}%");
        }

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)->get(['id', 'name as text']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    // ── Existing summary methods ───────────────────────────────────────────────

    public function data(Request $request)
    {
        $user         = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $companies    = UserAccessService::allowedCompanies($user->id, $isSuperAdmin);
        $companyIds   = $companies->pluck('id')->toArray();

        $fyId     = FinancialYear::currentId();
        $fromDate = $request->input('from_date');
        $toDate   = $request->input('to_date');

        $rows = DB::table('companies as c')
            ->whereIn('c.id', $companyIds)
            ->where('c.is_active', true)
            ->leftJoin('scan_file as s', function ($join) use ($fyId, $fromDate, $toDate) {
                $join->on('s.Group_Id', '=', 'c.id')
                     ->where('s.Temp_Scan', '=', 'Y')
                     ->where('s.Is_Deleted', '=', 'N')
                     ->when($fyId,     fn($j) => $j->where('s.year_id', $fyId))
                     ->when($fromDate, fn($j) => $j->whereDate('s.Temp_Scan_Date', '>=', $fromDate))
                     ->when($toDate,   fn($j) => $j->whereDate('s.Temp_Scan_Date', '<=', $toDate));
            })
            ->select([
                'c.id   as company_id',
                'c.name as company_name',
                DB::raw("COUNT(s.Scan_Id) AS total_scan"),
                DB::raw("SUM(CASE WHEN s.Bill_Approved = 'N' AND (s.temp_scan_reject IS NULL OR s.temp_scan_reject = 'N') THEN 1 ELSE 0 END) AS pending"),
                DB::raw("SUM(CASE WHEN s.Bill_Approved = 'Y' THEN 1 ELSE 0 END) AS approved"),
                DB::raw("SUM(CASE WHEN s.Bill_Approved = 'R' OR s.temp_scan_reject = 'Y' THEN 1 ELSE 0 END) AS rejected"),
                DB::raw("SUM(CASE WHEN s.Scan_Complete = 'N' AND (s.temp_scan_reject IS NULL OR s.temp_scan_reject = 'N') THEN 1 ELSE 0 END) AS pending_naming"),
                DB::raw("SUM(CASE WHEN s.Scan_Complete = 'Y' AND (s.temp_scan_reject IS NULL OR s.temp_scan_reject = 'N') AND (s.document_verified IS NULL OR s.document_verified = 'N') THEN 1 ELSE 0 END) AS pending_verification"),
            ])
            ->groupBy('c.id', 'c.name')
            ->orderBy('c.name')
            ->get();

        return DataTables::of($rows)
            ->addIndexColumn()
            ->addColumn('actions', fn($r) => '')
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function totals(Request $request)
    {
        $user         = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $companies    = UserAccessService::allowedCompanies($user->id, $isSuperAdmin);
        $companyIds   = $companies->pluck('id')->toArray();

        $fyId     = FinancialYear::currentId();
        $fromDate = $request->input('from_date');
        $toDate   = $request->input('to_date');

        $q = DB::table('scan_file')
            ->whereIn('Group_Id', $companyIds)
            ->where('Temp_Scan', 'Y')
            ->where('Is_Deleted', 'N')
            ->when($fyId,     fn($q) => $q->where('year_id', $fyId))
            ->when($fromDate, fn($q) => $q->whereDate('Temp_Scan_Date', '>=', $fromDate))
            ->when($toDate,   fn($q) => $q->whereDate('Temp_Scan_Date', '<=', $toDate));

        return response()->json([
            'total_scan'           => (clone $q)->count(),
            'pending'              => (clone $q)->where('Bill_Approved', 'N')->where(fn($q) => $q->whereNull('temp_scan_reject')->orWhere('temp_scan_reject', 'N'))->count(),
            'approved'             => (clone $q)->where('Bill_Approved', 'Y')->count(),
            'rejected'             => (clone $q)->where(fn($q) => $q->where('Bill_Approved', 'R')->orWhere('temp_scan_reject', 'Y'))->count(),
            'pending_naming'       => (clone $q)->where('Scan_Complete', 'N')->where(fn($q) => $q->whereNull('temp_scan_reject')->orWhere('temp_scan_reject', 'N'))->count(),
            'pending_verification' => (clone $q)->where('Scan_Complete', 'Y')->where(fn($q) => $q->whereNull('temp_scan_reject')->orWhere('temp_scan_reject', 'N'))->where(fn($q) => $q->whereNull('document_verified')->orWhere('document_verified', 'N'))->count(),
        ]);
    }

    public function detail(Request $request)
    {
        $user         = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $companies    = UserAccessService::allowedCompanies($user->id, $isSuperAdmin);
        $companyIds   = $companies->pluck('id')->toArray();

        $companyId = (int) $request->input('company_id', 0);
        $metric    = $request->input('metric', 'total_scan');
        $fyId      = FinancialYear::currentId();
        $fromDate  = $request->input('from_date');
        $toDate    = $request->input('to_date');

        if ($companyId && !in_array($companyId, $companyIds, true)) {
            abort(403);
        }

        $query = DB::table('scan_file as s')
            ->leftJoin('master_work_location as l', 'l.location_id', '=', 's.Location')
            ->leftJoin('users as u',   'u.id',  '=', 's.Temp_Scan_By')
            ->leftJoin('users as apv', 'apv.id', '=', 's.Bill_Approver')
            ->where('s.Temp_Scan', 'Y')
            ->where('s.Is_Deleted', 'N')
            ->when($fyId,     fn($q) => $q->where('s.year_id', $fyId))
            ->when($fromDate, fn($q) => $q->whereDate('s.Temp_Scan_Date', '>=', $fromDate))
            ->when($toDate,   fn($q) => $q->whereDate('s.Temp_Scan_Date', '<=', $toDate));

        if ($companyId) {
            $query->where('s.Group_Id', $companyId);
        } else {
            $query->whereIn('s.Group_Id', $companyIds);
        }

        switch ($metric) {
            case 'pending':
                $query->where('s.Bill_Approved', 'N')->where(fn($q) => $q->whereNull('s.temp_scan_reject')->orWhere('s.temp_scan_reject', 'N'));
                break;
            case 'approved':
                $query->where('s.Bill_Approved', 'Y');
                break;
            case 'rejected':
                $query->where(fn($q) => $q->where('s.Bill_Approved', 'R')->orWhere('s.temp_scan_reject', 'Y'));
                break;
            case 'pending_naming':
                $query->where('s.Scan_Complete', 'N')->where(fn($q) => $q->whereNull('s.temp_scan_reject')->orWhere('s.temp_scan_reject', 'N'));
                break;
            case 'pending_verification':
                $query->where('s.Scan_Complete', 'Y')->where(fn($q) => $q->whereNull('s.temp_scan_reject')->orWhere('s.temp_scan_reject', 'N'))->where(fn($q) => $q->whereNull('s.document_verified')->orWhere('s.document_verified', 'N'));
                break;
        }

        $query->select([
            's.Scan_Id', 'l.location_name', 's.File', 's.File_Location', 's.File_Ext',
            's.Temp_Scan_Date', 's.Scan_Date', 's.Scan_Complete', 's.document_verified', 's.Final_Submit',
            's.Bill_Approved', 's.temp_scan_reject', 'u.name as scanned_by', 'apv.name as approver_name', 's.Bill_Approver_Remark',
        ]);

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('Temp_Scan_Date', fn($r) => $r->Temp_Scan_Date ? \Carbon\Carbon::parse($r->Temp_Scan_Date)->format('d M Y') : '—')
            ->editColumn('Scan_Date',      fn($r) => $r->Scan_Date      ? \Carbon\Carbon::parse($r->Scan_Date)->format('d M Y')      : '—')
            ->addColumn('status_badge', function ($r) {
                if ($r->temp_scan_reject === 'Y' || $r->Bill_Approved === 'R') return '<span class="badge-rejected">Rejected</span>';
                return match ($r->Bill_Approved) {
                    'Y' => '<span class="badge-approved">Approved</span>',
                    default => '<span class="badge-pending">Pending</span>',
                };
            })
            ->addColumn('file_preview', fn($r) =>
                '<a href="' . e($r->File_Location) . '" target="_blank" class="inline-flex items-center gap-1 text-blue-600 hover:underline text-xs">' . e($r->File) . '</a>'
            )
            ->filterColumn('location_name', function ($query, $keyword) {
                $query->where('l.location_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('scanned_by', function ($query, $keyword) {
                $query->where('u.name', 'like', "%{$keyword}%");
            })
            ->filterColumn('approver_name', function ($query, $keyword) {
                $query->where('apv.name', 'like', "%{$keyword}%");
            })
            ->rawColumns(['status_badge', 'file_preview'])
            ->make(true);
    }

    public function exportExcel(Request $request)
    {
        $data     = $this->summaryExportData($request);
        $fileName = 'scan-summary-' . now()->format('Ymd-His') . '.xlsx';
        return Excel::download(new \App\Exports\SuperScannerExport($data), $fileName);
    }

    public function exportPdf(Request $request)
    {
        $data     = $this->summaryExportData($request);
        $fileName = 'scan-summary-' . now()->format('Ymd-His') . '.pdf';
        $pdf = Pdf::loadView('exports.super-scanner-pdf', [
            'rows'       => $data,
            'exportedBy' => Auth::user()->name,
            'exportedAt' => now()->format('d M Y H:i'),
            'fromDate'   => $request->input('from_date', ''),
            'toDate'     => $request->input('to_date', ''),
        ])->setPaper('a4', 'landscape');
        return $pdf->download($fileName);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function authorizeCompany(Company $company): void
    {
        $user         = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $companies    = UserAccessService::allowedCompanies($user->id, $isSuperAdmin);

        if (!$companies->contains('id', $company->id)) {
            // Better error message with debugging info
            $message = "You do not have permission to perform this action. ";
            if (config('app.debug')) {
                $message .= sprintf(
                    "(User: %d, Company: %d, SuperAdmin: %s, Allowed: [%s])",
                    $user->id,
                    $company->id,
                    $isSuperAdmin ? 'Yes' : 'No',
                    $companies->pluck('id')->implode(', ')
                );
            }
            abort(403, $message);
        }
    }

    private function summaryExportData(Request $request): \Illuminate\Support\Collection
    {
        $user         = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $companies    = UserAccessService::allowedCompanies($user->id, $isSuperAdmin);
        $companyIds   = $companies->pluck('id')->toArray();

        $fyId     = FinancialYear::currentId();
        $fromDate = $request->input('from_date');
        $toDate   = $request->input('to_date');

        return DB::table('companies as c')
            ->whereIn('c.id', $companyIds)
            ->where('c.is_active', true)
            ->leftJoin('scan_file as s', function ($join) use ($fyId, $fromDate, $toDate) {
                $join->on('s.Group_Id', '=', 'c.id')
                     ->where('s.Temp_Scan', '=', 'Y')
                     ->where('s.Is_Deleted', '=', 'N')
                     ->when($fyId,     fn($j) => $j->where('s.year_id', $fyId))
                     ->when($fromDate, fn($j) => $j->whereDate('s.Temp_Scan_Date', '>=', $fromDate))
                     ->when($toDate,   fn($j) => $j->whereDate('s.Temp_Scan_Date', '<=', $toDate));
            })
            ->select([
                'c.name as company_name',
                DB::raw("COUNT(s.Scan_Id) AS total_scan"),
                DB::raw("SUM(CASE WHEN s.Bill_Approved = 'N' AND (s.temp_scan_reject IS NULL OR s.temp_scan_reject = 'N') THEN 1 ELSE 0 END) AS pending"),
                DB::raw("SUM(CASE WHEN s.Bill_Approved = 'Y' THEN 1 ELSE 0 END) AS approved"),
                DB::raw("SUM(CASE WHEN s.Bill_Approved = 'R' OR s.temp_scan_reject = 'Y' THEN 1 ELSE 0 END) AS rejected"),
                DB::raw("SUM(CASE WHEN s.Scan_Complete = 'N' AND (s.temp_scan_reject IS NULL OR s.temp_scan_reject = 'N') THEN 1 ELSE 0 END) AS pending_naming"),
                DB::raw("SUM(CASE WHEN s.Scan_Complete = 'Y' AND (s.temp_scan_reject IS NULL OR s.temp_scan_reject = 'N') AND (s.document_verified IS NULL OR s.document_verified = 'N') THEN 1 ELSE 0 END) AS pending_verification"),
            ])
            ->groupBy('c.id', 'c.name')
            ->orderBy('c.name')
            ->get();
    }

    /**
     * GET /workflow/super-scanner/company/{company}/scan/{scan}/support-list (AJAX JSON)
     * Returns the list of supporting files for a scan in company scanning.
     */
    public function companySupportList(Company $company, ScanFile $scan)
    {
        $this->authorizeCompany($company);

        // Ensure scan belongs to this company
        if ($scan->Group_Id !== $company->id) {
            abort(403);
        }

        $files = DB::table('support_file as sf')
            ->leftJoin('supp_document_type_master as dt', 'dt.DocTypeId', '=', 'sf.DocTypeId')
            ->where('sf.Scan_Id', $scan->Scan_Id)
            ->select(['sf.Support_Id', 'sf.File', 'sf.File_Ext', 'sf.File_Location', 'dt.DocTypeName as doc_type_name'])
            ->get();

        return response()->json(['data' => $files]);
    }

    /**
     * POST /workflow/super-scanner/company/{company}/scan/{scan}/supporting (AJAX JSON)
     * Upload one supporting file → S3, insert support_file row for company scanning.
     */
    public function companyStoreSupporting(Request $request, Company $company, ScanFile $scan, S3Service $s3)
    {
        $this->authorizeCompany($company);

        // Ensure scan belongs to this company
        if ($scan->Group_Id !== $company->id) {
            return response()->json([
                'success' => false, 
                'message' => 'This scan does not belong to the specified company.'
            ], 403);
        }

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
     * DELETE /workflow/super-scanner/company/{company}/scan/{scan} (AJAX JSON)
     * Soft-delete a scan for company scanning.
     */
    public function companyDestroyScan(Company $company, ScanFile $scan)
    {
        $this->authorizeCompany($company);

        if ($scan->Group_Id !== $company->id) {
            abort(403);
        }

        DB::transaction(function () use ($scan) {
            DB::table('support_file')->where('Scan_Id', $scan->Scan_Id)->delete();
            $scan->update([
                'Is_Deleted'  => 'Y',
                'Delete_Date' => now(),
                'Deleted_By'  => Auth::id(),
            ]);
        });

        return response()->json(['success' => true]);
    }

    /**
     * POST /workflow/super-scanner/company/{company}/scan/{scan}/final-submit (AJAX JSON)
     * Final submit a scan for company scanning.
     */
    public function companyFinalSubmit(Company $company, ScanFile $scan)
    {
        $this->authorizeCompany($company);

        // Ensure scan belongs to this company
        if ($scan->Group_Id !== $company->id) {
            abort(403);
        }

        $scan->update(['Final_Submit' => 'Y']);

        return response()->json(['success' => true, 'message' => 'Scan submitted for final processing']);
    }

    /**
     * DELETE /workflow/super-scanner/company/{company}/scan/{scan}/support/{supportId} (AJAX JSON)
     * Delete a supporting file for company scanning.
     */
    public function companyDestroySupport(Company $company, ScanFile $scan, int $supportId)
    {
        $this->authorizeCompany($company);

        // Ensure scan belongs to this company
        if ($scan->Group_Id !== $company->id) {
            abort(403);
        }

        DB::table('support_file')
            ->where('Support_Id', $supportId)
            ->where('Scan_Id', $scan->Scan_Id)
            ->delete();

        return response()->json(['success' => true]);
    }

    /**
     * GET /workflow/super-scanner/select/doc-types?q=&page=
     * Paginated, searchable doc-type list for Select2.
     */
    public function docTypesSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $query = DB::table('supp_document_type_master')
            ->where('IsActive', 1)
            ->orderBy('DocTypeName');

        if ($q !== '') {
            $query->where('DocTypeName', 'like', "%{$q}%");
        }

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)->get(['DocTypeId as id', 'DocTypeName as text']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }
}

