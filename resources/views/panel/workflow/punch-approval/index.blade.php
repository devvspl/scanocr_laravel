@extends('layouts.app')
@section('title', 'Punch Approval')
@section('page-title', 'Punch Approval')
@push('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" defer></script>
<style>
.select2-container--default .select2-selection--single {height: 24px;border: 1px solid #d6d3d1;border-radius: .5rem;background: #fafaf9;display: flex;align-items: center;min-height: 24px}
.select2-container--default .select2-selection--single .select2-selection__rendered {font-size: .75rem;color: #292524;padding-left: .75rem;line-height: 34px}
.select2-container--default .select2-selection--single .select2-selection__arrow {height: 36px;right: .5rem}
.select2-container--default .select2-selection--single .select2-selection__placeholder {color: #a8a29e}
.select2-container--default .select2-results__option {font-size: .75rem;padding: .4rem .75rem}.select2-container--default .select2-results__option--highlighted {background: #7f1d1d;color: #fff}
.select2-search--dropdown .select2-search__field {font-size: .75rem;border: 1px solid #d6d3d1;border-radius: .375rem;padding: .3rem .5rem}
.select2-dropdown {border: 1px solid #d6d3d1;border-radius: .5rem;box-shadow: 0 4px 16px rgba(0, 0, 0, .08)}
.select2-container--open .select2-selection--single {border-color: #7f1d1d;box-shadow: 0 0 0 3px rgba(127, 29, 29, .08)}.select2-container .select2-selection--single {height: 24px !important;border: 1px solid #d6d3d1 !important;border-radius: 0.5rem !important;background: #fafaf9 !important;padding: 0 !important}
.select2-container .select2-selection--single .select2-selection__clear {background-color: transparent;border: none;font-size: smaller;color: #888888;}
.select2-container--default .select2-selection--single .select2-selection__arrow {height: 26px;position: absolute;top: -4px;right: 1px;width: 20px;}
.select2-container .select2-selection--single .select2-selection__rendered {padding: 0 0 0 12px !important;line-height: 34px !important;font-size: 10px !important;color: #292524 !important}
.select2-container .select2-selection--single .select2-selection__arrow {height: 34px !important;right: 8px !important}
.select2-container .select2-selection--single .select2-selection__arrow b {border-width: 4px 4px 0 4px !important;margin-top: -2px !important}.entry-grid{display:grid;grid-template-columns:1fr;height:calc(100vh - 120px)}
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
#paTable{border-collapse:collapse;width:100% !important}
#paTable thead th{background:#fafaf9;color:#78716c;font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;padding:.45rem .6rem;border-bottom:2px solid #e7e5e4;white-space:nowrap;text-align:left}
#paTable tbody td{padding:.45rem .6rem;border-bottom:1px solid #f0eeec;color:#292524;vertical-align:middle;font-size:.7rem;white-space:nowrap}
#paTable tbody tr{cursor:pointer;transition:background .1s}#paTable tbody tr:hover td{background:#fef2f2}
.dt-ctrl-bar{display:flex;align-items:center;justify-content:space-between;gap:.5rem;padding:.5rem 1rem;flex-wrap:wrap}
.dt-length-sel{height:1.7rem;padding:0 1rem 0 .4rem;font-size:.7rem;border:1px solid #d6d3d1;border-radius:.375rem;background:#fafaf9;color:#292524;appearance:none;cursor:pointer}
.dt-search-input{height:1.7rem;padding:0 .5rem;font-size:.7rem;border:1px solid #d6d3d1;border-radius:.375rem;background:#fafaf9;outline:none;width:150px;color:#292524}
.dt-search-input:focus{border-color:#7f1d1d}
.dataTables_info{font-size:.65rem;color:#a8a29e}
.dataTables_paginate{display:flex;gap:.15rem}
.dataTables_paginate .paginate_button{height:1.5rem;min-width:1.5rem;padding:0 .3rem;display:inline-flex;align-items:center;justify-content:center;border-radius:.3rem;font-size:.63rem;cursor:pointer;border:1px solid #e7e5e4;background:#fff;color:#292524;user-select:none}
.dataTables_paginate .paginate_button:hover:not(.disabled){background:#f5f5f4}
.dataTables_paginate .paginate_button.current{background:#7f1d1d;color:#fff;border-color:#7f1d1d}
.dataTables_paginate .paginate_button.disabled{opacity:.3;cursor:default}
#paTable_wrapper>.dataTables_length,#paTable_wrapper>.dataTables_filter,#paTable_wrapper>.dataTables_info,#paTable_wrapper>.dataTables_paginate{display:none !important}
.modal-backdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:300;backdrop-filter:blur(2px)}.modal-backdrop.open{display:block}
.modal-container{display:none;position:fixed;inset:0;z-index:301;padding:1rem;overflow-y:auto}.modal-container.open{display:flex;align-items:center;justify-content:center}
.detail-grid{display:grid;grid-template-columns:1fr;gap:0;height:100%}
@media(min-width:768px){.detail-grid{grid-template-columns:1.5fr 1fr}}
.vm-tabs-bar{display:flex;align-items:center;gap:0;padding:0 .75rem;background:#fafaf9;border-bottom:1px solid #e7e5e4;flex-shrink:0;flex-wrap:wrap;position:relative}
.vm-tab{padding:.4rem .65rem;font-size:.63rem;font-weight:600;color:#78716c;background:none;border:none;border-bottom:2px solid transparent;cursor:pointer}.vm-tab:hover{color:#292524}.vm-tab.active{color:#7f1d1d;border-bottom-color:#7f1d1d}
.vm-tab-files{display:none;position:absolute;top:100%;left:0;right:0;z-index:10;background:#fff;border-bottom:1px solid #e7e5e4;box-shadow:0 4px 12px rgba(0,0,0,.08);padding:.3rem .5rem;max-height:100px;overflow-y:auto}
.vm-tab-files.open{display:flex;flex-wrap:wrap;align-items:center;gap:.2rem}
.vm-tab-files .file-link{display:inline-flex;align-items:center;gap:.25rem;padding:.15rem .4rem;border-radius:.25rem;cursor:pointer;font-size:.6rem;color:#292524;border:1px solid #e7e5e4;background:#fafaf9}.vm-tab-files .file-link:hover{background:#f5f5f4}.vm-tab-files .file-link.active{background:#fef2f2;color:#7f1d1d;border-color:#7f1d1d}
.vm-viewer{flex:1;min-height:0;display:flex;flex-direction:column;background:#1c1917}
.vm-viewer-bar{padding:.35rem .65rem;background:rgba(0,0,0,.4);flex-shrink:0}
.vm-viewer-body{flex:1;position:relative;min-height:300px}
.vm-viewer-body iframe,.vm-viewer-body img{position:absolute;inset:0;width:100%;height:100%;border:none;object-fit:contain;background:#1c1917}
.info-row{display:flex;align-items:center;gap:.4rem;padding:.3rem 0;border-bottom:1px solid #f5f5f4}
.info-row:last-child{border-bottom:none}
.info-label{font-size:.58rem;font-weight:700;color:#78716c;text-transform:uppercase;letter-spacing:.03em;width:80px;flex-shrink:0}
.info-value{font-size:.7rem;color:#292524;word-break:break-word;flex:1}
.action-grid{display:grid;grid-template-columns:1fr;gap:.75rem}
</style>
@endpush

@section('content')
<div class="bg-white border border-stone-200 rounded-xl flex flex-col">
    {{-- Header --}}
    <div class="px-4 py-2.5 border-b border-stone-100 flex items-center justify-between gap-3 flex-shrink-0">
        <div>
            <h2 class="text-sm font-semibold text-stone-800">Punch Approval</h2>
            <p class="text-[11px] text-stone-400">Click a row to review and approve or reject</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="tabs">
        <button class="tab-btn active" data-tab="pending">Pending<span class="tab-badge" id="badge-pending">0</span></button>
        <button class="tab-btn" data-tab="approved">Approved<span class="tab-badge" id="badge-approved">0</span></button>
        <button class="tab-btn" data-tab="rejected">Rejected<span class="tab-badge" id="badge-rejected">0</span></button>
        <button class="tab-btn" data-tab="all">All<span class="tab-badge" id="badge-all">0</span></button>
    </div>

    {{-- Filters --}}
    <div class="filter-bar">
        <select id="filterCompany"  style="width:145px"></select>
        <select id="filterFY"       style="width:105px"></select>
        <select id="filterLocation" style="width:145px"></select>
        <select id="filterDocType"  style="width:145px"></select>
        <input type="date" id="filterFromDate" class="filter-input" style="width:115px" onfocus="if(this.showPicker)this.showPicker()">
        <input type="date" id="filterToDate"   class="filter-input" style="width:115px" onfocus="if(this.showPicker)this.showPicker()">
        <button id="btnApplyFilters"  class="filter-btn filter-btn-primary">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>Apply
        </button>
        <button id="btnResetFilters" class="filter-btn filter-btn-secondary">Reset</button>
    </div>

    {{-- DT controls top --}}
    <div class="dt-ctrl-bar border-b border-stone-100">
        <div class="flex items-center gap-2 text-xs text-stone-500"><span>Show</span><select class="dt-length-sel" id="dtLength"><option value="10" selected>10</option><option value="25">25</option><option value="50">50</option></select><span>entries</span></div>
        <div><input type="text" class="dt-search-input" id="dtSearch" placeholder="Search…"></div>
    </div>
    {{-- Table --}}
    <div class="overflow-x-auto flex-1">
        <table id="paTable" style="width:100%">
            <thead><tr>
                <th style="width:30px">#</th>
                <th>Company</th>
                <th>Location</th>
                <th>Document Type</th>
                <th>File</th>
                <th>Punch Date</th>
                <th>Punched By</th>
                <th>Scan Date</th>
                <th style="width:80px">Status</th>
            </tr></thead>
        </table>
    </div>

    {{-- DT controls bottom --}}
    <div class="dt-ctrl-bar border-t border-stone-100">
        <div id="dtInfo"></div>
        <div id="dtPaginate"></div>
    </div>
</div>

{{-- Detail Modal --}}
<div class="modal-backdrop" id="detailBackdrop"></div>
<div class="modal-container" id="detailModal">
    <div style="background:#fff;border-radius:1rem;box-shadow:0 20px 50px rgba(0,0,0,.25);width:100%;max-width:1150px;height:85vh;display:flex;flex-direction:column;overflow:hidden">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:.6rem 1rem;border-bottom:1px solid #e7e5e4;flex-shrink:0">
            <div>
                <h3 class="text-xs font-semibold text-stone-800" id="detailTitle">—</h3>
                <p class="text-[10px] text-stone-400" id="detailSub">—</p>
            </div>
            <button id="btnCloseDetail" class="w-6 h-6 flex items-center justify-center rounded text-stone-400 hover:bg-stone-100">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="detail-grid" style="flex:1;overflow:hidden">
            {{-- Left: file viewer --}}
            <div style="display:flex;flex-direction:column;border-right:1px solid #e7e5e4;overflow:hidden">
                <div class="vm-tabs-bar" id="detailTabsBar"></div>
                <div class="vm-viewer">
                    <div class="vm-viewer-bar"><span id="detailFileName" class="text-[9px] font-semibold text-stone-300">—</span></div>
                    <div class="vm-viewer-body" id="detailViewerBody"></div>
                </div>
            </div>
            {{-- Right: info + actions --}}
            <div style="display:flex;flex-direction:column;overflow-y:auto;padding:.75rem 1rem;gap:0">
                <div id="detailInfo" style="flex:1"></div>
                <div id="detailActions" style="flex-shrink:0;padding-top:.75rem;border-top:1px solid #e7e5e4;margin-top:.75rem"></div>
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
    data:       '{{ route("workflow.punch-approval.data") }}',
    tabCounts:  '{{ route("workflow.punch-approval.tab-counts") }}',
    companies:  '{{ route("workflow.punch-approval.companies") }}',
    fys:        '{{ route("workflow.punch-approval.financial-years") }}',
    locations:  '{{ route("workflow.punch-approval.locations") }}',
    docTypes:   '{{ route("workflow.punch-approval.doc-types") }}',
    detail:     id => `/workflow/punch-approval/${id}/detail`,
    approve:    id => `/workflow/punch-approval/${id}/approve`,
    reject:     id => `/workflow/punch-approval/${id}/reject`,
    supportList:id => `/workflow/punch-approval/${id}/support-list`,
};
let currentTab = 'pending';
let filters = { company_id:'', fy_id:'', location_id:'', doc_type_id:'', from_date:'', to_date:'' };

// Restore state from sessionStorage if available (for back navigation)
const savedState = sessionStorage.getItem('punchApprovalState');
if (savedState) {
    try {
        const state = JSON.parse(savedState);
        currentTab = state.currentTab || 'pending';
        filters = state.filters || filters;
        console.log('Restored state:', state);
    } catch(e) {
        console.error('Failed to restore state:', e);
    }
}

function esc(s){ return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;') }
function fmt(v){ if(!v) return '—'; const d=new Date(v); return d.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}) }

// ── Select2 filters ──────────────────────────────────────────────────────
function s2(sel,url,ph){ $(sel).select2({ placeholder:ph, allowClear:true, minimumInputLength:0, ajax:{ url, dataType:'json', delay:200, data:p=>({q:p.term||'',page:p.page||1}), processResults:d=>({results:d.results,pagination:d.pagination}) } }) }
s2('#filterCompany',  R.companies, 'All Companies');
s2('#filterFY',       R.fys,       'All FY');
s2('#filterLocation', R.locations, 'All Locations');
s2('#filterDocType',  R.docTypes,  'All Doc Types');

// ── DataTable ─────────────────────────────────────────────────────────────
const dt = $('#paTable').DataTable({
    serverSide:true, processing:true,
    ajax:{ 
        url:R.data, 
        type:'GET', 
        data:d=>{
            const params = Object.assign(d, filters, {tab:currentTab});
            // Add cache buster if refreshing
            if (sessionStorage.getItem('refreshPunchApproval') === 'true') {
                params._t = Date.now();
            }
            return params;
        }
    },
    order:[[5,'desc']], pageLength:10, dom:'rtp',
    columns:[
        { data:'DT_RowIndex', orderable:false, searchable:false, className:'text-center' },
        { data:'company_name',    defaultContent:'—' },
        { data:'location_name',   defaultContent:'—' },
        { data:'doc_type_label',  defaultContent:'—' },
        { data:'File',            defaultContent:'—', render:d=>d?`<span class="text-blue-600 text-xs">${esc(d)}</span>`:'—' },
        { data:'Punch_Date',  defaultContent:'—' },
        { data:'punched_by_name', defaultContent:'—' },
        { data:'scan_date',       defaultContent:'—' },
        { data:'status_badge',    orderable:false, searchable:false, className:'text-center' },
    ],
    language:{ emptyTable:'No records found', processing:'<span style="font-size:.7rem;color:#7f1d1d">Loading…</span>' },
    initComplete: function() {
        // Check if we need to refresh after approve/reject action
        const needsRefresh = sessionStorage.getItem('refreshPunchApproval') === 'true';
        console.log('initComplete - needsRefresh:', needsRefresh);
        
        if (needsRefresh) {
            sessionStorage.removeItem('refreshPunchApproval');
            sessionStorage.setItem('forceRefreshBadges', 'true');
            console.log('Refreshing punch approval list and counters...');
            
            // Force reload with cache buster
            const api = this.api();
            setTimeout(function() {
                console.log('Calling ajax.reload...');
                api.ajax.reload(function() {
                    console.log('Table reloaded successfully');
                    setTimeout(function() {
                        updateBadges();
                    }, 200);
                }, false);
            }, 500);
        }
    },
    drawCallback(){
        $('#paTable_wrapper .dataTables_paginate').first().appendTo('#dtPaginate');
        $('#paTable_wrapper .dataTables_info').first().appendTo('#dtInfo');
        updateBadges();
    },
    createdRow(row,data){ $(row).attr('data-scan-id', data.Scan_Id) },
});

// Restore UI state if we have saved state
if (savedState) {
    try {
        const state = JSON.parse(savedState);
        // Restore active tab
        $('.tab-btn').removeClass('active');
        $(`.tab-btn[data-tab="${state.currentTab}"]`).addClass('active');
        
        // Restore filter values (Select2 with AJAX requires creating options first)
        if (state.filters.company_id && state.filters.company_name) {
            const option = new Option(state.filters.company_name, state.filters.company_id, true, true);
            $('#filterCompany').append(option).trigger('change');
        }
        if (state.filters.fy_id && state.filters.fy_name) {
            const option = new Option(state.filters.fy_name, state.filters.fy_id, true, true);
            $('#filterFY').append(option).trigger('change');
        }
        if (state.filters.location_id && state.filters.location_name) {
            const option = new Option(state.filters.location_name, state.filters.location_id, true, true);
            $('#filterLocation').append(option).trigger('change');
        }
        if (state.filters.doc_type_id && state.filters.doc_type_name) {
            const option = new Option(state.filters.doc_type_name, state.filters.doc_type_id, true, true);
            $('#filterDocType').append(option).trigger('change');
        }
        
        // Restore date inputs
        if (state.filters.from_date) $('#filterFromDate').val(state.filters.from_date);
        if (state.filters.to_date) $('#filterToDate').val(state.filters.to_date);
        
        console.log('Restored UI state with filters');
    } catch(e) {
        console.error('Failed to restore UI state:', e);
    }
}

$('#paTable tbody').on('click','tr',function(){
    const id = $(this).data('scan-id'); 
    if(id) {
        // Save text/names for Select2 restoration
        filters.company_name = $('#filterCompany option:selected').text() || '';
        filters.fy_name = $('#filterFY option:selected').text() || '';
        filters.location_name = $('#filterLocation option:selected').text() || '';
        filters.doc_type_name = $('#filterDocType option:selected').text() || '';
        
        // Save current state before navigation
        sessionStorage.setItem('punchApprovalState', JSON.stringify({ currentTab, filters }));
        // Redirect to punch entry view page with approve/reject buttons
        window.location.href = `/workflow/punching/entry/${id}?view=1`;
    }
});
$('#dtLength').on('change',function(){ dt.page.len(+$(this).val()).draw() });
let st; $('#dtSearch').on('input',function(){ clearTimeout(st); const v=$(this).val(); st=setTimeout(()=>dt.search(v).draw(),300) });
$('.tab-btn').on('click',function(){
    currentTab=$(this).data('tab');
    $('.tab-btn').removeClass('active'); $(this).addClass('active');
    
    // Save text/names for Select2 restoration
    filters.company_name = $('#filterCompany option:selected').text() || '';
    filters.fy_name = $('#filterFY option:selected').text() || '';
    filters.location_name = $('#filterLocation option:selected').text() || '';
    filters.doc_type_name = $('#filterDocType option:selected').text() || '';
    
    // Save state to sessionStorage
    sessionStorage.setItem('punchApprovalState', JSON.stringify({ currentTab, filters }));
    dt.ajax.reload();
});
$('#btnApplyFilters').on('click',function(){
    filters.company_id  = $('#filterCompany').val()||'';
    filters.fy_id       = $('#filterFY').val()||'';
    filters.location_id = $('#filterLocation').val()||'';
    filters.doc_type_id = $('#filterDocType').val()||'';
    filters.from_date   = $('#filterFromDate').val();
    filters.to_date     = $('#filterToDate').val();
    
    // Save text/names for Select2 restoration
    filters.company_name = $('#filterCompany option:selected').text() || '';
    filters.fy_name = $('#filterFY option:selected').text() || '';
    filters.location_name = $('#filterLocation option:selected').text() || '';
    filters.doc_type_name = $('#filterDocType option:selected').text() || '';
    
    // Save state to sessionStorage
    sessionStorage.setItem('punchApprovalState', JSON.stringify({ currentTab, filters }));
    dt.ajax.reload();
});
$('#btnResetFilters').on('click',function(){
    $('#filterCompany,#filterFY,#filterLocation,#filterDocType').val(null).trigger('change');
    $('#filterFromDate,#filterToDate').val('');
    filters = { company_id:'', fy_id:'', location_id:'', doc_type_id:'', from_date:'', to_date:'' };
    dt.ajax.reload();
});
async function updateBadges(){
    try{
        // Add cache buster if refreshing
        const params = Object.assign({}, filters);
        if (sessionStorage.getItem('refreshPunchApproval') === 'true' || sessionStorage.getItem('forceRefreshBadges') === 'true') {
            params._t = Date.now();
            sessionStorage.removeItem('forceRefreshBadges');
        }
        console.log('updateBadges with params:', params);
        const c = await $.getJSON(R.tabCounts, params);
        console.log('Badge counts:', c);
        $('#badge-all').text(c.all||0); $('#badge-pending').text(c.pending||0);
        $('#badge-approved').text(c.approved||0); $('#badge-rejected').text(c.rejected||0);
    }catch(e){
        console.error('updateBadges error:', e);
    }
}

// ── Detail Modal ──────────────────────────────────────────────────────────
async function openDetail(scanId){
    try{
        const [detailRes, supportRes] = await Promise.all([
            $.getJSON(R.detail(scanId)),
            $.getJSON(R.supportList(scanId))
        ]);
        const d = detailRes.data; if(!d){ alert('Not found'); return }

        $('#detailTitle').text(`Scan #${d.Scan_Id} — ${d.doc_type_label||''}`);
        $('#detailSub').text(`${d.company_name||''} • ${d.fy_label||''} • ${d.location_name||''}`);

        // File tabs
        let tabs = `<button class="vm-tab active" data-url="${esc(d.File_Location)}" data-name="${esc(d.File)}">Main Scan</button>`;
        window.__dG = {};
        if(supportRes.data && supportRes.data.length){
            const g = {};
            supportRes.data.forEach(f=>{ const k=f.doc_type_name||'Other'; if(!g[k])g[k]=[]; g[k].push(f) });
            Object.keys(g).forEach(k=>{ tabs+=`<button class="vm-tab" data-tab="group" data-group="${esc(k)}">${esc(k)} (${g[k].length})</button>` });
            window.__dG = g;
        }
        $('#detailTabsBar').html(tabs + '<div class="vm-tab-files" id="dFP"></div>');
        dLoad(d.File_Location, d.File);

        // Info section
        let info = '';
        info += `<div class="info-row"><span class="info-label">Company</span><span class="info-value">${esc(d.company_name||'—')}</span></div>`;
        info += `<div class="info-row"><span class="info-label">Location</span><span class="info-value">${esc(d.location_name||'—')}</span></div>`;
        info += `<div class="info-row"><span class="info-label">Doc Type</span><span class="info-value">${esc(d.doc_type_label||'—')}</span></div>`;
        info += `<div class="info-row"><span class="info-label">File</span><span class="info-value text-blue-700">${esc(d.File||'—')}</span></div>`;
        if(d.vendor_name) info += `<div class="info-row"><span class="info-label">Vendor</span><span class="info-value">${esc(d.vendor_name)}</span></div>`;
        if(d.bill_no)     info += `<div class="info-row"><span class="info-label">Bill No</span><span class="info-value">${esc(d.bill_no)}</span></div>`;
        if(d.bill_date)   info += `<div class="info-row"><span class="info-label">Bill Date</span><span class="info-value">${fmt(d.bill_date)}</span></div>`;
        info += `<div class="info-row"><span class="info-label">Scan Date</span><span class="info-value">${fmt(d.scan_date)}</span></div>`;
        info += `<div class="info-row"><span class="info-label">Punch Date</span><span class="info-value">${fmt(d.Punch_Date)}</span></div>`;
        info += `<div class="info-row"><span class="info-label">Punched By</span><span class="info-value">${esc(d.punched_by_name||'—')}</span></div>`;
        if(d.Grand_Total) info += `<div class="info-row"><span class="info-label">Amount</span><span class="info-value font-semibold text-green-700">₹ ${esc(d.Grand_Total)}</span></div>`;
        if(d.punch_remark)info += `<div class="info-row"><span class="info-label">Remark</span><span class="info-value text-[11px]">${esc(d.punch_remark)}</span></div>`;

        // Status info
        if(d.File_Approved === 'Y')
            info += `<div class="info-row"><span class="info-label">Status</span><span class="info-value"><span class="badge-approved">Approved</span></span></div><div class="info-row"><span class="info-label">Approved On</span><span class="info-value">${fmt(d.Approve_Date)}</span></div><div class="info-row"><span class="info-label">By</span><span class="info-value">${esc(d.approved_by_name||'—')}</span></div>`;
        else if(d.Is_Rejected === 'Y')
            info += `<div class="info-row"><span class="info-label">Status</span><span class="info-value"><span class="badge-rejected">Rejected</span></span></div><div class="info-row"><span class="info-label">Rejected On</span><span class="info-value">${fmt(d.Reject_Date)}</span></div><div class="info-row"><span class="info-label">Remark</span><span class="info-value text-red-700 text-[11px]">${esc(d.Reject_Remark||'—')}</span></div>`;
        else
            info += `<div class="info-row"><span class="info-label">Status</span><span class="info-value"><span class="badge-pending">Pending</span></span></div>`;

        $('#detailInfo').html(info);

        // Actions section
        let act = '';
        if(d.File_Approved !== 'Y' && d.Is_Rejected !== 'Y'){
            act = `
            <div class="action-grid">
                <div>
                    <label class="text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-1 block">Approve</label>
                    <input type="text" id="approveRemark" placeholder="Remark (optional)…"
                           class="w-full h-7 px-2 text-[11px] border border-stone-200 rounded-md bg-white outline-none focus:border-green-400 mb-2">
                    <label class="flex items-center gap-1.5 text-[11px] text-stone-600 mb-2 cursor-pointer">
                        <input type="checkbox" id="approveEditPerm" class="rounded">
                        Grant Edit Permission after approval
                    </label>
                    <button id="btnApprove" data-id="${d.Scan_Id}"
                            class="w-full h-8 bg-green-600 hover:bg-green-700 text-white text-[11px] font-semibold rounded-lg flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Approve & Sync
                    </button>
                </div>
                <div>
                    <label class="text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-1 block">Reject</label>
                    <textarea id="rejectRemark" rows="3" placeholder="Rejection reason (required)…"
                              class="w-full px-2 py-1.5 text-[11px] border border-stone-200 rounded-md bg-white outline-none focus:border-red-400 resize-none mb-2"></textarea>
                    <label class="flex items-center gap-1.5 text-[11px] text-stone-600 mb-2 cursor-pointer">
                        <input type="checkbox" id="rejectEditPerm" class="rounded">
                        Grant Edit Permission after rejection
                    </label>
                    <button id="btnReject" data-id="${d.Scan_Id}"
                            class="w-full h-8 bg-red-700 hover:bg-red-800 text-white text-[11px] font-semibold rounded-lg flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Reject
                    </button>
                </div>
            </div>`;
        } else {
            act = `<p class="text-[11px] text-stone-400 text-center py-2">
                This entry has already been ${d.finance_punch_action_status==='Y'?'approved':'rejected'}.
            </p>`;
        }
        $('#detailActions').html(act);
        $('#detailModal,#detailBackdrop').addClass('open');

    }catch(e){ console.error(e); alert('Failed to load detail') }
}

$('#btnCloseDetail, #detailBackdrop').on('click',function(){
    $('#detailModal,#detailBackdrop').removeClass('open');
    $('#detailTabsBar').empty();
    $('#detailViewerBody').find('iframe,img').remove();
    $('#detailInfo,#detailActions').empty();
});

// Approve
$(document).on('click','#btnApprove',async function(){
    const id = $(this).data('id');
    if(!confirm('Approve this punched entry and sync to accounting?')) return;
    const $btn = $(this); $btn.prop('disabled',true).text('Processing…');
    try{
        await $.ajax({ url:R.approve(id), method:'POST', headers:{'X-CSRF-TOKEN':CSRF},
            data:{ remark:$('#approveRemark').val(), edit_permission:$('#approveEditPerm').is(':checked')?1:0 } });
        $('#detailModal,#detailBackdrop').removeClass('open');
        dt.ajax.reload(null,false);
    }catch(e){ alert(e.responseJSON?.message||'Approval failed'); $btn.prop('disabled',false).text('Approve & Sync') }
});

// Reject
$(document).on('click','#btnReject',async function(){
    const id = $(this).data('id');
    const remark = $('#rejectRemark').val().trim();
    if(!remark){ alert('Rejection reason is required'); return }
    const $btn = $(this); $btn.prop('disabled',true).text('Processing…');
    try{
        await $.ajax({ url:R.reject(id), method:'POST', headers:{'X-CSRF-TOKEN':CSRF},
            data:{ remark, edit_permission:$('#rejectEditPerm').is(':checked')?1:0 } });
        $('#detailModal,#detailBackdrop').removeClass('open');
        dt.ajax.reload(null,false);
    }catch(e){ alert(e.responseJSON?.message||'Rejection failed'); $btn.prop('disabled',false).text('Reject') }
});

// File viewer
$(document).on('click','#detailTabsBar .vm-tab:not([data-tab="group"])',function(){
    $('#detailTabsBar .vm-tab').removeClass('active'); $(this).addClass('active');
    $('#dFP').removeClass('open').empty();
    dLoad($(this).data('url'), $(this).data('name'));
});
$(document).on('click','#detailTabsBar .vm-tab[data-tab="group"]',function(){
    const g=$(this).data('group'); const f=window.__dG[g]||[];
    $('#detailTabsBar .vm-tab').removeClass('active'); $(this).addClass('active');
    let h=''; f.forEach(x=>{ h+=`<div class="file-link" data-url="${esc(x.File_Location)}" data-name="${esc(x.File)}"><span>${esc(x.File)}</span></div>` });
    $('#dFP').html(h).addClass('open');
});
$(document).on('click','#dFP .file-link',function(){
    $('#dFP .file-link').removeClass('active'); $(this).addClass('active');
    dLoad($(this).data('url'), $(this).data('name'));
});
function dLoad(url, name){
    const $b=$('#detailViewerBody'); $b.find('iframe,img').remove();
    $('#detailFileName').text(name||'');
    if(!url) return;
    (url.toLowerCase().includes('.pdf') ? $b.append(`<iframe src="${url}"></iframe>`) : $b.append(`<img src="${url}" alt="${esc(name)}">`));
}

});
</script>
@endpush

<script>
// Handle browser back/forward cache (bfcache) - checks flag when page is shown  
window.addEventListener('pageshow', function(event) {
    console.log('pageshow event - persisted:', event.persisted, 'refreshFlag:', sessionStorage.getItem('refreshPunchApproval'));
    
    // If page was restored from bfcache OR normal load, check if we need to refresh
    const needsRefresh = sessionStorage.getItem('refreshPunchApproval') === 'true';
    
    if (needsRefresh) {
        sessionStorage.removeItem('refreshPunchApproval');
        // KEEP punchApprovalState so filters are preserved
        sessionStorage.setItem('forceRefreshBadges', 'true');
        console.log('Forcing page refresh with filters preserved...');
        
        // Force full page reload to ensure everything updates
        setTimeout(function() {
            location.reload();
        }, 100);
    }
});
</script>
