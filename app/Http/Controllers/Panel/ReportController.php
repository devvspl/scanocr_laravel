<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\ExportLog;
use App\Services\UserAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    // ── Report Types ─────────────────────────────────────────────────────────

    private const REPORTS = [
        'scan_summary'       => ['label' => 'Scan Summary',          'group' => 'Scanning'],
        'scan_status'        => ['label' => 'Scan Status Report',    'group' => 'Scanning'],
        'bill_approval'      => ['label' => 'Bill Approval Report',  'group' => 'Approval'],
        'punch_status'       => ['label' => 'Punch Status Report',   'group' => 'Punching'],
        'punch_approval'     => ['label' => 'Punch Approval Report', 'group' => 'Approval'],
        'classification'     => ['label' => 'Classification Report', 'group' => 'Classification'],
        'vendor_wise'        => ['label' => 'Vendor Wise Report',    'group' => 'Analysis'],
        'location_wise'      => ['label' => 'Location Wise Report',  'group' => 'Analysis'],
        'user_productivity'  => ['label' => 'User Productivity',     'group' => 'Analysis'],
        'tat_report'         => ['label' => 'TAT Report',            'group' => 'TAT'],
        'stage_wise'         => ['label' => 'Stage Wise Report',     'group' => 'TAT'],
        'stage_pending'      => ['label' => 'Stage Pending Count',   'group' => 'TAT'],
        'action_log'         => ['label' => 'Action Log Report',     'group' => 'Audit'],
    ];

    // ── Page View ─────────────────────────────────────────────────────────────

    public function index()
    {
        $user         = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $companies    = UserAccessService::allowedCompanies($user->id, $isSuperAdmin);
        $financialYears = FinancialYear::orderByDesc('start_date')->get(['id', 'label', 'is_current']);
        $reports      = self::REPORTS;

        return view('panel.reports.index', compact('companies', 'financialYears', 'reports'));
    }

    // ── Generate Report (AJAX → returns file_url for embed) ───────────────────

    public function generate(Request $request)
    {
        set_time_limit(600);
        ini_set('memory_limit', '1G');

        \Log::info('[ReportController] generate() called', [
            'user_id'  => Auth::id(),
            'payload'  => $request->all(),
            'php'      => PHP_VERSION,
            'env'      => app()->environment(),
        ]);

        $request->validate([
            'report_type' => 'required|string|in:' . implode(',', array_keys(self::REPORTS)),
        ]);

        $type   = $request->input('report_type');
        $method = 'report' . str_replace('_', '', ucwords($type, '_'));

        \Log::info('[ReportController] resolved method', ['type' => $type, 'method' => $method, 'exists' => method_exists($this, $method)]);

        if (!method_exists($this, $method)) {
            return response()->json(['success' => false, 'message' => 'Report not implemented yet.'], 422);
        }

        // Check for cached export (graceful — skip if export_logs table missing)
        $dataHash = md5($type . json_encode($request->except(['_token'])));
        try {
            $existing = ExportLog::where('data_hash', $dataHash)
                ->where('user_id', Auth::id())
                ->where('created_at', '>=', now()->subHours(24))
                ->latest()
                ->first();

            if ($existing && Storage::disk('public')->exists($existing->file_path)) {
                \Log::info('[ReportController] returning cached export', ['file' => $existing->file_path]);
                return response()->json([
                    'success'   => true,
                    'file_url'  => asset('storage/' . $existing->file_path),
                    'file_name' => $existing->file_name,
                    'row_count' => $existing->row_count,
                    'cached'    => true,
                    'message'   => 'Report loaded from cache.',
                ]);
            }
        } catch (\Throwable $e) {
            \Log::warning('[ReportController] ExportLog cache check failed', ['error' => $e->getMessage()]);
        }

        // Generate report data
        \Log::info('[ReportController] running report method', ['method' => $method]);
        try {
            $result = $this->$method($request);
            \Log::info('[ReportController] report data generated', [
                'row_count' => is_object($result['rows']) && method_exists($result['rows'], 'count')
                    ? $result['rows']->count() : count($result['rows'] ?? []),
            ]);
        } catch (\Throwable $e) {
            \Log::error('[ReportController] query failed', [
                'method'  => $method,
                'error'   => $e->getMessage(),
                'file'    => $e->getFile() . ':' . $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Query error: ' . $e->getMessage(),
            ], 500);
        }

        if (
            empty($result['rows']) ||
            (is_object($result['rows']) && method_exists($result['rows'], 'isEmpty') && $result['rows']->isEmpty()) ||
            (is_array($result['rows']) && empty($result['rows']))
        ) {
            \Log::info('[ReportController] no data found', ['method' => $method]);
            return response()->json(['success' => false, 'message' => 'No data found for selected filters.'], 422);
        }

        // Ensure storage directory exists
        $fileName = $type . '-' . now()->format('Ymd-His') . '.xlsx';
        $filePath = 'reports/' . $fileName;
        $rowCount = is_object($result['rows']) && method_exists($result['rows'], 'count')
            ? $result['rows']->count()
            : count($result['rows']);

        \Log::info('[ReportController] creating storage directory and writing Excel', ['filePath' => $filePath]);

        try {
            Storage::disk('public')->makeDirectory('reports');
        } catch (\Throwable $e) {
            \Log::warning('[ReportController] makeDirectory failed (may already exist)', ['error' => $e->getMessage()]);
        }

        // Store Excel file
        try {
            \Log::info('[ReportController] Excel::store starting', [
                'row_count'    => $rowCount,
                'memory_before' => round(memory_get_usage(true) / 1024 / 1024, 1) . 'MB',
                'memory_limit'  => ini_get('memory_limit'),
            ]);

            Excel::store(
                new \App\Exports\ReportExport(
                    $result['rows'],
                    $result['headings'],
                    $result['title'] ?? self::REPORTS[$type]['label']
                ),
                $filePath,
                'public'
            );

            \Log::info('[ReportController] Excel stored successfully', [
                'filePath'      => $filePath,
                'memory_after'  => round(memory_get_usage(true) / 1024 / 1024, 1) . 'MB',
            ]);
        } catch (\Throwable $e) {
            \Log::error('[ReportController] Excel::store failed', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile() . ':' . $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Excel export failed: ' . $e->getMessage(),
            ], 500);
        }

        $fileUrl  = asset('storage/' . $filePath);

        // Log export (graceful — skip if export_logs table missing)
        try {
            ExportLog::create([
                'model'     => 'Report_' . $type,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'row_count' => $rowCount,
                'data_hash' => $dataHash,
                'user_id'   => Auth::id(),
            ]);
            \Log::info('[ReportController] ExportLog saved', ['file' => $fileName, 'rows' => $rowCount]);
        } catch (\Throwable $e) {
            \Log::warning('[ReportController] ExportLog::create failed (non-fatal)', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'success'   => true,
            'file_url'  => $fileUrl,
            'file_name' => $fileName,
            'row_count' => $rowCount,
            'cached'    => false,
        ]);
    }

    // ── Export Logs (AJAX for offcanvas) ──────────────────────────────────────

    public function exportLogs(Request $request)
    {
        $logs = ExportLog::where('user_id', Auth::id())
            ->where('model', 'like', 'Report_%')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function ($log) {
                $fileExists = Storage::disk('public')->exists($log->file_path);
                return [
                    'id'         => $log->id,
                    'file_name'  => $log->file_name,
                    'file_url'   => $fileExists ? asset('storage/' . $log->file_path) : null,
                    'model'      => str_replace('Report_', '', $log->model),
                    'row_count'  => $log->row_count,
                    'created_at' => $log->created_at->format('d M Y H:i'),
                    'available'  => $fileExists,
                ];
            });

        return response()->json(['data' => $logs]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // REPORT DATA METHODS
    // ══════════════════════════════════════════════════════════════════════════

    private function reportScanSummary(Request $request): array
    {
        $query = $this->baseQuery($request)
            ->select([
                'c.name as company_name',
                DB::raw('COUNT(*) as total_scans'),
                DB::raw("SUM(CASE WHEN s.Bill_Approved = 'Y' THEN 1 ELSE 0 END) as bill_approved"),
                DB::raw("SUM(CASE WHEN s.Bill_Approved = 'N' THEN 1 ELSE 0 END) as bill_pending"),
                DB::raw("SUM(CASE WHEN s.Bill_Approved = 'R' OR s.temp_scan_reject = 'Y' THEN 1 ELSE 0 END) as bill_rejected"),
                DB::raw("SUM(CASE WHEN s.is_extract = 'Y' THEN 1 ELSE 0 END) as classified"),
                DB::raw("SUM(CASE WHEN s.File_Punched = 'Y' THEN 1 ELSE 0 END) as punched"),
                DB::raw("SUM(CASE WHEN s.File_Approved = 'Y' THEN 1 ELSE 0 END) as punch_approved"),
            ])
            ->groupBy('c.name');

        return [
            'rows'     => $query->get(),
            'headings' => ['Company', 'Total Scans', 'Bill Approved', 'Bill Pending', 'Bill Rejected', 'Classified', 'Punched', 'Punch Approved'],
            'title'    => 'Scan Summary Report',
        ];
    }

    private function reportScanStatus(Request $request): array
    {
        $query = $this->baseQuery($request)
            ->leftJoin('document_types as dt', 'dt.id', '=', 's.DocType_Id')
            ->leftJoin('master_firm as mf', 'mf.firm_id', '=', 's.firm_id')
            ->leftJoin('users as u1', 'u1.id', '=', DB::raw("IF(s.Temp_Scan='Y', s.Temp_Scan_By, s.Scan_By)"))
            ->select([
                's.Scan_Id',
                'c.name as company_name',
                'l.location_name',
                'mf.firm_name as vendor_name',
                's.bill_voucher_date',
                's.bill_no_voucher_no',
                'dt.label as doc_type',
                'u1.name as scanned_by',
                DB::raw("IF(s.Temp_Scan='Y', s.Temp_Scan_Date, s.Scan_Date) as scan_date"),
                DB::raw("CASE 
                    WHEN s.File_Approved = 'Y' THEN 'Punch Approved'
                    WHEN s.Is_Rejected = 'Y' THEN 'Punch Rejected'
                    WHEN s.File_Punched = 'Y' THEN 'Punched'
                    WHEN s.is_extract = 'Y' THEN 'Classified'
                    WHEN s.Bill_Approved = 'Y' THEN 'Bill Approved'
                    WHEN s.Bill_Approved = 'R' OR s.temp_scan_reject = 'Y' THEN 'Bill Rejected'
                    ELSE 'Pending'
                END as current_status"),
            ])
            ->orderByDesc(DB::raw("IF(s.Temp_Scan='Y', s.Temp_Scan_Date, s.Scan_Date)"));

        return [
            'rows'     => $query->get(),
            'headings' => ['Scan ID', 'Company', 'Location', 'Vendor', 'Bill Date', 'Bill No', 'Doc Type', 'Scanned By', 'Scan Date', 'Current Status'],
            'title'    => 'Scan Status Report',
        ];
    }

    private function reportBillApproval(Request $request): array
    {
        $query = $this->baseQuery($request)
            ->leftJoin('master_firm as mf', 'mf.firm_id', '=', 's.firm_id')
            ->leftJoin('users as u1', 'u1.id', '=', DB::raw("IF(s.Temp_Scan='Y', s.Temp_Scan_By, s.Scan_By)"))
            ->leftJoin('users as approver', 'approver.id', '=', 's.Bill_Approver')
            ->select([
                's.Scan_Id',
                'c.name as company_name',
                'l.location_name',
                'mf.firm_name as vendor_name',
                's.bill_voucher_date',
                's.bill_no_voucher_no',
                'u1.name as scanned_by',
                'approver.name as approver_name',
                's.Bill_Approver_Date',
                DB::raw("CASE WHEN s.Bill_Approved='Y' THEN 'Approved' WHEN s.Bill_Approved='R' THEN 'Rejected' ELSE 'Pending' END as status"),
                's.Bill_Approver_Remark',
            ])
            ->orderByDesc('s.Bill_Approver_Date');

        if ($request->filled('approval_status')) {
            $query->where('s.Bill_Approved', $request->input('approval_status'));
        }

        return [
            'rows'     => $query->get(),
            'headings' => ['Scan ID', 'Company', 'Location', 'Vendor', 'Bill Date', 'Bill No', 'Scanned By', 'Approver', 'Approval Date', 'Status', 'Remark'],
            'title'    => 'Bill Approval Report',
        ];
    }

    private function reportPunchStatus(Request $request): array
    {
        $query = $this->baseQuery($request)
            ->leftJoin('document_types as dt', 'dt.id', '=', 's.DocType_Id')
            ->leftJoin('users as puncher', 'puncher.id', '=', 's.Punch_By')
            ->leftJoin('punchfile as pf', 'pf.Scan_Id', '=', 's.Scan_Id')
            ->where('s.is_extract', 'Y')
            ->select([
                's.Scan_Id',
                'c.name as company_name',
                'l.location_name',
                'dt.label as doc_type',
                'puncher.name as punched_by',
                's.Punch_Date',
                'pf.Total_Amount',
                DB::raw("CASE WHEN s.File_Punched='Y' THEN 'Punched' ELSE 'Pending' END as status"),
            ])
            ->orderByDesc('s.Punch_Date');

        return [
            'rows'     => $query->get(),
            'headings' => ['Scan ID', 'Company', 'Location', 'Doc Type', 'Punched By', 'Punch Date', 'Amount', 'Status'],
            'title'    => 'Punch Status Report',
        ];
    }

    private function reportPunchApproval(Request $request): array
    {
        $query = $this->baseQuery($request)
            ->leftJoin('document_types as dt', 'dt.id', '=', 's.DocType_Id')
            ->leftJoin('users as puncher', 'puncher.id', '=', 's.Punch_By')
            ->leftJoin('users as approver', 'approver.id', '=', 's.Approve_By')
            ->leftJoin('punchfile as pf', 'pf.Scan_Id', '=', 's.Scan_Id')
            ->where('s.File_Punched', 'Y')
            ->select([
                's.Scan_Id',
                'c.name as company_name',
                'l.location_name',
                'dt.label as doc_type',
                'puncher.name as punched_by',
                's.Punch_Date',
                'approver.name as approved_by',
                's.Approve_Date',
                'pf.Total_Amount',
                DB::raw("CASE WHEN s.File_Approved='Y' THEN 'Approved' WHEN s.Is_Rejected='Y' THEN 'Rejected' ELSE 'Pending' END as status"),
                's.Reject_Remark',
            ])
            ->orderByDesc('s.Approve_Date');

        return [
            'rows'     => $query->get(),
            'headings' => ['Scan ID', 'Company', 'Location', 'Doc Type', 'Punched By', 'Punch Date', 'Approved By', 'Approve Date', 'Amount', 'Status', 'Remark'],
            'title'    => 'Punch Approval Report',
        ];
    }

    private function reportClassification(Request $request): array
    {
        $query = $this->baseQuery($request)
            ->leftJoin('document_types as dt', 'dt.id', '=', 's.DocType_Id')
            ->leftJoin('users as classifier', 'classifier.id', '=', 's.classified_by')
            ->where('s.Bill_Approved', 'Y')
            ->select([
                's.Scan_Id',
                'c.name as company_name',
                'dt.label as doc_type',
                'classifier.name as classified_by',
                's.classified_date',
                DB::raw("CASE WHEN s.is_extract='Y' THEN 'Classified' WHEN s.is_autoclassified='Y' THEN 'Auto-Classified' ELSE 'Pending' END as status"),
            ])
            ->orderByDesc('s.classified_date');

        return [
            'rows'     => $query->get(),
            'headings' => ['Scan ID', 'Company', 'Doc Type', 'Classified By', 'Classification Date', 'Status'],
            'title'    => 'Classification Report',
        ];
    }

    private function reportVendorWise(Request $request): array
    {
        $query = $this->baseQuery($request)
            ->leftJoin('master_firm as mf', 'mf.firm_id', '=', 's.firm_id')
            ->leftJoin('punchfile as pf', 'pf.Scan_Id', '=', 's.Scan_Id')
            ->whereNotNull('s.firm_id')
            ->where('s.firm_id', '>', 0)
            ->select([
                'mf.firm_name as vendor_name',
                'mf.firm_code as vendor_code',
                'c.name as company_name',
                DB::raw('COUNT(*) as total_bills'),
                DB::raw("SUM(IFNULL(pf.Total_Amount, 0)) as total_amount"),
                DB::raw("SUM(CASE WHEN s.File_Approved='Y' THEN 1 ELSE 0 END) as approved"),
                DB::raw("SUM(CASE WHEN s.File_Punched='Y' AND s.File_Approved='N' THEN 1 ELSE 0 END) as pending"),
            ])
            ->groupBy('mf.firm_name', 'mf.firm_code', 'c.name')
            ->orderByDesc(DB::raw('COUNT(*)'));

        return [
            'rows'     => $query->get(),
            'headings' => ['Vendor Name', 'Vendor Code', 'Company', 'Total Bills', 'Total Amount', 'Approved', 'Pending'],
            'title'    => 'Vendor Wise Report',
        ];
    }

    private function reportLocationWise(Request $request): array
    {
        $query = $this->baseQuery($request)
            ->select([
                'l.location_name',
                'c.name as company_name',
                DB::raw('COUNT(*) as total_scans'),
                DB::raw("SUM(CASE WHEN s.Bill_Approved='Y' THEN 1 ELSE 0 END) as bill_approved"),
                DB::raw("SUM(CASE WHEN s.File_Punched='Y' THEN 1 ELSE 0 END) as punched"),
                DB::raw("SUM(CASE WHEN s.File_Approved='Y' THEN 1 ELSE 0 END) as punch_approved"),
            ])
            ->groupBy('l.location_name', 'c.name')
            ->orderByDesc(DB::raw('COUNT(*)'));

        return [
            'rows'     => $query->get(),
            'headings' => ['Location', 'Company', 'Total Scans', 'Bill Approved', 'Punched', 'Punch Approved'],
            'title'    => 'Location Wise Report',
        ];
    }

    private function reportUserProductivity(Request $request): array
    {
        // Scanners
        $scanners = $this->baseQuery($request)
            ->leftJoin('users as u', 'u.id', '=', DB::raw("IF(s.Temp_Scan='Y', s.Temp_Scan_By, s.Scan_By)"))
            ->select([
                'u.name as user_name',
                DB::raw("'Scanner' as role"),
                DB::raw('COUNT(*) as total_processed'),
            ])
            ->groupBy('u.name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->get();

        // Punchers
        $punchers = DB::table('scan_file as s')
            ->join('companies as c', 'c.id', '=', 's.Group_Id')
            ->leftJoin('users as u', 'u.id', '=', 's.Punch_By')
            ->where('s.Is_Deleted', 'N')
            ->where('s.File_Punched', 'Y')
            ->whereNotNull('s.Punch_By');

        if ($request->filled('company_id')) $punchers->where('s.Group_Id', $request->input('company_id'));
        if ($request->filled('fy_id'))      $punchers->where('s.year_id', $request->input('fy_id'));
        if ($request->filled('from_date'))  $punchers->whereDate('s.Punch_Date', '>=', $request->input('from_date'));
        if ($request->filled('to_date'))    $punchers->whereDate('s.Punch_Date', '<=', $request->input('to_date'));

        $punchers = $punchers->select([
                'u.name as user_name',
                DB::raw("'Puncher' as role"),
                DB::raw('COUNT(*) as total_processed'),
            ])
            ->groupBy('u.name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->get();

        $combined = $scanners->merge($punchers);

        return [
            'rows'     => $combined,
            'headings' => ['User Name', 'Role', 'Total Processed'],
            'title'    => 'User Productivity Report',
        ];
    }

    private function reportTatReport(Request $request): array
    {
        $query = $this->baseQuery($request)
            ->leftJoin('document_types as dt', 'dt.id', '=', 's.DocType_Id')
            ->leftJoin('master_firm as mf', 'mf.firm_id', '=', 's.firm_id')
            ->select([
                's.Scan_Id',
                'c.name as company_name',
                'l.location_name',
                'mf.firm_name as vendor_name',
                's.Document_name',
                'dt.label as doc_type',
                DB::raw("IF(s.Temp_Scan='Y', s.Temp_Scan_Date, s.Scan_Date) as scan_date"),
                's.Bill_Approver_Date',
                's.classified_date',
                's.Punch_Date',
                's.Approve_Date',
                // TAT calculations in days
                DB::raw("DATEDIFF(s.Bill_Approver_Date, IF(s.Temp_Scan='Y', s.Temp_Scan_Date, s.Scan_Date)) as tat_scan_to_bill_approval"),
                DB::raw("DATEDIFF(s.classified_date, s.Bill_Approver_Date) as tat_bill_to_classification"),
                DB::raw("DATEDIFF(s.Punch_Date, s.classified_date) as tat_classification_to_punch"),
                DB::raw("DATEDIFF(s.Approve_Date, s.Punch_Date) as tat_punch_to_approval"),
                DB::raw("DATEDIFF(COALESCE(s.Approve_Date, s.Punch_Date, s.classified_date, s.Bill_Approver_Date, NOW()), IF(s.Temp_Scan='Y', s.Temp_Scan_Date, s.Scan_Date)) as tat_total"),
                DB::raw("CASE 
                    WHEN s.File_Approved = 'Y' THEN 'Completed'
                    WHEN s.Is_Rejected = 'Y' THEN 'Rejected'
                    WHEN s.File_Punched = 'Y' THEN 'Punch Approval Pending'
                    WHEN s.is_extract = 'Y' THEN 'Punching Pending'
                    WHEN s.Bill_Approved = 'Y' THEN 'Classification Pending'
                    WHEN s.Bill_Approved = 'R' THEN 'Bill Rejected'
                    ELSE 'Bill Approval Pending'
                END as current_stage"),
            ])
            ->orderByDesc(DB::raw("IF(s.Temp_Scan='Y', s.Temp_Scan_Date, s.Scan_Date)"));

        return [
            'rows'     => $query->get(),
            'headings' => ['Scan ID', 'Company', 'Location', 'Vendor', 'Document', 'Doc Type', 'Scan Date', 'Bill Approval Date', 'Classification Date', 'Punch Date', 'Final Approval Date', 'Scan→Bill (days)', 'Bill→Classify (days)', 'Classify→Punch (days)', 'Punch→Approve (days)', 'Total TAT (days)', 'Current Stage'],
            'title'    => 'TAT (Turnaround Time) Report',
        ];
    }

    private function reportStageWise(Request $request): array
    {
        $query = $this->baseQuery($request)
            ->leftJoin('document_types as dt', 'dt.id', '=', 's.DocType_Id')
            ->leftJoin('master_firm as mf', 'mf.firm_id', '=', 's.firm_id')
            ->leftJoin('users as u_scan', 'u_scan.id', '=', DB::raw("IF(s.Temp_Scan='Y', s.Temp_Scan_By, s.Scan_By)"))
            ->leftJoin('users as u_approver', 'u_approver.id', '=', 's.Bill_Approver')
            ->leftJoin('users as u_classify', 'u_classify.id', '=', 's.classified_by')
            ->leftJoin('users as u_punch', 'u_punch.id', '=', 's.Punch_By')
            ->leftJoin('users as u_final', 'u_final.id', '=', 's.Approve_By')
            ->select([
                's.Scan_Id',
                'c.name as company_name',
                's.Document_name',
                'dt.label as doc_type',
                // Stage 1: Scanning
                'u_scan.name as scanned_by',
                DB::raw("DATE_FORMAT(IF(s.Temp_Scan='Y', s.Temp_Scan_Date, s.Scan_Date), '%d-%m-%Y') as scan_date"),
                // Stage 2: Bill Approval
                'u_approver.name as bill_approver',
                DB::raw("DATE_FORMAT(s.Bill_Approver_Date, '%d-%m-%Y') as bill_approval_date"),
                DB::raw("CASE WHEN s.Bill_Approved='Y' THEN 'Approved' WHEN s.Bill_Approved='R' THEN 'Rejected' ELSE 'Pending' END as bill_status"),
                // Stage 3: Classification
                'u_classify.name as classified_by',
                DB::raw("DATE_FORMAT(s.classified_date, '%d-%m-%Y') as classification_date"),
                DB::raw("CASE WHEN s.is_extract='Y' THEN 'Done' WHEN s.is_autoclassified='Y' THEN 'Auto' ELSE 'Pending' END as classification_status"),
                // Stage 4: Punching
                'u_punch.name as punched_by',
                DB::raw("DATE_FORMAT(s.Punch_Date, '%d-%m-%Y') as punch_date"),
                DB::raw("CASE WHEN s.File_Punched='Y' THEN 'Done' ELSE 'Pending' END as punch_status"),
                // Stage 5: Final Approval
                'u_final.name as approved_by',
                DB::raw("DATE_FORMAT(s.Approve_Date, '%d-%m-%Y') as approval_date"),
                DB::raw("CASE WHEN s.File_Approved='Y' THEN 'Approved' WHEN s.Is_Rejected='Y' THEN 'Rejected' ELSE 'Pending' END as final_status"),
            ])
            ->orderByDesc(DB::raw("IF(s.Temp_Scan='Y', s.Temp_Scan_Date, s.Scan_Date)"));

        return [
            'rows'     => $query->get(),
            'headings' => [
                'Scan ID', 'Company', 'Document', 'Doc Type',
                'Scanned By', 'Scan Date',
                'Bill Approver', 'Bill Date', 'Bill Status',
                'Classified By', 'Classification Date', 'Classification Status',
                'Punched By', 'Punch Date', 'Punch Status',
                'Final Approved By', 'Approval Date', 'Final Status',
            ],
            'title'    => 'Stage Wise Report',
        ];
    }

    private function reportStagePending(Request $request): array
    {
        $query = $this->baseQuery($request);

        $result = $query->selectRaw('
            COUNT(*) as total_scans,
            SUM(CASE WHEN s.Bill_Approved = "N" AND (s.temp_scan_reject IS NULL OR s.temp_scan_reject = "N") THEN 1 ELSE 0 END) as pending_bill_approval,
            SUM(CASE WHEN s.Bill_Approved = "Y" AND (s.is_extract = "N" OR s.is_extract IS NULL) AND s.is_autoclassified != "Y" THEN 1 ELSE 0 END) as pending_classification,
            SUM(CASE WHEN s.is_extract = "Y" AND (s.File_Punched = "N" OR s.File_Punched IS NULL) THEN 1 ELSE 0 END) as pending_punching,
            SUM(CASE WHEN s.File_Punched = "Y" AND s.File_Approved = "N" AND (s.Is_Rejected IS NULL OR s.Is_Rejected = "N") THEN 1 ELSE 0 END) as pending_punch_approval,
            SUM(CASE WHEN s.File_Approved = "Y" THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN s.Is_Rejected = "Y" OR s.Bill_Approved = "R" OR s.temp_scan_reject = "Y" THEN 1 ELSE 0 END) as rejected
        ')->first();

        $rows = collect([
            (object)['stage' => 'Bill Approval Pending', 'count' => $result->pending_bill_approval, 'percentage' => $result->total_scans > 0 ? round(($result->pending_bill_approval / $result->total_scans) * 100, 1) : 0, 'description' => 'Scans waiting for bill approver action'],
            (object)['stage' => 'Classification Pending', 'count' => $result->pending_classification, 'percentage' => $result->total_scans > 0 ? round(($result->pending_classification / $result->total_scans) * 100, 1) : 0, 'description' => 'Bills approved, waiting for document classification'],
            (object)['stage' => 'Punching Pending', 'count' => $result->pending_punching, 'percentage' => $result->total_scans > 0 ? round(($result->pending_punching / $result->total_scans) * 100, 1) : 0, 'description' => 'Classified, waiting for data punching'],
            (object)['stage' => 'Punch Approval Pending', 'count' => $result->pending_punch_approval, 'percentage' => $result->total_scans > 0 ? round(($result->pending_punch_approval / $result->total_scans) * 100, 1) : 0, 'description' => 'Punched, waiting for final approval'],
            (object)['stage' => 'Completed', 'count' => $result->completed, 'percentage' => $result->total_scans > 0 ? round(($result->completed / $result->total_scans) * 100, 1) : 0, 'description' => 'Fully processed and approved'],
            (object)['stage' => 'Rejected', 'count' => $result->rejected, 'percentage' => $result->total_scans > 0 ? round(($result->rejected / $result->total_scans) * 100, 1) : 0, 'description' => 'Rejected at any stage'],
            (object)['stage' => 'TOTAL', 'count' => $result->total_scans, 'percentage' => '100', 'description' => 'All scans in selected period'],
        ]);

        return [
            'rows'     => $rows,
            'headings' => ['Stage', 'Count', 'Percentage (%)', 'Description'],
            'title'    => 'Stage Pending Count Report',
        ];
    }

    private function reportActionLog(Request $request): array
    {
        $query = DB::table('scan_action_logs as al')
            ->leftJoin('users as u', 'u.id', '=', 'al.performed_by')
            ->select([
                'al.scan_id',
                'al.action_label',
                'u.name as performed_by',
                'al.remark',
                'al.performed_at',
            ])
            ->orderByDesc('al.performed_at');

        if ($request->filled('from_date'))  $query->whereDate('al.performed_at', '>=', $request->input('from_date'));
        if ($request->filled('to_date'))    $query->whereDate('al.performed_at', '<=', $request->input('to_date'));
        if ($request->filled('scan_id'))    $query->where('al.scan_id', $request->input('scan_id'));

        return [
            'rows'     => $query->get(),
            'headings' => ['Scan ID', 'Action', 'Performed By', 'Remark', 'Date & Time'],
            'title'    => 'Action Log Report',
        ];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════════════════════

    private function baseQuery(Request $request)
    {
        $user         = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $allowedIds   = UserAccessService::allowedCompanies($user->id, $isSuperAdmin)->pluck('id')->toArray();

        $query = DB::table('scan_file as s')
            ->join('companies as c', 'c.id', '=', 's.Group_Id')
            ->leftJoin('master_work_location as l', 'l.location_id', '=', 's.Location')
            ->whereIn('s.Group_Id', $allowedIds)
            ->where('s.Is_Deleted', 'N');

        if ($request->filled('company_id'))  $query->where('s.Group_Id', $request->input('company_id'));
        if ($request->filled('location_id')) $query->where('s.Location', $request->input('location_id'));
        if ($request->filled('doc_type_id')) $query->where('s.DocType_Id', $request->input('doc_type_id'));
        if ($request->filled('vendor_id'))   $query->where('s.firm_id', $request->input('vendor_id'));
        if ($request->filled('user_id'))     $query->where(fn($q) => $q->where('s.Temp_Scan_By', $request->input('user_id'))->orWhere('s.Scan_By', $request->input('user_id'))->orWhere('s.Punch_By', $request->input('user_id')));
        if ($request->filled('from_date'))   $query->whereDate(DB::raw("IF(s.Temp_Scan='Y', s.Temp_Scan_Date, s.Scan_Date)"), '>=', $request->input('from_date'));
        if ($request->filled('to_date'))     $query->whereDate(DB::raw("IF(s.Temp_Scan='Y', s.Temp_Scan_Date, s.Scan_Date)"), '<=', $request->input('to_date'));
        if ($request->filled('scan_id'))     $query->where('s.Scan_Id', $request->input('scan_id'));
        if ($request->filled('document_name')) $query->where('s.Document_name', 'like', '%' . $request->input('document_name') . '%');
        if ($request->filled('file_name'))   $query->where('s.File', 'like', '%' . $request->input('file_name') . '%');

        // Apply FY filter — 'all' or empty skips the filter
        if ($request->filled('fy_id') && $request->input('fy_id') !== 'all') {
            $query->where('s.year_id', $request->input('fy_id'));
        }

        return $query;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // SELECT2 SERVER-SIDE ENDPOINTS
    // ══════════════════════════════════════════════════════════════════════════

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

    public function locationsSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $query = DB::table('master_work_location')
            ->where('status', 'A')->where('is_deleted', 'N')
            ->orderBy('location_name');

        if ($q !== '') {
            $query->where(fn($qb) => $qb->where('location_name', 'like', "%{$q}%")->orWhere('location_code', 'like', "%{$q}%"));
        }

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)->get(['location_id as id', DB::raw("CONCAT(location_name, ' (', location_code, ')') as text")]);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    public function docTypesSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $query = DB::table('document_types')->where('is_active', true)->orderBy('label');

        if ($q !== '') {
            $query->where(fn($qb) => $qb->where('label', 'like', "%{$q}%")->orWhere('key', 'like', "%{$q}%"));
        }

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)->get(['id', 'label as text']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    public function vendorsSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $query = DB::table('master_firm')
            ->where('status', 'A')->where('is_deleted', 'N')
            ->orderBy('firm_name');

        if ($q !== '') {
            $query->where(fn($qb) => $qb->where('firm_name', 'like', "%{$q}%")->orWhere('firm_code', 'like', "%{$q}%"));
        }

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)->get(['firm_id as id', DB::raw("CONCAT(firm_name, IFNULL(CONCAT(' (', firm_code, ')'), '')) as text")]);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    public function usersSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $query = DB::table('users')->where('is_active', true)->orderBy('name');

        if ($q !== '') {
            $query->where(fn($qb) => $qb->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"));
        }

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)->get(['id', 'name as text']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    public function financialYearsSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $query = FinancialYear::orderByDesc('start_date');
        if ($q !== '') $query->where('label', 'like', "%{$q}%");

        $total   = $query->count();
        $items   = $query->offset(($page - 1) * $per)->limit($per)->get(['id', 'label as text']);

        // Prepend "All Years" on first page when no search
        $results = collect();
        if ($page === 1 && $q === '') {
            $results->push(['id' => 'all', 'text' => '— All Years —']);
        }
        $results = $results->merge($items);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }
}
