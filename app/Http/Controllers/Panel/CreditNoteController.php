<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Mail\ApprovalRequestMail;
use App\Models\ApprovalLog;
use App\Models\ApprovalSetting;
use App\Models\Company;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\Product;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreditNoteController extends Controller
{
    // ── List ─────────────────────────────────────────────────────────────────

    public function index()
    {
        $company = Company::getDefault();
        $fy      = $company
            ? FinancialYear::where('company_id', $company->id)->where('is_current', true)->first()
            : null;

        return view('panel.sales.credit-notes', compact('company', 'fy'));
    }

    // ── DataTables JSON ───────────────────────────────────────────────────────

    public function data(Request $request)
    {
        $company = Company::getDefault();
        $query   = CreditNote::with('party', 'creator')
            ->where('company_id', $company?->id);

        // Search
        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(fn($q) => $q
                ->where('credit_note_number', 'like', "%{$search}%")
                ->orWhereHas('party', fn($p) => $p->where('name', 'like', "%{$search}%"))
            );
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('credit_note_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('credit_note_date', '<=', $request->date_to);
        }

        $total    = $query->count();
        $start    = (int) $request->get('start', 0);
        $length   = (int) $request->get('length', 15);
        $order    = $request->input('order', [['column' => 0, 'dir' => 'desc']]);
        $cols     = ['credit_note_number', 'credit_note_date', 'party_name', 'grand_total', 'status', 'created_by'];
        $col      = $cols[(int)($order[0]['column'] ?? 0)] ?? 'credit_note_date';
        $dir      = ($order[0]['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if ($col === 'party_name') {
            $query->join('parties', 'parties.id', '=', 'credit_notes.party_id')
                  ->orderBy('parties.name', $dir)
                  ->select('credit_notes.*');
        } else {
            $sortable = ['credit_note_number', 'credit_note_date', 'grand_total', 'status'];
            $query->orderBy(in_array($col, $sortable) ? $col : 'credit_note_date', $dir);
        }

        $filtered = $query->count();
        $rows     = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows->map(fn($cn) => $this->row($cn)),
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
        $nextNumber = $company ? CreditNote::generateNumber($company->id) : 'CN/0001';
        // Revert the increment — we only commit on actual save
        if ($company) {
            \App\Models\NumberingSetting::where('company_id', $company->id)
                ->where('document_type', 'credit_note')
                ->decrement('next_number');
        }

        return view('panel.sales.credit-note-form', compact(
            'company', 'fy', 'customers', 'products', 'taxRates', 'nextNumber'
        ));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $data  = $this->validateHeader($request);
        $items = $this->validateItems($request);

        $creditNote = null;

        DB::transaction(function () use ($data, $items, $request, &$creditNote) {
            $company = Company::getDefault();

            $data['company_id']        = $company->id;
            $data['financial_year_id'] = FinancialYear::where('company_id', $company->id)
                ->where('is_current', true)->value('id');
            $data['credit_note_number'] = CreditNote::generateNumber($company->id);
            $data['created_by']        = auth()->id();
            $data['status']            = 'draft';

            // Totals
            $totals = $this->computeTotals($items, (bool) ($data['is_igst'] ?? false), $data);
            $data   = array_merge($data, $totals);

            $creditNote = CreditNote::create($data);

            foreach ($items as $i => $item) {
                $creditNote->items()->create(array_merge($item, ['sort_order' => $i]));
            }

            ActivityLogger::log('created', $creditNote, null, $creditNote->getAttributes());
        });

        return response()->json([
            'success'  => true,
            'message'  => 'Credit note saved as draft.',
            'id'       => $creditNote->id,
            'redirect' => route('sales.credit-notes.edit', $creditNote),
        ]);
    }

    // ── Edit form ─────────────────────────────────────────────────────────────

    public function edit(CreditNote $creditNote)
    {
        if (! $creditNote->canEdit()) {
            return redirect()->route('sales.credit-notes')
                ->with('error', 'This credit note cannot be edited in its current status.');
        }

        $company   = Company::getDefault();
        $fy        = FinancialYear::find($creditNote->financial_year_id);
        $customers = Party::customers()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'display_name', 'gstin', 'billing_address', 'shipping_address', 'state', 'credit_days']);
        $products  = Product::where('is_active', true)->with('unit')->orderBy('name')->get(['id', 'code', 'name', 'sale_price', 'tax_rate', 'hsn_sac', 'unit_id']);
        $taxRates  = TaxRate::where('is_active', true)->orderBy('rate')->get(['id', 'name', 'rate', 'cgst', 'sgst', 'igst']);
        $nextNumber = $creditNote->credit_note_number;

        return view('panel.sales.credit-note-form', compact(
            'creditNote', 'company', 'fy', 'customers', 'products', 'taxRates', 'nextNumber'
        ));
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(Request $request, CreditNote $creditNote)
    {
        if (! $creditNote->canEdit()) {
            return response()->json(['success' => false, 'message' => 'Credit note cannot be edited in its current status.'], 422);
        }

        $data  = $this->validateHeader($request);
        $items = $this->validateItems($request);

        DB::transaction(function () use ($creditNote, $data, $items, $request) {
            $old    = $creditNote->getAttributes();
            $totals = $this->computeTotals($items, (bool) ($data['is_igst'] ?? false), $data);
            $data   = array_merge($data, $totals);

            $creditNote->update($data);
            $creditNote->items()->delete();

            foreach ($items as $i => $item) {
                $creditNote->items()->create(array_merge($item, ['sort_order' => $i]));
            }

            ActivityLogger::log('updated', $creditNote, $old, $creditNote->getAttributes());
        });

        return response()->json([
            'success' => true,
            'message' => 'Credit note updated successfully.',
            'id'      => $creditNote->id,
            'stay'    => true,
        ]);
    }

    // ── Show (view-only) ──────────────────────────────────────────────────────

    public function show(CreditNote $creditNote)
    {
        $creditNote->load('party', 'items.product', 'creator', 'approver', 'submitter', 'rejecter', 'company');
        return view('panel.sales.credit-note-show', compact('creditNote'));
    }

    // ── PDF download ──────────────────────────────────────────────────────────

    public function pdf(Request $request, CreditNote $creditNote)
    {
        $creditNote->load('party', 'items.product', 'creator', 'approver', 'submitter', 'rejecter', 'company');

        $allowed  = ['1', '2', '3', '4', '5', '6'];
        $template = in_array($request->query('template'), $allowed) ? $request->query('template') : '1';
        $view     = "panel.sales.credit-note-pdf";

        $pdf = Pdf::loadView($view, compact('creditNote'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'          => 'DejaVu Sans',
                'isRemoteEnabled'      => false,
                'isHtml5ParserEnabled' => true,
                'dpi'                  => 150,
            ]);

        $filename = 'CreditNote-' . str_replace('/', '-', $creditNote->credit_note_number) . '.pdf';

        return $pdf->download($filename);
    }

    // ── Workflow actions ──────────────────────────────────────────────────────

    public function submit(CreditNote $creditNote)
    {
        if (! $creditNote->canSubmit()) {
            return response()->json(['success' => false, 'message' => 'Credit note cannot be submitted.'], 422);
        }

        $old = $creditNote->getAttributes();

        // Check approval settings
        $approvalSetting = ApprovalSetting::getFor('credit_note', $creditNote->company_id);

        if ($approvalSetting && $approvalSetting->isAutoApproved()) {
            // Auto-approve: skip approval flow
            $creditNote->update([
                'status'       => 'approved',
                'submitted_by' => auth()->id(),
                'submitted_at' => now(),
                'approved_by'  => auth()->id(),
                'approved_at'  => now(),
                'current_approval_level' => 0,
                'max_approval_level'     => 0,
            ]);
            ActivityLogger::log('auto_approved', $creditNote, $old, $creditNote->getAttributes());
            return response()->json(['success' => true, 'message' => 'Credit note auto-approved.']);
        }

        if ($approvalSetting && $approvalSetting->isRequired()) {
            // Multi-level approval: set to submitted, create pending logs for level 1
            $levelsCount = $approvalSetting->levels_count;
            $creditNote->update([
                'status'                 => 'submitted',
                'submitted_by'           => auth()->id(),
                'submitted_at'           => now(),
                'current_approval_level' => 1,
                'max_approval_level'     => $levelsCount,
            ]);

            // Create pending approval logs for level 1 approvers
            $level1 = $approvalSetting->getLevel(1);
            if ($level1) {
                $this->createPendingLogsAndNotify($creditNote, $level1, 1);
            }

            ActivityLogger::log('submitted', $creditNote, $old, $creditNote->getAttributes());
            return response()->json(['success' => true, 'message' => 'Credit note submitted for approval (Level 1).']);
        }

        // No approval / default: simple submit → approved
        $creditNote->update([
            'status'       => 'approved',
            'submitted_by' => auth()->id(),
            'submitted_at' => now(),
            'approved_by'  => auth()->id(),
            'approved_at'  => now(),
        ]);
        ActivityLogger::log('submitted', $creditNote, $old, $creditNote->getAttributes());

        return response()->json(['success' => true, 'message' => 'Credit note approved (no approval required).']);
    }

    public function approve(CreditNote $creditNote)
    {
        if (! $creditNote->canApprove()) {
            return response()->json(['success' => false, 'message' => 'Credit note cannot be approved.'], 422);
        }

        $old = $creditNote->getAttributes();
        $creditNote->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        ActivityLogger::log('approved', $creditNote, $old, $creditNote->getAttributes());

        return response()->json(['success' => true, 'message' => 'Credit note approved successfully.']);
    }

    public function reject(Request $request, CreditNote $creditNote)
    {
        if (! $creditNote->canReject()) {
            return response()->json(['success' => false, 'message' => 'Credit note cannot be rejected.'], 422);
        }

        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $old = $creditNote->getAttributes();
        $creditNote->update([
            'status'           => 'rejected',
            'rejected_by'      => auth()->id(),
            'rejected_at'      => now(),
            'rejection_reason' => $request->reason,
        ]);
        ActivityLogger::log('rejected', $creditNote, $old, $creditNote->getAttributes());

        return response()->json(['success' => true, 'message' => 'Credit note rejected.']);
    }

    public function cancel(Request $request, CreditNote $creditNote)
    {
        if (! $creditNote->canCancel()) {
            return response()->json(['success' => false, 'message' => 'Credit note cannot be cancelled.'], 422);
        }

        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $old = $creditNote->getAttributes();
        $creditNote->update([
            'status'        => 'cancelled',
            'cancelled_by'  => auth()->id(),
            'cancelled_at'  => now(),
            'cancel_reason' => $request->reason,
        ]);
        ActivityLogger::log('cancelled', $creditNote, $old, $creditNote->getAttributes());

        return response()->json(['success' => true, 'message' => 'Credit note cancelled.']);
    }

    // ── Multi-level approval actions ──────────────────────────────────────────

    public function levelApprove(Request $request, CreditNote $creditNote)
    {
        $request->validate(['remarks' => ['nullable', 'string', 'max:500']]);

        if ($creditNote->status !== 'submitted') {
            return response()->json(['success' => false, 'message' => 'Credit note is not pending approval.'], 422);
        }

        $userId = auth()->id();
        $currentLevel = $creditNote->current_approval_level;
        $approvalSetting = ApprovalSetting::getFor('credit_note', $creditNote->company_id);

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
        $existingLog = ApprovalLog::where('document_type', 'credit_note')
            ->where('document_id', $creditNote->id)
            ->where('level', $currentLevel)
            ->where('user_id', $userId)
            ->whereIn('action', ['approved', 'rejected'])
            ->first();

        if ($existingLog) {
            return response()->json(['success' => false, 'message' => 'You have already acted on this level.'], 422);
        }

        // Update the pending log to approved
        ApprovalLog::where('document_type', 'credit_note')
            ->where('document_id', $creditNote->id)
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
            $pendingCount = ApprovalLog::where('document_type', 'credit_note')
                ->where('document_id', $creditNote->id)
                ->where('level', $currentLevel)
                ->where('action', 'pending')
                ->count();
            $levelComplete = ($pendingCount === 0);
        }

        if ($levelComplete) {
            $nextLevel = $currentLevel + 1;
            if ($nextLevel > $creditNote->max_approval_level) {
                // All levels complete — mark as approved
                $old = $creditNote->getAttributes();
                $creditNote->update([
                    'status'                 => 'approved',
                    'approved_by'            => $userId,
                    'approved_at'            => now(),
                    'current_approval_level' => $currentLevel,
                ]);
                ActivityLogger::log('approved', $creditNote, $old, $creditNote->getAttributes());
                return response()->json(['success' => true, 'message' => 'Credit note fully approved.']);
            } else {
                // Move to next level
                $creditNote->update(['current_approval_level' => $nextLevel]);

                // Create pending logs for next level
                $nextLevelConfig = $approvalSetting->getLevel($nextLevel);
                if ($nextLevelConfig) {
                    $this->createPendingLogsAndNotify($creditNote, $nextLevelConfig, $nextLevel);
                }

                return response()->json(['success' => true, 'message' => "Level {$currentLevel} approved. Moved to Level {$nextLevel}."]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Your approval recorded. Waiting for other approvers.']);
    }

    public function levelReject(Request $request, CreditNote $creditNote)
    {
        $request->validate(['remarks' => ['required', 'string', 'max:500']]);

        if ($creditNote->status !== 'submitted') {
            return response()->json(['success' => false, 'message' => 'Credit note is not pending approval.'], 422);
        }

        $userId = auth()->id();
        $currentLevel = $creditNote->current_approval_level;
        $approvalSetting = ApprovalSetting::getFor('credit_note', $creditNote->company_id);

        if (!$approvalSetting || !$approvalSetting->isRequired()) {
            return response()->json(['success' => false, 'message' => 'No approval settings configured.'], 422);
        }

        $levelConfig = $approvalSetting->getLevel($currentLevel);
        if (!$levelConfig || !in_array($userId, $levelConfig['approver_ids'] ?? [])) {
            return response()->json(['success' => false, 'message' => 'You are not an approver at this level.'], 422);
        }

        // Update log to rejected
        ApprovalLog::where('document_type', 'credit_note')
            ->where('document_id', $creditNote->id)
            ->where('level', $currentLevel)
            ->where('user_id', $userId)
            ->update([
                'action'   => 'rejected',
                'remarks'  => $request->remarks,
                'acted_at' => now(),
            ]);

        // Any rejection = immediate rejection of the whole credit note
        $old = $creditNote->getAttributes();
        $creditNote->update([
            'status'           => 'rejected',
            'rejected_by'      => $userId,
            'rejected_at'      => now(),
            'rejection_reason' => $request->remarks,
        ]);
        ActivityLogger::log('rejected', $creditNote, $old, $creditNote->getAttributes());

        return response()->json(['success' => true, 'message' => 'Credit note rejected.']);
    }

    public function approvalLogs(CreditNote $creditNote)
    {
        $logs = ApprovalLog::where('document_type', 'credit_note')
            ->where('document_id', $creditNote->id)
            ->with('user:id,name,email')
            ->orderBy('level')
            ->orderBy('created_at')
            ->get();

        $approvalSetting = ApprovalSetting::getFor('credit_note', $creditNote->company_id);

        return response()->json([
            'success'     => true,
            'logs'        => $logs,
            'credit_note' => [
                'id'                     => $creditNote->id,
                'status'                 => $creditNote->status,
                'current_approval_level' => $creditNote->current_approval_level,
                'max_approval_level'     => $creditNote->max_approval_level,
            ],
            'setting'     => $approvalSetting ? [
                'approval_mode' => $approvalSetting->approval_mode,
                'levels_count'  => $approvalSetting->levels_count,
                'levels'        => $approvalSetting->levels,
            ] : null,
        ]);
    }

    public function destroy(CreditNote $creditNote)
    {
        if (! $creditNote->isDraft()) {
            return response()->json(['success' => false, 'message' => 'Only draft credit notes can be deleted.'], 422);
        }

        $snapshot = $creditNote->getAttributes();
        $creditNote->delete();
        ActivityLogger::log('deleted', $creditNote, $snapshot, null);

        return response()->json(['success' => true, 'message' => 'Credit note deleted.']);
    }

    // ── API: next credit note number preview ─────────────────────────────────

    public function nextNumber()
    {
        $company = Company::getDefault();
        if (! $company) {
            return response()->json(['number' => 'CN/0001']);
        }

        $setting = \App\Models\NumberingSetting::where('company_id', $company->id)
            ->where('document_type', 'credit_note')
            ->first();

        return response()->json(['number' => $setting ? $setting->buildPreview() : 'CN/0001']);
    }

    // ── API: search customers ─────────────────────────────────────────────────

    public function searchCustomers(Request $request)
    {
        $query = $request->input('q', '');
        $company = Company::getDefault();

        // Get recently used customers from this company's credit notes
        $recentCustomerIds = CreditNote::where('company_id', $company?->id)
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

        // Get recently used products from this company's credit notes
        $recentProductIds = CreditNoteItem::whereHas('creditNote', function($q) use ($company) {
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
            'credit_note_date'    => ['required', 'date'],
            'party_id'            => ['required', 'exists:parties,id'],
            'billing_address'     => ['nullable', 'string', 'max:500'],
            'reference_number'    => ['nullable', 'string', 'max:100'],
            'place_of_supply'     => ['nullable', 'string', 'max:100'],
            'is_igst'             => ['nullable', 'boolean'],
            'reason'              => ['required', 'in:return,billing_error,discount,deficiency,other'],
            'reason_details'      => ['nullable', 'string', 'max:1000'],
            'sale_invoice_id'     => ['nullable', 'exists:sale_invoices,id'],
            'notes'               => ['nullable', 'string', 'max:1000'],
            'terms'               => ['nullable', 'string', 'max:1000'],
            'narration'           => ['nullable', 'string', 'max:1000'],
            'bill_discount_type'  => ['nullable', 'in:percentage,amount'],
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
        ];
    }

    private function row(CreditNote $cn): array
    {
        $badge = $cn->statusBadge();
        return [
            'id'                 => $cn->id,
            'credit_note_number' => $cn->credit_note_number,
            'credit_note_date'   => $cn->credit_note_date->format('d M Y'),
            'party_name'         => $cn->party?->name ?? '—',
            'party_gstin'        => $cn->party?->gstin ?? '',
            'grand_total'        => number_format((float) $cn->grand_total, 2),
            'reason'             => $cn->reason,
            'reason_label'       => $cn->reasonLabel(),
            'status'             => $cn->status,
            'status_label'       => $badge['label'],
            'status_class'       => $badge['class'],
            'can_edit'           => $cn->canEdit(),
            'can_submit'         => $cn->canSubmit(),
            'can_approve'        => $cn->canApprove(),
            'can_reject'         => $cn->canReject(),
            'can_cancel'         => $cn->canCancel(),
            'can_delete'         => $cn->isDraft(),
            'created_by'         => $cn->creator?->name ?? '—',
        ];
    }

    // ── Send approval notification emails ─────────────────────────────────────

    private function createPendingLogsAndNotify(CreditNote $creditNote, array $levelConfig, int $level): void
    {
        $creditNote->load('party', 'submitter');
        $requireSignature = $levelConfig['require_signature'] ?? false;

        foreach ($levelConfig['approver_ids'] ?? [] as $approverId) {
            $token = Str::random(64);

            $log = ApprovalLog::create([
                'document_type' => 'credit_note',
                'document_id'   => $creditNote->id,
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
                        Mail::to($approver->email)->send(new ApprovalRequestMail($creditNote, $approver, $log, $requireSignature));
                    } catch (\Exception $e) {
                        \Log::error("Failed to send approval email to {$approver->email}: " . $e->getMessage());
                    }
                }
            }
        }
    }
}
