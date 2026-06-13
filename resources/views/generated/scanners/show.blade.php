@extends('layouts.app')
@section('content')
@php use Illuminate\Support\Facades\Storage; @endphp
<div class="bg-white border border-stone-200 rounded-1xl overflow-hidden">
    <div class="px-6 py-5 border-b border-stone-100 flex items-center justify-between">
        <div>
            <h3 class="text-sm font-semibold text-stone-800">Scanner — Detail</h3>
            <p class="text-xs text-stone-400 mt-0.5">Record #{{ $scanner->id }}</p>
        </div>
        <a href="{{ route('generated.scanners.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-stone-100 text-stone-600 hover:bg-stone-200 transition-colors"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Back</a>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-3 gap-5">
            <div class="col-span-1">
                <label class="block text-sm font-medium text-stone-700 mb-1.5">Document Title</label>
                <input type="text" disabled value="{{ $scanner->title ?? '—' }}" class="w-full px-3.5 py-2.5 text-sm border rounded-xl border-stone-200 bg-stone-50 text-stone-600 cursor-not-allowed">
            </div>
            <div class="col-span-1">
                <label class="block text-sm font-medium text-stone-700 mb-1.5">Document No</label>
                <input type="text" disabled value="{{ $scanner->document_no ?? '—' }}" class="w-full px-3.5 py-2.5 text-sm border rounded-xl border-stone-200 bg-stone-50 text-stone-600 cursor-not-allowed">
            </div>
            <div class="col-span-1">
                <label class="block text-sm font-medium text-stone-700 mb-1.5">Document Date</label>
                <input type="text" disabled value="{{ $scanner->document_date ?? '—' }}" class="w-full px-3.5 py-2.5 text-sm border rounded-xl border-stone-200 bg-stone-50 text-stone-600 cursor-not-allowed">
            </div>
            <div class="col-span-3">
                <label class="block text-sm font-medium text-stone-700 mb-1.5">Document Type</label>
                <input type="text" disabled value="{{ $scanner->document_type ?? '—' }}" class="w-full px-3.5 py-2.5 text-sm border rounded-xl border-stone-200 bg-stone-50 text-stone-600 cursor-not-allowed">
            </div>
            <div class="col-span-3">
                <label class="block text-sm font-medium text-stone-700 mb-1.5">Remarks</label>
                <input type="text" disabled value="{{ $scanner->remarks ?? '—' }}" class="w-full px-3.5 py-2.5 text-sm border rounded-xl border-stone-200 bg-stone-50 text-stone-600 cursor-not-allowed">
            </div>
            <div class="col-span-1">
                <label class="block text-sm font-medium text-stone-700 mb-1.5">Upload Scan Copy</label>
                @php $__files = array_filter((array)($scanner->upload_scan_copy ?? [])); @endphp
                @if(count($__files))
                <div class="flex flex-wrap gap-2">
                    @foreach($__files as $__fp)
                    <button type="button" onclick="openFilePreview('{{ Storage::url($__fp) }}', 'file', '{{ basename($__fp) }}')" class="group relative rounded-lg border border-stone-200 overflow-hidden hover:border-red-300 transition focus:outline-none" title="{{ basename($__fp) }}">
                        <img src="{{ Storage::url($__fp) }}" alt="{{ basename($__fp) }}" class="h-20 w-20 object-cover" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                        <span style="display:none;" class="h-20 w-20 flex-col items-center justify-center bg-stone-50 gap-1"><svg class="w-6 h-6 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><span class="text-xs text-stone-400 uppercase">{{ strtoupper(pathinfo($__fp, PATHINFO_EXTENSION)) }}</span></span>
                        <span class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition flex items-center justify-center"><svg class="w-5 h-5 text-white opacity-0 group-hover:opacity-100 drop-shadow transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg></span>
                    </button>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-stone-400">—</p>
                @endif
            </div>
            <div class="col-span-1">
                <label class="block text-sm font-medium text-stone-700 mb-1.5">other</label>
                <input type="text" disabled value="{{ $scanner->other ?? '—' }}" class="w-full px-3.5 py-2.5 text-sm border rounded-xl border-stone-200 bg-stone-50 text-stone-600 cursor-not-allowed">
            </div>
        </div>
    </div>
    <div class="px-6 py-1 bg-stone-50 border-t border-stone-100 flex items-center justify-end">
        <a href="{{ route('generated.scanners.edit', $scanner) }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-red-800 hover:bg-red-700 text-white text-sm font-medium transition-colors shadow-sm"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>Edit</a>
    </div>
