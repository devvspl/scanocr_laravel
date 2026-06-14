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
        <div class="px-5 py-3.5 border-b border-stone-100 flex items-center justify-between shrink-0">
            <div>
                <h3 class="text-sm font-semibold text-stone-800">Permissions & Access</h3>
                <p class="text-xs text-stone-400 mt-0.5">
                    Managing access for <span class="font-medium text-stone-600" x-text="selectedUser?.name"></span>
                </p>
            </div>
            <div class="flex items-center gap-2">
                {{-- Select/Deselect All — only shown on permission group tabs --}}
                <template x-if="!['__company','__doc_access'].includes(activePermGroup)">
                    <div class="flex items-center gap-2">
                        <button @click="toggleAllPerms(true)"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-stone-300 text-stone-600 hover:bg-stone-50 text-xs font-medium transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Select All
                        </button>
                        <button @click="toggleAllPerms(false)"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-stone-300 text-stone-600 hover:bg-stone-50 text-xs font-medium transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Deselect All
                        </button>
                    </div>
                </template>
                <button @click="permsOpen = false" class="w-8 h-8 flex items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        {{-- Body: left nav + right detail --}}
        <div class="flex flex-1 overflow-hidden">

            {{-- ── Left sidebar ── --}}
            <div class="w-52 shrink-0 border-r border-stone-100 flex flex-col bg-stone-50 overflow-y-auto">

                {{-- 1. Company --}}
                <div class="px-3 py-2 shrink-0 border-b border-stone-100">
                    <p class="text-[10px] font-bold text-stone-400 uppercase tracking-widest">Company</p>
                </div>
                <button
                    @click="activePermGroup = '__company'; loadCompanies()"
                    :class="activePermGroup === '__company'
                        ? 'bg-white border-r-2 border-red-700 text-red-700 font-semibold'
                        : 'text-stone-600 hover:bg-stone-100 hover:text-stone-800'"
                    class="w-full flex items-center justify-between gap-2 px-4 py-2.5 text-xs text-left transition-colors shrink-0">
                    <div class="flex items-center gap-2 truncate">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <span>Company</span>
                    </div>
                    <span class="shrink-0 text-[10px] font-mono px-1.5 py-0.5 rounded-full"
                          :class="loadingCompanies
                            ? 'bg-stone-100 text-stone-400'
                            : companies.filter(c => c.has_access).length > 0
                                ? 'bg-red-100 text-red-700'
                                : 'bg-stone-200 text-stone-500'"
                          x-text="loadingCompanies ? '…' : (companies.filter(c => c.has_access).length + '/' + companies.length)">
                    </span>
                </button>

                {{-- 2. Access Control (Document Types) --}}
                <div class="px-3 py-2 shrink-0 border-y border-stone-100 mt-1">
                    <p class="text-[10px] font-bold text-stone-400 uppercase tracking-widest">Access Control</p>
                </div>
                <button
                    @click="activePermGroup = '__doc_access'; loadDocTypes()"
                    :class="activePermGroup === '__doc_access'
                        ? 'bg-white border-r-2 border-red-700 text-red-700 font-semibold'
                        : 'text-stone-600 hover:bg-stone-100 hover:text-stone-800'"
                    class="w-full flex items-center justify-between gap-2 px-4 py-2.5 text-xs text-left transition-colors shrink-0">
                    <div class="flex items-center gap-2 truncate">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Document Types</span>
                    </div>
                    <span class="shrink-0 text-[10px] font-mono px-1.5 py-0.5 rounded-full"
                          :class="loadingDocTypes
                            ? 'bg-stone-100 text-stone-400'
                            : docTypes.filter(d => d.can_view).length > 0
                                ? 'bg-red-100 text-red-700'
                                : 'bg-stone-200 text-stone-500'"
                          x-text="loadingDocTypes ? '…' : (docTypes.filter(d => d.can_view).length + '/' + docTypes.length)">
                    </span>
                </button>

                {{-- Location Access — only for Bill Approval users --}}
                <template x-if="hasBillApprovalRole">
                    <div>
                        <div class="px-3 py-2 shrink-0 border-y border-stone-100 mt-1">
                            <p class="text-[10px] font-bold text-stone-400 uppercase tracking-widest">Location Access</p>
                        </div>
                        <button
                            @click="activePermGroup = '__location'; loadLocations()"
                            :class="activePermGroup === '__location'
                                ? 'bg-white border-r-2 border-red-700 text-red-700 font-semibold'
                                : 'text-stone-600 hover:bg-stone-100 hover:text-stone-800'"
                            class="w-full flex items-center justify-between gap-2 px-4 py-2.5 text-xs text-left transition-colors shrink-0">
                            <div class="flex items-center gap-2 truncate">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span>Locations</span>
                            </div>
                            <span class="shrink-0 text-[10px] font-mono px-1.5 py-0.5 rounded-full"
                                  :class="loadingLocations
                                    ? 'bg-stone-100 text-stone-400'
                                    : locations.filter(l => l.has_access).length > 0
                                        ? 'bg-red-100 text-red-700'
                                        : 'bg-stone-200 text-stone-500'"
                                  x-text="loadingLocations ? '…' : (locations.filter(l => l.has_access).length + '/' + locations.length)">
                            </span>
                        </button>
                    </div>
                </template>

                {{-- 3. Permissions --}}
                <div class="px-3 py-2 shrink-0 border-y border-stone-100 mt-1">
                    <p class="text-[10px] font-bold text-stone-400 uppercase tracking-widest">Permissions</p>
                </div>
                <nav class="flex-1 py-1 overflow-y-auto">
                    <template x-for="group in permGroups" :key="group.name">
                        <button
                            @click="activePermGroup = group.name; searchPerm = ''"
                            :class="activePermGroup === group.name
                                ? 'bg-white border-r-2 border-red-700 text-red-700 font-semibold'
                                : 'text-stone-600 hover:bg-stone-100 hover:text-stone-800'"
                            class="w-full flex items-center justify-between gap-2 px-4 py-2.5 text-xs text-left transition-colors">
                            <span x-text="group.name" class="truncate"></span>
                            <span class="shrink-0 text-[10px] font-mono px-1.5 py-0.5 rounded-full"
                                  :class="permGroupSelectedCount(group) > 0
                                    ? 'bg-red-100 text-red-700'
                                    : 'bg-stone-200 text-stone-500'"
                                  x-text="permGroupSelectedCount(group) + '/' + group.permissions.length">
                            </span>
                        </button>
                    </template>
                </nav>
            </div>

            {{-- ── Right panel ── --}}
            <div class="flex-1 overflow-y-auto p-5">

                {{-- ── Company tab ── --}}
                <div x-show="activePermGroup === '__company'">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h4 class="text-sm font-semibold text-stone-800">Company Access</h4>
                            <p class="text-xs text-stone-400 mt-0.5">
                                Control which companies
                                <span class="font-medium text-stone-600" x-text="selectedUser?.name"></span>
                                can access
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="setAllCompanyAccess(true)"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-medium transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                All
                            </button>
                            <button @click="setAllCompanyAccess(false)"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-medium transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                None
                            </button>
                        </div>
                    </div>

                    {{-- Search --}}
                    <div class="flex items-center gap-2 bg-stone-50 border border-stone-200 rounded-lg px-3 py-2 mb-3 focus-within:border-red-700 focus-within:ring-1 focus-within:ring-red-700/10 transition">
                        <svg class="w-3.5 h-3.5 text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" x-model="searchCompany" placeholder="Search companies…"
                               class="flex-1 text-xs bg-transparent outline-none border-none p-0 text-stone-700 placeholder-stone-400">
                        <button x-show="searchCompany" @click="searchCompany = ''" class="text-stone-300 hover:text-stone-500 transition shrink-0">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <template x-if="companies.length === 0">
                        <div class="flex items-center justify-center h-32 text-sm text-stone-400">
                            <svg class="w-4 h-4 animate-spin mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            Loading…
                        </div>
                    </template>

                    <div class="grid grid-cols-3 gap-2">
                        <template x-for="co in companies.filter(c => !searchCompany || c.name.toLowerCase().includes(searchCompany.toLowerCase()))" :key="co.id">
                            <label class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl border border-stone-200 cursor-pointer hover:bg-stone-50 transition-colors"
                                   :class="co.has_access ? 'bg-red-50 border-red-200' : ''">
                                <button type="button"
                                        @click.prevent="co.has_access = !co.has_access"
                                        :class="co.has_access ? 'bg-red-700' : 'bg-stone-200'"
                                        class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-red-700/30">
                                    <span :class="co.has_access ? 'translate-x-5' : 'translate-x-1'"
                                          class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow-sm transition-transform"></span>
                                </button>
                                <span class="flex-1 text-xs font-medium truncate"
                                      :class="co.has_access ? 'text-stone-800' : 'text-stone-500'"
                                      x-text="co.name">
                                </span>
                                <span x-show="co.is_default"
                                      class="text-[10px] text-stone-400 bg-stone-100 px-1.5 py-0.5 rounded-full shrink-0">
                                    Default
                                </span>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- ── Document Access tab ── --}}
                <div x-show="activePermGroup === '__doc_access'">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h4 class="text-sm font-semibold text-stone-800">Document Type Access</h4>
                            <p class="text-xs text-stone-400 mt-0.5">
                                Control which document types
                                <span class="font-medium text-stone-600" x-text="selectedUser?.name"></span>
                                can view
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="setAllDocAccess(true)"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-medium transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                All
                            </button>
                            <button @click="setAllDocAccess(false)"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-medium transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                None
                            </button>
                        </div>
                    </div>

                    {{-- Search --}}
                    <div class="flex items-center gap-2 bg-stone-50 border border-stone-200 rounded-lg px-3 py-2 mb-3 focus-within:border-red-700 focus-within:ring-1 focus-within:ring-red-700/10 transition">
                        <svg class="w-3.5 h-3.5 text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" x-model="searchDocType" placeholder="Search document types…"
                               class="flex-1 text-xs bg-transparent outline-none border-none p-0 text-stone-700 placeholder-stone-400">
                        <button x-show="searchDocType" @click="searchDocType = ''" class="text-stone-300 hover:text-stone-500 transition shrink-0">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <template x-if="docTypes.length === 0">
                        <div class="flex items-center justify-center h-32 text-sm text-stone-400">
                            <svg class="w-4 h-4 animate-spin mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            Loading…
                        </div>
                    </template>

                    <div class="grid grid-cols-3 gap-2">
                        <template x-for="dt in docTypes.filter(d => !searchDocType || d.label.toLowerCase().includes(searchDocType.toLowerCase()))" :key="dt.id">
                            <label class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl border border-stone-200 cursor-pointer hover:bg-stone-50 transition-colors"
                                   :class="dt.can_view ? 'bg-red-50 border-red-200' : ''"
                                   :style="!dt.is_active ? 'opacity:0.5' : ''">
                                <button type="button"
                                        @click.prevent="dt.can_view = !dt.can_view"
                                        :class="dt.can_view ? 'bg-red-700' : 'bg-stone-200'"
                                        class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-red-700/30">
                                    <span :class="dt.can_view ? 'translate-x-5' : 'translate-x-1'"
                                          class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow-sm transition-transform"></span>
                                </button>
                                <span class="flex-1 text-xs font-medium truncate"
                                      :class="dt.can_view ? 'text-stone-800' : 'text-stone-500'"
                                      x-text="dt.label">
                                </span>
                                <span x-show="!dt.is_active"
                                      class="text-[10px] text-stone-400 bg-stone-100 px-1 py-0.5 rounded-full shrink-0">
                                    Off
                                </span>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- ── Location Access tab (Bill Approval users only) ── --}}
                <div x-show="activePermGroup === '__location'">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h4 class="text-sm font-semibold text-stone-800">Location Access</h4>
                            <p class="text-xs text-stone-400 mt-0.5">
                                Control which locations
                                <span class="font-medium text-stone-600" x-text="selectedUser?.name"></span>
                                can approve bills for
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="setAllLocationAccess(true)"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-medium transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                All
                            </button>
                            <button @click="setAllLocationAccess(false)"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-medium transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                None
                            </button>
                        </div>
                    </div>

                    {{-- Search --}}
                    <div class="flex items-center gap-2 bg-stone-50 border border-stone-200 rounded-lg px-3 py-2 mb-3 focus-within:border-red-700 focus-within:ring-1 focus-within:ring-red-700/10 transition">
                        <svg class="w-3.5 h-3.5 text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" x-model="searchLocation" placeholder="Search locations…"
                               class="flex-1 text-xs bg-transparent outline-none border-none p-0 text-stone-700 placeholder-stone-400">
                        <button x-show="searchLocation" @click="searchLocation = ''" class="text-stone-300 hover:text-stone-500 transition shrink-0">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <template x-if="locations.length === 0">
                        <div class="flex items-center justify-center h-32 text-sm text-stone-400">
                            <svg class="w-4 h-4 animate-spin mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            Loading…
                        </div>
                    </template>

                    <div class="grid grid-cols-3 gap-2">
                        <template x-for="loc in locations.filter(l => !searchLocation || l.name.toLowerCase().includes(searchLocation.toLowerCase()))" :key="loc.id">
                            <label class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl border border-stone-200 cursor-pointer hover:bg-stone-50 transition-colors"
                                   :class="loc.has_access ? 'bg-red-50 border-red-200' : ''">
                                <button type="button"
                                        @click.prevent="loc.has_access = !loc.has_access"
                                        :class="loc.has_access ? 'bg-red-700' : 'bg-stone-200'"
                                        class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-red-700/30">
                                    <span :class="loc.has_access ? 'translate-x-5' : 'translate-x-1'"
                                          class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow-sm transition-transform"></span>
                                </button>
                                <span class="flex-1 text-xs font-medium truncate"
                                      :class="loc.has_access ? 'text-stone-800' : 'text-stone-500'"
                                      x-text="loc.name">
                                </span>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- ── Permission groups ── --}}
                <template x-if="permGroups.length === 0 && !['__company','__doc_access','__location'].includes(activePermGroup)">
                    <div class="flex items-center justify-center h-40 text-sm text-stone-400">No permissions found</div>
                </template>

                <template x-for="group in permGroups" :key="group.name">
                    <div x-show="activePermGroup === group.name">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h4 class="text-sm font-semibold text-stone-800" x-text="group.name"></h4>
                                <p class="text-xs text-stone-400 mt-0.5"
                                   x-text="permGroupSelectedCount(group) + ' of ' + group.permissions.length + ' permissions selected'">
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="togglePermGroup(group, true)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-medium transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    All
                                </button>
                                <button @click="togglePermGroup(group, false)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-medium transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    None
                                </button>
                            </div>
                        </div>

                        {{-- Search --}}
                        <div class="flex items-center gap-2 bg-stone-50 border border-stone-200 rounded-lg px-3 py-2 mb-3 focus-within:border-red-700 focus-within:ring-1 focus-within:ring-red-700/10 transition">
                            <svg class="w-3.5 h-3.5 text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="text" x-model="searchPerm" placeholder="Search permissions…"
                                   class="flex-1 text-xs bg-transparent outline-none border-none p-0 text-stone-700 placeholder-stone-400">
                            <button x-show="searchPerm" @click="searchPerm = ''" class="text-stone-300 hover:text-stone-500 transition shrink-0">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <template x-for="perm in group.permissions.filter(p => !searchPerm || p.name.toLowerCase().includes(searchPerm.toLowerCase()))" :key="perm.id">
                                <label class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl border border-stone-200 cursor-pointer hover:bg-stone-50 transition-colors"
                                       :class="selectedPerms.includes(perm.id) ? 'bg-red-50 border-red-200' : ''">
                                    <button type="button"
                                            @click.prevent="togglePerm(perm.id, !selectedPerms.includes(perm.id))"
                                            :class="selectedPerms.includes(perm.id) ? 'bg-red-700' : 'bg-stone-200'"
                                            class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-red-700/30">
                                        <span :class="selectedPerms.includes(perm.id) ? 'translate-x-5' : 'translate-x-1'"
                                              class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow-sm transition-transform"></span>
                                    </button>
                                    <span class="flex-1 text-xs font-medium truncate"
                                          :class="selectedPerms.includes(perm.id) ? 'text-stone-800' : 'text-stone-500'"
                                          x-text="perm.name">
                                    </span>
                                </label>
                            </template>
                        </div>
                    </div>
                </template>

            </div>

        </div>

        {{-- Footer --}}
        <div class="px-5 py-3.5 border-t border-stone-100 flex items-center justify-between gap-3 shrink-0 bg-stone-50">
            {{-- Company count --}}
            <p class="text-xs text-stone-400" x-show="activePermGroup === '__company'">
                <span class="font-semibold text-stone-600" x-text="companies.filter(c => c.has_access).length"></span>
                of <span x-text="companies.length"></span> companies accessible
            </p>
            {{-- Doc access count --}}
            <p class="text-xs text-stone-400" x-show="activePermGroup === '__doc_access'">
                <span class="font-semibold text-stone-600" x-text="docTypes.filter(d => d.can_view).length"></span>
                of <span x-text="docTypes.length"></span> document types accessible
            </p>
            {{-- Location access count --}}
            <p class="text-xs text-stone-400" x-show="activePermGroup === '__location'">
                <span class="font-semibold text-stone-600" x-text="locations.filter(l => l.has_access).length"></span>
                of <span x-text="locations.length"></span> locations accessible
            </p>
            {{-- Permissions count --}}
            <p class="text-xs text-stone-400" x-show="!['__company','__doc_access','__location'].includes(activePermGroup)">
                <span class="font-semibold text-stone-600" x-text="selectedPerms.length"></span>
                direct permission<span x-show="selectedPerms.length !== 1">s</span> assigned
            </p>
            <div class="flex items-center gap-2">
                <button @click="permsOpen = false" class="tb-btn tb-btn-edit">Cancel</button>
                <button @click="saveAll()" :disabled="saving"
                        class="inline-flex items-center gap-2 px-5 py-2 rounded-lg bg-red-800 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-semibold transition-colors">
                    <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save All
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
        docAccessOpen: false,
        docAccessTab: 'types',
        docTypes: [],
        companies: [],
        locations: [],
        hasBillApprovalRole: false,
        loadingCompanies: false,
        loadingDocTypes: false,
        loadingLocations: false,
        loadingPerms: false,
        editId: null,
        saving: false,
        mainUsers: [],
        selectedUser: null,
        selectedUserRoles: [],
        selectedPerms: [],
        permGroups: [],
        activePermGroup: '',
        searchCompany: '',
        searchDocType: '',
        searchLocation: '',
        searchPerm: '',
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
            // Guard: prevent multiple concurrent loads
            if (this.loadingPerms) return;
            this.loadingPerms = true;

            try {
                const res  = await fetch(`/settings/users/${id}/permissions`, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.selectedUser        = data.user;
                this.selectedPerms       = data.directPerms ?? [];
                this.permGroups          = Object.entries(data.allPermissions).map(([name, perms]) => ({ name, permissions: perms }));
                this.hasBillApprovalRole = (data.userRoles ?? []).includes('Bill Approval');
                this.activePermGroup     = '__company';
                this.docTypes            = [];
                this.companies           = [];
                this.locations           = [];
                this.loadingCompanies    = true;
                this.loadingDocTypes     = true;
                this.loadingLocations    = true;
                this.searchCompany       = '';
                this.searchDocType       = '';
                this.searchLocation      = '';
                this.searchPerm          = '';
                this.permsOpen           = true;

                // Pre-load all access data in parallel so badges show correct counts immediately
                const uid = this.selectedUser.id;
                const fetches = [
                    fetch(`/settings/users/${uid}/company-access`,  { headers: { 'Accept': 'application/json' } })
                        .then(r => r.json()).then(d => { this.companies = d.companies ?? []; this.loadingCompanies = false; }),
                    fetch(`/settings/users/${uid}/document-access`, { headers: { 'Accept': 'application/json' } })
                        .then(r => r.json()).then(d => { this.docTypes  = d.types     ?? []; this.loadingDocTypes  = false; }),
                ];
                if (this.hasBillApprovalRole) {
                    fetches.push(
                        fetch(`/settings/users/${uid}/location-access`, { headers: { 'Accept': 'application/json' } })
                            .then(r => r.json()).then(d => { this.locations = d.locations ?? []; this.loadingLocations = false; })
                    );
                } else {
                    this.loadingLocations = false;
                }
                await Promise.all(fetches);
            } catch (e) {
                _showGlobalToast('error', 'Failed to load permissions.');
            } finally {
                this.loadingPerms = false;
            }
        },

        async loadDocTypes() {
            if (this.docTypes.length > 0 || !this.selectedUser) return;
            this.loadingDocTypes = true;
            const res  = await fetch(`/settings/users/${this.selectedUser.id}/document-access`, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            this.docTypes        = data.types ?? [];
            this.loadingDocTypes = false;
        },

        async loadCompanies() {
            if (this.companies.length > 0 || !this.selectedUser) return;
            this.loadingCompanies = true;
            const res = await fetch(`/settings/users/${this.selectedUser.id}/company-access`, {
                headers: { 'Accept': 'application/json' }
            });
            if (res.ok) {
                const data        = await res.json();
                this.companies    = data.companies ?? [];
            }
            this.loadingCompanies = false;
        },

        async loadLocations() {
            if (this.locations.length > 0 || !this.selectedUser) return;
            this.loadingLocations = true;
            const res = await fetch(`/settings/users/${this.selectedUser.id}/location-access`, {
                headers: { 'Accept': 'application/json' }
            });
            if (res.ok) {
                const data        = await res.json();
                this.locations    = data.locations ?? [];
            }
            this.loadingLocations = false;
        },

        setAllLocationAccess(val) {
            this.locations = this.locations.map(l => ({ ...l, has_access: val }));
        },

        setAllDocAccess(val) {
            this.docTypes = this.docTypes.map(d => ({ ...d, can_view: val }));
        },

        setAllCompanyAccess(val) {
            this.companies = this.companies.map(c => ({ ...c, has_access: val }));
        },

        togglePerm(id, checked) {
            if (checked) {
                if (!this.selectedPerms.includes(id)) this.selectedPerms.push(id);
            } else {
                this.selectedPerms = this.selectedPerms.filter(p => p !== id);
            }
        },

        permGroupSelectedCount(group) {
            return group.permissions.filter(p => this.selectedPerms.includes(p.id)).length;
        },

        togglePermGroup(group, checked) {
            group.permissions.forEach(p => this.togglePerm(p.id, checked));
        },

        toggleAllPerms(checked) {
            this.permGroups.forEach(g => this.togglePermGroup(g, checked));
        },

        async saveAll() {
            this.saving = true;
            try {
                const CSRF = document.querySelector('meta[name="csrf-token"]').content;
                const headers = { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' };
                const uid = this.selectedUser.id;

                const promises = [
                    // Always save permissions
                    fetch(`/settings/users/${uid}/permissions`, {
                        method: 'PUT', headers,
                        body: JSON.stringify({ permissions: this.selectedPerms }),
                    }),
                ];

                // Save doc access if loaded
                if (this.docTypes.length > 0) {
                    promises.push(fetch(`/settings/users/${uid}/document-access`, {
                        method: 'PUT', headers,
                        body: JSON.stringify({ access: this.docTypes.map(d => ({ id: d.id, can_view: d.can_view })) }),
                    }));
                }

                // Save company access if loaded
                if (this.companies.length > 0) {
                    promises.push(fetch(`/settings/users/${uid}/company-access`, {
                        method: 'PUT', headers,
                        body: JSON.stringify({ access: this.companies.map(c => ({ id: c.id, has_access: c.has_access })) }),
                    }));
                }

                // Save location access if loaded
                if (this.locations.length > 0) {
                    promises.push(fetch(`/settings/users/${uid}/location-access`, {
                        method: 'PUT', headers,
                        body: JSON.stringify({ access: this.locations.map(l => ({ id: l.id, has_access: l.has_access })) }),
                    }));
                }

                const results = await Promise.all(promises);
                const jsons   = await Promise.all(results.map(r => r.json()));
                const allOk   = jsons.every(j => j.success);

                if (allOk) {
                    this.permsOpen = false;
                    _showGlobalToast('success', 'Permissions & access saved.');
                } else {
                    _showGlobalToast('error', jsons.find(j => !j.success)?.message ?? 'Something went wrong.');
                }
            } catch (e) {
                _showGlobalToast('error', 'Network error.');
            } finally {
                this.saving = false;
            }
        },

        setAllDocAccess(val) {
            this.docTypes = this.docTypes.map(d => ({ ...d, can_view: val }));
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
                                <button class="act-btn act-edit btn-perms" data-id="${id}" title="Permissions & Access" style="color:#0369a1">
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

            $('#users-table').on('click', '.btn-perms', function () {
                const id = $(this).data('id');
                self.openPermsPanel(id);   // guard inside prevents concurrent calls
            });

            $('#users-table').on('click', '.btn-doc-access', async function () {
                await self.openDocAccessPanel($(this).data('id'));
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
