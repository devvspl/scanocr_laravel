<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Mail\ApprovalRequestMail;
use App\Models\ApprovalLog;
use App\Models\ApprovalSetting;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\Receipt;
use App\Models\SaleInvoice;
use App\Models\User;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ReceiptController extends Controller
{
    public function index()
    {
        $company = Company::getDefault();
        $fy = $company ? FinancialYear::where('company_id', $company->id)->where('is_current', true)->first() : null;
        return view('panel.sales.receipts', compact('company', 'fy'));
    }

    public function data(Request $request)
    {
        $company = Company::getDefault();
        $query = Receipt::with('party', 'creator', 'saleInvoice')->where('company_id', $company?->id);

        $search = $request->input('search.value', '');
        if ($search !== '') {
            $query->where(fn($q) => $q->where('receipt_number', 'like', "%{$search}%")
                ->orWhereHas('party', fn($p) => $p->where('name', 'like', "%{$search}%")));
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('date_from')) $query->whereDate('receipt_date', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('receipt_date', '<=', $request->date_to);

        $total = $query->count();
        $start = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 15);
        $query->orderByDesc('receipt_date');
        $filtered = $query->count();
        $rows = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $rows->map(fn($r) => $this->row($r)),
        ]);
    }

    public function create()
    {
        $company = Company::getDefault();
        $fy = $company ? FinancialYear::where('company_id', $company->id)->where('is_current', true)->first() : null;
        $customers = Party::customers()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'display_name', 'gstin', 'phone', 'mobile', 'billing_address', 'state', 'city']);

        $nextNumber = $company ? Receipt::generateNumber($company->id) : 'RCP/0001';
        if ($company) {
            \App\Models\NumberingSetting::where('company_id', $company->id)->where('document_type', 'receipt')->decrement('next_number');
        }

        // Get outstanding invoices for linking
        $outstandingInvoices = SaleInvoice::where('company_id', $company?->id)
            ->where('status', 'approved')
            ->where('amount_due', '>', 0)
            ->with('party:id,name,display_name')
            ->orderByDesc('invoice_date')
            ->limit(50)
            ->get(['id', 'invoice_number', 'invoice_date', 'party_id', 'grand_total', 'amount_due']);

        return view('panel.sales.receipt-form', compact('company', 'fy', 'customers', 'nextNumber', 'outstandingInvoices'));
    }

    public function store(Request $request)
    {
        $data = $this->validateReceipt($request);
        $receipt = null;

        DB::transaction(function () use ($data, &$receipt) {
            $company = Company::getDefault();
            $data['company_id'] = $company->id;
            $data['financial_year_id'] = FinancialYear::where('company_id', $company->id)->where('is_current', true)->value('id');
            $data['receipt_number'] = Receipt::generateNumber($company->id);
            $data['created_by'] = auth()->id();
            $data['status'] = 'draft';

            $receipt = Receipt::create($data);
            ActivityLogger::log('created', $receipt, null, $receipt->getAttributes());
        });

        return response()->json(['success' => true, 'message' => 'Receipt saved as draft.', 'id' => $receipt->id, 'redirect' => route('sales.receipts.edit', $receipt)]);
    }

    public function edit(Receipt $receipt)
    {
        if (!$receipt->canEdit()) return redirect()->route('sales.receipts')->with('error', 'Cannot edit.');
        $company = Company::getDefault();
        $fy = FinancialYear::find($receipt->financial_year_id);
        $customers = Party::customers()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'display_name', 'gstin', 'phone', 'mobile', 'billing_address', 'state', 'city']);
        $nextNumber = $receipt->receipt_number;
        $outstandingInvoices = SaleInvoice::where('company_id', $company?->id)->where('status', 'approved')->where('amount_due', '>', 0)->with('party:id,name,display_name')->orderByDesc('invoice_date')->limit(50)->get(['id', 'invoice_number', 'invoice_date', 'party_id', 'grand_total', 'amount_due']);

        return view('panel.sales.receipt-form', compact('receipt', 'company', 'fy', 'customers', 'nextNumber', 'outstandingInvoices'));
    }

    public function update(Request $request, Receipt $receipt)
    {
        if (!$receipt->canEdit()) return response()->json(['success' => false, 'message' => 'Cannot edit.'], 422);
        $data = $this->validateReceipt($request);
        $old = $receipt->getAttributes();
        $receipt->update($data);
        ActivityLogger::log('updated', $receipt, $old, $receipt->getAttributes());
        return response()->json(['success' => true, 'message' => 'Receipt updated.', 'id' => $receipt->id, 'stay' => true]);
    }

    public function show(Receipt $receipt)
    {
        $receipt->load('party', 'saleInvoice', 'creator', 'approver', 'submitter', 'rejecter', 'company');
        return view('panel.sales.receipt-show', compact('receipt'));
    }

    public function pdf(Receipt $receipt)
    {
        $receipt->load('party', 'saleInvoice', 'creator', 'company');
        $pdf = Pdf::loadView('panel.sales.receipt-pdf', compact('receipt'))->setPaper('a4', 'portrait')->setOptions(['defaultFont' => 'DejaVu Sans', 'isHtml5ParserEnabled' => true, 'dpi' => 150]);
        return $pdf->download('Receipt-' . str_replace('/', '-', $receipt->receipt_number) . '.pdf');
    }

    public function submit(Receipt $receipt)
    {
        if (!$receipt->canSubmit()) return response()->json(['success' => false, 'message' => 'Cannot submit.'], 422);
        $old = $receipt->getAttributes();
        $approvalSetting = ApprovalSetting::getFor('receipt', $receipt->company_id);

        if ($approvalSetting && $approvalSetting->isAutoApproved()) {
            $receipt->update(['status' => 'approved', 'submitted_by' => auth()->id(), 'submitted_at' => now(), 'approved_by' => auth()->id(), 'approved_at' => now()]);
            $this->applyPaymentToInvoice($receipt);
            ActivityLogger::log('auto_approved', $receipt, $old, $receipt->getAttributes());
            return response()->json(['success' => true, 'message' => 'Receipt auto-approved.']);
        }

        if ($approvalSetting && $approvalSetting->isRequired()) {
            $levelsCount = $approvalSetting->levels_count;
            $receipt->update(['status' => 'submitted', 'submitted_by' => auth()->id(), 'submitted_at' => now(), 'current_approval_level' => 1, 'max_approval_level' => $levelsCount]);
            $level1 = $approvalSetting->getLevel(1);
            if ($level1) $this->createPendingLogsAndNotify($receipt, $level1, 1);
            ActivityLogger::log('submitted', $receipt, $old, $receipt->getAttributes());
            return response()->json(['success' => true, 'message' => 'Receipt submitted for approval.']);
        }

        $receipt->update(['status' => 'approved', 'submitted_by' => auth()->id(), 'submitted_at' => now(), 'approved_by' => auth()->id(), 'approved_at' => now()]);
        $this->applyPaymentToInvoice($receipt);
        ActivityLogger::log('submitted', $receipt, $old, $receipt->getAttributes());
        return response()->json(['success' => true, 'message' => 'Receipt approved.']);
    }

    public function approve(Receipt $receipt)
    {
        if (!$receipt->canApprove()) return response()->json(['success' => false, 'message' => 'Cannot approve.'], 422);
        $old = $receipt->getAttributes();
        $receipt->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        $this->applyPaymentToInvoice($receipt);
        ActivityLogger::log('approved', $receipt, $old, $receipt->getAttributes());
        return response()->json(['success' => true, 'message' => 'Receipt approved.']);
    }

    public function reject(Request $request, Receipt $receipt)
    {
        if (!$receipt->canReject()) return response()->json(['success' => false, 'message' => 'Cannot reject.'], 422);
        $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $old = $receipt->getAttributes();
        $receipt->update(['status' => 'rejected', 'rejected_by' => auth()->id(), 'rejected_at' => now(), 'rejection_reason' => $request->reason]);
        ActivityLogger::log('rejected', $receipt, $old, $receipt->getAttributes());
        return response()->json(['success' => true, 'message' => 'Receipt rejected.']);
    }

    public function cancel(Request $request, Receipt $receipt)
    {
        if (!$receipt->canCancel()) return response()->json(['success' => false, 'message' => 'Cannot cancel.'], 422);
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $old = $receipt->getAttributes();
        // Reverse payment if was approved
        if ($receipt->isApproved()) $this->reversePaymentFromInvoice($receipt);
        $receipt->update(['status' => 'cancelled', 'cancelled_by' => auth()->id(), 'cancelled_at' => now(), 'cancel_reason' => $request->reason]);
        ActivityLogger::log('cancelled', $receipt, $old, $receipt->getAttributes());
        return response()->json(['success' => true, 'message' => 'Receipt cancelled.']);
    }

    public function destroy(Receipt $receipt)
    {
        if (!$receipt->isDraft()) return response()->json(['success' => false, 'message' => 'Only drafts can be deleted.'], 422);
        $snapshot = $receipt->getAttributes();
        $receipt->delete();
        ActivityLogger::log('deleted', $receipt, $snapshot, null);
        return response()->json(['success' => true, 'message' => 'Receipt deleted.']);
    }

    public function nextNumber()
    {
        $company = Company::getDefault();
        if (!$company) return response()->json(['number' => 'RCP/0001']);
        $setting = \App\Models\NumberingSetting::where('company_id', $company->id)->where('document_type', 'receipt')->first();
        return response()->json(['number' => $setting ? $setting->buildPreview() : 'RCP/0001']);
    }

    public function searchCustomers(Request $request)
    {
        $query = $request->input('q', '');
        $customers = Party::customers()->where('is_active', true)
            ->where(function($q) use ($query) { if ($query) { $q->where('name', 'like', "%{$query}%")->orWhere('display_name', 'like', "%{$query}%")->orWhere('phone', 'like', "%{$query}%"); } })
            ->orderBy('name')->limit(20)->get(['id', 'name', 'display_name', 'gstin', 'phone', 'mobile', 'city', 'state']);
        return response()->json(['suggestions' => $customers->map(fn($c) => ['id' => $c->id, 'name' => $c->display_name ?? $c->name, 'gstin' => $c->gstin ?? '', 'phone' => $c->phone ?? $c->mobile ?? '', 'city' => $c->city ?? '', 'state' => $c->state ?? ''])->values()]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validateReceipt(Request $request): array
    {
        return $request->validate([
            'receipt_date'      => ['required', 'date'],
            'party_id'          => ['required', 'exists:parties,id'],
            'amount'            => ['required', 'numeric', 'min:0.01'],
            'payment_method'    => ['required', 'in:cash,bank_transfer,cheque,upi,card,other'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'payment_date'      => ['nullable', 'date'],
            'bank_name'         => ['nullable', 'string', 'max:100'],
            'bank_account'      => ['nullable', 'string', 'max:50'],
            'sale_invoice_id'   => ['nullable', 'exists:sale_invoices,id'],
            'description'       => ['nullable', 'string', 'max:1000'],
            'narration'         => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function applyPaymentToInvoice(Receipt $receipt): void
    {
        if ($receipt->sale_invoice_id) {
            $invoice = SaleInvoice::find($receipt->sale_invoice_id);
            if ($invoice) {
                // Calculate new paid amount (advance + receipt payments)
                $newPaid = (float)$invoice->advance_amount + (float)$invoice->amount_paid + (float)$receipt->amount;
                // Ensure amount_paid includes only non-advance payments
                $totalPaid = (float)$invoice->amount_paid + (float)$receipt->amount;
                $invoice->update([
                    'amount_paid' => min($totalPaid, (float)$invoice->grand_total - (float)$invoice->advance_amount),
                    'amount_due'  => max((float)$invoice->grand_total - ((float)$invoice->advance_amount + $totalPaid), 0),
                ]);
            }
        }
    }

    private function reversePaymentFromInvoice(Receipt $receipt): void
    {
        if ($receipt->sale_invoice_id) {
            $invoice = SaleInvoice::find($receipt->sale_invoice_id);
            if ($invoice) {
                $newPaid = max((float)$invoice->amount_paid - (float)$receipt->amount, 0);
                $invoice->update([
                    'amount_paid' => $newPaid,
                    'amount_due'  => (float)$invoice->grand_total - ((float)$invoice->advance_amount + $newPaid),
                ]);
            }
        }
    }

    private function row(Receipt $r): array
    {
        $badge = $r->statusBadge();
        return [
            'id'              => $r->id,
            'receipt_number'  => $r->receipt_number,
            'receipt_date'    => $r->receipt_date->format('d M Y'),
            'party_name'      => $r->party?->display_name ?? $r->party?->name ?? '—',
            'amount'          => number_format((float)$r->amount, 2),
            'payment_method'  => $r->paymentMethodLabel(),
            'invoice_number'  => $r->saleInvoice?->invoice_number ?? '—',
            'status'          => $r->status,
            'status_label'    => $badge['label'],
            'status_class'    => $badge['class'],
            'can_edit'        => $r->canEdit(),
            'can_submit'      => $r->canSubmit(),
            'can_delete'      => $r->isDraft(),
            'created_by'      => $r->creator?->name ?? '—',
        ];
    }

    private function createPendingLogsAndNotify(Receipt $receipt, array $levelConfig, int $level): void
    {
        $receipt->load('party', 'submitter');
        $requireSignature = $levelConfig['require_signature'] ?? false;

        foreach ($levelConfig['approver_ids'] ?? [] as $approverId) {
            $token = Str::random(64);
            $log = ApprovalLog::create(['document_type' => 'receipt', 'document_id' => $receipt->id, 'level' => $level, 'level_name' => $levelConfig['name'] ?? "Level {$level}", 'user_id' => $approverId, 'action' => 'pending', 'token' => $token]);
            $notifyVia = $levelConfig['notify_via'] ?? 'email';
            if (in_array($notifyVia, ['email', 'both'])) {
                $approver = User::find($approverId);
                if ($approver && $approver->email) {
                    try { Mail::to($approver->email)->send(new ApprovalRequestMail($receipt, $approver, $log, $requireSignature)); } catch (\Exception $e) { \Log::error("Failed: {$e->getMessage()}"); }
                }
            }
        }
    }
}
