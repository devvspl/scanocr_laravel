@extends('layouts.app')

@section('title', 'Account Groups')
@section('page-title', 'Account Groups')

@section('content')
<div class="mx-auto space-y-4" x-data="accountGroups()" x-init="init()">

    {{-- Tab header --}}
    <div class="bg-white border border-stone-200 rounded-1xl overflow-hidden mb-3">
        <div class="flex gap-1 px-0">
            <a href="{{ route('master.accounts') }}"
               class="flex items-center gap-1.5 px-4 py-3 text-sm transition-colors -mb-px whitespace-nowrap text-stone-500 hover:text-stone-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M12 7h.01M3 5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5z"/>
                </svg>
                Chart of Accounts
            </a>
            <a href="{{ route('master.account-groups') }}"
               class="flex items-center gap-1.5 px-4 py-3 text-sm transition-colors -mb-px whitespace-nowrap border-b-2 border-red-700 text-red-700 font-semibold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                Account Groups
            </a>
        </div>
    </div>

    {{-- Main two-column layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">

        {{-- LEFT: Tree --}}
        <div class="lg:col-span-2 bg-white border border-stone-200 rounded-1xl overflow-hidden">
            <div class="px-4 py-2.5 border-b border-stone-100 flex items-center justify-between min-h-[48px]">
                <h3 class="text-sm font-semibold text-stone-800">Group Tree</h3>
                <div class="flex items-center gap-2">
                    <button @click="$dispatch('open-natures')" class="tb-btn tb-btn-add">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                        </svg>
                        Natures
                    </button>
                    <button @click="expandAll = !expandAll" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white-800 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-semibold transition-colors">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                        </svg>
                        <span x-text="expandAll ? 'Collapse All' : 'Expand All'"></span>
                    </button>
                </div>
            </div>
            <div class="p-3 overflow-y-auto" style="max-height: 520px;">
                @forelse($tree as $group)
                    @include('panel.accounts._group-node', ['group' => $group, 'depth' => 0])
                @empty
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <div class="w-10 h-10 rounded-xl bg-stone-100 flex items-center justify-center mb-2 mx-auto">
                            <svg class="w-5 h-5 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-stone-600">No groups yet</p>
                        <p class="text-xs text-stone-400 mt-1">Add your first group using the form.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- RIGHT: Form --}}
        <div class="lg:col-span-3 bg-white border border-stone-200 rounded-1xl overflow-hidden">
            <div class="px-4 py-2.5 border-b border-stone-100 flex items-center justify-between min-h-[48px]">
                <h3 class="text-sm font-semibold text-stone-800" x-text="editId ? 'Edit Group' : 'Add Group'"></h3>
                <button x-show="editId" @click="resetForm()" class="tb-btn tb-btn-clear">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Cancel
                </button>
            </div>
            <div class="p-5">
                <form x-show="!editId" method="POST" action="{{ route('master.account-groups.store') }}">
                    @csrf
                    @include('panel.accounts._group-form', ['groups' => $groups])
                    <div class="mt-5 flex items-center gap-2">
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-800 hover:bg-red-700 text-white text-sm font-semibold transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Group
                        </button>
                    </div>
                </form>

                @foreach($groups as $g)
                <form x-show="editId === {{ $g->id }}" method="POST" action="{{ route('master.account-groups.update', $g) }}">
                    @csrf
                    @method('PUT')
                    @include('panel.accounts._group-form', ['groups' => $groups, 'current' => $g])
                    <div class="mt-5 flex items-center gap-2">
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-800 hover:bg-red-700 text-white text-sm font-semibold transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Save Changes
                        </button>
                        <button type="button" @click="resetForm()" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white-800 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-semibold transition-colors">Cancel</button>
                    </div>
                </form>
                @endforeach
            </div>
        </div>

    </div>

</div>
@endsection

