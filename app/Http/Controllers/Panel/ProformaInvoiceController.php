<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Mail\ApprovalRequestMail;
use App\Models\ApprovalLog;
use App\Models\ApprovalSetting;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\Product;
use App\Models\ProformaInvoice;
use App\Models\ProformaInvoiceItem;
use App\Models\SaleInvoice;
use App\Models\SaleInvoiceItem;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ProformaInvoiceController extends Controller
{
    // ── List ─────────────────────────────────────────────────────────────────

    public function index()
    {
        $company = Company::getDefault();
        $fy      = $company
            ? FinancialYear::where('company_id', $company->id)->where('is_current', true)->first()
            : null;

        return view('panel.sales.proformas', compact('company', 'fy'));
    }

    // ── DataTables JSON ───────────────────────────────────────────────────────

    public function data(Request $request)
    {
        $company = Company::getDefault();
        $query   = ProformaInvoice::with('party', 'creator')
            ->where('company_id', $company?->id);

        // Search
        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(fn($q) => $q
                ->where('proforma_number', 'like', "%{$search}%")
                ->orWhereHas('party', fn($p) => $p->where('name', 'like', "%{$search}%"))
            );
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('proforma_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('proforma_date', '<=', $request->date_to);
        }

        $total    = $query->count();
        $start    = (int) $request->get('start', 0);
        $length   = (int) $request->get('length', 15);
        $order    = $request->input('order', [['column' => 0, 'dir' => 'desc']]);
        $cols     = ['proforma_number', 'proforma_date', 'party_name', 'grand_total', 'status', 'created_by'];
        $col      = $cols[(int)($order[0]['column'] ?? 0)] ?? 'proforma_date';
        $dir      = ($order[0]['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if ($col === 'party_name') {
            $query->join('parties', 'parties.id', '=', 'proforma_invoices.party_id')
                  ->orderBy('parties.name', $dir)
                  ->select('proforma_invoices.*');
        } else {
            $sortable = ['proforma_number', 'proforma_date', 'grand_total', 'status'];
            $query->orderBy(in_array($col, $sortable) ? $col : 'proforma_date', $dir);
        }

        $filtered = $query->count();
        $rows     = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows->map(fn($inv) => $this->row($inv)),
        ]);
    }

    // ── Create form ───────────────────────────────────────────────────────────

    public function create()
    {
        $company   = Company::getDefault();
        $fy        = $company
            ? FinancialYear::where('company_id', $company->id)->where('is_current', true)->first()
            : null;
        $customers = Party::customers()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'display_name', 'gstin', 'billing_address', 'shipping_address', 'state', 'credit_days']);
        $products  = Product::where('is_active', true)->with('unit')->orderBy('name')->get(['id', 'code', 'name', 'sale_price', 'tax_rate', 'hsn_sac', 'unit_id']);
        $taxRates  = TaxRate::where('is_active', true)->orderBy('rate')->get(['id', 'name', 'rate', 'cgst', 'sgst', 'igst']);

        // Generate preview number (not committed yet)
        $nextNumber = $company ? ProformaInvoice::generateNumber($company->id) : 'PF/0001';
        // Revert the increment — we only commit on actual save
        if ($company) {
            \App\Models\NumberingSetting::where('company_id', $company->id)
                ->where('document_type', 'proforma')
                ->decrement('next_number');
        }

        return view('panel.sales.proforma-form', compact(
            'company', 'fy', 'customers', 'products', 'taxRates', 'nextNumber'
        ));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $data  = $this->validateHeader($request);
        $items = $this->validateItems($request);

        $proforma = null;

        DB::transaction(function () use ($data, $items, $request, &$proforma) {
            $company = Company::getDefault();

            $data['company_id']        = $company->id;
            $data['financial_year_id'] = FinancialYear::where('company_id', $company->id)
                ->where('is_current', true)->value('id');
            $data['proforma_number']   = ProformaInvoice::generateNumber($company->id);
            $data['created_by']        = auth()->id();
            $data['status']            = 'draft';

            // Totals
            $totals = $this->computeTotals($items, (bool) ($data['is_igst'] ?? false), $data);
            $data   = array_merge($data, $totals);

            $proforma = ProformaInvoice::create($data);

            foreach ($items as $i => $item) {
                $proforma->items()->create(array_merge($item, ['sort_order' => $i]));
            }

            ActivityLogger::log('created', $proforma, null, $proforma->getAttributes());
        });

        return response()->json([
            'success'  => true,
            'message'  => 'Proforma saved as draft.',
            'id'       => $proforma->id,
            'redirect' => route('sales.proforma.edit', $proforma),
        ]);
    }

    // ── Edit form ─────────────────────────────────────────────────────────────

    public function edit(ProformaInvoice $proforma)
    {
        if (! $proforma->canEdit()) {
            return redirect()->route('sales.proforma')
                ->with('error', 'This proforma cannot be edited in its current status.');
        }

        $company   = Company::getDefault();
        $fy        = FinancialYear::find($proforma->financial_year_id);
        $customers = Party::customers()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'display_name', 'gstin', 'billing_address', 'shipping_address', 'state', 'credit_days']);
        $products  = Product::where('is_active', true)->with('unit')->orderBy('name')->get(['id', 'code', 'name', 'sale_price', 'tax_rate', 'hsn_sac', 'unit_id']);
        $taxRates  = TaxRate::where('is_active', true)->orderBy('rate')->get(['id', 'name', 'rate', 'cgst', 'sgst', 'igst']);
        $nextNumber = $proforma->proforma_number;

        return view('panel.sales.proforma-form', compact(
            'proforma', 'company', 'fy', 'customers', 'products', 'taxRates', 'nextNumber'
        ));
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(Request $request, ProformaInvoice $proforma)
    {
        if (! $proforma->canEdit()) {
            return response()->json(['success' => false, 'message' => 'Proforma cannot be edited in its current status.'], 422);
        }

        $data  = $this->validateHeader($request);
        $items = $this->validateItems($request);

        DB::transaction(function () use ($proforma, $data, $items, $request) {
            $old    = $proforma->getAttributes();
            $totals = $this->computeTotals($items, (bool) ($data['is_igst'] ?? false), $data);
            $data   = array_merge($data, $totals);

            $proforma->update($data);
            $proforma->items()->delete();

            foreach ($items as $i => $item) {
                $proforma->items()->create(array_merge($item, ['sort_order' => $i]));
            }

            ActivityLogger::log('updated', $proforma, $old, $proforma->getAttributes());
        });

        return response()->json([
            'success' => true,
            'message' => 'Proforma updated successfully.',
            'id'      => $proforma->id,
            'stay'    => true,
        ]);
    }

    // ── Show (view-only) ──────────────────────────────────────────────────────

    public function show(ProformaInvoice $proforma)
    {
        $proforma->load('party', 'items.product', 'creator', 'approver', 'submitter', 'rejecter', 'company', 'convertedInvoice');
        return view('panel.sales.proforma-show', compact('proforma'));
    }

    // ── PDF download ──────────────────────────────────────────────────────────

    public function pdf(Request $request, ProformaInvoice $proforma)
    {
        $proforma->load('party', 'items.product', 'creator', 'approver', 'submitter', 'rejecter', 'company');

        // Alias $proforma as $invoice for the shared PDF templates
        $invoice = $proforma;

        $allowed  = ['1', '2', '3', '4', '5', '6'];
        $template = in_array($request->query('template'), $allowed) ? $request->query('template') : '1';
        $view     = "panel.sales.pdf.template-{$template}";

        $pdf = Pdf::loadView($view, compact('invoice'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'          => 'DejaVu Sans',
                'isRemoteEnabled'      => false,
                'isHtml5ParserEnabled' => true,
                'dpi'                  => 150,
            ]);

        $filename = 'Proforma-' . str_replace('/', '-', $proforma->proforma_number) . '.pdf';

        return $pdf->download($filename);
    }

    // ── Workflow actions ──────────────────────────────────────────────────────

    public function submit(ProformaInvoice $proforma)
    {
        if (! $proforma->canSubmit()) {
            return response()->json(['success' => false, 'message' => 'Proforma cannot be submitted.'], 422);
        }

        $old = $proforma->getAttributes();

        // Check approval settings
        $approvalSetting = ApprovalSetting::getFor('proforma', $proforma->company_id);

        if ($approvalSetting && $approvalSetting->isAutoApproved()) {
            $proforma->update([
                'status'       => 'approved',
                'submitted_by' => auth()->id(),
                'submitted_at' => now(),
                'approved_by'  => auth()->id(),
                'approved_at'  => now(),
                'current_approval_level' => 0,
                'max_approval_level'     => 0,
            ]);
            ActivityLogger::log('auto_approved', $proforma, $old, $proforma->getAttributes());
            return response()->json(['success' => true, 'message' => 'Proforma auto-approved.']);
        }

        if ($approvalSetting && $approvalSetting->isRequired()) {
            $levelsCount = $approvalSetting->levels_count;
            $proforma->update([
                'status'                 => 'submitted',
                'submitted_by'           => auth()->id(),
                'submitted_at'           => now(),
                'current_approval_level' => 1,
                'max_approval_level'     => $levelsCount,
            ]);

            $level1 = $approvalSetting->getLevel(1);
            if ($level1) {
                $this->createPendingLogsAndNotify($proforma, $level1, 1);
            }

            ActivityLogger::log('submitted', $proforma, $old, $proforma->getAttributes());
            return response()->json(['success' => true, 'message' => 'Proforma submitted for approval (Level 1).']);
        }

        // No approval / default: simple submit → approved
        $proforma->update([
            'status'       => 'approved',
            'submitted_by' => auth()->id(),
            'submitted_at' => now(),
            'approved_by'  => auth()->id(),
            'approved_at'  => now(),
        ]);
        ActivityLogger::log('submitted', $proforma, $old, $proforma->getAttributes());

        return response()->json(['success' => true, 'message' => 'Proforma approved (no approval required).']);
    }

    public function approve(ProformaInvoice $proforma)
    {
        if (! $proforma->canApprove()) {
            return response()->json(['success' => false, 'message' => 'Proforma cannot be approved.'], 422);
        }

        $old = $proforma->getAttributes();
        $proforma->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        ActivityLogger::log('approved', $proforma, $old, $proforma->getAttributes());

        return response()->json(['success' => true, 'message' => 'Proforma approved successfully.']);
    }

    public function reject(Request $request, ProformaInvoice $proforma)
    {
        if (! $proforma->canReject()) {
            return response()->json(['success' => false, 'message' => 'Proforma cannot be rejected.'], 422);
        }

        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $old = $proforma->getAttributes();
        $proforma->update([
            'status'           => 'rejected',
            'rejected_by'      => auth()->id(),
            'rejected_at'      => now(),
            'rejection_reason' => $request->reason,
        ]);
        ActivityLogger::log('rejected', $proforma, $old, $proforma->getAttributes());

        return response()->json(['success' => true, 'message' => 'Proforma rejected.']);
    }

    public function cancel(Request $request, ProformaInvoice $proforma)
    {
        if (! $proforma->canCancel()) {
            return response()->json(['success' => false, 'message' => 'Proforma cannot be cancelled.'], 422);
        }

        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $old = $proforma->getAttributes();
        $proforma->update([
            'status'        => 'cancelled',
            'cancelled_by'  => auth()->id(),
            'cancelled_at'  => now(),
            'cancel_reason' => $request->reason,
        ]);
        ActivityLogger::log('cancelled', $proforma, $old, $proforma->getAttributes());

        return response()->json(['success' => true, 'message' => 'Proforma cancelled.']);
    }

    // ── Multi-level approval actions ──────────────────────────────────────────

    public function levelApprove(Request $request, ProformaInvoice $proforma)
    {
        $request->validate(['remarks' => ['nullable', 'string', 'max:500']]);

        if ($proforma->status !== 'submitted') {
            return response()->json(['success' => false, 'message' => 'Proforma is not pending approval.'], 422);
        }

        $userId = auth()->id();
        $currentLevel = $proforma->current_approval_level;
        $approvalSetting = ApprovalSetting::getFor('proforma', $proforma->company_id);

        if (!$approvalSetting || !$approvalSetting->isRequired()) {
            return response()->json(['success' => false, 'message' => 'No approval settings configured.'], 422);
        }

        $levelConfig = $approvalSetting->getLevel($currentLevel);
        if (!$levelConfig) {
            return response()->json(['success' => false, 'message' => 'Invalid approval level.'], 422);
        }

        if (!in_array($userId, $levelConfig['approver_ids'] ?? [])) {
            return response()->json(['success' => false, 'message' => 'You are not an approver at this level.'], 422);
        }

        $existingLog = ApprovalLog::where('document_type', 'proforma')
            ->where('document_id', $proforma->id)
            ->where('level', $currentLevel)
            ->where('user_id', $userId)
            ->whereIn('action', ['approved', 'rejected'])
            ->first();

        if ($existingLog) {
            return response()->json(['success' => false, 'message' => 'You have already acted on this level.'], 422);
        }

        ApprovalLog::where('document_type', 'proforma')
            ->where('document_id', $proforma->id)
            ->where('level', $currentLevel)
            ->where('user_id', $userId)
            ->update([
                'action'    => 'approved',
                'remarks'   => $request->remarks,
                'acted_at'  => now(),
            ]);

        $approvalType = $levelConfig['approval_type'] ?? 'any_one';
        $levelComplete = false;

        if ($approvalType === 'any_one') {
            $levelComplete = true;
        } else {
            $pendingCount = ApprovalLog::where('document_type', 'proforma')
                ->where('document_id', $proforma->id)
                ->where('level', $currentLevel)
                ->where('action', 'pending')
                ->count();
            $levelComplete = ($pendingCount === 0);
        }

        if ($levelComplete) {
            $nextLevel = $currentLevel + 1;
            if ($nextLevel > $proforma->max_approval_level) {
                $old = $proforma->getAttributes();
                $proforma->update([
                    'status'                 => 'approved',
                    'approved_by'            => $userId,
                    'approved_at'            => now(),
                    'current_approval_level' => $currentLevel,
                ]);
                ActivityLogger::log('approved', $proforma, $old, $proforma->getAttributes());
                return response()->json(['success' => true, 'message' => 'Proforma fully approved.']);
            } else {
                $proforma->update(['current_approval_level' => $nextLevel]);
                $nextLevelConfig = $approvalSetting->getLevel($nextLevel);
                if ($nextLevelConfig) {
                    $this->createPendingLogsAndNotify($proforma, $nextLevelConfig, $nextLevel);
                }
                return response()->json(['success' => true, 'message' => "Level {$currentLevel} approved. Moved to Level {$nextLevel}."]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Your approval recorded. Waiting for other approvers.']);
    }

    public function levelReject(Request $request, ProformaInvoice $proforma)
    {
        $request->validate(['remarks' => ['required', 'string', 'max:500']]);

        if ($proforma->status !== 'submitted') {
            return response()->json(['success' => false, 'message' => 'Proforma is not pending approval.'], 422);
        }

        $userId = auth()->id();
        $currentLevel = $proforma->current_approval_level;
        $approvalSetting = ApprovalSetting::getFor('proforma', $proforma->company_id);

        if (!$approvalSetting || !$approvalSetting->isRequired()) {
            return response()->json(['success' => false, 'message' => 'No approval settings configured.'], 422);
        }

        $levelConfig = $approvalSetting->getLevel($currentLevel);
        if (!$levelConfig || !in_array($userId, $levelConfig['approver_ids'] ?? [])) {
            return response()->json(['success' => false, 'message' => 'You are not an approver at this level.'], 422);
        }

        ApprovalLog::where('document_type', 'proforma')
            ->where('document_id', $proforma->id)
            ->where('level', $currentLevel)
            ->where('user_id', $userId)
            ->update([
                'action'   => 'rejected',
                'remarks'  => $request->remarks,
                'acted_at' => now(),
            ]);

        $old = $proforma->getAttributes();
        $proforma->update([
            'status'           => 'rejected',
            'rejected_by'      => $userId,
            'rejected_at'      => now(),
            'rejection_reason' => $request->remarks,
        ]);
        ActivityLogger::log('rejected', $proforma, $old, $proforma->getAttributes());

        return response()->json(['success' => true, 'message' => 'Proforma rejected.']);
    }

    public function approvalLogs(ProformaInvoice $proforma)
    {
        $logs = ApprovalLog::where('document_type', 'proforma')
            ->where('document_id', $proforma->id)
            ->with('user:id,name,email')
            ->orderBy('level')
            ->orderBy('created_at')
            ->get();

        $approvalSetting = ApprovalSetting::getFor('proforma', $proforma->company_id);

        return response()->json([
            'success'  => true,
            'logs'     => $logs,
            'invoice'  => [
                'id'                     => $proforma->id,
                'status'                 => $proforma->status,
                'current_approval_level' => $proforma->current_approval_level,
                'max_approval_level'     => $proforma->max_approval_level,
            ],
            'setting'  => $approvalSetting ? [
                'approval_mode' => $approvalSetting->approval_mode,
                'levels_count'  => $approvalSetting->levels_count,
                'levels'        => $approvalSetting->levels,
            ] : null,
        ]);
    }

    public function destroy(ProformaInvoice $proforma)
    {
        if (! $proforma->isDraft()) {
            return response()->json(['success' => false, 'message' => 'Only draft proformas can be deleted.'], 422);
        }

        $snapshot = $proforma->getAttributes();
        $proforma->delete();
        ActivityLogger::log('deleted', $proforma, $snapshot, null);

        return response()->json(['success' => true, 'message' => 'Proforma deleted.']);
    }

    // ── Convert to Invoice ────────────────────────────────────────────────────

    public function convertToInvoice(ProformaInvoice $proforma)
    {
        if (!$proforma->canConvert()) {
            return response()->json(['success' => false, 'message' => 'This proforma cannot be converted.'], 422);
        }

        $invoice = null;

        DB::transaction(function () use ($proforma, &$invoice) {
            $company = Company::getDefault();

            $invoiceData = [
                'company_id'        => $proforma->company_id,
                'financial_year_id' => $proforma->financial_year_id,
                'invoice_number'    => SaleInvoice::generateNumber($proforma->company_id),
                'invoice_date'      => now()->toDateString(),
                'due_date'          => $proforma->due_date,
                'party_id'          => $proforma->party_id,
                'billing_address'   => $proforma->billing_address,
                'shipping_address'  => $proforma->shipping_address,
                'reference_number'  => $proforma->reference_number,
                'place_of_supply'   => $proforma->place_of_supply,
                'subtotal'          => $proforma->subtotal,
                'discount_amount'   => $proforma->discount_amount,
                'taxable_amount'    => $proforma->taxable_amount,
                'cgst_amount'       => $proforma->cgst_amount,
                'sgst_amount'       => $proforma->sgst_amount,
                'igst_amount'       => $proforma->igst_amount,
                'cess_amount'       => $proforma->cess_amount,
                'total_tax'         => $proforma->total_tax,
                'grand_total'       => $proforma->grand_total,
                'amount_paid'       => 0,
                'amount_due'        => $proforma->grand_total,
                'is_igst'           => $proforma->is_igst,
                'status'            => 'draft',
                'notes'             => $proforma->notes,
                'terms'             => $proforma->terms,
                'created_by'        => auth()->id(),
            ];

            $invoice = SaleInvoice::create($invoiceData);

            // Copy items
            foreach ($proforma->items as $item) {
                $invoice->items()->create([
                    'product_id'      => $item->product_id,
                    'description'     => $item->description,
                    'hsn_sac'         => $item->hsn_sac,
                    'qty'             => $item->qty,
                    'unit'            => $item->unit,
                    'unit_price'      => $item->unit_price,
                    'discount_pct'    => $item->discount_pct,
                    'discount_amount' => $item->discount_amount,
                    'taxable_amount'  => $item->taxable_amount,
                    'tax_rate'        => $item->tax_rate,
                    'cgst_rate'       => $item->cgst_rate,
                    'sgst_rate'       => $item->sgst_rate,
                    'igst_rate'       => $item->igst_rate,
                    'cgst_amount'     => $item->cgst_amount,
                    'sgst_amount'     => $item->sgst_amount,
                    'igst_amount'     => $item->igst_amount,
                    'total_tax'       => $item->total_tax,
                    'line_total'      => $item->line_total,
                    'sort_order'      => $item->sort_order,
                ]);
            }

            // Mark proforma as converted
            $proforma->update([
                'is_converted'           => true,
                'converted_to_invoice_id' => $invoice->id,
            ]);

            ActivityLogger::log('converted_to_invoice', $proforma, null, ['invoice_id' => $invoice->id]);
        });

        return response()->json([
            'success'  => true,
            'message'  => 'Proforma converted to invoice successfully.',
            'redirect' => route('sales.invoices.show', $invoice),
        ]);
    }

    // ── API: next proforma number preview ─────────────────────────────────────

    public function nextNumber()
    {
        $company = Company::getDefault();
        if (! $company) {
            return response()->json(['number' => 'PF/0001']);
        }

        $setting = \App\Models\NumberingSetting::where('company_id', $company->id)
            ->where('document_type', 'proforma')
            ->first();

        return response()->json(['number' => $setting ? $setting->buildPreview() : 'PF/0001']);
    }

    // ── API: search customers ─────────────────────────────────────────────────

    public function searchCustomers(Request $request)
    {
        $query = $request->input('q', '');
        $company = Company::getDefault();

        $recentCustomerIds = ProformaInvoice::where('company_id', $company?->id)
            ->whereHas('party', function($q) use ($query) {
                if ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('display_name', 'like', "%{$query}%")
                      ->orWhere('gstin', 'like', "%{$query}%")
                      ->orWhere('phone', 'like', "%{$query}%")
                      ->orWhere('mobile', 'like', "%{$query}%");
                }
            })
            ->select('party_id')
            ->distinct()
            ->orderByDesc('created_at')
            ->limit(10)
            ->pluck('party_id')
            ->toArray();

        $recentCustomers = Party::customers()
            ->where('is_active', true)
            ->whereIn('id', $recentCustomerIds)
            ->get(['id', 'name', 'display_name', 'gstin', 'phone', 'mobile', 'billing_address', 'shipping_address', 'state', 'city', 'credit_days']);

        $otherCustomers = Party::customers()
            ->where('is_active', true)
            ->whereNotIn('id', $recentCustomerIds)
            ->where(function($q) use ($query) {
                if ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('display_name', 'like', "%{$query}%")
                      ->orWhere('gstin', 'like', "%{$query}%")
                      ->orWhere('phone', 'like', "%{$query}%")
                      ->orWhere('mobile', 'like', "%{$query}%");
                }
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'display_name', 'gstin', 'phone', 'mobile', 'billing_address', 'shipping_address', 'state', 'city', 'credit_days']);

        $allCustomers = $recentCustomers->concat($otherCustomers)->unique('id')->take(20);

        $results = $allCustomers->map(fn($c) => [
            'id'               => $c->id,
            'name'             => $c->display_name ?? $c->name,
            'gstin'            => $c->gstin ?? '',
            'phone'            => $c->phone ?? $c->mobile ?? '',
            'city'             => $c->city ?? '',
            'state'            => $c->state ?? '',
            'billing_address'  => $c->billing_address ?? '',
            'shipping_address' => $c->shipping_address ?? '',
            'credit_days'      => $c->credit_days ?? 0,
            'is_recent'        => in_array($c->id, $recentCustomerIds),
        ]);

        $recent = $results->where('is_recent', true)->values();
        $suggestions = $results->where('is_recent', false)->values();

        return response()->json([
            'recent' => $recent,
            'suggestions' => $suggestions,
        ]);
    }

    // ── API: search products ──────────────────────────────────────────────────

    public function searchProducts(Request $request)
    {
        $query = $request->input('q', '');
        $company = Company::getDefault();

        $recentProductIds = ProformaInvoiceItem::whereHas('invoice', function($q) use ($company) {
                $q->where('company_id', $company?->id);
            })
            ->where(function($q) use ($query) {
                if ($query) {
                    $q->whereHas('product', function($p) use ($query) {
                        $p->where('name', 'like', "%{$query}%")
                          ->orWhere('code', 'like', "%{$query}%")
                          ->orWhere('hsn_sac', 'like', "%{$query}%");
                    });
                }
            })
            ->select('product_id')
            ->distinct()
            ->orderByDesc('created_at')
            ->limit(10)
            ->pluck('product_id')
            ->toArray();

        $recentProducts = Product::where('is_active', true)
            ->with('unit')
            ->whereIn('id', $recentProductIds)
            ->get(['id', 'code', 'name', 'description', 'hsn_sac', 'unit_id', 'sale_price', 'tax_rate']);

        $otherProducts = Product::where('is_active', true)
            ->with('unit')
            ->whereNotIn('id', $recentProductIds)
            ->where(function($q) use ($query) {
                if ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('code', 'like', "%{$query}%")
                      ->orWhere('hsn_sac', 'like', "%{$query}%");
                }
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'code', 'name', 'description', 'hsn_sac', 'unit_id', 'sale_price', 'tax_rate']);

        $allProducts = $recentProducts->concat($otherProducts)->unique('id')->take(20);

        $results = $allProducts->map(fn($p) => [
            'id'          => $p->id,
            'code'        => $p->code ?? '',
            'name'        => $p->name,
            'description' => $p->description ?? $p->name,
            'hsn_sac'     => $p->hsn_sac ?? '',
            'unit'        => $p->unit?->symbol ?? '',
            'unit_price'  => (float) $p->sale_price,
            'tax_rate'    => (float) $p->tax_rate,
            'is_recent'   => in_array($p->id, $recentProductIds),
        ]);

        $recent = $results->where('is_recent', true)->values();
        $suggestions = $results->where('is_recent', false)->values();

        return response()->json([
            'recent' => $recent,
            'suggestions' => $suggestions,
        ]);
    }

    // ── API: product details for line item autofill ───────────────────────────

    public function productDetails(Product $product)
    {
        return response()->json([
            'id'          => $product->id,
            'name'        => $product->name,
            'description' => $product->description ?? $product->name,
            'hsn_sac'     => $product->hsn_sac,
            'unit'        => $product->unit?->symbol ?? '',
            'unit_price'  => (float) $product->sale_price,
            'tax_rate'    => (float) $product->tax_rate,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validateHeader(Request $request): array
    {
        $data = $request->validate([
            'proforma_date'    => ['required', 'date'],
            'due_date'         => ['nullable', 'date', 'after_or_equal:proforma_date'],
            'party_id'         => ['required', 'exists:parties,id'],
            'billing_address'  => ['nullable', 'string', 'max:500'],
            'shipping_address' => ['nullable', 'string', 'max:500'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'place_of_supply'  => ['nullable', 'string', 'max:100'],
            'is_igst'          => ['nullable', 'boolean'],
            'notes'            => ['nullable', 'string', 'max:1000'],
            'terms'            => ['nullable', 'string', 'max:1000'],
            'narration'        => ['nullable', 'string', 'max:1000'],
            'bill_discount_type' => ['nullable', 'in:percentage,amount'],
            'bill_discount_value' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['is_igst'] = $request->boolean('is_igst', false);
        return $data;
    }

    private function validateItems(Request $request): array
    {
        $request->validate([
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.description'    => ['required', 'string', 'max:500'],
            'items.*.qty'            => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price'     => ['required', 'numeric', 'min:0'],
            'items.*.discount_type'  => ['nullable', 'in:percentage,amount'],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_pct'   => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_rate'       => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.product_id'     => ['nullable', 'exists:products,id'],
            'items.*.hsn_sac'        => ['nullable', 'string', 'max:20'],
            'items.*.unit'           => ['nullable', 'string', 'max:20'],
        ]);

        return $request->input('items');
    }

    private function computeTotals(array $items, bool $isIgst, array $headerData = []): array
    {
        $subtotal      = 0;
        $discountTotal = 0;
        $taxableTotal  = 0;
        $cgstTotal     = 0;
        $sgstTotal     = 0;
        $igstTotal     = 0;
        $totalTax      = 0;

        foreach ($items as &$item) {
            $qty          = (float) ($item['qty'] ?? 1);
            $unitPrice    = (float) ($item['unit_price'] ?? 0);
            $taxRate      = (float) ($item['tax_rate'] ?? 0);

            $lineGross    = round($qty * $unitPrice, 2);

            $discAmt = 0;
            if (isset($item['discount_type']) && $item['discount_type'] === 'amount') {
                $discAmt = round((float) ($item['discount_value'] ?? 0), 2);
            } else {
                $discountPct = (float) ($item['discount_pct'] ?? $item['discount_value'] ?? 0);
                $discAmt = round($lineGross * $discountPct / 100, 2);
            }

            $taxable      = round($lineGross - $discAmt, 2);

            if ($isIgst) {
                $igst = round($taxable * $taxRate / 100, 2);
                $cgst = 0;
                $sgst = 0;
            } else {
                $half = $taxRate / 2;
                $cgst = round($taxable * $half / 100, 2);
                $sgst = round($taxable * $half / 100, 2);
                $igst = 0;
            }

            $lineTax   = $cgst + $sgst + $igst;
            $lineTotal = $taxable + $lineTax;

            $item['discount_amount'] = $discAmt;
            $item['taxable_amount']  = $taxable;
            $item['cgst_rate']       = $isIgst ? 0 : $taxRate / 2;
            $item['sgst_rate']       = $isIgst ? 0 : $taxRate / 2;
            $item['igst_rate']       = $isIgst ? $taxRate : 0;
            $item['cgst_amount']     = $cgst;
            $item['sgst_amount']     = $sgst;
            $item['igst_amount']     = $igst;
            $item['total_tax']       = $lineTax;
            $item['line_total']      = $lineTotal;

            $subtotal      += $lineGross;
            $discountTotal += $discAmt;
            $taxableTotal  += $taxable;
            $cgstTotal     += $cgst;
            $sgstTotal     += $sgst;
            $igstTotal     += $igst;
            $totalTax      += $lineTax;
        }
        unset($item);

        // Apply bill-level discount
        $billDiscountAmt = 0;
        if (!empty($headerData['bill_discount_type']) && !empty($headerData['bill_discount_value'])) {
            if ($headerData['bill_discount_type'] === 'amount') {
                $billDiscountAmt = round((float) $headerData['bill_discount_value'], 2);
            } else {
                $billDiscountAmt = round($taxableTotal * (float) $headerData['bill_discount_value'] / 100, 2);
            }
        }

        $taxableTotal = round($taxableTotal - $billDiscountAmt, 2);
        $discountTotal = round($discountTotal + $billDiscountAmt, 2);

        if ($billDiscountAmt > 0) {
            $cgstTotal = 0;
            $sgstTotal = 0;
            $igstTotal = 0;
            $totalTax = 0;

            foreach ($items as &$item) {
                $itemTaxable = (float) $item['taxable_amount'];
                $proportion = $taxableTotal > 0 ? ($itemTaxable / ($taxableTotal + $billDiscountAmt)) : 0;
                $adjustedTaxable = round($taxableTotal * $proportion, 2);
                $taxRate = (float) ($item['tax_rate'] ?? 0);

                if ($isIgst) {
                    $igst = round($adjustedTaxable * $taxRate / 100, 2);
                    $cgst = 0;
                    $sgst = 0;
                } else {
                    $half = $taxRate / 2;
                    $cgst = round($adjustedTaxable * $half / 100, 2);
                    $sgst = round($adjustedTaxable * $half / 100, 2);
                    $igst = 0;
                }

                $cgstTotal += $cgst;
                $sgstTotal += $sgst;
                $igstTotal += $igst;
                $totalTax += ($cgst + $sgst + $igst);
            }
            unset($item);
        }

        $grandTotal = $taxableTotal + $totalTax;

        return [
            'subtotal'        => round($subtotal, 2),
            'discount_amount' => round($discountTotal, 2),
            'taxable_amount'  => round($taxableTotal, 2),
            'cgst_amount'     => round($cgstTotal, 2),
            'sgst_amount'     => round($sgstTotal, 2),
            'igst_amount'     => round($igstTotal, 2),
            'cess_amount'     => 0,
            'total_tax'       => round($totalTax, 2),
            'grand_total'     => round($grandTotal, 2),
            'amount_paid'     => 0,
            'amount_due'      => round($grandTotal, 2),
        ];
    }

    private function row(ProformaInvoice $inv): array
    {
        $badge = $inv->statusBadge();
        return [
            'id'              => $inv->id,
            'proforma_number' => $inv->proforma_number,
            'proforma_date'   => $inv->proforma_date->format('d M Y'),
            'due_date'        => $inv->due_date?->format('d M Y') ?? '—',
            'party_name'      => $inv->party?->name ?? '—',
            'party_gstin'     => $inv->party?->gstin ?? '',
            'grand_total'     => number_format((float) $inv->grand_total, 2),
            'amount_due'      => number_format((float) $inv->amount_due, 2),
            'status'          => $inv->status,
            'status_label'    => $badge['label'],
            'status_class'    => $badge['class'],
            'can_edit'        => $inv->canEdit(),
            'can_submit'      => $inv->canSubmit(),
            'can_approve'     => $inv->canApprove(),
            'can_reject'      => $inv->canReject(),
            'can_cancel'      => $inv->canCancel(),
            'can_delete'      => $inv->isDraft(),
            'can_convert'     => $inv->canConvert(),
            'is_converted'    => $inv->is_converted,
            'created_by'      => $inv->creator?->name ?? '—',
        ];
    }

    // ── Send approval notification emails ─────────────────────────────────────

    private function createPendingLogsAndNotify(ProformaInvoice $proforma, array $levelConfig, int $level): void
    {
        $proforma->load('party', 'submitter');
        $requireSignature = $levelConfig['require_signature'] ?? false;

        foreach ($levelConfig['approver_ids'] ?? [] as $approverId) {
            $token = Str::random(64);

            $log = ApprovalLog::create([
                'document_type' => 'proforma',
                'document_id'   => $proforma->id,
                'level'         => $level,
                'level_name'    => $levelConfig['name'] ?? "Level {$level}",
                'user_id'       => $approverId,
                'action'        => 'pending',
                'token'         => $token,
            ]);

            $notifyVia = $levelConfig['notify_via'] ?? 'email';
            if (in_array($notifyVia, ['email', 'both'])) {
                $approver = User::find($approverId);
                if ($approver && $approver->email) {
                    try {
                        Mail::to($approver->email)->send(new ApprovalRequestMail($proforma, $approver, $log, $requireSignature));
                    } catch (\Exception $e) {
                        \Log::error("Failed to send approval email to {$approver->email}: " . $e->getMessage());
                    }
                }
            }
        }
    }
}
