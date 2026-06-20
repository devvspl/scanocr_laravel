@extends('layouts.app')
@section('title','Scan Summary')
@section('page-title','Scan Summary')
@push('head')
<style>
/* ── Table ─────────────────────────────────────────────────── */
#summaryTable{border-collapse:collapse;width:100% !important;table-layout:auto}
#summaryTable thead th{background:#fafaf9;color:#78716c;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;padding:.5rem .75rem;border-bottom:2px solid #e7e5e4;white-space:nowrap;text-align:center}
#summaryTable thead th:nth-child(1),#summaryTable thead th:nth-child(2){text-align:left}
#summaryTable thead th.sorting:after,#summaryTable thead th.sorting_asc:after,#summaryTable thead th.sorting_desc:after{font-size:.5rem;opacity:.5;margin-left:.2rem}
#summaryTable tbody td{padding:.5rem .75rem;border-bottom:1px solid #f0eeec;color:#292524;vertical-align:middle;font-size:.72rem;text-align:center}
#summaryTable tbody td:nth-child(1){text-align:center;color:#a8a29e;width:40px}
#summaryTable tbody td:nth-child(2){text-align:left;font-weight:500}
#summaryTable tbody tr:hover td{background:#fafaf9}
#summaryTable tbody tr:last-child td{border-bottom:none}
/* group header rows */
#summaryTable thead tr.group-header th{background:#f0eeec;font-size:.6rem;color:#57534e;padding:.3rem .75rem;border-bottom:1px solid #e7e5e4}
/* Clickable number cells */
.num-cell{cursor:pointer;font-weight:600;border-radius:.3rem;padding:.2rem .5rem;transition:all .15s;display:inline-block;min-width:2rem}
.num-cell:hover{color: #7f1d1d;text-decoration: underline;}
.num-cell.zero{color:#d6d3d1;cursor:default;font-weight:400}
.num-cell.zero:hover{background:transparent;color:#d6d3d1}
/* Grand total row */
tr.grand-total td{background:#fef9c3 !important;font-weight:700;font-size:.73rem;border-top:2px solid #e7e5e4}
/* ── Table Footer ─────────────────────────────────────────────── */
#summaryTable tfoot tr {
    background:#fafaf9 !important;
    border-top:2px solid #e7e5e4;
}
#summaryTable tfoot td {
    padding: 0.75rem 1rem;
    vertical-align: middle;
    font-weight: 600;
    border-bottom: none;
}
/* ── Badges ──────────────────────────────────────────────────── */
.badge{display:inline-flex;align-items:center;padding:.15rem .5rem;border-radius:9999px;font-size:.6rem;font-weight:600;white-space:nowrap}
.badge-approved{background:#dcfce7;color:#15803d}.badge-pending{background:#fef9c3;color:#a16207}.badge-rejected{background:#fee2e2;color:#b91c1c}
/* ── DT controls ─────────────────────────────────────────────── */
.dt-ctrl-bar{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.75rem 1.25rem;flex-wrap:wrap}
.dt-length-sel{height:1.9rem;padding:0 1.4rem 0 .5rem;font-size:.72rem;border:1px solid #d6d3d1;border-radius:.375rem;background:#fafaf9;color:#292524;appearance:none;cursor:pointer;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%23a8a29e' stroke-width='2.5'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right .35rem center}
.dt-search-input{height:1.9rem;padding:0 .65rem 0 1.85rem;font-size:.72rem;border:1px solid #d6d3d1;border-radius:.375rem;background:#fafaf9;outline:none;width:160px;color:#292524}
.dt-search-input:focus{border-color:#7f1d1d;box-shadow:0 0 0 3px rgba(127,29,29,.08)}
.dt-search-wrap{position:relative}.dt-search-wrap svg{position:absolute;left:.5rem;top:50%;transform:translateY(-50%);color:#a8a29e;pointer-events:none;width:13px;height:13px}
.dataTables_info{font-size:.68rem;color:#a8a29e}
.dataTables_paginate{display:flex;gap:.2rem}
.dataTables_paginate .paginate_button{height:1.65rem;min-width:1.65rem;padding:0 .35rem;display:inline-flex;align-items:center;justify-content:center;border-radius:.35rem;font-size:.68rem;cursor:pointer;border:1px solid #e7e5e4;background:#fff;color:#292524;user-select:none}
.dataTables_paginate .paginate_button:hover:not(.disabled){background:#f5f5f4}.dataTables_paginate .paginate_button.current{background:#7f1d1d;color:#fff;border-color:#7f1d1d}.dataTables_paginate .paginate_button.disabled{opacity:.35;cursor:default}
#summaryTable_wrapper>.dataTables_length,#summaryTable_wrapper>.dataTables_filter,#summaryTable_wrapper>.dataTables_info,#summaryTable_wrapper>.dataTables_paginate{display:none !important}
/* ── Filter bar ──────────────────────────────────────────────── */
.filter-bar{display:flex;align-items:center;gap:.75rem;padding:.875rem 1.25rem;background:#fafaf9;border-bottom:1px solid #e7e5e4;flex-wrap:wrap}
.filter-input{height:2rem;padding:0 .65rem;font-size:.72rem;border:1px solid #d6d3d1;border-radius:.375rem;background:#fff;outline:none;color:#292524}
.filter-input:focus{border-color:#7f1d1d;box-shadow:0 0 0 3px rgba(127,29,29,.08)}
.filter-btn{height:2rem;display:inline-flex;align-items:center;gap:.35rem;font-size:.72rem;font-weight:600;border-radius:.375rem;cursor:pointer;transition:all .15s}
.filter-btn-primary{background:#7f1d1d;color:#fff;border:none;padding:0 .875rem}.filter-btn-primary:hover{background:#6b1a1a}
.filter-btn-secondary{background:#fff;color:#57534e;border:1px solid #d6d3d1;padding:0 .75rem}.filter-btn-secondary:hover{background:#f5f5f4}
/* ── Export menu ─────────────────────────────────────────────── */
.export-menu{position:relative;display:inline-block}
.export-drop{display:none;position:absolute;right:0;top:calc(100% + .3rem);z-index:60;background:#fff;border:1px solid #e7e5e4;border-radius:.5rem;box-shadow:0 6px 20px rgba(0,0,0,.1);min-width:150px;overflow:hidden}
.export-menu.open .export-drop{display:block}
.export-drop a,.export-drop button{display:flex;align-items:center;gap:.5rem;padding:.5rem .875rem;font-size:.73rem;color:#292524;text-decoration:none;background:none;border:none;width:100%;cursor:pointer;text-align:left}
.export-drop a:hover,.export-drop button:hover{background:#f5f5f4}
/* ── Modal ───────────────────────────────────────────────────── */
.modal-backdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:300;backdrop-filter:blur(2px)}.modal-backdrop.open{display:block}
.modal-container{display:none;position:fixed;inset:0;z-index:301;padding:1.5rem;overflow-y:auto}.modal-container.open{display:flex;align-items:center;justify-content:center}
.modal-dialog{background:#fff;border-radius:1rem;box-shadow:0 20px 50px rgba(0,0,0,.25);width:100%;max-width:1100px;max-height:90vh;display:flex;flex-direction:column}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;border-bottom:1px solid #e7e5e4;flex-shrink:0}
.modal-body{flex:1;overflow:auto;padding:0}
/* Modal detail table */
#detailTable{border-collapse:collapse;width:100% !important;table-layout:auto}
#detailTable thead th{background:#fafaf9;color:#78716c;font-size:.62rem;font-weight:700;text-transform:uppercase;padding:.5rem .75rem;border-bottom:2px solid #e7e5e4;white-space:nowrap}
#detailTable tbody td{padding:.5rem .75rem;border-bottom:1px solid #f0eeec;font-size:.72rem;color:#292524;vertical-align:middle}
#detailTable tbody tr:hover td{background:#fafaf9}
#detailTable_wrapper>.dataTables_length,#detailTable_wrapper>.dataTables_filter,#detailTable_wrapper>.dataTables_info,#detailTable_wrapper>.dataTables_paginate{display:none !important}
/* ── File Viewer Modal ─────────────────────────────────────── */
.modal-tabs-bar{display:flex;align-items:center;gap:0;padding:0 1rem;background:#fafaf9;border-bottom:1px solid #e7e5e4;flex-shrink:0;flex-wrap:wrap;position:relative}
.modal-tab{padding:.55rem .85rem;font-size:.68rem;font-weight:600;color:#78716c;background:none;border:none;border-bottom:2px solid transparent;cursor:pointer;transition:all .15s;white-space:nowrap}
.modal-tab:hover{color:#292524}
.modal-tab.active{color:#7f1d1d;border-bottom-color:#7f1d1d}
.modal-tab-files{display:none;position:absolute;top:100%;left:0;right:0;z-index:10;background:#fff;border-bottom:1px solid #e7e5e4;box-shadow:0 4px 12px rgba(0,0,0,.08);padding:.4rem .75rem;max-height:150px;overflow-y:auto}
.modal-tab-files.open{display:flex;flex-wrap:wrap;align-items:center;gap:.3rem}
.modal-tab-files .file-link{display:inline-flex;align-items:center;gap:.35rem;padding:.25rem .55rem;border-radius:.35rem;cursor:pointer;font-size:.67rem;color:#292524;transition:background .12s;border:1px solid #e7e5e4;background:#fafaf9;white-space:nowrap}
.modal-tab-files .file-link:hover{background:#f5f5f4;border-color:#d6d3d1}
.modal-tab-files .file-link.active{background:#fef2f2;color:#7f1d1d;font-weight:600;border-color:#7f1d1d}
.modal-tab-files .file-ext{width:1.1rem;height:1.1rem;display:flex;align-items:center;justify-content:center;background:#f5f5f4;border-radius:.2rem;font-size:6px;font-weight:700;color:#78716c;text-transform:uppercase;flex-shrink:0;border:1px solid #e7e5e4}
.modal-viewer-section{flex:1;min-height:0;display:flex;flex-direction:column;background:#1c1917}
.modal-viewer-toolbar{display:flex;align-items:center;justify-content:space-between;padding:.5rem 1rem;background:rgba(0,0,0,.4);flex-shrink:0}
.modal-viewer-body{flex:1;position:relative;min-height:400px}
.modal-viewer-body iframe,.modal-viewer-body img{position:absolute;inset:0;width:100%;height:100%;border:none;object-fit:contain;background:#1c1917}
</style>
@endpush

@section('content')
<div class="bg-white border border-stone-200 rounded-xl flex flex-col">

    {{-- Header --}}
    <div class="px-5 py-3.5 border-b border-stone-100 flex items-center justify-between gap-3 flex-shrink-0 flex-wrap">
        <div>
            <h2 class="text-sm font-semibold text-stone-800">Scan Summary</h2>
            <p class="text-xs text-stone-400 mt-0.5">Company-wise scanning status overview</p>
        </div>
        <div class="flex items-center gap-2">
            <div class="export-menu" id="exportMenu">
                <button id="btnExportToggle" class="h-8 px-3 flex items-center gap-1.5 text-xs font-medium bg-stone-800 hover:bg-stone-900 text-white rounded-lg transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>Export
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="export-drop">
                    <a href="#" id="btnExportExcel">
                        <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9v10a2 2 0 01-2 2z"/></svg>Export Excel
                    </a>
                    <a href="#" id="btnExportPdf">
                        <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>Export PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="filter-bar">
        <div class="flex items-center gap-2">
            <label class="text-[11px] font-semibold text-stone-600 uppercase">Scan Date From</label>
            <input type="date" id="filterFromDate" class="filter-input" style="width:140px" onfocus="this.showPicker()">
        </div>
        <div class="flex items-center gap-2">
            <label class="text-[11px] font-semibold text-stone-600 uppercase">To</label>
            <input type="date" id="filterToDate" class="filter-input" style="width:140px" onfocus="this.showPicker()">
        </div>
        <button id="btnApplyFilters" class="filter-btn filter-btn-primary">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>Apply
        </button>
        <button id="btnResetFilters" class="filter-btn filter-btn-secondary">Reset</button>
    </div>

    {{-- DT controls --}}
    <div class="dt-ctrl-bar border-b border-stone-100 flex-shrink-0">
        <div class="flex items-center gap-2 text-xs text-stone-500">
            <span>Show</span>
            <select class="dt-length-sel" id="dtLength">
                <option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option>
            </select>
            <span>entries</span>
        </div>
        <div class="dt-search-wrap">
            <input type="text" class="dt-search-input" id="dtSearch" placeholder="Search company…">
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto flex-1">
        <table id="summaryTable" style="width:100%">
            <thead>
                <tr class="group-header">
                    <th rowspan="2" style="vertical-align:middle">#</th>
                    <th rowspan="2" style="vertical-align:middle;text-align:left">Company</th>
                    <th colspan="4" style="text-align:center !important ;border-left:2px solid #e7e5e4">Scanning Process</th>
                    <th rowspan="2" style="vertical-align:middle;border-left:2px solid #e7e5e4">Pending<br>for Naming</th>
                    <th rowspan="2" style="vertical-align:middle;border-left:2px solid #e7e5e4">Pending for<br>Verification</th>
                </tr>
                <tr>
                    <th style="border-left:2px solid #e7e5e4">Total</th>
                    <th>Pending</th>
                    <th>Approved</th>
                    <th>Rejected</th>
                </tr>
            </thead>
            <tbody></tbody>
            <tfoot>
                <tr class="bg-stone-50 border-t-2 border-stone-200 font-semibold">
                    <td class="py-3 px-4 text-center text-stone-600 text-xs uppercase tracking-wide" style="text-align: center;">—</td>
                    <td class="py-3 px-4 text-left text-stone-600 text-xs uppercase tracking-wide font-bold">Grand Total</td>
                    <td class="py-3 px-4 text-center" style="text-align: center;">
                        <span class="text-sm text-stone-800" id="gt-total">—</span>
                    </td>
                    <td class="py-3 px-4 text-center" style="text-align: center;">
                        <span class="text-sm text-stone-800" id="gt-pending">—</span>
                    </td>
                    <td class="py-3 px-4 text-center" style="text-align: center;">
                        <span class="text-sm text-stone-800" id="gt-approved">—</span>
                    </td>
                    <td class="py-3 px-4 text-center" style="text-align: center;">
                        <span class="text-sm text-stone-800" id="gt-rejected">—</span>
                    </td>
                    <td class="py-3 px-4 text-center" style="text-align: center;">
                        <span class="text-sm text-stone-800" id="gt-naming">—</span>
                    </td>
                    <td class="py-3 px-4 text-center" style="text-align: center;">
                        <span class="text-sm text-stone-800" id="gt-verify">—</span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Pagination bar --}}
    <div class="dt-ctrl-bar border-t border-stone-100 flex-shrink-0">
        <div id="dtInfo" class="dataTables_info"></div>
        <div id="dtPaginate"></div>
    </div>
</div>

{{-- ── Detail Modal ──────────────────────────────────────── --}}
<div class="modal-backdrop" id="detailModalBackdrop"></div>
<div class="modal-container" id="detailModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <div>
                <h3 class="text-sm font-semibold text-stone-800" id="detailModalTitle">Scan Detail</h3>
                <p class="text-xs text-stone-400 mt-0.5" id="detailModalSub"></p>
            </div>
            <button id="btnCloseDetailModal" class="w-7 h-7 flex items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="dt-ctrl-bar border-b border-stone-100">
                <div class="flex items-center gap-2 text-xs text-stone-500">
                    <span>Show</span>
                    <select class="dt-length-sel" id="modalDtLength"><option value="10">10</option><option value="25">25</option><option value="50">50</option></select>
                    <span>entries</span>
                </div>
                <div class="dt-search-wrap">
                    <input type="text" class="dt-search-input" id="modalDtSearch" placeholder="Search…">
                </div>
            </div>
            <div class="overflow-x-auto">
                <table id="detailTable" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Company</th>
                            <th>Location</th>
                            <th>File</th>
                            <th>Scan Date</th>
                            <th>Temp Scan Date</th>
                            <th>Status</th>
                            <th>Scanned By</th>
                            <th>Approver</th>
                            <th>Remark</th>
                        </tr>
                    </thead>
                </table>
            </div>
            <div class="dt-ctrl-bar border-t border-stone-100">
                <div id="modalDtInfo" class="dataTables_info"></div>
                <div id="modalDtPaginate"></div>
            </div>
        </div>
    </div>
</div>

{{-- ── File Viewer Modal ─────────────────────────────────── --}}
<div class="modal-backdrop" id="viewScanModalBackdrop"></div>
<div class="modal-container" id="viewScanModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <div>
                <h3 class="text-sm font-semibold text-stone-800" id="viewModalTitle">—</h3>
                <p class="text-xs text-stone-400 mt-0.5" id="viewModalMeta">—</p>
            </div>
            <button id="btnCloseViewModal" class="w-7 h-7 flex items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-body" style="display:flex;flex-direction:column">
            <div class="modal-tabs-bar" id="viewModalTabsBar"></div>
            <div class="modal-viewer-section">
                <div class="modal-viewer-toolbar"><span id="viewModalFileName" class="text-[10px] font-semibold text-stone-300">Main Scan</span></div>
                <div class="modal-viewer-body" id="viewModalViewerBody"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function(){
const CSRF = $('meta[name="csrf-token"]').attr('content');
const R = {
    data:   '{{ route("workflow.super-scanner.data") }}',
    totals: '{{ route("workflow.super-scanner.totals") }}',
    detail: '{{ route("workflow.super-scanner.detail") }}',
    excel:  '{{ route("workflow.super-scanner.export.excel") }}',
    pdf:    '{{ route("workflow.super-scanner.export.pdf") }}',
};

let currentFilters = { from_date: '', to_date: '' };

// ── Metric labels ─────────────────────────────────────────────────────────
const METRIC_LABELS = {
    total_scan:           'All Scans',
    pending:              'Pending',
    approved:             'Approved',
    rejected:             'Rejected',
    pending_naming:       'Pending for Naming',
    pending_verification: 'Pending for Verification',
};

// ── Main summary DataTable ────────────────────────────────────────────────
function numCell(val, companyId, companyName, metric) {
    if (!val || val == 0) return `<span class="num-cell zero">0</span>`;
    return `<span class="num-cell" data-id="${companyId}" data-name="${esc(companyName)}" data-metric="${metric}">${val}</span>`;
}

function esc(s){ return String(s||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])) }

const dt = $('#summaryTable').DataTable({
    serverSide: true,
    processing: true,
    ajax: {
        url: R.data,
        type: 'GET',
        data: d => Object.assign(d, currentFilters),
    },
    order: [[1, 'asc']],
    pageLength: 25,
    dom: 'rtp',
    columns: [
        { data: 'DT_RowIndex',   orderable: false, searchable: false, className: 'text-center', width: '40px' },
        { data: 'company_name',  defaultContent: '—',
          render: (d,t,r) => t==='display' ? `<a href="/workflow/super-scanner/company/${r.company_id}" class="text-red-900 hover:text-red-700 font-medium hover:underline">${esc(d)}</a>` : d },
        { data: 'total_scan',    orderable: true,  className: 'text-center',
          render: (d,t,r) => t==='display' ? numCell(d, r.company_id, r.company_name, 'total_scan') : d },
        { data: 'pending',       orderable: true,  className: 'text-center',
          render: (d,t,r) => t==='display' ? numCell(d, r.company_id, r.company_name, 'pending') : d },
        { data: 'approved',      orderable: true,  className: 'text-center',
          render: (d,t,r) => t==='display' ? numCell(d, r.company_id, r.company_name, 'approved') : d },
        { data: 'rejected',      orderable: true,  className: 'text-center',
          render: (d,t,r) => t==='display' ? numCell(d, r.company_id, r.company_name, 'rejected') : d },
        { data: 'pending_naming', orderable: true, className: 'text-center',
          render: (d,t,r) => t==='display' ? numCell(d, r.company_id, r.company_name, 'pending_naming') : d },
        { data: 'pending_verification', orderable: true, className: 'text-center',
          render: (d,t,r) => t==='display' ? numCell(d, r.company_id, r.company_name, 'pending_verification') : d },
    ],
    language: {
        emptyTable: 'No companies found',
        processing: '<span style="font-size:.72rem;color:#7f1d1d">Loading…</span>',
    },
    drawCallback: function () {
        const $p = $('#summaryTable_wrapper .dataTables_paginate').first();
        const $i = $('#summaryTable_wrapper .dataTables_info').first();
        if ($p.length) $p.appendTo('#dtPaginate');
        if ($i.length) $i.appendTo('#dtInfo');
        loadTotals();
    },
});

$('#dtLength').on('change', function(){ dt.page.len(+$(this).val()).draw() });
let st; $('#dtSearch').on('input', function(){ clearTimeout(st); const v=$(this).val(); st=setTimeout(()=>dt.search(v).draw(),350) });

// ── Filters ───────────────────────────────────────────────────────────────
$('#btnApplyFilters').on('click', function(){
    currentFilters.from_date = $('#filterFromDate').val();
    currentFilters.to_date   = $('#filterToDate').val();
    dt.ajax.reload();
});
$('#btnResetFilters').on('click', function(){
    $('#filterFromDate,#filterToDate').val('');
    currentFilters = { from_date:'', to_date:'' };
    dt.ajax.reload();
});

// ── Grand totals ──────────────────────────────────────────────────────────
async function loadTotals() {
    try {
        const t = await $.getJSON(R.totals, currentFilters);
        $('#gt-total').text(t.total_scan   || 0);
        $('#gt-pending').text(t.pending    || 0);
        $('#gt-approved').text(t.approved  || 0);
        $('#gt-rejected').text(t.rejected  || 0);
        $('#gt-naming').text(t.pending_naming        || 0);
        $('#gt-verify').text(t.pending_verification  || 0);
    } catch(e) { console.warn('Totals load failed', e) }
}

// ── Clickable number cells → detail modal ─────────────────────────────────
let detailDt = null;
$(document).on('click', '.num-cell:not(.zero)', function(){
    const $el       = $(this);
    const companyId = $el.data('id');
    const company   = $el.data('name');
    const metric    = $el.data('metric');
    openDetailModal(companyId, company, metric);
});

function openDetailModal(companyId, companyName, metric) {
    const label = METRIC_LABELS[metric] || metric;
    $('#detailModalTitle').text(`${companyName} — ${label}`);
    $('#detailModalSub').text('Scan records corresponding to the selected metric');
    $('#detailModalBackdrop,#detailModal').addClass('open');

    // Destroy previous instance if exists
    if (detailDt) { detailDt.destroy(); detailDt = null; $('#detailTable tbody').empty(); }

    detailDt = $('#detailTable').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: R.detail,
            type: 'GET',
            data: d => Object.assign(d, currentFilters, { company_id: companyId, metric }),
        },
        pageLength: 10,
        dom: 'rtp',
        order: [[4, 'desc']],
        columns: [
            { data: 'DT_RowIndex',    orderable: false, searchable: false, width: '36px', className:'text-center' },
            { data: 'company_name',   defaultContent:'—' },
            { data: 'location_name',  defaultContent:'—' },
            { data: 'file_preview',   orderable: false, searchable: false,
          render: (d,t,r) => t==='display' ? `<a href="javascript:void(0)" class="text-blue-600 hover:underline text-xs btn-view-scan" data-id="${r.Scan_Id}" data-file="${esc(r.File)}" data-url="${esc(r.File_Location)}">${esc(r.File)}</a>` : r.File },
            { data: 'Scan_Date',      defaultContent:'—' },
            { data: 'Temp_Scan_Date', defaultContent:'—' },
            { data: 'status_badge',   orderable: false, searchable: false, className:'text-center' },
            { data: 'scanned_by',     defaultContent:'—' },
            { data: 'approver_name',  defaultContent:'—' },
            { data: 'Bill_Approver_Remark', defaultContent:'—',
              render: d => d ? `<span title="${esc(d)}" class="block truncate max-w-[120px]">${esc(d)}</span>` : '—' },
        ],
        language: { emptyTable:'No records', processing:'<span style="font-size:.72rem;color:#7f1d1d">Loading…</span>' },
        drawCallback: function(){
            const $p = $('#detailTable_wrapper .dataTables_paginate').first();
            const $i = $('#detailTable_wrapper .dataTables_info').first();
            if($p.length) $p.appendTo('#modalDtPaginate');
            if($i.length) $i.appendTo('#modalDtInfo');
        },
    });

    $('#modalDtLength').on('change', function(){ detailDt.page.len(+$(this).val()).draw() });
    let mst; $('#modalDtSearch').on('input', function(){ clearTimeout(mst); const v=$(this).val(); mst=setTimeout(()=>detailDt.search(v).draw(),350) });
}

$('#btnCloseDetailModal,#detailModalBackdrop').on('click', function(){
    $('#detailModalBackdrop,#detailModal').removeClass('open');
    if(detailDt){ detailDt.destroy(); detailDt=null; $('#detailTable tbody').empty(); }
    $('#modalDtPaginate,#modalDtInfo').empty();
});

// ── Export ────────────────────────────────────────────────────────────────
$('#btnExportToggle').on('click', function(e){
    e.stopPropagation();
    $('#exportMenu').toggleClass('open');
});
$(document).on('click', function(){ $('#exportMenu').removeClass('open') });

function buildExportUrl(base) {
    const p = new URLSearchParams(currentFilters);
    return base + (p.toString() ? '?' + p.toString() : '');
}

$('#btnExportExcel').on('click', function(e){
    e.preventDefault();
    window.location.href = buildExportUrl(R.excel);
    $('#exportMenu').removeClass('open');
});
$('#btnExportPdf').on('click', function(e){
    e.preventDefault();
    window.location.href = buildExportUrl(R.pdf);
    $('#exportMenu').removeClass('open');
});

// ── File Viewer Modal (tabbed view with supporting files) ─────────────────
const SUPPORT_URL = (scanId) => `/workflow/temp-scan/${scanId}/support-list`;

$(document).on('click', '.btn-view-scan', async function(e) {
    e.preventDefault();
    const scanId = $(this).data('id');
    const mainUrl = $(this).data('url');
    const mainName = $(this).data('file');

    window.__ssModalMainUrl = mainUrl;
    window.__ssModalMainName = mainName;

    $('#viewModalTitle').text(`Scan #${scanId}`);
    $('#viewModalMeta').text(mainName);

    let tabsHtml = `<button class="modal-tab active" data-tab="main" data-url="${esc(mainUrl)}" data-name="${esc(mainName)}">Main Scan</button>`;

    try {
        const res = await $.getJSON(SUPPORT_URL(scanId));
        if (res.data && res.data.length) {
            const grouped = {};
            res.data.forEach(f => { const g = f.doc_type_name || 'Other'; if (!grouped[g]) grouped[g] = []; grouped[g].push(f) });
            Object.keys(grouped).forEach(gn => {
                tabsHtml += `<button class="modal-tab" data-tab="group" data-group="${esc(gn)}">${esc(gn)} (${grouped[gn].length})</button>`;
            });
            window.__ssModalGroups = grouped;
        } else {
            window.__ssModalGroups = {};
        }
    } catch(err) {
        window.__ssModalGroups = {};
    }

    $('#viewModalTabsBar').html(tabsHtml + '<div class="modal-tab-files" id="ssTabFilesPanel"></div>');
    $('#viewScanModal,#viewScanModalBackdrop').addClass('open');
    ssLoadViewer(mainUrl, mainName);
});

$(document).on('click', '#viewScanModal .modal-tab[data-tab="main"]', function() {
    $('#viewScanModal .modal-tab').removeClass('active'); $(this).addClass('active');
    $('#ssTabFilesPanel').removeClass('open').empty();
    ssLoadViewer($(this).data('url'), $(this).data('name'));
});

$(document).on('click', '#viewScanModal .modal-tab[data-tab="group"]', function() {
    const gn = $(this).data('group');
    const files = window.__ssModalGroups[gn] || [];
    $('#viewScanModal .modal-tab').removeClass('active'); $(this).addClass('active');
    let html = '';
    files.forEach(f => { html += `<div class="file-link" data-url="${esc(f.File_Location)}" data-name="${esc(f.File)}"><span class="file-ext">${esc(f.File_Ext || '?')}</span><span>${esc(f.File)}</span></div>` });
    $('#ssTabFilesPanel').html(html).addClass('open');
});

$(document).on('click', '#ssTabFilesPanel .file-link', function() {
    const url = $(this).data('url'); const name = $(this).data('name');
    $('#ssTabFilesPanel .file-link').removeClass('active'); $(this).addClass('active');
    ssLoadViewer(url, name);
});

function ssLoadViewer(url, name) {
    const $body = $('#viewModalViewerBody'); $body.find('iframe,img').remove();
    $('#viewModalFileName').text(name);
    const isPdf = url.toLowerCase().includes('.pdf'); const isImg = /\.(jpe?g|png|gif|webp)(\?|$)/i.test(url);
    if (isPdf) { $body.append(`<iframe src="${esc(url)}"></iframe>`) }
    else if (isImg) { $body.append(`<img src="${esc(url)}" alt="${esc(name)}">`) }
    else { $body.append(`<iframe src="${esc(url)}"></iframe>`) }
}

$('#btnCloseViewModal,#viewScanModalBackdrop').on('click', function() {
    $('#viewScanModal,#viewScanModalBackdrop').removeClass('open');
    $('#viewModalTabsBar').empty(); $('#viewModalViewerBody').find('iframe,img').remove();
    window.__ssModalMainUrl = null; window.__ssModalMainName = null; window.__ssModalGroups = {};
});

});
</script>
@endpush
