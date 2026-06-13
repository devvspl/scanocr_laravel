@extends('layouts.app')

@section('title', $workflow->name)
@section('page-title', $workflow->name)

@section('breadcrumb')
    <span class="text-stone-400">Process</span>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-stone-600">{{ $workflow->name }}</span>
@endsection

@section('content')
@php $layoutTemplate = $currentStage->layout_template ?? 'form_sidebar'; @endphp

<div class="max-w-7xl mx-auto space-y-5">
    {{-- Stage Progress (only show if multiple stages) --}}
    @if($stages->count() > 1)
    <div class="bg-white rounded-xl border border-stone-200 px-5 py-3">
        <div class="flex items-center gap-1 overflow-x-auto">
            @foreach($stages as $index => $stage)
                @if(!$loop->first)
                    <svg class="w-4 h-4 text-stone-300 shrink-0 mx-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                @endif
                <a href="?stage={{ $stage->id }}" class="flex items-center gap-2 px-3 py-1.5 rounded-full shrink-0 transition-colors {{ $stage->id === $currentStage->id ? 'bg-red-700 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
                    <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold {{ $stage->id === $currentStage->id ? 'bg-white text-red-700' : 'bg-stone-300 text-white' }}">{{ $index + 1 }}</span>
                    <span class="text-xs font-semibold">{{ $stage->display_name }}</span>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ══════════ FORM + SIDEBAR ══════════ --}}
    @if($layoutTemplate === 'form_sidebar')

    @php $isFirstStage = $currentStage->position === $stages->first()->position; @endphp

    @if($isFirstStage)
    {{-- FIRST STAGE: Form on left + Table on right --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-1 space-y-4">
            @include('workflow.widgets.entry-form', ['widget' => null, 'stage' => $currentStage])
            <div class="flex flex-wrap gap-2">
                @foreach($currentStage->actionMap as $actionMap)
                    @php $def = $actionMap->actionDefinition; @endphp
                    <button data-action-key="{{ $def->action_key }}" class="flex-1 min-w-[100px] px-3 py-2 rounded-lg text-[12px] font-semibold flex items-center justify-center gap-1.5 shadow-sm transition-all
                        @switch($def->button_style)
                            @case('success') bg-green-600 text-white hover:bg-green-700 @break
                            @case('danger') bg-red-600 text-white hover:bg-red-700 @break
                            @default bg-red-700 text-white hover:bg-red-800
                        @endswitch
                    "><i class="{{ $def->icon }} text-[10px]"></i>{{ $def->display_label }}</button>
                @endforeach
            </div>
        </div>
        <div class="lg:col-span-2">
            @include('workflow.widgets.table', ['widget' => (object)['title' => $currentStage->display_name . ' Entries', 'config' => ['limit' => 10]]])
        </div>
    </div>
    @else
    {{-- NON-FIRST STAGES: Full-width table with row-click to open edit modal --}}
    <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl border border-stone-200 p-4 flex items-center gap-4">
                <div class="w-11 h-11 rounded-lg flex items-center justify-center bg-stone-100"><i class="{{ $currentStage->icon ?? 'fa-solid fa-layer-group' }} text-base text-stone-600"></i></div>
                <div><p class="text-xl font-bold text-stone-800" id="counter-value">0</p><p class="text-[11px] text-stone-500">At This Stage</p></div>
            </div>
            <div class="bg-white rounded-xl border border-stone-200 p-4 flex items-center gap-4">
                <div class="w-11 h-11 rounded-lg flex items-center justify-center bg-amber-50"><svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                <div><p class="text-xl font-bold text-stone-800" id="counter-pending">0</p><p class="text-[11px] text-stone-500">Pending</p></div>
            </div>
            <div class="bg-white rounded-xl border border-stone-200 p-4 flex items-center gap-4">
                <div class="w-11 h-11 rounded-lg flex items-center justify-center bg-green-50"><svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                <div><p class="text-xl font-bold text-stone-800" id="counter-completed">0</p><p class="text-[11px] text-stone-500">Completed</p></div>
            </div>
        </div>
        @include('workflow.widgets.table', ['widget' => (object)['title' => $currentStage->display_name . ' — Entries to Process', 'config' => ['limit' => 15]]])
    </div>

    {{-- Edit Entry Modal for non-first stages --}}
    <div id="edit-entry-modal" class="fixed inset-0 z-[100] hidden">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeEditModal()"></div>
        <div class="absolute inset-4 md:inset-y-6 md:inset-x-[10%] bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-stone-200 flex items-center justify-between">
                <h3 class="text-sm font-bold text-stone-800" id="edit-modal-title">Process Entry</h3>
                <button onclick="closeEditModal()" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-stone-100 text-stone-400 hover:text-stone-700 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto">
                <div class="grid grid-cols-1 lg:grid-cols-3 h-full">
                    {{-- Left: Previous stage data (read-only) --}}
                    <div class="lg:col-span-1 bg-stone-50 border-r border-stone-200 p-5 overflow-y-auto">
                        <p class="text-[11px] font-bold text-stone-400 uppercase tracking-wider mb-3">Source Data</p>
                        <div id="edit-modal-source" class="space-y-3 text-xs"></div>
                    </div>
                    {{-- Right: Current stage form (editable) --}}
                    <div class="lg:col-span-2 p-5 overflow-y-auto">
                        <p class="text-[11px] font-bold text-stone-400 uppercase tracking-wider mb-3">{{ $currentStage->display_name }} — Fill Details</p>
                        @if($currentStage->page)
                            <form id="edit-stage-form" class="space-y-4">
                                @foreach($currentStage->page->fields as $field)
                                    <div>
                                        <label class="block text-[12px] font-semibold text-stone-600 mb-1.5">
                                            {{ $field->label ?? $field->field_name }}
                                            @if($field->is_required) <span class="text-red-500">*</span> @endif
                                        </label>
                                        <x-page-field-input :field="$field" />
                                    </div>
                                @endforeach
                            </form>
                        @else
                            <p class="text-sm text-stone-400 italic">No form configured for this stage.</p>
                        @endif
                        {{-- Action buttons --}}
                        <div class="mt-6 pt-4 border-t border-stone-200 flex flex-wrap gap-2">
                            @foreach($currentStage->actionMap as $actionMap)
                                @php $def = $actionMap->actionDefinition; @endphp
                                <button type="button" onclick="submitEditAction('{{ $def->action_key }}')" class="px-4 py-2 rounded-lg text-[13px] font-semibold flex items-center gap-2 shadow-sm transition-all
                                    @switch($def->button_style)
                                        @case('success') bg-green-600 text-white hover:bg-green-700 @break
                                        @case('danger') bg-red-600 text-white hover:bg-red-700 @break
                                        @case('warning') bg-amber-500 text-white hover:bg-amber-600 @break
                                        @default bg-red-700 text-white hover:bg-red-800
                                    @endswitch
                                ">
                                    <i class="{{ $def->icon }} text-xs"></i>
                                    {{ $def->display_label }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════ SPLIT PANEL ══════════ --}}
    @elseif($layoutTemplate === 'split_panel')
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">
        <div class="lg:col-span-2 space-y-4">
            @include('workflow.widgets.entry-form', ['widget' => null, 'stage' => $currentStage])
            <div class="flex flex-wrap gap-2">
                @foreach($currentStage->actionMap as $actionMap)
                    @php $def = $actionMap->actionDefinition; @endphp
                    <button data-action-key="{{ $def->action_key }}" class="px-4 py-2 rounded-lg text-[13px] font-semibold flex items-center gap-2 shadow-sm transition-all
                        @switch($def->button_style)
                            @case('success') bg-green-600 text-white hover:bg-green-700 @break
                            @case('danger') bg-red-600 text-white hover:bg-red-700 @break
                            @case('warning') bg-amber-500 text-white hover:bg-amber-600 @break
                            @default bg-red-700 text-white hover:bg-red-800
                        @endswitch
                    ">
                        <i class="{{ $def->icon }} text-xs"></i>
                        {{ $def->display_label }}
                    </button>
                @endforeach
            </div>
        </div>
        <div class="lg:col-span-3">
            @include('workflow.widgets.table', ['widget' => (object)['title' => 'Recent Entries', 'config' => ['limit' => 10]]])
        </div>
    </div>

    {{-- ══════════ FULL DASHBOARD ══════════ --}}
    @elseif($layoutTemplate === 'full_dashboard')
    {{-- Counters --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-stone-200 p-4 flex items-center gap-4">
            <div class="w-11 h-11 rounded-lg flex items-center justify-center" style="background: {{ $currentStage->color }}15;"><i class="{{ $currentStage->icon }} text-base" style="color: {{ $currentStage->color }};"></i></div>
            <div><p class="text-xl font-bold text-stone-800" id="counter-value">0</p><p class="text-[11px] text-stone-500">Total Entries</p></div>
        </div>
        <div class="bg-white rounded-xl border border-stone-200 p-4 flex items-center gap-4">
            <div class="w-11 h-11 rounded-lg flex items-center justify-center bg-amber-50"><svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            <div><p class="text-xl font-bold text-stone-800" id="counter-pending">0</p><p class="text-[11px] text-stone-500">Pending</p></div>
        </div>
        <div class="bg-white rounded-xl border border-stone-200 p-4 flex items-center gap-4">
            <div class="w-11 h-11 rounded-lg flex items-center justify-center bg-green-50"><svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            <div><p class="text-xl font-bold text-stone-800" id="counter-completed">0</p><p class="text-[11px] text-stone-500">Completed</p></div>
        </div>
    </div>
    {{-- Main --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-1 space-y-4">
            @include('workflow.widgets.entry-form', ['widget' => null, 'stage' => $currentStage])
            <div class="flex flex-wrap gap-2">
                @foreach($currentStage->actionMap as $actionMap)
                    @php $def = $actionMap->actionDefinition; @endphp
                    <button data-action-key="{{ $def->action_key }}" class="flex-1 min-w-[100px] px-3 py-2 rounded-lg text-[12px] font-semibold flex items-center justify-center gap-1.5 shadow-sm transition-all
                        @switch($def->button_style)
                            @case('success') bg-green-600 text-white hover:bg-green-700 @break
                            @case('danger') bg-red-600 text-white hover:bg-red-700 @break
                            @default bg-red-700 text-white hover:bg-red-800
                        @endswitch
                    "><i class="{{ $def->icon }} text-[10px]"></i>{{ $def->display_label }}</button>
                @endforeach
            </div>
        </div>
        <div class="lg:col-span-2">
            @include('workflow.widgets.table', ['widget' => (object)['title' => 'All Entries', 'config' => ['limit' => 15]]])
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const workflowId = {{ $workflow->id }};
    const stageId = {{ $currentStage->id }};
    let currentEntryId = null;
    let currentPage = 1;
    let searchQuery = '';
    let searchTimeout = null;
    let allColumns = [];

    // Stage move actions (for row-level buttons)
    @php
        $stageActionsData = $currentStage->actionMap->map(function($m) {
            return [
                'action_key' => $m->actionDefinition->action_key,
                'label' => $m->actionDefinition->display_label,
                'icon' => $m->actionDefinition->icon,
                'button_color' => $m->actionDefinition->button_color,
                'button_style' => $m->actionDefinition->button_style,
                'has_move' => !empty($m->actionDefinition->logic_config['move_to']),
            ];
        })->filter(function($a) {
            return $a['has_move'];
        })->values();
    @endphp
    const stageActions = @json($stageActionsData);

    document.querySelectorAll('[data-action-key]').forEach(btn => {
        btn.addEventListener('click', async function() { await executeAction(this.dataset.actionKey); });
    });

    // Search with debounce
    const searchInput = document.getElementById('entries-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchQuery = this.value.trim();
                currentPage = 1;
                loadEntries();
            }, 300);
        });
    }

    async function executeAction(actionKey) {
        // Find the active/visible form (could be main or sub-stage form)
        let form = document.getElementById('stage-form');
        if (!form) {
            // Try sub-stage forms - find the visible one
            const visibleForms = document.querySelectorAll('.stage-form');
            visibleForms.forEach(f => {
                if (f.offsetParent !== null) form = f;
            });
        }

        // Client-side validation: check required fields including file inputs
        if (form) {
            let valid = true;
            form.querySelectorAll('[required]').forEach(input => {
                if (input.type === 'file') {
                    if (input.files.length === 0) { valid = false; input.closest('.relative')?.classList.add('ring-2', 'ring-red-400', 'rounded-xl'); }
                    else { input.closest('.relative')?.classList.remove('ring-2', 'ring-red-400', 'rounded-xl'); }
                } else if (!input.value) {
                    valid = false; input.classList.add('border-red-400', 'bg-red-50');
                } else {
                    input.classList.remove('border-red-400', 'bg-red-50');
                }
            });
            if (!valid) { showToast('error', 'Please fill all required fields.'); return; }
        }

        const formData = new FormData();
        formData.append('action_key', actionKey);
        formData.append('stage_id', stageId);
        if (currentEntryId) formData.append('entry_id', currentEntryId);
        if (form) {
            form.querySelectorAll('input, select, textarea').forEach(input => {
                if (input.type === 'file') {
                    if (input.files.length > 0) {
                        for (let i = 0; i < input.files.length; i++) { formData.append(input.name, input.files[i]); }
                    }
                }
                else if (input.type === 'checkbox' || input.type === 'radio') { if (input.checked) formData.append(input.name, input.value || '1'); }
                else if (input.value) { formData.append(input.name, input.value); }
            });
        }
        try {
            const res = await fetch(`/process/workflow/${workflowId}/action`, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }, body: formData });
            const json = await res.json();
            if (!res.ok && json.errors) {
                const msgs = Object.values(json.errors).flat().join(', ');
                showToast('error', msgs); return;
            }
            if (json.success) { currentEntryId = json.entry_id; showToast('success', json.message); loadEntries(); if (form) form.reset(); form.querySelectorAll('.file-name').forEach(el => el.textContent = 'No file selected'); }
            else { showToast('error', json.message || 'Failed'); }
        } catch (e) { showToast('error', 'Network error.'); }
    }

    async function loadEntries() {
        try {
            const params = new URLSearchParams({ page: currentPage, per_page: 10, search: searchQuery, stage_id: stageId });
            const res = await fetch(`/process/workflow/${workflowId}/entries?${params}`, { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            if (json.success) {
                document.querySelectorAll('#counter-value').forEach(el => el.textContent = json.total || 0);
                document.querySelectorAll('#counter-pending').forEach(el => el.textContent = json.pending || 0);
                document.querySelectorAll('#counter-completed').forEach(el => el.textContent = json.completed || 0);

                const tb = document.getElementById('entries-table-body');
                const thead = document.getElementById('entries-thead');
                const columns = json.columns || [];
                allColumns = json.all_columns || columns;
                const pagination = json.pagination || {};

                // Update table headers dynamically
                if (thead && columns.length > 0) {
                    thead.innerHTML = `<th class="px-4 py-2.5 text-left font-semibold text-stone-500 w-10">#</th>` +
                        columns.map(c => `<th class="px-4 py-2.5 text-left font-semibold text-stone-500">${c.label}</th>`).join('') +
                        `<th class="px-4 py-2.5 text-left font-semibold text-stone-500">Status</th>` +
                        `<th class="px-4 py-2.5 text-left font-semibold text-stone-500">Date</th>` +
                        `<th class="px-4 py-2.5 text-left font-semibold text-stone-500 w-36">Action</th>`;
                }

                if (tb && json.entries && json.entries.length > 0) {
                    const startIdx = (pagination.current_page - 1) * pagination.per_page;
                    tb.innerHTML = json.entries.map((e, i) => {
                        const rd = e.record_data || {};
                        const colCells = columns.map(c => {
                            let val = rd[c.key] ?? '';
                            if (typeof val === 'object') val = '';
                            if (String(val).length > 30) val = String(val).substring(0, 30) + '...';
                            return `<td class="px-4 py-2.5 text-xs text-stone-700">${val}</td>`;
                        }).join('');

                        // Build action buttons for this row
                        let actionBtns = `<button class="w-7 h-7 rounded-lg bg-stone-100 hover:bg-stone-200 text-stone-600 inline-flex items-center justify-center transition" title="View Details" onclick='event.stopPropagation(); viewEntry(${JSON.stringify(e)})'><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button>`;
                        // Show stage move actions only for entries at current stage with actionable status
                        if (['draft', 'in_progress', 'punched', 'verified', 'pending_approval'].includes(e.status) && e.current_stage_id == stageId) {
                            stageActions.forEach(a => {
                                const color = a.button_color || '#16a34a';
                                actionBtns += ` <button class="w-7 h-7 rounded-lg text-white inline-flex items-center justify-center transition opacity-90 hover:opacity-100 shadow-sm" style="background-color:${color}" title="${a.label}" onclick="event.stopPropagation(); executeEntryAction('${a.action_key}', ${e.id}, ${e.form_data?.gen_record_id || 'null'})">${a.icon || ''}</button>`;
                            });
                        }

                        return `<tr class="border-b border-stone-100 hover:bg-stone-50 cursor-pointer" onclick='openEditModal(${JSON.stringify(e)})'>` +
                            `<td class="px-4 py-2.5 text-xs text-stone-400">${startIdx + i + 1}</td>` +
                            colCells +
                            `<td class="px-4 py-2.5"><span class="text-[10px] px-2 py-0.5 rounded-full font-semibold ${getStatusClass(e.status)}">${e.status}</span></td>` +
                            `<td class="px-4 py-2.5 text-xs text-stone-500">${new Date(e.created_at).toLocaleDateString()}</td>` +
                            `<td class="px-4 py-2.5"><div class="flex items-center gap-1">${actionBtns}</div></td></tr>`;
                    }).join('');
                } else if (tb) {
                    const colSpan = columns.length + 4;
                    tb.innerHTML = `<tr><td colspan="${colSpan}" class="px-4 py-8 text-center text-stone-400 text-xs">No entries yet</td></tr>`;
                }

                // Render pagination
                renderPagination(pagination);
            }
        } catch(e) { console.error(e); }
    }

    function renderPagination(p) {
        const info = document.getElementById('entries-info');
        const pages = document.getElementById('entries-pages');
        if (!p || !p.total) {
            if (info) info.textContent = 'Showing 0 entries';
            if (pages) pages.innerHTML = '';
            return;
        }
        const from = ((p.current_page - 1) * p.per_page) + 1;
        const to = Math.min(p.current_page * p.per_page, p.total);
        if (info) info.textContent = `Showing ${from}-${to} of ${p.total}`;
        if (pages) {
            let btns = '';
            // Prev
            btns += `<button class="px-2.5 py-1 rounded-md border border-stone-200 ${p.current_page <= 1 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-stone-100 cursor-pointer'}" ${p.current_page <= 1 ? 'disabled' : `onclick="goToPage(${p.current_page - 1})"`}>&laquo;</button>`;
            // Page numbers
            for (let i = 1; i <= p.total_pages; i++) {
                if (p.total_pages > 7 && i > 3 && i < p.total_pages - 2 && Math.abs(i - p.current_page) > 1) {
                    if (i === 4 || i === p.total_pages - 3) btns += `<span class="px-1 text-stone-400">...</span>`;
                    continue;
                }
                btns += `<button class="px-2.5 py-1 rounded-md border ${i === p.current_page ? 'bg-red-700 text-white border-red-700' : 'border-stone-200 hover:bg-stone-100'} cursor-pointer" onclick="goToPage(${i})">${i}</button>`;
            }
            // Next
            btns += `<button class="px-2.5 py-1 rounded-md border border-stone-200 ${p.current_page >= p.total_pages ? 'opacity-40 cursor-not-allowed' : 'hover:bg-stone-100 cursor-pointer'}" ${p.current_page >= p.total_pages ? 'disabled' : `onclick="goToPage(${p.current_page + 1})"`}>&raquo;</button>`;
            pages.innerHTML = btns;
        }
    }

    window.goToPage = function(page) {
        currentPage = page;
        loadEntries();
    };

    window.executeEntryAction = async function(actionKey, wfEntryId, genRecordId) {
        if (!confirm('Are you sure you want to perform this action?')) return;
        try {
            const formData = new FormData();
            formData.append('action_key', actionKey);
            formData.append('stage_id', stageId);
            formData.append('wf_entry_id', wfEntryId);
            if (genRecordId) formData.append('entry_id', genRecordId);

            const res = await fetch(`/process/workflow/${workflowId}/action`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: formData
            });
            const json = await res.json();
            if (!res.ok && json.errors) {
                showToast('error', Object.values(json.errors).flat().join(', ')); return;
            }
            if (json.success) {
                showToast('success', json.message + (json.stage_moved ? ' Entry moved to next stage.' : ''));
                loadEntries();
            } else {
                showToast('error', json.message || 'Failed');
            }
        } catch (e) { showToast('error', 'Network error.'); }
    };

    window.viewEntry = function(entry) {
        const modal = document.getElementById('entry-modal');
        const title = document.getElementById('modal-title');
        const body = document.getElementById('modal-body');
        if (!modal) return;

        const rd = entry.record_data || {};
        title.textContent = `Entry #${entry.id} — ${entry.status}`;

        // Separate file columns from detail columns
        let fileColumns = [];
        let detailColumns = [];
        allColumns.forEach(col => {
            if (col.type === 'file' || col.type === 'image') {
                fileColumns.push(col);
            } else {
                detailColumns.push(col);
            }
        });

        // Build file preview (left side)
        let fileHtml = '';
        fileColumns.forEach(col => {
            let val = rd[col.key] ?? '';
            try {
                const files = typeof val === 'string' && val ? JSON.parse(val) : (Array.isArray(val) ? val : []);
                if (files.length > 0) {
                    fileHtml += files.map(f => {
                        const fname = f.split('/').pop();
                        const ext = fname.split('.').pop().toLowerCase();
                        const fileUrl = `/storage/${f}`;
                        const isImage = ['jpg','jpeg','png','gif','webp','svg','bmp'].includes(ext);
                        const isPdf = ext === 'pdf';

                        let preview = '';
                        if (isImage) {
                            preview = `<iframe src="${fileUrl}" class="w-full h-full min-h-[400px] rounded-lg border border-stone-200 bg-stone-50" frameborder="0"></iframe>`;
                        } else if (isPdf) {
                            preview = `<iframe src="${fileUrl}" class="w-full h-full min-h-[400px] rounded-lg border border-stone-200 bg-stone-50" frameborder="0"></iframe>`;
                        } else {
                            preview = `<div class="w-full h-full min-h-[400px] rounded-lg border border-stone-200 bg-stone-50 flex flex-col items-center justify-center gap-3">
                                <svg class="w-16 h-16 text-stone-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p class="text-sm text-stone-500 font-medium">${fname}</p>
                                <p class="text-xs text-stone-400">${ext.toUpperCase()} file</p>
                            </div>`;
                        }

                        return `<div class="space-y-2">
                            <p class="text-[11px] font-semibold text-stone-500 uppercase tracking-wide">${col.label}</p>
                            ${preview}
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-stone-500 truncate">${fname}</span>
                                <a href="${fileUrl}" target="_blank" download class="text-[11px] text-red-700 font-semibold hover:underline flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Download
                                </a>
                            </div>
                        </div>`;
                    }).join('');
                } else {
                    fileHtml += `<div class="space-y-2">
                        <p class="text-[11px] font-semibold text-stone-500 uppercase tracking-wide">${col.label}</p>
                        <div class="w-full h-48 rounded-lg border-2 border-dashed border-stone-200 bg-stone-50 flex items-center justify-center">
                            <span class="text-sm text-stone-400 italic">No file uploaded</span>
                        </div>
                    </div>`;
                }
            } catch(e) {
                fileHtml += `<div class="space-y-2">
                    <p class="text-[11px] font-semibold text-stone-500 uppercase tracking-wide">${col.label}</p>
                    <div class="w-full h-48 rounded-lg border-2 border-dashed border-stone-200 bg-stone-50 flex items-center justify-center">
                        <span class="text-sm text-stone-400 italic">No file uploaded</span>
                    </div>
                </div>`;
            }
        });

        // Build details (right side)
        let detailHtml = '<div class="space-y-4">';
        detailColumns.forEach(col => {
            let val = rd[col.key] ?? '';
            let displayVal = '';

            if (col.type === 'checkbox' || col.type === 'toggle') {
                displayVal = val ? '<span class="text-green-600 font-medium">Yes</span>' : '<span class="text-stone-400">No</span>';
            } else if (col.type === 'color' && val) {
                displayVal = `<div class="flex items-center gap-2"><span class="w-5 h-5 rounded border border-stone-200" style="background:${val}"></span><span class="text-xs">${val}</span></div>`;
            } else {
                displayVal = val || '<span class="text-stone-400 italic">—</span>';
            }

            detailHtml += `<div class="space-y-0.5">
                <p class="text-[11px] font-semibold text-stone-500 uppercase tracking-wide">${col.label}</p>
                <div class="text-sm text-stone-800">${displayVal}</div>
            </div>`;
        });
        detailHtml += '</div>';

        // Layout: file on left, details on right (or stacked if no files)
        let html = '';
        if (fileColumns.length > 0) {
            html = `<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">${fileHtml}</div>
                <div>${detailHtml}</div>
            </div>`;
        } else {
            html = `<div class="grid grid-cols-1 md:grid-cols-2 gap-4">`;
            detailColumns.forEach(col => {
                let val = rd[col.key] ?? '';
                let displayVal = '';
                if (col.type === 'checkbox' || col.type === 'toggle') {
                    displayVal = val ? '<span class="text-green-600 font-medium">Yes</span>' : '<span class="text-stone-400">No</span>';
                } else if (col.type === 'color' && val) {
                    displayVal = `<div class="flex items-center gap-2"><span class="w-5 h-5 rounded border border-stone-200" style="background:${val}"></span><span class="text-xs">${val}</span></div>`;
                } else {
                    displayVal = val || '<span class="text-stone-400 italic">—</span>';
                }
                html += `<div class="space-y-0.5">
                    <p class="text-[11px] font-semibold text-stone-500 uppercase tracking-wide">${col.label}</p>
                    <div class="text-sm text-stone-800">${displayVal}</div>
                </div>`;
            });
            html += '</div>';
        }

        // Meta info
        html += `<div class="mt-6 pt-4 border-t border-stone-200 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div><p class="text-[10px] text-stone-400 uppercase">Status</p><span class="text-[11px] px-2 py-0.5 rounded-full font-semibold ${getStatusClass(entry.status)}">${entry.status}</span></div>
            <div><p class="text-[10px] text-stone-400 uppercase">Created</p><p class="text-xs text-stone-700">${new Date(entry.created_at).toLocaleString()}</p></div>
            <div><p class="text-[10px] text-stone-400 uppercase">Entry ID</p><p class="text-xs text-stone-700">#${entry.id}</p></div>
            <div><p class="text-[10px] text-stone-400 uppercase">Stage</p><p class="text-xs text-stone-700">${entry.current_stage?.display_name || '—'}</p></div>
        </div>`;

        body.innerHTML = html;
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    };

    window.closeEntryModal = function() {
        const modal = document.getElementById('entry-modal');
        if (modal) modal.classList.add('hidden');
        document.body.style.overflow = '';
    };

    // ── Edit Entry Modal (for non-first stages) ──
    let editingEntry = null;

    window.openEditModal = function(entry) {
        editingEntry = entry;
        const modal = document.getElementById('edit-entry-modal');
        if (!modal) {
            // Fallback to view modal if edit modal doesn't exist (first stage)
            viewEntry(entry);
            return;
        }

        const title = document.getElementById('edit-modal-title');
        const source = document.getElementById('edit-modal-source');

        title.textContent = `Process Entry #${entry.id}`;

        // Show source data (from previous stage) on the left
        const rd = entry.record_data || {};
        let sourceHtml = '';
        allColumns.forEach(col => {
            let val = rd[col.key] ?? '';
            if (col.type === 'file' || col.type === 'image') {
                try {
                    const files = typeof val === 'string' && val ? JSON.parse(val) : [];
                    val = files.length > 0 ? files.map(f => `<a href="/storage/${f}" target="_blank" class="text-red-700 hover:underline">${f.split('/').pop()}</a>`).join('<br>') : '<span class="text-stone-400 italic">No file</span>';
                } catch(e) { val = '—'; }
            } else if (typeof val === 'object') {
                val = JSON.stringify(val);
            }
            sourceHtml += `<div class="pb-2 border-b border-stone-200">
                <p class="text-[10px] text-stone-400 uppercase font-semibold">${col.label}</p>
                <div class="text-stone-700 mt-0.5">${val || '<span class="text-stone-400">—</span>'}</div>
            </div>`;
        });
        sourceHtml += `<div class="pt-2">
            <p class="text-[10px] text-stone-400 uppercase font-semibold">Status</p>
            <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold ${getStatusClass(entry.status)}">${entry.status}</span>
        </div>`;
        source.innerHTML = sourceHtml;

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    };

    window.closeEditModal = function() {
        const modal = document.getElementById('edit-entry-modal');
        if (modal) modal.classList.add('hidden');
        document.body.style.overflow = '';
        editingEntry = null;
    };

    window.submitEditAction = async function(actionKey) {
        if (!editingEntry) return;

        const form = document.getElementById('edit-stage-form');
        const formData = new FormData();
        formData.append('action_key', actionKey);
        formData.append('stage_id', stageId);
        formData.append('wf_entry_id', editingEntry.id);
        formData.append('entry_id', editingEntry.form_data?.gen_record_id || '');

        // Collect form data if form exists
        if (form) {
            form.querySelectorAll('input, select, textarea').forEach(input => {
                if (input.type === 'file') {
                    if (input.files.length > 0) {
                        for (let i = 0; i < input.files.length; i++) { formData.append(input.name, input.files[i]); }
                    }
                } else if (input.type === 'checkbox' || input.type === 'radio') {
                    if (input.checked) formData.append(input.name, input.value || '1');
                } else if (input.value) {
                    formData.append(input.name, input.value);
                }
            });
        }

        try {
            const res = await fetch(`/process/workflow/${workflowId}/action`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: formData
            });
            const json = await res.json();
            if (!res.ok && json.errors) {
                showToast('error', Object.values(json.errors).flat().join(', ')); return;
            }
            if (json.success) {
                showToast('success', json.message + (json.stage_moved ? ' Moved to next stage.' : ''));
                closeEditModal();
                loadEntries();
            } else {
                showToast('error', json.message || 'Failed');
            }
        } catch (e) { showToast('error', 'Network error.'); }
    };

    // Close modals on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') { closeEntryModal(); closeEditModal(); }
    });

    function getStatusClass(s) { return {draft:'bg-stone-100 text-stone-600',in_progress:'bg-blue-100 text-blue-700',pending_approval:'bg-amber-100 text-amber-700',approved:'bg-green-100 text-green-700',rejected:'bg-red-100 text-red-700',completed:'bg-emerald-100 text-emerald-700'}[s]||'bg-stone-100 text-stone-600'; }
    function showToast(t,m){const el=document.createElement('div');el.className=`fixed bottom-5 right-5 z-[999] px-4 py-3 rounded-xl border text-sm font-medium flex items-center gap-2 shadow-lg ${t==='error'?'bg-red-50 border-red-200 text-red-700':'bg-green-50 border-green-200 text-green-700'}`;el.innerHTML=`<span>${m}</span>`;document.body.appendChild(el);setTimeout(()=>el.remove(),3500);}
    loadEntries();
});
</script>
@endpush
@endsection
