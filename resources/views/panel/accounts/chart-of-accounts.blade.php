@extends('layouts.app')

@section('title', 'Chart of Accounts')
@section('page-title', 'Chart of Accounts')

@section('content')
<div class="mx-auto space-y-4" x-data="chartOfAccounts()" x-init="init()">

    {{-- Tab header --}}
    <div class="bg-white border border-stone-200 rounded-1xl overflow-hidden mb-3">
        <div class="flex gap-1 px-0">
            <a href="{{ route('master.accounts') }}"
               class="flex items-center gap-1.5 px-4 py-3 text-sm transition-colors -mb-px whitespace-nowrap border-b-2 border-red-700 text-red-700 font-semibold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M12 7h.01M3 5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5z"/>
                </svg>
                Chart of Accounts
            </a>
            <a href="{{ route('master.account-groups') }}"
               class="flex items-center gap-1.5 px-4 py-3 text-sm transition-colors -mb-px whitespace-nowrap text-stone-500 hover:text-stone-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                Account Groups
            </a>
        </div>
    </div>

    {{-- Main card --}}
    <div class="bg-white border border-stone-200 overflow-hidden">

        {{-- Toolbar --}}
        <div class="px-4 py-2.5 border-b border-stone-100 flex items-center justify-between gap-2 min-h-[48px]">
            <div class="flex items-center gap-2">
                {{-- Group filter --}}
                <select id="filter-group"
                        class="h-7 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10">
                    <option value="">All Groups</option>
                    @foreach($groups as $g)
                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                    @endforeach
                </select>
                {{-- Nature filter --}}
                <select id="filter-nature"
                        class="h-7 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10">
                    <option value="">All Types</option>
                    <option value="assets">Assets</option>
                    <option value="liabilities">Liabilities</option>
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                </select>
            </div>
            <button @click="openModal()"
                    class="tb-btn tb-btn-add">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                Add Account
            </button>
        </div>

        {{-- Table area --}}
        <div id="dt-wrapper" class="overflow-x-auto">
            <table id="accounts-table" class="w-full">
                <thead>
                    <tr>
                        <th style="width:80px;">Code</th>
                        <th>Account Name</th>
                        <th>Group</th>
                        <th style="width:140px;">Opening Balance</th>
                        <th style="width:120px;">Created By</th>
                        <th class="dt-center" style="width:80px;">Status</th>
                        <th class="dt-center" style="width:80px;">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        {{-- Empty state --}}
        <div id="empty-state" class="hidden flex-col items-center justify-center py-16 text-center">
            <div class="w-12 h-12 rounded-xl bg-stone-100 flex items-center justify-center mb-3 mx-auto">
                <svg class="w-6 h-6 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M12 7h.01M3 5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5z"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-stone-600">No accounts yet</p>
            <p class="text-xs text-stone-400 mt-1">Click "Add Account" to get started.</p>
        </div>

    </div>

    {{-- ── Modal ── --}}
    <div x-show="modalOpen"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display:none">

        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeModal()"></div>

        {{-- Panel --}}
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            {{-- Modal header --}}
            <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-stone-800" x-text="editId ? 'Edit Account' : 'Add Account'"></h3>
                <button @click="closeModal()" class="act-btn act-edit">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Modal body --}}
            <div class="p-5">
                {{-- Toast --}}
                <div x-show="toast.show" x-transition
                     :class="toast.type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'"
                     class="mb-4 px-4 py-3 rounded-xl border text-sm font-medium flex items-center gap-2"
                     style="display:none">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              x-bind:d="toast.type === 'error'
                                ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
                                : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'"/>
                    </svg>
                    <span x-text="toast.message"></span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    {{-- Code --}}
                    <div>
                        <label class="block text-xs font-semibold text-stone-600 mb-1.5">Account Code <span class="text-red-600">*</span></label>
                        <input type="text" x-model="form.code" placeholder="e.g. 1001"
                               class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400
                                      focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors"
                               :class="errors.code ? 'border-red-400' : ''">
                        <p x-show="errors.code" x-text="errors.code" class="mt-1 text-xs text-red-600"></p>
                    </div>

                    {{-- Name --}}
                    <div>
                        <label class="block text-xs font-semibold text-stone-600 mb-1.5">Account Name <span class="text-red-600">*</span></label>
                        <input type="text" x-model="form.name" placeholder="e.g. Cash in Hand"
                               class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400
                                      focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors"
                               :class="errors.name ? 'border-red-400' : ''">
                        <p x-show="errors.name" x-text="errors.name" class="mt-1 text-xs text-red-600"></p>
                    </div>

                    {{-- Group --}}
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-stone-600 mb-1.5">Account Group <span class="text-red-600">*</span></label>
                        <select x-model="form.account_group_id"
                                class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800
                                       focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors"
                                :class="errors.account_group_id ? 'border-red-400' : ''">
                            <option value="">— Select Group —</option>
                            @foreach($groups as $g)
                            <option value="{{ $g->id }}">{{ $g->name }}</option>
                            @endforeach
                        </select>
                        <p x-show="errors.account_group_id" x-text="errors.account_group_id" class="mt-1 text-xs text-red-600"></p>
                    </div>

                    {{-- Opening Balance --}}
                    <div>
                        <label class="block text-xs font-semibold text-stone-600 mb-1.5">Opening Balance</label>
                        <input type="number" x-model="form.opening_balance" placeholder="0.00" step="0.01"
                               class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400
                                      focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors">
                    </div>

                    {{-- Balance Type --}}
                    <div>
                        <label class="block text-xs font-semibold text-stone-600 mb-1.5">Balance Type <span class="text-red-600">*</span></label>
                        <select x-model="form.balance_type"
                                class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800
                                       focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors">
                            <option value="debit">Debit</option>
                            <option value="credit">Credit</option>
                        </select>
                    </div>

                    {{-- Description --}}
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-stone-600 mb-1.5">Description</label>
                        <textarea x-model="form.description" rows="2" placeholder="Optional notes..."
                                  class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400
                                         focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors resize-none"></textarea>
                    </div>

                    {{-- Status --}}
                    <div class="sm:col-span-2 flex items-center gap-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="form.is_active" class="sr-only peer">
                            <div class="w-9 h-5 bg-stone-200 peer-focus:ring-2 peer-focus:ring-red-700/20 rounded-full peer
                                        peer-checked:after:translate-x-full peer-checked:after:border-white
                                        after:content-[''] after:absolute after:top-0.5 after:left-[2px]
                                        after:bg-white after:border-stone-300 after:border after:rounded-full
                                        after:h-4 after:w-4 after:transition-all peer-checked:bg-red-700"></div>
                        </label>
                        <span class="text-sm text-stone-600 font-medium">Active</span>
                    </div>

                </div>
            </div>

            {{-- Modal footer --}}
            <div class="px-5 py-4 border-t border-stone-100 flex items-center justify-end gap-2">
                <button @click="closeModal()" class="tb-btn tb-btn-edit">Cancel</button>
                <button @click="saveAccount()" :disabled="saving"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-800 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-semibold transition-colors">
                    <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span x-text="editId ? 'Save Changes' : 'Add Account'"></span>
                </button>
            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function chartOfAccounts() {
    return {
        modalOpen: false,
        editId: null,
        saving: false,
        toast: { show: false, type: 'success', message: '' },
        errors: {},
        form: {
            code: '', name: '', account_group_id: '',
            opening_balance: '', balance_type: 'debit',
            description: '', is_active: true,
        },

        init() {
            // Expose openModal so jQuery DataTable handlers can call it
            window._openAccountModal = (data) => this.openModal(data);
        },

        openModal(data = null) {
            this.errors = {};
            this.toast = { show: false, type: 'success', message: '' };
            if (data) {
                this.editId = data.id;
                this.form = {
                    code: data.code,
                    name: data.name,
                    account_group_id: String(data.account_group_id ?? ''),
                    opening_balance: data.opening_balance,
                    balance_type: data.balance_type,
                    description: data.description ?? '',
                    is_active: data.is_active,
                };
            } else {
                this.editId = null;
                this.form = { code: '', name: '', account_group_id: '', opening_balance: '', balance_type: 'debit', description: '', is_active: true };
            }
            this.modalOpen = true;
        },

        closeModal() {
            this.modalOpen = false;
        },

        showToast(type, message) {
            this.toast = { show: true, type, message };
            setTimeout(() => { this.toast.show = false; }, 3500);
        },

        async saveAccount() {
            this.saving = true;
            this.errors = {};
            const url = this.editId
                ? `/master/accounts/${this.editId}`
                : '/master/accounts';
            const method = this.editId ? 'PUT' : 'POST';

            try {
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        ...this.form,
                        is_active: this.form.is_active ? 1 : 0,
                        opening_balance: this.form.opening_balance || 0,
                    }),
                });

                const json = await res.json();

                if (!res.ok) {
                    if (res.status === 422 && json.errors) {
                        const flat = {};
                        for (const [k, v] of Object.entries(json.errors)) {
                            flat[k] = Array.isArray(v) ? v[0] : v;
                        }
                        this.errors = flat;
                        this.showToast('error', 'Please fix the errors below.');
                    } else {
                        this.showToast('error', json.message ?? 'Something went wrong.');
                    }
                    return;
                }

                this.closeModal();
                window._accountsTable?.ajax.reload(null, false);
                window._showGlobalToast?.('success', json.message ?? 'Account saved.');
            } catch (e) {
                this.showToast('error', 'Network error. Please try again.');
            } finally {
                this.saving = false;
            }
        },
    };
}

