@extends('layouts.app')
@section('content')
@php use Illuminate\Support\Facades\Storage; @endphp
<div class="bg-white border border-stone-200 rounded-1xl overflow-hidden">
    <div class="px-6 py-5 border-b border-stone-100 flex items-center justify-between gap-4">
        <div>
            <h3 class="text-sm font-semibold text-stone-800">Scanner</h3>
            <p class="text-xs text-stone-400 mt-0.5">{{ $scanners->total() }} {{ Str::plural('record', $scanners->total()) }}</p>
        </div>
        <div class="flex items-center gap-3">
            <form method="GET" action="{{ route('generated.scanners.index') }}">
                <div class="flex items-center gap-2 border border-stone-300 rounded-xl px-3 py-2 focus-within:border-red-700 focus-within:ring-2 focus-within:ring-red-700/10 transition bg-white">
                    <svg class="w-4 h-4 text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search…" autocomplete="off" class="text-sm outline-none border-none p-0 bg-transparent text-stone-700 placeholder-stone-400 w-40" oninput="clearTimeout(window._st); window._st = setTimeout(() => this.form.submit(), 400)">
                    @if(!empty($search))
                    <a href="{{ route('generated.scanners.index') }}" class="text-stone-400 hover:text-stone-600 transition shrink-0"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></a>
                    @endif
                </div>
            </form>
            <a href="{{ route('generated.scanners.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-red-800 hover:bg-red-700 text-white text-sm font-medium transition-colors shadow-sm whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add New
            </a>
            <div class="inline-flex rounded-xl overflow-hidden shadow-sm">
                <a href="{{ route('generated.scanners.export') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-green-700 hover:bg-green-600 text-white text-sm font-medium transition-colors whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export
                </a>
                <button onclick="openExportLog()" class="inline-flex items-center px-2.5 py-2 bg-green-800 hover:bg-green-700 text-white text-sm transition-colors border-l border-green-600" title="Export history">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </button>
            </div>
        </div>
    </div>
    @if($scanners->isEmpty())
    <div class="flex flex-col items-center justify-center py-20 text-center">
        <div class="w-14 h-14 rounded-1xl bg-stone-100 flex items-center justify-center mb-4"><svg class="w-7 h-7 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>
        <p class="text-sm font-medium text-stone-600">No records yet</p>
        <p class="text-xs text-stone-400 mt-1">Click "Add New" to get started.</p>
    </div>
    @else
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-stone-100 bg-stone-50 text-left">
                <th class="px-6 py-3 text-xs font-semibold text-stone-500 tracking-wider w-12">#</th>
                <th class="px-6 py-3 text-xs font-semibold text-stone-500 tracking-wider">Document Title</th>
                <th class="px-6 py-3 text-xs font-semibold text-stone-500 tracking-wider">Document No</th>
                <th class="px-6 py-3 text-xs font-semibold text-stone-500 tracking-wider">Document Date</th>
                <th class="px-6 py-3 text-xs font-semibold text-stone-500 tracking-wider">Document Type</th>
                <th class="px-6 py-3 text-xs font-semibold text-stone-500 tracking-wider">Remarks</th>
                <th class="px-6 py-3 text-xs font-semibold text-stone-500 tracking-wider">Upload Scan Copy</th>
                <th class="px-6 py-3 text-xs font-semibold text-stone-500 tracking-wider">other</th>
                <th class="px-6 py-3 text-xs font-semibold text-stone-500 tracking-wider text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-stone-100">
            @foreach($scanners as $index => $scanner)
            <tr class="hover:bg-stone-50 transition-colors">
                <td class="px-6 py-1 text-stone-400">{{ $scanners->firstItem() + $index }}</td>
                <td class="px-1 py-1 text-stone-700">{{ $scanner->title ?? '—' }}</td>
                <td class="px-1 py-1 text-stone-700">{{ $scanner->document_no ?? '—' }}</td>
                <td class="px-1 py-1 text-stone-700">{{ $scanner->document_date ?? '—' }}</td>
                <td class="px-1 py-1 text-stone-700">{{ $scanner->document_type ?? '—' }}</td>
                <td class="px-1 py-1 text-stone-700">{{ $scanner->remarks ?? '—' }}</td>
                <td class="px-1 py-1">
                    @php $__files = array_filter((array)($scanner->upload_scan_copy ?? [])); @endphp
                    @if(count($__files))
                    <div class="flex items-center gap-1">
                        @foreach($__files as $__fp)
                        <button type="button" onclick="openFilePreview('{{ Storage::url($__fp) }}', 'file', '{{ basename($__fp) }}')" class="act-btn act-edit" title="{{ basename($__fp) }}">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        </button>
                        @endforeach
                    </div>
                    @else
                    <span class="text-stone-300">—</span>
                    @endif
                </td>
                <td class="px-1 py-1 text-stone-700">{{ $scanner->other ?? '—' }}</td>
                <td class="px-6 py-1 text-right">
                    <div class="act-group justify-end">
                        <a href="{{ route('generated.scanners.show', $scanner) }}" class="act-btn act-edit" title="View"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
                        <a href="{{ route('generated.scanners.edit', $scanner) }}" class="act-btn act-edit" title="Edit"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>
                        <form method="POST" action="{{ route('generated.scanners.destroy', $scanner) }}" onsubmit="return confirm('Delete this record?')" style="display:contents">@csrf @method('DELETE')<button type="submit" class="act-btn act-delete" title="Delete"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($scanners->hasPages())
    <div class="px-6 py-1 border-t border-stone-100 flex items-center justify-between gap-4">
        <p class="text-xs text-stone-400">Showing {{ $scanners->firstItem() }}–{{ $scanners->lastItem() }} of {{ $scanners->total() }} results</p>
        <div class="flex items-center gap-1">
            @if($scanners->onFirstPage())<span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-stone-300 cursor-not-allowed"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></span>@else<a href="{{ $scanners->previousPageUrl() }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-stone-500 hover:bg-stone-100 transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></a>@endif
            @foreach($scanners->getUrlRange(1, $scanners->lastPage()) as $pg => $url)<a href="{{ $url }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-medium transition-colors {{ $pg == $scanners->currentPage() ? 'bg-red-800 text-white' : 'text-stone-600 hover:bg-stone-100' }}">{{ $pg }}</a>@endforeach
            @if($scanners->hasMorePages())<a href="{{ $scanners->nextPageUrl() }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-stone-500 hover:bg-stone-100 transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>@else<span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-stone-300 cursor-not-allowed"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></span>@endif
        </div>
    </div>
    @endif
    @endif
</div>

{{-- Export Log Offcanvas --}}
<div id="exportLogOverlay" onclick="closeExportLog()" class="fixed inset-0 bg-black/40 z-40 hidden"></div>
<div id="exportLogPanel" class="fixed top-0 right-0 h-full w-96 bg-white shadow-2xl z-50 translate-x-full transition-transform duration-300 flex flex-col">
    <div class="flex items-center justify-between px-5 py-4 border-b border-stone-100">
        <div>
            <h4 class="text-sm font-semibold text-stone-800">Export History</h4>
            <p class="text-xs text-stone-400 mt-0.5">Scanner</p>
        </div>
        <button onclick="closeExportLog()" class="w-8 h-8 flex items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
    </div>
    <div class="flex-1 overflow-y-auto p-4 space-y-2">
        @forelse($exportLogs as $log)
        <div class="flex items-center justify-between gap-3 px-4 py-3 rounded-xl border border-stone-100 bg-stone-50 hover:bg-white hover:border-stone-200 transition-colors">
            <div class="min-w-0">
                <p class="text-xs font-medium text-stone-700 truncate">{{ $log->file_name }}</p>
                <p class="text-xs text-stone-400 mt-0.5">{{ $log->row_count }} rows &middot; {{ $log->created_at->format('d M Y, H:i') }}</p>
                @if($log->user)<p class="text-xs text-stone-400">by {{ $log->user->name }}</p>@endif
            </div>
            <a href="{{ route('generated.scanners.export.download', $log) }}" class="shrink-0 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-green-50 text-green-700 hover:bg-green-100 transition-colors"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>Download</a>
        </div>
        @empty
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <div class="w-12 h-12 rounded-1xl bg-stone-100 flex items-center justify-center mb-3"><svg class="w-6 h-6 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>
            <p class="text-sm font-medium text-stone-500">No exports yet</p>
            <p class="text-xs text-stone-400 mt-1">Click Export to generate your first file.</p>
        </div>
        @endforelse
    </div>
</div>
<script>
function openExportLog(){document.getElementById('exportLogOverlay').classList.remove('hidden');document.getElementById('exportLogPanel').classList.remove('translate-x-full');}
function closeExportLog(){document.getElementById('exportLogOverlay').classList.add('hidden');document.getElementById('exportLogPanel').classList.add('translate-x-full');}
</script>

{{-- File Preview Modal --}}
<div id="filePreviewOverlay" class="fixed inset-0 bg-black/60 z-50 hidden" style="align-items:center;justify-content:center;">
    <div onclick="event.stopPropagation()" class="bg-white rounded-2xl shadow-2xl w-full mx-4 overflow-hidden" style="max-width:640px;">
        <div class="flex items-center justify-between px-5 py-4 border-b border-stone-100">
            <p id="filePreviewName" class="text-sm font-semibold text-stone-800 truncate mr-4"></p>
            <div class="flex items-center gap-2 shrink-0">
                <a id="filePreviewDownload" href="#" download class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-stone-100 text-stone-600 hover:bg-stone-200 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download
                </a>
                <button onclick="closeFilePreview()" class="w-8 h-8 flex items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
        <div id="filePreviewBody" class="p-5 flex items-center justify-center bg-stone-50" style="min-height:200px;">
        </div>
    </div>
</div>
<script>
var _imageExts = ['jpg','jpeg','png','gif','webp','svg','bmp','ico','tiff','avif'];
var _videoExts = ['mp4','webm','ogg','mov'];
function _fileExt(name) { return (name.split('.').pop() || '').toLowerCase(); }
function openFilePreview(url, fieldType, name) {
    document.getElementById('filePreviewName').textContent = name;
    document.getElementById('filePreviewDownload').href = url;
    var body = document.getElementById('filePreviewBody');
    var ext = _fileExt(name);
    if (ext === 'pdf') {
        body.style.padding = '0'; body.style.minHeight = '520px';
        body.innerHTML = '<iframe src="' + url + '#toolbar=1&navpanes=0" style="width:100%;height:520px;border:none;display:block;" allowfullscreen></iframe>';
    } else if (_imageExts.indexOf(ext) !== -1) {
        body.style.padding = '20px'; body.style.minHeight = '220px';
        body.innerHTML = '<img src="' + url + '" alt="' + name + '" style="max-height:460px;max-width:100%;border-radius:8px;object-fit:contain;">';
    } else if (_videoExts.indexOf(ext) !== -1) {
        body.style.padding = '20px'; body.style.minHeight = '220px';
        body.innerHTML = '<video controls style="max-height:420px;max-width:100%;border-radius:8px;"><source src="' + url + '"><p style="font-size:13px;color:#78716c;">Your browser does not support video playback.</p></video>';
    } else {
        body.style.padding = '20px'; body.style.minHeight = '220px';
        body.innerHTML = '<div style="display:flex;flex-direction:column;align-items:center;gap:12px;padding:32px 0;"><svg style="width:48px;height:48px;color:#d4d4d4;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><p style="font-size:13px;color:#78716c;">No preview available for <strong>' + ext.toUpperCase() + '</strong> files.</p><a href="' + url + '" target="_blank" style="display:inline-flex;align-items:center;gap:6px;padding:8px 20px;border-radius:10px;background:#991b1b;color:#fff;font-size:13px;font-weight:500;text-decoration:none;">Open File</a></div>';
    }
    document.getElementById('filePreviewOverlay').style.display = 'flex';
}
function closeFilePreview() {
    document.getElementById('filePreviewOverlay').style.display = 'none';
    var body = document.getElementById('filePreviewBody');
    body.innerHTML = ''; body.style.padding = ''; body.style.minHeight = '220px';
}
document.getElementById('filePreviewOverlay').addEventListener('click', function(e) { if (e.target === this) closeFilePreview(); });
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeFilePreview(); });
</script>
@endsection
