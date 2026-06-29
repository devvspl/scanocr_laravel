<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\ScanFile;
use App\Models\User;
use App\Services\UserAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user         = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');

        // Workflow-only role users redirect
        $workflowRedirects = [
            'Temp Scanning'   => 'workflow.temp-scan.index',
            'Direct Scanning' => 'workflow.direct-scan.index',
            'Super Scanner'   => 'workflow.super-scanner.index',
            'Bill Approval'   => 'workflow.bill-approval.index',
            'Classification'  => 'workflow.classification.index',
            'Data Punching'   => 'workflow.punching.index',
            'Punch Approval'  => 'workflow.punch-approval.index',
        ];

        if (!$isSuperAdmin && $user->roles->count() === 1) {
            foreach ($workflowRedirects as $role => $routeName) {
                if ($user->hasRole($role)) return redirect()->route($routeName);
            }
        }

        return view('dashboard');
    }

    // ── Dashboard Data API (AJAX) ─────────────────────────────────────────────

    public function data(Request $request)
    {
        $user         = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $companyIds   = UserAccessService::allowedCompanies($user->id, $isSuperAdmin)->pluck('id')->toArray();

        // ── Base Query with Filters ───────────────────────────────────────────
        $base = DB::table('scan_file as s')
            ->whereIn('s.Group_Id', $companyIds)
            ->where('s.Is_Deleted', 'N')
            ->when($request->filled('fy_id'), fn($q) => $q->where('s.year_id', $request->input('fy_id')))
            ->when($request->filled('company_id'), fn($q) => $q->where('s.Group_Id', $request->input('company_id')))
            ->when($request->filled('location_id'), fn($q) => $q->where('s.Location', $request->input('location_id')))
            ->when($request->filled('user_id'), fn($q) => $q->where(DB::raw("IF(s.Temp_Scan='Y', s.Temp_Scan_By, s.Scan_By)"), $request->input('user_id')))
            ->when($request->filled('doc_type_id'), fn($q) => $q->where('s.DocType_Id', $request->input('doc_type_id')))
            ->when($request->filled('from_date'), fn($q) => $q->whereDate(DB::raw("COALESCE(s.Temp_Scan_Date, s.Scan_Date)"), '>=', $request->input('from_date')))
            ->when($request->filled('to_date'), fn($q) => $q->whereDate(DB::raw("COALESCE(s.Temp_Scan_Date, s.Scan_Date)"), '<=', $request->input('to_date')));

        // ── KPI Stage Status ──────────────────────────────────────────────────
        $kpi = (clone $base)->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN s.Scan_Complete = 'N' AND (s.temp_scan_reject IS NULL OR s.temp_scan_reject = 'N') THEN 1 ELSE 0 END) as pending_naming,
            SUM(CASE WHEN s.Scan_Complete = 'Y' AND (s.document_verified IS NULL OR s.document_verified = 'N') AND (s.temp_scan_reject IS NULL OR s.temp_scan_reject = 'N') THEN 1 ELSE 0 END) as pending_verification,
            SUM(CASE WHEN s.document_verified = 'Y' THEN 1 ELSE 0 END) as document_received,
            SUM(CASE WHEN s.Bill_Approved = 'N' AND (s.temp_scan_reject IS NULL OR s.temp_scan_reject = 'N') THEN 1 ELSE 0 END) as pending_bill_approval,
            SUM(CASE WHEN s.Bill_Approved = 'Y' AND (s.is_extract = 'N' OR s.is_extract IS NULL) AND (s.is_autoclassified IS NULL OR s.is_autoclassified != 'Y') THEN 1 ELSE 0 END) as pending_classification,
            SUM(CASE WHEN s.is_extract = 'Y' AND (s.File_Punched = 'N' OR s.File_Punched IS NULL) THEN 1 ELSE 0 END) as pending_punching,
            SUM(CASE WHEN s.File_Punched = 'Y' AND s.File_Approved = 'N' AND (s.Is_Rejected IS NULL OR s.Is_Rejected = 'N') THEN 1 ELSE 0 END) as pending_punch_approval,
            SUM(CASE WHEN s.File_Approved = 'Y' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN s.Is_Rejected = 'Y' OR s.Bill_Approved = 'R' OR s.temp_scan_reject = 'Y' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN s.Bill_Approved = 'Y' THEN 1 ELSE 0 END) as bill_approved_total,
            SUM(CASE WHEN s.is_extract = 'Y' THEN 1 ELSE 0 END) as extracted_total,
            SUM(CASE WHEN s.is_extract = 'N' OR s.is_extract IS NULL THEN 1 ELSE 0 END) as pending_extraction
        ")->first();

        // ── Today's Activity ──────────────────────────────────────────────────
        $today = (clone $base)->selectRaw("
            SUM(CASE WHEN DATE(COALESCE(s.Temp_Scan_Date, s.Scan_Date)) = CURDATE() THEN 1 ELSE 0 END) as scanned,
            SUM(CASE WHEN DATE(s.document_verified_date) = CURDATE() AND s.document_verified = 'Y' THEN 1 ELSE 0 END) as doc_received,
            SUM(CASE WHEN DATE(s.Bill_Approver_Date) = CURDATE() AND s.Bill_Approved = 'Y' THEN 1 ELSE 0 END) as bill_approved,
            SUM(CASE WHEN DATE(s.classified_date) = CURDATE() THEN 1 ELSE 0 END) as classified,
            SUM(CASE WHEN DATE(s.Punch_Date) = CURDATE() THEN 1 ELSE 0 END) as punched,
            SUM(CASE WHEN DATE(s.Approve_Date) = CURDATE() AND s.File_Approved = 'Y' THEN 1 ELSE 0 END) as final_approved
        ")->first();

        // Extraction today (from tbl_queues)
        $extractionToday = DB::table('tbl_queues')
            ->whereDate('completed_at', today())
            ->where('status', 'completed')
            ->count();

        // ── Monthly Trend (all FY years comparison — 12 months) ─────────────────
        $fys = FinancialYear::orderByDesc('start_date')->get();
        $monthly = [];
        $months = ['Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan','Feb','Mar'];
        foreach ($fys as $fy) {
            $fyData = [];
            for ($m = 0; $m < 12; $m++) {
                $date = $fy->start_date->copy()->addMonths($m);
                $count = (clone $base)->whereYear(DB::raw("COALESCE(s.Temp_Scan_Date, s.Scan_Date)"), $date->year)
                    ->whereMonth(DB::raw("COALESCE(s.Temp_Scan_Date, s.Scan_Date)"), $date->month)->count();
                $fyData[] = $count;
            }
            $monthly[] = ['label' => $fy->label, 'data' => $fyData];
        }
        $monthlyLabels = $months;

        // ── Company Wise ──────────────────────────────────────────────────────
        $companyWise = (clone $base)->join('companies as c', 'c.id', '=', 's.Group_Id')
            ->selectRaw("c.name as label, COUNT(*) as total, SUM(CASE WHEN s.Bill_Approved='Y' THEN 1 ELSE 0 END) as bill_approved, SUM(CASE WHEN s.File_Punched='Y' THEN 1 ELSE 0 END) as punched, SUM(CASE WHEN s.File_Approved='Y' THEN 1 ELSE 0 END) as completed")
            ->groupBy('c.name')->orderByDesc(DB::raw('COUNT(*)'))->get();

        // ── Bill Approver ─────────────────────────────────────────────────────
        $billApprover = (clone $base)->join('users as u', 'u.id', '=', 's.Bill_Approver')
            ->selectRaw("u.name as label, COUNT(*) as total, SUM(CASE WHEN s.Bill_Approved='Y' THEN 1 ELSE 0 END) as approved, SUM(CASE WHEN s.Bill_Approved='R' THEN 1 ELSE 0 END) as rejected, SUM(CASE WHEN s.Bill_Approved='N' THEN 1 ELSE 0 END) as pending, ROUND(SUM(CASE WHEN s.Bill_Approved='Y' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 1) as approve_rate, ROUND(SUM(CASE WHEN s.Bill_Approved='R' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 1) as reject_rate")
            ->groupBy('u.name')->orderByDesc(DB::raw('COUNT(*)'))->get();

        // ── Location Wise (top 25 locations with stage breakdown for line chart) ──
        $locationWise = (clone $base)->leftJoin('master_work_location as l', 'l.location_id', '=', 's.Location')
            ->selectRaw("COALESCE(l.location_name, 'Unknown') as label,
                COUNT(*) as total,
                SUM(CASE WHEN s.Bill_Approved = 'N' AND (s.temp_scan_reject IS NULL OR s.temp_scan_reject = 'N') THEN 1 ELSE 0 END) as pending_approval,
                SUM(CASE WHEN s.Bill_Approved = 'Y' AND (s.is_extract = 'N' OR s.is_extract IS NULL) THEN 1 ELSE 0 END) as pending_classification,
                SUM(CASE WHEN s.is_extract = 'Y' AND (s.File_Punched = 'N' OR s.File_Punched IS NULL) THEN 1 ELSE 0 END) as pending_punching,
                SUM(CASE WHEN s.File_Punched = 'Y' AND s.File_Approved = 'N' AND (s.Is_Rejected IS NULL OR s.Is_Rejected = 'N') THEN 1 ELSE 0 END) as pending_punch_approval,
                SUM(CASE WHEN s.File_Approved = 'Y' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN s.Is_Rejected = 'Y' OR s.Bill_Approved = 'R' OR s.temp_scan_reject = 'Y' THEN 1 ELSE 0 END) as rejected")
            ->groupBy('l.location_name')->orderByDesc(DB::raw('COUNT(*)'))->limit(25)->get();

        // ── Top Document Types ────────────────────────────────────────────────
        $topDocTypes = (clone $base)->leftJoin('document_types as dt', 'dt.id', '=', 's.DocType_Id')->where('s.DocType_Id', '>', 0)
            ->selectRaw("COALESCE(dt.label, 'Unknown') as label, COUNT(*) as total")
            ->groupBy('dt.label')->orderByDesc('dt.label')->get();

        // ── Top Scanners ──────────────────────────────────────────────────────
        $topScanners = (clone $base)->leftJoin('users as u', 'u.id', '=', DB::raw("IF(s.Temp_Scan='Y', s.Temp_Scan_By, s.Scan_By)"))
            ->selectRaw("u.name as label, COUNT(*) as total")
            ->groupBy('u.name')->orderByDesc(DB::raw('COUNT(*)'))->limit(10)->get();

        // ── Top Punchers ──────────────────────────────────────────────────────
        $topPunchers = (clone $base)->leftJoin('users as u', 'u.id', '=', 's.Punch_By')->where('s.File_Punched', 'Y')
            ->selectRaw("u.name as label, COUNT(*) as total")
            ->groupBy('u.name')->orderByDesc(DB::raw('COUNT(*)'))->limit(10)->get();

        // ── Top Approvers ─────────────────────────────────────────────────────
        $topApprovers = (clone $base)->leftJoin('users as u', 'u.id', '=', 's.Bill_Approver')->where('s.Bill_Approved', 'Y')
            ->selectRaw("u.name as label, COUNT(*) as total")
            ->groupBy('u.name')->orderByDesc(DB::raw('COUNT(*)'))->limit(10)->get();

        // ── Top Locations ─────────────────────────────────────────────────────
        $topLocations = (clone $base)->leftJoin('master_work_location as l', 'l.location_id', '=', 's.Location')
            ->selectRaw("COALESCE(l.location_name, 'Unknown') as label, COUNT(*) as total")
            ->groupBy('l.location_name')->orderByDesc(DB::raw('COUNT(*)'))->limit(10)->get();

        // ── Top Companies ─────────────────────────────────────────────────────
        $topCompanies = (clone $base)->join('companies as c', 'c.id', '=', 's.Group_Id')
            ->selectRaw("c.name as label, COUNT(*) as total")
            ->groupBy('c.name')->orderByDesc(DB::raw('COUNT(*)'))->limit(10)->get();

        return response()->json([
            'kpi'           => $kpi,
            'today'         => $today,
            'extractionToday' => $extractionToday,
            'monthly'       => ['labels' => $monthlyLabels, 'datasets' => $monthly],
            'companyWise'   => $companyWise,
            'billApprover'  => $billApprover,
            'locationWise'  => $locationWise,
            'topDocTypes'   => $topDocTypes,
            'topScanners'   => $topScanners,
            'topPunchers'   => $topPunchers,
            'topApprovers'  => $topApprovers,
            'topLocations'  => $topLocations,
            'topCompanies'  => $topCompanies,
        ]);
    }

    // ── Global Search (AJAX) ──────────────────────────────────────────────────

    public function globalSearch(Request $request)
    {
        $q    = trim($request->input('q', ''));
        $page = max(1, (int) $request->input('page', 1));
        $per  = 15;

        if (strlen($q) < 2) {
            return response()->json(['results' => [], 'has_more' => false]);
        }

        $user         = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $companyIds   = UserAccessService::allowedCompanies($user->id, $isSuperAdmin)->pluck('id')->toArray();

        $query = DB::table('scan_file as s')
            ->leftJoin('companies as c', 'c.id', '=', 's.Group_Id')
            ->leftJoin('master_firm as mf', 'mf.firm_id', '=', 's.firm_id')
            ->leftJoin('document_types as dt', 'dt.id', '=', 's.DocType_Id')
            ->whereIn('s.Group_Id', $companyIds)
            ->where('s.Is_Deleted', 'N')
            ->where(fn($qb) => $qb
                ->where('s.Scan_Id', 'like', "%{$q}%")
                ->orWhere('s.Document_name', 'like', "%{$q}%")
                ->orWhere('s.bill_no_voucher_no', 'like', "%{$q}%")
                ->orWhere('s.File', 'like', "%{$q}%")
                ->orWhere('mf.firm_name', 'like', "%{$q}%")
                ->orWhere('c.name', 'like', "%{$q}%")
            )
            ->select([
                's.Scan_Id', 's.Document_name', 's.File', 's.File_Ext',
                's.bill_no_voucher_no', 's.bill_voucher_date',
                'c.name as company_name', 'mf.firm_name as vendor_name',
                'dt.label as doc_type',
                DB::raw("CASE 
                    WHEN s.File_Approved='Y' THEN 'Approved'
                    WHEN s.Is_Rejected='Y' THEN 'Rejected'
                    WHEN s.File_Punched='Y' THEN 'Punched'
                    WHEN s.is_extract='Y' THEN 'Classified'
                    WHEN s.Bill_Approved='Y' THEN 'Bill Approved'
                    WHEN s.Bill_Approved='R' THEN 'Bill Rejected'
                    ELSE 'Pending'
                END as status"),
            ])
            ->orderByDesc('s.Scan_Id');

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)->get();

        return response()->json(['results' => $results, 'has_more' => ($page * $per) < $total, 'total' => $total]);
    }
}
