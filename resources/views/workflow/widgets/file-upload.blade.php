@php $config = $widget->config ?? []; @endphp
<div class="bg-white rounded-xl border border-stone-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-stone-200">
        <h3 class="text-sm font-bold text-stone-700">{{ $widget->title }}</h3>
    </div>
    <div class="p-5">
        <div class="border-2 border-dashed border-stone-300 rounded-xl p-8 text-center hover:border-red-400 transition-colors cursor-pointer" id="dropzone-{{ $widget->id }}">
            <svg class="w-12 h-12 mx-auto text-stone-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            <p class="text-sm font-medium text-stone-600 mb-1">Drag & drop files here</p>
            <p class="text-xs text-stone-400">or click to browse</p>
            <p class="text-[10px] text-stone-400 mt-2">Max size: {{ $config['max_file_size_mb'] ?? 10 }}MB</p>
            <input type="file" class="hidden" multiple>
        </div>
        <div class="mt-4 space-y-2" id="file-list-{{ $widget->id }}">
            {{-- Uploaded files will appear here --}}
        </div>
    </div>
</div>
