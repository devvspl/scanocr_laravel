@extends('layouts.app')

@section('title', isset($receipt) ? 'Edit Receipt — ' . $receipt->receipt_number : 'New Receipt')
@section('page-title', isset($receipt) ? 'Edit Receipt' : 'New Receipt')

@section('breadcrumb')
    <span>Sales</span>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('sales.receipts') }}" class="hover:text-stone-600 transition-colors">Receipts</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-stone-600">{{ isset($receipt) ? $receipt->receipt_number : 'New' }}</span>
@endsection

@section('content')
<div x-data="receiptForm()" x-init="init()" class="flex flex-col bg-white border border-stone-200 rounded-xl overflow-hidden" style="height: calc(100vh - 7.5rem);">

    {{-- ── HEADER BAND ── --}}
    <div class="shrink-0 border-b border-stone-200 bg-stone-50 px-4 py-3">
        <div class="flex items-end gap-3 min-w-0 flex-wrap">
            <div class="shrink-0 w-36">
                <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-0.5">Receipt #</label>
                <input type="text" :value="form.receipt_number" readonly
                    class="w-full h-7 px-2 text-xs font-mono bg-stone-100 border border-stone-300 rounded-lg text-stone-500 cursor-not-allowed focus:outline-none">
            </div>
            <div class="shrink-0 w-32">
                <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-0.5">Date <span class="text-red-600">*</span></label>
                <input type="date" x-model="form.receipt_date" required
                    class="w-full h-7 px-2 text-xs border border-stone-300 rounded-lg text-stone-800 bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10">
            </div>
            <div class="flex-1 min-w-[180px]" x-data="customerSearch()" @keydown.escape="close()">
                <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-0.5">Customer <span class="text-red-600">*</span></label>
                <div class="relative">
                    <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="search()"
                        @keydown.arrow-down.prevent="moveDown()" @keydown.arrow-up.prevent="moveUp()"
                        @keydown.enter.prevent="selectHighlighted()"
                        :placeholder="selectedName || 'Search customer…'"
                        :class="selectedName && !query ? 'font-medium' : ''"
                        autocomplete="new-password" spellcheck="false"
                        class="w-full h-7 px-2 pr-6 text-xs border border-stone-300 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 placeholder-stone-400 text-stone-800">
                    <button x-show="selectedName" @click="clearCustomer()" type="button"
                        class="absolute right-1.5 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    <div x-show="open" x-cloak @click.outside="close()" x-ref="customerDropdown"
                        class="absolute top-full left-0 right-0 mt-1 bg-white rounded-xl z-50 max-h-64 overflow-y-auto"
                        style="border:1px solid #e7e5e4;box-shadow:0 4px 16px rgba(0,0,0,.07);">
                        <template x-if="loading"><div class="px-3 py-4 text-center text-xs text-stone-400">Searching…</div></template>
                        <template x-if="!loading && suggestions.length === 0"><div class="px-3 py-4 text-center text-xs text-stone-400">No customers found</div></template>
                        <template x-if="!loading && suggestions.length > 0">
                            <div><template x-for="(c, ci) in suggestions" :key="c.id">
                                <button type="button" @click="select(c)" @mouseenter="highlightIdx=ci"
                                    :class="highlightIdx === ci ? 'bg-red-50' : ''"
                                    class="w-full text-left px-3 py-2 hover:bg-red-50 transition-colors"
                                    style="border:none;border-bottom:1px solid #f5f5f4;outline:none;background:transparent;">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-xs font-medium text-stone-800" x-text="c.name"></span>
                                        <span class="text-[10px] text-stone-400 font-mono shrink-0" x-text="c.gstin"></span>
                                    </div>
                                    <div class="text-[10px] text-stone-400 mt-0.5" x-text="[c.city,c.state].filter(Boolean).join(', ')"></div>
                                </button>
                            </template></div>
                        </template>
                    </div>
                </div>
            </div>
            <div class="shrink-0 w-28">
                <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-0.5">Amount <span class="text-red-600">*</span></label>
                <input type="number" x-model="form.amount" min="0.01" step="0.01" placeholder="0.00" required
                    class="w-full h-7 px-2 text-xs text-right border border-stone-300 rounded-lg text-stone-800 bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 placeholder-stone-400">
            </div>
        </div>
    </div>

    {{-- ── FORM BODY ── --}}
    <div class="flex-1 overflow-y-auto min-h-0 px-4 py-4">
        <div class="grid grid-cols-3 gap-4 max-w-4xl">
            <div>
                <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-1">Payment Method <span class="text-red-600">*</span></label>
                <select x-model="form.payment_method" required
                    class="w-full h-8 px-2.5 text-xs border border-stone-300 rounded-lg text-stone-800 bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10">
                    <option value="">— Select —</option>
                    <option value="cash">Cash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cheque">Cheque</option>
                    <option value="upi">UPI</option>
                    <option value="card">Card</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-1">Payment Reference</label>
                <input type="text" x-model="form.payment_reference" placeholder="Txn ID / Cheque No."
                    class="w-full h-8 px-2.5 text-xs border border-stone-300 rounded-lg text-stone-800 bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 placeholder-stone-400">
            </div>
            <div>
                <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-1">Payment Date</label>
                <input type="date" x-model="form.payment_date"
                    class="w-full h-8 px-2.5 text-xs border border-stone-300 rounded-lg text-stone-800 bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10">
            </div>
            <div x-show="form.payment_method === 'bank_transfer' || form.payment_method === 'cheque'" x-cloak>
                <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-1">Bank Name</label>
                <input type="text" x-model="form.bank_name" placeholder="Bank name"
                    class="w-full h-8 px-2.5 text-xs border border-stone-300 rounded-lg text-stone-800 bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 placeholder-stone-400">
            </div>
            <div x-show="form.payment_method === 'bank_transfer' || form.payment_method === 'cheque'" x-cloak>
                <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-1">Bank Account</label>
                <input type="text" x-model="form.bank_account" placeholder="Account number"
                    class="w-full h-8 px-2.5 text-xs border border-stone-300 rounded-lg text-stone-800 bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 placeholder-stone-400">
            </div>
            <div>
                <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-1">Against Invoice</label>
                <select x-model="form.sale_invoice_id"
                    class="w-full h-8 px-2.5 text-xs border border-stone-300 rounded-lg text-stone-800 bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10">
                    <option value="">— None —</option>
                    @foreach($outstandingInvoices as $inv)
                        <option value="{{ $inv->id }}">{{ $inv->invoice_number }} — {{ $inv->party->display_name ?? $inv->party->name }} (Due: ₹{{ number_format((float)$inv->amount_due, 2) }})</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4 max-w-4xl mt-4">
            <div>
                <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-1">Description</label>
                <textarea x-model="form.description" rows="3" placeholder="Payment description…"
                    class="w-full px-2.5 py-2 text-xs border border-stone-300 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 placeholder-stone-400 text-stone-700 resize-none"></textarea>
            </div>
            <div>
                <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-1">Narration</label>
                <textarea x-model="form.narration" rows="3" placeholder="Internal narration…"
                    class="w-full px-2.5 py-2 text-xs border border-stone-300 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 placeholder-stone-400 text-stone-700 resize-none"></textarea>
            </div>
        </div>
    </div>

    {{-- ── FOOTER ── --}}
    <div class="shrink-0 border-t border-stone-200 bg-white px-4 py-3">
        <div class="flex items-center justify-between">
            <div class="text-sm font-bold text-stone-800">
                Amount: <span class="text-red-700" x-text="'₹ ' + parseFloat(form.amount || 0).toLocaleString('en-IN', {minimumFractionDigits:2})"></span>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('sales.receipts') }}"
                    class="h-8 px-4 flex items-center justify-center text-xs font-semibold text-stone-600 border border-stone-200 rounded-lg hover:bg-stone-50">Cancel</a>
                <button type="button" @click="save(false)" :disabled="saving"
                    class="h-8 px-4 flex items-center justify-center text-xs font-semibold text-stone-700 border border-stone-200 rounded-lg hover:bg-stone-50 disabled:opacity-50">
                    <span x-text="saving && !submitAfterSave ? 'Saving…' : 'Save Draft'"></span>
                </button>
                <button type="button" @click="save(true)" :disabled="saving"
                    class="h-8 px-4 flex items-center justify-center text-xs font-semibold text-white rounded-lg disabled:opacity-50"
                    style="background:#7f1d1d">
                    <span x-text="saving && submitAfterSave ? 'Submitting…' : 'Create & Submit'"></span>
                </button>
            </div>
        </div>
        <div x-show="errorMsg" x-cloak class="mt-2 px-3 py-2 bg-red-50 border border-red-200 rounded-lg flex items-center gap-2">
            <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="text-xs text-red-700 flex-1" x-text="errorMsg"></span>
            <button @click="errorMsg=''" class="text-red-400 hover:text-red-600"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function customerSearch() {
    return {
        query: '', open: false, loading: false, suggestions: [], highlightIdx: -1,
        selectedName: @json(isset($receipt) && $receipt->party ? ($receipt->party->display_name ?? $receipt->party->name) : ''),

        async search() {
            this.loading = true; this.open = true;
            const res = await fetch(`/sales/receipts/search-customers?q=${encodeURIComponent(this.query)}`);
            const json = await res.json();
            this.suggestions = json.suggestions || [];
            this.loading = false; this.highlightIdx = -1;
        },
        select(c) {
            this.selectedName = c.name; this.query = ''; this.open = false;
            document.dispatchEvent(new CustomEvent('receipt-customer-selected', { detail: c }));
        },
        clearCustomer() {
            this.selectedName = ''; this.query = '';
            document.dispatchEvent(new CustomEvent('receipt-customer-selected', { detail: { id: '' } }));
        },
        close() { this.open = false; },
        moveDown() { if (this.highlightIdx < this.suggestions.length - 1) this.highlightIdx++; },
        moveUp() { if (this.highlightIdx > 0) this.highlightIdx--; },
        selectHighlighted() { if (this.highlightIdx >= 0 && this.suggestions[this.highlightIdx]) this.select(this.suggestions[this.highlightIdx]); },
    };
}

