@extends('layouts.app')
@section('title', 'Data Punching')
@section('page-title', 'Data Punching')
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
        }.badge-approved{background:#dcfce7;color:#15803d;display:inline-flex;align-items:center;padding:.15rem .5rem;border-radius:9999px;font-size:.6rem;font-weight:600}
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
#punchTable{border-collapse:collapse;width:100%!important;table-layout:auto}
#punchTable thead th{background:#fafaf9;color:#78716c;font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;padding:.45rem .6rem;border-bottom:2px solid #e7e5e4;white-space:nowrap;text-align:left}
#punchTable tbody td{padding:.45rem .6rem;border-bottom:1px solid #f0eeec;color:#292524;vertical-align:middle;font-size:.7rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#punchTable tbody tr{cursor:pointer;transition:background .1s}#punchTable tbody tr:hover td{background:#fef2f2}
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
#punchTable_wrapper>.dataTables_length,#punchTable_wrapper>.dataTables_filter,#punchTable_wrapper>.dataTables_info,#punchTable_wrapper>.dataTables_paginate{display:none!important}
.modal-backdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:300;backdrop-filter:blur(2px)}.modal-backdrop.open{display:block}
.modal-container{display:none;position:fixed;inset:0;z-index:301;padding:1rem;overflow-y:auto}.modal-container.open{display:flex;align-items:center;justify-content:center}
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
.info-row{display:flex;align-items:center;gap:.4rem;padding:.3rem 0;border-bottom:1px solid #f5f5f4}.info-row:last-child{border-bottom:none}
.info-label{font-size:.58rem;font-weight:700;color:#78716c;text-transform:uppercase;letter-spacing:.03em;width:70px;flex-shrink:0}
.info-value{font-size:.7rem;color:#292524;word-break:break-word;flex:1}
</style>
@endpush

@section('content')
<div class="bg-white border border-stone-200 rounded-xl flex flex-col">
    <div class="px-4 py-2.5 border-b border-stone-100 flex items-center justify-between gap-3 flex-shrink-0">
        <div><h2 class="text-sm font-semibold text-stone-800">Data Punching</h2><p class="text-[11px] text-stone-400">Click a row to view details and mark as punched</p></div>
    </div>
    <div class="tabs">
        <button class="tab-btn active" data-tab="pending">Pending<span class="tab-badge" id="badge-pending">0</span></button>
        <button class="tab-btn" data-tab="my-punching">My Punching<span class="tab-badge" id="badge-my">0</span></button>
        <button class="tab-btn" data-tab="rejected">Rejected (Edit)<span class="tab-badge" id="badge-rejected">0</span></button>
    </div>
    <div class="filter-bar">
        <select id="filterScanner" style="width:140px"></select>
        <select id="filterApprover" style="width:140px"></select>
        <select id="filterDocType" style="width:150px"></select>
        <select id="filterLocation" style="width:140px"></select>
        <input type="date" id="filterFromDate" class="filter-input" style="width:120px" onfocus="this.showPicker()">
        <input type="date" id="filterToDate" class="filter-input" style="width:120px" onfocus="this.showPicker()">
        <label id="editPermCheckWrap" class="flex items-center gap-1 text-[10px] text-stone-600" style="display:none"><input type="checkbox" id="chkIncludeNoEdit" class="w-3 h-3 rounded border-stone-300 accent-red-700">Include no-edit</label>
        <button id="btnApplyFilters" class="filter-btn filter-btn-primary"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>Apply</button>
        <button id="btnResetFilters" class="filter-btn filter-btn-secondary">Reset</button>
    </div>
    <div class="dt-ctrl-bar border-b border-stone-100">
        <div class="flex items-center gap-2 text-xs text-stone-500"><span>Show</span><select class="dt-length-sel" id="dtLength"><option value="10">10</option><option value="25" selected>25</option><option value="50">50</option></select><span>entries</span></div>
        <div><input type="text" class="dt-search-input" id="dtSearch" placeholder="Search…"></div>
    </div>
    <div class="overflow-x-auto flex-1">
        <table id="punchTable" style="width:100%"><thead><tr>
            <th style="width:30px">#</th><th>Location</th><th>File</th><th>Document Name</th><th>Doc Type</th><th>Bill Date</th><th>Scan Date</th><th>Scanned By</th><th style="width:70px">Status</th>
        </tr></thead></table>
    </div>
    <div class="dt-ctrl-bar border-t border-stone-100"><div id="dtInfo"></div><div id="dtPaginate"></div></div>
</div>

{{-- Detail Modal --}}
<div class="modal-backdrop" id="punchBackdrop"></div>
<div class="modal-container" id="punchModal">
    <div style="background:#fff;border-radius:1rem;box-shadow:0 20px 50px rgba(0,0,0,.25);width:100%;max-width:1150px;height:85vh;display:flex;flex-direction:column;overflow:hidden">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:.6rem 1rem;border-bottom:1px solid #e7e5e4;flex-shrink:0">
            <div><h3 class="text-xs font-semibold text-stone-800" id="punchTitle">—</h3><p class="text-[10px] text-stone-400" id="punchSub">—</p></div>
            <button id="btnCloseModal" class="w-6 h-6 flex items-center justify-center rounded text-stone-400 hover:bg-stone-100"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div style="flex:1;overflow:hidden;display:grid;grid-template-columns:1.5fr 1fr">
            <div style="display:flex;flex-direction:column;border-right:1px solid #e7e5e4;overflow:hidden">
                <div class="vm-tabs-bar" id="punchTabsBar"></div>
                <div class="vm-viewer"><div class="vm-viewer-bar"><span id="punchFileName" class="text-[9px] font-semibold text-stone-300">—</span></div><div class="vm-viewer-body" id="punchViewerBody"></div></div>
            </div>
            <div style="display:flex;flex-direction:column;overflow-y:auto;padding:.75rem 1rem">
                <div id="punchInfo"></div>
                <div id="punchActions" class="border-t border-stone-100 pt-3 mt-3"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function(){
const CSRF=$('meta[name="csrf-token"]').attr('content');
const R={
    data:'{{ route("workflow.punching.data") }}',
    tabCounts:'{{ route("workflow.punching.tab-counts") }}',
    scanners:'{{ route("workflow.punching.scanners") }}',
    approvers:'{{ route("workflow.punching.approvers") }}',
    docTypes:'{{ route("workflow.punching.doc-types") }}',
    locations:'{{ route("workflow.punching.locations") }}',
    detail:id=>`/workflow/punching/${id}/detail`,
    supportList:id=>`/workflow/punching/${id}/support-list`,
    markPunched:id=>`/workflow/punching/${id}/mark-punched`,
};
let currentTab='pending';
let filters={scanned_by:'',approver:'',doc_type_id:'',location_id:'',from_date:'',to_date:'',include_no_edit:''};
function esc(s){return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;')}

function s2(sel,url,ph){$(sel).select2({placeholder:ph,allowClear:true,minimumInputLength:0,ajax:{url,dataType:'json',delay:200,data:p=>({q:p.term||'',page:p.page||1}),processResults:d=>({results:d.results,pagination:d.pagination})}})}
s2('#filterScanner',R.scanners,'All Scanners');
s2('#filterApprover',R.approvers,'All Approvers');
s2('#filterDocType',R.docTypes,'All Doc Types');
s2('#filterLocation',R.locations,'All Locations');

const dt=$('#punchTable').DataTable({
    serverSide:true,processing:true,
    ajax:{url:R.data,type:'GET',data:d=>Object.assign(d,filters,{tab:currentTab})},
    order:[[7,'desc']],pageLength:25,dom:'rtp',
    columns:[
        {data:'DT_RowIndex',orderable:false,searchable:false,className:'text-center'},
        {data:'location_name',defaultContent:'—'},
        {data:'File',defaultContent:'—',render:d=>d?`<span class="text-blue-600">${esc(d)}</span>`:'—'},
        {data:'Document_name',defaultContent:'—'},
        {data:'doc_type_name',defaultContent:'—',searchable:false},
        {data:'bill_voucher_date',defaultContent:'—',searchable:false},
        {data:'scan_date',defaultContent:'—',searchable:false},
        {data:'scanned_by',defaultContent:'—',searchable:false},
        {data:'status_badge',orderable:false,searchable:false,className:'text-center'},
    ],
    language:{emptyTable:'No records found',processing:'<span style="font-size:.7rem;color:#7f1d1d">Loading…</span>'},
    drawCallback(){$('#punchTable_wrapper .dataTables_paginate').first().appendTo('#dtPaginate');$('#punchTable_wrapper .dataTables_info').first().appendTo('#dtInfo');updateBadges()},
    createdRow(row,data){$(row).attr('data-scan-id',data.Scan_Id||data.scan_id||'')},
});

$('#punchTable tbody').on('click','tr',function(){const id=$(this).attr('data-scan-id');if(id)window.location.href='/workflow/punching/entry/'+id});
$('#dtLength').on('change',function(){dt.page.len(+$(this).val()).draw()});
let st;$('#dtSearch').on('input',function(){clearTimeout(st);const v=$(this).val();st=setTimeout(()=>dt.search(v).draw(),300)});
$('.tab-btn').on('click',function(){currentTab=$(this).data('tab');$('.tab-btn').removeClass('active');$(this).addClass('active');$('#editPermCheckWrap').toggle(currentTab==='rejected');dt.ajax.reload()});
$('#btnApplyFilters').on('click',function(){filters.scanned_by=$('#filterScanner').val()||'';filters.approver=$('#filterApprover').val()||'';filters.doc_type_id=$('#filterDocType').val()||'';filters.location_id=$('#filterLocation').val()||'';filters.from_date=$('#filterFromDate').val();filters.to_date=$('#filterToDate').val();filters.include_no_edit=$('#chkIncludeNoEdit').is(':checked')?'1':'';dt.ajax.reload()});
$('#btnResetFilters').on('click',function(){$('#filterScanner,#filterApprover,#filterDocType,#filterLocation').val(null).trigger('change');$('#filterFromDate,#filterToDate').val('');$('#chkIncludeNoEdit').prop('checked',false);filters={scanned_by:'',approver:'',doc_type_id:'',location_id:'',from_date:'',to_date:'',include_no_edit:''};dt.ajax.reload()});
async function updateBadges(){try{const c=await $.getJSON(R.tabCounts);$('#badge-pending').text(c.pending||0);$('#badge-my').text(c.my||0);$('#badge-rejected').text(c.rejected||0)}catch(e){}}

async function openModal(scanId){
    try{
        const [detailRes,supportRes]=await Promise.all([$.getJSON(R.detail(scanId)),$.getJSON(R.supportList(scanId))]);
        const d=detailRes.data;if(!d)return;

        $('#punchTitle').text(`Scan #${d.Scan_Id}`);
        $('#punchSub').text(`${d.company_name||''} • ${d.doc_type_name||''}`);

        // Tabs
        let tabs=`<button class="vm-tab active" data-tab="main" data-url="${esc(d.File_Location)}" data-name="${esc(d.File)}">Main Scan</button>`;
        window.__pG={};
        if(supportRes.data&&supportRes.data.length){const g={};supportRes.data.forEach(f=>{const k=f.doc_type_name||'Other';if(!g[k])g[k]=[];g[k].push(f)});Object.keys(g).forEach(k=>{tabs+=`<button class="vm-tab" data-tab="group" data-group="${esc(k)}">${esc(k)} (${g[k].length})</button>`});window.__pG=g}
        $('#punchTabsBar').html(tabs+'<div class="vm-tab-files" id="pFP"></div>');
        pLoad(d.File_Location,d.File);

        // Info
        const fmt=v=>v?new Date(v).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}):'—';
        let info='';
        info+=`<div class="info-row"><span class="info-label">Location</span><span class="info-value">${esc(d.location_name||'—')}</span></div>`;
        info+=`<div class="info-row"><span class="info-label">Doc Name</span><span class="info-value">${esc(d.Document_name||'—')}</span></div>`;
        info+=`<div class="info-row"><span class="info-label">Doc Type</span><span class="info-value">${esc(d.doc_type_name||'—')}</span></div>`;
        info+=`<div class="info-row"><span class="info-label">Vendor</span><span class="info-value">${esc(d.vendor_name||'—')}</span></div>`;
        info+=`<div class="info-row"><span class="info-label">Bill Date</span><span class="info-value">${fmt(d.bill_voucher_date)}</span></div>`;
        info+=`<div class="info-row"><span class="info-label">Bill No</span><span class="info-value">${esc(d.bill_no_voucher_no||'—')}</span></div>`;
        info+=`<div class="info-row"><span class="info-label">Scanned By</span><span class="info-value">${esc(d.scanned_by||'—')}</span></div>`;
        info+=`<div class="info-row"><span class="info-label">Scan Date</span><span class="info-value">${fmt(d.scan_date)}</span></div>`;
        if(d.File_Punched==='Y') info+=`<div class="info-row"><span class="info-label">Status</span><span class="info-value"><span class="badge-approved">Punched</span> ${fmt(d.Punch_Date)}</span></div>`;
        else if(d.Edit_Permission==='Y') info+=`<div class="info-row"><span class="info-label">Status</span><span class="info-value"><span class="badge-rejected">Edit Required</span></span></div><div class="info-row"><span class="info-label">Remark</span><span class="info-value text-red-700">${esc(d.Scan_Resend_Remark||'—')}</span></div>`;
        else info+=`<div class="info-row"><span class="info-label">Status</span><span class="info-value"><span class="badge-pending">Pending</span></span></div>`;
        $('#punchInfo').html(info);

        // Actions
        let act='';
        if(d.File_Punched!=='Y'){
            act=`<button id="btnMarkPunched" class="w-full h-9 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded-lg flex items-center justify-center gap-2" data-id="${d.Scan_Id}"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Mark as Punched</button>`;
        } else {
            act=`<p class="text-[11px] text-stone-400 text-center py-2">Already punched.</p>`;
        }
        $('#punchActions').html(act);

        $('#punchModal,#punchBackdrop').addClass('open');
    }catch(e){console.error(e);alert('Failed to load')}
}

$('#btnCloseModal,#punchBackdrop').on('click',function(){$('#punchModal,#punchBackdrop').removeClass('open');$('#punchTabsBar').empty();$('#punchViewerBody').find('iframe,img').remove();$('#punchInfo,#punchActions').empty()});

$(document).on('click','#btnMarkPunched',async function(){
    const id=$(this).data('id');if(!confirm('Mark this scan as punched?'))return;
    try{await $.ajax({url:R.markPunched(id),method:'POST',headers:{'X-CSRF-TOKEN':CSRF}});$('#punchModal,#punchBackdrop').removeClass('open');dt.ajax.reload(null,false)}catch(e){alert(e.responseJSON?.message||'Failed')}
});

// File viewer
$(document).on('click','#punchTabsBar .vm-tab[data-tab="main"]',function(){$('#punchTabsBar .vm-tab').removeClass('active');$(this).addClass('active');$('#pFP').removeClass('open').empty();pLoad($(this).data('url'),$(this).data('name'))});
$(document).on('click','#punchTabsBar .vm-tab[data-tab="group"]',function(){const g=$(this).data('group');const f=window.__pG[g]||[];$('#punchTabsBar .vm-tab').removeClass('active');$(this).addClass('active');let h='';f.forEach(x=>{h+=`<div class="file-link" data-url="${esc(x.File_Location)}" data-name="${esc(x.File)}"><span class="file-ext">${esc(x.File_Ext||'?')}</span><span>${esc(x.File)}</span></div>`});$('#pFP').html(h).addClass('open')});
$(document).on('click','#pFP .file-link',function(){$('#pFP .file-link').removeClass('active');$(this).addClass('active');pLoad($(this).data('url'),$(this).data('name'))});
function pLoad(url,name){const $b=$('#punchViewerBody');$b.find('iframe,img').remove();$('#punchFileName').text(name||'');if(!url)return;url.toLowerCase().includes('.pdf')?$b.append(`<iframe src="${url}"></iframe>`):$b.append(`<img src="${url}" alt="${esc(name)}">`)}
});
</script>
@endpush