{{-- ═══════════════════════════════════════════════════════════════════════════
     NATURE OFFCANVAS — rendered outside #main-wrap to avoid stacking context
═══════════════════════════════════════════════════════════════════════════ --}}
@push('modals')
<div x-data="natureOffcanvas()" x-init="init()" @open-natures.window="open()">

    {{-- Backdrop --}}
    <div x-show="isOpen"
         x-transition:enter="transition-opacity duration-300"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-200"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click="close()"
         class="fixed inset-0 bg-black/40 backdrop-blur-sm z-40"
         x-cloak>
    </div>

    {{-- Panel --}}
    <div x-show="isOpen"
         x-transition:enter="transition-transform duration-300 ease-out"
         x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition-transform duration-200 ease-in"
         x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
         class="fixed top-0 right-0 h-full w-full max-w-5xl bg-white shadow-2xl z-50 flex flex-col"
         x-cloak>

        {{-- Header --}}
        <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between shrink-0">
            <div>
                <h3 class="text-sm font-semibold text-stone-800">Account Natures</h3>
                <p class="text-xs text-stone-400 mt-0.5">Manage nature types for account groups</p>
            </div>
            <button @click="close()" class="w-8 h-8 flex items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-hidden flex">

            {{-- LEFT: Form --}}
            <div class="w-2/5 border-r border-stone-200 overflow-y-auto p-5">

                <div x-show="toast.show" x-transition
                     :class="toast.type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'"
                     class="mb-4 px-4 py-3 rounded-xl border text-sm font-medium flex items-center gap-2"
                     x-cloak>
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              :d="toast.type==='error' ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'"/>
                    </svg>
                    <span x-text="toast.message"></span>
                </div>

                <div class="bg-stone-50 border border-stone-200 rounded-xl p-4">
                    <h4 class="text-xs font-semibold text-stone-700 mb-3" x-text="editId ? 'Edit Nature' : 'Add Nature'"></h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-stone-600 mb-1.5">Nature Name <span class="text-red-600">*</span></label>
                            <input type="text" x-model="form.name" placeholder="e.g. Assets"
                                   class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors"
                                   :class="errors.name ? 'border-red-400' : ''">
                            <p x-show="errors.name" x-text="errors.name" class="mt-1 text-xs text-red-600"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-stone-600 mb-1.5">Slug <span class="text-red-600">*</span></label>
                            <input type="text" x-model="form.slug" placeholder="e.g. assets"
                                   class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors"
                                   :class="errors.slug ? 'border-red-400' : ''">
                            <p x-show="errors.slug" x-text="errors.slug" class="mt-1 text-xs text-red-600"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-stone-600 mb-1.5">Color Badge</label>
                            <select x-model="form.color"
                                    class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors">
                                <option value="blue">Blue</option>
                                <option value="green">Green</option>
                                <option value="orange">Orange</option>
                                <option value="red">Red</option>
                                <option value="purple">Purple</option>
                                <option value="stone">Stone</option>
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
                    <div class="mt-4 flex items-center gap-2">
                        <button @click="save()" :disabled="saving"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-800 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-semibold transition-colors">
                            <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span x-text="editId ? 'Save Changes' : 'Add Nature'"></span>
                        </button>
                        <button x-show="editId" @click="resetForm()" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white-800 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-semibold transition-colors">Cancel</button>
                    </div>
                </div>
            </div>

            {{-- RIGHT: List --}}
            <div class="w-3/5 overflow-y-auto p-5">
                <div class="mb-4 relative">
                    <input type="text" x-model="search" @input="filter()"
                           placeholder="Search natures..."
                           class="w-full border border-stone-300 rounded-xl pl-10 pr-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors">
                    <svg class="w-4 h-4 text-stone-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-xs font-semibold text-stone-700">Existing Natures</h4>
                    <span class="text-[10px] text-stone-400" x-text="`${filtered.length} of ${natures.length}`"></span>
                </div>

                <div class="space-y-2">
                    <template x-if="filtered.length === 0">
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <div class="w-10 h-10 rounded-xl bg-stone-100 flex items-center justify-center mb-2 mx-auto">
                                <svg class="w-5 h-5 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-stone-600" x-text="search ? 'No matching natures' : 'No natures yet'"></p>
                            <p class="text-xs text-stone-400 mt-1" x-text="search ? 'Try a different search term' : 'Add your first nature using the form.'"></p>
                        </div>
                    </template>

                    <template x-for="n in filtered" :key="n.id">
                        <div class="bg-white border border-stone-200 rounded-xl p-3 flex items-center justify-between hover:border-stone-300 transition-colors">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold"
                                      :class="{
                                          'bg-blue-50 text-blue-700':   n.color==='blue',
                                          'bg-green-50 text-green-700': n.color==='green',
                                          'bg-orange-50 text-orange-700': n.color==='orange',
                                          'bg-red-50 text-red-700':     n.color==='red',
                                          'bg-purple-50 text-purple-700': n.color==='purple',
                                          'bg-stone-100 text-stone-600': n.color==='stone'
                                      }"
                                      x-text="n.name"></span>
                                <span class="text-xs text-stone-400 font-mono" x-text="n.slug"></span>
                                <span x-show="!n.is_active" class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-semibold bg-stone-100 text-stone-500">Inactive</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <button @click="startEdit(n)" class="act-btn act-edit" title="Edit">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button @click="del(n.id)" class="act-btn act-delete" title="Delete">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
