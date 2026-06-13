@extends('layouts.app')

@section('title', 'AI Document Predictor')
@section('page-title', 'AI Document Type Predictor')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-stone-600">Home</a>
    <span>/</span>
    <span class="text-stone-600">AI Doc Predictor</span>
@endsection

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-stone-800">AI Document Type Predictor</h2>
            <p class="text-sm text-stone-500 mt-1">Upload a document to predict its classification using AI</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('document-ai.playground') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-red-700 rounded-lg">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Playground
            </a>
            <a href="{{ route('document-ai.logs') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs text-stone-600 bg-white border border-stone-200 rounded-lg hover:bg-stone-50">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Logs
            </a>
            <a href="{{ route('document-ai.analytics') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs text-stone-600 bg-white border border-stone-200 rounded-lg hover:bg-stone-50">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Analytics
            </a>
            <a href="{{ route('document-ai.settings') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs text-stone-600 bg-white border border-stone-200 rounded-lg hover:bg-stone-50">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Settings
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {{-- LEFT COLUMN: Upload + Preview --}}
        <div class="lg:col-span-5 space-y-4">
            {{-- Upload Zone --}}
            <div class="bg-white border border-stone-200 rounded-xl p-6">
                <div id="drop-zone"
                     class="border-2 border-dashed border-stone-300 rounded-xl p-8 text-center cursor-pointer transition-all hover:border-red-400 hover:bg-red-50/30">
                    <svg class="w-12 h-12 mx-auto text-stone-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <p class="text-sm font-medium text-stone-700">Drag & Drop or click to upload</p>
                    <p class="text-xs text-stone-400 mt-1">PDF, JPG, JPEG, PNG — Max 20MB</p>
                    <input type="file" id="file-input" accept=".pdf,.jpg,.jpeg,.png" class="hidden">
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

                {{-- Error Message --}}
                <div id="upload-error" class="hidden mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700"></div>

                {{-- Upload Button --}}
                <button id="btn-predict" disabled
                        class="mt-4 w-full py-2.5 px-4 bg-red-700 text-white text-sm font-medium rounded-lg hover:bg-red-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    <svg class="w-4 h-4 inline mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Analyze & Predict
                </button>

                {{-- Progress Bar --}}
                <div id="progress-bar-wrap" class="hidden mt-4">
                    <div class="flex items-center justify-between mb-1">
                        <span id="progress-label" class="text-xs text-stone-500">Uploading...</span>
                        <span id="progress-pct" class="text-xs font-medium text-stone-700">0%</span>
                    </div>
                    <div class="w-full bg-stone-200 rounded-full h-2">
                        <div id="upload-progress" class="bg-red-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                    <p id="progress-hint" class="text-[10px] text-stone-400 mt-1 hidden">AI models are analyzing your document. This may take 30-60 seconds on first run...</p>
                </div>
            </div>

            {{-- File Preview (below upload form) --}}
            <div id="preview-section" class="hidden bg-white border border-stone-200 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-stone-700 mb-3">File Preview</h3>
                <img id="preview-img" class="hidden max-h-80 rounded-lg border border-stone-200 mx-auto" alt="Preview">
                <iframe id="preview-pdf-iframe" class="hidden w-full rounded-lg border border-stone-200" style="height:500px" frameborder="0"></iframe>
            </div>
        </div>

        {{-- RIGHT COLUMN: Results --}}
        <div class="lg:col-span-7 space-y-4">
            {{-- OCR Text Section --}}
            <div id="ocr-section" class="hidden bg-white border border-stone-200 rounded-xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-stone-700">Extracted Text (OCR)</h3>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-stone-500">Confidence:
                            <span id="ocr-confidence-badge" class="font-semibold text-green-700"></span>
                        </span>
                        <span class="text-xs text-stone-500">Pages:
                            <span id="ocr-page-count" class="font-semibold text-stone-700"></span>
                        </span>
                    </div>
                </div>
                <textarea id="ocr-textarea" readonly rows="6"
                          class="w-full text-xs font-mono text-stone-700 bg-stone-50 border border-stone-200 rounded-lg p-3 resize-y"></textarea>
            </div>

            {{-- AI Prediction Section --}}
            <div id="prediction-section" class="hidden bg-white border border-stone-200 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-stone-700 mb-4">AI Prediction</h3>

                {{-- Top Prediction Card --}}
                <div class="bg-gradient-to-r from-red-50 to-orange-50 border border-red-200 rounded-xl p-4 mb-1">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs text-stone-500">Predicted Type</p>
                            <p id="pred-type" class="text-lg font-bold text-stone-800"></p>
                        </div>
                        <div class="text-right">
                            <p id="pred-confidence" class="text-2xl font-bold text-red-700"></p>
                            <p class="text-xs text-stone-500">confidence</p>
                        </div>
                    </div>
                    <div class="mt-3 w-full bg-red-100 rounded-full h-2.5">
                        <div id="pred-confidence-bar" class="bg-red-600 h-2.5 rounded-full transition-all duration-700" style="width: 0%"></div>
                    </div>
                </div>

                {{-- All Scores --}}
                <div class="mb-5">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-medium text-stone-500 uppercase tracking-wide">All Predictions</p>
                        <button id="btn-show-reasoning" class="text-xs text-red-600 hover:text-red-800 font-medium hidden">
                            View Reasoning →
                        </button>
                    </div>
                    <div id="all-scores-container" class="space-y-1.5 max-h-48 overflow-y-auto cursor-pointer"></div>
                    <p class="text-xs text-stone-400 mt-2 italic">Click any row to see prediction reasoning</p>
                </div>

                {{-- Confirm Classification --}}
                <div class="border-t border-stone-200 pt-4">
                    <p class="text-sm font-medium text-stone-700 mb-3">Confirm Classification</p>
                    <input type="hidden" id="hidden-prediction-id" value="">

                    <div class="space-y-3">
                        <div>
                            <label class="text-xs text-stone-500 mb-1 block">Document Type</label>
                            <select id="confirm-basis-select"
                                    class="w-full text-sm border border-stone-200 rounded-lg px-3 py-2 bg-white focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none">
                                @foreach($types as $type)
                                    <option value="{{ $type->id }}">{{ $type->label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="text-xs text-stone-500 mb-1 block">Remark (optional)</label>
                            <input type="text" id="user-remark" placeholder="Add a note..."
                                   class="w-full text-sm border border-stone-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none">
                        </div>

                        <div class="flex gap-2">
                            <button id="btn-save-classification"
                                    class="flex-1 py-2.5 px-4 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Save Classification
                            </button>
                            <button id="btn-train-ai"
                                    class="flex-1 py-2.5 px-4 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                                Train AI with this
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Save Success Message --}}
                <div id="save-success" class="hidden mt-3 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700"></div>
            </div>
        </div>
    </div>
