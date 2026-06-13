<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Exports\OutstandingExport;
use App\Exports\SalesRegisterExport;
use App\Models\Company;
use App\Models\ExportLog;
use App\Models\FinancialYear;
use App\Models\SaleInvoice;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SalesReportController extends Controller
{
    // ══════════════════════════════════════════════════════════════════════════
    // SALES REGISTER
    // ══════════════════════════════════════════════════════════════════════════

    public function register()
    {
        $company = Company::getDefault();
        $fy = $company ? FinancialYear::where('company_id', $company->id)->where('is_current', true)->first() : null;
        return view('panel.sales.register', compact('company', 'fy'));
    }

    public function registerData(Request $request)
    {
        $company = Company::getDefault();
        $query = SaleInvoice::with('party:id,name,display_name,gstin')
            ->where('company_id', $company?->id)
            ->whereIn('status', ['approved', 'submitted']);

        // Filters
        if ($request->filled('date_from')) $query->whereDate('invoice_date', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('invoice_date', '<=', $request->date_to);
        if ($request->filled('party_id')) $query->where('party_id', $request->party_id);
        if ($request->filled('status')) $query->where('status', $request->status);

        // Search
        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(fn($q) => $q
                ->where('invoice_number', 'like', "%{$search}%")
                ->orWhereHas('party', fn($p) => $p->where('name', 'like', "%{$search}%")));
        }

        $total = $query->count();

        // Totals for summary row
        $totals = (clone $query)->selectRaw('
            SUM(taxable_amount) as total_taxable,
            SUM(cgst_amount) as total_cgst,
            SUM(sgst_amount) as total_sgst,
            SUM(igst_amount) as total_igst,
            SUM(total_tax) as total_tax,
            SUM(grand_total) as total_grand,
            SUM(amount_paid) as total_paid,
            SUM(amount_due) as total_due
        ')->first();

        $start = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 25);
        $query->orderByDesc('invoice_date');
        $filtered = $total;
        $rows = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $rows->map(fn($inv) => [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'invoice_date' => $inv->invoice_date->format('d M Y'),
                'party_name' => $inv->party?->display_name ?? $inv->party?->name ?? '—',
                'party_gstin' => $inv->party?->gstin ?? '',
                'taxable_amount' => number_format((float)$inv->taxable_amount, 2),
                'cgst' => number_format((float)$inv->cgst_amount, 2),
                'sgst' => number_format((float)$inv->sgst_amount, 2),
                'igst' => number_format((float)$inv->igst_amount, 2),
                'total_tax' => number_format((float)$inv->total_tax, 2),
                'grand_total' => number_format((float)$inv->grand_total, 2),
                'amount_paid' => number_format((float)$inv->amount_paid, 2),
                'amount_due' => number_format((float)$inv->amount_due, 2),
                'status' => $inv->status,
            ])->values(),
            'totals' => [
                'taxable' => number_format((float)($totals->total_taxable ?? 0), 2),
                'cgst' => number_format((float)($totals->total_cgst ?? 0), 2),
                'sgst' => number_format((float)($totals->total_sgst ?? 0), 2),
                'igst' => number_format((float)($totals->total_igst ?? 0), 2),
                'tax' => number_format((float)($totals->total_tax ?? 0), 2),
                'grand' => number_format((float)($totals->total_grand ?? 0), 2),
                'paid' => number_format((float)($totals->total_paid ?? 0), 2),
                'due' => number_format((float)($totals->total_due ?? 0), 2),
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // OUTSTANDING
    // ══════════════════════════════════════════════════════════════════════════

    public function outstanding()
    {
        $company = Company::getDefault();
        $fy = $company ? FinancialYear::where('company_id', $company->id)->where('is_current', true)->first() : null;
        return view('panel.sales.outstanding', compact('company', 'fy'));
    }

    public function outstandingData(Request $request)
    {
        $company = Company::getDefault();
        $today = now();

        $query = SaleInvoice::with('party:id,name,display_name,gstin,phone,mobile')
            ->where('company_id', $company?->id)
            ->where('status', 'approved')
            ->where('amount_due', '>', 0);

        // Filters
        if ($request->filled('party_id')) $query->where('party_id', $request->party_id);
        if ($request->filled('ageing')) {
            $ageing = $request->ageing;
            if ($ageing === '0-30') $query->whereDate('due_date', '>=', $today->copy()->subDays(30));
            elseif ($ageing === '31-60') $query->whereDate('due_date', '<', $today->copy()->subDays(30))->whereDate('due_date', '>=', $today->copy()->subDays(60));
            elseif ($ageing === '61-90') $query->whereDate('due_date', '<', $today->copy()->subDays(60))->whereDate('due_date', '>=', $today->copy()->subDays(90));
            elseif ($ageing === '90+') $query->whereDate('due_date', '<', $today->copy()->subDays(90));
        }

        // Search
        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(fn($q) => $q
                ->where('invoice_number', 'like', "%{$search}%")
                ->orWhereHas('party', fn($p) => $p->where('name', 'like', "%{$search}%")));
        }

        $total = $query->count();
        $totalDue = (clone $query)->sum('amount_due');

        $start = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 25);
        $query->orderBy('due_date', 'asc');
        $filtered = $total;
        $rows = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $rows->map(function ($inv) use ($today) {
                $dueDate = $inv->due_date ?? $inv->invoice_date;
                $overdueDays = $dueDate->isPast() ? (int)$dueDate->diffInDays($today) : 0;

                $ageingLabel = 'Current';
                $ageingClass = 'bg-green-50 text-green-700';
                if ($overdueDays > 90) { $ageingLabel = '90+ days'; $ageingClass = 'bg-red-50 text-red-700'; }
                elseif ($overdueDays > 60) { $ageingLabel = '61-90 days'; $ageingClass = 'bg-orange-50 text-orange-700'; }
                elseif ($overdueDays > 30) { $ageingLabel = '31-60 days'; $ageingClass = 'bg-amber-50 text-amber-700'; }
                elseif ($overdueDays > 0) { $ageingLabel = '1-30 days'; $ageingClass = 'bg-yellow-50 text-yellow-700'; }

                return [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'invoice_date' => $inv->invoice_date->format('d M Y'),
                    'due_date' => $dueDate->format('d M Y'),
                    'party_name' => $inv->party?->display_name ?? $inv->party?->name ?? '—',
                    'party_gstin' => $inv->party?->gstin ?? '',
                    'party_phone' => $inv->party?->phone ?? $inv->party?->mobile ?? '',
                    'grand_total' => number_format((float)$inv->grand_total, 2),
                    'amount_paid' => number_format((float)$inv->amount_paid, 2),
                    'amount_due' => number_format((float)$inv->amount_due, 2),
                    'overdue_days' => $overdueDays,
                    'ageing_label' => $ageingLabel,
                    'ageing_class' => $ageingClass,
                ];
            })->values(),
            'summary' => [
                'total_outstanding' => number_format((float)$totalDue, 2),
                'count' => $total,
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // EXPORTS
    // ══════════════════════════════════════════════════════════════════════════

    public function exportRegister(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $status = $request->get('status');

        $fileName = 'Sales_Register_' . now()->format('Ymd_His') . '.xlsx';
        $filePath = 'exports/' . $fileName;

        Excel::store(new SalesRegisterExport($dateFrom, $dateTo, $status), $filePath, 'public');

        $rowCount = SaleInvoice::where('company_id', Company::getDefault()?->id)
            ->whereIn('status', ['approved', 'submitted'])
            ->when($dateFrom, fn($q) => $q->whereDate('invoice_date', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('invoice_date', '<=', $dateTo))
            ->when($status, fn($q) => $q->where('status', $status))
            ->count();

        ExportLog::create([
            'model'     => 'SalesRegister',
            'file_name' => $fileName,
            'file_path' => $filePath,
            'row_count' => $rowCount,
            'data_hash' => md5($dateFrom . $dateTo . $status . $rowCount),
            'user_id'   => auth()->id(),
        ]);

        return Excel::download(new SalesRegisterExport($dateFrom, $dateTo, $status), $fileName);
    }

    public function exportOutstanding(Request $request)
    {
        $ageing = $request->get('ageing');

        $fileName = 'Outstanding_' . now()->format('Ymd_His') . '.xlsx';
        $filePath = 'exports/' . $fileName;

        Excel::store(new OutstandingExport($ageing), $filePath, 'public');

        $rowCount = SaleInvoice::where('company_id', Company::getDefault()?->id)
            ->where('status', 'approved')
            ->where('amount_due', '>', 0)
            ->count();

        ExportLog::create([
            'model'     => 'Outstanding',
            'file_name' => $fileName,
            'file_path' => $filePath,
            'row_count' => $rowCount,
            'data_hash' => md5($ageing . $rowCount . now()->format('Ymd')),
            'user_id'   => auth()->id(),
        ]);

        return Excel::download(new OutstandingExport($ageing), $fileName);
    }
}
