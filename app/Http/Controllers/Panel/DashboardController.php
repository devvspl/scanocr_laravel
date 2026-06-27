<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\ScanFile;
use App\Models\User;
use App\Services\UserAccessService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user         = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');

        // Workflow-only role users redirect to their module
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
                if ($user->hasRole($role)) {
                    return redirect()->route($routeName);
                }
            }
        }

        $company    = Company::getDefault();
        $currentFY  = FinancialYear::where('is_current', true)->first();
        $companies  = UserAccessService::allowedCompanies($user->id, $isSuperAdmin);
        $companyIds = $companies->pluck('id')->toArray();
        $financialYears = FinancialYear::orderByDesc('start_date')->get(['id', 'label', 'is_current']);

        // Filters from request (defaults: all years, all companies)
        $request    = request();
        $filterFy   = $request->input('fy_id');
        $filterComp = $request->input('company_id');

        // FY Progress (always show current FY)
        $fyProgress = null;
        if ($currentFY) {
            $total   = max($currentFY->start_date->diffInDays($currentFY->end_date), 1);
            $elapsed = $currentFY->start_date->diffInDays(now()->min($currentFY->end_date));
            $fyProgress = round(($elapsed / $total) * 100);
        }

        // ── Base query (all years by default, filtered by access) ─────────────
        $base = DB::table('scan_file')
            ->whereIn('Group_Id', $companyIds)
            ->where('Is_Deleted', 'N')
            ->when($filterFy, fn($q) => $q->where('year_id', $filterFy))
            ->when($filterComp, fn($q) => $q->where('Group_Id', $filterComp));

        // ── Top Stats ─────────────────────────────────────────────────────────
        $totalScans     = (clone $base)->count();
        $todayScans     = (clone $base)->whereDate(DB::raw("COALESCE(Temp_Scan_Date, Scan_Date)"), today())->count();
        $totalCompanies = count($companyIds);
        $totalUsers     = User::where('is_active', true)->count();

        // ── Stage-wise Pipeline Counts (single optimized query) ───────────────
        $pipeline = (clone $base)->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN Bill_Approved = 'N' AND (temp_scan_reject IS NULL OR temp_scan_reject = 'N') THEN 1 ELSE 0 END) as pending_bill_approval,
            SUM(CASE WHEN Bill_Approved = 'Y' AND (is_extract = 'N' OR is_extract IS NULL) AND is_autoclassified != 'Y' THEN 1 ELSE 0 END) as pending_classification,
            SUM(CASE WHEN is_extract = 'Y' AND (File_Punched = 'N' OR File_Punched IS NULL) THEN 1 ELSE 0 END) as pending_punching,
            SUM(CASE WHEN File_Punched = 'Y' AND File_Approved = 'N' AND (Is_Rejected IS NULL OR Is_Rejected = 'N') THEN 1 ELSE 0 END) as pending_punch_approval,
            SUM(CASE WHEN File_Approved = 'Y' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN Is_Rejected = 'Y' OR Bill_Approved = 'R' OR temp_scan_reject = 'Y' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN Bill_Approved = 'Y' THEN 1 ELSE 0 END) as bill_approved,
            SUM(CASE WHEN is_extract = 'Y' THEN 1 ELSE 0 END) as classified,
            SUM(CASE WHEN File_Punched = 'Y' THEN 1 ELSE 0 END) as punched
        ")->first();

        // ── Scanning Type Breakdown ───────────────────────────────────────────
        $scanTypes = (clone $base)->selectRaw("
            SUM(CASE WHEN Temp_Scan = 'Y' THEN 1 ELSE 0 END) as temp_count,
            SUM(CASE WHEN Temp_Scan IS NULL OR Temp_Scan != 'Y' THEN 1 ELSE 0 END) as direct_count
        ")->first();

        // ── Today's Activity ──────────────────────────────────────────────────
        $todayActivity = (clone $base)->selectRaw("
            SUM(CASE WHEN DATE(COALESCE(Temp_Scan_Date, Scan_Date)) = CURDATE() THEN 1 ELSE 0 END) as scanned_today,
            SUM(CASE WHEN DATE(Bill_Approver_Date) = CURDATE() AND Bill_Approved = 'Y' THEN 1 ELSE 0 END) as approved_today,
            SUM(CASE WHEN DATE(Punch_Date) = CURDATE() THEN 1 ELSE 0 END) as punched_today,
            SUM(CASE WHEN DATE(Approve_Date) = CURDATE() AND File_Approved = 'Y' THEN 1 ELSE 0 END) as final_approved_today
        ")->first();

        // ── Monthly Trend (Last 6 Months) ─────────────────────────────────────
        $monthlyTrend = collect(range(5, 0))->map(function ($i) use ($base) {
            $date = now()->subMonths($i);
            return [
                'month' => $date->format('M'),
                'count' => (clone $base)
                    ->whereYear(DB::raw("COALESCE(Temp_Scan_Date, Scan_Date)"), $date->year)
                    ->whereMonth(DB::raw("COALESCE(Temp_Scan_Date, Scan_Date)"), $date->month)
                    ->count(),
            ];
        });

        // ── Recent Activity (last 10 scans) ───────────────────────────────────
        $recentScans = DB::table('scan_file as s')
            ->leftJoin('companies as c', 'c.id', '=', 's.Group_Id')
            ->leftJoin('users as u', 'u.id', '=', DB::raw("IF(s.Temp_Scan='Y', s.Temp_Scan_By, s.Scan_By)"))
            ->leftJoin('document_types as dt', 'dt.id', '=', 's.DocType_Id')
            ->whereIn('s.Group_Id', $companyIds)
            ->where('s.Is_Deleted', 'N')
            ->when($filterFy, fn($q) => $q->where('s.year_id', $filterFy))
            ->when($filterComp, fn($q) => $q->where('s.Group_Id', $filterComp))
            ->orderByDesc(DB::raw("COALESCE(s.Temp_Scan_Date, s.Scan_Date)"))
            ->limit(10)
            ->select([
                's.Scan_Id', 's.File', 's.File_Ext', 's.Document_name',
                's.Temp_Scan', 's.Bill_Approved', 's.temp_scan_reject',
                's.File_Punched', 's.File_Approved', 's.Is_Rejected',
                DB::raw("COALESCE(s.Temp_Scan_Date, s.Scan_Date) as scan_date"),
                'c.name as company_name', 'u.name as scanned_by',
                'dt.label as doc_type',
            ])
            ->get();

        // ── Top Companies ─────────────────────────────────────────────────────
        $topCompanies = DB::table('scan_file as s')
            ->join('companies as c', 'c.id', '=', 's.Group_Id')
            ->whereIn('s.Group_Id', $companyIds)
            ->where('s.Is_Deleted', 'N')
            ->when($filterFy, fn($q) => $q->where('s.year_id', $filterFy))
            ->when($filterComp, fn($q) => $q->where('s.Group_Id', $filterComp))
            ->selectRaw('c.name as company_name, COUNT(*) as scan_count')
            ->groupBy('c.id', 'c.name')
            ->orderByDesc('scan_count')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'company', 'currentFY', 'fyProgress', 'totalUsers', 'totalCompanies',
            'totalScans', 'todayScans', 'pipeline', 'scanTypes', 'todayActivity',
            'recentScans', 'monthlyTrend', 'topCompanies', 'companies', 'financialYears',
            'filterFy', 'filterComp'
        ));
    }
}
