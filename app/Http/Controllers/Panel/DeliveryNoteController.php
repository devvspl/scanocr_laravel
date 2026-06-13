<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Mail\ApprovalRequestMail;
use App\Models\ApprovalLog;
use App\Models\ApprovalSetting;
use App\Models\Company;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\Product;
use App\Models\User;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DeliveryNoteController extends Controller
{
    // ── List ─────────────────────────────────────────────────────────────────

    public function index()
    {
        $company = Company::getDefault();
        $fy      = $company
            ? FinancialYear::where('company_id', $company->id)->where('is_current', true)->first()
            : null;

        return view('panel.sales.delivery-notes', compact('company', 'fy'));
    }

    // ── DataTables JSON ───────────────────────────────────────────────────────

    public function data(Request $request)
    {
        $company = Company::getDefault();
        $query   = DeliveryNote::with('party', 'creator')
            ->where('company_id', $company?->id);

        // Search
        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(fn($q) => $q
                ->where('delivery_number', 'like', "%{$search}%")
                ->orWhere('transporter_name', 'like', "%{$search}%")
                ->orWhereHas('party', fn($p) => $p->where('name', 'like', "%{$search}%"))
            );
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('dispatch_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('dispatch_date', '<=', $request->date_to);
        }

        $total    = $query->count();
        $start    = (int) $request->get('start', 0);
        $length   = (int) $request->get('length', 15);
        $order    = $request->input('order', [['column' => 0, 'dir' => 'desc']]);
        $cols     = ['delivery_number', 'dispatch_date', 'party_name', 'total_packages', 'status', 'created_by'];
        $col      = $cols[(int)($order[0]['column'] ?? 0)] ?? 'dispatch_date';
        $dir      = ($order[0]['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if ($col === 'party_name') {
            $query->join('parties', 'parties.id', '=', 'delivery_notes.party_id')
                  ->orderBy('parties.name', $dir)
                  ->select('delivery_notes.*');
        } else {
            $sortable = ['delivery_number', 'dispatch_date', 'total_packages', 'status'];
            $query->orderBy(in_array($col, $sortable) ? $col : 'dispatch_date', $dir);
        }

        $filtered = $query->count();
        $rows     = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows->map(fn($dn) => $this->row($dn)),
        ]);
    }

    // ── Create form ───────────────────────────────────────────────────────────

    public function create()
    {
        $company   = Company::getDefault();
        $fy        = $company
            ? FinancialYear::where('company_id', $company->id)->where('is_current', true)->first()
            : null;
        $customers = Party::customers()->where('is_active', true)->orderBy('name')
            ->get(['id', 'name', 'display_name', 'gstin', 'billing_address', 'shipping_address', 'state', 'phone', 'mobile']);
        $products  = Product::where('is_active', true)->with('unit')->orderBy('name')
            ->get(['id', 'code', 'name', 'description', 'hsn_sac', 'unit_id']);

        // Generate preview number (not committed yet)
        $nextNumber = $company ? DeliveryNote::generateNumber($company->id) : 'DN/0001';
        if ($company) {
            \App\Models\NumberingSetting::where('company_id', $company->id)
                ->where('document_type', 'delivery_note')
                ->decrement('next_number');
        }

        return view('panel.sales.delivery-form', compact(
            'company', 'fy', 'customers', 'products', 'nextNumber'
        ));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $data  = $this->validateHeader($request);
        $items = $this->validateItems($request);

        $delivery = null;

        DB::transaction(function () use ($data, $items, &$delivery) {
            $company = Company::getDefault();

            $data['company_id']        = $company->id;
            $data['financial_year_id'] = FinancialYear::where('company_id', $company->id)
                ->where('is_current', true)->value('id');
            $data['delivery_number']   = DeliveryNote::generateNumber($company->id);
            $data['created_by']        = auth()->id();
            $data['status']            = 'draft';

            $delivery = DeliveryNote::create($data);

            foreach ($items as $i => $item) {
                $delivery->items()->create(array_merge($item, ['sort_order' => $i]));
            }

            ActivityLogger::log('created', $delivery, null, $delivery->getAttributes());
        });

        return response()->json([
            'success'  => true,
            'message'  => 'Delivery Note saved as draft.',
            'id'       => $delivery->id,
            'redirect' => route('sales.delivery.edit', $delivery),
        ]);
    }

    // ── Edit form ─────────────────────────────────────────────────────────────

    public function edit(DeliveryNote $delivery)
    {
        if (! $delivery->canEdit()) {
            return redirect()->route('sales.delivery')
                ->with('error', 'This delivery note cannot be edited in its current status.');
        }

        $company   = Company::getDefault();
        $fy        = FinancialYear::find($delivery->financial_year_id);
        $customers = Party::customers()->where('is_active', true)->orderBy('name')
            ->get(['id', 'name', 'display_name', 'gstin', 'billing_address', 'shipping_address', 'state', 'phone', 'mobile']);
        $products  = Product::where('is_active', true)->with('unit')->orderBy('name')
            ->get(['id', 'code', 'name', 'description', 'hsn_sac', 'unit_id']);
        $nextNumber = $delivery->delivery_number;

        return view('panel.sales.delivery-form', compact(
            'delivery', 'company', 'fy', 'customers', 'products', 'nextNumber'
        ));
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(Request $request, DeliveryNote $delivery)
    {
        if (! $delivery->canEdit()) {
            return response()->json(['success' => false, 'message' => 'Cannot be edited in current status.'], 422);
        }

        $data  = $this->validateHeader($request);
        $items = $this->validateItems($request);

        DB::transaction(function () use ($delivery, $data, $items) {
            $old = $delivery->getAttributes();
            $delivery->update($data);
            $delivery->items()->delete();

            foreach ($items as $i => $item) {
                $delivery->items()->create(array_merge($item, ['sort_order' => $i]));
            }

            ActivityLogger::log('updated', $delivery, $old, $delivery->getAttributes());
        });

        return response()->json([
            'success' => true,
            'message' => 'Delivery Note updated successfully.',
            'id'      => $delivery->id,
            'stay'    => true,
        ]);
    }

    // ── Show (view-only) ──────────────────────────────────────────────────────

    public function show(DeliveryNote $delivery)
    {
        $delivery->load('party', 'items.product', 'creator', 'approver', 'submitter', 'rejecter', 'company');
        return view('panel.sales.delivery-show', compact('delivery'));
    }

    // ── PDF download ──────────────────────────────────────────────────────────

    public function pdf(Request $request, DeliveryNote $delivery)
    {
        $delivery->load('party', 'items.product', 'creator', 'company');

        $pdf = Pdf::loadView('panel.sales.delivery-pdf', compact('delivery'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'          => 'DejaVu Sans',
                'isRemoteEnabled'      => false,
                'isHtml5ParserEnabled' => true,
                'dpi'                  => 150,
            ]);

        $filename = 'DeliveryNote-' . str_replace('/', '-', $delivery->delivery_number) . '.pdf';
        return $pdf->download($filename);
    }

    // ── Workflow: Submit ───────────────────────────────────────────────────────

    public function submit(DeliveryNote $delivery)
    {
        if (! $delivery->canSubmit()) {
            return response()->json(['success' => false, 'message' => 'Cannot be submitted.'], 422);
        }

        $old = $delivery->getAttributes();
        $approvalSetting = ApprovalSetting::getFor('delivery_note', $delivery->company_id);

        if ($approvalSetting && $approvalSetting->isAutoApproved()) {
            $delivery->update([
                'status'       => 'approved',
                'submitted_by' => auth()->id(),
                'submitted_at' => now(),
                'approved_by'  => auth()->id(),
                'approved_at'  => now(),
            ]);
            ActivityLogger::log('auto_approved', $delivery, $old, $delivery->getAttributes());
            return response()->json(['success' => true, 'message' => 'Delivery Note auto-approved (dispatched).']);
        }

        if ($approvalSetting && $approvalSetting->isRequired()) {
            $levelsCount = $approvalSetting->levels_count;
            $delivery->update([
                'status'                 => 'submitted',
                'submitted_by'           => auth()->id(),
                'submitted_at'           => now(),
                'current_approval_level' => 1,
                'max_approval_level'     => $levelsCount,
            ]);

            $level1 = $approvalSetting->getLevel(1);
            if ($level1) {
                $this->createPendingLogsAndNotify($delivery, $level1, 1);
            }

            ActivityLogger::log('submitted', $delivery, $old, $delivery->getAttributes());
            return response()->json(['success' => true, 'message' => 'Submitted for approval (Level 1).']);
        }

        // No approval configured — auto approve
        $delivery->update([
            'status'       => 'approved',
            'submitted_by' => auth()->id(),
            'submitted_at' => now(),
            'approved_by'  => auth()->id(),
            'approved_at'  => now(),
        ]);
        ActivityLogger::log('submitted', $delivery, $old, $delivery->getAttributes());

        return response()->json(['success' => true, 'message' => 'Delivery Note dispatched.']);
    }

    // ── Workflow: Approve ──────────────────────────────────────────────────────

    public function approve(DeliveryNote $delivery)
    {
        if (! $delivery->canApprove()) {
            return response()->json(['success' => false, 'message' => 'Cannot be approved.'], 422);
        }

        $old = $delivery->getAttributes();
        $delivery->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        ActivityLogger::log('approved', $delivery, $old, $delivery->getAttributes());

        return response()->json(['success' => true, 'message' => 'Delivery Note approved.']);
    }

    // ── Workflow: Reject ───────────────────────────────────────────────────────

    public function reject(Request $request, DeliveryNote $delivery)
    {
        if (! $delivery->canReject()) {
            return response()->json(['success' => false, 'message' => 'Cannot be rejected.'], 422);
        }

        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $old = $delivery->getAttributes();
        $delivery->update([
            'status'           => 'rejected',
            'rejected_by'      => auth()->id(),
            'rejected_at'      => now(),
            'rejection_reason' => $request->reason,
        ]);
        ActivityLogger::log('rejected', $delivery, $old, $delivery->getAttributes());

        return response()->json(['success' => true, 'message' => 'Delivery Note rejected.']);
    }

    // ── Workflow: Cancel ───────────────────────────────────────────────────────

    public function cancel(Request $request, DeliveryNote $delivery)
    {
        if (! $delivery->canCancel()) {
            return response()->json(['success' => false, 'message' => 'Cannot be cancelled.'], 422);
        }

        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $old = $delivery->getAttributes();
        $delivery->update([
            'status'        => 'cancelled',
            'cancelled_by'  => auth()->id(),
            'cancelled_at'  => now(),
            'cancel_reason' => $request->reason,
        ]);
        ActivityLogger::log('cancelled', $delivery, $old, $delivery->getAttributes());

        return response()->json(['success' => true, 'message' => 'Delivery Note cancelled.']);
    }

    // ── Workflow: Mark Delivered (sign-off) ────────────────────────────────────

    public function markDelivered(Request $request, DeliveryNote $delivery)
    {
        if (! $delivery->canMarkDelivered()) {
            return response()->json(['success' => false, 'message' => 'Cannot mark as delivered.'], 422);
        }

        $request->validate([
            'received_by'      => ['nullable', 'string', 'max:200'],
            'receiver_remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $old = $delivery->getAttributes();
        $delivery->update([
            'status'           => 'delivered',
            'received_by'      => $request->received_by,
            'received_at'      => now(),
            'receiver_remarks' => $request->receiver_remarks,
        ]);
        ActivityLogger::log('delivered', $delivery, $old, $delivery->getAttributes());

        return response()->json(['success' => true, 'message' => 'Delivery Note marked as delivered.']);
    }

    // ── Multi-level approval: Level Approve ───────────────────────────────────

    public function levelApprove(Request $request, DeliveryNote $delivery)
    {
        $request->validate(['remarks' => ['nullable', 'string', 'max:500']]);

        if ($delivery->status !== 'submitted') {
            return response()->json(['success' => false, 'message' => 'Not pending approval.'], 422);
        }

        $userId = auth()->id();
        $currentLevel = $delivery->current_approval_level;
        $approvalSetting = ApprovalSetting::getFor('delivery_note', $delivery->company_id);

        if (!$approvalSetting || !$approvalSetting->isRequired()) {
            return response()->json(['success' => false, 'message' => 'No approval settings.'], 422);
        }

        $levelConfig = $approvalSetting->getLevel($currentLevel);
        if (!$levelConfig) {
            return response()->json(['success' => false, 'message' => 'Invalid level.'], 422);
        }

        if (!in_array($userId, $levelConfig['approver_ids'] ?? [])) {
            return response()->json(['success' => false, 'message' => 'Not an approver at this level.'], 422);
        }

        $existingLog = ApprovalLog::where('document_type', 'delivery_note')
            ->where('document_id', $delivery->id)
            ->where('level', $currentLevel)
            ->where('user_id', $userId)
            ->whereIn('action', ['approved', 'rejected'])
            ->first();

        if ($existingLog) {
            return response()->json(['success' => false, 'message' => 'Already acted on this level.'], 422);
        }

        ApprovalLog::where('document_type', 'delivery_note')
            ->where('document_id', $delivery->id)
            ->where('level', $currentLevel)
            ->where('user_id', $userId)
            ->update(['action' => 'approved', 'remarks' => $request->remarks, 'acted_at' => now()]);

        $approvalType = $levelConfig['approval_type'] ?? 'any_one';
        $levelComplete = false;

        if ($approvalType === 'any_one') {
            $levelComplete = true;
        } else {
            $pendingCount = ApprovalLog::where('document_type', 'delivery_note')
                ->where('document_id', $delivery->id)
                ->where('level', $currentLevel)
                ->where('action', 'pending')
                ->count();
            $levelComplete = ($pendingCount === 0);
        }

        if ($levelComplete) {
            $nextLevel = $currentLevel + 1;
            if ($nextLevel > $delivery->max_approval_level) {
                $old = $delivery->getAttributes();
                $delivery->update([
                    'status'      => 'approved',
                    'approved_by' => $userId,
                    'approved_at' => now(),
                    'current_approval_level' => $currentLevel,
                ]);
                ActivityLogger::log('approved', $delivery, $old, $delivery->getAttributes());
                return response()->json(['success' => true, 'message' => 'Fully approved — dispatched.']);
            } else {
                $delivery->update(['current_approval_level' => $nextLevel]);
                $nextLevelConfig = $approvalSetting->getLevel($nextLevel);
                if ($nextLevelConfig) {
                    $this->createPendingLogsAndNotify($delivery, $nextLevelConfig, $nextLevel);
                }
                return response()->json(['success' => true, 'message' => "Level {$currentLevel} approved. Moved to Level {$nextLevel}."]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Approval recorded. Waiting for others.']);
    }

    // ── Multi-level approval: Level Reject ────────────────────────────────────

    public function levelReject(Request $request, DeliveryNote $delivery)
    {
        $request->validate(['remarks' => ['required', 'string', 'max:500']]);

        if ($delivery->status !== 'submitted') {
            return response()->json(['success' => false, 'message' => 'Not pending approval.'], 422);
        }

        $userId = auth()->id();
        $currentLevel = $delivery->current_approval_level;
        $approvalSetting = ApprovalSetting::getFor('delivery_note', $delivery->company_id);

        if (!$approvalSetting || !$approvalSetting->isRequired()) {
            return response()->json(['success' => false, 'message' => 'No approval settings.'], 422);
        }

        $levelConfig = $approvalSetting->getLevel($currentLevel);
        if (!$levelConfig || !in_array($userId, $levelConfig['approver_ids'] ?? [])) {
            return response()->json(['success' => false, 'message' => 'Not an approver at this level.'], 422);
        }

        ApprovalLog::where('document_type', 'delivery_note')
            ->where('document_id', $delivery->id)
            ->where('level', $currentLevel)
            ->where('user_id', $userId)
            ->update(['action' => 'rejected', 'remarks' => $request->remarks, 'acted_at' => now()]);

        $old = $delivery->getAttributes();
        $delivery->update([
            'status'           => 'rejected',
            'rejected_by'      => $userId,
            'rejected_at'      => now(),
            'rejection_reason' => $request->remarks,
        ]);
        ActivityLogger::log('rejected', $delivery, $old, $delivery->getAttributes());

        return response()->json(['success' => true, 'message' => 'Delivery Note rejected.']);
    }

    // ── Approval Logs ─────────────────────────────────────────────────────────

    public function approvalLogs(DeliveryNote $delivery)
    {
        $logs = ApprovalLog::where('document_type', 'delivery_note')
            ->where('document_id', $delivery->id)
            ->with('user:id,name,email')
            ->orderBy('level')->orderBy('created_at')
            ->get();

        $approvalSetting = ApprovalSetting::getFor('delivery_note', $delivery->company_id);

        return response()->json([
            'success' => true,
            'logs'    => $logs,
            'invoice' => [
                'id'                     => $delivery->id,
                'status'                 => $delivery->status,
                'current_approval_level' => $delivery->current_approval_level,
                'max_approval_level'     => $delivery->max_approval_level,
            ],
            'setting' => $approvalSetting ? [
                'approval_mode' => $approvalSetting->approval_mode,
                'levels_count'  => $approvalSetting->levels_count,
                'levels'        => $approvalSetting->levels,
            ] : null,
        ]);
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function destroy(DeliveryNote $delivery)
    {
        if (! $delivery->isDraft()) {
            return response()->json(['success' => false, 'message' => 'Only drafts can be deleted.'], 422);
        }

        $snapshot = $delivery->getAttributes();
        $delivery->delete();
        ActivityLogger::log('deleted', $delivery, $snapshot, null);

        return response()->json(['success' => true, 'message' => 'Delivery Note deleted.']);
    }

    // ── API: next number preview ──────────────────────────────────────────────

    public function nextNumber()
    {
        $company = Company::getDefault();
        if (! $company) {
            return response()->json(['number' => 'DN/0001']);
        }

        $setting = \App\Models\NumberingSetting::where('company_id', $company->id)
            ->where('document_type', 'delivery_note')
            ->first();

        return response()->json(['number' => $setting ? $setting->buildPreview() : 'DN/0001']);
    }

    // ── API: search customers ─────────────────────────────────────────────────

    public function searchCustomers(Request $request)
    {
        $query   = $request->input('q', '');
        $company = Company::getDefault();

        $customers = Party::customers()
            ->where('is_active', true)
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
            ->limit(20)
            ->get(['id', 'name', 'display_name', 'gstin', 'phone', 'mobile', 'billing_address', 'shipping_address', 'state', 'city']);

        $results = $customers->map(fn($c) => [
            'id'               => $c->id,
            'name'             => $c->display_name ?? $c->name,
            'gstin'            => $c->gstin ?? '',
            'phone'            => $c->phone ?? $c->mobile ?? '',
            'city'             => $c->city ?? '',
            'state'            => $c->state ?? '',
            'shipping_address' => $c->shipping_address ?? '',
        ]);

        return response()->json(['suggestions' => $results->values()]);
    }

    // ── API: search transporters (vendors) ────────────────────────────────────

    public function searchTransporters(Request $request)
    {
        $query = $request->input('q', '');

        $vendors = Party::vendors()
            ->where('is_active', true)
            ->where(function($q) use ($query) {
                if ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('display_name', 'like', "%{$query}%")
                      ->orWhere('phone', 'like', "%{$query}%")
                      ->orWhere('mobile', 'like', "%{$query}%");
                }
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'display_name', 'phone', 'mobile', 'city', 'state', 'gstin']);

        $results = $vendors->map(fn($v) => [
            'id'    => $v->id,
            'name'  => $v->display_name ?? $v->name,
            'phone' => $v->phone ?? $v->mobile ?? '',
            'city'  => $v->city ?? '',
            'state' => $v->state ?? '',
            'gstin' => $v->gstin ?? '',
        ]);

        return response()->json(['suggestions' => $results->values()]);
    }

    // ── API: search products ──────────────────────────────────────────────────

    public function searchProducts(Request $request)
    {
        $query = $request->input('q', '');

        $products = Product::where('is_active', true)
            ->with('unit')
            ->where(function($q) use ($query) {
                if ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('code', 'like', "%{$query}%")
                      ->orWhere('hsn_sac', 'like', "%{$query}%");
                }
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'code', 'name', 'description', 'hsn_sac', 'unit_id']);

        $results = $products->map(fn($p) => [
            'id'          => $p->id,
            'code'        => $p->code ?? '',
            'name'        => $p->name,
            'description' => $p->description ?? $p->name,
            'hsn_sac'     => $p->hsn_sac ?? '',
            'unit'        => $p->unit?->symbol ?? '',
        ]);

        return response()->json(['suggestions' => $results->values()]);
    }

    // ── API: search invoices/proformas to pick from ───────────────────────────

    public function searchInvoices(Request $request)
    {
        $query   = $request->input('q', '');
        $company = Company::getDefault();

        $invoices = \App\Models\SaleInvoice::where('company_id', $company?->id)
            ->where('status', 'approved')
            ->with('party', 'items.product')
            ->where(function($q) use ($query) {
                if ($query) {
                    $q->where('invoice_number', 'like', "%{$query}%")
                      ->orWhereHas('party', fn($p) => $p->where('name', 'like', "%{$query}%"));
                }
            })
            ->orderByDesc('invoice_date')
            ->limit(15)
            ->get();

        $proformas = \App\Models\ProformaInvoice::where('company_id', $company?->id)
            ->where('status', 'approved')
            ->with('party', 'items.product')
            ->where(function($q) use ($query) {
                if ($query) {
                    $q->where('proforma_number', 'like', "%{$query}%")
                      ->orWhereHas('party', fn($p) => $p->where('name', 'like', "%{$query}%"));
                }
            })
            ->orderByDesc('proforma_date')
            ->limit(10)
            ->get();

        $results = collect();

        foreach ($invoices as $inv) {
            $results->push([
                'id'          => $inv->id,
                'type'        => 'invoice',
                'number'      => $inv->invoice_number,
                'date'        => $inv->invoice_date->format('d M Y'),
                'party_id'    => $inv->party_id,
                'party_name'  => $inv->party->display_name ?? $inv->party->name,
                'party_phone' => $inv->party->phone ?? $inv->party->mobile ?? '',
                'party_address' => $inv->party->shipping_address ?? $inv->party->billing_address ?? '',
                'grand_total' => number_format((float)$inv->grand_total, 2),
                'items'       => $inv->items->map(fn($i) => [
                    'product_id'   => $i->product_id,
                    'description'  => $i->description,
                    'product_code' => $i->product?->code ?? '',
                    'hsn_sac'      => $i->hsn_sac ?? '',
                    'qty'          => (float)$i->qty,
                    'unit'         => $i->unit ?? '',
                    'unit_price'   => (float)$i->unit_price,
                    'tax_rate'     => (float)$i->tax_rate,
                ])->toArray(),
            ]);
        }

        foreach ($proformas as $pro) {
            $results->push([
                'id'          => $pro->id,
                'type'        => 'proforma',
                'number'      => $pro->proforma_number,
                'date'        => $pro->proforma_date->format('d M Y'),
                'party_id'    => $pro->party_id,
                'party_name'  => $pro->party->display_name ?? $pro->party->name,
                'party_phone' => $pro->party->phone ?? $pro->party->mobile ?? '',
                'party_address' => $pro->party->shipping_address ?? $pro->party->billing_address ?? '',
                'grand_total' => number_format((float)$pro->grand_total, 2),
                'items'       => $pro->items->map(fn($i) => [
                    'product_id'   => $i->product_id,
                    'description'  => $i->description,
                    'product_code' => $i->product?->code ?? '',
                    'hsn_sac'      => $i->hsn_sac ?? '',
                    'qty'          => (float)$i->qty,
                    'unit'         => $i->unit ?? '',
                    'unit_price'   => (float)$i->unit_price,
                    'tax_rate'     => (float)$i->tax_rate,
                ])->toArray(),
            ]);
        }

        return response()->json(['results' => $results->values()]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validateHeader(Request $request): array
    {
        return $request->validate([
            'dispatch_date'      => ['required', 'date'],
            'party_id'           => ['required', 'exists:parties,id'],
            'receiver_name'      => ['nullable', 'string', 'max:200'],
            'receiver_phone'     => ['nullable', 'string', 'max:30'],
            'delivery_address'   => ['required', 'string', 'max:1000'],
            'order_number'       => ['nullable', 'string', 'max:100'],
            'transport_mode'     => ['required', 'string', 'in:Road,Rail,Air,Sea,Courier,Hand Delivery'],
            'transporter_name'   => ['required', 'string', 'max:200'],
            'vehicle_number'     => ['nullable', 'string', 'max:50'],
            'driver_name'        => ['nullable', 'string', 'max:100'],
            'driver_phone'       => ['nullable', 'string', 'max:30'],
            'tracking_number'    => ['nullable', 'string', 'max:100'],
            'total_packages'     => ['nullable', 'integer', 'min:0'],
            'total_weight'       => ['nullable', 'string', 'max:50'],
            'notes'              => ['nullable', 'string', 'max:1000'],
            'narration'          => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function validateItems(Request $request): array
    {
        $request->validate([
            'items'               => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.qty'         => ['required', 'numeric', 'min:0.001'],
            'items.*.product_id'  => ['nullable', 'exists:products,id'],
            'items.*.product_code'=> ['nullable', 'string', 'max:50'],
            'items.*.hsn_sac'     => ['nullable', 'string', 'max:20'],
            'items.*.unit'        => ['nullable', 'string', 'max:20'],
            'items.*.weight'      => ['nullable', 'string', 'max:50'],
            'items.*.remarks'     => ['nullable', 'string', 'max:500'],
        ]);

        return $request->input('items');
    }

    private function row(DeliveryNote $dn): array
    {
        $badge = $dn->statusBadge();
        return [
            'id'              => $dn->id,
            'delivery_number' => $dn->delivery_number,
            'dispatch_date'   => $dn->dispatch_date->format('d M Y'),
            'party_name'      => $dn->party?->display_name ?? $dn->party?->name ?? '—',
            'total_packages'  => $dn->total_packages ?? '—',
            'total_weight'    => $dn->total_weight ?? '—',
            'transporter'     => $dn->transporter_name ?? '—',
            'status'          => $dn->status,
            'status_label'    => $badge['label'],
            'status_class'    => $badge['class'],
            'can_edit'        => $dn->canEdit(),
            'can_submit'      => $dn->canSubmit(),
            'can_delete'      => $dn->isDraft(),
            'created_by'      => $dn->creator?->name ?? '—',
        ];
    }

    // ── Send approval notification emails ─────────────────────────────────────

    private function createPendingLogsAndNotify(DeliveryNote $delivery, array $levelConfig, int $level): void
    {
        $delivery->load('party', 'submitter');
        $requireSignature = $levelConfig['require_signature'] ?? false;

        foreach ($levelConfig['approver_ids'] ?? [] as $approverId) {
            $token = Str::random(64);

            $log = ApprovalLog::create([
                'document_type' => 'delivery_note',
                'document_id'   => $delivery->id,
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
                        Mail::to($approver->email)->send(new ApprovalRequestMail($delivery, $approver, $log, $requireSignature));
                    } catch (\Exception $e) {
                        \Log::error("Failed to send approval email to {$approver->email}: " . $e->getMessage());
                    }
                }
            }
        }
    }
}
