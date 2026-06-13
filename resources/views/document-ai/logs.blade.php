@extends('layouts.app')

@section('title', 'Prediction Logs')
@section('page-title', 'Document AI — Prediction Logs')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-stone-600">Home</a>
    <span>/</span>
    <a href="{{ route('document-ai.playground') }}" class="hover:text-stone-600">AI Doc Predictor</a>
    <span>/</span>
    <span class="text-stone-600">Logs</span>
@endsection

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Header with Tabs --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-stone-800">Prediction Logs & Analytics</h2>
            <p class="text-sm text-stone-500 mt-1">Track all document predictions and AI performance</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('document-ai.playground') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs text-stone-600 bg-white border border-stone-200 rounded-lg hover:bg-stone-50">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Playground
            </a>
            <a href="{{ route('document-ai.logs') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-red-700 rounded-lg">
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

    {{-- Filters --}}
    <div class="bg-white border border-stone-200 rounded-xl p-4 mb-4">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <div>
                <label class="text-[10px] text-stone-500 uppercase font-medium">Status</label>
                <select id="filter-status" class="w-full text-xs border border-stone-200 rounded-lg px-2 py-1.5 mt-0.5">
                    <option value="">All</option>
                    <option value="pending">Pending</option>
                    <option value="predicted">Predicted</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="corrected">Corrected</option>
                </select>
            </div>
            <div>
                <label class="text-[10px] text-stone-500 uppercase font-medium">Document Type</label>
                <select id="filter-type" class="w-full text-xs border border-stone-200 rounded-lg px-2 py-1.5 mt-0.5">
                    <option value="">All Types</option>
                    @foreach($types as $type)
                        <option value="{{ $type->id }}">{{ $type->label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-[10px] text-stone-500 uppercase font-medium">Department</label>
                <select id="filter-dept" class="w-full text-xs border border-stone-200 rounded-lg px-2 py-1.5 mt-0.5">
                    <option value="">All Depts</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->department_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-[10px] text-stone-500 uppercase font-medium">From Date</label>
                <input type="date" id="filter-date-from" class="w-full text-xs border border-stone-200 rounded-lg px-2 py-1.5 mt-0.5">
            </div>
            <div>
                <label class="text-[10px] text-stone-500 uppercase font-medium">To Date</label>
                <input type="date" id="filter-date-to" class="w-full text-xs border border-stone-200 rounded-lg px-2 py-1.5 mt-0.5">
            </div>
            <div class="flex items-end gap-2">
                <button id="btn-filter" class="flex-1 px-3 py-1.5 text-xs font-medium text-white bg-red-700 rounded-lg hover:bg-red-800">Filter</button>
                <button id="btn-reset" class="px-3 py-1.5 text-xs text-stone-600 bg-stone-100 rounded-lg hover:bg-stone-200">Reset</button>
            </div>
        </div>
    </div>

    {{-- DataTable --}}
    <div class="bg-white border border-stone-200 rounded-xl overflow-hidden">
        <table id="logs-table" class="w-full text-sm" style="width:100%">
            <thead class="bg-stone-50 border-b border-stone-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-stone-500">#</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-stone-500">File</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-stone-500">Predicted Type</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-stone-500">Confidence</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-stone-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-stone-500">Date</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

{{-- Detail Modal --}}
<div id="log-detail-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display:none">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[85vh] overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-stone-200 bg-stone-50">
            <h3 class="text-lg font-semibold text-stone-800">Prediction Detail</h3>
            <button onclick="$('#log-detail-modal').hide()" class="text-stone-400 hover:text-stone-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto p-6" id="log-detail-content"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    var table = $('#logs-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("document-ai.logs.data") }}',
            data: function(d) {
                d.status = $('#filter-status').val();
                d.type_id = $('#filter-type').val();
                d.department_id = $('#filter-dept').val();
                d.date_from = $('#filter-date-from').val();
                d.date_to = $('#filter-date-to').val();
            }
        },
        columns: [
            { data: 'id', width: '50px' },
            {
                data: 'filename',
                render: function(data, type, row) {
                    var icon = row.file_ext === 'pdf'
                        ? '<svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'
                        : '<svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>';
                    return '<div class="flex items-center gap-2 cursor-pointer" onclick="showLogDetail(' + row.id + ', this)" data-row=\'' + JSON.stringify(row).replace(/'/g, "&#39;") + '\'>' +
                        icon +
                        '<div><p class="text-xs font-medium text-stone-800 truncate max-w-[180px]">' + data + '</p>' +
                        '<p class="text-[10px] text-stone-400">' + (row.department !== '-' ? row.department : '') + (row.location !== '-' ? ' • ' + row.location : '') + '</p></div></div>';
                }
            },
            {
                data: 'predicted_type',
                render: function(data) {
                    return '<span class="px-2 py-0.5 bg-red-50 text-red-700 text-xs font-medium rounded-full">' + data + '</span>';
                }
            },
            {
                data: 'confidence',
                render: function(data) {
                    if (!data) return '-';
                    var color = data >= 80 ? 'text-green-700' : data >= 60 ? 'text-amber-600' : 'text-red-600';
                    return '<span class="font-bold text-xs ' + color + '">' + data + '%</span>';
                }
            },
            {
                data: 'status',
                render: function(data) {
                    var colors = {
                        pending: 'bg-stone-100 text-stone-600',
                        predicted: 'bg-blue-50 text-blue-700',
                        confirmed: 'bg-green-50 text-green-700',
                        corrected: 'bg-amber-50 text-amber-700',
                    };
                    return '<span class="px-2 py-0.5 text-[10px] font-medium rounded-full ' + (colors[data] || 'bg-stone-100 text-stone-600') + '">' + data + '</span>';
                }
            },
            { data: 'created_at', className: 'text-xs text-stone-500' }
        ],
        order: [[0, 'desc']],
        pageLength: 15,
        lengthMenu: [10, 15, 25, 50, 100],
        language: {
            emptyTable: 'No predictions yet. Upload a document to get started.',
            processing: '<div class="text-center py-4"><span class="text-sm text-stone-500">Loading...</span></div>'
        },
        dom: '<"flex items-center justify-between px-4 py-3 border-b border-stone-100"lf>rt<"flex items-center justify-between px-4 py-3 border-t border-stone-100"ip>'
    });

    $('#btn-filter').on('click', function() { table.ajax.reload(); });
    $('#btn-reset').on('click', function() {
        $('#filter-status, #filter-type, #filter-dept').val('');
        $('#filter-date-from, #filter-date-to').val('');
        table.ajax.reload();
    });
});

