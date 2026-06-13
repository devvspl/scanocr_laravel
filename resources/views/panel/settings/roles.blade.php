@extends('layouts.app')

@section('title', 'Roles')
@section('page-title', 'Roles')

@section('content')
<div x-data="rolesPage()" x-init="init()">

    @include('panel.settings._access-nav')

    {{-- Table card --}}
    <div class="bg-white border border-stone-200 rounded-1xl overflow-hidden">

        {{-- Toolbar --}}
        <div class="px-4 py-2.5 border-b border-stone-100 flex items-center justify-between gap-2 min-h-[48px]">
            <div class="flex items-center gap-2">
                <select id="filter-status" class="h-7 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <button @click="openPanel()" class="tb-btn tb-btn-add">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Add Role
            </button>
        </div>

        <div class="overflow-x-auto">
            <table id="roles-table" class="w-full">
                <thead><tr>
                    <th class="td-center" style="width:50px;">#</th>
                    <th>Role Name</th>
                    <th>Guard</th>
                    <th class="td-center">Users</th>
                    <th class="td-center">Permissions</th>
                    <th class="td-center">Status</th>
                    <th class="td-center" style="width:100px;">Actions</th>
                </tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    {{-- ── Role modal ── --}}
    <div x-show="panelOpen"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display:none">

        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closePanel()"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl overflow-hidden" style="width:420px;max-width:calc(100vw - 2rem)"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            {{-- Header --}}
            <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-stone-800" x-text="editId ? 'Edit Role' : 'Add Role'"></h3>
                <button @click="closePanel()" class="act-btn act-edit">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-5">
                {{-- Toast --}}
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
                        <label class="block text-xs font-semibold text-stone-600 mb-1.5">Role Name <span class="text-red-600">*</span></label>
                        <input type="text" x-model="form.name" placeholder="e.g. Accountant"
                               class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors"
                               :class="errors.name ? 'border-red-400' : ''">
                        <p x-show="errors.name" x-text="errors.name" class="mt-1 text-xs text-red-600"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-stone-600 mb-1.5">Guard</label>
                        <select x-model="form.guard_name"
                                class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors">
                            <option value="web">web</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="form.is_active" class="sr-only peer">
                            <div class="w-9 h-5 bg-stone-200 peer-focus:ring-2 peer-focus:ring-red-700/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-stone-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-red-700"></div>
                        </label>
                        <span class="text-sm text-stone-600 font-medium">Active</span>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-5 py-4 border-t border-stone-100 flex items-center justify-end gap-2">
                <button @click="closePanel()" class="tb-btn tb-btn-edit">Cancel</button>
                <button @click="save()" :disabled="saving"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-800 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-semibold transition-colors">
                    <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span x-text="editId ? 'Save Changes' : 'Create Role'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── Permissions full-width offcanvas ── --}}
    <div x-show="permsOpen"
         x-transition:enter="transition-transform duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition-transform duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed top-0 right-0 h-full w-full bg-white shadow-2xl z-50 flex flex-col"
         style="display:none">

        {{-- Header --}}
        <div class="px-6 py-4 border-b border-stone-100 flex items-center justify-between shrink-0">
            <div>
                <h3 class="text-sm font-semibold text-stone-800">Manage Permissions</h3>
                <p class="text-xs text-stone-400 mt-0.5" x-text="selectedRole?.name"></p>
            </div>
            <div class="flex items-center gap-3">
                {{-- Select All / Deselect All --}}
                <button @click="toggleAll(true)"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-stone-300 text-stone-600 hover:bg-stone-50 text-xs font-medium transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Select All
                </button>
                <button @click="toggleAll(false)"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-stone-300 text-stone-600 hover:bg-stone-50 text-xs font-medium transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Deselect All
                </button>
                <button @click="permsOpen = false" class="w-8 h-8 flex items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        {{-- Scrollable body — permission groups grid --}}
        <div class="flex-1 overflow-y-auto p-6">
            <template x-if="permGroups.length === 0">
                <div class="flex items-center justify-center h-40 text-sm text-stone-400">No permissions found</div>
            </template>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                <template x-for="group in permGroups" :key="group.name">
                    <div class="bg-white border border-stone-200 rounded-xl overflow-hidden">
                        {{-- Group header --}}
                        <div class="px-4 py-2.5 bg-stone-50 border-b border-stone-200 flex items-center justify-between">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-stone-600" x-text="group.name"></span>
                            <div class="flex items-center gap-2">
                                <button @click="toggleGroup(group, true)"
                                        class="text-[10px] text-red-700 hover:underline font-medium">All</button>
                                <span class="text-stone-300 text-[10px]">|</span>
                                <button @click="toggleGroup(group, false)"
                                        class="text-[10px] text-stone-500 hover:underline font-medium">None</button>
                            </div>
                        </div>
                        {{-- Permissions list --}}
                        <div class="divide-y divide-stone-100">
                            <template x-for="perm in group.permissions" :key="perm.id">
                                <label class="flex items-center gap-2.5 px-4 py-2.5 cursor-pointer hover:bg-stone-50 transition-colors">
                                    <input type="checkbox"
                                           :value="perm.id"
                                           :checked="selectedPerms.includes(perm.id)"
                                           @change="togglePerm(perm.id, $event.target.checked)"
                                           class="w-3.5 h-3.5 rounded border-stone-300 text-red-700 focus:ring-red-700 shrink-0">
                                    <span class="text-xs text-stone-700" x-text="perm.name"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-6 py-4 border-t border-stone-100 flex items-center justify-between gap-3 shrink-0 bg-stone-50">
            <p class="text-xs text-stone-400">
                <span class="font-semibold text-stone-600" x-text="selectedPerms.length"></span> permission<span x-show="selectedPerms.length !== 1">s</span> selected
            </p>
            <div class="flex items-center gap-2">
                <button @click="permsOpen = false" class="tb-btn tb-btn-edit">Cancel</button>
                <button @click="savePerms()" :disabled="saving"
                        class="inline-flex items-center gap-2 px-5 py-2 rounded-lg bg-red-800 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-semibold transition-colors">
                    <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save Permissions
                </button>
            </div>
        </div>
    </div>

    {{-- Permissions overlay --}}
    <div x-show="permsOpen"
         x-transition:enter="transition-opacity duration-300"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-200"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click="permsOpen = false"
         class="fixed inset-0 bg-black/40 backdrop-blur-sm z-40"
         style="display:none">
    </div>

