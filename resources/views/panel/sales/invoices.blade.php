@extends('layouts.app')

@section('title', 'Sales Invoices')
@section('page-title', 'Sales Invoices')

@section('breadcrumb')
    <span>Sales</span>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-stone-600">Invoices</span>
@endsection

@section('content')
<div x-data="invoiceList()" x-init="init()">

    {{-- Toolbar --}}
    <div class="bg-white border border-stone-200 rounded-1xl overflow-hidden mb-4">
        <div class="px-4 py-2.5 flex items-center justify-between gap-3 min-h-[52px]">
            <div class="flex items-center gap-2">
                {{-- Status filter --}}
                <select id="filter-status"
                        class="h-8 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 w-36">
                    <option value="">All Status</option>
                    <option value="draft">Draft</option>
                    <option value="submitted">Submitted</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                {{-- Date range --}}
                <div class="flex items-center gap-1.5">
                    <span class="text-[10px] text-stone-400 font-medium whitespace-nowrap">From</span>
                    <input type="date" id="filter-date-from"
                           class="h-8 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 w-36">
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="text-[10px] text-stone-400 font-medium whitespace-nowrap">To</span>
                    <input type="date" id="filter-date-to"
                           class="h-8 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 w-36">
                </div>
            </div>
            <a href="{{ route('sales.invoices.create') }}" class="tb-btn tb-btn-add shrink-0">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                New Invoice
            </a>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white border border-stone-200 rounded-1xl overflow-hidden">
        <div class="overflow-x-auto">
            <table id="invoice-table" class="w-full">
                <thead><tr>
                    <th style="width:140px;">Invoice #</th>
                    <th style="width:110px;">Date</th>
                    <th>Customer</th>
                    <th style="width:130px;" class="text-right">Amount</th>
                    <th style="width:130px;" class="text-right">Due</th>
                    <th style="width:100px;" class="dt-center">Status</th>
                    <th style="width:110px;" class="dt-center">Actions</th>
                </tr></thead>
                <tbody></tbody>
            </table>
        </div>

        {{-- Empty state --}}
        <div id="empty-state" class="hidden flex-col items-center justify-center py-20 text-center">
            <div class="w-14 h-14 rounded-2xl bg-stone-100 flex items-center justify-center mb-4 mx-auto">
                <svg class="w-7 h-7 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <p class="text-sm font-semibold text-stone-600">No invoices yet</p>
            <p class="text-xs text-stone-400 mt-1 mb-4">Create your first sales invoice to get started.</p>
            <a href="{{ route('sales.invoices.create') }}" class="tb-btn tb-btn-add">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                New Invoice
            </a>
        </div>
    </div>

    {{-- Reject modal --}}
    <div x-show="rejectModal.open" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="rejectModal.open=false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <h3 class="text-sm font-semibold text-stone-800 mb-4">Reject Invoice</h3>
            <label class="block text-xs font-semibold text-stone-600 mb-1.5">Reason <span class="text-red-600">*</span></label>
            <textarea x-model="rejectModal.reason" rows="3" placeholder="Reason for rejection..."
                      class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors resize-none"></textarea>
            <div class="flex items-center justify-end gap-2 mt-4">
                <button @click="rejectModal.open=false" class="tb-btn tb-btn-edit">Cancel</button>
                <button @click="confirmReject()"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-800 hover:bg-red-700 text-white text-sm font-semibold transition-colors">
                    Reject Invoice
                </button>
            </div>
        </div>
    </div>

    {{-- Cancel modal --}}
    <div x-show="cancelModal.open" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="cancelModal.open=false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <h3 class="text-sm font-semibold text-stone-800 mb-4">Cancel Invoice</h3>
            <label class="block text-xs font-semibold text-stone-600 mb-1.5">Reason (optional)</label>
            <textarea x-model="cancelModal.reason" rows="3" placeholder="Reason for cancellation..."
                      class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors resize-none"></textarea>
            <div class="flex items-center justify-end gap-2 mt-4">
                <button @click="cancelModal.open=false" class="tb-btn tb-btn-edit">Back</button>
                <button @click="confirmCancel()"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-sm font-semibold transition-colors">
                    Cancel Invoice
                </button>
            </div>
        </div>
    </div>

    {{-- ── Approval Slide-over Panel ── --}}
    <div x-show="approvalPanel.open" x-cloak class="fixed inset-0 z-50 flex justify-end">
        <div class="absolute inset-0 bg-black/30" @click="approvalPanel.open = false"></div>
        <div class="relative w-full max-w-md bg-white shadow-2xl flex flex-col overflow-hidden"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">

            {{-- Panel header --}}
            <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between shrink-0">
                <div>
                    <h3 class="text-sm font-semibold text-stone-800">Approval Progress</h3>
                    <p class="text-[11px] text-stone-400 mt-0.5" x-text="'Invoice #' + approvalPanel.invoiceNumber"></p>
                </div>
                <button @click="approvalPanel.open = false" class="w-7 h-7 flex items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Panel body --}}
            <div class="flex-1 overflow-y-auto px-5 py-4">
                {{-- Loading --}}
                <div x-show="approvalPanel.loading" class="flex items-center justify-center py-12">
                    <svg class="w-6 h-6 animate-spin text-stone-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                </div>

                {{-- No approval configured --}}
                <div x-show="!approvalPanel.loading && !approvalPanel.setting" class="text-center py-12">
                    <svg class="w-10 h-10 text-stone-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    <p class="text-xs text-stone-500 font-semibold">No approval workflow configured</p>
                    <p class="text-[11px] text-stone-400 mt-1">Configure approval in Settings → Numbering</p>
                </div>

                {{-- Approval progress --}}
                <div x-show="!approvalPanel.loading && approvalPanel.setting">
                    {{-- Status badge --}}
                    <div class="mb-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold"
                              :class="{
                                  'bg-stone-100 text-stone-600': approvalPanel.invoice?.status === 'draft',
                                  'bg-blue-50 text-blue-700': approvalPanel.invoice?.status === 'submitted',
                                  'bg-green-50 text-green-700': approvalPanel.invoice?.status === 'approved',
                                  'bg-red-50 text-red-700': approvalPanel.invoice?.status === 'rejected',
                                  'bg-amber-50 text-amber-700': approvalPanel.invoice?.status === 'cancelled',
                              }" x-text="(approvalPanel.invoice?.status || '').charAt(0).toUpperCase() + (approvalPanel.invoice?.status || '').slice(1)">
                        </span>
                        <span class="text-[11px] text-stone-400 ml-2" x-show="approvalPanel.setting?.approval_mode === 'required'"
                              x-text="'Level ' + (approvalPanel.invoice?.current_approval_level || 0) + ' of ' + (approvalPanel.invoice?.max_approval_level || 0)"></span>
                    </div>

                    {{-- Level progress steps --}}
                    <div class="space-y-3 mb-5">
                        <template x-for="(lvl, li) in (approvalPanel.setting?.levels || [])" :key="li">
                            <div class="p-3 rounded-xl border transition-colors"
                                 :class="{
                                     'border-green-200 bg-green-50': isLevelComplete(li+1),
                                     'border-blue-200 bg-blue-50': isLevelCurrent(li+1),
                                     'border-stone-100 bg-stone-50': !isLevelComplete(li+1) && !isLevelCurrent(li+1),
                                     'border-red-200 bg-red-50': isLevelRejected(li+1),
                                 }">
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-bold"
                                         :class="{
                                             'bg-green-600 text-white': isLevelComplete(li+1),
                                             'bg-blue-600 text-white': isLevelCurrent(li+1),
                                             'bg-stone-300 text-white': !isLevelComplete(li+1) && !isLevelCurrent(li+1),
                                             'bg-red-600 text-white': isLevelRejected(li+1),
                                         }" x-text="li+1"></div>
                                    <span class="text-xs font-semibold text-stone-700" x-text="lvl.name || ('Level ' + (li+1))"></span>
                                    <span x-show="isLevelComplete(li+1)" class="ml-auto text-[10px] font-semibold text-green-700">✓ Approved</span>
                                    <span x-show="isLevelCurrent(li+1)" class="ml-auto text-[10px] font-semibold text-blue-700">● Pending</span>
                                    <span x-show="isLevelRejected(li+1)" class="ml-auto text-[10px] font-semibold text-red-700">✗ Rejected</span>
                                </div>
                                {{-- Approver details --}}
                                <div class="space-y-1 pl-7">
                                    <template x-for="log in getLogsForLevel(li+1)" :key="log.id">
                                        <div class="flex items-center gap-2 text-[11px]">
                                            <span class="w-1.5 h-1.5 rounded-full shrink-0"
                                                  :class="{'bg-green-500': log.action==='approved', 'bg-red-500': log.action==='rejected', 'bg-amber-400': log.action==='pending'}"></span>
                                            <span class="text-stone-700 font-medium" x-text="log.user?.name || 'Unknown'"></span>
                                            <span class="text-stone-400" x-text="log.action === 'pending' ? 'Pending' : (log.acted_at ? new Date(log.acted_at).toLocaleDateString() : '')"></span>
                                            <span x-show="log.remarks" class="text-stone-400 italic truncate max-w-[120px]" x-text="log.remarks"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Action buttons (if user can act) --}}
                    <div x-show="approvalPanel.invoice?.status === 'submitted'" class="border-t border-stone-100 pt-4">
                        <label class="form-label mb-1.5">Remarks</label>
                        <textarea x-model="approvalPanel.remarks" rows="2" placeholder="Optional remarks..."
                                  class="w-full border border-stone-200 rounded-xl px-3 py-2 text-xs text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 resize-none mb-3"></textarea>
                        <div class="flex gap-2">
                            <button @click="panelApprove()" class="flex-1 inline-flex items-center justify-center gap-1.5 h-9 px-4 text-xs font-semibold text-white rounded-lg transition-colors" style="background:#16a34a;" onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Approve
                            </button>
                            <button @click="panelReject()" class="flex-1 inline-flex items-center justify-center gap-1.5 h-9 px-4 text-xs font-semibold text-white rounded-lg transition-colors" style="background:#dc2626;" onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                Reject
                            </button>
                        </div>
                    </div>

                    {{-- Full history log --}}
                    <div class="mt-5 border-t border-stone-100 pt-4">
                        <p class="text-[10px] font-bold text-stone-400 uppercase tracking-widest mb-2">Approval History</p>
                        <div class="space-y-2">
                            <template x-for="log in approvalPanel.logs" :key="log.id">
                                <div class="flex items-start gap-2 text-[11px]">
                                    <span class="w-1.5 h-1.5 rounded-full mt-1.5 shrink-0"
                                          :class="{'bg-green-500': log.action==='approved', 'bg-red-500': log.action==='rejected', 'bg-amber-400': log.action==='pending', 'bg-blue-500': log.action==='escalated'}"></span>
                                    <div>
                                        <span class="font-medium text-stone-700" x-text="log.user?.name || 'System'"></span>
                                        <span class="text-stone-400" x-text="' — ' + log.action + (log.level_name ? ' (' + log.level_name + ')' : '')"></span>
                                        <span x-show="log.remarks" class="block text-stone-400 italic mt-0.5" x-text="log.remarks"></span>
                                        <span x-show="log.acted_at" class="block text-stone-300 text-[10px]" x-text="log.acted_at ? new Date(log.acted_at).toLocaleString() : ''"></span>
                                    </div>
                                </div>
                            </template>
                            <div x-show="!approvalPanel.logs || approvalPanel.logs.length === 0" class="text-[11px] text-stone-400 italic">No approval history yet.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function invoiceList() {
    return {
        rejectModal: { open: false, id: null, reason: '' },
        cancelModal: { open: false, id: null, reason: '' },
        approvalPanel: { open: false, loading: false, invoiceId: null, invoiceNumber: '', invoice: null, setting: null, logs: [], remarks: '' },

        init() {},

        openReject(id) { this.rejectModal = { open: true, id, reason: '' }; },
        openCancel(id) { this.cancelModal = { open: true, id, reason: '' }; },

        async openApprovalPanel(id, invoiceNumber) {
            this.approvalPanel = { open: true, loading: true, invoiceId: id, invoiceNumber: invoiceNumber || '', invoice: null, setting: null, logs: [], remarks: '' };
            try {
                const res = await fetch(`/sales/invoices/${id}/approval-logs`, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                if (json.success) {
                    this.approvalPanel.invoice = json.invoice;
                    this.approvalPanel.setting = json.setting;
                    this.approvalPanel.logs = json.logs || [];
                }
            } finally {
                this.approvalPanel.loading = false;
            }
        },

        getLogsForLevel(level) {
            return (this.approvalPanel.logs || []).filter(l => l.level === level);
        },

        isLevelComplete(level) {
            const logs = this.getLogsForLevel(level);
            if (logs.length === 0) return false;
            // Level is complete if current_approval_level > this level OR all approved
            const inv = this.approvalPanel.invoice;
            if (inv && inv.current_approval_level > level) return true;
            if (inv && inv.status === 'approved') return true;
            return logs.length > 0 && logs.every(l => l.action === 'approved');
        },

        isLevelCurrent(level) {
            const inv = this.approvalPanel.invoice;
            return inv && inv.status === 'submitted' && inv.current_approval_level === level;
        },

        isLevelRejected(level) {
            const logs = this.getLogsForLevel(level);
            return logs.some(l => l.action === 'rejected');
        },

        async panelApprove() {
            const id = this.approvalPanel.invoiceId;
            const res = await fetch(`/sales/invoices/${id}/level-approve`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                body: JSON.stringify({ remarks: this.approvalPanel.remarks }),
            });
            const json = await res.json();
            if (json.success) {
                _showGlobalToast('success', json.message);
                window._invoiceTable?.ajax.reload(null, false);
                this.openApprovalPanel(id, this.approvalPanel.invoiceNumber);
            } else {
                _showGlobalToast('error', json.message);
            }
        },

        async panelReject() {
            if (!this.approvalPanel.remarks.trim()) { _showGlobalToast('error', 'Please enter remarks for rejection.'); return; }
            const id = this.approvalPanel.invoiceId;
            const res = await fetch(`/sales/invoices/${id}/level-reject`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                body: JSON.stringify({ remarks: this.approvalPanel.remarks }),
            });
            const json = await res.json();
            if (json.success) {
                _showGlobalToast('success', json.message);
                window._invoiceTable?.ajax.reload(null, false);
                this.openApprovalPanel(id, this.approvalPanel.invoiceNumber);
            } else {
                _showGlobalToast('error', json.message);
            }
        },

        async confirmReject() {
            if (!this.rejectModal.reason.trim()) { alert('Please enter a reason.'); return; }
            await this.action(`/sales/invoices/${this.rejectModal.id}/reject`, 'POST', { reason: this.rejectModal.reason });
            this.rejectModal.open = false;
        },

        async confirmCancel() {
            await this.action(`/sales/invoices/${this.cancelModal.id}/cancel`, 'POST', { reason: this.cancelModal.reason });
            this.cancelModal.open = false;
        },

        async action(url, method, body = {}) {
            const res  = await fetch(url, {
                method,
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                body: JSON.stringify(body),
            });
            const json = await res.json();
            if (json.success) {
                window._invoiceTable?.ajax.reload(null, false);
                _showGlobalToast('success', json.message ?? 'Done.');
            } else {
                _showGlobalToast('error', json.message ?? 'Something went wrong.');
            }
        },
    };
}

