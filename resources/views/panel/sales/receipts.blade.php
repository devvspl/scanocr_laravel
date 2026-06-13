@extends('layouts.app')

@section('title', 'Receipts')
@section('page-title', 'Receipts')

@section('breadcrumb')
    <span>Sales</span>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-stone-600">Receipts</span>
@endsection

@section('content')
<div x-data="receiptList()" x-init="init()">

    {{-- Toolbar --}}
    <div class="bg-white border border-stone-200 rounded-1xl overflow-hidden mb-4">
        <div class="px-4 py-2.5 flex items-center justify-between gap-3 min-h-[52px]">
            <div class="flex items-center gap-2">
                <select id="filter-status"
                        class="h-8 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 w-36">
                    <option value="">All Status</option>
                    <option value="draft">Draft</option>
                    <option value="submitted">Submitted</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="cancelled">Cancelled</option>
                </select>
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
            <a href="{{ route('sales.receipts.create') }}" class="tb-btn tb-btn-add shrink-0">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                New Receipt
            </a>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white border border-stone-200 rounded-1xl overflow-hidden">
        <div class="overflow-x-auto">
            <table id="receipts-table" class="w-full">
                <thead><tr>
                    <th style="width:120px;">Receipt #</th>
                    <th style="width:100px;">Date</th>
                    <th>Customer</th>
                    <th style="width:110px;" class="dt-right">Amount</th>
                    <th style="width:110px;">Payment Method</th>
                    <th style="width:130px;">Against Invoice</th>
                    <th style="width:100px;" class="dt-center">Status</th>
                    <th style="width:120px;" class="dt-center">Actions</th>
                </tr></thead>
                <tbody></tbody>
            </table>
        </div>

        {{-- Empty state --}}
        <div id="empty-state" class="hidden flex-col items-center justify-center py-20 text-center">
            <div class="w-14 h-14 rounded-2xl bg-stone-100 flex items-center justify-center mb-4 mx-auto">
                <svg class="w-7 h-7 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <p class="text-sm font-semibold text-stone-600">No receipts yet</p>
            <p class="text-xs text-stone-400 mt-1 mb-4">Create your first receipt to record a payment against an invoice.</p>
            <a href="{{ route('sales.receipts.create') }}" class="tb-btn tb-btn-add">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                New Receipt
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
            <h3 class="text-sm font-semibold text-stone-800 mb-4">Reject Receipt</h3>
            <label class="block text-xs font-semibold text-stone-600 mb-1.5">Reason</label>
            <textarea x-model="rejectModal.reason" rows="3" placeholder="Reason for rejection..."
                      class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors resize-none"></textarea>
            <div class="flex items-center justify-end gap-2 mt-4">
                <button @click="rejectModal.open=false" class="tb-btn tb-btn-edit">Back</button>
                <button @click="confirmReject()"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-600 hover:bg-red-500 text-white text-sm font-semibold transition-colors">
                    Reject Receipt
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
            <h3 class="text-sm font-semibold text-stone-800 mb-4">Cancel Receipt</h3>
            <label class="block text-xs font-semibold text-stone-600 mb-1.5">Reason (optional)</label>
            <textarea x-model="cancelModal.reason" rows="3" placeholder="Reason for cancellation..."
                      class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors resize-none"></textarea>
            <div class="flex items-center justify-end gap-2 mt-4">
                <button @click="cancelModal.open=false" class="tb-btn tb-btn-edit">Back</button>
                <button @click="confirmCancel()"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-sm font-semibold transition-colors">
                    Cancel Receipt
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function receiptList() {
    return {
        rejectModal: { open: false, id: null, reason: '' },
        cancelModal: { open: false, id: null, reason: '' },

        init() {},

        openReject(id) { this.rejectModal = { open: true, id, reason: '' }; },
        openCancel(id) { this.cancelModal = { open: true, id, reason: '' }; },

        async confirmReject() {
            await this.action(`/sales/receipts/${this.rejectModal.id}/reject`, 'POST', { reason: this.rejectModal.reason });
            this.rejectModal.open = false;
        },

        async confirmCancel() {
            await this.action(`/sales/receipts/${this.cancelModal.id}/cancel`, 'POST', { reason: this.cancelModal.reason });
            this.cancelModal.open = false;
        },

        async action(url, method, body = {}) {
            const res = await fetch(url, {
                method,
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                body: JSON.stringify(body),
            });
            const json = await res.json();
            if (json.success) {
                window._receiptsTable?.ajax.reload(null, false);
                _showGlobalToast('success', json.message ?? 'Done.');
            } else {
                _showGlobalToast('error', json.message ?? 'Something went wrong.');
            }
        },
    };
}

