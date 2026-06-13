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
use App\Models\SaleInvoice;
use App\Models\SaleInvoiceItem;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\LedgerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SaleInvoiceController extends Controller
{
    protected $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    // ── List ─────────────────────────────────────────────────────────────────

    public function index()
    {
        $company = Company::getDefault();
        $fy      = $company
            ? FinancialYear::where('company_id', $company->id)->where('is_current', true)->first()
            : null;

        return view('panel.sales.invoices', compact('company', 'fy'));
    }

    // ── DataTables JSON ───────────────────────────────────────────────────────

    public function data(Request $request)
    {
        $company = Company::getDefault();
        $query   = SaleInvoice::with('party', 'creator')
            ->where('company_id', $company?->id);

        // Search
        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(fn($q) => $q
                ->where('invoice_number', 'like', "%{$search}%")
                ->orWhereHas('party', fn($p) => $p->where('name', 'like', "%{$search}%"))
            );
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->date_to);
        }

        $total    = $query->count();
        $start    = (int) $request->get('start', 0);
        $length   = (int) $request->get('length', 15);
        $order    = $request->input('order', [['column' => 0, 'dir' => 'desc']]);
        $cols     = ['invoice_number', 'invoice_date', 'party_name', 'grand_total', 'status', 'created_by'];
        $col      = $cols[(int)($order[0]['column'] ?? 0)] ?? 'invoice_date';
        $dir      = ($order[0]['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if ($col === 'party_name') {
            $query->join('parties', 'parties.id', '=', 'sale_invoices.party_id')
                  ->orderBy('parties.name', $dir)
                  ->select('sale_invoices.*');
        } else {
            $sortable = ['invoice_number', 'invoice_date', 'grand_total', 'status'];
            $query->orderBy(in_array($col, $sortable) ? $col : 'invoice_date', $dir);
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
        $nextNumber = $company ? SaleInvoice::generateNumber($company->id) : 'INV/0001';
        // Revert the increment — we only commit on actual save
        if ($company) {
            \App\Models\NumberingSetting::where('company_id', $company->id)
                ->where('document_type', 'invoice')
                ->decrement('next_number');
        }

        return view('panel.sales.invoice-form', compact(
            'company', 'fy', 'customers', 'products', 'taxRates', 'nextNumber'
        ));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $data  = $this->validateHeader($request);
        $items = $this->validateItems($request);

        $invoice = null;

        DB::transaction(function () use ($data, $items, $request, &$invoice) {
            $company = Company::getDefault();

            $data['company_id']        = $company->id;
            $data['financial_year_id'] = FinancialYear::where('company_id', $company->id)
                ->where('is_current', true)->value('id');
            $data['invoice_number']    = SaleInvoice::generateNumber($company->id);
            $data['created_by']        = auth()->id();
            $data['status']            = 'draft';

            // Totals
            $totals = $this->computeTotals($items, (bool) ($data['is_igst'] ?? false), $data);
            $data   = array_merge($data, $totals);

            $invoice = SaleInvoice::create($data);

            foreach ($items as $i => $item) {
                $invoice->items()->create(array_merge($item, ['sort_order' => $i]));
            }

            ActivityLogger::log('created', $invoice, null, $invoice->getAttributes());
        });

        return response()->json([
            'success'  => true,
            'message'  => 'Invoice saved as draft.',
            'id'       => $invoice->id,
            'redirect' => route('sales.invoices.edit', $invoice),
        ]);
    }

    // ── Edit form ─────────────────────────────────────────────────────────────

    public function edit(SaleInvoice $invoice)
    {
        if (! $invoice->canEdit()) {
            return redirect()->route('sales.invoices')
                ->with('error', 'This invoice cannot be edited in its current status.');
        }

        $company   = Company::getDefault();
        $fy        = FinancialYear::find($invoice->financial_year_id);
        $customers = Party::customers()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'display_name', 'gstin', 'billing_address', 'shipping_address', 'state', 'credit_days']);
        $products  = Product::where('is_active', true)->with('unit')->orderBy('name')->get(['id', 'code', 'name', 'sale_price', 'tax_rate', 'hsn_sac', 'unit_id']);
        $taxRates  = TaxRate::where('is_active', true)->orderBy('rate')->get(['id', 'name', 'rate', 'cgst', 'sgst', 'igst']);
        $nextNumber = $invoice->invoice_number;

        return view('panel.sales.invoice-form', compact(
            'invoice', 'company', 'fy', 'customers', 'products', 'taxRates', 'nextNumber'
        ));
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(Request $request, SaleInvoice $invoice)
    {
        if (! $invoice->canEdit()) {
            return response()->json(['success' => false, 'message' => 'Invoice cannot be edited in its current status.'], 422);
        }

        $data  = $this->validateHeader($request);
        $items = $this->validateItems($request);

        DB::transaction(function () use ($invoice, $data, $items, $request) {
            $old    = $invoice->getAttributes();
            $totals = $this->computeTotals($items, (bool) ($data['is_igst'] ?? false), $data);
            $data   = array_merge($data, $totals);

            $invoice->update($data);
            $invoice->items()->delete();

            foreach ($items as $i => $item) {
                $invoice->items()->create(array_merge($item, ['sort_order' => $i]));
            }

            ActivityLogger::log('updated', $invoice, $old, $invoice->getAttributes());
        });

        return response()->json([
            'success' => true,
            'message' => 'Invoice updated successfully.',
            'id'      => $invoice->id,
            'stay'    => true,
        ]);
    }

    // ── Show (view-only) ──────────────────────────────────────────────────────

    public function show(SaleInvoice $invoice)
    {
        $invoice->load('party', 'items.product', 'creator', 'approver', 'submitter', 'rejecter', 'company');
        return view('panel.sales.invoice-show', compact('invoice'));
    }

    // ── PDF download ──────────────────────────────────────────────────────────

    public function pdf(Request $request, SaleInvoice $invoice)
    {
        $invoice->load('party', 'items.product', 'creator', 'approver', 'submitter', 'rejecter', 'company');

        $allowed  = ['1', '2', '3', '4', '5', '6'];
        $template = in_array($request->query('template'), $allowed) ? $request->query('template') : '1';
        $view     = "panel.sales.pdf.template-{$template}";

        $pdf = Pdf::loadView($view, compact('invoice'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'          => 'DejaVu Sans',
                'isRemoteEnabled'      => false,
                'isHtml5ParserEnabled' => true,
                'dpi'                  => 96,
            ]);

        $filename = 'Invoice-' . str_replace('/', '-', $invoice->invoice_number) . '.pdf';

        return $pdf->download($filename);
    }

    // ── Workflow actions ──────────────────────────────────────────────────────

    public function submit(SaleInvoice $invoice)
    {
        if (! $invoice->canSubmit()) {
            return response()->json(['success' => false, 'message' => 'Invoice cannot be submitted.'], 422);
        }

        $old = $invoice->getAttributes();

        // Check approval settings
        $approvalSetting = ApprovalSetting::getFor('invoice', $invoice->company_id);

        if ($approvalSetting && $approvalSetting->isAutoApproved()) {
            // Auto-approve: skip approval flow
            $invoice->update([
                'status'       => 'approved',
                'submitted_by' => auth()->id(),
                'submitted_at' => now(),
                'approved_by'  => auth()->id(),
                'approved_at'  => now(),
                'current_approval_level' => 0,
                'max_approval_level'     => 0,
            ]);
            ActivityLogger::log('auto_approved', $invoice, $old, $invoice->getAttributes());
            return response()->json(['success' => true, 'message' => 'Invoice auto-approved.']);
        }

        if ($approvalSetting && $approvalSetting->isRequired()) {
            // Multi-level approval: set to submitted, create pending logs for level 1
            $levelsCount = $approvalSetting->levels_count;
            $invoice->update([
                'status'                 => 'submitted',
                'submitted_by'           => auth()->id(),
                'submitted_at'           => now(),
                'current_approval_level' => 1,
                'max_approval_level'     => $levelsCount,
            ]);

            // Create pending approval logs for level 1 approvers
            $level1 = $approvalSetting->getLevel(1);
            if ($level1) {
                $this->createPendingLogsAndNotify($invoice, $level1, 1);
            }

            ActivityLogger::log('submitted', $invoice, $old, $invoice->getAttributes());
            return response()->json(['success' => true, 'message' => 'Invoice submitted for approval (Level 1).']);
        }

        // No approval / default: simple submit → approved
        $invoice->update([
            'status'       => 'approved',
            'submitted_by' => auth()->id(),
            'submitted_at' => now(),
            'approved_by'  => auth()->id(),
            'approved_at'  => now(),
        ]);
        ActivityLogger::log('submitted', $invoice, $old, $invoice->getAttributes());

        return response()->json(['success' => true, 'message' => 'Invoice approved (no approval required).']);
    }

    public function approve(SaleInvoice $invoice)
    {
        if (! $invoice->canApprove()) {
            return response()->json(['success' => false, 'message' => 'Invoice cannot be approved.'], 422);
        }

        $old = $invoice->getAttributes();
        $invoice->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        ActivityLogger::log('approved', $invoice, $old, $invoice->getAttributes());

        // Post to Ledger
        try {
            $this->ledgerService->postSalesInvoice($invoice->fresh(['party']));
        } catch (\Exception $e) {
            \Log::error('Failed to post invoice to ledger: ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Invoice approved successfully.']);
    }

    public function reject(Request $request, SaleInvoice $invoice)
    {
        if (! $invoice->canReject()) {
            return response()->json(['success' => false, 'message' => 'Invoice cannot be rejected.'], 422);
        }

        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $old = $invoice->getAttributes();
        $invoice->update([
            'status'           => 'rejected',
            'rejected_by'      => auth()->id(),
            'rejected_at'      => now(),
            'rejection_reason' => $request->reason,
        ]);
        ActivityLogger::log('rejected', $invoice, $old, $invoice->getAttributes());

        return response()->json(['success' => true, 'message' => 'Invoice rejected.']);
    }

    public function cancel(Request $request, SaleInvoice $invoice)
    {
        if (! $invoice->canCancel()) {
            return response()->json(['success' => false, 'message' => 'Invoice cannot be cancelled.'], 422);
        }

        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $old = $invoice->getAttributes();
        $invoice->update([
            'status'        => 'cancelled',
            'cancelled_by'  => auth()->id(),
            'cancelled_at'  => now(),
            'cancel_reason' => $request->reason,
        ]);
        ActivityLogger::log('cancelled', $invoice, $old, $invoice->getAttributes());

        return response()->json(['success' => true, 'message' => 'Invoice cancelled.']);
    }

    // ── Multi-level approval actions ──────────────────────────────────────────

    public function levelApprove(Request $request, SaleInvoice $invoice)
    {
        $request->validate(['remarks' => ['nullable', 'string', 'max:500']]);

        if ($invoice->status !== 'submitted') {
            return response()->json(['success' => false, 'message' => 'Invoice is not pending approval.'], 422);
        }

        $userId = auth()->id();
        $currentLevel = $invoice->current_approval_level;
        $approvalSetting = ApprovalSetting::getFor('invoice', $invoice->company_id);

        if (!$approvalSetting || !$approvalSetting->isRequired()) {
            return response()->json(['success' => false, 'message' => 'No approval settings configured.'], 422);
        }

        $levelConfig = $approvalSetting->getLevel($currentLevel);
        if (!$levelConfig) {
            return response()->json(['success' => false, 'message' => 'Invalid approval level.'], 422);
        }

        // Check if user is an approver at this level
        if (!in_array($userId, $levelConfig['approver_ids'] ?? [])) {
            return response()->json(['success' => false, 'message' => 'You are not an approver at this level.'], 422);
        }

        // Check if already acted
        $existingLog = ApprovalLog::where('document_type', 'invoice')
            ->where('document_id', $invoice->id)
            ->where('level', $currentLevel)
            ->where('user_id', $userId)
            ->whereIn('action', ['approved', 'rejected'])
            ->first();

        if ($existingLog) {
            return response()->json(['success' => false, 'message' => 'You have already acted on this level.'], 422);
        }

        // Update the pending log to approved
        ApprovalLog::where('document_type', 'invoice')
            ->where('document_id', $invoice->id)
            ->where('level', $currentLevel)
            ->where('user_id', $userId)
            ->update([
                'action'    => 'approved',
                'remarks'   => $request->remarks,
                'acted_at'  => now(),
            ]);

        // Check if level is complete
        $approvalType = $levelConfig['approval_type'] ?? 'any_one';
        $levelComplete = false;

        if ($approvalType === 'any_one') {
            // Any one approver is enough
            $levelComplete = true;
        } else {
            // All must approve — check if all approvers at this level have approved
            $pendingCount = ApprovalLog::where('document_type', 'invoice')
                ->where('document_id', $invoice->id)
                ->where('level', $currentLevel)
                ->where('action', 'pending')
                ->count();
            $levelComplete = ($pendingCount === 0);
        }

        if ($levelComplete) {
            $nextLevel = $currentLevel + 1;
            if ($nextLevel > $invoice->max_approval_level) {
                // All levels complete — mark as approved
                $old = $invoice->getAttributes();
                $invoice->update([
                    'status'                 => 'approved',
                    'approved_by'            => $userId,
                    'approved_at'            => now(),
                    'current_approval_level' => $currentLevel,
                ]);
                ActivityLogger::log('approved', $invoice, $old, $invoice->getAttributes());
                return response()->json(['success' => true, 'message' => 'Invoice fully approved.']);
            } else {
                // Move to next level
                $invoice->update(['current_approval_level' => $nextLevel]);

                // Create pending logs for next level
                $nextLevelConfig = $approvalSetting->getLevel($nextLevel);
                if ($nextLevelConfig) {
                    $this->createPendingLogsAndNotify($invoice, $nextLevelConfig, $nextLevel);
                }

                return response()->json(['success' => true, 'message' => "Level {$currentLevel} approved. Moved to Level {$nextLevel}."]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Your approval recorded. Waiting for other approvers.']);
    }

    public function levelReject(Request $request, SaleInvoice $invoice)
    {
        $request->validate(['remarks' => ['required', 'string', 'max:500']]);

        if ($invoice->status !== 'submitted') {
            return response()->json(['success' => false, 'message' => 'Invoice is not pending approval.'], 422);
        }

        $userId = auth()->id();
        $currentLevel = $invoice->current_approval_level;
        $approvalSetting = ApprovalSetting::getFor('invoice', $invoice->company_id);

        if (!$approvalSetting || !$approvalSetting->isRequired()) {
            return response()->json(['success' => false, 'message' => 'No approval settings configured.'], 422);
        }

        $levelConfig = $approvalSetting->getLevel($currentLevel);
        if (!$levelConfig || !in_array($userId, $levelConfig['approver_ids'] ?? [])) {
            return response()->json(['success' => false, 'message' => 'You are not an approver at this level.'], 422);
        }

        // Update log to rejected
        ApprovalLog::where('document_type', 'invoice')
            ->where('document_id', $invoice->id)
            ->where('level', $currentLevel)
            ->where('user_id', $userId)
            ->update([
                'action'   => 'rejected',
                'remarks'  => $request->remarks,
                'acted_at' => now(),
            ]);

        // Any rejection = immediate rejection of the whole invoice
        $old = $invoice->getAttributes();
        $invoice->update([
            'status'           => 'rejected',
            'rejected_by'      => $userId,
            'rejected_at'      => now(),
            'rejection_reason' => $request->remarks,
        ]);
        ActivityLogger::log('rejected', $invoice, $old, $invoice->getAttributes());

        return response()->json(['success' => true, 'message' => 'Invoice rejected.']);
    }

    public function approvalLogs(SaleInvoice $invoice)
    {
        $logs = ApprovalLog::where('document_type', 'invoice')
            ->where('document_id', $invoice->id)
            ->with('user:id,name,email')
            ->orderBy('level')
            ->orderBy('created_at')
            ->get();

        $approvalSetting = ApprovalSetting::getFor('invoice', $invoice->company_id);

        return response()->json([
            'success'  => true,
            'logs'     => $logs,
            'invoice'  => [
                'id'                     => $invoice->id,
                'status'                 => $invoice->status,
                'current_approval_level' => $invoice->current_approval_level,
                'max_approval_level'     => $invoice->max_approval_level,
            ],
            'setting'  => $approvalSetting ? [
                'approval_mode' => $approvalSetting->approval_mode,
                'levels_count'  => $approvalSetting->levels_count,
                'levels'        => $approvalSetting->levels,
            ] : null,
        ]);
    }

    public function destroy(SaleInvoice $invoice)
    {
        if (! $invoice->isDraft()) {
            return response()->json(['success' => false, 'message' => 'Only draft invoices can be deleted.'], 422);
        }

        $snapshot = $invoice->getAttributes();
        $invoice->delete();
        ActivityLogger::log('deleted', $invoice, $snapshot, null);

        return response()->json(['success' => true, 'message' => 'Invoice deleted.']);
    }

    // ── API: next invoice number preview ─────────────────────────────────────

    public function nextNumber()
    {
        $company = Company::getDefault();
        if (! $company) {
            return response()->json(['number' => 'INV/0001']);
        }

        $setting = \App\Models\NumberingSetting::where('company_id', $company->id)
            ->where('document_type', 'invoice')
            ->first();

        return response()->json(['number' => $setting ? $setting->buildPreview() : 'INV/0001']);
    }

    // ── API: search customers ─────────────────────────────────────────────────

    public function searchCustomers(Request $request)
    {
        $query = $request->input('q', '');
        $company = Company::getDefault();
        
        // Get recently used customers from this company's invoices
        $recentCustomerIds = SaleInvoice::where('company_id', $company?->id)
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

        // Get recently used customers
        $recentCustomers = Party::customers()
            ->where('is_active', true)
            ->whereIn('id', $recentCustomerIds)
            ->get(['id', 'name', 'display_name', 'gstin', 'phone', 'mobile', 'billing_address', 'shipping_address', 'state', 'city', 'credit_days']);

        // Get other matching customers (excluding recent ones)
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

        // Combine: recent first, then others
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

        // Separate recent and suggestions
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
        
        // Get recently used products from this company's invoices
        $recentProductIds = SaleInvoiceItem::whereHas('invoice', function($q) use ($company) {
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

        // Get recently used products
        $recentProducts = Product::where('is_active', true)
            ->with('unit')
            ->whereIn('id', $recentProductIds)
            ->get(['id', 'code', 'name', 'description', 'hsn_sac', 'unit_id', 'sale_price', 'tax_rate']);

        // Get other matching products (excluding recent ones)
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

        // Combine: recent first, then others
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

        // Separate recent and suggestions
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
            'invoice_date'     => ['required', 'date'],
            'due_date'         => ['nullable', 'date', 'after_or_equal:invoice_date'],
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
            'advance_amount'   => ['nullable', 'numeric', 'min:0'],
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

            // Handle item discount (both % and amount)
            $discAmt = 0;
            if (isset($item['discount_type']) && $item['discount_type'] === 'amount') {
                $discAmt = round((float) ($item['discount_value'] ?? 0), 2);
            } else {
                // Default to percentage
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
                // Percentage
                $billDiscountAmt = round($taxableTotal * (float) $headerData['bill_discount_value'] / 100, 2);
            }
        }

        $taxableTotal = round($taxableTotal - $billDiscountAmt, 2);
        $discountTotal = round($discountTotal + $billDiscountAmt, 2);

        // Recalculate tax on adjusted taxable amount if bill discount applied
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

        $grandTotal = round($taxableTotal + $totalTax, 2);
        
        // Handle advance payment
        $advanceAmount = 0;
        if (!empty($headerData['advance_amount'])) {
            $advanceAmount = round((float) $headerData['advance_amount'], 2);
            // Ensure advance doesn't exceed grand total
            $advanceAmount = min($advanceAmount, $grandTotal);
        }
        
        // Calculate amount_paid and amount_due
        // advance_amount is tracked separately, amount_paid includes advance + any additional payments
        $amountPaid = $advanceAmount;
        $amountDue = round($grandTotal - $amountPaid, 2);

        return [
            'subtotal'        => round($subtotal, 2),
            'discount_amount' => round($discountTotal, 2),
            'taxable_amount'  => round($taxableTotal, 2),
            'cgst_amount'     => round($cgstTotal, 2),
            'sgst_amount'     => round($sgstTotal, 2),
            'igst_amount'     => round($igstTotal, 2),
            'cess_amount'     => 0,
            'total_tax'       => round($totalTax, 2),
            'grand_total'     => $grandTotal,
            'advance_amount'  => $advanceAmount,
            'amount_paid'     => $amountPaid,
            'amount_due'      => $amountDue,
        ];
    }

    private function row(SaleInvoice $inv): array
    {
        $badge = $inv->statusBadge();
        return [
            'id'             => $inv->id,
            'invoice_number' => $inv->invoice_number,
            'invoice_date'   => $inv->invoice_date->format('d M Y'),
            'due_date'       => $inv->due_date?->format('d M Y') ?? '—',
            'party_name'     => $inv->party?->name ?? '—',
            'party_gstin'    => $inv->party?->gstin ?? '',
            'grand_total'    => number_format((float) $inv->grand_total, 2),
            'amount_due'     => number_format((float) $inv->amount_due, 2),
            'status'         => $inv->status,
            'status_label'   => $badge['label'],
            'status_class'   => $badge['class'],
            'can_edit'       => $inv->canEdit(),
            'can_submit'     => $inv->canSubmit(),
            'can_approve'    => $inv->canApprove(),
            'can_reject'     => $inv->canReject(),
            'can_cancel'     => $inv->canCancel(),
            'can_delete'     => $inv->isDraft(),
            'created_by'     => $inv->creator?->name ?? '—',
        ];
    }

    // ── Send approval notification emails ─────────────────────────────────────

    private function createPendingLogsAndNotify(SaleInvoice $invoice, array $levelConfig, int $level): void
    {
        $invoice->load('party', 'submitter');
        $requireSignature = $levelConfig['require_signature'] ?? false;

        foreach ($levelConfig['approver_ids'] ?? [] as $approverId) {
            $token = Str::random(64);

            $log = ApprovalLog::create([
                'document_type' => 'invoice',
                'document_id'   => $invoice->id,
                'level'         => $level,
                'level_name'    => $levelConfig['name'] ?? "Level {$level}",
                'user_id'       => $approverId,
                'action'        => 'pending',
                'token'         => $token,
            ]);

            // Send email notification if notify_via includes email
            $notifyVia = $levelConfig['notify_via'] ?? 'email';
            if (in_array($notifyVia, ['email', 'both'])) {
                $approver = User::find($approverId);
                if ($approver && $approver->email) {
                    try {
                        Mail::to($approver->email)->send(new ApprovalRequestMail($invoice, $approver, $log, $requireSignature));
                    } catch (\Exception $e) {
                        \Log::error("Failed to send approval email to {$approver->email}: " . $e->getMessage());
                    }
                }
            }
        }
    }

    // ── Direct email approval/rejection via token ─────────────────────────────

    public static function handleEmailAction(string $token, string $action)
    {
        $log = ApprovalLog::where('token', $token)->where('action', 'pending')->first();

        if (!$log) {
            return ['success' => false, 'message' => 'This approval link has already been used or is invalid.'];
        }

        $invoice = SaleInvoice::find($log->document_id);
        if (!$invoice || $invoice->status !== 'submitted') {
            return ['success' => false, 'message' => 'This invoice is no longer pending approval.'];
        }

        if ($invoice->current_approval_level !== $log->level) {
            return ['success' => false, 'message' => 'This approval level is no longer active.'];
        }

        $approvalSetting = ApprovalSetting::getFor('invoice', $invoice->company_id);
        if (!$approvalSetting) {
            return ['success' => false, 'message' => 'Approval settings not found.'];
        }

        $levelConfig = $approvalSetting->getLevel($log->level);

        if ($action === 'approve') {
            $log->update(['action' => 'approved', 'acted_at' => now(), 'remarks' => 'Approved via email']);

            // Check if level is complete
            $approvalType = $levelConfig['approval_type'] ?? 'any_one';
            $levelComplete = false;

            if ($approvalType === 'any_one') {
                $levelComplete = true;
            } else {
                $pendingCount = ApprovalLog::where('document_type', 'invoice')
                    ->where('document_id', $invoice->id)
                    ->where('level', $log->level)
                    ->where('action', 'pending')
                    ->count();
                $levelComplete = ($pendingCount === 0);
            }

            if ($levelComplete) {
                $nextLevel = $log->level + 1;
                if ($nextLevel > $invoice->max_approval_level) {
                    $invoice->update([
                        'status'      => 'approved',
                        'approved_by' => $log->user_id,
                        'approved_at' => now(),
                    ]);
                } else {
                    $invoice->update(['current_approval_level' => $nextLevel]);
                    $nextLevelConfig = $approvalSetting->getLevel($nextLevel);
                    if ($nextLevelConfig) {
                        (new static)->createPendingLogsAndNotify($invoice, $nextLevelConfig, $nextLevel);
                    }
                }
            }

            return ['success' => true, 'message' => 'Invoice approved successfully.'];
        }

        if ($action === 'reject') {
            $log->update(['action' => 'rejected', 'acted_at' => now(), 'remarks' => 'Rejected via email']);
            $invoice->update([
                'status'           => 'rejected',
                'rejected_by'      => $log->user_id,
                'rejected_at'      => now(),
                'rejection_reason' => 'Rejected via email link',
            ]);

            return ['success' => true, 'message' => 'Invoice rejected.'];
        }

        return ['success' => false, 'message' => 'Invalid action.'];
    }
}
