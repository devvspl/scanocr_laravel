@extends('layouts.app')

@section('title', 'Units')
@section('page-title', 'Units')

@section('content')
<div x-data="unitsPage()" x-init="init()">

    @include('panel.settings._nav')

    <div class="bg-white border border-stone-200 rounded-1xl overflow-hidden">

        {{-- Toolbar --}}
        <div class="px-4 py-2.5 border-b border-stone-100 flex items-center justify-between gap-2 min-h-[48px]">
            <div class="flex items-center gap-2">
                <select id="dt-filter-status" class="h-7 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <button @click="openModal()" class="tb-btn tb-btn-add">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Add Unit
            </button>
        </div>

        <div class="overflow-x-auto">
            <table id="dt-table" class="w-full">
                <thead><tr>
                    <th class="td-center" style="width:50px;">#</th>
                    <th>Unit Name</th>
                    <th>Unit Code</th>
                    <th>Created By</th>
                    <th class="dt-center">Status</th>
                    <th class="dt-center" style="width:80px;">Actions</th>
                </tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    {{-- Modal --}}
    <div x-show="modalOpen"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display:none">

        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeModal()"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl overflow-hidden"
             style="width:480px;max-width:calc(100vw - 2rem)"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            {{-- Header --}}
            <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-stone-800" x-text="editId ? 'Edit Unit' : 'Add Unit'"></h3>
                <button @click="closeModal()" class="act-btn act-edit">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-5">
                <div x-show="toast.show" x-transition
                     :class="toast.type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'"
                     class="mb-4 px-4 py-3 rounded-xl border text-sm font-medium flex items-center gap-2" style="display:none">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              x-bind:d="toast.type === 'error' ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'"/>
                    </svg>
                    <span x-text="toast.message"></span>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-stone-600 mb-1.5">Unit Name <span class="text-red-600">*</span></label>
                        <input type="text" x-model="form.unit_name" placeholder="e.g. Kilogram"
                               class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors"
                               :class="errors.unit_name ? 'border-red-400' : ''">
                        <p x-show="errors.unit_name" x-text="errors.unit_name" class="mt-1 text-xs text-red-600"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-stone-600 mb-1.5">Unit Code <span class="text-red-600">*</span></label>
                        <input type="text" x-model="form.unit_code" placeholder="e.g. KG" maxlength="10"
                               class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 font-mono focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors"
                               :class="errors.unit_code ? 'border-red-400' : ''">
                        <p x-show="errors.unit_code" x-text="errors.unit_code" class="mt-1 text-xs text-red-600"></p>
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="form.status" class="sr-only peer">
                            <div class="w-9 h-5 bg-stone-200 peer-focus:ring-2 peer-focus:ring-red-700/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-stone-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-green-600"></div>
                        </label>
                        <span class="text-sm text-stone-600 font-medium">Active</span>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-5 py-4 border-t border-stone-100 flex items-center justify-end gap-2">
                <button @click="closeModal()" class="tb-btn tb-btn-edit">Cancel</button>
                <button @click="save()" :disabled="saving"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-800 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-semibold transition-colors">
                    <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span x-text="editId ? 'Save Changes' : 'Add Unit'"></span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function unitsPage() {
    return {
        modalOpen: false, editId: null, saving: false,
        toast: { show: false, type: 'success', message: '' },
        errors: {}, form: {},

        init() {
            this.resetForm();
            this.initTable();
        },

        resetForm() {
            this.form = { unit_name: '', unit_code: '', status: true };
        },

        async openModal(id = null) {
            this.errors = {}; this.toast = { show: false, type: 'success', message: '' };
            if (id) {
                this.editId = id;
                const res  = await fetch(`/settings/units/${id}`, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                const data = await res.json();
                this.form  = {
                    unit_name: data.unit_name ?? '',
                    unit_code: data.unit_code ?? '',
                    status:        data.status === 'A',
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
            const url    = this.editId ? `/settings/units/${this.editId}` : '/settings/units';
            const method = this.editId ? 'PUT' : 'POST';
            try {
                const res = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ ...this.form, status: this.form.status ? 'A' : 'D' }),
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
                window._dtTable?.ajax.reload(null, false);
                _showGlobalToast('success', json.message ?? 'Saved.');
            } catch (e) {
                this.showToast('error', 'Network error.');
            } finally {
                this.saving = false;
            }
        },

        initTable() {
            const CSRF = $('meta[name="csrf-token"]').attr('content');

            if ($.fn.DataTable.isDataTable('#dt-table')) $('#dt-table').DataTable().destroy();

            const table = $('#dt-table').DataTable({
                serverSide: true, processing: true,
                ajax: {
                    url: '/settings/units/data',
                    data(d) { d.status = $('#dt-filter-status').val(); },
                },
                columns: [
                    { data: 'unit_id', className: 'td-center text-stone-400 text-xs' },
                    { data: 'unit_name', render: v => `<span class="font-medium text-stone-800">${v}</span>` },
                    { data: 'unit_code', render: v => `<code class="text-xs bg-stone-100 px-1.5 py-0.5 rounded font-mono text-stone-600">${v}</code>` },
                    {
                        data: 'created_by_name',
                        render(v) {
                            if (!v || v === '—') return '<span class="text-stone-400">—</span>';
                            return `<span class="inline-flex items-center gap-1 text-xs text-stone-600"><svg class="w-3 h-3 text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>${v}</span>`;
                        }
                    },
                    {
                        data: 'status', className: 'td-center',
                        render: v => v === 'A'
                            ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-50 text-green-700">Active</span>'
                            : '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-stone-100 text-stone-500">Inactive</span>',
                    },
                    {
                        data: 'unit_id', orderable: false, className: 'td-center',
                        render(id) {
                            return `<div class="act-group justify-center"><button class="act-btn act-edit btn-edit" data-id="${id}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button><button class="act-btn act-delete btn-delete" data-id="${id}"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></div>`;
                        }
                    },
                ],
                order: [[0, 'asc']], pageLength: 25, pagingType: 'simple_numbers',
                dom: '<"top"lf>t<"bottom"ip>',
                language: {
                    emptyTable: '<div class="py-12 text-center text-sm text-stone-400">No units found</div>',
                },
            });

            window._dtTable = table;
            $('#dt-filter-status').on('change', () => table.ajax.reload(null, false));

            const self = this;
            $('#dt-table').on('click', '.btn-edit', async function () {
                await self.openModal($(this).data('id'));
            });
            $('#dt-table').on('click', '.btn-delete', async function () {
                if (!confirm('Delete this unit?')) return;
                const id  = $(this).data('id');
                const res = await fetch(`/settings/units/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                });
                const json = await res.json();
                json.success ? (table.ajax.reload(null, false), _showGlobalToast('success', json.message)) : _showGlobalToast('error', json.message);
            });
        },
    };
}

window._showGlobalToast = window._showGlobalToast || function (type, message) {
    const el = document.createElement('div');
    el.className = `fixed bottom-5 right-5 z-[999] px-4 py-3 rounded-xl border text-sm font-medium flex items-center gap-2 shadow-lg ${type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'}`;
    el.innerHTML = `<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${type === 'error' ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'}"/></svg><span>${message}</span>`;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 3500);
};
</script>
@endpush