</div>
@endsection

@push('scripts')
<script>
function rolesPage() {
    return {
        panelOpen: false,
        permsOpen: false,
        editId: null,
        saving: false,
        selectedRole: null,
        selectedPerms: [],
        permGroups: [],   // [{ name, permissions: [{id, name}] }]
        toast: { show: false, type: 'success', message: '' },
        errors: {},
        form: { name: '', guard_name: 'web', is_active: true },

        init() {
            this.initTable();
        },

        resetForm() {
            this.form = { name: '', guard_name: 'web', is_active: true };
        },

        async openPanel(id = null) {
            this.errors = {};
            this.toast  = { show: false, type: 'success', message: '' };
            if (id) {
                this.editId = id;
                const res  = await fetch(`/settings/roles/${id}`, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.form  = {
                    name:       data.name       ?? '',
                    guard_name: data.guard_name ?? 'web',
                    is_active:  data.is_active  ?? true,
                };
            } else {
                this.editId = null;
                this.resetForm();
            }
            this.panelOpen = true;
        },

        closePanel() { this.panelOpen = false; },

        showToast(type, message) {
            this.toast = { show: true, type, message };
            setTimeout(() => { this.toast.show = false; }, 3500);
        },

        async save() {
            this.saving = true; this.errors = {};
            const url    = this.editId ? `/settings/roles/${this.editId}` : '/settings/roles';
            const method = this.editId ? 'PUT' : 'POST';
            try {
                const res  = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ ...this.form, is_active: this.form.is_active ? 1 : 0 }),
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
                this.closePanel();
                window._rolesTable?.ajax.reload(null, false);
                _showGlobalToast('success', json.message ?? 'Saved.');
            } catch (e) {
                this.showToast('error', 'Network error.');
            } finally {
                this.saving = false;
            }
        },

        async openPermsPanel(id) {
            const res  = await fetch(`/settings/roles/${id}/permissions`, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            this.selectedRole  = data.role;
            this.selectedPerms = data.rolePermissions ?? [];
            // Build permGroups from allPermissions object {groupName: [{id,name,...}]}
            this.permGroups = Object.entries(data.allPermissions).map(([name, perms]) => ({
                name,
                permissions: perms,
            }));
            this.permsOpen = true;
        },

        togglePerm(id, checked) {
            if (checked) {
                if (!this.selectedPerms.includes(id)) this.selectedPerms.push(id);
            } else {
                this.selectedPerms = this.selectedPerms.filter(p => p !== id);
            }
        },

        toggleGroup(group, checked) {
            group.permissions.forEach(p => this.togglePerm(p.id, checked));
        },

        toggleAll(checked) {
            this.permGroups.forEach(g => this.toggleGroup(g, checked));
        },

        async savePerms() {
            this.saving = true;
            try {
                const res  = await fetch(`/settings/roles/${this.selectedRole.id}/permissions`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ permissions: this.selectedPerms }),
                });
                const json = await res.json();
                if (json.success) {
                    this.permsOpen = false;
                    window._rolesTable?.ajax.reload(null, false);
                    _showGlobalToast('success', json.message);
                } else {
                    _showGlobalToast('error', json.message ?? 'Something went wrong.');
                }
            } catch (e) {
                _showGlobalToast('error', 'Network error.');
            } finally {
                this.saving = false;
            }
        },

        initTable() {
            const CSRF = document.querySelector('meta[name="csrf-token"]').content;
            const self = this;

            if ($.fn.DataTable.isDataTable('#roles-table')) $('#roles-table').DataTable().destroy();

            const table = $('#roles-table').DataTable({
                serverSide: true, processing: true,
                ajax: {
                    url: '{{ route("settings.roles.data") }}',
                    data(d) { d.status = $('#filter-status').val(); },
                },
                columns: [
                    {
                        data: 'id', className: 'td-center text-stone-400 text-xs',
                        render(v, t, row, meta) { return meta.row + 1; },
                        orderable: false,
                    },
                    {
                        data: 'name',
                        render: v => `<span class="font-medium text-stone-800">${v}</span>`
                    },
                    {
                        data: 'guard_name',
                        render: v => `<code class="text-xs bg-stone-100 px-1.5 py-0.5 rounded font-mono text-stone-600">${v}</code>`
                    },
                    {
                        data: 'users_count', className: 'td-center',
                        render: v => `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-700">${v}</span>`
                    },
                    {
                        data: 'permissions_count', className: 'td-center',
                        render: v => `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-purple-50 text-purple-700">${v}</span>`
                    },
                    {
                        data: 'is_active', className: 'td-center',
                        render: v => v
                            ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-50 text-green-700">Active</span>'
                            : '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-stone-100 text-stone-500">Inactive</span>',
                    },
                    {
                        data: 'id', orderable: false, searchable: false, className: 'td-center',
                        render(id) {
                            return `<div class="act-group justify-center">
                                <button class="act-btn act-edit btn-edit" data-id="${id}" title="Edit">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button class="act-btn act-edit btn-perms" data-id="${id}" title="Manage Permissions" style="color:#7c3aed">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                </button>
                                <button class="act-btn act-delete btn-delete" data-id="${id}" title="Delete">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>`;
                        }
                    },
                ],
                order: [[1, 'asc']], pageLength: 25, pagingType: 'simple_numbers',
                dom: '<"top"lf>t<"bottom"ip>',
                language: {
                    emptyTable: '<div class="py-12 text-center text-sm text-stone-400">No roles found</div>',
                },
            });

            window._rolesTable = table;

            $('#filter-status').on('change', () => table.ajax.reload(null, false));

            $('#roles-table').on('click', '.btn-edit', async function () {
                await self.openPanel($(this).data('id'));
            });

            $('#roles-table').on('click', '.btn-perms', async function () {
                await self.openPermsPanel($(this).data('id'));
            });

            $('#roles-table').on('click', '.btn-delete', async function () {
                if (!confirm('Delete this role?')) return;
                const id  = $(this).data('id');
                const res = await fetch(`/settings/roles/${id}`, {
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
