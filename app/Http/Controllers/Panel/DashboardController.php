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

        // Workflow-only role users have no business on the dashboard.
        // Only redirect when the user has exactly one role and it is a workflow role —
        // multi-role users (e.g. Temp Scanning + Direct Scanning) need the full nav panel.
        $workflowRedirects = [
            'Temp Scanning'   => 'workflow.temp-scan.index',
            'Direct Scanning' => 'workflow.direct-scan.index',
            'Super Scanner'   => 'workflow.super-scanner.index',
            'Bill Approval'   => 'workflow.bill-approval.index',
            'Classification'  => 'workflow.classification.index',
        ];

        if (!$isSuperAdmin && $user->roles->count() === 1) {
            foreach ($workflowRedirects as $role => $routeName) {
                if ($user->hasRole($role)) {
                    return redirect()->route($routeName);
                }
            }
        }

        $company      = Company::getDefault();
        $currentFY    = FinancialYear::where('is_current', true)->first();
        $fyId         = FinancialYear::currentId();
        $companies    = UserAccessService::allowedCompanies($user->id, $isSuperAdmin);
        $companyIds   = $companies->pluck('id')->toArray();

        // Financial Year Progress
        $fyProgress = null;
        if ($currentFY) {
            $total   = max($currentFY->start_date->diffInDays($currentFY->end_date), 1);
            $elapsed = $currentFY->start_date->diffInDays(now()->min($currentFY->end_date));
            $fyProgress = round(($elapsed / $total) * 100);
        }

        // Core Workflow Statistics
        $totalUsers = User::where('is_active', true)->count();
        $totalCompanies = count($companyIds);
        
        // Scanning Overview
        $scanQuery = DB::table('scan_file')
            ->whereIn('Group_Id', $companyIds)
            ->where('Is_Deleted', 'N')
            ->when($fyId, fn($q) => $q->where('year_id', $fyId));

        $totalScans = (clone $scanQuery)->count();
        $todayScans = (clone $scanQuery)->whereDate('Temp_Scan_Date', today())->count();

        // Workflow Status Breakdown
        $workflowStats = [
            // Temp Scanning (Temp_Scan = Y)
            'temp_total'    => (clone $scanQuery)->where('Temp_Scan', 'Y')->count(),
            'temp_pending'  => (clone $scanQuery)->where('Temp_Scan', 'Y')
                ->where('Bill_Approved', 'N')
                ->where(fn($q) => $q->whereNull('temp_scan_reject')->orWhere('temp_scan_reject', 'N'))
                ->count(),
            'temp_approved' => (clone $scanQuery)->where('Temp_Scan', 'Y')
                ->where('Bill_Approved', 'Y')
                ->count(),
            'temp_rejected' => (clone $scanQuery)->where('Temp_Scan', 'Y')
                ->where(fn($q) => $q->where('Bill_Approved', 'R')->orWhere('temp_scan_reject', 'Y'))
                ->count(),

            // Direct Scanning (Temp_Scan = N or NULL)
            'direct_total'    => (clone $scanQuery)->where(fn($q) => $q->where('Temp_Scan', 'N')->orWhereNull('Temp_Scan'))->count(),
            'direct_pending'  => (clone $scanQuery)->where(fn($q) => $q->where('Temp_Scan', 'N')->orWhereNull('Temp_Scan'))
                ->where('Bill_Approved', 'N')
                ->count(),
            'direct_approved' => (clone $scanQuery)->where(fn($q) => $q->where('Temp_Scan', 'N')->orWhereNull('Temp_Scan'))
                ->where('Bill_Approved', 'Y')
                ->count(),
            'direct_rejected' => (clone $scanQuery)->where(fn($q) => $q->where('Temp_Scan', 'N')->orWhereNull('Temp_Scan'))
                ->where('Bill_Approved', 'R')
                ->count(),

            // Processing Stages
            'pending_naming'       => (clone $scanQuery)->where('Scan_Complete', 'N')->count(),
            'pending_verification' => (clone $scanQuery)->where('Scan_Complete', 'Y')
                ->where(fn($q) => $q->whereNull('document_verified')->orWhere('document_verified', 'N'))
                ->count(),
            'final_submitted'      => (clone $scanQuery)->where('Final_Submit', 'Y')->count(),
        ];

        // Recent Scanning Activity
        $recentScans = DB::table('scan_file as s')
            ->leftJoin('companies as c', 'c.id', '=', 's.Group_Id')
            ->leftJoin('master_work_location as l', 'l.location_id', '=', 's.Location')
            ->leftJoin('users as u', 'u.id', '=', 's.Temp_Scan_By')
            ->whereIn('s.Group_Id', $companyIds)
            ->where('s.Is_Deleted', 'N')
            ->orderBy('s.Temp_Scan_Date', 'desc')
            ->limit(8)
            ->select([
                's.Scan_Id',
                's.File',
                's.File_Ext',
                's.Temp_Scan',
                's.Bill_Approved',
                's.temp_scan_reject',
                's.Temp_Scan_Date',
                'c.name as company_name',
                'l.location_name',
                'u.name as scanned_by'
            ])
            ->get();

        // Monthly Scanning Trend (Last 6 Months)
        $monthlyTrend = collect(range(5, 0))->map(function ($i) use ($scanQuery) {
            $date = now()->subMonths($i);
            return [
                'month' => $date->format('M'),
                'count' => (clone $scanQuery)
                    ->whereYear('Temp_Scan_Date', $date->year)
                    ->whereMonth('Temp_Scan_Date', $date->month)
                    ->count(),
            ];
        });

        // Top Scanning Companies
        $topCompanies = DB::table('scan_file as s')
            ->join('companies as c', 'c.id', '=', 's.Group_Id')
            ->whereIn('s.Group_Id', $companyIds)
            ->where('s.Is_Deleted', 'N')
            ->when($fyId, fn($q) => $q->where('s.year_id', $fyId))
            ->selectRaw('c.name as company_name, COUNT(*) as scan_count')
            ->groupBy('c.id', 'c.name')
            ->orderByDesc('scan_count')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'company', 'currentFY', 'fyProgress', 'totalUsers', 'totalCompanies',
            'totalScans', 'todayScans', 'workflowStats', 'recentScans', 
            'monthlyTrend', 'topCompanies'
        ));
    }
}
