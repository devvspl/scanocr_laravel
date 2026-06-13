@extends('layouts.app')

@section('title', 'Delivery Note #' . $delivery->delivery_number)
@section('page-title', 'Delivery Note #' . $delivery->delivery_number)

@section('breadcrumb')
    <a href="{{ route('sales.delivery') }}" class="hover:text-stone-600">Sales</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('sales.delivery') }}" class="hover:text-stone-600">Delivery Notes</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-stone-600">{{ $delivery->delivery_number }}</span>
@endsection

@section('content')
<div x-data="deliveryShow()" x-init="init()">

    <div x-show="toast.show" x-cloak x-transition
         :class="toast.type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'"
         class="fixed bottom-5 right-5 z-50 px-4 py-3 rounded-xl border text-xs font-semibold flex items-center gap-2 shadow-lg">
        <span x-text="toast.message"></span>
    </div>

    {{-- ── ACTION BAR ── --}}
    <div class="bg-white border border-stone-200 rounded-xl mb-3 px-4 py-2.5 flex items-center justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-2.5">
            @php $badge = $delivery->statusBadge(); @endphp
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $badge['class'] }}">{{ $badge['label'] }}</span>
            <span class="text-[11px] text-stone-400">Created {{ $delivery->created_at->format('d M Y, h:i A') }} by {{ $delivery->creator->name ?? 'System' }}</span>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('sales.delivery') }}" class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-stone-600 border border-stone-200 rounded-lg hover:bg-stone-50 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg> Back
            </a>

            <a href="{{ route('sales.delivery.pdf', $delivery) }}" title="Download PDF" class="inline-flex items-center justify-center h-8 w-8 text-stone-600 border border-stone-200 rounded-lg hover:bg-red-50 hover:text-red-700 hover:border-red-200 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            </a>

            @if($delivery->canEdit())
            <a href="{{ route('sales.delivery.edit', $delivery) }}" class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-stone-600 border border-stone-200 rounded-lg hover:bg-stone-50 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg> Edit
            </a>
            @endif

            @if($delivery->canSubmit())
            <button @click="submit()" class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-white rounded-lg transition-colors" style="background:#2563eb;">Submit / Dispatch</button>
            @endif

            @if($delivery->canMarkDelivered())
            <button @click="openDelivered()" class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-white rounded-lg transition-colors" style="background:#059669;">Mark Delivered</button>
            @endif

            @if($delivery->canCancel())
            <button @click="openCancel()" class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-stone-700 border border-stone-200 rounded-lg hover:bg-stone-50 transition-colors">Cancel</button>
            @endif
        </div>
    </div>

    {{-- ── DELIVERY NOTE CARD ── --}}
    <div class="bg-white border border-stone-200 rounded-xl overflow-hidden">

        {{-- Company & Customer --}}
        <div class="px-5 py-4 border-b border-stone-100">
            <div class="grid grid-cols-2 gap-6 mb-5">
                <div>
                    <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-1.5">From (Sender)</p>
                    <p class="text-sm font-bold text-stone-800">{{ $delivery->company->name }}</p>
                    @if($delivery->company->address_line1)
                        <p class="text-xs text-stone-600 mt-1">{{ $delivery->company->address_line1 }}</p>
                        @if($delivery->company->address_line2)<p class="text-xs text-stone-600">{{ $delivery->company->address_line2 }}</p>@endif
                        <p class="text-xs text-stone-600">{{ implode(', ', array_filter([$delivery->company->city, $delivery->company->state, $delivery->company->pincode])) }}</p>
                    @endif
                    @if($delivery->company->phone)<p class="text-[10px] text-stone-400 mt-1">Phone: {{ $delivery->company->phone }}</p>@endif
                    @if($delivery->company->gstin)<p class="text-[10px] text-stone-400 font-mono">GSTIN: {{ $delivery->company->gstin }}</p>@endif
                </div>
                <div>
                    <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-1.5">Deliver To (Receiver)</p>
                    <p class="text-sm font-bold text-stone-800">{{ $delivery->party->display_name ?? $delivery->party->name }}</p>
                    @if($delivery->receiver_name)<p class="text-xs text-stone-600 mt-0.5">Attn: {{ $delivery->receiver_name }}</p>@endif
                    @if($delivery->delivery_address)<p class="text-xs text-stone-600 mt-1 whitespace-pre-line">{{ $delivery->delivery_address }}</p>@endif
                    @if($delivery->receiver_phone)<p class="text-[10px] text-stone-400 mt-1">Phone: {{ $delivery->receiver_phone }}</p>@endif
                    @if($delivery->party->gstin)<p class="text-[10px] text-stone-400 font-mono">GSTIN: {{ $delivery->party->gstin }}</p>@endif
                </div>
            </div>

            {{-- Document Info --}}
            <div class="flex flex-wrap gap-6 pt-4 border-t border-stone-100">
                <div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Delivery Note #</p><p class="text-sm text-stone-800 mt-0.5 font-mono">{{ $delivery->delivery_number }}</p></div>
                <div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Dispatch Date</p><p class="text-sm text-stone-800 mt-0.5">{{ $delivery->dispatch_date->format('d M Y') }}</p></div>
                @if($delivery->order_number)<div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Order / PO Ref</p><p class="text-sm text-stone-800 mt-0.5">{{ $delivery->order_number }}</p></div>@endif
                @if($delivery->total_packages)<div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Packages</p><p class="text-sm text-stone-800 mt-0.5">{{ $delivery->total_packages }}</p></div>@endif
                @if($delivery->total_weight)<div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Total Weight</p><p class="text-sm text-stone-800 mt-0.5">{{ $delivery->total_weight }}</p></div>@endif
            </div>
        </div>

        {{-- Transport Details --}}
        @if($delivery->transporter_name || $delivery->vehicle_number || $delivery->driver_name || $delivery->tracking_number)
        <div class="px-5 py-3 border-b border-stone-100 bg-stone-50/50">
            <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-2">Transport / Carrier Details</p>
            <div class="flex flex-wrap gap-6">
                @if($delivery->transport_mode)<div><p class="text-[10px] text-stone-400">Mode</p><p class="text-xs font-medium text-stone-800">{{ $delivery->transport_mode }}</p></div>@endif
                @if($delivery->transporter_name)<div><p class="text-[10px] text-stone-400">Transporter</p><p class="text-xs font-medium text-stone-800">{{ $delivery->transporter_name }}</p></div>@endif
                @if($delivery->vehicle_number)<div><p class="text-[10px] text-stone-400">Vehicle No.</p><p class="text-xs font-medium text-stone-800">{{ $delivery->vehicle_number }}</p></div>@endif
                @if($delivery->driver_name)<div><p class="text-[10px] text-stone-400">Driver</p><p class="text-xs font-medium text-stone-800">{{ $delivery->driver_name }}</p></div>@endif
                @if($delivery->driver_phone)<div><p class="text-[10px] text-stone-400">Driver Phone</p><p class="text-xs font-medium text-stone-800">{{ $delivery->driver_phone }}</p></div>@endif
                @if($delivery->tracking_number)<div><p class="text-[10px] text-stone-400">AWB / LR / Docket</p><p class="text-xs font-medium text-stone-800 font-mono">{{ $delivery->tracking_number }}</p></div>@endif
            </div>
        </div>
        @endif

        {{-- Item Details Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-xs" style="table-layout:fixed;">
                <colgroup><col style="width:36px;"><col><col style="width:90px;"><col style="width:70px;"><col style="width:52px;"><col style="width:80px;"><col style="width:20%;"></colgroup>
                <thead style="background:#f5f5f4;border-bottom:1px solid #e7e5e4;">
                    <tr>
                        <th class="py-2 px-2 text-left text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Sr.</th>
                        <th class="py-2 px-2 text-left text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Item Description</th>
                        <th class="py-2 px-2 text-left text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Product Code</th>
                        <th class="py-2 px-2 text-right text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Quantity</th>
                        <th class="py-2 px-2 text-left text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Unit</th>
                        <th class="py-2 px-2 text-left text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Weight</th>
                        <th class="py-2 px-2 text-left text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($delivery->items as $idx => $item)
                    <tr style="border-bottom:1px solid #f5f5f4;">
                        <td class="py-2.5 px-2 text-[10px] text-stone-400 font-mono">{{ $idx + 1 }}</td>
                        <td class="py-2.5 px-2"><p class="font-medium text-stone-800 text-xs">{{ $item->description }}</p></td>
                        <td class="py-2.5 px-2 text-xs text-stone-600 font-mono">{{ $item->product_code ?: '—' }}</td>
                        <td class="py-2.5 px-2 text-right text-xs font-semibold text-stone-800">{{ rtrim(rtrim(number_format((float)$item->qty, 3), '0'), '.') }}</td>
                        <td class="py-2.5 px-2 text-xs text-stone-600">{{ $item->unit ?: '—' }}</td>
                        <td class="py-2.5 px-2 text-xs text-stone-600">{{ $item->weight ?: '—' }}</td>
                        <td class="py-2.5 px-2 text-xs text-stone-500">{{ $item->remarks ?: '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Summary --}}
        <div class="px-5 py-3 border-t border-stone-100 flex justify-end">
            <div class="w-48 space-y-1">
                <div class="flex justify-between text-xs"><span class="text-stone-500">Total Items</span><span class="text-stone-800 font-semibold">{{ $delivery->items->count() }}</span></div>
                <div class="flex justify-between text-xs"><span class="text-stone-500">Total Qty</span><span class="text-stone-800 font-semibold">{{ rtrim(rtrim(number_format($delivery->items->sum('qty'), 3), '0'), '.') }}</span></div>
                @if($delivery->total_packages)<div class="flex justify-between text-xs"><span class="text-stone-500">Packages</span><span class="text-stone-800 font-semibold">{{ $delivery->total_packages }}</span></div>@endif
                @if($delivery->total_weight)<div class="flex justify-between text-xs"><span class="text-stone-500">Weight</span><span class="text-stone-800 font-semibold">{{ $delivery->total_weight }}</span></div>@endif
            </div>
        </div>

        {{-- Sign-off / Proof of Delivery --}}
        @if($delivery->isDelivered() || $delivery->received_by)
        <div class="px-5 py-4 border-t border-stone-100 bg-emerald-50/50">
            <p class="text-[10px] font-semibold text-emerald-700 uppercase tracking-wide mb-2">✓ Proof of Delivery</p>
            <div class="flex flex-wrap gap-6">
                @if($delivery->received_by)<div><p class="text-[10px] text-stone-400">Received By</p><p class="text-xs font-medium text-stone-800">{{ $delivery->received_by }}</p></div>@endif
                @if($delivery->received_at)<div><p class="text-[10px] text-stone-400">Date & Time</p><p class="text-xs font-medium text-stone-800">{{ $delivery->received_at->format('d M Y, h:i A') }}</p></div>@endif
                @if($delivery->receiver_remarks)<div><p class="text-[10px] text-stone-400">Remarks</p><p class="text-xs text-stone-600">{{ $delivery->receiver_remarks }}</p></div>@endif
            </div>
        </div>
        @endif

        {{-- Notes --}}
        @if($delivery->notes || $delivery->narration)
        <div class="px-5 py-4 border-t border-stone-100">
            <div class="grid grid-cols-2 gap-6">
                @if($delivery->notes)
                <div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-1">Notes</p><p class="text-xs text-stone-600 whitespace-pre-line">{{ $delivery->notes }}</p></div>
                @endif
                @if($delivery->narration)
                <div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-1">Narration</p><p class="text-xs text-stone-600 whitespace-pre-line">{{ $delivery->narration }}</p></div>
                @endif
            </div>
        </div>
        @endif

        {{-- Digital Signatures --}}
        @php
            $approvalLogs = \App\Models\ApprovalLog::where('document_type', 'delivery_note')
                ->where('document_id', $delivery->id)
                ->whereNotNull('signature_path')
                ->where('action', 'approved')
                ->with('user')
                ->orderBy('level')
                ->get();
        @endphp
        @include('panel.sales._digital-signatures', ['approvalLogs' => $approvalLogs])

        {{-- Signature Section (for print) --}}
        <div class="px-5 py-6 border-t border-stone-100">
            <div class="grid grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="border-t border-stone-300 mt-10 pt-2">
                        <p class="text-[10px] text-stone-500">Prepared By</p>
                    </div>
                </div>
                <div class="text-center">
                    <div class="border-t border-stone-300 mt-10 pt-2">
                        <p class="text-[10px] text-stone-500">Dispatched By</p>
                    </div>
                </div>
                <div class="text-center">
                    <div class="border-t border-stone-300 mt-10 pt-2">
                        <p class="text-[10px] text-stone-500">Receiver's Signature & Date</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Mark Delivered Modal --}}
    <div x-show="deliveredModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="deliveredModal.open = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <h3 class="text-sm font-semibold text-stone-800 mb-4">Mark as Delivered</h3>
            <label class="block text-xs font-semibold text-stone-600 mb-1.5">Received By</label>
            <input type="text" x-model="deliveredModal.received_by" placeholder="Name of person who received"
                   class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors mb-3">
            <label class="block text-xs font-semibold text-stone-600 mb-1.5">Remarks (optional)</label>
            <textarea x-model="deliveredModal.remarks" rows="2" placeholder="Any remarks…"
                      class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors resize-none"></textarea>
            <div class="flex items-center justify-end gap-2 mt-4">
                <button @click="deliveredModal.open = false" class="px-3 py-2 text-xs font-semibold text-stone-600 border border-stone-200 rounded-lg hover:bg-stone-50">Cancel</button>
                <button @click="confirmDelivered()" class="px-4 py-2 text-xs font-semibold text-white rounded-lg transition-colors" style="background:#059669;">Confirm Delivery</button>
            </div>
        </div>
    </div>

    {{-- Cancel Modal --}}
    <div x-show="cancelModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="cancelModal.open = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <h3 class="text-sm font-semibold text-stone-800 mb-4">Cancel Delivery Note</h3>
            <label class="block text-xs font-semibold text-stone-600 mb-1.5">Reason (optional)</label>
            <textarea x-model="cancelModal.reason" rows="3" placeholder="Reason for cancellation..."
                      class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors resize-none"></textarea>
            <div class="flex items-center justify-end gap-2 mt-4">
                <button @click="cancelModal.open = false" class="px-3 py-2 text-xs font-semibold text-stone-600 border border-stone-200 rounded-lg hover:bg-stone-50">Back</button>
                <button @click="confirmCancel()" class="px-4 py-2 text-xs font-semibold text-white rounded-lg transition-colors" style="background:#d97706;">Cancel Delivery Note</button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function deliveryShow() {
    return {
        toast: { show: false, message: '', type: 'success' },
        deliveredModal: { open: false, received_by: '', remarks: '' },
        cancelModal: { open: false, reason: '' },

        init() {},

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 3500);
        },

        async submit() {
            if (!confirm('Submit this delivery note for dispatch?')) return;
            const res = await fetch(`/sales/delivery/{{ $delivery->id }}/submit`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            });
            const json = await res.json();
            if (json.success) { this.showToast(json.message); setTimeout(() => location.reload(), 1500); }
            else { this.showToast(json.message, 'error'); }
        },

        openDelivered() { this.deliveredModal = { open: true, received_by: '', remarks: '' }; },

        async confirmDelivered() {
            const res = await fetch(`/sales/delivery/{{ $delivery->id }}/mark-delivered`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                body: JSON.stringify({ received_by: this.deliveredModal.received_by, receiver_remarks: this.deliveredModal.remarks }),
            });
            const json = await res.json();
            this.deliveredModal.open = false;
            if (json.success) { this.showToast(json.message); setTimeout(() => location.reload(), 1500); }
            else { this.showToast(json.message, 'error'); }
        },

        openCancel() { this.cancelModal = { open: true, reason: '' }; },

        async confirmCancel() {
            const res = await fetch(`/sales/delivery/{{ $delivery->id }}/cancel`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                body: JSON.stringify({ reason: this.cancelModal.reason }),
            });
            const json = await res.json();
            this.cancelModal.open = false;
            if (json.success) { this.showToast(json.message); setTimeout(() => location.reload(), 1500); }
            else { this.showToast(json.message, 'error'); }
        },
    };
}
</script>
@endpush
