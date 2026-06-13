@extends('layouts.app')

@section('title', 'Workflow Designer')
@section('page-title', 'Workflow Designer')

@section('breadcrumb')
    <span>EMS</span>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-stone-600">Workflow Designer</span>
@endsection

@section('content')
<div x-data="workflowIndex()">

    {{-- Toolbar --}}
    <div class="bg-white border border-stone-200 rounded-xl overflow-hidden mb-4">
        <div class="px-4 py-2.5 flex items-center justify-between gap-3 min-h-[52px]">
            <div class="flex items-center gap-2">
                <span class="text-xs text-stone-500">Manage document processing workflows</span>
            </div>
            <button @click="openCreate()" class="tb-btn tb-btn-add shrink-0">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                New Workflow
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white border border-stone-200 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-stone-100 bg-stone-50">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wide w-8">#</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wide">Name</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wide">Doc Type</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wide w-20">Version</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wide w-20">Stages</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wide w-24">Status</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wide w-24">Default</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wide">Created By</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wide w-32">Date</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wide w-40">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse($workflows as $i => $wf)
                    <tr class="hover:bg-stone-50 transition-colors">
                        <td class="px-4 py-3 text-xs text-stone-400">{{ $i + 1 }}</td>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-stone-800 text-sm">{{ $wf->name }}</div>
                            @if($wf->description)
                                <div class="text-xs text-stone-400 mt-0.5 truncate max-w-xs">{{ $wf->description }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-stone-600">
                            {{ $wf->docType?->label ?? '— Global —' }}
                        </td>
                        <td class="px-4 py-3 text-xs text-stone-600">v{{ $wf->version }}</td>
                        <td class="px-4 py-3 text-xs text-stone-600">{{ $wf->stages_count }}</td>
                        <td class="px-4 py-3">
                            @if($wf->is_active)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-50 text-green-700">Active</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-stone-100 text-stone-500">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($wf->is_default)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    Default
                                </span>
                            @else
                                <span class="text-xs text-stone-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-stone-600">{{ $wf->createdBy?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-stone-500">{{ $wf->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="act-group justify-center">
                                {{-- Design --}}
                                <a href="{{ route('master.workflow.designer', $wf->id) }}" class="act-btn act-edit" title="Design">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                {{-- Duplicate --}}
                                <button class="act-btn act-edit" title="Duplicate" @click="duplicate({{ $wf->id }}, '{{ addslashes($wf->name) }}')">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </button>
                                {{-- Activate --}}
                                @if(!$wf->is_default)
                                <button class="act-btn" title="Set as Default" style="color:#16a34a" @click="activate({{ $wf->id }})">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </button>
                                @endif
                                {{-- Delete --}}
                                @if(!$wf->is_default)
                                <button class="act-btn act-delete" title="Delete" @click="deleteWorkflow({{ $wf->id }})">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-4 py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-stone-100 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                                </div>
                                <p class="text-sm font-semibold text-stone-600">No workflows yet</p>
                                <p class="text-xs text-stone-400">Create your first workflow to get started.</p>
                                <button @click="openCreate()" class="tb-btn tb-btn-add mt-1">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                    New Workflow
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Create Modal --}}
    <div x-show="createModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="createModal.open = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <h3 class="text-sm font-semibold text-stone-800 mb-4">New Workflow</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1.5">Workflow Name <span class="text-red-600">*</span></label>
                    <input type="text" x-model="createModal.name" placeholder="e.g. Standard Invoice Processing"
                           class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1.5">Document Type</label>
                    <select x-model="createModal.doc_type_id"
                            class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors">
                        <option value="">— Global (all doc types) —</option>
                        @foreach($docTypes as $dt)
                            <option value="{{ $dt->id }}">{{ $dt->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1.5">Description</label>
                    <textarea x-model="createModal.description" rows="2" placeholder="Optional description..."
                              class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors resize-none"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 mt-5">
                <button @click="createModal.open = false" class="tb-btn tb-btn-edit">Cancel</button>
                <button @click="submitCreate()" :disabled="createModal.loading"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-800 hover:bg-red-700 text-white text-sm font-semibold transition-colors disabled:opacity-60">
                    <span x-show="createModal.loading">Creating...</span>
                    <span x-show="!createModal.loading">Create Workflow</span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

function _showGlobalToast(type, message) {
    const el = document.createElement('div');
    el.className = `fixed bottom-5 right-5 z-[999] px-4 py-3 rounded-xl border text-sm font-medium flex items-center gap-2 shadow-lg ${type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'}`;
    el.innerHTML = `<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${type === 'error' ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'}"/></svg><span>${message}</span>`;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 3500);
}

function workflowIndex() {
    return {
        createModal: { open: false, name: '', doc_type_id: '', description: '', loading: false },

        openCreate() {
            this.createModal = { open: true, name: '', doc_type_id: '', description: '', loading: false };
        },

        async submitCreate() {
            if (!this.createModal.name.trim()) {
                _showGlobalToast('error', 'Workflow name is required.');
                return;
            }
            this.createModal.loading = true;
            try {
                const res = await fetch('{{ route("master.workflow.store") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        name:        this.createModal.name,
                        doc_type_id: this.createModal.doc_type_id || null,
                        description: this.createModal.description,
                    }),
                });
                const json = await res.json();
                if (json.success) {
                    window.location.href = json.redirect;
                } else {
                    _showGlobalToast('error', json.message ?? 'Something went wrong.');
                    this.createModal.loading = false;
                }
            } catch (e) {
                _showGlobalToast('error', 'Request failed.');
                this.createModal.loading = false;
            }
        },

        async duplicate(id, name) {
            if (!confirm(`Duplicate "${name}"?`)) return;
            const res = await fetch(`/master/workflow/${id}/duplicate`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            const json = await res.json();
            if (json.success) {
                _showGlobalToast('success', 'Workflow duplicated.');
                window.location.href = json.redirect;
            } else {
                _showGlobalToast('error', json.message ?? 'Failed to duplicate.');
            }
        },

        async activate(id) {
            if (!confirm('Set this workflow as the default?')) return;
            const res = await fetch(`/master/workflow/${id}/activate`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            const json = await res.json();
            if (json.success) {
                _showGlobalToast('success', json.message);
                window.location.reload();
            } else {
                _showGlobalToast('error', json.message ?? 'Failed.');
            }
        },

        async deleteWorkflow(id) {
            if (!confirm('Delete this workflow? This cannot be undone.')) return;
            const res = await fetch(`/master/workflow/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            const json = await res.json();
            if (json.success) {
                _showGlobalToast('success', 'Workflow deleted.');
                window.location.reload();
            } else {
                _showGlobalToast('error', json.message ?? 'Failed to delete.');
            }
        },
    };
}
</script>
@endpush
