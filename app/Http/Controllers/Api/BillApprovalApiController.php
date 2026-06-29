<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScanActionLog;
use App\Models\ScanFile;
use App\Models\User;
use App\Services\UserAccessService;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BillApprovalApiController extends Controller
{
    // ── Login ─────────────────────────────────────────────────────────────────

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials.'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['success' => false, 'message' => 'Account is deactivated.'], 403);
        }

        // Generate JWT
        $payload = [
            'sub' => $user->id,
            'iat' => time(),
            'exp' => time() + (30 * 24 * 60 * 60), // 30 days
        ];

        $token = JWT::encode($payload, config('app.key'), 'HS256');

        // Save FCM token if provided
        if ($request->filled('fcm_token')) {
            $user->update(['fcm_token' => $request->fcm_token]);
        }

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    // ── Tab Counts ────────────────────────────────────────────────────────────

    public function tabCounts(Request $request)
    {
        $userId     = Auth::id();
        $allowedIds = $this->allowedCompanyIds();

        $base = DB::table('scan_file')
            ->whereIn('Group_Id', $allowedIds)
            ->where('Bill_Approver', $userId)
            ->where('Is_Deleted', 'N');

        // Apply filters
        $this->applyFilters($base, $request);

        return response()->json([
            'success' => true,
            'data'    => [
                'pending'  => (clone $base)->where('Bill_Approved', 'N')->where(fn($q) => $q->whereNull('temp_scan_reject')->orWhere('temp_scan_reject', 'N'))->count(),
                'approved' => (clone $base)->where('Bill_Approved', 'Y')->count(),
                'rejected' => (clone $base)->where(fn($q) => $q->where('Bill_Approved', 'R')->orWhere('temp_scan_reject', 'Y'))->count(),
                'all'      => (clone $base)->count(),
            ],
        ]);
    }

    // ── List Bills ────────────────────────────────────────────────────────────

    public function list(Request $request)
    {
        $userId     = Auth::id();
        $allowedIds = $this->allowedCompanyIds();
        $tab        = $request->input('tab', 'pending');
        $page       = max(1, (int) $request->input('page', 1));
        $perPage    = min(50, max(10, (int) $request->input('per_page', 20)));
        $search     = $request->input('search', '');

        $query = DB::table('scan_file as s')
            ->leftJoin('companies as c', 'c.id', '=', 's.Group_Id')
            ->leftJoin('master_work_location as l', 'l.location_id', '=', 's.Location')
            ->leftJoin('master_firm as mf', 'mf.firm_id', '=', 's.firm_id')
            ->leftJoin('users as u', 'u.id', '=', DB::raw("IF(s.Temp_Scan='Y', s.Temp_Scan_By, s.Scan_By)"))
            ->whereIn('s.Group_Id', $allowedIds)
            ->where('s.Bill_Approver', $userId)
            ->where('s.Is_Deleted', 'N');

        // Apply filters
        if ($request->filled('company_id'))  $query->where('s.Group_Id', $request->input('company_id'));
        if ($request->filled('fy_id'))       $query->where('s.year_id', $request->input('fy_id'));
        if ($request->filled('location_id')) $query->where('s.Location', $request->input('location_id'));
        if ($request->filled('scanned_by'))  $query->where(DB::raw("IF(s.Temp_Scan='Y', s.Temp_Scan_By, s.Scan_By)"), $request->input('scanned_by'));
        if ($request->filled('from_date'))   $query->whereDate(DB::raw("IF(s.Temp_Scan='Y', s.Temp_Scan_Date, s.Scan_Date)"), '>=', $request->input('from_date'));
        if ($request->filled('to_date'))     $query->whereDate(DB::raw("IF(s.Temp_Scan='Y', s.Temp_Scan_Date, s.Scan_Date)"), '<=', $request->input('to_date'));

        // Tab filter
        match ($tab) {
            'pending'  => $query->where('s.Bill_Approved', 'N')->where(fn($q) => $q->whereNull('s.temp_scan_reject')->orWhere('s.temp_scan_reject', 'N')),
            'approved' => $query->where('s.Bill_Approved', 'Y'),
            'rejected' => $query->where(fn($q) => $q->where('s.Bill_Approved', 'R')->orWhere('s.temp_scan_reject', 'Y')),
            default    => null,
        };

        // Search
        if ($search) {
            $query->where(fn($q) => $q
                ->where('s.Document_name', 'like', "%{$search}%")
                ->orWhere('s.bill_no_voucher_no', 'like', "%{$search}%")
                ->orWhere('mf.firm_name', 'like', "%{$search}%")
                ->orWhere('c.name', 'like', "%{$search}%")
            );
        }

        $total = $query->count();

        $rows = $query->select([
                's.Scan_Id', 's.Document_name', 's.File', 's.File_Location', 's.File_Ext',
                's.bill_voucher_date', 's.bill_no_voucher_no', 's.Bill_Approved',
                's.Bill_Approver_Date', 's.Bill_Approver_Remark', 's.temp_scan_reject',
                'c.name as company_name', 'l.location_name',
                'mf.firm_name as vendor_name',
                'u.name as scanned_by',
                DB::raw("IF(s.Temp_Scan='Y', s.Temp_Scan_Date, s.Scan_Date) as scan_date"),
            ])
            ->orderByDesc(DB::raw("IF(s.Temp_Scan='Y', s.Temp_Scan_Date, s.Scan_Date)"))
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn($r) => [
                'scan_id'       => $r->Scan_Id,
                'document_name' => $r->Document_name ?? $r->File,
                'file_url'      => $r->File_Location,
                'file_ext'      => $r->File_Ext,
                'bill_date'     => $r->bill_voucher_date,
                'bill_no'       => $r->bill_no_voucher_no,
                'company'       => $r->company_name,
                'location'      => $r->location_name,
                'vendor'        => $r->vendor_name,
                'scanned_by'    => $r->scanned_by,
                'scan_date'     => $r->scan_date,
                'status'        => $this->resolveStatus($r),
                'remark'        => $r->Bill_Approver_Remark,
                'approval_date' => $r->Bill_Approver_Date,
            ]);

        return response()->json([
            'success'      => true,
            'data'         => $rows,
            'total'        => $total,
            'page'         => $page,
            'per_page'     => $perPage,
            'total_pages'  => ceil($total / $perPage),
        ]);
    }

    // ── Detail ────────────────────────────────────────────────────────────────

    public function detail($scanId)
    {
        $userId     = Auth::id();
        $allowedIds = $this->allowedCompanyIds();

        $detail = DB::table('scan_file as s')
            ->leftJoin('companies as c', 'c.id', '=', 's.Group_Id')
            ->leftJoin('master_work_location as l', 'l.location_id', '=', 's.Location')
            ->leftJoin('master_firm as mf', 'mf.firm_id', '=', 's.firm_id')
            ->leftJoin('financial_years as fy', 'fy.id', '=', 's.year_id')
            ->leftJoin('users as u', 'u.id', '=', DB::raw("IF(s.Temp_Scan='Y', s.Temp_Scan_By, s.Scan_By)"))
            ->where('s.Scan_Id', $scanId)
            ->whereIn('s.Group_Id', $allowedIds)
            ->where('s.Bill_Approver', $userId)
            ->select([
                's.Scan_Id', 's.Document_name', 's.File', 's.File_Location', 's.File_Ext',
                's.bill_voucher_date', 's.bill_no_voucher_no',
                's.Bill_Approved', 's.Bill_Approver_Date', 's.Bill_Approver_Remark',
                'c.name as company_name', 'l.location_name', 'fy.label as fy_label',
                'mf.firm_name as vendor_name', 'u.name as scanned_by',
                DB::raw("IF(s.Temp_Scan='Y', s.Temp_Scan_Date, s.Scan_Date) as scan_date"),
            ])
            ->first();

        if (!$detail) {
            return response()->json(['success' => false, 'message' => 'Scan not found or access denied.'], 404);
        }

        // Support files
        $supports = DB::table('support_file as sf')
            ->leftJoin('supp_document_type_master as dt', 'dt.DocTypeId', '=', 'sf.DocTypeId')
            ->where('sf.Scan_Id', $scanId)
            ->select(['sf.Support_Id', 'sf.File', 'sf.File_Ext', 'sf.File_Location', 'dt.DocTypeName as doc_type_name'])
            ->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'scan_id'       => $detail->Scan_Id,
                'document_name' => $detail->Document_name ?? $detail->File,
                'file_url'      => $detail->File_Location,
                'file_ext'      => $detail->File_Ext,
                'bill_date'     => $detail->bill_voucher_date,
                'bill_no'       => $detail->bill_no_voucher_no,
                'company'       => $detail->company_name,
                'location'      => $detail->location_name,
                'fy'            => $detail->fy_label,
                'vendor'        => $detail->vendor_name,
                'scanned_by'    => $detail->scanned_by,
                'scan_date'     => $detail->scan_date,
                'status'        => $this->resolveStatus($detail),
                'remark'        => $detail->Bill_Approver_Remark,
                'approval_date' => $detail->Bill_Approver_Date,
                'supports'      => $supports->map(fn($s) => [
                    'id'       => $s->Support_Id,
                    'file'     => $s->File,
                    'file_ext' => $s->File_Ext,
                    'file_url' => $s->File_Location,
                    'doc_type' => $s->doc_type_name,
                ]),
            ],
        ]);
    }

    // ── Approve ───────────────────────────────────────────────────────────────

    public function approve(Request $request, $scanId)
    {
        $scan = $this->findAuthorizedScan($scanId);
        if (!$scan) {
            return response()->json(['success' => false, 'message' => 'Scan not found or access denied.'], 404);
        }

        DB::table('scan_file')->where('Scan_Id', $scanId)->update([
            'Bill_Approved'        => 'Y',
            'Bill_Approver_Date'   => now()->toDateString(),
            'Bill_Approver_Remark' => $request->input('remark'),
        ]);

        ScanActionLog::log($scanId, 'bill_approved', 'Bill Approved (App)', $request->input('remark'));

        return response()->json(['success' => true, 'message' => 'Bill approved successfully.']);
    }

    // ── Reject ────────────────────────────────────────────────────────────────

    public function reject(Request $request, $scanId)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $scan = $this->findAuthorizedScan($scanId);
        if (!$scan) {
            return response()->json(['success' => false, 'message' => 'Scan not found or access denied.'], 404);
        }

        DB::table('scan_file')->where('Scan_Id', $scanId)->update([
            'Bill_Approved'        => 'R',
            'Bill_Approver_Date'   => now()->toDateString(),
            'Bill_Approver_Remark' => $request->input('reason'),
        ]);

        ScanActionLog::log($scanId, 'bill_rejected', 'Bill Rejected (App)', $request->input('reason'));

        return response()->json(['success' => true, 'message' => 'Bill rejected.']);
    }

    // ── Update FCM Token ──────────────────────────────────────────────────────

    public function updateFcmToken(Request $request)
    {
        $request->validate(['fcm_token' => 'required|string']);
        Auth::user()->update(['fcm_token' => $request->fcm_token]);
        return response()->json(['success' => true]);
    }

    // ── Profile ───────────────────────────────────────────────────────────────

    public function profile()
    {
        $user = Auth::user();
        return response()->json([
            'success' => true,
            'data'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    // ── Filter Data Endpoints ────────────────────────────────────────────────

    public function filterCompanies()
    {
        $allowedIds = $this->allowedCompanyIds();
        $companies  = DB::table('companies')
            ->whereIn('id', $allowedIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['success' => true, 'data' => $companies]);
    }

    public function filterLocations()
    {
        $locations = DB::table('master_work_location')
            ->where('status', 'A')
            ->where('is_deleted', 'N')
            ->orderBy('location_name')
            ->get(['location_id as id', 'location_name as name']);

        return response()->json(['success' => true, 'data' => $locations]);
    }

    public function filterFinancialYears()
    {
        $fys = DB::table('financial_years')
            ->orderByDesc('start_date')
            ->get(['id', 'label as name', 'is_current']);

        return response()->json(['success' => true, 'data' => $fys]);
    }

    public function filterUsers()
    {
        $users = DB::table('users')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['success' => true, 'data' => $users]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════════════════════

    private function allowedCompanyIds(): array
    {
        $user         = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        return UserAccessService::allowedCompanies($user->id, $isSuperAdmin)->pluck('id')->toArray();
    }

    private function findAuthorizedScan($scanId)
    {
        return DB::table('scan_file')
            ->where('Scan_Id', $scanId)
            ->whereIn('Group_Id', $this->allowedCompanyIds())
            ->where('Bill_Approver', Auth::id())
            ->where('Is_Deleted', 'N')
            ->first();
    }

    private function resolveStatus($row): string
    {
        if (($row->temp_scan_reject ?? null) === 'Y' || $row->Bill_Approved === 'R') return 'rejected';
        if ($row->Bill_Approved === 'Y') return 'approved';
        return 'pending';
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('company_id'))  $query->where('Group_Id', $request->input('company_id'));
        if ($request->filled('fy_id'))       $query->where('year_id', $request->input('fy_id'));
        if ($request->filled('location_id')) $query->where('Location', $request->input('location_id'));
        if ($request->filled('scanned_by'))  $query->where(DB::raw("IF(Temp_Scan='Y', Temp_Scan_By, Scan_By)"), $request->input('scanned_by'));
        if ($request->filled('from_date'))   $query->whereDate(DB::raw("IF(Temp_Scan='Y', Temp_Scan_Date, Scan_Date)"), '>=', $request->input('from_date'));
        if ($request->filled('to_date'))     $query->whereDate(DB::raw("IF(Temp_Scan='Y', Temp_Scan_Date, Scan_Date)"), '<=', $request->input('to_date'));
    }
}