function receiptForm() {
    return {
        saving: false,
        submitAfterSave: false,
        errorMsg: '',
        form: {
            receipt_number: @json($nextNumber),
            receipt_date: @json(isset($receipt) ? $receipt->receipt_date->format('Y-m-d') : now()->format('Y-m-d')),
            party_id: @json(isset($receipt) ? (string)$receipt->party_id : ''),
            amount: @json(isset($receipt) ? (float)$receipt->amount : ''),
            payment_method: @json(isset($receipt) ? $receipt->payment_method : ''),
            payment_reference: @json(isset($receipt) ? ($receipt->payment_reference ?? '') : ''),
            payment_date: @json(isset($receipt) && $receipt->payment_date ? $receipt->payment_date->format('Y-m-d') : ''),
            bank_name: @json(isset($receipt) ? ($receipt->bank_name ?? '') : ''),
            bank_account: @json(isset($receipt) ? ($receipt->bank_account ?? '') : ''),
            sale_invoice_id: @json(isset($receipt) ? (string)($receipt->sale_invoice_id ?? '') : ''),
            description: @json(isset($receipt) ? ($receipt->description ?? '') : ''),
            narration: @json(isset($receipt) ? ($receipt->narration ?? '') : ''),
        },

        init() {
            document.addEventListener('receipt-customer-selected', (e) => { this.form.party_id = e.detail.id; });
        },

        validate() {
            if (!this.form.receipt_date) return 'Receipt date is required.';
            if (!this.form.party_id) return 'Please select a customer.';
            if (!this.form.amount || parseFloat(this.form.amount) <= 0) return 'Amount must be greater than zero.';
            if (!this.form.payment_method) return 'Please select a payment method.';
            return null;
        },

        async save(submitAfter = false) {
            this.errorMsg = '';
            const err = this.validate();
            if (err) { this.errorMsg = err; return; }

            this.saving = true;
            this.submitAfterSave = submitAfter;

            const isEdit = {{ isset($receipt) ? 'true' : 'false' }};
            const url = isEdit ? '/sales/receipts/{{ isset($receipt) ? $receipt->id : '' }}' : '/sales/receipts';
            const method = isEdit ? 'PUT' : 'POST';

            try {
                const res = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify(this.form),
                });
                const json = await res.json();

                if (!res.ok || !json.success) {
                    this.errorMsg = json.message || Object.values(json.errors || {}).flat().join(' ') || 'Save failed.';
                    this.saving = false;
                    return;
                }

                if (submitAfter && json.id) {
                    const sr = await fetch(`/sales/receipts/${json.id}/submit`, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' } });
                    const sj = await sr.json();
                    if (!sj.success) { this.errorMsg = sj.message || 'Submit failed.'; this.saving = false; return; }
                }

                if (json.redirect && !json.stay) {
                    window.location.href = json.redirect;
                } else if (json.stay) {
                    _showGlobalToast('success', json.message || 'Saved.');
                    this.saving = false;
                } else {
                    window.location.href = '/sales/receipts';
                }
            } catch (e) {
                this.errorMsg = 'Network error. Please try again.';
                this.saving = false;
            }
        },
    };
}

function _showGlobalToast(type, message) {
    const el = document.createElement('div');
    el.className = `fixed bottom-5 right-5 z-[999] px-4 py-3 rounded-xl border text-sm font-medium flex items-center gap-2 shadow-lg ${type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'}`;
    el.innerHTML = `<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${type === 'error' ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'}"/></svg><span>${message}</span>`;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 3500);
}
</script>
@endpush
