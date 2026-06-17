<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Exports\BillDateSyncExport;
use App\Models\FinancialYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ScanFileBillDateSyncController extends Controller
{
    public function index()
    {
        // Get statistics
        $totalScans = DB::table('scan_file')->where('Is_Deleted', 'N')->count();
        $withBillDate = DB::table('scan_file')->where('Is_Deleted', 'N')->whereNotNull('bill_date')->count();
        $withYearId = DB::table('scan_file')->where('Is_Deleted', 'N')->whereNotNull('year_id')->count();
        $pendingSync = $totalScans - $withBillDate;

        return view('panel.settings.bill-date-sync', compact(
            'totalScans',
            'withBillDate',
            'withYearId',
            'pendingSync'
        ));
    }

    public function process()
    {
        // Lift PHP limits for this heavy operation
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        try {
            $stats = [
                'total_processed'   => 0,
                'bill_date_updated' => 0,
                'year_id_updated'   => 0,
                'failed'            => 0,
            ];

            // ── Step 1: Bulk-copy BillDate from punchfile ─────────────────────
            DB::statement("
                UPDATE scan_file sf
                INNER JOIN punchfile pf ON pf.Scan_Id = sf.Scan_Id
                SET sf.bill_date = NULLIF(NULLIF(TRIM(pf.BillDate), ''), '0000-00-00')
                WHERE sf.Is_Deleted = 'N'
                  AND (sf.bill_date IS NULL OR sf.bill_date = '0000-00-00')
                  AND pf.BillDate IS NOT NULL
                  AND TRIM(pf.BillDate) != ''
                  AND TRIM(pf.BillDate) != '0000-00-00'
            ");

            $stats['bill_date_updated'] += DB::select('SELECT ROW_COUNT() as n')[0]->n ?? 0;

            // ── Step 2: Bulk-copy RegPurDate from punchfile2 for remaining ────
            DB::statement("
                UPDATE scan_file sf
                INNER JOIN punchfile2 pf2 ON pf2.Scan_Id = sf.Scan_Id
                SET sf.bill_date = NULLIF(NULLIF(TRIM(pf2.RegPurDate), ''), '0000-00-00')
                WHERE sf.Is_Deleted = 'N'
                  AND (sf.bill_date IS NULL OR sf.bill_date = '0000-00-00')
                  AND pf2.RegPurDate IS NOT NULL
                  AND TRIM(pf2.RegPurDate) != ''
                  AND TRIM(pf2.RegPurDate) != '0000-00-00'
            ");

            $stats['bill_date_updated'] += DB::select('SELECT ROW_COUNT() as n')[0]->n ?? 0;

            // ── Step 3: Bulk-map year_id from financial_years ─────────────────
            // For each financial year, update scan_file.year_id where bill_date falls in range
            $financialYears = FinancialYear::select('id', 'start_date', 'end_date')->get();

            foreach ($financialYears as $fy) {
                DB::statement("
                    UPDATE scan_file
                    SET year_id = ?
                    WHERE Is_Deleted = 'N'
                      AND (year_id IS NULL OR year_id = 0)
                      AND bill_date IS NOT NULL
                      AND TRIM(bill_date) != ''
                      AND TRIM(bill_date) != '0000-00-00'
                      AND DATE(bill_date) >= ?
                      AND DATE(bill_date) <= ?
                ", [
                    $fy->id,
                    \Carbon\Carbon::parse($fy->start_date)->format('Y-m-d'),
                    \Carbon\Carbon::parse($fy->end_date)->format('Y-m-d'),
                ]);

                $stats['year_id_updated'] += DB::select('SELECT ROW_COUNT() as n')[0]->n ?? 0;
            }

            // ── Step 4: Count totals for response ─────────────────────────────
            $stats['total_processed'] = DB::table('scan_file')
                ->where('Is_Deleted', 'N')
                ->count();

            Log::info('Bill date sync completed', $stats);

            return response()->json([
                'success' => true,
                'message' => 'Bill date sync completed successfully',
                'stats'   => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('Bill date sync failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Sync process failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function export()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1G');

        $totals = [
            'total'          => DB::table('scan_file')->where('Is_Deleted', 'N')->count(),
            'with_bill_date' => DB::table('scan_file')->where('Is_Deleted', 'N')->whereNotNull('bill_date')->count(),
            'with_fy_mapped' => DB::table('scan_file')->where('Is_Deleted', 'N')->whereNotNull('year_id')->count(),
            'pending'        => DB::table('scan_file')->where('Is_Deleted', 'N')->whereNull('bill_date')->count(),
        ];

        $filename = 'bill-date-sync-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new BillDateSyncExport($totals), $filename);
    }
}