$(function () {
    const CSRF = $('meta[name="csrf-token"]').attr('content');

    const natureColors = {
        assets:      'bg-blue-50 text-blue-700',
        liabilities: 'bg-orange-50 text-orange-700',
        income:      'bg-green-50 text-green-700',
        expense:     'bg-red-50 text-red-700',
    };

    const table = $('#accounts-table').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: '{{ route("master.accounts.data") }}',
            data(d) {
                d.group  = $('#filter-group').val();
                d.nature = $('#filter-nature').val();
            },
        },
        columns: [
            { data: 'code',            className: 'td-num' },
            { data: 'name',            className: 'td-name' },
            { data: 'group_name',      className: '' },
            {
                data: 'opening_balance',
                className: 'td-num',
                render(v, t, row) {
                    const sign = row.balance_type === 'credit' ? 'Cr' : 'Dr';
                    return `<span class="font-variant-numeric">${v}</span> <span class="text-[10px] text-stone-400">${sign}</span>`;
                },
            },
            {
                data: 'created_by_name',
                className: '',
                render(v) {
                    if (!v || v === '—') return '<span class="text-stone-400">—</span>';
                    return `<span class="inline-flex items-center gap-1 text-xs text-stone-600">
                        <svg class="w-3 h-3 text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>${v}</span>`;
                },
            },
            {
                data: 'is_active',
                className: 'td-center',
                render(v) {
                    return v
                        ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-50 text-green-700">Active</span>'
                        : '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-stone-100 text-stone-500">Inactive</span>';
                },
            },
            {
                data: 'id',
                orderable: false,
                className: 'td-center',
                render(id) {
                    return `<div class="act-group justify-center">
                        <button class="act-btn act-edit btn-edit" data-id="${id}" title="Edit">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <button class="act-btn act-delete btn-delete" data-id="${id}" title="Delete">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>`;
                },
            },
        ],
        order: [[0, 'asc']],
        pageLength: 10,
        pagingType: 'simple_numbers',
        dom: '<"top"lf>t<"bottom"ip>',
        drawCallback() {
            const info = this.api().page.info();
            const total = info.recordsTotal;
            if (total === 0) {
                $('#accounts-table, .dataTables_wrapper .bottom').addClass('d-none').hide();
                $('#empty-state').removeClass('hidden').addClass('flex');
            } else {
                $('#accounts-table, .dataTables_wrapper .bottom').show();
                $('#empty-state').removeClass('flex').addClass('hidden');
            }
        },
    });

    window._accountsTable = table;

    // Filters
    $('#filter-group, #filter-nature').on('change', () => table.ajax.reload(null, false));

    // Edit
    $('#accounts-table').on('click', '.btn-edit', async function () {
        const id = $(this).data('id');
        const res = await fetch(`/master/accounts/${id}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        });
        const data = await res.json();
        window._openAccountModal(data);
    });

    // Delete
    $('#accounts-table').on('click', '.btn-delete', async function () {
        if (!confirm('Delete this account?')) return;
        const id = $(this).data('id');
        const res = await fetch(`/master/accounts/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const json = await res.json();
        if (json.success) {
            table.ajax.reload(null, false);
            window._showGlobalToast?.('success', json.message ?? 'Account deleted.');
        }
    });

    // Global toast helper (reuse if layout provides one, else simple alert)
    window._showGlobalToast = function(type, message) {
        // Simple inline notification — matches existing pattern
        const el = document.createElement('div');
        el.className = `fixed bottom-5 right-5 z-[999] px-4 py-3 rounded-xl border text-sm font-medium flex items-center gap-2 shadow-lg transition-all
            ${type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'}`;
        el.innerHTML = `<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="${type === 'error' ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'}"/>
        </svg><span>${message}</span>`;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 3500);
    };
});
</script>
@endpush
