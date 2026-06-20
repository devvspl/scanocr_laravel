@extends('layouts.app')
@section('title', 'PDF Compression Playground')
@section('page-title', 'PDF Compression Playground')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-stone-600">Home</a>
    <span>/</span>
    <span class="text-stone-600">Tools</span>
    <span>/</span>
    <span class="text-stone-600">PDF Compressor</span>
@endsection

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-stone-800">PDF Compression Playground</h2>
            <p class="text-sm text-stone-500 mt-1">Upload and compress PDF files using multiple engines</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {{-- LEFT COLUMN: Upload + Controls --}}
        <div class="lg:col-span-5 space-y-4">
            {{-- Upload Zone --}}
            <div class="bg-white border border-stone-200 rounded-xl p-6">
                <div id="drop-zone"
                     class="border-2 border-dashed border-stone-300 rounded-xl p-8 text-center cursor-pointer transition-all hover:border-red-400 hover:bg-red-50/30">
                    <svg class="w-12 h-12 mx-auto text-stone-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <p class="text-sm font-medium text-stone-700">Drag & Drop or click to upload PDF</p>
                    <p class="text-xs text-stone-400 mt-1">PDF files only — Max 500MB</p>
                    <input type="file" id="pdf-input" accept=".pdf" class="hidden">
                </div>

                {{-- File Info --}}
                <div id="file-info" class="hidden mt-4 p-3 bg-stone-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <svg class="w-8 h-8 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <div class="min-w-0 flex-1">
                            <p id="file-name" class="text-sm font-medium text-stone-800 truncate"></p>
                            <p id="file-size" class="text-xs text-stone-400"></p>
                        </div>
                        <button id="btn-remove-file" class="text-stone-400 hover:text-red-500 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Compression Settings --}}
                <div id="settings-panel" class="hidden mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-stone-600 mb-2">Compression Engine</label>
                        <select id="engine" class="w-full text-sm border border-stone-200 rounded-lg px-3 py-2 bg-white focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none">
                            <option value="auto" selected>Auto (Best Available)</option>
                            <option value="ghostscript">Ghostscript</option>
                            <option value="pikepdf">pikepdf (Python)</option>
                            <option value="pymupdf">PyMuPDF</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-stone-600 mb-2">Quality Level</label>
                        <select id="quality" class="w-full text-sm border border-stone-200 rounded-lg px-3 py-2 bg-white focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none">
                            <option value="screen">Screen (Lowest quality, smallest size)</option>
                            <option value="ebook" selected>eBook (Good quality)</option>
                            <option value="printer">Printer (High quality)</option>
                            <option value="prepress">Prepress (Highest quality)</option>
                        </select>
                    </div>
                </div>

                {{-- Error Message --}}
                <div id="upload-error" class="hidden mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700"></div>

                {{-- Compress Button --}}
                <button id="btn-compress" disabled
                        class="mt-4 w-full py-2.5 px-4 bg-red-700 text-white text-sm font-medium rounded-lg hover:bg-red-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    <svg class="w-4 h-4 inline mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Compress PDF
                </button>

                {{-- Progress Bar --}}
                <div id="progress-bar-wrap" class="hidden mt-4">
                    <div class="flex items-center justify-between mb-1">
                        <span id="progress-label" class="text-xs text-stone-500">Compressing...</span>
                        <span id="progress-pct" class="text-xs font-medium text-stone-700">0%</span>
                    </div>
                    <div class="w-full bg-stone-200 rounded-full h-2">
                        <div id="compress-progress" class="bg-red-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                    <p id="progress-hint" class="text-[10px] text-stone-400 mt-1">This may take 1-30 minutes depending on file size...</p>
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN: Results + Preview --}}
        <div class="lg:col-span-7 space-y-4">
            {{-- Compression Results --}}
            <div id="results-section" class="hidden bg-white border border-stone-200 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-stone-700 mb-4">Compression Results</h3>

                {{-- Stats Cards --}}
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="bg-gradient-to-r from-blue-50 to-cyan-50 border border-blue-200 rounded-lg p-3">
                        <p class="text-xs text-stone-500">Original Size</p>
                        <p id="original-size" class="text-lg font-bold text-stone-800">—</p>
                        <p class="text-xs text-stone-400" id="original-pages">— pages</p>
                    </div>
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg p-3">
                        <p class="text-xs text-stone-500">Compressed Size</p>
                        <p id="compressed-size" class="text-lg font-bold text-stone-800">—</p>
                        <p class="text-xs text-stone-400">Saved: <span id="saved-bytes">—</span></p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-5">
                    <div class="bg-gradient-to-r from-orange-50 to-amber-50 border border-orange-200 rounded-lg p-3">
                        <p class="text-xs text-stone-500">Compression Ratio</p>
                        <p id="compression-ratio" class="text-2xl font-bold text-orange-700">—</p>
                        <div class="mt-1 w-full bg-orange-100 rounded-full h-2">
                            <div id="ratio-bar" class="bg-orange-600 h-2 rounded-full transition-all duration-700" style="width: 0%"></div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-r from-purple-50 to-violet-50 border border-purple-200 rounded-lg p-3">
                        <p class="text-xs text-stone-500">Processing Time</p>
                        <p id="processing-time" class="text-lg font-bold text-purple-700">—</p>
                        <p class="text-xs text-stone-400">Engine: <span id="engine-used">—</span></p>
                    </div>
                </div>

                {{-- Download Button --}}
                <button id="btn-download" disabled
                        class="w-full py-2.5 px-4 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 disabled:opacity-50 transition-colors">
                    <svg class="w-4 h-4 inline mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-4-4m4 4l4-4M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                    </svg>
                    Download Compressed PDF
                </button>

                {{-- Success Message --}}
                <div id="success-msg" class="hidden mt-3 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700"></div>
            </div>

            {{-- Recent Jobs History --}}
            @if($jobs->count() > 0)
            <div class="bg-white border border-stone-200 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-stone-700 mb-4">Recent Compressions</h3>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    @foreach($jobs as $job)
                    <div class="flex items-center justify-between p-3 bg-stone-50 rounded-lg">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-stone-800 truncate">{{ $job->original_filename }}</p>
                            <div class="flex items-center gap-3 mt-1">
                                <span class="text-[10px] text-stone-400">{{ number_format($job->original_size / 1024, 1) }}KB → {{ number_format($job->compressed_size / 1024, 1) }}KB</span>
                                <span class="text-[10px] text-stone-400">{{ number_format($job->compression_ratio * 100, 1) }}%</span>
                                <span class="text-[10px] text-stone-400">{{ $job->processing_time }}s</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            @if($job->status === 'done')
                                <a href="{{ route('tools.pdf-compressor.download', $job->id) }}"
                                   class="w-6 h-6 flex items-center justify-center rounded text-green-600 hover:bg-green-50 transition-colors" title="Download">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-4-4m4 4l4-4M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                                    </svg>
                                </a>
                            @endif
                            <button class="w-6 h-6 flex items-center justify-center rounded text-red-400 hover:bg-red-50 transition-colors btn-delete" data-id="{{ $job->id }}" title="Delete">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    let selectedFile = null;
    const dropZone = $('#drop-zone');
    const fileInput = $('#pdf-input');
    const maxSize = 500 * 1024 * 1024; // 500MB

    // Drag & Drop
    dropZone.on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('border-red-400 bg-red-50/50');
    }).on('dragleave drop', function(e) {
        e.preventDefault();
        $(this).removeClass('border-red-400 bg-red-50/50');
    }).on('drop', function(e) {
        const files = e.originalEvent.dataTransfer.files;
        if (files.length) handleFile(files[0]);
    }).on('click', function(e) {
        if (e.target === fileInput[0]) return;
        fileInput.trigger('click');
    });

    fileInput.on('change', function() {
        if (this.files.length) handleFile(this.files[0]);
    });

    function handleFile(file) {
        $('#upload-error').hide();

        if (file.type !== 'application/pdf') {
            showError('Invalid file type. Please upload PDF files only.');
            return;
        }
        if (file.size > maxSize) {
            showError('File too large. Maximum size is 500MB.');
            return;
        }

        selectedFile = file;
        $('#file-name').text(file.name);
        $('#file-size').text(formatSize(file.size));
        $('#file-info').show();
        $('#settings-panel').show();
        $('#btn-compress').prop('disabled', false);
    }

    $('#btn-remove-file').on('click', function() {
        selectedFile = null;
        fileInput.val('');
        $('#file-info').hide();
        $('#settings-panel').hide();
        $('#btn-compress').prop('disabled', true);
        $('#results-section').hide();
    });

    // Compress
    $('#btn-compress').on('click', function() {
        if (!selectedFile) return;

        const formData = new FormData();
        formData.append('pdf_file', selectedFile);
        formData.append('engine', $('#engine').val());
        formData.append('quality', $('#quality').val());
        formData.append('_token', '{{ csrf_token() }}');

        // Reset UI
        $('#progress-bar-wrap').show();
        $('#results-section').hide();
        $('#upload-error').hide();
        $('#success-msg').hide();
        $('#progress-label').text('Uploading...');
        $(this).prop('disabled', true);

        // Fake progress for upload phase
        let progress = 0;
        const uploadInterval = setInterval(() => {
            progress += Math.random() * 10;
            if (progress >= 50) {
                clearInterval(uploadInterval);
                progress = 50;
                $('#progress-label').text('Compressing PDF...');
            }
            $('#compress-progress').css('width', progress + '%');
            $('#progress-pct').text(Math.round(progress) + '%');
        }, 200);

        $.ajax({
            url: '{{ route("tools.pdf-compressor.compress") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 1800000, // 30 minutes
            success: function(res) {
                clearInterval(uploadInterval);
                $('#compress-progress').css('width', '100%');
                $('#progress-pct').text('100%');

                if (res.success) {
                    displayResults(res);
                    $('#success-msg').text('PDF compressed successfully!').show();
                    setTimeout(() => location.reload(), 2000); // Refresh to show in history
                } else {
                    showError(res.message || 'Compression failed.');
                }
            },
            error: function(xhr) {
                clearInterval(uploadInterval);
                const msg = xhr.responseJSON?.message || 'Compression failed. Please try again.';
                showError(msg);
            },
            complete: function() {
                $('#btn-compress').prop('disabled', false);
                $('#progress-bar-wrap').hide();
            }
        });
    });

    function displayResults(data) {
        $('#original-size').text(formatSize(data.original_size));
        $('#original-pages').text(data.original_pages + ' pages');
        $('#compressed-size').text(formatSize(data.compressed_size));
        $('#saved-bytes').text(formatSize(data.saved_bytes));
        $('#compression-ratio').text((data.ratio * 100).toFixed(1) + '%');
        $('#processing-time').text(data.processing_time + 's');
        $('#engine-used').text(data.engine_used);

        // Animate progress bar for ratio
        setTimeout(() => {
            $('#ratio-bar').css('width', (data.ratio * 100) + '%');
        }, 100);

        $('#btn-download').prop('disabled', false).off('click').on('click', function() {
            window.location = data.download_url;
        });

        $('#results-section').show();
    }

    function showError(message) {
        $('#upload-error').text(message).show();
    }

    function formatSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    // Delete job history
    $('.btn-delete').on('click', function() {
        if (!confirm('Delete this compression job?')) return;
        
        const id = $(this).data('id');
        const $row = $(this).closest('.bg-stone-50');
        
        $.ajax({
            url: '/tools/pdf-compressor/' + id,
            method: 'DELETE',
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            success: () => $row.fadeOut(300, () => $row.remove()),
            error: () => alert('Failed to delete job.')
        });
    });
});
</script>
@endpush