@extends('layouts.app')

@section('title', 'Users')
@section('page-title', 'Users')

@section('content')
<div x-data="usersPage()" x-init="init()">

    @include('panel.settings._access-nav')

    {{-- Table card --}}
    <div class="bg-white border border-stone-200 rounded-1xl overflow-hidden">

        {{-- Toolbar --}}
        <div class="px-4 py-2.5 border-b border-stone-100 flex items-center justify-between gap-2 min-h-[48px]">
            <div class="flex items-center gap-2">
                <select id="filter-type" class="h-7 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10">
                    <option value="">All Users</option>
                    <option value="main">Main Users</option>
                    <option value="sub">Sub Users</option>
                </select>
                <select id="filter-status" class="h-7 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <button @click="openPanel()" class="tb-btn tb-btn-add">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Add User
            </button>
        </div>

        <div class="overflow-x-auto">
            <table id="users-table" class="w-full">
                <thead><tr>
                    <th class="td-center" style="width:50px;">#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Type</th>
                    <th>Parent User</th>
                    <th>Roles</th>
                    <th class="td-center">Status</th>
                    <th class="td-center" style="width:100px;">Actions</th>
                </tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    {{-- ── User offcanvas panel ── --}}
    <div x-show="panelOpen"
         x-transition:enter="transition-transform duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition-transform duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed top-0 right-0 h-full w-full max-w-2xl bg-white shadow-2xl z-50 flex flex-col"
         style="display:none">

        {{-- Header --}}
        <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between shrink-0">
            <div>
                <h3 class="text-sm font-semibold text-stone-800" x-text="editId ? 'Edit User' : 'Add User'"></h3>
                <p class="text-xs text-stone-400 mt-0.5" x-text="editId ? 'Update user details' : 'Fill in the user information'"></p>
            </div>
            <button @click="closePanel()" class="w-8 h-8 flex items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Toast --}}
        <div x-show="toast.show" x-transition
             :class="toast.type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'"
             class="mx-5 mt-4 px-4 py-3 rounded-xl border text-sm font-medium flex items-center gap-2 shrink-0"
             style="display:none">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      x-bind:d="toast.type === 'error' ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'"/>
            </svg>
            <span x-text="toast.message"></span>
        </div>

        {{-- Scrollable body --}}
        <div class="flex-1 overflow-y-auto p-5 space-y-5">

            {{-- Identity --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 sm:col-span-1">
                    <label class="form-label">Name <span class="text-red-500">*</span></label>
                    <input type="text" x-model="form.name" placeholder="Full name"
                           class="form-input" :class="errors.name ? 'border-red-400' : ''">
                    <p x-show="errors.name" x-text="errors.name" class="form-error"></p>
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <label class="form-label">Email <span class="text-red-500">*</span></label>
                    <input type="email" x-model="form.email" placeholder="user@example.com"
                           class="form-input" :class="errors.email ? 'border-red-400' : ''">
                    <p x-show="errors.email" x-text="errors.email" class="form-error"></p>
                </div>
                <div>
                    <label class="form-label">Phone</label>
                    <input type="text" x-model="form.phone" placeholder="+91 98765 43210" class="form-input">
                </div>
                <div>
                    <label class="form-label">Designation</label>
                    <input type="text" x-model="form.designation" placeholder="e.g. Manager" class="form-input">
                </div>
                <div>
                    <label class="form-label">Department</label>
                    <input type="text" x-model="form.department" placeholder="e.g. Finance" class="form-input">
                </div>
                <div>
                    <label class="form-label">Parent User</label>
                    <select x-model="form.parent_id" class="form-input">
                        <option value="">— None (Main User) —</option>
                        <template x-for="u in mainUsers" :key="u.id">
                            <option :value="u.id" x-text="u.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="form-label">
                        Password
                        <span x-show="!editId" class="text-red-500">*</span>
                    </label>
                    <input type="password" x-model="form.password" placeholder="Min 8 characters"
                           class="form-input" :class="errors.password ? 'border-red-400' : ''">
                    <p x-show="editId" class="text-[10px] text-stone-400 mt-1">Leave blank to keep current password</p>
                    <p x-show="errors.password" x-text="errors.password" class="form-error"></p>
                </div>
                <div>
                    <label class="form-label">
                        Confirm Password
                        <span x-show="!editId" class="text-red-500">*</span>
                    </label>
                    <input type="password" x-model="form.password_confirmation" placeholder="Repeat password" class="form-input">
                </div>
            </div>

            {{-- Roles --}}
            <div>
                <label class="form-label mb-2">Roles</label>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($roles as $role)
                    <label class="flex items-center gap-2.5 cursor-pointer px-3 py-2.5 rounded-lg border border-stone-200 hover:bg-stone-50 transition-colors">
                        <input type="checkbox" value="{{ $role->id }}" x-model="form.roles"
                               class="w-3.5 h-3.5 rounded border-stone-300 text-red-700 focus:ring-red-700">
                        <span class="text-xs font-medium text-stone-700">{{ $role->name }}</span>
                        @if($role->permissions->count() > 0)
                        <span class="ml-auto text-[10px] text-stone-400">{{ $role->permissions->count() }} perms</span>
                        @endif
                    </label>
                    @endforeach
                </div>
            </div>

        </div>

        {{-- Footer --}}
        <div class="px-5 py-4 border-t border-stone-100 flex items-center justify-between gap-3 shrink-0 bg-stone-50">
            <div class="flex items-center gap-3">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="form.is_active" class="sr-only peer">
                    <div class="w-9 h-5 bg-stone-200 peer-focus:ring-2 peer-focus:ring-red-700/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-stone-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-red-700"></div>
                </label>
                <span class="text-sm text-stone-600 font-medium">Active</span>
            </div>
            <div class="flex items-center gap-2">
                <button @click="closePanel()" class="tb-btn tb-btn-edit">Cancel</button>
                <button @click="save()" :disabled="saving"
                        class="inline-flex items-center gap-2 px-5 py-2 rounded-lg bg-red-800 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-semibold transition-colors">
                    <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span x-text="editId ? 'Save Changes' : 'Create User'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Panel overlay --}}
    <div x-show="panelOpen"
         x-transition:enter="transition-opacity duration-300"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-200"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click="closePanel()"
         class="fixed inset-0 bg-black/40 backdrop-blur-sm z-40"
         style="display:none">
    </div>

    {{-- ── Roles offcanvas panel ── --}}
    <div x-show="rolesOpen"
         x-transition:enter="transition-transform duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition-transform duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed top-0 right-0 h-full w-full max-w-md bg-white shadow-2xl z-50 flex flex-col"
         style="display:none">

        <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between shrink-0">
            <div>
                <h3 class="text-sm font-semibold text-stone-800">Manage Roles</h3>
                <p class="text-xs text-stone-400 mt-0.5" x-text="selectedUser?.name"></p>
            </div>
            <button @click="rolesOpen = false" class="w-8 h-8 flex items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-5 space-y-2">
            @foreach($roles as $role)
            <label class="flex items-center gap-3 cursor-pointer px-3 py-3 rounded-lg border border-stone-200 hover:bg-stone-50 transition-colors">
                <input type="checkbox" value="{{ $role->id }}" x-model="selectedUserRoles"
                       class="w-3.5 h-3.5 rounded border-stone-300 text-red-700 focus:ring-red-700">
                <div class="flex-1">
                    <span class="text-sm font-medium text-stone-700">{{ $role->name }}</span>
                    @if($role->permissions->count() > 0)
                    <p class="text-[10px] text-stone-400 mt-0.5">{{ $role->permissions->count() }} permissions</p>
                    @endif
                </div>
            </label>
            @endforeach
        </div>

        <div class="px-5 py-4 border-t border-stone-100 flex items-center justify-end gap-2 shrink-0 bg-stone-50">
            <button @click="rolesOpen = false" class="tb-btn tb-btn-edit">Cancel</button>
            <button @click="saveRoles()" :disabled="saving"
                    class="inline-flex items-center gap-2 px-5 py-2 rounded-lg bg-red-800 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-semibold transition-colors">
                <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Save Roles
            </button>
        </div>
    </div>

    {{-- Roles overlay --}}
    <div x-show="rolesOpen"
         x-transition:enter="transition-opacity duration-300"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-200"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click="rolesOpen = false"
         class="fixed inset-0 bg-black/40 backdrop-blur-sm z-40"
         style="display:none">
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
                <h3 class="text-sm font-semibold text-stone-800">Additional Permissions</h3>
                <p class="text-xs text-stone-400 mt-0.5">
                    Direct permissions for <span class="font-medium text-stone-600" x-text="selectedUser?.name"></span>
                    — these are <em>in addition to</em> role permissions
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="toggleAllPerms(true)"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-stone-300 text-stone-600 hover:bg-stone-50 text-xs font-medium transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Select All
                </button>
                <button @click="toggleAllPerms(false)"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-stone-300 text-stone-600 hover:bg-stone-50 text-xs font-medium transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Deselect All
                </button>
                <button @click="permsOpen = false" class="w-8 h-8 flex items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        {{-- Scrollable body --}}
        <div class="flex-1 overflow-y-auto p-6">
            <template x-if="permGroups.length === 0">
                <div class="flex items-center justify-center h-40 text-sm text-stone-400">No permissions found</div>
            </template>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                <template x-for="group in permGroups" :key="group.name">
                    <div class="bg-white border border-stone-200 rounded-xl overflow-hidden">
                        <div class="px-4 py-2.5 bg-stone-50 border-b border-stone-200 flex items-center justify-between">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-stone-600" x-text="group.name"></span>
                            <div class="flex items-center gap-2">
                                <button @click="togglePermGroup(group, true)" class="text-[10px] text-red-700 hover:underline font-medium">All</button>
                                <span class="text-stone-300 text-[10px]">|</span>
                                <button @click="togglePermGroup(group, false)" class="text-[10px] text-stone-500 hover:underline font-medium">None</button>
                            </div>
                        </div>
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
                <span class="font-semibold text-stone-600" x-text="selectedPerms.length"></span>
                direct permission<span x-show="selectedPerms.length !== 1">s</span> assigned
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
function usersPage() {
    return {
        panelOpen: false,
        rolesOpen: false,
        permsOpen: false,
        editId: null,
        saving: false,
        mainUsers: [],
        selectedUser: null,
        selectedUserRoles: [],
        selectedPerms: [],
        permGroups: [],
        toast: { show: false, type: 'success', message: '' },
        errors: {},
        form: {},

        init() {
            this.resetForm();
            this.loadMainUsers();
            this.initTable();
        },

        resetForm() {
            this.form = {
                name: '', email: '', phone: '', designation: '',
                department: '', parent_id: '', password: '',
                password_confirmation: '', roles: [], is_active: true
            };
        },

        async loadMainUsers() {
            try {
                const res  = await fetch('{{ route("settings.users.data") }}?type=main&length=500', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                this.mainUsers = data.data || [];
            } catch (e) {}
        },

        async openPanel(id = null) {
            this.errors = {};
            this.toast  = { show: false, type: 'success', message: '' };
            if (id) {
                this.editId = id;
                const res  = await fetch(`/settings/users/${id}`, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.form  = {
                    name:                  data.name                  ?? '',
                    email:                 data.email                 ?? '',
                    phone:                 data.phone                 ?? '',
                    designation:           data.designation           ?? '',
                    department:            data.department            ?? '',
                    parent_id:             data.parent_id             ?? '',
                    password:              '',
                    password_confirmation: '',
                    roles:                 data.roles?.map(r => r.id) ?? [],
                    is_active:             data.is_active             ?? true,
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
            const url    = this.editId ? `/settings/users/${this.editId}` : '/settings/users';
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
                window._usersTable?.ajax.reload(null, false);
                this.loadMainUsers();
                _showGlobalToast('success', json.message ?? 'Saved.');
            } catch (e) {
                this.showToast('error', 'Network error.');
            } finally {
                this.saving = false;
            }
        },

        async openRolesPanel(id) {
            const res  = await fetch(`/settings/users/${id}/roles`, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            this.selectedUser      = data.user;
            this.selectedUserRoles = data.userRoles ?? [];
            this.rolesOpen         = true;
        },

        async saveRoles() {
            this.saving = true;
            try {
                const res  = await fetch(`/settings/users/${this.selectedUser.id}/roles`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ roles: this.selectedUserRoles }),
                });
                const json = await res.json();
                if (json.success) {
                    this.rolesOpen = false;
                    window._usersTable?.ajax.reload(null, false);
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

        async openPermsPanel(id) {
            const res  = await fetch(`/settings/users/${id}/permissions`, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            this.selectedUser  = data.user;
            this.selectedPerms = data.directPerms ?? [];
            this.permGroups    = Object.entries(data.allPermissions).map(([name, perms]) => ({ name, permissions: perms }));
            this.permsOpen     = true;
        },

        togglePerm(id, checked) {
            if (checked) {
                if (!this.selectedPerms.includes(id)) this.selectedPerms.push(id);
            } else {
                this.selectedPerms = this.selectedPerms.filter(p => p !== id);
            }
        },

        togglePermGroup(group, checked) {
            group.permissions.forEach(p => this.togglePerm(p.id, checked));
        },

        toggleAllPerms(checked) {
            this.permGroups.forEach(g => this.togglePermGroup(g, checked));
        },

        async savePerms() {
            this.saving = true;
            try {
                const res  = await fetch(`/settings/users/${this.selectedUser.id}/permissions`, {
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

            if ($.fn.DataTable.isDataTable('#users-table')) $('#users-table').DataTable().destroy();

            const table = $('#users-table').DataTable({
                serverSide: true, processing: true,
                ajax: {
                    url: '{{ route("settings.users.data") }}',
                    data(d) {
                        d.type   = $('#filter-type').val();
                        d.status = $('#filter-status').val();
                    },
                },
                columns: [
                    {
                        data: 'id', className: 'td-center text-stone-400 text-xs',
                        render(v, t, row, meta) { return meta.row + 1; },
                        orderable: false,
                    },
                    {
                        data: 'name',
                        render(v, t, row) {
                            const sub = row.parent_id
                                ? '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-50 text-blue-600 uppercase tracking-wide ml-1">Sub</span>'
                                : '';
                            return `<span class="font-medium text-stone-800">${v}</span>${sub}`;
                        }
                    },
                    { data: 'email', render: v => `<span class="text-xs text-stone-500">${v}</span>` },
                    {
                        data: 'user_type', orderable: false,
                        render(v) {
                            const isMain = v === 'Main User';
                            return `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold ${isMain ? 'bg-purple-50 text-purple-700' : 'bg-blue-50 text-blue-700'}">${v}</span>`;
                        }
                    },
                    {
                        data: 'parent_name', orderable: false,
                        render: v => v && v !== '-'
                            ? `<span class="text-xs text-stone-600">${v}</span>`
                            : '<span class="text-stone-400">—</span>'
                    },
                    {
                        data: 'roles_list', orderable: false,
                        render(v) {
                            if (!v || v === 'No roles assigned') return '<span class="text-stone-400 text-xs">No roles</span>';
                            return v.split(', ').map(r =>
                                `<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-stone-100 text-stone-600 mr-1">${r}</span>`
                            ).join('');
                        }
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
                                <button class="act-btn act-edit btn-perms" data-id="${id}" title="Manage Permissions" style="color:#0369a1">
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
                    emptyTable: '<div class="py-12 text-center text-sm text-stone-400">No users found</div>',
                },
            });

            window._usersTable = table;

            $('#filter-type, #filter-status').on('change', () => table.ajax.reload(null, false));

            $('#users-table').on('click', '.btn-edit', async function () {
                await self.openPanel($(this).data('id'));
            });

            $('#users-table').on('click', '.btn-roles', async function () {
                await self.openRolesPanel($(this).data('id'));
            });

            $('#users-table').on('click', '.btn-perms', async function () {
                await self.openPermsPanel($(this).data('id'));
            });

            $('#users-table').on('click', '.btn-delete', async function () {
                if (!confirm('Delete this user?')) return;
                const id  = $(this).data('id');
                const res = await fetch(`/settings/users/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                });
                const json = await res.json();
                json.success
                    ? (table.ajax.reload(null, false), self.loadMainUsers(), _showGlobalToast('success', json.message))
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