function accountGroups() {
    return {
        editId: {{ session('edit_group') ?? 'null' }},
        expandAll: true,

        init() {},

        setEdit(id) {
            this.editId = id;
        },

        resetForm() {
            this.editId = null;
        },
    };
}

function natureOffcanvas() {
    return {
        isOpen: false,
        natures: [],
        filtered: [],
        search: '',
        editId: null,
        saving: false,
        toast: { show: false, type: 'success', message: '' },
        errors: {},
        form: { name: '', slug: '', color: 'blue', is_active: true },

        async init() {
            await this.load();
        },

        open() {
            this.isOpen = true;
        },

        close() {
            this.isOpen = false;
            this.resetForm();
            this.search = '';
            this.filter();
        },

        async load() {
            try {
                const res = await fetch('/master/natures', {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                });
                const json = await res.json();
                this.natures = json.data || [];
                this.filter();
            } catch (e) {
                console.error('Failed to load natures:', e);
            }
        },

        filter() {
            const q = this.search.toLowerCase().trim();
            this.filtered = q
                ? this.natures.filter(n => n.name.toLowerCase().includes(q) || n.slug.toLowerCase().includes(q))
                : [...this.natures];
        },

        resetForm() {
            this.editId = null;
            this.form = { name: '', slug: '', color: 'blue', is_active: true };
            this.errors = {};
        },

        startEdit(n) {
            this.editId = n.id;
            this.form = { name: n.name, slug: n.slug, color: n.color || 'blue', is_active: n.is_active };
            this.errors = {};
        },

        showToast(type, message) {
            this.toast = { show: true, type, message };
            setTimeout(() => { this.toast.show = false; }, 3500);
        },

        async save() {
            this.saving = true;
            this.errors = {};
            const url    = this.editId ? `/master/natures/${this.editId}` : '/master/natures';
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
                    } else {
                        this.showToast('error', json.message ?? 'Something went wrong.');
                    }
                    return;
                }
                this.showToast('success', json.message ?? 'Nature saved.');
                this.resetForm();
                await this.load();
                setTimeout(() => location.reload(), 1500);
            } catch (e) {
                this.showToast('error', 'Network error. Please try again.');
            } finally {
                this.saving = false;
            }
        },

        async del(id) {
            if (!confirm('Delete this nature? This may affect existing groups.')) return;
            try {
                const res = await fetch(`/master/natures/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                });
                const json = await res.json();
                if (res.ok) {
                    this.showToast('success', json.message ?? 'Nature deleted.');
                    await this.load();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    this.showToast('error', json.message ?? 'Failed to delete.');
                }
            } catch (e) {
                this.showToast('error', 'Network error. Please try again.');
            }
        },
    };
}
</script>
@endpush