</div>

{{-- Reasoning Modal --}}
<div id="reasoning-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display:none">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[85vh] overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-stone-200 bg-stone-50">
            <h3 class="text-lg font-semibold text-stone-800">Prediction Reasoning</h3>
            <button onclick="$('#reasoning-modal').hide()" class="text-stone-400 hover:text-stone-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto p-6 space-y-5" id="reasoning-content">
            {{-- Populated by JS --}}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    let selectedFile = null;
    const dropZone = $('#drop-zone');
    const fileInput = $('#file-input');
    const maxSize = 20 * 1024 * 1024; // 20MB

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
        if (e.target === fileInput[0]) return; // prevent loop
        fileInput.trigger('click');
    });

    fileInput.on('click', function(e) {
        e.stopPropagation(); // prevent bubbling back to drop zone
    }).on('change', function() {
        if (this.files.length) handleFile(this.files[0]);
    });

    function handleFile(file) {
        $('#upload-error').hide();

        const allowed = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        if (!allowed.includes(file.type)) {
            showError('Invalid file type. Please upload PDF, JPG, JPEG, or PNG.');
            return;
        }
        if (file.size > maxSize) {
            showError('File too large. Maximum size is 20MB.');
            return;
        }

        selectedFile = file;
        $('#file-name').text(file.name);
        $('#file-size').text(formatSize(file.size));
        $('#file-info').show();
        $('#btn-predict').prop('disabled', false);
    }

    $('#btn-remove-file').on('click', function() {
        selectedFile = null;
        fileInput.val('');
        $('#file-info').hide();
        $('#btn-predict').prop('disabled', true);
    });

    // Predict
    $('#btn-predict').on('click', function() {
        if (!selectedFile) return;

        const formData = new FormData();
        formData.append('file', selectedFile);
        formData.append('_token', '{{ csrf_token() }}');

        // Reset UI
        $('#progress-bar-wrap').show();
        $('#prediction-section').hide();
        $('#ocr-section').hide();
        $('#preview-section').hide();
        $('#upload-error').hide();
        $('#save-success').hide();
        $('#progress-label').text('Uploading file...');
        $('#progress-hint').hide();
        $(this).prop('disabled', true);

        var processingInterval = null;

        $.ajax({
            url: '{{ route("document-ai.predict") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 300000, // 5 minutes — ML models need time on first load
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        // Upload phase: 0% to 50%
                        var pct = Math.round((e.loaded / e.total) * 50);
                        $('#upload-progress').css('width', pct + '%');
                        $('#progress-pct').text(pct + '%');
                    }
                });
                xhr.upload.addEventListener('load', function() {
                    // Upload done, now server is processing
                    $('#progress-label').text('Analyzing document (OCR + AI)...');
                    $('#progress-hint').show();
                    $('#upload-progress').css('width', '50%');
                    $('#progress-pct').text('50%');

                    // Animate from 50% to 95% slowly over 60 seconds
                    var currentPct = 50;
                    processingInterval = setInterval(function() {
                        if (currentPct < 95) {
                            currentPct += 0.75;
                            $('#upload-progress').css('width', currentPct + '%');
                            $('#progress-pct').text(Math.round(currentPct) + '%');
                        }
                    }, 1000);
                });
                return xhr;
            },
            success: function(res) {
                if (processingInterval) clearInterval(processingInterval);
                $('#upload-progress').css('width', '100%');
                $('#progress-pct').text('100%');
                $('#progress-label').text('Done!');

                setTimeout(function() {
                    $('#progress-bar-wrap').hide();
                    if (res.success) {
                        populatePrediction(res);
                    } else {
                        showError(res.message || 'Prediction failed.');
                    }
                }, 500);
            },
            error: function(xhr) {
                if (processingInterval) clearInterval(processingInterval);
                $('#progress-bar-wrap').hide();
                const msg = xhr.responseJSON?.message || 'Upload failed. Please try again.';
                showError(msg);
            },
            complete: function() {
                if (processingInterval) clearInterval(processingInterval);
                $('#upload-progress').css('width', '0%');
                $('#progress-pct').text('0%');
                $('#btn-predict').prop('disabled', false);
            }
        });
    });

    function populatePrediction(res) {
        // Preview
        if (['jpg', 'jpeg', 'png'].includes(res.file_ext)) {
            $('#preview-img').attr('src', res.file_url).show();
            $('#preview-pdf-iframe').hide();
        } else {
            $('#preview-pdf-iframe').attr('src', res.file_url).show();
            $('#preview-img').hide();
        }
        $('#preview-section').show();

        // OCR Text
        $('#ocr-textarea').val(res.ocr_text || '(Text extraction not available on this server — use Train AI to add text manually)');
        $('#ocr-confidence-badge').text(res.ocr_confidence ? res.ocr_confidence + '%' : 'N/A');
        $('#ocr-page-count').text(res.page_count);
        $('#ocr-section').show();

        // Prediction
        $('#pred-type').text(res.prediction.basis_name);
        $('#pred-confidence').text(res.prediction.confidence + '%');
        setTimeout(function() {
            $('#pred-confidence-bar').css('width', res.prediction.confidence + '%');
        }, 100);

        // All scores
        renderAllScores(res.all_scores);

        // Store reasoning for modal
        window._predictionReasoning = res.reasoning;
        window._predictionId = res.prediction_id;
        $('#btn-show-reasoning').show();

        // Set dropdown
        $('#confirm-basis-select').val(res.prediction.basis_id);
        $('#hidden-prediction-id').val(res.prediction_id);
        $('#prediction-section').show();
    }

    function renderAllScores(scores) {
        let html = '';
        scores.forEach(function(s) {
            const color = s.confidence >= 80 ? 'bg-green-500'
                        : s.confidence >= 50 ? 'bg-yellow-500' : 'bg-stone-400';
            html += '<div class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-stone-50 transition-colors" onclick="showReasoningModal()">';
            html += '<span class="w-32 text-xs text-stone-600 truncate">' + s.basis_name + '</span>';
            html += '<div class="flex-1 bg-stone-100 rounded-full h-2">';
            html += '<div class="' + color + ' h-2 rounded-full" style="width:' + s.confidence + '%"></div>';
            html += '</div>';
            html += '<span class="w-12 text-xs text-stone-600 text-right">' + s.confidence + '%</span>';
            html += '</div>';
        });
        $('#all-scores-container').html(html);
    }

    // Show Reasoning Modal
    $('#btn-show-reasoning').on('click', function() {
        window.showReasoningModal();
    });

    // Save Classification
    $('#btn-save-classification').on('click', function() {
        saveClassification(false);
    });

    // Train AI
    $('#btn-train-ai').on('click', function() {
        if (confirm('This will save the OCR text as training data for the selected document type. Continue?')) {
            saveClassification(true);
        }
    });

    function saveClassification(addToTraining) {
        $.ajax({
            url: '{{ route("document-ai.save-classification") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                prediction_id: $('#hidden-prediction-id').val(),
                basis_id: $('#confirm-basis-select').val(),
                user_remark: $('#user-remark').val(),
                add_to_training: addToTraining ? 1 : 0
            },
            success: function(res) {
                if (res.success) {
                    const msg = addToTraining
                        ? 'Classification saved & training data added.'
                        : res.message;
                    $('#save-success').text(msg).show();
                    setTimeout(function() { $('#save-success').fadeOut(); }, 4000);
                }
            },
            error: function(xhr) {
                showError(xhr.responseJSON?.message || 'Save failed.');
            }
        });
    }

    function showError(msg) {
        $('#upload-error').text(msg).show();
    }

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }
});

