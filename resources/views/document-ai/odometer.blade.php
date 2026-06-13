@extends('layouts.app')

@section('title', 'Odometer Reader')
@section('page-title', 'Odometer Reading Extractor')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-stone-600">Home</a>
    <span>/</span>
    <span class="text-stone-600">Odometer Reader</span>
@endsection

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-stone-800">Odometer Reading Extractor</h2>
            <p class="text-sm text-stone-500 mt-1">Upload a dashboard photo to extract the odometer reading</p>
        </div>
        <a href="{{ route('document-ai.playground') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs text-stone-600 bg-white border border-stone-200 rounded-lg hover:bg-stone-50">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Doc Predictor
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {{-- LEFT: Upload --}}
        <div class="lg:col-span-5 space-y-4">
            <div class="bg-white border border-stone-200 rounded-xl p-6">
                <div id="drop-zone" class="border-2 border-dashed border-stone-300 rounded-xl p-8 text-center cursor-pointer hover:border-red-400 hover:bg-red-50/30 transition-all">
                    <svg class="w-12 h-12 mx-auto text-stone-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <p class="text-sm font-medium text-stone-700">Upload Dashboard Photo</p>
                    <p class="text-xs text-stone-400 mt-1">JPG, JPEG, PNG — Max 10MB</p>
                    <input type="file" id="file-input" accept=".jpg,.jpeg,.png" class="hidden">
                </div>

                <div id="file-info" class="hidden mt-4 p-3 bg-stone-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <div class="flex-1 min-w-0">
                            <p id="file-name" class="text-sm font-medium text-stone-800 truncate"></p>
                            <p id="file-size" class="text-xs text-stone-400"></p>
                        </div>
                        <button id="btn-remove" class="text-stone-400 hover:text-red-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="text-xs text-stone-500 mb-1 block">Odometer Type</label>
                    <select id="odometer-type" class="w-full text-sm border border-stone-200 rounded-lg px-3 py-2">
                        <option value="auto">Auto Detect</option>
                        <option value="digital">Digital (LCD/LED)</option>
                        <option value="analog">Analog (Drum)</option>
                    </select>
                </div>

                <button id="btn-extract" disabled class="mt-4 w-full py-2.5 px-4 bg-red-700 text-white text-sm font-medium rounded-lg hover:bg-red-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    <svg class="w-4 h-4 inline mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    Extract Reading
                </button>

                <div id="progress-wrap" class="hidden mt-4">
                    <div class="flex justify-between mb-1"><span id="prog-label" class="text-xs text-stone-500">Processing...</span><span id="prog-pct" class="text-xs font-medium">0%</span></div>
                    <div class="w-full bg-stone-200 rounded-full h-2"><div id="prog-bar" class="bg-red-600 h-2 rounded-full transition-all" style="width:0%"></div></div>
                </div>

                <div id="error-msg" class="hidden mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700"></div>
            </div>

            {{-- Preview --}}
            <div id="preview-section" class="hidden bg-white border border-stone-200 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-stone-700 mb-3">Full Image</h3>
                <img id="preview-img" class="w-full rounded-lg border border-stone-200" alt="Preview">
            </div>
        </div>

        {{-- RIGHT: Results --}}
        <div class="lg:col-span-7 space-y-4">
            <div id="crop-section" class="hidden bg-white border border-stone-200 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-stone-700 mb-3">Detected Odometer Region</h3>
                <img id="crop-img" class="max-h-48 rounded-lg border border-stone-200 mx-auto" alt="Cropped">
            </div>

            <div id="result-section" class="hidden bg-white border border-stone-200 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-stone-700 mb-4">Extraction Result</h3>

                <div id="odo-type-badge" class="mb-3"></div>

                <div class="bg-gradient-to-r from-stone-900 to-stone-800 rounded-xl p-6 text-center mb-4">
                    <p id="reading-display" class="text-4xl font-mono font-bold text-green-400 tracking-wider">0</p>
                    <p id="reading-unit" class="text-sm text-green-300 mt-1">KM</p>
                    <div class="mt-3 flex items-center justify-center gap-2">
                        <div class="flex-1 max-w-48 bg-stone-700 rounded-full h-2"><div id="conf-bar" class="bg-green-500 h-2 rounded-full transition-all" style="width:0%"></div></div>
                        <span id="conf-pct" class="text-xs text-green-400 font-medium">0%</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="bg-stone-50 rounded-lg p-3">
                        <p class="text-[10px] text-stone-500 uppercase">Raw OCR</p>
                        <p id="raw-ocr" class="text-xs font-mono text-stone-700 mt-1"></p>
                    </div>
                    <div class="bg-stone-50 rounded-lg p-3">
                        <p class="text-[10px] text-stone-500 uppercase">Validation</p>
                        <p id="validation-badge" class="text-xs font-medium mt-1"></p>
                    </div>
                </div>

                <div class="border-t border-stone-200 pt-4">
                    <p class="text-sm font-medium text-stone-700 mb-3">Confirm Reading</p>
                    <input type="hidden" id="hidden-record-id">
                    <div class="grid grid-cols-3 gap-3 mb-3">
                        <div class="col-span-2">
                            <input type="number" id="confirm-reading" class="w-full text-sm border border-stone-200 rounded-lg px-3 py-2" placeholder="Reading value">
                        </div>
                        <select id="confirm-unit" class="text-sm border border-stone-200 rounded-lg px-3 py-2">
                            <option value="km">KM</option>
                            <option value="miles">Miles</option>
                        </select>
                    </div>
                    <input type="text" id="user-remark" placeholder="Remark (optional)" class="w-full text-sm border border-stone-200 rounded-lg px-3 py-2 mb-3">
                    <div class="flex gap-2">
                        <button id="btn-confirm" class="flex-1 py-2.5 px-4 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Confirm
                        </button>
                        <button id="btn-train" class="flex-1 py-2.5 px-4 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                            Confirm + Train
                        </button>
                    </div>
                    <div id="success-msg" class="hidden mt-3 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    let selectedFile = null;
    const dropZone = $('#drop-zone'), fileInput = $('#file-input');

    dropZone.on('dragover', function(e) { e.preventDefault(); $(this).addClass('border-red-400 bg-red-50/50'); })
        .on('dragleave drop', function(e) { e.preventDefault(); $(this).removeClass('border-red-400 bg-red-50/50'); })
        .on('drop', function(e) { if (e.originalEvent.dataTransfer.files.length) handleFile(e.originalEvent.dataTransfer.files[0]); })
        .on('click', function(e) { if (e.target !== fileInput[0]) fileInput.trigger('click'); });

    fileInput.on('click', function(e) { e.stopPropagation(); }).on('change', function() { if (this.files.length) handleFile(this.files[0]); });

    function handleFile(file) {
        if (file.size > 10485760) { showError('Max 10MB'); return; }
        selectedFile = file;
        $('#file-name').text(file.name);
        $('#file-size').text((file.size/1024).toFixed(0) + ' KB');
        $('#file-info').show();
        $('#btn-extract').prop('disabled', false);
    }

    $('#btn-remove').on('click', function() { selectedFile = null; fileInput.val(''); $('#file-info').hide(); $('#btn-extract').prop('disabled', true); });

    $('#btn-extract').on('click', function() {
        if (!selectedFile) return;
        var fd = new FormData();
        fd.append('file', selectedFile);
        fd.append('odometer_type', $('#odometer-type').val());
        fd.append('_token', '{{ csrf_token() }}');

        $('#progress-wrap').show(); $('#error-msg').hide(); $('#result-section').hide(); $('#crop-section').hide(); $('#preview-section').hide(); $('#success-msg').hide();
        $(this).prop('disabled', true);
        var interval = null;

        $.ajax({
            url: '{{ route("odometer.extract") }}', method: 'POST', data: fd, processData: false, contentType: false, timeout: 300000,
            xhr: function() {
                var xhr = new XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) { if (e.lengthComputable) { var p = Math.round((e.loaded/e.total)*50); $('#prog-bar').css('width',p+'%'); $('#prog-pct').text(p+'%'); }});
                xhr.upload.addEventListener('load', function() { $('#prog-label').text('Analyzing...'); var c=50; interval=setInterval(function(){if(c<95){c+=1;$('#prog-bar').css('width',c+'%');$('#prog-pct').text(Math.round(c)+'%');}},500); });
                return xhr;
            },
            success: function(res) {
                if (interval) clearInterval(interval);
                $('#prog-bar').css('width','100%'); $('#prog-pct').text('100%');
                setTimeout(function() { $('#progress-wrap').hide(); if (res.success) populateResult(res); else showError(res.message); }, 300);
            },
            error: function(xhr) { if(interval)clearInterval(interval); $('#progress-wrap').hide(); showError(xhr.responseJSON?.message||'Failed'); },
            complete: function() { if(interval)clearInterval(interval); $('#btn-extract').prop('disabled',false); }
        });
    });

    function populateResult(res) {
        $('#preview-img').attr('src', res.file_url); $('#preview-section').show();
        if (res.crop_url) { $('#crop-img').attr('src', res.crop_url); $('#crop-section').show(); }

        var badge = res.odometer_type === 'digital'
            ? '<span class="px-2.5 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">DIGITAL</span>'
            : '<span class="px-2.5 py-1 bg-amber-100 text-amber-700 text-xs font-medium rounded-full">ANALOG</span>';
        $('#odo-type-badge').html(badge);

        // Animate counter — show exact value without formatting
        var target = res.reading || 0;
        $({val:0}).animate({val:target},{duration:800,step:function(){$('#reading-display').text(this.val.toFixed(1));},complete:function(){$('#reading-display').text(target.toFixed(1));}});
        $('#reading-unit').text((res.unit||'km').toUpperCase());
        setTimeout(function(){$('#conf-bar').css('width',res.confidence+'%');},100);
        $('#conf-pct').text(res.confidence+'%');
        $('#raw-ocr').text(res.raw_ocr_text||'-');
        var vc = res.validation?.is_valid ? 'text-green-700' : 'text-red-600';
        $('#validation-badge').attr('class','text-xs font-medium mt-1 '+vc).text(res.validation?.message||'');
        $('#confirm-reading').val(res.reading); $('#confirm-unit').val(res.unit||'km');
        $('#hidden-record-id').val(res.record_id);
        $('#result-section').show();
    }

    function submitConfirm(train) {
        $.post('{{ route("odometer.confirm") }}', {
            _token:'{{ csrf_token() }}', record_id:$('#hidden-record-id').val(),
            confirmed_reading:$('#confirm-reading').val(), confirmed_unit:$('#confirm-unit').val(),
            user_remark:$('#user-remark').val(), add_to_training:train?1:0
        }, function(res) { if(res.success) $('#success-msg').text(res.message).show(); });
    }

    $('#btn-confirm').on('click', function(){submitConfirm(false);});
    $('#btn-train').on('click', function(){submitConfirm(true);});

    function showError(m) { $('#error-msg').text(m).show(); }
});
</script>
@endpush
