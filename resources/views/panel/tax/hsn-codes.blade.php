@extends('layouts.app')

@section('title', 'HSN / SAC Codes')
@section('page-title', 'HSN / SAC Codes')

@section('content')
<div class="mx-auto space-y-4" x-data="hsnPage()" x-init="init()">

    {{-- Tab header --}}
    <div class="bg-white border border-stone-200 rounded-1xl overflow-hidden mb-3">
        <div class="flex gap-1 px-0">
            <a href="{{ route('master.taxes') }}"
               class="flex items-center gap-1.5 px-4 py-3 text-sm transition-colors -mb-px whitespace-nowrap text-stone-500 hover:text-stone-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                </svg>
                Tax Rates
            </a>
            <a href="{{ route('master.hsn') }}"
               class="flex items-center gap-1.5 px-4 py-3 text-sm transition-colors -mb-px whitespace-nowrap border-b-2 border-red-700 text-red-700 font-semibold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                HSN / SAC Codes
            </a>
        </div>
    </div>

    {{-- Table card --}}
    <div class="bg-white border border-stone-200 overflow-hidden">
        <div class="px-4 py-2.5 border-b border-stone-100 flex items-center justify-between gap-2 min-h-[48px]">
            <div class="flex items-center gap-2">
                <select id="filter-status" class="h-7 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <select id="filter-type" class="h-7 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10">
                    <option value="">HSN + SAC</option>
                    <option value="hsn">HSN Only</option>
                    <option value="sac">SAC Only</option>
                </select>
            </div>
            <button @click="openModal()" class="tb-btn tb-btn-add">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Add HSN / SAC
            </button>
        </div>
        <div class="overflow-x-auto">
            <table id="hsn-table" class="w-full">
                <thead><tr>
                    <th class="td-center">Code</th>
                    <th class="td-center">Type</th>
                    <th>Description</th>
                    <th>Tax Rate</th>
                    <th>Created By</th>
                    <th class="dt-center">Status</th>
                    <th class="dt-center" style="width:80px;">Actions</th>
                </tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    {{-- Modal --}}
    <div x-show="modalOpen" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden" x-transition>
            <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-stone-800" x-text="editId ? 'Edit HSN / SAC Code' : 'Add HSN / SAC Code'"></h3>
                <button @click="closeModal()" class="act-btn act-edit"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="p-5 space-y-4">
                <div x-show="toast.show" x-transition :class="toast.type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'" class="px-4 py-3 rounded-xl border text-sm font-medium flex items-center gap-2" style="display:none">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" x-bind:d="toast.type === 'error' ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'"/></svg>
                    <span x-text="toast.message"></span>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-stone-600 mb-1.5">Code <span class="text-red-600">*</span></label>
                        <input type="text" x-model="form.code" placeholder="e.g. 8471 or 998314" class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors" :class="errors.code ? 'border-red-400' : ''">
                        <p x-show="errors.code" x-text="errors.code" class="mt-1 text-xs text-red-600"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-stone-600 mb-1.5">Type <span class="text-red-600">*</span></label>
                        <select x-model="form.type" class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors" :class="errors.type ? 'border-red-400' : ''">
                            <option value="hsn">HSN (Goods)</option>
                            <option value="sac">SAC (Services)</option>
                        </select>
                        <p x-show="errors.type" x-text="errors.type" class="mt-1 text-xs text-red-600"></p>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-stone-600 mb-1.5">Description <span class="text-red-600">*</span></label>
                        <textarea x-model="form.description" rows="2" placeholder="e.g. Automatic data processing machines" class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors resize-none" :class="errors.description ? 'border-red-400' : ''"></textarea>
                        <p x-show="errors.description" x-text="errors.description" class="mt-1 text-xs text-red-600"></p>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-stone-600 mb-1.5">Tax Rate</label>
                        <select x-model="form.tax_rate_id" class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors">
                            <option value="">— None —</option>
                            @foreach($taxRates as $tax)
                            <option value="{{ $tax->id }}">{{ $tax->name }} ({{ number_format($tax->rate, 0) }}%)</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="form.is_active" class="sr-only peer">
                        <div class="w-9 h-5 bg-stone-200 peer-focus:ring-2 peer-focus:ring-red-700/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-stone-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-red-700"></div>
                    </label>
                    <span class="text-sm text-stone-600 font-medium">Active</span>
                </div>
            </div>
            <div class="px-5 py-4 border-t border-stone-100 flex items-center justify-end gap-2">
                <button @click="closeModal()" class="tb-btn tb-btn-edit">Cancel</button>
                <button @click="save()" :disabled="saving" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-800 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-semibold transition-colors">
                    <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span x-text="editId ? 'Save' : 'Add'"></span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function hsnPage() {
    return {
        modalOpen: false, editId: null, saving: false,
        toast: { show: false, type: 'success', message: '' },
        errors: {}, form: {},

        init() {
            this.resetForm();
            window._openHsnModal = (data) => this.openModal(data);
        },

        resetForm() {
            this.form = { code: '', type: 'hsn', description: '', tax_rate_id: '', is_active: true };
        },

        openModal(data = null) {
            this.errors = {}; this.toast = { show: false, type: 'success', message: '' };
            if (data) {
                this.editId = data.id;
                this.form = {
                    code: data.code ?? '', type: data.type ?? 'hsn',
                    description: data.description ?? '',
                    tax_rate_id: data.tax_rate_id ? String(data.tax_rate_id) : '',
                    is_active: data.is_active,
                };
            } else {
                this.editId = null; this.resetForm();
            }
            this.modalOpen = true;
        },

        closeModal() { this.modalOpen = false; },

        showToast(type, message) {
            this.toast = { show: true, type, message };
            setTimeout(() => { this.toast.show = false; }, 3500);
        },

        async save() {
            this.saving = true; this.errors = {};
            const url    = this.editId ? `/master/hsn/${this.editId}` : '/master/hsn';
            const method = this.editId ? 'PUT' : 'POST';
            try {
                const res = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ ...this.form, is_active: this.form.is_active ? 1 : 0 }),
                });
                const json = await res.json();
                if (!res.ok) {
                    if (res.status === 422 && json.errors) {
                        const flat = {};
                        for (const [k, v] of Object.entries(json.errors)) flat[k] = Array.isArray(v) ? v[0] : v;
                        this.errors = flat;
                        this.showToast('error', 'Please fix the errors below.');
                    } else this.showToast('error', json.message ?? 'Something went wrong.');
                    return;
                }
                this.closeModal();
                window._hsnTable?.ajax.reload(null, false);
                _showGlobalToast('success', json.message ?? 'Saved.');
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

    if ($.fn.DataTable.isDataTable('#hsn-table')) {
        $('#hsn-table').DataTable().destroy();
    }

    const table = $('#hsn-table').DataTable({
        serverSide: true, processing: true,
        ajax: {
            url: '{{ route("master.hsn.data") }}',
            data(d) {
                d.status = $('#filter-status').val();
                d.type   = $('#filter-type').val();
            },
        },
        columns: [
            { data: 'code', className: 'td-center font-mono font-semibold text-stone-800' },
            {
                data: 'type', className: 'td-center',
                render: v => v === 'hsn'
                    ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-700">HSN</span>'
                    : '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-purple-50 text-purple-700">SAC</span>',
            },
            { data: 'description', render: v => `<span class="text-sm text-stone-700">${v}</span>` },
            {
                data: 'tax_rate_name',
                render: v => v && v !== '—'
                    ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700">${v}</span>`
                    : '<span class="text-stone-400">—</span>',
            },
            {
                data: 'created_by_name',
                render(v) {
                    if (!v || v === '—') return '<span class="text-stone-400">—</span>';
                    return `<span class="inline-flex items-center gap-1 text-xs text-stone-600"><svg class="w-3 h-3 text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>${v}</span>`;
                }
            },
            {
                data: 'is_active', className: 'td-center',
                render: v => v
                    ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-50 text-green-700">Active</span>'
                    : '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-stone-100 text-stone-500">Inactive</span>',
            },
            {
                data: 'id', orderable: false, className: 'td-center',
                render: id => `<div class="act-group justify-center"><button class="act-btn act-edit btn-edit" data-id="${id}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button><button class="act-btn act-delete btn-delete" data-id="${id}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></div>`,
            },
        ],
        order: [[0, 'asc']], pageLength: 15, pagingType: 'simple_numbers',
        dom: '<"top"lf>t<"bottom"ip>',
        language: {
            emptyTable: '<div class="flex flex-col items-center justify-center py-12 text-center"><svg class="w-10 h-10 text-stone-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg><p class="text-sm font-medium text-stone-500">No HSN / SAC codes yet</p><p class="text-xs text-stone-400 mt-1">Click &ldquo;Add HSN / SAC&rdquo; to get started.</p></div>',
            zeroRecords: '<div class="flex flex-col items-center justify-center py-12 text-center"><svg class="w-10 h-10 text-stone-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg><p class="text-sm font-medium text-stone-500">No matching records</p><p class="text-xs text-stone-400 mt-1">Try adjusting your search or filters.</p></div>',
        },
    });

    window._hsnTable = table;
    $('#filter-status, #filter-type').on('change', () => table.ajax.reload(null, false));

    $('#hsn-table').on('click', '.btn-edit', async function () {
        const id  = $(this).data('id');
        const res = await fetch(`/master/hsn/${id}`, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
        const data = await res.json();
        window._openHsnModal(data);
    });

    $('#hsn-table').on('click', '.btn-delete', async function () {
        if (!confirm('Delete this HSN/SAC code?')) return;
        const id  = $(this).data('id');
        const res = await fetch(`/master/hsn/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
        const json = await res.json();
        json.success ? (table.ajax.reload(null, false), _showGlobalToast('success', json.message)) : _showGlobalToast('error', json.message);
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