// Global: Reasoning Modal (accessible from inline onclick)
function showReasoningModal() {
    const r = window._predictionReasoning;
    if (!r) return;

    let html = '';

    html += '<div>';
    html += '<h4 class="text-sm font-semibold text-stone-800 mb-3 flex items-center gap-2">';
    html += '<svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
    html += 'Document Type Classification</h4>';
    if (r.type_reasoning && r.type_reasoning.length) {
        html += '<div class="space-y-3">';
        r.type_reasoning.forEach(function(tr, idx) {
            var isTop = idx === 0;
            var border = isTop ? 'border-red-200 bg-red-50/50' : 'border-stone-200';
            html += '<div class="border ' + border + ' rounded-lg p-3">';
            html += '<div class="flex items-center justify-between mb-1">';
            html += '<span class="text-sm font-medium text-stone-800">' + (isTop ? '🏆 ' : '') + tr.type_name + '</span>';
            html += '<span class="text-sm font-bold ' + (isTop ? 'text-red-700' : 'text-stone-600') + '">' + tr.confidence + '%</span>';
            html += '</div>';
            html += '<p class="text-xs text-stone-500 mb-1">Method: ' + tr.method + '</p>';
            if (tr.matched_keywords && tr.matched_keywords.length) {
                html += '<div class="flex flex-wrap gap-1 mt-1">';
                tr.matched_keywords.forEach(function(kw) {
                    html += '<span class="px-1.5 py-0.5 bg-amber-100 text-amber-700 text-[10px] rounded">' + kw + '</span>';
                });
                html += '</div>';
            } else {
                html += '<p class="text-xs text-stone-400 italic">No direct keyword matches (similarity-based)</p>';
            }
            html += '</div>';
        });
        html += '</div>';
    }
    html += '</div>';

    html += '<div class="border-t border-stone-200 pt-4">';
    html += '<h4 class="text-sm font-semibold text-stone-800 mb-2">OCR Text Analyzed</h4>';
    html += '<p class="text-xs text-stone-500">Total characters: ' + (r.ocr_length || 0) + '</p>';
    html += '<div class="mt-2 p-2 bg-stone-50 rounded text-[10px] font-mono text-stone-600 max-h-24 overflow-y-auto">' + (r.ocr_snippet || '').substring(0, 300) + '...</div>';
    html += '</div>';

    $('#reasoning-content').html(html);
    $('#reasoning-modal').show();
}
</script>
@endpush
