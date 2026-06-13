@extends('layouts.app')

@section('title', 'Workflow Designer')
@section('page-title', $workflow->name)

@section('breadcrumb')
    <a href="{{ route('master.workflow.index') }}" class="text-stone-400 hover:text-stone-600">Workflows</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-stone-600">Designer</span>
@endsection

@section('content')
<link rel="stylesheet" href="{{ asset('css/workflow-designer.css') }}?v={{ time() }}">

<div id="wf-designer-app">
    {{-- Topbar --}}
    <div class="wf-save-bar">
        <div class="flex items-center gap-3">
            <a href="{{ route('master.workflow.index') }}" class="text-stone-500 hover:text-stone-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h2 class="text-sm font-semibold text-stone-800">{{ $workflow->name }}</h2>
                <p class="text-xs text-stone-400">v{{ $workflow->version }} • {{ $workflow->docType?->label ?? 'Global' }}</p>
            </div>
            @if($workflow->is_active)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-green-50 text-green-700">Active</span>
            @endif
            @if($workflow->is_default)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    Default
                </span>
            @endif
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.open('{{ route('master.workflow.log', $workflow->id) }}', '_blank')" class="tb-btn tb-btn-edit text-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                View Log
            </button>
            <button id="btn-publish-workflow" class="tb-btn text-xs" style="background: #15803d; color: #fff; border-color: #15803d;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l14 9-14 9V3z"/></svg>
                Publish
            </button>
            <span id="wf-save-status" class="text-xs"></span>
        </div>
    </div>

    {{-- Main Layout --}}
    <div class="flex gap-0" style="height: calc(100vh - 180px);">
        {{-- Left: Stage Library --}}
        <div class="wf-stage-library">
            <div class="px-4 py-3 border-b border-stone-200 flex items-center justify-between">
                <h3 class="text-xs font-bold text-stone-500 uppercase tracking-wide">Stage Library</h3>
                <button id="btn-add-stage" class="text-xs px-2 py-1 bg-red-700 text-white rounded hover:bg-red-800 transition-colors" title="Add New Stage">
                    <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New
                </button>
            </div>
            <div class="p-3 space-y-2" id="stage-library">
                {{-- Populated by JS --}}
            </div>
        </div>

        {{-- Center: Pipeline Canvas --}}
        <div class="flex-1 overflow-y-auto bg-stone-50 p-8">
            <div id="wf-pipeline-canvas" class="wf-pipeline-canvas">
                {{-- Populated by JS --}}
            </div>
        </div>

        {{-- Right: Config Drawer --}}
        <div id="wf-drawer" class="wf-drawer">
            <div class="wf-drawer-header">
                <span id="drawer-stage-name"></span>
                <button id="drawer-close" class="w-7 h-7 flex items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <ul class="nav nav-tabs border-b border-stone-200 flex" id="drawer-tabs">
                <li class="flex-1"><a href="#" data-tab="actions" class="block px-4 py-2 text-xs font-semibold text-center border-b-2 border-transparent hover:border-red-700 hover:text-red-700 transition-colors">Actions</a></li>
                <li class="flex-1"><a href="#" data-tab="layout" class="block px-4 py-2 text-xs font-semibold text-center border-b-2 border-transparent hover:border-red-700 hover:text-red-700 transition-colors">Layout</a></li>
                <li class="flex-1"><a href="#" data-tab="config" class="block px-4 py-2 text-xs font-semibold text-center border-b-2 border-transparent hover:border-red-700 hover:text-red-700 transition-colors">Config</a></li>
            </ul>
            <div id="drawer-content" class="p-4">
                {{-- Dynamically populated by JS --}}
            </div>
        </div>
    </div>
</div>

<script>
function _showGlobalToast(type, message) {
    const el = document.createElement('div');
    el.className = `fixed bottom-5 right-5 z-[999] px-4 py-3 rounded-xl border text-sm font-medium flex items-center gap-2 shadow-lg ${type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'}`;
    el.innerHTML = `<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${type === 'error' ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'}"/></svg><span>${message}</span>`;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 3500);
}

const WF_DATA = @json($wfData);
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
<script src="{{ asset('js/workflow-designer.js') }}?v={{ time() }}"></script>
@endsection
