<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Services\UserAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class SuperScannerController extends Controller
{
    /**
     * GET /workflow/super-scanner
     * Company-wise scan summary table for Super Scanner role.
     */
    public function index()
    {
        return view('panel.workflow.super-scanner.index');
    }

    /**
     * GET /workflow/super-scanner/data  (AJAX — server-side DataTables)
     * Returns one row per company with aggregated scan counts.
     */
    public function data(Request $request)
    {
        $user         = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $companies    = UserAccessService::allowedCompanies($user->id, $isSuperAdmin);
        $companyIds   = $companies->pluck('id')->toArray();

        $fyId       = FinancialYear::currentId();
        $fromDate   = $request->input('from_date');
        $toDate     = $request->input('to_date');

        // Build a subquery for each metric using conditional aggregation.
        // All counts are scoped to Temp_Scan = 'Y' and Is_Deleted = 'N'
        $rows = DB::table('companies as c')
            ->whereIn('c.id', $companyIds)
            ->where('c.is_active', true)
            ->leftJoin('scan_file as s', function ($join) use ($fyId, $fromDate, $toDate) {
                $join->on('s.Group_Id', '=', 'c.id')
                     ->where('s.Temp_Scan', '=', 'Y')
                     ->where('s.Is_Deleted', '=', 'N')
                     ->when($fyId, fn($j) => $j->where('s.year_id', $fyId))
                     ->when($fromDate, fn($j) => $j->whereDate('s.Temp_Scan_Date', '>=', $fromDate))
                     ->when($toDate,   fn($j) => $j->whereDate('s.Temp_Scan_Date', '<=', $toDate));
            })
            ->select([
                'c.id   as company_id',
                'c.name as company_name',

                // ── Scanning Process ────────────────────────────────────────
                DB::raw("COUNT(s.Scan_Id)                                                            AS total_scan"),
                DB::raw("SUM(CASE WHEN s.Bill_Approved = 'N' AND (s.temp_scan_reject IS NULL OR s.temp_scan_reject = 'N') THEN 1 ELSE 0 END) AS pending"),
                DB::raw("SUM(CASE WHEN s.Bill_Approved = 'Y' THEN 1 ELSE 0 END)                      AS approved"),
                DB::raw("SUM(CASE WHEN s.Bill_Approved = 'R' OR s.temp_scan_reject = 'Y' THEN 1 ELSE 0 END) AS rejected"),

                // ── Pending for Naming ──────────────────────────────────────
                // Temp_Scan=Y, Scan_Complete=N, temp_scan_reject=N
                DB::raw("SUM(CASE WHEN s.Scan_Complete = 'N' AND (s.temp_scan_reject IS NULL OR s.temp_scan_reject = 'N') THEN 1 ELSE 0 END) AS pending_naming"),

                // ── Pending for Verification ────────────────────────────────
                // Temp_Scan=Y, Scan_Complete=Y, temp_scan_reject=N, document_verified=N
                DB::raw("SUM(CASE WHEN s.Scan_Complete = 'Y' AND (s.temp_scan_reject IS NULL OR s.temp_scan_reject = 'N') AND (s.document_verified IS NULL OR s.document_verified = 'N') THEN 1 ELSE 0 END) AS pending_verification"),
            ])
            ->groupBy('c.id', 'c.name')
            ->orderBy('c.name')
            ->get();

        return DataTables::of($rows)
            ->addIndexColumn()
            ->addColumn('actions', fn($r) => '')   // placeholder — rendered in JS
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * GET /workflow/super-scanner/totals  (AJAX JSON)
     * Grand-total row across all allowed companies.
     */
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

    /**
     * GET /workflow/super-scanner/detail  (AJAX — server-side DataTables)
     * Returns scan records for a specific company + metric combination for the modal.
     */
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

        // Security: ensure requested company is within allowed list
        if ($companyId && !in_array($companyId, $companyIds, true)) {
            abort(403);
        }

        $query = DB::table('scan_file as s')
            ->leftJoin('master_work_location as l', 'l.location_id', '=', 's.Location')
            ->leftJoin('users as u',                'u.id',          '=', 's.Temp_Scan_By')
            ->leftJoin('users as apv',              'apv.id',        '=', 's.Bill_Approver')
            ->leftJoin('companies as c',            'c.id',          '=', 's.Group_Id')
            ->where('s.Temp_Scan', 'Y')
            ->where('s.Is_Deleted', 'N')
            ->when($fyId,      fn($q) => $q->where('s.year_id', $fyId))
            ->when($fromDate,  fn($q) => $q->whereDate('s.Temp_Scan_Date', '>=', $fromDate))
            ->when($toDate,    fn($q) => $q->whereDate('s.Temp_Scan_Date', '<=', $toDate));

        // Scope to company if provided
        if ($companyId) {
            $query->where('s.Group_Id', $companyId);
        } else {
            $query->whereIn('s.Group_Id', $companyIds);
        }

        // Apply metric filter
        switch ($metric) {
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
            case 'pending_naming':
                $query->where('s.Scan_Complete', 'N')
                      ->where(fn($q) => $q->whereNull('s.temp_scan_reject')->orWhere('s.temp_scan_reject', 'N'));
                break;
            case 'pending_verification':
                $query->where('s.Scan_Complete', 'Y')
                      ->where(fn($q) => $q->whereNull('s.temp_scan_reject')->orWhere('s.temp_scan_reject', 'N'))
                      ->where(fn($q) => $q->whereNull('s.document_verified')->orWhere('s.document_verified', 'N'));
                break;
            // 'total_scan' → no extra filter
        }

        $query->select([
            's.Scan_Id',
            'c.name as company_name',
            'l.location_name',
            's.File',
            's.File_Location',
            's.File_Ext',
            's.Temp_Scan_Date',
            's.Scan_Date',
            's.Scan_Complete',
            's.document_verified',
            's.Final_Submit',
            's.Bill_Approved',
            's.temp_scan_reject',
            'u.name   as scanned_by',
            'apv.name as approver_name',
            's.Bill_Approver_Remark',
        ]);

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('Temp_Scan_Date', fn($r) => $r->Temp_Scan_Date
                ? \Carbon\Carbon::parse($r->Temp_Scan_Date)->format('d M Y')
                : '—')
            ->editColumn('Scan_Date', fn($r) => $r->Scan_Date
                ? \Carbon\Carbon::parse($r->Scan_Date)->format('d M Y')
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
            ->addColumn('file_preview', fn($r) =>
                '<a href="' . e($r->File_Location) . '" target="_blank"
                    class="inline-flex items-center gap-1 text-blue-600 hover:underline text-xs">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>' . e($r->File) . '</a>'
            )
            ->rawColumns(['status_badge', 'file_preview'])
            ->make(true);
    }

    /**
     * GET /workflow/super-scanner/export/excel
     */
    public function exportExcel(Request $request)
    {
        $data     = $this->summaryExportData($request);
        $fileName = 'scan-summary-' . now()->format('Ymd-His') . '.xlsx';
        return Excel::download(new \App\Exports\SuperScannerExport($data), $fileName);
    }

    /**
     * GET /workflow/super-scanner/export/pdf
     */
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
}
