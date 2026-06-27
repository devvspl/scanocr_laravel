@extends('layouts.app')
@section('title', 'API Control')
@section('page-title', 'API Control')

@section('content')
<div x-data="apiControlPage()" x-init="init()">

    @include('panel.settings._nav')

    <div class="bg-white border border-stone-200 rounded-1xl overflow-hidden">

        {{-- Toolbar --}}
        <div class="px-4 py-2.5 border-b border-stone-100 flex items-center justify-between gap-2 min-h-[48px]">
            <div class="flex items-center gap-2">
                <select id="api-filter-doctype" class="h-7 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10">
                    <option value="">All Doc Types</option>
                    @foreach($documentTypes as $dt)
                    <option value="{{ $dt->id }}">{{ $dt->label }}</option>
                    @endforeach
                </select>
                <select id="api-filter-status" class="h-7 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <button @click="openModal()" class="tb-btn tb-btn-add">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Add API Control
            </button>
        </div>

        <div class="overflow-x-auto">
            <table id="api-table" class="w-full">
                <thead><tr>
                    <th class="td-center" style="width:50px;">#</th>
                    <th>Document Type</th>
                    <th>Endpoint</th>
                    <th>Description</th>
                    <th class="td-center">Status</th>
                    <th class="td-center">Created</th>
                    <th class="td-center" style="width:80px;">Actions</th>
                </tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    {{-- Modal --}}
    <div x-show="modalOpen"
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl overflow-hidden"
             style="width:520px;max-width:calc(100vw - 2rem)"
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

            <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-stone-800" x-text="editId ? 'Edit API Control' : 'Add API Control'"></h3>
                <button @click="closeModal()" class="act-btn act-edit">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

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
                        <label class="form-label">Document Type <span class="text-red-600">*</span></label>
                        <select x-model="form.doctype_id" class="form-input" :class="errors.doctype_id ? 'border-red-400' : ''">
                            <option value="">— Select —</option>
                            @foreach($documentTypes as $dt)
                            <option value="{{ $dt->id }}">{{ $dt->label }}</option>
                            @endforeach
                        </select>
                        <p x-show="errors.doctype_id" x-text="errors.doctype_id" class="form-error"></p>
                    </div>
                    <div>
                        <label class="form-label">Endpoint <span class="text-red-600">*</span></label>
                        <input type="text" x-model="form.endpoint" placeholder="https://api.example.com/endpoint"
                               class="form-input font-mono" :class="errors.endpoint ? 'border-red-400' : ''">
                        <p x-show="errors.endpoint" x-text="errors.endpoint" class="form-error"></p>
                    </div>
                    <div>
                        <label class="form-label">Description</label>
                        <textarea x-model="form.description" rows="3" placeholder="Optional description"
                                  class="form-input resize-none"></textarea>
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="form.status" class="sr-only peer">
                            <div class="w-9 h-5 bg-stone-200 peer-focus:ring-2 peer-focus:ring-red-700/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-stone-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-red-700"></div>
                        </label>
                        <span class="text-sm text-stone-600 font-medium">Active</span>
                    </div>
                </div>
            </div>

            <div class="px-5 py-4 border-t border-stone-100 flex items-center justify-end gap-2">
                <button @click="closeModal()" class="tb-btn tb-btn-edit">Cancel</button>
                <button @click="save()" :disabled="saving"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-800 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-semibold transition-colors">
                    <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                    <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span x-text="editId ? 'Save Changes' : 'Add API Control'"></span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function apiControlPage() {
    return {
        modalOpen: false, editId: null, saving: false,
        toast: { show: false, type: 'success', message: '' },
        errors: {}, form: {},

        init() { this.resetForm(); this.initTable(); },

        resetForm() {
            this.form = { doctype_id: '', endpoint: '', description: '', status: true };
        },

        async openModal(id = null) {
            this.errors = {}; this.toast = { show: false, type: 'success', message: '' };
            if (id) {
                this.editId = id;
                const res  = await fetch(`/settings/ext-api-control/${id}`, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.form  = {
                    doctype_id:  String(data.doctype_id ?? ''),
                    endpoint:    data.endpoint    ?? '',
                    description: data.description ?? '',
                    status:      data.status == 1,
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
            const url    = this.editId ? `/settings/ext-api-control/${this.editId}` : '/settings/ext-api-control';
            const method = this.editId ? 'PUT' : 'POST';
            try {
                const res  = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ ...this.form, status: this.form.status ? 1 : 0 }),
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
                window._apiTable?.ajax.reload(null, false);
                _showGlobalToast('success', json.message ?? 'Saved.');
            } catch (e) {
                this.showToast('error', 'Network error.');
            } finally {
                this.saving = false;
            }
        },

        initTable() {
            const CSRF = document.querySelector('meta[name="csrf-token"]').content;
            if ($.fn.DataTable.isDataTable('#api-table')) $('#api-table').DataTable().destroy();

            const table = $('#api-table').DataTable({
                serverSide: true, processing: true,
                ajax: {
                    url: '{{ route("settings.ext-api-control.data") }}',
                    data(d) {
                        d.doctype_id = $('#api-filter-doctype').val();
                        d.status     = $('#api-filter-status').val();
                    },
                },
                columns: [
                    { data: 'id', className: 'td-center text-stone-400 text-xs', render: (v, t, r, m) => m.row + 1, orderable: false },
                    { data: 'doctype_label', render: v => `<span class="font-medium text-stone-800">${v}</span>` },
                    { data: 'endpoint', render: v => `<code class="text-xs bg-stone-100 px-2 py-0.5 rounded font-mono text-stone-700 break-all">${v}</code>` },
                    { data: 'description', render: v => v ? `<span class="text-xs text-stone-500">${v.substring(0, 80)}${v.length > 80 ? '…' : ''}</span>` : '<span class="text-stone-300">—</span>' },
                    {
                        data: 'status', className: 'td-center',
                        render: v => v
                            ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-50 text-green-700">Active</span>'
                            : '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-stone-100 text-stone-500">Inactive</span>',
                    },
                    { data: 'created', className: 'td-center', render: v => `<span class="text-xs text-stone-500">${v ?? '—'}</span>` },
                    {
                        data: 'id', orderable: false, className: 'td-center',
                        render: id => `<div class="act-group justify-center">
                            <button class="act-btn act-edit btn-edit" data-id="${id}" title="Edit"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                            <button class="act-btn act-delete btn-delete" data-id="${id}" title="Delete"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                        </div>`,
                    },
                ],
                order: [[0, 'asc']], pageLength: 10, pagingType: 'simple_numbers',
                dom: '<"top"lf>t<"bottom"ip>',
                language: { emptyTable: '<div class="py-12 text-center text-sm text-stone-400">No API controls found</div>' },
            });

            window._apiTable = table;
            $('#api-filter-doctype, #api-filter-status').on('change', () => table.ajax.reload(null, false));

            const self = this;
            $('#api-table').on('click', '.btn-edit', async function () { await self.openModal($(this).data('id')); });
            $('#api-table').on('click', '.btn-delete', async function () {
                if (!confirm('Delete this API control?')) return;
                const res  = await fetch(`/settings/ext-api-control/${$(this).data('id')}`, {
                    method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                });
                const json = await res.json();
                json.success ? (table.ajax.reload(null, false), _showGlobalToast('success', json.message)) : _showGlobalToast('error', json.message);
            });
        },
    };
}
</script>
@endpush
