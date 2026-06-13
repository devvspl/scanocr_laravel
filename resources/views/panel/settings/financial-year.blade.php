@extends('layouts.app')

@section('title', 'Financial Year')
@section('page-title', 'Financial Year')

@section('content')
<div x-data="fyPage()" x-init="init()">

    {{-- Settings nav --}}
    @include('panel.settings._nav')

    {{-- List card --}}
    <div class="bg-white border border-stone-200 rounded-1xl overflow-hidden">
        <div class="px-5 py-3 border-b border-stone-100 flex items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-stone-800">Financial Years</h3>
                <p class="text-xs text-stone-400 mt-0.5">{{ $financialYears->count() }} {{ Str::plural('period', $financialYears->count()) }}</p>
            </div>
            <button @click="openModal()" class="tb-btn tb-btn-add">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Add Financial Year
            </button>
        </div>

        @if($financialYears->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <div class="w-14 h-14 rounded-2xl bg-stone-100 flex items-center justify-center mb-4">
                <svg class="w-7 h-7 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <p class="text-sm font-semibold text-stone-600">No financial years yet</p>
            <p class="text-xs text-stone-400 mt-1 mb-4">Add your first financial year to get started.</p>
            <button @click="openModal()" class="tb-btn tb-btn-add">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Add Financial Year
            </button>
        </div>
        @else
        <div class="divide-y divide-stone-100">
            @foreach($financialYears as $fy)
            <div class="flex items-center gap-4 px-5 py-4 hover:bg-stone-50 transition-colors group">

                {{-- Icon --}}
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0
                            {{ $fy->is_current ? 'bg-red-50 text-red-700' : 'bg-stone-100 text-stone-500' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-sm font-semibold text-stone-800">{{ $fy->label }}</span>
                        @if($fy->is_current)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-50 text-green-700 border border-green-200">Active</span>
                        @endif
                        @if($fy->is_locked)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Locked
                        </span>
                        @endif
                    </div>
                    <p class="text-xs text-stone-400 mt-0.5">
                        {{ $fy->start_date->format('d M Y') }} — {{ $fy->end_date->format('d M Y') }}
                        @if($fy->notes)
                        &nbsp;·&nbsp; {{ Str::limit($fy->notes, 60) }}
                        @endif
                    </p>
                </div>

                {{-- Duration badge --}}
                <div class="hidden sm:block shrink-0">
                    @php
                        $months = $fy->start_date->diffInMonths($fy->end_date) + 1;
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-medium bg-stone-100 text-stone-600">
                        {{ $months }} months
                    </span>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-1 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                    @if(!$fy->is_current)
                    <button @click="setCurrent({{ $fy->id }})"
                            title="Set as active"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-stone-400 hover:bg-green-50 hover:text-green-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </button>
                    @endif
                    <button @click="openModal({{ $fy->id }})" class="act-btn act-edit" title="Edit">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button @click="deleteFy({{ $fy->id }}, '{{ addslashes($fy->label) }}')" class="act-btn act-delete" title="Delete">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
            @endforeach
        </div>
        @endif
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
                <h3 class="text-sm font-semibold text-stone-800" x-text="editId ? 'Edit Financial Year' : 'Add Financial Year'"></h3>
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
                        <label class="block text-xs font-semibold text-stone-600 mb-1.5">Label <span class="text-red-600">*</span></label>
                        <input type="text" x-model="form.label" placeholder="e.g. FY 2025-26"
                               class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors"
                               :class="errors.label ? 'border-red-400' : ''">
                        <p x-show="errors.label" x-text="errors.label" class="mt-1 text-xs text-red-600"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-stone-600 mb-1.5">Start Date <span class="text-red-600">*</span></label>
                            <input type="date" x-model="form.start_date" @change="autoLabel()"
                                   class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors"
                                   :class="errors.start_date ? 'border-red-400' : ''">
                            <p x-show="errors.start_date" x-text="errors.start_date" class="mt-1 text-xs text-red-600"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-stone-600 mb-1.5">End Date <span class="text-red-600">*</span></label>
                            <input type="date" x-model="form.end_date" @change="autoLabel()"
                                   class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors"
                                   :class="errors.end_date ? 'border-red-400' : ''">
                            <p x-show="errors.end_date" x-text="errors.end_date" class="mt-1 text-xs text-red-600"></p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-stone-600 mb-1.5">Notes</label>
                        <textarea x-model="form.notes" rows="2" placeholder="Optional notes"
                                  class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors resize-none"></textarea>
                    </div>
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="form.is_current" class="w-4 h-4 rounded border-stone-300 text-red-700 focus:ring-red-700">
                            <span class="text-sm text-stone-600 font-medium">Set as Active</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="form.is_locked" class="w-4 h-4 rounded border-stone-300 text-amber-600 focus:ring-amber-600">
                            <span class="text-sm text-stone-600 font-medium">Lock Period</span>
                        </label>
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
                    <span x-text="editId ? 'Save Changes' : 'Add Financial Year'"></span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function fyPage() {
    return {
        modalOpen: false, editId: null, saving: false,
        toast: { show: false, type: 'success', message: '' },
        errors: {}, form: {},

        init() { this.resetForm(); },

        resetForm() {
            this.form = { label: '', start_date: '', end_date: '', is_current: false, is_locked: false, notes: '' };
        },

        autoLabel() {
            if (this.form.start_date && this.form.end_date && !this.editId) {
                const s = new Date(this.form.start_date);
                const e = new Date(this.form.end_date);
                const sy = s.getFullYear(), ey = e.getFullYear();
                this.form.label = sy === ey ? `FY ${sy}-${String(ey+1).slice(-2)}` : `FY ${sy}-${String(ey).slice(-2)}`;
            }
        },

        async openModal(id = null) {
            this.errors = {}; this.toast = { show: false, type: 'success', message: '' };
            if (id) {
                this.editId = id;
                const res  = await fetch(`/settings/financial-year/${id}`, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.form  = {
                    label:      data.label      ?? '',
                    start_date: data.start_date ?? '',
                    end_date:   data.end_date   ?? '',
                    is_current: data.is_current,
                    is_locked:  data.is_locked,
                    notes:      data.notes      ?? '',
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
            const url    = this.editId ? `/settings/financial-year/${this.editId}` : '/settings/financial-year';
            const method = this.editId ? 'PUT' : 'POST';
            try {
                const res = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ ...this.form }),
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
                window.location.reload();
            } catch (e) {
                this.showToast('error', 'Network error.');
            } finally {
                this.saving = false;
            }
        },

        async setCurrent(id) {
            const res  = await fetch(`/settings/financial-year/${id}/current`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            });
            const json = await res.json();
            if (json.success) window.location.reload();
        },

        async deleteFy(id, label) {
            if (!confirm(`Delete "${label}"?`)) return;
            const res  = await fetch(`/settings/financial-year/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            });
            const json = await res.json();
            json.success ? window.location.reload() : alert(json.message ?? 'Failed.');
        },
    };
}
</script>
@endpush
