@extends('layouts.app')

@section('title', 'Permissions')
@section('page-title', 'Permissions')

@section('content')
<div x-data="permsPage()" x-init="init()">

    @include('panel.settings._access-nav')

    <div class="bg-white border border-stone-200 rounded-1xl overflow-hidden">

        {{-- Toolbar --}}
        <div class="px-4 py-2.5 border-b border-stone-100 flex items-center justify-between gap-2 min-h-[48px]">
            <div class="flex items-center gap-2">
                <select id="filter-guard" class="h-7 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10">
                    <option value="">All Guards</option>
                    <option value="web">web</option>
                </select>
                <select id="filter-group" class="h-7 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10">
                    <option value="">All Groups</option>
                    <template x-for="g in groups" :key="g.id">
                        <option :value="g.name" x-text="g.name"></option>
                    </template>
                </select>
            </div>
            <button @click="openModal()" class="tb-btn tb-btn-add">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Add Permission
            </button>
        </div>

        <div class="overflow-x-auto">
            <table id="perms-table" class="w-full">
                <thead><tr>
                    <th class="td-center" style="width:50px;">#</th>
                    <th>Permission Name</th>
                    <th>Group</th>
                    <th>Guard</th>
                    <th class="td-center">Roles</th>
                    <th class="td-center">Users</th>
                    <th class="td-center" style="width:80px;">Actions</th>
                </tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    {{-- Permission modal --}}
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
             style="width:460px;max-width:calc(100vw - 2rem)"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-stone-800" x-text="editId ? 'Edit Permission' : 'Add Permission'"></h3>
                <button @click="closeModal()" class="act-btn act-edit">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-5">
                <div x-show="toast.show" x-transition
                     :class="toast.type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'"
                     class="mb-4 px-4 py-3 rounded-xl border text-sm font-medium flex items-center gap-2"
                     style="display:none">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              x-bind:d="toast.type === 'error' ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'"/>
                    </svg>
                    <span x-text="toast.message"></span>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-stone-600 mb-1.5">Permission Name <span class="text-red-600">*</span></label>
                        <input type="text" x-model="form.name" placeholder="e.g. invoices.create"
                               class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors"
                               :class="errors.name ? 'border-red-400' : ''">
                        <p x-show="errors.name" x-text="errors.name" class="mt-1 text-xs text-red-600"></p>
                        <p class="mt-1 text-[10px] text-stone-400">Use lowercase with dots, e.g. <code class="font-mono">invoices.create</code></p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-stone-600 mb-1.5">Group</label>
                        <div x-show="!newGroupMode" class="flex items-center gap-2">
                            <select x-model="form.group"
                                    class="flex-1 border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors">
                                <option value="">— No Group —</option>
                                <template x-for="g in groups" :key="g.id">
                                    <option :value="g.name" x-text="g.name"></option>
                                </template>
                            </select>
                            <button type="button" @click="newGroupMode = true"
                                    class="shrink-0 inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-stone-300 text-stone-500 hover:border-red-700 hover:text-red-700 text-xs font-medium transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                New
                            </button>
                        </div>
                        <div x-show="newGroupMode" class="space-y-2">
                            <input type="text" x-model="newGroupName" placeholder="Group name, e.g. Invoices"
                                   class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors"
                                   @keydown.enter.prevent="confirmNewGroup()"
                                   @keydown.escape.prevent="cancelNewGroup()">
                            <div class="flex items-center gap-2">
                                <button type="button" @click="confirmNewGroup()" :disabled="savingGroup"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-800 hover:bg-red-700 disabled:opacity-60 text-white text-xs font-semibold transition-colors">
                                    <svg x-show="savingGroup" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    <svg x-show="!savingGroup" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Add Group
                                </button>
                                <button type="button" @click="cancelNewGroup()"
                                        class="inline-flex items-center px-3 py-1.5 rounded-lg border border-stone-300 text-stone-500 hover:bg-stone-50 text-xs font-medium transition-colors">
                                    Cancel
                                </button>
                            </div>
                        </div>
                        <p class="mt-1 text-[10px] text-stone-400">Group permissions by module for easier management.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-stone-600 mb-1.5">Guard</label>
                        <select x-model="form.guard_name"
                                class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors">
                            <option value="web">web</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="px-5 py-4 border-t border-stone-100 flex items-center justify-end gap-2">
                <button @click="closeModal()" class="tb-btn tb-btn-edit">Cancel</button>
                <button @click="save()" :disabled="saving"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-800 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-semibold transition-colors">
                    <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span x-text="editId ? 'Save Changes' : 'Create Permission'"></span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function permsPage() {
    return {
        modalOpen: false,
        editId: null,
        saving: false,
        savingGroup: false,
        groups: [],
        newGroupMode: false,
        newGroupName: '',
        toast: { show: false, type: 'success', message: '' },
        errors: {},
        form: { name: '', group: '', guard_name: 'web' },

        init() {
            this.loadGroups();
            this.initTable();
        },

        resetForm() {
            this.form = { name: '', group: '', guard_name: 'web' };
            this.newGroupMode = false;
            this.newGroupName = '';
        },

        async loadGroups() {
            try {
                const res = await fetch('/settings/permission-groups', { headers: { 'Accept': 'application/json' } });
                this.groups = await res.json();
                // Rebuild filter dropdown
                const sel = document.getElementById('filter-group');
                while (sel.options.length > 1) sel.remove(1);
                this.groups.forEach(g => {
                    const opt = document.createElement('option');
                    opt.value = g.name; opt.textContent = g.name;
                    sel.appendChild(opt);
                });
            } catch (e) {}
        },

        async confirmNewGroup() {
            const name = this.newGroupName.trim();
            if (!name) return;
            this.savingGroup = true;
            try {
                const res  = await fetch('/settings/permission-groups', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ name }),
                });
                const json = await res.json();
                if (json.success) {
                    await this.loadGroups();
                    this.form.group   = name;
                    this.newGroupMode = false;
                    this.newGroupName = '';
                } else {
                    alert(json.message ?? 'Failed to create group.');
                }
            } catch (e) {
                alert('Network error.');
            } finally {
                this.savingGroup = false;
            }
        },

        cancelNewGroup() {
            this.newGroupMode = false;
            this.newGroupName = '';
        },

        async openModal(id = null) {
            this.errors = {};
            this.toast  = { show: false, type: 'success', message: '' };
            this.newGroupMode = false;
            this.newGroupName = '';
            if (id) {
                this.editId = id;
                const res  = await fetch(`/settings/permissions/${id}`, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.form  = { name: data.name ?? '', group: data.group ?? '', guard_name: data.guard_name ?? 'web' };
            } else {
                this.editId = null;
                this.resetForm();
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
            const url    = this.editId ? `/settings/permissions/${this.editId}` : '/settings/permissions';
            const method = this.editId ? 'PUT' : 'POST';
            try {
                const res  = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.form),
                });
                const json = await res.json();
                if (!res.ok) {
                    if (res.status === 422 && json.errors) {
                        const flat = {};
                        for (const [k, v] of Object.entries(json.errors)) flat[k] = Array.isArray(v) ? v[0] : v;
                        this.errors = flat;
                        this.showToast('error', 'Please fix the errors below.');
                    } else {
                        this.showToast('error', json.message ?? 'Something went wrong.');
                    }
                    return;
                }
                this.closeModal();
                await this.loadGroups();
                window._permsTable?.ajax.reload(null, false);
                _showGlobalToast('success', json.message ?? 'Saved.');
            } catch (e) {
                this.showToast('error', 'Network error.');
            } finally {
                this.saving = false;
            }
        },

        initTable() {
            const CSRF = document.querySelector('meta[name="csrf-token"]').content;
            const self = this;

            if ($.fn.DataTable.isDataTable('#perms-table')) $('#perms-table').DataTable().destroy();

            const table = $('#perms-table').DataTable({
                serverSide: true, processing: true,
                ajax: {
                    url: '{{ route("settings.permissions.data") }}',
                    data(d) {
                        d.guard = $('#filter-guard').val();
                        d.group = $('#filter-group').val();
                    },
                },
                columns: [
                    {
                        data: 'id', className: 'td-center text-stone-400 text-xs',
                        render(v, t, row, meta) { return meta.row + 1; },
                        orderable: false,
                    },
                    { data: 'name', render: v => `<span class="font-medium text-stone-800">${v}</span>` },
                    {
                        data: 'group', orderable: false,
                        render(v) {
                            if (!v) return '<span class="text-stone-400">—</span>';
                            return `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700">${v}</span>`;
                        }
                    },
                    { data: 'guard_name', render: v => `<code class="text-xs bg-stone-100 px-1.5 py-0.5 rounded font-mono text-stone-600">${v}</code>` },
                    { data: 'roles_count', className: 'td-center', render: v => `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-purple-50 text-purple-700">${v}</span>` },
                    { data: 'users_count', className: 'td-center', render: v => `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-700">${v}</span>` },
                    {
                        data: 'id', orderable: false, searchable: false, className: 'td-center',
                        render(id) {
                            return `<div class="act-group justify-center">
                                <button class="act-btn act-edit btn-edit" data-id="${id}" title="Edit">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button class="act-btn act-delete btn-delete" data-id="${id}" title="Delete">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>`;
                        }
                    },
                ],
                order: [[2, 'asc'], [1, 'asc']], pageLength: 10, pagingType: 'simple_numbers',
                dom: '<"top"lf>t<"bottom"ip>',
                language: {
                    emptyTable: '<div class="py-12 text-center text-sm text-stone-400">No permissions found</div>',
                },
            });

            window._permsTable = table;

            $('#filter-guard, #filter-group').on('change', () => table.ajax.reload(null, false));

            $('#perms-table').on('click', '.btn-edit', async function () {
                await self.openModal($(this).data('id'));
            });

            $('#perms-table').on('click', '.btn-delete', async function () {
                if (!confirm('Delete this permission?')) return;
                const id  = $(this).data('id');
                const res = await fetch(`/settings/permissions/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                });
                const json = await res.json();
                json.success
                    ? (table.ajax.reload(null, false), _showGlobalToast('success', json.message))
                    : _showGlobalToast('error', json.message);
            });
        },
    };
}

window._showGlobalToast = function (type, message) {
    const el = document.createElement('div');
    el.className = `fixed bottom-5 right-5 z-[999] px-4 py-3 rounded-xl border text-sm font-medium flex items-center gap-2 shadow-lg ${type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'}`;
    el.innerHTML = `<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${type === 'error' ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'}"/></svg><span>${message}</span>`;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 3500);
};
</script>
@endpush