$(function () {
    const CSRF = $('meta[name="csrf-token"]').attr('content');

    const table = $('#receipts-table').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: '{{ route("sales.receipts.data") }}',
            data(d) {
                d.status    = $('#filter-status').val();
                d.date_from = $('#filter-date-from').val();
                d.date_to   = $('#filter-date-to').val();
            },
        },
        columns: [
            {
                data: 'receipt_number',
                render(v, t, row) {
                    return `<a href="/sales/receipts/${row.id}" class="font-mono text-xs font-semibold text-red-700 hover:underline">${v}</a>`;
                },
            },
            { data: 'receipt_date', className: 'td-date text-xs' },
            {
                data: 'party_name',
                render(v) { return `<span class="font-medium text-stone-800 text-xs">${v}</span>`; },
            },
            {
                data: 'amount',
                className: 'dt-right',
                render(v) { return `<span class="font-mono text-xs font-semibold text-stone-800">${parseFloat(v.replace(/,/g,'')).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</span>`; },
            },
            {
                data: 'payment_method',
                render(v) {
                    return `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-stone-100 text-stone-600">${v}</span>`;
                },
            },
            {
                data: 'invoice_number',
                render(v) {
                    return v !== '—' ? `<span class="font-mono text-xs text-red-700">${v}</span>` : `<span class="text-xs text-stone-400">—</span>`;
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

                    btns += `<a href="/sales/receipts/${id}" class="act-btn act-edit" title="View">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </a>`;

                    if (row.can_edit) {
                        btns += `<a href="/sales/receipts/${id}/edit" class="act-btn act-edit" title="Edit">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </a>`;
                    }

                    if (row.can_submit) {
                        btns += `<button class="act-btn btn-submit" data-id="${id}" title="Submit" style="color:#2563eb">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </button>`;
                    }

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
                $('#receipts-table').closest('.overflow-x-auto').hide();
                $('#empty-state').removeClass('hidden').addClass('flex');
            } else {
                $('#receipts-table').closest('.overflow-x-auto').show();
                $('#empty-state').removeClass('flex').addClass('hidden');
            }
        },
    });

    window._receiptsTable = table;

    $('#filter-status, #filter-date-from, #filter-date-to').on('change', () => table.ajax.reload(null, false));

    $('#receipts-table').on('click', '.btn-submit', async function () {
        if (!confirm('Submit this receipt for approval?')) return;
        const id  = $(this).data('id');
        const res = await fetch(`/sales/receipts/${id}/submit`, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
        const json = await res.json();
        if (json.success) { table.ajax.reload(null, false); _showGlobalToast('success', json.message); }
        else _showGlobalToast('error', json.message);
    });

    $('#receipts-table').on('click', '.btn-delete', async function () {
        if (!confirm('Delete this draft receipt? This cannot be undone.')) return;
        const id  = $(this).data('id');
        const res = await fetch(`/sales/receipts/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
        const json = await res.json();
        if (json.success) { table.ajax.reload(null, false); _showGlobalToast('success', json.message); }
        else _showGlobalToast('error', json.message);
    });

    window._showGlobalToast = window._showGlobalToast || function (type, message) {
        const el = document.createElement('div');
        el.className = `fixed bottom-5 right-5 z-[999] px-4 py-3 rounded-xl border text-sm font-medium flex items-center gap-2 shadow-lg ${type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'}`;
        el.innerHTML = `<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${type === 'error' ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'}"/></svg><span>${message}</span>`;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 3500);
    };
});
</script>
@endpush
