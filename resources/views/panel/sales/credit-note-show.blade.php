@extends('layouts.app')
@section('title', 'Credit Note #' . $creditNote->credit_note_number)
@section('page-title', 'Credit Note #' . $creditNote->credit_note_number)
@section('breadcrumb')
    <a href="{{ route('sales.credit-notes') }}" class="hover:text-stone-600">Sales</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('sales.credit-notes') }}" class="hover:text-stone-600">Credit Notes</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-stone-600">{{ $creditNote->credit_note_number }}</span>
@endsection
@section('content')
<div x-data="creditNoteShow()" x-init="init()">
    <div x-show="toast.show" x-cloak x-transition :class="toast.type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'" class="fixed bottom-5 right-5 z-50 px-4 py-3 rounded-xl border text-xs font-semibold flex items-center gap-2 shadow-lg"><span x-text="toast.message"></span></div>

    {{-- ── ACTION BAR ── --}}
    <div class="bg-white border border-stone-200 rounded-xl mb-3 px-4 py-2.5 flex items-center justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-2.5">
            @php $badge = $creditNote->statusBadge(); @endphp
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $badge['class'] }}">{{ $badge['label'] }}</span>
            <span class="text-[11px] text-stone-400">Created {{ $creditNote->created_at->format('d M Y, h:i A') }} by {{ $creditNote->creator->name ?? 'System' }}</span>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('sales.credit-notes') }}" class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-stone-600 border border-stone-200 rounded-lg hover:bg-stone-50 transition-colors"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg> Back</a>
            <a href="/sales/credit-notes/{{ $creditNote->id }}/pdf" title="Download PDF" class="inline-flex items-center justify-center h-8 w-8 text-stone-600 border border-stone-200 rounded-lg hover:bg-red-50 hover:text-red-700 hover:border-red-200 transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></a>
            @if($creditNote->canEdit())
            <a href="{{ route('sales.credit-notes.edit', $creditNote) }}" class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-stone-600 border border-stone-200 rounded-lg hover:bg-stone-50 transition-colors"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg> Edit</a>
            @endif
            @if($creditNote->canSubmit())<button @click="submit()" class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-white rounded-lg transition-colors" style="background:#2563eb;">Submit</button>@endif
            @if($creditNote->canApprove())<button @click="approve()" class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-white rounded-lg transition-colors" style="background:#059669;">Approve</button>@endif
            @if($creditNote->canReject())<button @click="openReject()" class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-white rounded-lg transition-colors" style="background:#dc2626;">Reject</button>@endif
            @if($creditNote->canCancel())<button @click="openCancel()" class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-stone-700 border border-stone-200 rounded-lg hover:bg-stone-50 transition-colors">Cancel</button>@endif
        </div>
    </div>

    {{-- ── CREDIT NOTE CARD ── --}}
    <div class="bg-white border border-stone-200 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100">
            <div class="grid grid-cols-2 gap-6 mb-5">
                <div>
                    <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-1.5">From</p>
                    <p class="text-sm font-bold text-stone-800">{{ $creditNote->company->name }}</p>
                    @if($creditNote->company->address_line1)
                        <p class="text-xs text-stone-600 mt-1">{{ $creditNote->company->address_line1 }}</p>
                        @if($creditNote->company->address_line2)<p class="text-xs text-stone-600">{{ $creditNote->company->address_line2 }}</p>@endif
                        <p class="text-xs text-stone-600">{{ implode(', ', array_filter([$creditNote->company->city, $creditNote->company->state, $creditNote->company->pincode])) }}</p>
                    @endif
                    @if($creditNote->company->gstin)<p class="text-[10px] text-stone-400 mt-1 font-mono">GSTIN: {{ $creditNote->company->gstin }}</p>@endif
                </div>
                <div>
                    <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-1.5">Bill To</p>
                    <p class="text-sm font-bold text-stone-800">{{ $creditNote->party->display_name ?? $creditNote->party->name }}</p>
                    @if($creditNote->billing_address)<p class="text-xs text-stone-600 mt-1 whitespace-pre-line">{{ $creditNote->billing_address }}</p>@endif
                    @if($creditNote->party->gstin)<p class="text-[10px] text-stone-400 mt-1 font-mono">GSTIN: {{ $creditNote->party->gstin }}</p>@endif
                </div>
            </div>
            <div class="flex flex-wrap gap-6 pt-4 border-t border-stone-100">
                <div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Credit Note #</p><p class="text-sm text-stone-800 mt-0.5 font-mono">{{ $creditNote->credit_note_number }}</p></div>
                <div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Date</p><p class="text-sm text-stone-800 mt-0.5">{{ $creditNote->credit_note_date->format('d M Y') }}</p></div>
                @if($creditNote->reference_number)<div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Reference</p><p class="text-sm text-stone-800 mt-0.5">{{ $creditNote->reference_number }}</p></div>@endif
                <div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Reason</p><p class="text-sm text-stone-800 mt-0.5">{{ $creditNote->reasonLabel() }}</p></div>
                @if($creditNote->place_of_supply)<div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Place of Supply</p><p class="text-sm text-stone-800 mt-0.5">{{ $creditNote->place_of_supply }}</p></div>@endif
                <div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Tax Type</p><p class="text-sm text-stone-800 mt-0.5">{{ $creditNote->is_igst ? 'IGST' : 'CGST + SGST' }}</p></div>
            </div>
        </div>

        {{-- Line Items --}}
        <div class="overflow-x-auto"><table class="w-full text-xs" style="table-layout:fixed;">
                <colgroup><col style="width:36px;"><col><col style="width:80px;"><col style="width:64px;"><col style="width:52px;"><col style="width:90px;"><col style="width:90px;"><col style="width:80px;"><col style="width:90px;"><col style="width:64px;"><col style="width:100px;"></colgroup>
                <thead style="background:#f5f5f4;border-bottom:1px solid #e7e5e4;"><tr>
                    <th class="py-2 px-2 text-left text-[10px] font-semibold text-stone-500 uppercase tracking-wide">#</th>
                    <th class="py-2 px-2 text-left text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Description</th>
                    <th class="py-2 px-2 text-left text-[10px] font-semibold text-stone-500 uppercase tracking-wide">HSN/SAC</th>
                    <th class="py-2 px-2 text-right text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Qty</th>
                    <th class="py-2 px-2 text-left text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Unit</th>
                    <th class="py-2 px-2 text-right text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Rate (₹)</th>
                    <th class="py-2 px-2 text-right text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Amount (₹)</th>
                    <th class="py-2 px-2 text-right text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Disc (₹)</th>
                    <th class="py-2 px-2 text-right text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Taxable (₹)</th>
                    <th class="py-2 px-2 text-right text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Tax %</th>
                    <th class="py-2 px-2 text-right text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Total (₹)</th>
                </tr></thead>
                <tbody>
                    @foreach($creditNote->items as $idx => $item)
                    <tr style="border-bottom:1px solid #f5f5f4;">
                        <td class="py-2.5 px-2 text-[10px] text-stone-400 font-mono">{{ $idx + 1 }}</td>
                        <td class="py-2.5 px-2"><p class="font-medium text-stone-800 text-xs">{{ $item->description }}</p></td>
                        <td class="py-2.5 px-2 text-[10px] text-stone-500 font-mono">{{ $item->hsn_sac ?: '—' }}</td>
                        <td class="py-2.5 px-2 text-right text-xs font-semibold text-stone-800">{{ rtrim(rtrim(number_format((float)$item->qty, 3), '0'), '.') }}</td>
                        <td class="py-2.5 px-2 text-xs text-stone-600">{{ $item->unit ?: '—' }}</td>
                        <td class="py-2.5 px-2 text-right text-xs text-stone-700">{{ number_format((float)$item->unit_price, 2) }}</td>
                        <td class="py-2.5 px-2 text-right text-xs text-stone-700">{{ number_format((float)$item->qty * (float)$item->unit_price, 2) }}</td>
                        <td class="py-2.5 px-2 text-right text-xs text-stone-500">{{ $item->discount_amount > 0 ? number_format((float)$item->discount_amount, 2) : '—' }}</td>
                        <td class="py-2.5 px-2 text-right text-xs font-semibold text-stone-800">{{ number_format((float)$item->taxable_amount, 2) }}</td>
                        <td class="py-2.5 px-2 text-right text-xs text-stone-500">@if($creditNote->is_igst){{ number_format((float)$item->igst_rate, 2) }}%@else{{ number_format((float)$item->cgst_rate + (float)$item->sgst_rate, 2) }}%@endif</td>
                        <td class="py-2.5 px-2 text-right text-xs font-bold text-stone-800">{{ number_format((float)$item->line_total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table></div>

        {{-- Totals --}}
        <div class="px-5 py-4 border-t border-stone-100 flex justify-end">
            <div class="w-64 space-y-1.5">
                <div class="flex justify-between text-xs"><span class="text-stone-500">Subtotal</span><span class="text-stone-800 font-semibold">₹{{ number_format((float)$creditNote->subtotal, 2) }}</span></div>
                @if($creditNote->discount_amount > 0)<div class="flex justify-between text-xs"><span class="text-stone-500">Discount</span><span class="text-red-600">−₹{{ number_format((float)$creditNote->discount_amount, 2) }}</span></div>@endif
                <div class="flex justify-between text-xs"><span class="text-stone-500">Taxable Amount</span><span class="text-stone-800">₹{{ number_format((float)$creditNote->taxable_amount, 2) }}</span></div>
                @if($creditNote->is_igst)
                <div class="flex justify-between text-xs"><span class="text-stone-500">IGST</span><span class="text-stone-800">₹{{ number_format((float)$creditNote->igst_amount, 2) }}</span></div>
                @else
                <div class="flex justify-between text-xs"><span class="text-stone-500">CGST</span><span class="text-stone-800">₹{{ number_format((float)$creditNote->cgst_amount, 2) }}</span></div>
                <div class="flex justify-between text-xs"><span class="text-stone-500">SGST</span><span class="text-stone-800">₹{{ number_format((float)$creditNote->sgst_amount, 2) }}</span></div>
                @endif
                <div class="flex justify-between text-sm pt-2 border-t border-stone-200"><span class="font-bold text-stone-800">Grand Total</span><span class="font-bold text-stone-800">₹{{ number_format((float)$creditNote->grand_total, 2) }}</span></div>
            </div>
        </div>

        {{-- Notes / Terms / Narration --}}
        @if($creditNote->notes || $creditNote->terms || $creditNote->narration)
        <div class="px-5 py-4 border-t border-stone-100">
            <div class="grid grid-cols-3 gap-6">
                @if($creditNote->notes)<div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-1">Notes</p><p class="text-xs text-stone-600 whitespace-pre-line">{{ $creditNote->notes }}</p></div>@endif
                @if($creditNote->terms)<div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-1">Terms</p><p class="text-xs text-stone-600 whitespace-pre-line">{{ $creditNote->terms }}</p></div>@endif
                @if($creditNote->narration)<div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-1">Narration</p><p class="text-xs text-stone-600 whitespace-pre-line">{{ $creditNote->narration }}</p></div>@endif
            </div>
        </div>
        @endif

        {{-- Digital Signatures --}}
        @php $approvalLogs = \App\Models\ApprovalLog::where('document_type', 'credit_note')->where('document_id', $creditNote->id)->whereNotNull('signature_path')->where('action', 'approved')->with('user')->orderBy('level')->get(); @endphp
        @include('panel.sales._digital-signatures', ['approvalLogs' => $approvalLogs])

        {{-- Workflow History --}}
        <div class="px-5 py-4 border-t border-stone-100">
            <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-2">Workflow History</p>
            <div class="space-y-1 text-xs">
                <div class="flex gap-2"><span class="text-stone-400 w-20">Created</span><span class="text-stone-700">{{ $creditNote->created_at->format('d M Y, h:i A') }} by {{ $creditNote->creator->name ?? '—' }}</span></div>
                @if($creditNote->submitted_at)<div class="flex gap-2"><span class="text-stone-400 w-20">Submitted</span><span class="text-stone-700">{{ $creditNote->submitted_at->format('d M Y, h:i A') }} by {{ $creditNote->submitter->name ?? '—' }}</span></div>@endif
                @if($creditNote->approved_at)<div class="flex gap-2"><span class="text-stone-400 w-20">Approved</span><span class="text-stone-700">{{ $creditNote->approved_at->format('d M Y, h:i A') }} by {{ $creditNote->approver->name ?? '—' }}</span></div>@endif
                @if($creditNote->rejected_at)<div class="flex gap-2"><span class="text-stone-400 w-20">Rejected</span><span class="text-stone-700">{{ $creditNote->rejected_at->format('d M Y, h:i A') }}@if($creditNote->rejection_reason) — {{ $creditNote->rejection_reason }}@endif</span></div>@endif
                @if($creditNote->cancelled_at)<div class="flex gap-2"><span class="text-stone-400 w-20">Cancelled</span><span class="text-stone-700">{{ $creditNote->cancelled_at->format('d M Y, h:i A') }}@if($creditNote->cancel_reason) — {{ $creditNote->cancel_reason }}@endif</span></div>@endif
            </div>
        </div>
    </div>

    {{-- Reject Modal --}}
    <div x-show="rejectModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="rejectModal.open = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <h3 class="text-sm font-semibold text-stone-800 mb-4">Reject Credit Note</h3>
            <label class="block text-xs font-semibold text-stone-600 mb-1.5">Reason</label>
            <textarea x-model="rejectModal.reason" rows="3" placeholder="Reason for rejection..." class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 resize-none"></textarea>
            <div class="flex items-center justify-end gap-2 mt-4"><button @click="rejectModal.open = false" class="px-3 py-2 text-xs font-semibold text-stone-600 border border-stone-200 rounded-lg hover:bg-stone-50">Back</button><button @click="confirmReject()" class="px-4 py-2 text-xs font-semibold text-white rounded-lg" style="background:#dc2626;">Reject</button></div>
        </div>
    </div>
    {{-- Cancel Modal --}}
    <div x-show="cancelModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="cancelModal.open = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <h3 class="text-sm font-semibold text-stone-800 mb-4">Cancel Credit Note</h3>
            <label class="block text-xs font-semibold text-stone-600 mb-1.5">Reason (optional)</label>
            <textarea x-model="cancelModal.reason" rows="3" placeholder="Reason for cancellation..." class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 resize-none"></textarea>
            <div class="flex items-center justify-end gap-2 mt-4"><button @click="cancelModal.open = false" class="px-3 py-2 text-xs font-semibold text-stone-600 border border-stone-200 rounded-lg hover:bg-stone-50">Back</button><button @click="confirmCancel()" class="px-4 py-2 text-xs font-semibold text-white rounded-lg" style="background:#d97706;">Cancel Credit Note</button></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function creditNoteShow() {
    return {
        toast: { show: false, message: '', type: 'success' },
        rejectModal: { open: false, reason: '' },
        cancelModal: { open: false, reason: '' },
        init() {},
        showToast(msg, type = 'success') { this.toast = { show: true, message: msg, type }; setTimeout(() => { this.toast.show = false; }, 3500); },
        async submit() {
            if (!confirm('Submit this credit note for approval?')) return;
            const r = await fetch(`/sales/credit-notes/{{ $creditNote->id }}/submit`, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' } });
            const j = await r.json(); j.success ? (this.showToast(j.message), setTimeout(() => location.reload(), 1500)) : this.showToast(j.message, 'error');
        },
        async approve() {
            if (!confirm('Approve this credit note?')) return;
            const r = await fetch(`/sales/credit-notes/{{ $creditNote->id }}/approve`, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' } });
            const j = await r.json(); j.success ? (this.showToast(j.message), setTimeout(() => location.reload(), 1500)) : this.showToast(j.message, 'error');
        },
        openReject() { this.rejectModal = { open: true, reason: '' }; },
        async confirmReject() {
            const r = await fetch(`/sales/credit-notes/{{ $creditNote->id }}/reject`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }, body: JSON.stringify({ reason: this.rejectModal.reason }) });
            const j = await r.json(); this.rejectModal.open = false; j.success ? (this.showToast(j.message), setTimeout(() => location.reload(), 1500)) : this.showToast(j.message, 'error');
        },
        openCancel() { this.cancelModal = { open: true, reason: '' }; },
        async confirmCancel() {
            const r = await fetch(`/sales/credit-notes/{{ $creditNote->id }}/cancel`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }, body: JSON.stringify({ reason: this.cancelModal.reason }) });
            const j = await r.json(); this.cancelModal.open = false; j.success ? (this.showToast(j.message), setTimeout(() => location.reload(), 1500)) : this.showToast(j.message, 'error');
        },
    };
}
</script>
@endpush