function showLogDetail(id, el) {
    var row = JSON.parse($(el).closest('[data-row]').attr('data-row'));
    var html = '';

    html += '<div class="grid grid-cols-2 gap-4 mb-4">';
    html += '<div class="bg-stone-50 rounded-lg p-3"><p class="text-[10px] text-stone-500 uppercase">File</p><p class="text-sm font-medium text-stone-800 truncate">' + row.filename + '</p></div>';
    html += '<div class="bg-stone-50 rounded-lg p-3"><p class="text-[10px] text-stone-500 uppercase">Status</p><p class="text-sm font-medium text-stone-800">' + row.status + '</p></div>';
    html += '<div class="bg-red-50 rounded-lg p-3"><p class="text-[10px] text-red-500 uppercase">Predicted Type</p><p class="text-sm font-bold text-red-800">' + row.predicted_type + ' <span class="text-red-600 text-xs">(' + (row.confidence || 0) + '%)</span></p></div>';
    html += '<div class="bg-blue-50 rounded-lg p-3"><p class="text-[10px] text-blue-500 uppercase">Department</p><p class="text-sm font-medium text-blue-800">' + row.department + ' <span class="text-blue-500 text-xs">(' + (row.dept_code || '-') + ')</span></p></div>';
    html += '</div>';

    if (row.confirmed_type) {
        html += '<div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4"><p class="text-[10px] text-green-500 uppercase">Confirmed As</p><p class="text-sm font-bold text-green-800">' + row.confirmed_type + '</p></div>';
    }
    if (row.user_remark) {
        html += '<div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4"><p class="text-[10px] text-amber-500 uppercase">User Remark</p><p class="text-sm text-amber-800">' + row.user_remark + '</p></div>';
    }

    html += '<div class="border-t border-stone-200 pt-3"><p class="text-[10px] text-stone-500 uppercase mb-1">OCR Text Preview</p>';
    html += '<div class="bg-stone-50 rounded-lg p-3 text-[10px] font-mono text-stone-600 max-h-32 overflow-y-auto">' + (row.ocr_text_short || 'No text extracted') + '...</div></div>';

    html += '<div class="flex items-center justify-between mt-4 pt-3 border-t border-stone-200">';
    html += '<span class="text-xs text-stone-400">By: ' + row.created_by + ' • ' + row.created_at + '</span>';
    if (row.file_url) {
        html += '<a href="' + row.file_url + '" target="_blank" class="text-xs text-red-600 hover:text-red-800 font-medium">View File →</a>';
    }
    html += '</div>';

    $('#log-detail-content').html(html);
    $('#log-detail-modal').show();
}
</script>
@endpush
