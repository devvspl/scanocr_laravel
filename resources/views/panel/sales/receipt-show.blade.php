@extends('layouts.app')
@section('title', 'Receipt #' . $receipt->receipt_number)
@section('page-title', 'Receipt #' . $receipt->receipt_number)
@section('breadcrumb')
    <a href="{{ route('sales.receipts') }}" class="hover:text-stone-600">Sales</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('sales.receipts') }}" class="hover:text-stone-600">Receipts</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-stone-600">{{ $receipt->receipt_number }}</span>
@endsection
@section('content')
<div x-data="receiptShow()" x-init="init()">
    <div x-show="toast.show" x-cloak x-transition
        :class="toast.type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'"
        class="fixed bottom-5 right-5 z-50 px-4 py-3 rounded-xl border text-xs font-semibold flex items-center gap-2 shadow-lg">
        <span x-text="toast.message"></span>
    </div>

    {{-- ── ACTION BAR ── --}}
    <div class="bg-white border border-stone-200 rounded-xl mb-3 px-4 py-2.5 flex items-center justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-2.5">
            @php $badge = $receipt->statusBadge(); @endphp
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $badge['class'] }}">{{ $badge['label'] }}</span>
            <span class="text-[11px] text-stone-400">Created {{ $receipt->created_at->format('d M Y, h:i A') }} by {{ $receipt->creator->name ?? 'System' }}</span>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('sales.receipts') }}" class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-stone-600 border border-stone-200 rounded-lg hover:bg-stone-50 transition-colors"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg> Back</a>
            <a href="/sales/receipts/{{ $receipt->id }}/pdf" title="Download PDF" class="inline-flex items-center justify-center h-8 w-8 text-stone-600 border border-stone-200 rounded-lg hover:bg-red-50 hover:text-red-700 hover:border-red-200 transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></a>
            @if($receipt->canEdit())
            <a href="{{ route('sales.receipts.edit', $receipt) }}" class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-stone-600 border border-stone-200 rounded-lg hover:bg-stone-50 transition-colors"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg> Edit</a>
            @endif
            @if($receipt->canSubmit())<button @click="submit()" class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-white rounded-lg transition-colors" style="background:#2563eb;">Submit</button>@endif
            @if($receipt->canApprove())<button @click="approve()" class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-white rounded-lg transition-colors" style="background:#059669;">Approve</button>@endif
            @if($receipt->canReject())<button @click="openReject()" class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-white rounded-lg transition-colors" style="background:#dc2626;">Reject</button>@endif
            @if($receipt->canCancel())<button @click="openCancel()" class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-stone-700 border border-stone-200 rounded-lg hover:bg-stone-50 transition-colors">Cancel</button>@endif
        </div>
    </div>

    {{-- ── RECEIPT CARD ── --}}
    <div class="bg-white border border-stone-200 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100">
            <div class="grid grid-cols-2 gap-6 mb-5">
                <div>
                    <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-1.5">From</p>
                    <p class="text-sm font-bold text-stone-800">{{ $receipt->company->name }}</p>
                    @if($receipt->company->address_line1)
                        <p class="text-xs text-stone-600 mt-1">{{ $receipt->company->address_line1 }}</p>
                        @if($receipt->company->address_line2)<p class="text-xs text-stone-600">{{ $receipt->company->address_line2 }}</p>@endif
                        <p class="text-xs text-stone-600">{{ implode(', ', array_filter([$receipt->company->city, $receipt->company->state, $receipt->company->pincode])) }}</p>
                    @endif
                    @if($receipt->company->gstin)<p class="text-[10px] text-stone-400 mt-1 font-mono">GSTIN: {{ $receipt->company->gstin }}</p>@endif
                </div>
                <div>
                    <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-1.5">Received From</p>
                    <p class="text-sm font-bold text-stone-800">{{ $receipt->party->display_name ?? $receipt->party->name }}</p>
                    @if($receipt->party->billing_address)<p class="text-xs text-stone-600 mt-1 whitespace-pre-line">{{ $receipt->party->billing_address }}</p>@endif
                    @if($receipt->party->gstin)<p class="text-[10px] text-stone-400 mt-1 font-mono">GSTIN: {{ $receipt->party->gstin }}</p>@endif
                </div>
            </div>
            <div class="flex flex-wrap gap-6 pt-4 border-t border-stone-100">
                <div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Receipt #</p><p class="text-sm text-stone-800 mt-0.5 font-mono">{{ $receipt->receipt_number }}</p></div>
                <div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Date</p><p class="text-sm text-stone-800 mt-0.5">{{ $receipt->receipt_date->format('d M Y') }}</p></div>
                <div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Amount</p><p class="text-sm font-bold text-red-700 mt-0.5">₹{{ number_format((float)$receipt->amount, 2) }}</p></div>
                <div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Payment Method</p><p class="text-sm text-stone-800 mt-0.5">{{ $receipt->paymentMethodLabel() }}</p></div>
                @if($receipt->payment_reference)<div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Reference</p><p class="text-sm text-stone-800 mt-0.5 font-mono">{{ $receipt->payment_reference }}</p></div>@endif
                @if($receipt->payment_date)<div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Payment Date</p><p class="text-sm text-stone-800 mt-0.5">{{ $receipt->payment_date->format('d M Y') }}</p></div>@endif
                @if($receipt->bank_name)<div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Bank</p><p class="text-sm text-stone-800 mt-0.5">{{ $receipt->bank_name }}</p></div>@endif
                @if($receipt->bank_account)<div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Account</p><p class="text-sm text-stone-800 mt-0.5 font-mono">{{ $receipt->bank_account }}</p></div>@endif
                @if($receipt->saleInvoice)
                <div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Against Invoice</p><p class="text-sm mt-0.5"><a href="/sales/invoices/{{ $receipt->saleInvoice->id }}" class="text-red-700 hover:underline font-mono">{{ $receipt->saleInvoice->invoice_number }}</a></p></div>
                @endif
            </div>
        </div>

        @if($receipt->description || $receipt->narration)
        <div class="px-5 py-4 border-b border-stone-100">
            <div class="grid grid-cols-2 gap-6">
                @if($receipt->description)<div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-1">Description</p><p class="text-xs text-stone-600 whitespace-pre-line">{{ $receipt->description }}</p></div>@endif
                @if($receipt->narration)<div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-1">Narration</p><p class="text-xs text-stone-600 whitespace-pre-line">{{ $receipt->narration }}</p></div>@endif
            </div>
        </div>
        @endif

        @php $approvalLogs = \App\Models\ApprovalLog::where('document_type', 'receipt')->where('document_id', $receipt->id)->whereNotNull('signature_path')->where('action', 'approved')->with('user')->orderBy('level')->get(); @endphp
        @include('panel.sales._digital-signatures', ['approvalLogs' => $approvalLogs])

        <div class="px-5 py-4 border-t border-stone-100">
            <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-2">Workflow History</p>
            <div class="space-y-1 text-xs">
                <div class="flex gap-2"><span class="text-stone-400 w-20">Created</span><span class="text-stone-700">{{ $receipt->created_at->format('d M Y, h:i A') }} by {{ $receipt->creator->name ?? '—' }}</span></div>
                @if($receipt->submitted_at)<div class="flex gap-2"><span class="text-stone-400 w-20">Submitted</span><span class="text-stone-700">{{ $receipt->submitted_at->format('d M Y, h:i A') }} by {{ $receipt->submitter->name ?? '—' }}</span></div>@endif
                @if($receipt->approved_at)<div class="flex gap-2"><span class="text-stone-400 w-20">Approved</span><span class="text-stone-700">{{ $receipt->approved_at->format('d M Y, h:i A') }} by {{ $receipt->approver->name ?? '—' }}</span></div>@endif
                @if($receipt->rejected_at)<div class="flex gap-2"><span class="text-stone-400 w-20">Rejected</span><span class="text-stone-700">{{ $receipt->rejected_at->format('d M Y, h:i A') }}@if($receipt->rejection_reason) — {{ $receipt->rejection_reason }}@endif</span></div>@endif
                @if($receipt->cancelled_at)<div class="flex gap-2"><span class="text-stone-400 w-20">Cancelled</span><span class="text-stone-700">{{ $receipt->cancelled_at->format('d M Y, h:i A') }}@if($receipt->cancel_reason) — {{ $receipt->cancel_reason }}@endif</span></div>@endif
            </div>
        </div>
    </div>

    {{-- Reject Modal --}}
    <div x-show="rejectModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="rejectModal.open = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <h3 class="text-sm font-semibold text-stone-800 mb-4">Reject Receipt</h3>
            <label class="block text-xs font-semibold text-stone-600 mb-1.5">Reason</label>
            <textarea x-model="rejectModal.reason" rows="3" placeholder="Reason for rejection..." class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 resize-none"></textarea>
            <div class="flex items-center justify-end gap-2 mt-4">
                <button @click="rejectModal.open = false" class="px-3 py-2 text-xs font-semibold text-stone-600 border border-stone-200 rounded-lg hover:bg-stone-50">Back</button>
                <button @click="confirmReject()" class="px-4 py-2 text-xs font-semibold text-white rounded-lg" style="background:#dc2626;">Reject</button>
            </div>
        </div>
    </div>

    {{-- Cancel Modal --}}
    <div x-show="cancelModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="cancelModal.open = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <h3 class="text-sm font-semibold text-stone-800 mb-4">Cancel Receipt</h3>
            <label class="block text-xs font-semibold text-stone-600 mb-1.5">Reason (optional)</label>
            <textarea x-model="cancelModal.reason" rows="3" placeholder="Reason for cancellation..." class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 resize-none"></textarea>
            <div class="flex items-center justify-end gap-2 mt-4">
                <button @click="cancelModal.open = false" class="px-3 py-2 text-xs font-semibold text-stone-600 border border-stone-200 rounded-lg hover:bg-stone-50">Back</button>
                <button @click="confirmCancel()" class="px-4 py-2 text-xs font-semibold text-white rounded-lg" style="background:#d97706;">Cancel Receipt</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function receiptShow() {
    return {
        toast: { show: false, message: '', type: 'success' },
        rejectModal: { open: false, reason: '' },
        cancelModal: { open: false, reason: '' },
        init() {},
        showToast(msg, type = 'success') { this.toast = { show: true, message: msg, type }; setTimeout(() => { this.toast.show = false; }, 3500); },
        async submit() {
            if (!confirm('Submit this receipt for approval?')) return;
            const r = await fetch(`/sales/receipts/{{ $receipt->id }}/submit`, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' } });
            const j = await r.json(); j.success ? (this.showToast(j.message), setTimeout(() => location.reload(), 1500)) : this.showToast(j.message, 'error');
        },
        async approve() {
            if (!confirm('Approve this receipt?')) return;
            const r = await fetch(`/sales/receipts/{{ $receipt->id }}/approve`, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' } });
            const j = await r.json(); j.success ? (this.showToast(j.message), setTimeout(() => location.reload(), 1500)) : this.showToast(j.message, 'error');
        },
        openReject() { this.rejectModal = { open: true, reason: '' }; },
        async confirmReject() {
            const r = await fetch(`/sales/receipts/{{ $receipt->id }}/reject`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }, body: JSON.stringify({ reason: this.rejectModal.reason }) });
            const j = await r.json(); this.rejectModal.open = false; j.success ? (this.showToast(j.message), setTimeout(() => location.reload(), 1500)) : this.showToast(j.message, 'error');
        },
        openCancel() { this.cancelModal = { open: true, reason: '' }; },
        async confirmCancel() {
            const r = await fetch(`/sales/receipts/{{ $receipt->id }}/cancel`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }, body: JSON.stringify({ reason: this.cancelModal.reason }) });
            const j = await r.json(); this.cancelModal.open = false; j.success ? (this.showToast(j.message), setTimeout(() => location.reload(), 1500)) : this.showToast(j.message, 'error');
        },
    };
}
</script>
@endpush
