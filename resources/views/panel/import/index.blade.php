@extends('layouts.app')

@section('title', 'Import Data')
@section('page-title', 'Import Data')

@section('content')
<div class="mx-auto space-y-6">

    {{-- Main Import Interface --}}
    <div class="bg-white border border-stone-200 rounded-xl p-6">
        
        {{-- Import Source Selection --}}
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-4">Import Data</h3>
            
            <div class="flex gap-4 mb-6">
                <button onclick="showImportType('file')" id="btn-import-file" class="flex-1 p-4 border-2 border-red-700 bg-red-50 rounded-lg text-left transition-all">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <div>
                            <div class="font-semibold text-red-700">File Import</div>
                            <div class="text-sm text-stone-600">Upload Excel, CSV, or SQL file</div>
                        </div>
                    </div>
                </button>
                
                <button onclick="showImportType('api')" id="btn-import-api" class="flex-1 p-4 border-2 border-stone-300 rounded-lg text-left transition-all hover:border-red-700 hover:bg-red-50">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-stone-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <div>
                            <div class="font-semibold text-stone-700">API Import</div>
                            <div class="text-sm text-stone-600">Import from external API</div>
                        </div>
                    </div>
                </button>
            </div>
        </div>

        {{-- File Import Section --}}
        <div id="file-import-section">
            @include('panel.import.sections.file')
        </div>

        {{-- API Import Section --}}
        <div id="api-import-section" class="hidden">
            @include('panel.import.sections.api')
        </div>

    </div>

    {{-- Import History --}}
    <div class="bg-white border border-stone-200 rounded-xl p-6">
        @include('panel.import.sections.history')
    </div>

</div>

<script>
function showImportType(type) {
    // Update buttons
    document.getElementById('btn-import-file').className = type === 'file' 
        ? 'flex-1 p-4 border-2 border-red-700 bg-red-50 rounded-lg text-left transition-all'
        : 'flex-1 p-4 border-2 border-stone-300 rounded-lg text-left transition-all hover:border-red-700 hover:bg-red-50';
    
    document.getElementById('btn-import-api').className = type === 'api'
        ? 'flex-1 p-4 border-2 border-red-700 bg-red-50 rounded-lg text-left transition-all'
        : 'flex-1 p-4 border-2 border-stone-300 rounded-lg text-left transition-all hover:border-red-700 hover:bg-red-50';
    
    // Show/hide sections
    document.getElementById('file-import-section').classList.toggle('hidden', type !== 'file');
    document.getElementById('api-import-section').classList.toggle('hidden', type !== 'api');
}
</script>
@endsection