</div>

{{-- File Preview Modal --}}
<div id="filePreviewOverlay" class="fixed inset-0 bg-black/60 z-50 hidden" style="align-items:center;justify-content:center;">
    <div onclick="event.stopPropagation()" class="bg-white rounded-2xl shadow-2xl w-full mx-4 overflow-hidden" style="max-width:720px;">
        <div class="flex items-center justify-between px-5 py-4 border-b border-stone-100">
            <p id="filePreviewName" class="text-sm font-semibold text-stone-800 truncate mr-4"></p>
            <div class="flex items-center gap-2 shrink-0">
                <a id="filePreviewDownload" href="#" download class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-stone-100 text-stone-600 hover:bg-stone-200 transition-colors"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>Download</a>
                <button onclick="closeFilePreview()" class="w-8 h-8 flex items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
        </div>
        <div id="filePreviewBody" class="flex items-center justify-center bg-stone-50" style="min-height:220px;"></div>
    </div>
</div>
<script>
var _imageExts=['jpg','jpeg','png','gif','webp','svg','bmp','ico','tiff','avif'];
var _videoExts=['mp4','webm','ogg','mov'];
function _fileExt(n){return(n.split('.').pop()||'').toLowerCase();}
function openFilePreview(url,ft,name){
    document.getElementById('filePreviewName').textContent=name;
    document.getElementById('filePreviewDownload').href=url;
    var b=document.getElementById('filePreviewBody'),ext=_fileExt(name);
    if(ext==='pdf'){b.style.padding='0';b.style.minHeight='520px';b.innerHTML='<iframe src="'+url+'#toolbar=1&navpanes=0" style="width:100%;height:520px;border:none;display:block;" allowfullscreen></iframe>';}
    else if(_imageExts.indexOf(ext)!==-1){b.style.padding='20px';b.style.minHeight='220px';b.innerHTML='<img src="'+url+'" alt="'+name+'" style="max-height:460px;max-width:100%;border-radius:8px;object-fit:contain;">';
    }else if(_videoExts.indexOf(ext)!==-1){b.style.padding='20px';b.style.minHeight='220px';b.innerHTML='<video controls style="max-height:420px;max-width:100%;border-radius:8px;"><source src="'+url+'"><p style="font-size:13px;color:#78716c;">Your browser does not support video playback.</p></video>';
    }else{b.style.padding='20px';b.style.minHeight='220px';b.innerHTML='<div style="display:flex;flex-direction:column;align-items:center;gap:12px;padding:32px 0;"><svg style="width:48px;height:48px;color:#d4d4d4;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><p style="font-size:13px;color:#78716c;">No preview for <strong>'+ext.toUpperCase()+'</strong> files.</p><a href="'+url+'" target="_blank" style="display:inline-flex;align-items:center;gap:6px;padding:8px 20px;border-radius:10px;background:#991b1b;color:#fff;font-size:13px;font-weight:500;text-decoration:none;">Open File</a></div>';}
    document.getElementById('filePreviewOverlay').style.display='flex';
}
function closeFilePreview(){
    document.getElementById('filePreviewOverlay').style.display='none';
    var b=document.getElementById('filePreviewBody');b.innerHTML='';b.style.padding='';b.style.minHeight='220px';
}
document.getElementById('filePreviewOverlay').addEventListener('click',function(e){if(e.target===this)closeFilePreview();});
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeFilePreview();});
</script>
@endsection