$(function () {
    const CSRF = $('meta[name="csrf-token"]').attr('content');

    const table = $('#invoice-table').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: '{{ route("sales.invoices.data") }}',
            data(d) {
                d.status    = $('#filter-status').val();
                d.date_from = $('#filter-date-from').val();
                d.date_to   = $('#filter-date-to').val();
            },
        },
        columns: [
            {
                data: 'invoice_number',
                render(v, t, row) {
                    return `<a href="/sales/invoices/${row.id}" class="font-mono text-xs font-semibold text-red-700 hover:underline">${v}</a>`;
                },
            },
            { data: 'invoice_date', className: 'td-date text-xs' },
            {
                data: 'party_name',
                render(v, t, row) {
                    const gstin = row.party_gstin ? `<span class="text-[10px] text-stone-400 font-mono block">${row.party_gstin}</span>` : '';
                    return `<span class="font-medium text-stone-800 text-xs">${v}</span>${gstin}`;
                },
            },
            {
                data: 'grand_total',
                className: 'text-right',
                render(v) { return `<span class="font-semibold text-stone-800 text-xs">₹${v}</span>`; },
            },
            {
                data: 'amount_due',
                className: 'text-right',
                render(v, t, row) {
                    const cls = parseFloat(v.replace(/,/g,'')) > 0 ? 'text-red-700' : 'text-green-700';
                    return `<span class="font-semibold text-xs ${cls}">₹${v}</span>`;
                },
            },
            {
                data: 'status',
                className: 'dt-center',
                render(v, t, row) {
                    return `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold ${row.status_class}">${row.status_label}</span>`;
                },
            },
            {
                data: 'id',
                orderable: false,
                className: 'dt-center',
                render(id, t, row) {
                    let btns = `<div class="act-group justify-center">`;

                    // View
                    btns += `<a href="/sales/invoices/${id}" class="act-btn act-edit" title="View">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </a>`;

                    // Approval panel
                    if (row.status === 'submitted' || row.status === 'approved' || row.status === 'rejected') {
                        btns += `<button class="act-btn btn-approval" data-id="${id}" data-number="${row.invoice_number}" title="Approval" style="color:#7c3aed">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </button>`;
                    }

                    // Edit
                    if (row.can_edit) {
                        btns += `<a href="/sales/invoices/${id}/edit" class="act-btn act-edit" title="Edit">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </a>`;
                    }

                    // Submit
                    if (row.can_submit) {
                        btns += `<button class="act-btn btn-submit" data-id="${id}" title="Submit for Approval" style="color:#2563eb">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </button>`;
                    }

                    // Approve
                    if (row.can_approve) {
                        btns += `<button class="act-btn btn-approve" data-id="${id}" title="Approve" style="color:#16a34a">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </button>`;
                    }

                    // Reject
                    if (row.can_reject) {
                        btns += `<button class="act-btn btn-reject act-delete" data-id="${id}" title="Reject">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>`;
                    }

                    // Delete
                    if (row.can_delete) {
                        btns += `<button class="act-btn act-delete btn-delete" data-id="${id}" title="Delete">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>`;
                    }

                    btns += `</div>`;
                    return btns;
                },
            },
        ],
        order: [[1, 'desc']],
        pageLength: 15,
        pagingType: 'simple_numbers',
        dom: '<"top"lf>t<"bottom"ip>',
        drawCallback() {
            const total = this.api().page.info().recordsTotal;
            if (total === 0) {
                $('#invoice-table').closest('.overflow-x-auto').hide();
                $('#empty-state').removeClass('hidden').addClass('flex');
            } else {
                $('#invoice-table').closest('.overflow-x-auto').show();
                $('#empty-state').removeClass('flex').addClass('hidden');
            }
        },
    });

    window._invoiceTable = table;

    $('#filter-status, #filter-date-from, #filter-date-to').on('change', () => table.ajax.reload(null, false));

    // Submit
    $('#invoice-table').on('click', '.btn-submit', async function () {
        if (!confirm('Submit this invoice for approval?')) return;
        const id  = $(this).data('id');
        const res = await fetch(`/sales/invoices/${id}/submit`, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
        const json = await res.json();
        if (json.success) { table.ajax.reload(null, false); _showGlobalToast('success', json.message); }
        else _showGlobalToast('error', json.message);
    });

    // Approve
    $('#invoice-table').on('click', '.btn-approve', async function () {
        if (!confirm('Approve this invoice?')) return;
        const id  = $(this).data('id');
        const res = await fetch(`/sales/invoices/${id}/approve`, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
        const json = await res.json();
        if (json.success) { table.ajax.reload(null, false); _showGlobalToast('success', json.message); }
        else _showGlobalToast('error', json.message);
    });

    // Reject — open Alpine modal
    $('#invoice-table').on('click', '.btn-reject', function () {
        const id = $(this).data('id');
        Alpine.store && Alpine.store('invoiceList') ? null : null;
        document.querySelector('[x-data="invoiceList()"]')?._x_dataStack?.[0]?.openReject(id);
        // Fallback: dispatch custom event
        window.dispatchEvent(new CustomEvent('open-reject', { detail: id }));
    });

    // Delete
    $('#invoice-table').on('click', '.btn-delete', async function () {
        if (!confirm('Delete this draft invoice? This cannot be undone.')) return;
        const id  = $(this).data('id');
        const res = await fetch(`/sales/invoices/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
        const json = await res.json();
        if (json.success) { table.ajax.reload(null, false); _showGlobalToast('success', json.message); }
        else _showGlobalToast('error', json.message);
    });

    // Approval panel
    $('#invoice-table').on('click', '.btn-approval', function () {
        const id = $(this).data('id');
        const num = $(this).data('number');
        const comp = document.querySelector('[x-data="invoiceList()"]')?._x_dataStack?.[0];
        if (comp) comp.openApprovalPanel(id, num);
    });

    window._showGlobalToast = function (type, message) {
        const el = document.createElement('div');
        el.className = `fixed bottom-5 right-5 z-[999] px-4 py-3 rounded-xl border text-sm font-medium flex items-center gap-2 shadow-lg ${type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'}`;
        el.innerHTML = `<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${type === 'error' ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'}"/></svg><span>${message}</span>`;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 3500);
    };
});
</script>
@endpush
