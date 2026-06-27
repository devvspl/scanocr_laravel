@extends('layouts.app')
@section('title', 'Bill Approval')
@section('page-title', 'Bill Approval')
@push('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" defer></script>
<style>

        .select2-container--default .select2-selection--single {
            height: 24px;
            border: 1px solid #d6d3d1;
            border-radius: .5rem;
            background: #fafaf9;
            display: flex;
            align-items: center;
            min-height: 24px
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            font-size: .75rem;
            color: #292524;
            padding-left: .75rem;
            line-height: 34px
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
            right: .5rem
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #a8a29e
        }

        .select2-container--default .select2-results__option {
            font-size: .75rem;
            padding: .4rem .75rem
        }

        .select2-container--default .select2-results__option--highlighted {
            background: #7f1d1d;
            color: #fff
        }

        .select2-search--dropdown .select2-search__field {
            font-size: .75rem;
            border: 1px solid #d6d3d1;
            border-radius: .375rem;
            padding: .3rem .5rem
        }

        .select2-dropdown {
            border: 1px solid #d6d3d1;
            border-radius: .5rem;
            box-shadow: 0 4px 16px rgba(0, 0, 0, .08)
        }

        .select2-container--open .select2-selection--single {
            border-color: #7f1d1d;
            box-shadow: 0 0 0 3px rgba(127, 29, 29, .08)
        }
        .select2-container .select2-selection--single {
            height: 24px !important;
            border: 1px solid #d6d3d1 !important;
            border-radius: 0.5rem !important;
            background: #fafaf9 !important;
            padding: 0 !important
        }

        .select2-container .select2-selection--single .select2-selection__clear {
            background-color: transparent;
            border: none;
            font-size: smaller;
                color: #888888;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 26px;
            position: absolute;
            top: -4px;
            right: 1px;
            width: 20px;
        }

        .select2-container .select2-selection--single .select2-selection__rendered {
            padding: 0 0 0 12px !important;
            line-height: 34px !important;
            font-size: 10px !important;
            color: #292524 !important
        }

        .select2-container .select2-selection--single .select2-selection__arrow {
            height: 34px !important;
            right: 8px !important
        }

        .select2-container .select2-selection--single .select2-selection__arrow b {
            border-width: 4px 4px 0 4px !important;
            margin-top: -2px !important
        }
.badge-approved{background:#dcfce7;color:#15803d;display:inline-flex;align-items:center;padding:.15rem .5rem;border-radius:9999px;font-size:.6rem;font-weight:600}
.badge-pending{background:#fef9c3;color:#a16207;display:inline-flex;align-items:center;padding:.15rem .5rem;border-radius:9999px;font-size:.6rem;font-weight:600}
.badge-rejected{background:#fee2e2;color:#b91c1c;display:inline-flex;align-items:center;padding:.15rem .5rem;border-radius:9999px;font-size:.6rem;font-weight:600}
.tabs{display:flex;gap:.2rem;border-bottom:2px solid #e7e5e4;padding:0 1rem;background:#fff}
.tab-btn{padding:.5rem .85rem;font-size:.7rem;font-weight:600;color:#78716c;background:transparent;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;transition:all .15s}
.tab-btn:hover{color:#292524}.tab-btn.active{color:#7f1d1d;border-bottom-color:#7f1d1d}
.tab-badge{display:inline-flex;align-items:center;justify-content:center;min-width:1.1rem;height:1.1rem;padding:0 .3rem;border-radius:9999px;background:#e7e5e4;color:#57534e;font-size:.55rem;font-weight:700;margin-left:.3rem}
.tab-btn.active .tab-badge{background:#7f1d1d;color:#fff}
.filter-bar{display:flex;align-items:center;gap:.5rem;padding:.6rem 1rem;background:#fafaf9;border-bottom:1px solid #e7e5e4;flex-wrap:wrap}
.filter-input{height:28px;padding:0 .5rem;font-size:.7rem;border:1px solid #d6d3d1;border-radius:.375rem;background:#fff;outline:none;color:#292524}
.filter-input:focus{border-color:#7f1d1d;box-shadow:0 0 0 2px rgba(127,29,29,.06)}
.filter-btn{height:28px;display:inline-flex;align-items:center;gap:.25rem;font-size:.7rem;font-weight:600;border-radius:.375rem;cursor:pointer;transition:all .12s}
.filter-btn-primary{background:#7f1d1d;color:#fff;border:none;padding:0 .65rem}.filter-btn-primary:hover{background:#6b1a1a}
.filter-btn-secondary{background:#fff;color:#57534e;border:1px solid #d6d3d1;padding:0 .55rem}.filter-btn-secondary:hover{background:#f5f5f4}
#billTable{border-collapse:collapse;width:100%!important;table-layout:auto}
#billTable thead th{background:#fafaf9;color:#78716c;font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;padding:.45rem .6rem;border-bottom:2px solid #e7e5e4;white-space:nowrap;text-align:left}
#billTable tbody td{padding:.45rem .6rem;border-bottom:1px solid #f0eeec;color:#292524;vertical-align:middle;font-size:.7rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#billTable tbody tr{cursor:pointer;transition:background .1s}#billTable tbody tr:hover td{background:#fef2f2}
.dt-ctrl-bar{display:flex;align-items:center;justify-content:space-between;gap:.5rem;padding:.5rem 1rem;flex-wrap:wrap}
.dt-length-sel{height:1.7rem;padding:0 1rem 0 .4rem;font-size:.7rem;border:1px solid #d6d3d1;border-radius:.375rem;background:#fafaf9;color:#292524;appearance:none;cursor:pointer}
.dt-search-input{height:1.7rem;padding:0 .5rem;font-size:.7rem;border:1px solid #d6d3d1;border-radius:.375rem;background:#fafaf9;outline:none;width:150px;color:#292524}
.dt-search-input:focus{border-color:#7f1d1d;box-shadow:0 0 0 2px rgba(127,29,29,.06)}
.dataTables_info{font-size:.65rem;color:#a8a29e}
.dataTables_paginate{display:flex;gap:.15rem}
.dataTables_paginate .paginate_button{height:1.5rem;min-width:1.5rem;padding:0 .3rem;display:inline-flex;align-items:center;justify-content:center;border-radius:.3rem;font-size:.63rem;cursor:pointer;border:1px solid #e7e5e4;background:#fff;color:#292524;user-select:none}
.dataTables_paginate .paginate_button:hover:not(.disabled){background:#f5f5f4}
.dataTables_paginate .paginate_button.current{background:#7f1d1d;color:#fff;border-color:#7f1d1d}
.dataTables_paginate .paginate_button.disabled{opacity:.3;cursor:default}
#billTable_wrapper>.dataTables_length,#billTable_wrapper>.dataTables_filter,#billTable_wrapper>.dataTables_info,#billTable_wrapper>.dataTables_paginate{display:none!important}
.export-menu{position:relative;display:inline-block}
.export-drop{display:none;position:absolute;right:0;top:calc(100% + .3rem);z-index:60;background:#fff;border:1px solid #e7e5e4;border-radius:.5rem;box-shadow:0 6px 20px rgba(0,0,0,.1);min-width:130px;overflow:hidden}
.export-menu.open .export-drop{display:block}
.export-drop a{display:flex;align-items:center;gap:.4rem;padding:.4rem .75rem;font-size:.7rem;color:#292524;text-decoration:none;cursor:pointer}.export-drop a:hover{background:#f5f5f4}
#logCanvas{position:fixed;top:0;right:-400px;width:400px;height:100vh;background:#fff;z-index:200;box-shadow:-4px 0 24px rgba(0,0,0,.12);transition:right .3s cubic-bezier(.4,0,.2,1);display:flex;flex-direction:column}
#logCanvas.open{right:0}
#logCanvasBackdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:199}
#logCanvasBackdrop.open{display:block}
.lc-header{display:flex;align-items:center;justify-content:space-between;padding:.875rem 1.25rem;border-bottom:1px solid #f0eeec;flex-shrink:0}
.lc-body{flex:1;overflow-y:auto;padding:1rem 1.25rem}
.log-row{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.6rem 0;border-bottom:1px solid #f5f5f4}
.log-row:last-child{border-bottom:none}
.modal-backdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:300;backdrop-filter:blur(2px)}.modal-backdrop.open{display:block}
.modal-container{display:none;position:fixed;inset:0;z-index:301;padding:1rem;overflow-y:auto}.modal-container.open{display:flex;align-items:center;justify-content:center}
.detail-grid{display:grid;grid-template-columns:1fr;gap:0;height:100%}
@media(min-width:768px){.detail-grid{grid-template-columns:1.5fr 1fr}}
.vm-tabs-bar{display:flex;align-items:center;gap:0;padding:0 .75rem;background:#fafaf9;border-bottom:1px solid #e7e5e4;flex-shrink:0;flex-wrap:wrap;position:relative}
.vm-tab{padding:.4rem .65rem;font-size:.63rem;font-weight:600;color:#78716c;background:none;border:none;border-bottom:2px solid transparent;cursor:pointer;white-space:nowrap}.vm-tab:hover{color:#292524}.vm-tab.active{color:#7f1d1d;border-bottom-color:#7f1d1d}
.vm-tab-files{display:none;position:absolute;top:100%;left:0;right:0;z-index:10;background:#fff;border-bottom:1px solid #e7e5e4;box-shadow:0 4px 12px rgba(0,0,0,.08);padding:.3rem .5rem;max-height:100px;overflow-y:auto}
.vm-tab-files.open{display:flex;flex-wrap:wrap;align-items:center;gap:.2rem}
.vm-tab-files .file-link{display:inline-flex;align-items:center;gap:.25rem;padding:.15rem .4rem;border-radius:.25rem;cursor:pointer;font-size:.6rem;color:#292524;border:1px solid #e7e5e4;background:#fafaf9;white-space:nowrap}.vm-tab-files .file-link:hover{background:#f5f5f4}.vm-tab-files .file-link.active{background:#fef2f2;color:#7f1d1d;border-color:#7f1d1d}
.vm-tab-files .file-ext{width:.9rem;height:.9rem;display:flex;align-items:center;justify-content:center;background:#f5f5f4;border-radius:.15rem;font-size:5px;font-weight:700;color:#78716c;text-transform:uppercase;flex-shrink:0;border:1px solid #e7e5e4}
.vm-viewer{flex:1;min-height:0;display:flex;flex-direction:column;background:#1c1917}
.vm-viewer-bar{padding:.35rem .65rem;background:rgba(0,0,0,.4);flex-shrink:0}
.vm-viewer-body{flex:1;position:relative;min-height:300px}
.vm-viewer-body iframe,.vm-viewer-body img{position:absolute;inset:0;width:100%;height:100%;border:none;object-fit:contain;background:#1c1917}
.info-row{display:flex;align-items:center;gap:.4rem;padding:.3rem 0;border-bottom:1px solid #f5f5f4}
.info-row:last-child{border-bottom:none}
.info-label{font-size:.58rem;font-weight:700;color:#78716c;text-transform:uppercase;letter-spacing:.03em;width:70px;flex-shrink:0}
.info-value{font-size:.7rem;color:#292524;word-break:break-word;flex:1}
.action-grid{grid-template-columns:1fr !important}
@media(min-width:768px){.action-grid{grid-template-columns:1fr 1fr !important}}
</style>
@endpush

@section('content')
<div class="bg-white border border-stone-200 rounded-xl flex flex-col">
    <div class="px-4 py-2.5 border-b border-stone-100 flex items-center justify-between gap-3 flex-shrink-0">
        <div><h2 class="text-sm font-semibold text-stone-800">Bill Approval</h2><p class="text-[11px] text-stone-400">Click a row to review and take action</p></div>
        <div class="flex items-center gap-2">
            <button id="btnOpenLog" class="h-8 px-3 flex items-center gap-1.5 text-xs font-medium border border-stone-200 rounded-lg text-stone-600 hover:bg-stone-50 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export Log
            </button>
            <div class="export-menu" id="exportMenu">
                <button id="btnExportToggle" class="h-8 px-3 flex items-center gap-1.5 text-xs font-medium bg-stone-800 hover:bg-stone-900 text-white rounded-lg transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Export
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="export-drop">
                    <a id="btnExportExcel">
                        <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9v10a2 2 0 01-2 2z"/></svg>
                        Export Excel
                    </a>
                    <a id="btnExportPdf">
                        <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        Export PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="tabs">
        <button class="tab-btn active" data-tab="pending">Pending<span class="tab-badge" id="badge-pending">0</span></button>
        <button class="tab-btn" data-tab="approved">Approved<span class="tab-badge" id="badge-approved">0</span></button>
        <button class="tab-btn" data-tab="rejected">Rejected<span class="tab-badge" id="badge-rejected">0</span></button>
        <button class="tab-btn" data-tab="all">All<span class="tab-badge" id="badge-all">0</span></button>
    </div>
    <div class="filter-bar">
        <select id="filterCompany" style="width:145px"></select>
        <select id="filterFY" style="width:105px"></select>
        <select id="filterLocation" style="width:145px"></select>
        <select id="filterScannedBy" style="width:145px"></select>
        <input type="date" id="filterFromDate" class="filter-input" style="width:115px" onfocus="this.showPicker()">
        <input type="date" id="filterToDate" class="filter-input" style="width:115px" onfocus="this.showPicker()">
        <button id="btnApplyFilters" class="filter-btn filter-btn-primary"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>Apply</button>
        <button id="btnResetFilters" class="filter-btn filter-btn-secondary">Reset</button>
    </div>
    <div class="dt-ctrl-bar border-b border-stone-100">
        <div class="flex items-center gap-2 text-xs text-stone-500"><span>Show</span><select class="dt-length-sel" id="dtLength"><option value="10">10</option><option value="25" selected>25</option><option value="50">50</option></select><span>entries</span></div>
        <div><input type="text" class="dt-search-input" id="dtSearch" placeholder="Search…"></div>
    </div>
    <div class="overflow-x-auto flex-1">
        <table id="billTable" style="width:100%"><thead><tr>
            <th style="width:30px">#</th><th>Company</th><th>Location</th><th>File</th><th>Vendor</th><th>Bill Date</th><th>Bill No</th><th>Scan Date</th><th>Scanned By</th><th style="width:65px">Status</th>
        </tr></thead></table>
    </div>
    <div class="dt-ctrl-bar border-t border-stone-100"><div id="dtInfo"></div><div id="dtPaginate"></div></div>
</div>

{{-- Detail Modal --}}
<div class="modal-backdrop" id="detailBackdrop"></div>
<div class="modal-container" id="detailModal">
    <div style="background:#fff;border-radius:1rem;box-shadow:0 20px 50px rgba(0,0,0,.25);width:100%;max-width:1150px;height:85vh;display:flex;flex-direction:column;overflow:hidden">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:.6rem 1rem;border-bottom:1px solid #e7e5e4;flex-shrink:0">
            <div><h3 class="text-xs font-semibold text-stone-800" id="detailTitle">—</h3><p class="text-[10px] text-stone-400" id="detailSub">—</p></div>
            <button id="btnCloseDetail" class="w-6 h-6 flex items-center justify-center rounded text-stone-400 hover:bg-stone-100"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div class="detail-grid" style="flex:1;overflow:hidden">
            <div style="display:flex;flex-direction:column;border-right:1px solid #e7e5e4;overflow:hidden">
                <div class="vm-tabs-bar" id="detailTabsBar"></div>
                <div class="vm-viewer"><div class="vm-viewer-bar"><span id="detailFileName" class="text-[9px] font-semibold text-stone-300">—</span></div><div class="vm-viewer-body" id="detailViewerBody"></div></div>
            </div>
            <div style="display:flex;flex-direction:column;overflow-y:auto;padding:.75rem 1rem;gap:0">
                <div id="detailInfo" style="flex:1"></div>
                <div id="detailActions" style="flex-shrink:0;padding-top:.6rem;border-top:1px solid #e7e5e4;margin-top:.6rem"></div>
            </div>
        </div>
    </div>
</div>
{{-- Export Log Offcanvas --}}
<div id="logCanvasBackdrop"></div>
<div id="logCanvas">
    <div class="lc-header">
        <div><h3 class="text-sm font-semibold text-stone-800">Export Log</h3><p class="text-xs text-stone-400 mt-0.5">Your recent bill approval exports</p></div>
        <button id="btnCloseLog" class="w-7 h-7 flex items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
    </div>
    <div class="lc-body" id="logBody"><p class="text-center text-xs text-stone-400 py-8">No exports yet.</p></div>
</div>
@endsection

@push('scripts')
<script>
$(function(){
const CSRF=$('meta[name="csrf-token"]').attr('content');
const R={
    data:'{{ route("workflow.bill-approval.data") }}',
    tabCounts:'{{ route("workflow.bill-approval.tab-counts") }}',
    companies:'{{ route("workflow.bill-approval.companies") }}',
    fys:'{{ route("workflow.bill-approval.financial-years") }}',
    locations:'{{ route("workflow.bill-approval.locations") }}',
    users:'{{ route("workflow.bill-approval.users") }}',
    detail:id=>`/workflow/bill-approval/${id}/detail`,
    approve:id=>`/workflow/bill-approval/${id}/approve`,
    reject:id=>`/workflow/bill-approval/${id}/reject`,
    supportList:id=>`/workflow/bill-approval/${id}/support-list`,
    reasons:'{{ route("workflow.bill-approval.rejection-reasons") }}',
    reasonsStore:'{{ route("workflow.bill-approval.rejection-reasons.store") }}',
    exportLogs:'{{ route("workflow.bill-approval.export.logs") }}',
};
let currentTab='pending';
let filters={company_id:'',fy_id:'',location_id:'',scanned_by:'',from_date:'',to_date:''};
function esc(s){return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;')}

// Select2 filters
function s2(sel,url,ph){$(sel).select2({placeholder:ph,allowClear:true,minimumInputLength:0,ajax:{url,dataType:'json',delay:200,data:p=>({q:p.term||'',page:p.page||1}),processResults:d=>({results:d.results,pagination:d.pagination})}})}
s2('#filterCompany',R.companies,'All Companies');
s2('#filterFY',R.fys,'All FY');
s2('#filterLocation',R.locations,'All Locations');
s2('#filterScannedBy',R.users,'All Scanned By');

const dt=$('#billTable').DataTable({
    serverSide:true,processing:true,
    ajax:{url:R.data,type:'GET',data:d=>Object.assign(d,filters,{tab:currentTab})},
    order:[[5,'desc']],pageLength:10,dom:'rtp',
    columns:[
        {data:'DT_RowIndex',orderable:false,searchable:false,className:'text-center'},
        {data:'company_name',defaultContent:'—'},
        {data:'location_name',defaultContent:'—'},
        {data:'File',defaultContent:'—',render:d=>d?`<span class="text-blue-600">${esc(d)}</span>`:'—'},
        {data:'vendor_name',defaultContent:'—'},
        {data:'bill_voucher_date',defaultContent:'—'},
        {data:'bill_no_voucher_no',defaultContent:'—',searchable:false},
        {data:'scan_date',defaultContent:'—',searchable:false},
        {data:'scanned_by',defaultContent:'—'},
        {data:'status_badge',orderable:false,searchable:false,className:'text-center'},
    ],
    language:{emptyTable:'No bills found',processing:'<span style="font-size:.7rem;color:#7f1d1d">Loading…</span>'},
    drawCallback(){$('#billTable_wrapper .dataTables_paginate').first().appendTo('#dtPaginate');$('#billTable_wrapper .dataTables_info').first().appendTo('#dtInfo');updateBadges()},
    createdRow(row,data){$(row).attr('data-scan-id',data.Scan_Id)},
});

$('#billTable tbody').on('click','tr',function(){const id=$(this).data('scan-id');if(id)openDetailModal(id)});
$('#dtLength').on('change',function(){dt.page.len(+$(this).val()).draw()});
let st;$('#dtSearch').on('input',function(){clearTimeout(st);const v=$(this).val();st=setTimeout(()=>dt.search(v).draw(),300)});
$('.tab-btn').on('click',function(){currentTab=$(this).data('tab');$('.tab-btn').removeClass('active');$(this).addClass('active');dt.ajax.reload()});
$('#btnApplyFilters').on('click',function(){filters.company_id=$('#filterCompany').val()||'';filters.fy_id=$('#filterFY').val()||'';filters.location_id=$('#filterLocation').val()||'';filters.scanned_by=$('#filterScannedBy').val()||'';filters.from_date=$('#filterFromDate').val();filters.to_date=$('#filterToDate').val();dt.ajax.reload()});
$('#btnResetFilters').on('click',function(){$('#filterCompany,#filterFY,#filterLocation,#filterScannedBy').val(null).trigger('change');$('#filterFromDate,#filterToDate').val('');filters={company_id:'',fy_id:'',location_id:'',scanned_by:'',from_date:'',to_date:''};dt.ajax.reload()});
async function updateBadges(){try{const c=await $.getJSON(R.tabCounts,filters);$('#badge-all').text(c.all||0);$('#badge-pending').text(c.pending||0);$('#badge-approved').text(c.approved||0);$('#badge-rejected').text(c.rejected||0)}catch(e){}}

// Export
$('#btnExportToggle').on('click',function(e){e.stopPropagation();$('#exportMenu').toggleClass('open')});
$(document).on('click',function(){$('#exportMenu').removeClass('open')});
$('#btnExportExcel').on('click',function(){
    const params=new URLSearchParams(Object.assign({},filters,{tab:currentTab}));
    window.location.href='{{ route("workflow.bill-approval.export.excel") }}?'+params.toString();
    $('#exportMenu').removeClass('open');
});
$('#btnExportPdf').on('click',function(){
    const params=new URLSearchParams(Object.assign({},filters,{tab:currentTab}));
    window.location.href='{{ route("workflow.bill-approval.export.pdf") }}?'+params.toString();
    $('#exportMenu').removeClass('open');
});

// Export Log offcanvas
$('#btnOpenLog').on('click',function(){$('#logCanvas,#logCanvasBackdrop').addClass('open');loadLogEntries()});
$('#btnCloseLog,#logCanvasBackdrop').on('click',function(){$('#logCanvas,#logCanvasBackdrop').removeClass('open')});
async function loadLogEntries(){
    $('#logBody').html('<p class="text-center text-xs text-stone-400 py-8">Loading…</p>');
    try{
        const res=await $.getJSON(R.exportLogs);
        if(!res.data.length){$('#logBody').html('<p class="text-center text-xs text-stone-400 py-8">No exports yet.</p>');return}
        const rows=res.data.map(l=>`<div class="log-row"><div class="min-w-0"><p class="text-xs font-medium text-stone-700 truncate" title="${esc(l.file_name)}">${esc(l.file_name)}</p><p class="text-[10px] text-stone-400 mt-0.5">${l.row_count} rows &bull; ${fmtDate(l.created_at)}</p></div><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold flex-shrink-0 ${l.file_name.endsWith('.xlsx')?'bg-green-50 text-green-700':'bg-red-50 text-red-700'}">${l.file_name.endsWith('.xlsx')?'Excel':'PDF'}</span></div>`).join('');
        $('#logBody').html(rows);
    }catch(e){$('#logBody').html('<p class="text-center text-xs text-red-500 py-8">Failed to load.</p>')}
}
function fmtDate(s){if(!s)return'—';const d=new Date(s);return d.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'})+' '+d.toLocaleTimeString('en-GB',{hour:'2-digit',minute:'2-digit'})}

// ── Detail Modal ──────────────────────────────────────────────────────────
async function openDetailModal(scanId){
    try{
        const [detailRes,supportRes]=await Promise.all([$.getJSON(R.detail(scanId)),$.getJSON(R.supportList(scanId))]);
        const d=detailRes.data;if(!d){alert('Not found');return}

        $('#detailTitle').text(`Scan #${d.Scan_Id}`);
        $('#detailSub').text(`${d.company_name||''} • ${d.fy_label||''}`);

        // Tabs
        let tabs=`<button class="vm-tab active" data-tab="main" data-url="${esc(d.File_Location)}" data-name="${esc(d.File)}">Main Scan</button>`;
        window.__dG={};
        if(supportRes.data&&supportRes.data.length){const g={};supportRes.data.forEach(f=>{const k=f.doc_type_name||'Other';if(!g[k])g[k]=[];g[k].push(f)});Object.keys(g).forEach(k=>{tabs+=`<button class="vm-tab" data-tab="group" data-group="${esc(k)}">${esc(k)} (${g[k].length})</button>`});window.__dG=g}
        $('#detailTabsBar').html(tabs+'<div class="vm-tab-files" id="dFP"></div>');
        dLoad(d.File_Location,d.File);

        // Info
        const fmt=v=>v?new Date(v).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}):'—';
        let info=`<div class="info-row"><span class="info-label">Company</span><span class="info-value">${esc(d.company_name||'—')}</span></div>`;
        info+=`<div class="info-row"><span class="info-label">Location</span><span class="info-value">${esc(d.location_name||'—')}</span></div>`;
        info+=`<div class="info-row"><span class="info-label">Vendor</span><span class="info-value">${esc(d.vendor_name||'—')}</span></div>`;
        info+=`<div class="info-row"><span class="info-label">Bill Date</span><span class="info-value">${fmt(d.bill_voucher_date)}</span></div>`;
        info+=`<div class="info-row"><span class="info-label">Bill No</span><span class="info-value">${esc(d.bill_no_voucher_no||'—')}</span></div>`;
        info+=`<div class="info-row"><span class="info-label">Doc Name</span><span class="info-value">${esc(d.Document_name||'—')}</span></div>`;
        info+=`<div class="info-row"><span class="info-label">Scanned By</span><span class="info-value">${esc(d.scanned_by||'—')}</span></div>`;
        info+=`<div class="info-row"><span class="info-label">Scan Date</span><span class="info-value">${fmt(d.scan_date)}</span></div>`;
        if(d.Bill_Approved==='Y') info+=`<div class="info-row"><span class="info-label">Status</span><span class="info-value"><span class="badge-approved">Approved</span></span></div><div class="info-row"><span class="info-label">Approved On</span><span class="info-value">${fmt(d.Bill_Approver_Date)}</span></div>${d.Bill_Approver_Remark?`<div class="info-row"><span class="info-label">Remark</span><span class="info-value text-[11px]">${esc(d.Bill_Approver_Remark)}</span></div>`:''}`;
        else if(d.Bill_Approved==='R') info+=`<div class="info-row"><span class="info-label">Status</span><span class="info-value"><span class="badge-rejected">Rejected</span></span></div><div class="info-row"><span class="info-label">Rejected On</span><span class="info-value">${fmt(d.Bill_Approver_Date)}</span></div><div class="info-row"><span class="info-label">Remark</span><span class="info-value text-red-700 text-[11px]">${esc(d.Bill_Approver_Remark||'—')}</span></div>`;
        else info+=`<div class="info-row"><span class="info-label">Status</span><span class="info-value"><span class="badge-pending">Pending</span></span></div>`;
        $('#detailInfo').html(info);

        // Actions
        let act='';
        if(d.Bill_Approved==='N'){
            act=`<div style="display:grid;grid-template-columns:1fr;gap:.75rem;align-items:start" class="action-grid">
                <div><label class="text-[10px] font-medium text-stone-500 mb-1 block">Remark (optional)</label><input type="text" id="approveRemark" placeholder="Add a note…" class="w-full h-7 px-2 text-[11px] border border-stone-200 rounded-md bg-white outline-none focus:border-stone-400 mb-2"><button id="btnApprove" class="w-full h-8 bg-green-600 hover:bg-green-700 text-white text-[11px] font-semibold rounded-lg flex items-center justify-center gap-1.5" data-id="${d.Scan_Id}"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Approve</button></div>
                <div><label class="text-[10px] font-medium text-stone-500 mb-1 block">Rejection Reason <span class="text-red-500">*</span></label><select id="sel-reject-reason" style="width:100%" class="mb-2"></select><button id="btnReject" style="margin-top:8px" class="w-full h-8 bg-red-700 hover:bg-red-800 text-white text-[11px] font-semibold rounded-lg flex items-center justify-center gap-1.5" data-id="${d.Scan_Id}"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>Reject</button></div>
            </div>`;
        } else {
            act=`<p class="text-[11px] text-stone-400 text-center py-2">Bill already ${d.Bill_Approved==='Y'?'approved':'rejected'}.</p>`;
        }
        $('#detailActions').html(act);
        if(d.Bill_Approved==='N') initReason();

        $('#detailModal,#detailBackdrop').addClass('open');
    }catch(e){console.error(e);alert('Failed to load')}
}

$('#btnCloseDetail,#detailBackdrop').on('click',function(){$('#detailModal,#detailBackdrop').removeClass('open');$('#detailTabsBar').empty();$('#detailViewerBody').find('iframe,img').remove();$('#detailInfo,#detailActions').empty()});

// Approve
$(document).on('click','#btnApprove',async function(){const id=$(this).data('id');if(!confirm('Approve this bill?'))return;const remark=$('#approveRemark').val()||'';try{await $.ajax({url:R.approve(id),method:'POST',headers:{'X-CSRF-TOKEN':CSRF},data:{remark}});$('#detailModal,#detailBackdrop').removeClass('open');dt.ajax.reload(null,false)}catch(e){alert(e.responseJSON?.message||'Failed')}});

// Reject
$(document).on('click','#btnReject',async function(){
    const id=$(this).data('id');const sel=$('#sel-reject-reason').select2('data')[0];
    if(!sel||!sel.id){alert('Rejection reason is required');return}
    let reason=sel.text;
    if(sel.newTag){try{await $.ajax({url:R.reasonsStore,method:'POST',headers:{'X-CSRF-TOKEN':CSRF},data:{reason}})}catch(e){if(e.status!==422){alert('Failed');return}}}
    try{await $.ajax({url:R.reject(id),method:'POST',headers:{'X-CSRF-TOKEN':CSRF},data:{reason}});$('#detailModal,#detailBackdrop').removeClass('open');dt.ajax.reload(null,false)}catch(e){alert(e.responseJSON?.message||'Failed')}
});

function initReason(){const $s=$('#sel-reject-reason');if($s.data('select2'))$s.select2('destroy');$s.empty().append('<option value="">Select or type…</option>');$s.select2({placeholder:'Select or type reason…',allowClear:true,tags:true,createTag:p=>{const t=$.trim(p.term);return t?{id:t,text:t,newTag:true}:null},ajax:{url:R.reasons,dataType:'json',delay:200,data:p=>({q:p.term||'',page:p.page||1}),processResults:d=>({results:d.results,pagination:d.pagination})},dropdownParent:$('#detailModal')})}

// File Viewer
$(document).on('click','#detailTabsBar .vm-tab[data-tab="main"]',function(){$('#detailTabsBar .vm-tab').removeClass('active');$(this).addClass('active');$('#dFP').removeClass('open').empty();dLoad($(this).data('url'),$(this).data('name'))});
$(document).on('click','#detailTabsBar .vm-tab[data-tab="group"]',function(){const g=$(this).data('group');const f=window.__dG[g]||[];$('#detailTabsBar .vm-tab').removeClass('active');$(this).addClass('active');let h='';f.forEach(x=>{h+=`<div class="file-link" data-url="${esc(x.File_Location)}" data-name="${esc(x.File)}"><span class="file-ext">${esc(x.File_Ext||'?')}</span><span>${esc(x.File)}</span></div>`});$('#dFP').html(h).addClass('open')});
$(document).on('click','#dFP .file-link',function(){$('#dFP .file-link').removeClass('active');$(this).addClass('active');dLoad($(this).data('url'),$(this).data('name'))});
function dLoad(url,name){const $b=$('#detailViewerBody');$b.find('iframe,img').remove();$('#detailFileName').text(name||'');if(!url)return;url.toLowerCase().includes('.pdf')?$b.append(`<iframe src="${url}"></iframe>`):$b.append(`<img src="${url}" alt="${esc(name)}">`)}
});
</script>
@endpush
