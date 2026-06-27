@extends('layouts.app')
@section('title', ($isViewMode ?? false) ? 'View Entry - Scan #' . $scanData->Scan_Id : 'Invoice Entry - Scan #' . $scanData->Scan_Id)
@section('page-title', ($isViewMode ?? false) ? 'View Punched Entry' : 'Invoice Entry')

@php
    $viewMode = $isViewMode ?? false;
@endphp

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
@media(min-width:1024px){.entry-grid{grid-template-columns:4fr 8fr}}
.file-panel{display:flex;flex-direction:column;border-right:1px solid #e7e5e4;overflow:hidden;background:#292524}
.file-tabs{display:flex;gap:0;padding:0 .5rem;background:#fafaf9;border-bottom:1px solid #e7e5e4;flex-wrap:wrap}
.file-tab{padding:.35rem .6rem;font-size:.6rem;font-weight:600;color:#78716c;border:none;background:none;border-bottom:2px solid transparent;cursor:pointer}
.file-tab.active{color:#7f1d1d;border-bottom-color:#7f1d1d}
.file-viewer{flex:1;position:relative;min-height:300px}
.file-viewer iframe,.file-viewer img{position:absolute;inset:0;width:100%;height:100%;border:none;object-fit:contain;background:#292524}
</style>
<style>
.form-panel{display:flex;flex-direction:column;overflow-y:auto;padding:.75rem 1rem;background:#fff}
.f-row{display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem;margin-bottom:.6rem}
.f-row.cols-1{grid-template-columns:1fr}
.f-row.cols-2{grid-template-columns:repeat(2,1fr)}
.f-row.cols-3{grid-template-columns:repeat(3,1fr)}
.f-group{min-width:0}
.f-group label{font-size:.6rem;font-weight:600;color:#78716c;text-transform:uppercase;display:block;margin-bottom:2px}
.f-group .hint{font-size:.55rem;color:#dc2626;display:block;margin-bottom:1px}
.f-input{height:28px;width:100%;padding:0 .5rem;font-size:.72rem;border:1px solid #d6d3d1;border-radius:.375rem;background:#fff;outline:none;color:#292524}
.f-input:focus{border-color:#7f1d1d;box-shadow:0 0 0 2px rgba(127,29,29,.06)}
textarea.f-input{height:60px;resize:vertical;padding:.4rem .5rem}
.items-table{width:100%;border-collapse:collapse;font-size:.7rem}
.items-table th{background:#7f1d1d;color:#fff;font-size:.58rem;font-weight:600;text-transform:uppercase;padding:.4rem .4rem;text-align:center;white-space:nowrap;position:sticky;top:0;z-index:2}
.items-table td{padding: 5px 5px !important;border-bottom:1px solid #e7e5e4;text-align:center;vertical-align:middle}
.items-table tbody tr:nth-child(even){background:#fafaf9}
.items-table tbody tr:hover{background:#fef2f2} 
.items-table input:focus,.items-table select:focus{border-color:#7f1d1d;outline:none;box-shadow:0 0 0 2px rgba(127,29,29,.08)}
.items-table input[readonly]{background:#f5f5f4;color:#57534e}
.btn-add-row{background:#7f1d1d;color:#fff;border:none;border-radius:.25rem;width:20px;height:20px;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center}
.btn-del-row{background:#292524;color:#fff;border:none;border-radius:.25rem;width:20px;height:20px;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center}
.btn-add-emp{background:#7f1d1d;color:#fff;border:none;border-radius:.25rem;width:20px;height:20px;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center}
.btn-del-emp{background:#292524;color:#fff;border:none;border-radius:.25rem;width:20px;height:20px;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center}
.btn-add-meals-emp{background:#7f1d1d;color:#fff;border:none;border-radius:.25rem;width:20px;height:20px;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center}
.btn-del-meals-emp{background:#292524;color:#fff;border:none;border-radius:.25rem;width:20px;height:20px;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center}
.btn-add-labour{background:#7f1d1d;color:#fff;border:none;border-radius:.25rem;width:20px;height:20px;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center}
.btn-del-labour{background:#292524;color:#fff;border:none;border-radius:.25rem;width:20px;height:20px;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center}
.form-footer{display:flex;align-items:center;gap:.5rem;padding:.75rem 1rem;border-top:1px solid #e7e5e4;background:#fafaf9;flex-shrink:0}
.form-footer .btn-cancel{margin-right:auto}
.btn-draft{height:34px;padding:0 1.25rem;font-size:.72rem;font-weight:600;border:none;border-radius:.5rem;background:#7f1d1d;color:#fff;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center}
.btn-draft:hover{background:#6b1a1a}
.btn-submit{height:34px;padding:0 1.25rem;font-size:.72rem;font-weight:600;border:none;border-radius:.5rem;background:#16a34a;color:#fff;cursor:pointer}
.btn-submit:hover{background:#15803d}
.btn-approve{height:34px;padding:0 1.25rem;font-size:.72rem;font-weight:600;border:none;border-radius:.5rem;background:#7f1d1d;color:#fff;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem}
.btn-approve:hover{background:#6b1a1a}
.btn-reject{height:34px;padding:0 1.25rem;font-size:.72rem;font-weight:600;border:none;border-radius:.5rem;background:#dc2626;color:#fff;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem}
.btn-reject:hover{background:#b91c1c}
.btn-back{height:34px;padding:0 1rem;font-size:.72rem;font-weight:600;border:1px solid #d6d3d1;border-radius:.5rem;background:#fff;color:#57534e;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem}
#alertBox{display:none;padding:.5rem .75rem;border-radius:.5rem;font-size:.7rem;margin-bottom:.5rem}
#alertBox.error{display:block;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c}
#alertBox.success{display:block;background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d}
.spinner{display:inline-block;width:12px;height:12px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite;vertical-align:middle}
@keyframes spin{to{transform:rotate(360deg)}}
.btn-draft:disabled,.btn-submit:disabled{cursor:not-allowed;pointer-events:none}
/* View Mode Styles */
</style>
@endpush
@if($viewMode)
<style>
.f-input, .select2-selection, textarea.f-input { pointer-events: none !important; opacity: 0.8 !important; background: #f5f5f4 !important; }
.items-table input, .items-table select { pointer-events: none !important; opacity: 0.8 !important; background: #f5f5f4 !important; }
.btn-add-row, .btn-del-row, .btn-add-emp, .btn-del-emp, .btn-add-meals-emp, .btn-del-meals-emp, .btn-add-labour, .btn-del-labour { display: none !important; }
.round-opt { pointer-events: none !important; }
</style>
@endif
@section('content')
<div class="entry-grid">
    {{-- Left: File Viewer Panel --}}
    <div class="file-panel">
        <div class="file-tabs">
            <button class="file-tab active" data-url="{{ $scanData->File_Location }}" data-name="{{ $scanData->File }}">Main Scan</button>
            @foreach($supportFiles as $sf)
                <button class="file-tab" data-url="{{ $sf->File_Location }}" data-name="{{ $sf->File }}">{{ $sf->doc_type_name ?: 'Support' }}</button>
            @endforeach
        </div>
        <div class="file-viewer" id="fileViewer">
            @if(strtolower($scanData->File_Ext) === 'pdf')
                <iframe src="{{ $scanData->File_Location }}"></iframe>
            @else
                <img src="{{ $scanData->File_Location }}" alt="{{ $scanData->File }}">
            @endif
        </div>
    </div>

    {{-- Right: Form Panel --}}
    <div style="display:flex;flex-direction:column;overflow:hidden">
        <div class="form-panel" id="formPanel">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem">
                <div>
                    <h2 style="font-size:.85rem;font-weight:700;color:#292524">
                        {{ $viewMode ? 'View Punched Entry' : 'Invoice Entry' }} — Scan #{{ $scanData->Scan_Id }}
                    </h2>
                    <p style="font-size:.6rem;color:#78716c;margin-bottom:.25rem">{{ $scanData->company_name }} • {{ $scanData->doc_type_label }}</p>
                    @if($viewMode && $scanData->File_Punched === 'Y')
                    <div style="display:flex;align-items:center;gap:.5rem;margin-top:.35rem">
                        @if($scanData->Is_Rejected === 'Y')
                        <span style="display:inline-flex;align-items:center;gap:.25rem;padding:.15rem .5rem;background:#fee2e2;color:#991b1b;border-radius:.375rem;font-size:.6rem;font-weight:600">
                            <svg style="width:10px;height:10px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Rejected
                        </span>
                        <span style="font-size:.58rem;color:#78716c">
                            by {{ $scanData->approved_by_name ?? 'N/A' }} on {{ $scanData->Reject_Date ? \Carbon\Carbon::parse($scanData->Reject_Date)->format('d M Y') : 'N/A' }}
                        </span>
                        @if($scanData->Reject_Remark)
                        <span style="font-size:.58rem;color:#dc2626;font-weight:500">• {{ $scanData->Reject_Remark }}</span>
                        @endif
                        @elseif($scanData->File_Approved === 'Y')
                        <span style="display:inline-flex;align-items:center;gap:.25rem;padding:.15rem .5rem;background:#dcfce7;color:#166534;border-radius:.375rem;font-size:.6rem;font-weight:600">
                            <svg style="width:10px;height:10px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Approved
                        </span>
                        <span style="font-size:.58rem;color:#78716c">
                            by {{ $scanData->approved_by_name ?? 'N/A' }} on {{ $scanData->Approve_Date ? \Carbon\Carbon::parse($scanData->Approve_Date)->format('d M Y') : 'N/A' }}
                        </span>
                        @else
                        <span style="display:inline-flex;align-items:center;gap:.25rem;padding:.15rem .5rem;background:#fef3c7;color:#92400e;border-radius:.375rem;font-size:.6rem;font-weight:600">
                            <svg style="width:10px;height:10px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Pending Approval
                        </span>
                        <span style="font-size:.58rem;color:#78716c">
                            Punched by {{ $scanData->punched_by_name ?? 'N/A' }} on {{ $scanData->Punch_Date ? \Carbon\Carbon::parse($scanData->Punch_Date)->format('d M Y') : 'N/A' }}
                        </span>
                        @endif
                    </div>
                    @endif
                </div>
                <div style="display:flex;align-items:center;gap:.4rem">
                    @if(!$viewMode)
                    <button type="button" id="btnHistory" class="btn-back" style="background:#fafaf9">
                        <svg style="width:12px;height:12px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>History
                    </button>
                    @endif
                    <button type="button" onclick="window.history.back()" class="btn-back" style="text-decoration:none">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Back
                    </button>
                </div>
            </div>

            <div id="alertBox"></div>

            <form id="entryForm" novalidate>
                @csrf
                <input type="hidden" name="Scan_Id" value="{{ $scanData->Scan_Id }}">

                @include('panel.workflow.punching.forms.' . $formPartial, [
                    'scanData' => $scanData,
                    'punchDetail' => $punchDetail,
                    'tempData' => $tempData,
                    'kmRows' => $kmRows ?? collect(),
                ])
            </form>
        </div>

        {{-- Footer Buttons --}}
        @if(!$viewMode)
        <div class="form-footer">
            <button type="button" onclick="window.history.back()" class="btn-cancel btn-back" style="text-decoration:none">Cancel</button>
            <button type="button" id="btnDraft" class="btn-draft">Save Draft</button>
            <button type="button" id="btnSubmit" class="btn-submit">Final Submit</button>
        </div>
        @else
        <div class="form-footer">
            <button type="button" onclick="window.history.back()" class="btn-back" style="margin-right:auto;text-decoration:none">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:12px;height:12px"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to List
            </button>
            @if($canApprove)
            <button type="button" id="btnReject" class="btn-reject" style="height:34px;padding:0 1.25rem;font-size:.72rem;font-weight:600;border:none;border-radius:.5rem;background:#dc2626;color:#fff;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem">
                <svg style="width:14px;height:14px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                Reject
            </button>
            <button type="button" id="btnApprove" class="btn-approve" style="height:34px;padding:0 1.25rem;font-size:.72rem;font-weight:600;border:none;border-radius:.5rem;background:#7f1d1d;color:#fff;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem">
                <svg style="width:14px;height:14px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Approve
            </button>
            @endif
        </div>
        @endif
    </div>
</div>

{{-- History Offcanvas --}}
<div id="historyOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.3);z-index:9998" onclick="closeHistory()"></div>

{{-- Approval/Rejection Modal --}}
<div id="approvalOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:9998;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:.75rem;box-shadow:0 20px 60px rgba(0,0,0,.15);max-width:480px;width:90%;padding:1.5rem">
        <h3 id="approvalModalTitle" style="font-size:.9rem;font-weight:700;color:#292524;margin:0 0 1rem">Approval Action</h3>
        <div id="approvalAlertBox" style="display:none;padding:.5rem .75rem;border-radius:.5rem;font-size:.7rem;margin-bottom:.75rem"></div>
        <div style="margin-bottom:1rem">
            <label style="display:block;font-size:.7rem;font-weight:600;color:#44403c;margin-bottom:.35rem">Remark <span id="remarkRequired" style="color:#dc2626">*</span></label>
            <textarea id="approvalRemark" rows="3" style="width:100%;padding:.5rem;font-size:.7rem;border:1px solid #d6d3d1;border-radius:.5rem;resize:vertical" placeholder="Enter remark..."></textarea>
        </div>
        <div id="editPermissionSection" style="margin-bottom:1rem;display:none">
            <label style="display:flex;align-items:center;gap:.5rem;font-size:.7rem;color:#44403c;cursor:pointer">
                <input type="checkbox" id="editPermissionCheck" style="cursor:pointer">
                <span>Allow edit permission to puncher</span>
            </label>
        </div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end">
            <button type="button" id="approvalModalCancel" style="padding:.5rem 1.2rem;font-size:.7rem;font-weight:600;border:1px solid #d6d3d1;border-radius:.5rem;background:#fff;color:#57534e;cursor:pointer">Cancel</button>
            <button type="button" id="approvalModalConfirm" style="padding:.5rem 1.2rem;font-size:.7rem;font-weight:600;border:none;border-radius:.5rem;background:#7f1d1d;color:#fff;cursor:pointer">Confirm</button>
        </div>
    </div>
</div>

{{-- Confirm Submit Modal --}}
<div id="confirmOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:9998;display:none;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:.75rem;box-shadow:0 20px 60px rgba(0,0,0,.15);max-width:360px;width:90%;padding:1.5rem;text-align:center">
        <div style="width:48px;height:48px;border-radius:50%;background:#fef2f2;display:flex;align-items:center;justify-content:center;margin:0 auto .75rem">
            <svg style="width:24px;height:24px;color:#dc2626" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.999L13.732 4.001c-.77-1.333-2.694-1.333-3.464 0L3.34 16.001C2.57 17.334 3.532 19 5.072 19z"/></svg>
        </div>
        <h3 style="font-size:.85rem;font-weight:700;color:#292524;margin:0 0 .4rem">Confirm Final Submit</h3>
        <p style="font-size:.7rem;color:#78716c;margin:0 0 1.25rem;line-height:1.4">Are you sure you want to submit this entry? Once submitted, it cannot be edited without approval.</p>
        <div style="display:flex;gap:.5rem;justify-content:center">
            <button type="button" id="confirmCancel" style="padding:.4rem 1rem;font-size:.7rem;font-weight:600;border:1px solid #d6d3d1;border-radius:.5rem;background:#fff;color:#57534e;cursor:pointer">Cancel</button>
            <button type="button" id="confirmSubmit" style="padding:.4rem 1rem;font-size:.7rem;font-weight:600;border:none;border-radius:.5rem;background:#7f1d1d;color:#fff;cursor:pointer">Yes, Submit</button>
        </div>
    </div>
</div>
<div id="historyPanel" style="position:fixed;top:0;right:-380px;width:380px;height:100%;background:#fff;z-index:9999;box-shadow:-4px 0 20px rgba(0,0,0,.1);transition:right .25s ease;display:flex;flex-direction:column">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:.75rem 1rem;border-bottom:1px solid #e7e5e4">
        <h3 style="font-size:.8rem;font-weight:700;color:#292524;margin:0">Scan History — #{{ $scanData->Scan_Id }}</h3>
        <button type="button" onclick="closeHistory()" style="background:none;border:none;font-size:1.1rem;cursor:pointer;color:#78716c">&times;</button>
    </div>
    <div id="historyContent" style="flex:1;overflow-y:auto;padding:1rem">
        <div style="text-align:center;color:#a8a29e;font-size:.7rem;padding:2rem 0">Loading...</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function(){
    const CSRF = $('meta[name="csrf-token"]').attr('content');
    const SCAN_ID = {{ $scanData->Scan_Id }};
    const R = {
        items: `/workflow/punching/entry/${SCAN_ID}/items`,
        save: `/workflow/punching/entry/${SCAN_ID}/save`,
        selItems: '{{ route("workflow.punching.entry.select.items") }}',
        selItemsCreate: '{{ route("workflow.punching.entry.select.items.create") }}',
        selUnits: '{{ route("workflow.punching.entry.select.units") }}',
        selBuyers: '{{ route("workflow.punching.entry.select.buyers") }}',
        selVendors: '{{ route("workflow.punching.entry.select.vendors") }}',
        selDepts: '{{ route("workflow.punching.entry.select.departments") }}',
        selCategories: '{{ route("workflow.punching.entry.select.categories") }}',
        selLedgers: '{{ route("workflow.punching.entry.select.ledgers") }}',
        selLocations: '{{ route("workflow.punching.entry.select.locations") }}',
        selFiles: '{{ route("workflow.punching.entry.select.files") }}',
        selEmployees: '{{ route("workflow.punching.entry.select.employees") }}',
        selLastReading: '{{ route("workflow.punching.entry.select.lastReading") }}',
        selHotels: '{{ route("workflow.punching.entry.select.hotels") }}',
        selAgents: '{{ route("workflow.punching.entry.select.agents") }}',
        selAirlines: '{{ route("workflow.punching.entry.select.airlines") }}'
    };

    // ========== File Tabs ==========
    $('.file-tab').on('click', function(){
        $('.file-tab').removeClass('active');
        $(this).addClass('active');
        const url = $(this).data('url');
        const $v = $('#fileViewer');
        $v.find('iframe, img').remove();
        if(url.toLowerCase().endsWith('.pdf')){
            $v.append(`<iframe src="${url}"></iframe>`);
        } else {
            $v.append(`<img src="${url}">`);
        }
    });

    // ========== Select2 Inits ==========
    function s2(sel, url, placeholder){
        $(sel).select2({
            placeholder: placeholder || 'Select…',
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: url,
                dataType: 'json',
                delay: 200,
                data: function(p){ return { q: p.term || '', page: p.page || 1 }; },
                processResults: function(d){ return { results: d.results, pagination: d.pagination }; }
            }
        });
    }
    s2('#selBuyer', R.selBuyers, 'Search Buyer');
    s2('#selVendor', R.selVendors, 'Search Vendor');
    s2('#selDept', R.selDepts, 'Select Department');
    s2('#selCategory', R.selCategories, 'Select Category');
    s2('#selLedger', R.selLedgers, 'Select Ledger');
    s2('#selLocation', R.selLocations, 'Select Location');
    s2('#selFile', R.selFiles, 'Select File');

    // ========== Employee Select2 (from master_employee) ==========
    if ($('#selEmployee').length || $('#selHiredEmployee').length || $('#selLocalEmployee').length || $('#selMealsEmployee').length) {
        var empSel = $('#selEmployee').length ? '#selEmployee' : ($('#selHiredEmployee').length ? '#selHiredEmployee' : ($('#selLocalEmployee').length ? '#selLocalEmployee' : '#selMealsEmployee'));
        $(empSel).select2({
            placeholder: 'Search Employee / Payee',
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: R.selEmployees,
                dataType: 'json',
                delay: 200,
                data: function(p){ return { q: p.term || '', page: p.page || 1 }; },
                processResults: function(d){ return { results: d.results, pagination: d.pagination }; }
            },
            templateResult: function(item){
                if(item.loading) return item.text;
                return $('<span>').text(item.text + (item.emp_code ? ' [' + item.emp_code + ']' : ''));
            }
        });
        // Auto-fill Emp Code on employee select
        $(empSel).on('select2:select', function(e){
            $('input[name="Emp_Code"]').val(e.params.data.emp_code || '');
        });
        $(empSel).on('select2:clear', function(){
            $('input[name="Emp_Code"]').val('');
        });
    }

    // Address auto-fill on buyer/vendor select
    $('#selBuyer').on('select2:select', function(e){
        $('input[name="Buyer_Address"]').val(e.params.data.address || '');
        $('input[name="Billing_Address"]').val(e.params.data.address || '');
    });
    $('#selVendor').on('select2:select', function(e){
        $('input[name="Vendor_Address"]').val(e.params.data.address || '');
        $('input[name="Agency_Address"]').val(e.params.data.address || '');
    });

    // ========== Units as Select2 (server-side) ==========
    function initUnitSelect2(sel, selectedVal, selectedText) {
        $(sel).select2({
            placeholder: 'Unit',
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: R.selUnits,
                dataType: 'json',
                delay: 200,
                data: function(p){ return { q: p.term || '', page: p.page || 1 }; },
                processResults: function(d){ return { results: d.results, pagination: d.pagination }; }
            }
        });
        if (selectedVal && selectedText) {
            $(sel).append(new Option(selectedText, selectedVal, true, true)).trigger('change');
        }
    }
    initUnitSelect2('.unit-sel');

    // ========== Item Select2 with Tags ==========
    function initParticular(sel){
        $(sel).select2({
            placeholder: 'Search Item',
            allowClear: true,
            tags: true,
            minimumInputLength: 0,
            ajax: {
                url: R.selItems,
                dataType: 'json',
                delay: 200,
                data: function(p){ return { q: p.term || '', page: p.page || 1 }; },
                processResults: function(d){ return { results: d.results, pagination: d.pagination }; }
            },
            createTag: function(params){
                var term = $.trim(params.term);
                if(term === '') return null;
                return { id: term, text: term, newTag: true };
            }
        }).on('select2:select', function(e){
            var data = e.params.data;
            if(data.newTag){
                var $s = $(this);
                $.ajax({
                    url: R.selItemsCreate,
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': CSRF},
                    data: { item_name: data.text }
                }).done(function(r){
                    if(r.success){
                        $s.find('option[value="'+data.id+'"]').val(r.item.id);
                    }
                });
            }
        });
    }
    initParticular('.particular-sel');

    // ========== Numeric validation — only allow numbers and decimal ==========
    $(document).on('keypress', '.calc-trigger', function(e) {
        var charCode = e.which || e.keyCode;
        if (charCode === 46 && $(this).val().indexOf('.') !== -1) return false; // only one dot
        if (charCode !== 46 && charCode > 31 && (charCode < 48 || charCode > 57)) return false;
        return true;
    });
    $(document).on('paste', '.calc-trigger', function(e) {
        var paste = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
        if (!/^\d*\.?\d*$/.test(paste)) e.preventDefault();
    });

    // ========== Add / Remove Rows ==========
    let rowCount = 1;

    function addRow(){
        rowCount++;
        const tr = `<tr>
            <td>${rowCount}</td>
            <td><select name="Particular[]" class="particular-sel" style="width:100%"><option value="">Select</option></select></td>
            <td><input type="text" name="HSN[]"></td>
            <td><input type="text" name="Qty[]" class="calc-trigger" inputmode="decimal"></td>
            <td><select name="Unit[]" class="unit-sel"></select></td>
            <td><input type="text" name="MRP[]" class="calc-trigger" inputmode="decimal"></td>
            <td><input type="text" name="Discount[]" class="calc-trigger" inputmode="decimal"></td>
            <td><input type="text" name="Price[]" readonly></td>
            <td><input type="text" name="Amount[]" class="amt-field" readonly></td>
            <td><input type="text" name="GST[]" class="calc-trigger" inputmode="decimal"></td>
            <td><input type="text" name="SGST[]" readonly></td>
            <td><input type="text" name="IGST[]" class="calc-trigger" inputmode="decimal"></td>
            <td><input type="text" name="Cess[]" class="calc-trigger" inputmode="decimal"></td>
            <td><input type="text" name="TAmount[]" class="total-field" readonly></td>
            <td><button type="button" class="btn-del-row">−</button></td>
        </tr>`;
        $('#itemsBody').append(tr);
        initParticular('#itemsBody tr:last .particular-sel');
        initUnitSelect2('#itemsBody tr:last .unit-sel');
    }

    $(document).on('click', '.btn-add-row', addRow);
    $(document).on('click', '.btn-del-row', function(){
        $(this).closest('tr').remove();
        reindex();
        calcTotals();
    });

    function reindex(){
        $('#itemsBody tr').each(function(i){
            $(this).find('td:first').text(i + 1);
        });
        rowCount = $('#itemsBody tr').length;
    }

    // ========== Row Calculations ==========
    $(document).on('input', '.calc-trigger', function(){
        const $tr = $(this).closest('tr');
        calcRow($tr);
        calcTotals();
    });

    function calcRow($tr){
        const qty = parseFloat($tr.find('input[name="Qty[]"]').val()) || 0;
        const mrp = parseFloat($tr.find('input[name="MRP[]"]').val()) || 0;
        const discount = parseFloat($tr.find('input[name="Discount[]"]').val()) || 0;
        const gst = parseFloat($tr.find('input[name="GST[]"]').val()) || 0;
        const igst = parseFloat($tr.find('input[name="IGST[]"]').val()) || 0;
        const cess = parseFloat($tr.find('input[name="Cess[]"]').val()) || 0;

        // Price = MRP - Discount
        const price = mrp - discount;
        $tr.find('input[name="Price[]"]').val(price.toFixed(2));

        // Amount = Qty * Price
        const amount = qty * price;
        $tr.find('input[name="Amount[]"]').val(amount.toFixed(2));

        // SGST = GST / 2
        const sgst = gst / 2;
        $tr.find('input[name="SGST[]"]').val(sgst.toFixed(2));

        // Total = Amount + GST amount + IGST amount + Cess amount
        const gstAmt = (amount * gst) / 100;
        const igstAmt = (amount * igst) / 100;
        const cessAmt = (amount * cess) / 100;
        const total = amount + gstAmt + igstAmt + cessAmt;
        $tr.find('input[name="TAmount[]"]').val(total.toFixed(2));
    }

    function calcTotals(){
        let subTotal = 0;
        $('#itemsBody tr').each(function(){
            subTotal += parseFloat($(this).find('input[name="TAmount[]"]').val()) || 0;
        });
        $('#subTotal').val(subTotal.toFixed(2));

        const tcs = parseFloat($('#tcsField').val()) || 0;
        const total = subTotal + (subTotal * tcs / 100);
        $('#totalField').val(total.toFixed(2));

        // Apply Round Off (for invoice form)
        if ($('input[name="Round_Off_Type"]').length && !$('input[name="Dist_Opening[]"]').length) {
            const roundType = $('input[name="Round_Off_Type"]:checked').val() || 'none';
            let grandTotal = total;
            let roundOff = 0;

            if (roundType === 'upper' && total > 0) {
                grandTotal = Math.ceil(total);
                roundOff = grandTotal - total;
            } else if (roundType === 'lower' && total > 0) {
                grandTotal = Math.floor(total);
                roundOff = total - grandTotal;
            }

            $('#roundOffField').val(roundOff !== 0 ? roundOff.toFixed(2) : '');
            $('#grandTotal').val(grandTotal > 0 ? grandTotal.toFixed(2) : '0.00');
        } else if (!$('input[name="Dist_Opening[]"]').length) {
            $('#grandTotal').val(total.toFixed(2));
        }
    }

    $('#tcsField').on('input', calcTotals);

    // Invoice Round Off type change
    if ($('input[name="Round_Off_Type"]').length && !$('input[name="Dist_Opening[]"]').length) {
        $('input[name="Round_Off_Type"]').on('change', function(){
            // Update pill-style active state
            $('.round-opt').css({background:'#fafaf9', color:'#57534e'});
            $(this).closest('.round-opt').css({background:'#7f1d1d', color:'#fff'});
            calcTotals();
        });
        // Set initial active state
        (function(){
            $('.round-opt').css({background:'#fafaf9', color:'#57534e'});
            $('input[name="Round_Off_Type"]:checked').closest('.round-opt').css({background:'#7f1d1d', color:'#fff'});
        })();
    }

    // ========== KM-Based Form (Two/Four Wheeler) — Add/Remove Rows & Calculation ==========
    if ($('#itemsBody').closest('.items-table').find('input[name="Dist_Opening[]"]').length) {
        // Override addRow for KM-based form
        $(document).off('click', '.btn-add-row');
        $(document).on('click', '.btn-add-row', function(){
            addKmRow();
        });

        function addKmRow(){
            const count = $('#itemsBody tr').length + 1;
            const tr = `<tr>
                <td>${count}</td>
                <td><input type="text" name="Dist_Opening[]" class="calc-trigger" inputmode="decimal"></td>
                <td><input type="text" name="Dist_Closing[]" class="calc-trigger" inputmode="decimal"></td>
                <td><input type="text" name="Km[]" readonly></td>
                <td><input type="text" name="Amount[]" readonly></td>
                <td><button type="button" class="btn-del-row">−</button></td>
            </tr>`;
            $('#itemsBody').append(tr);
        }

        $(document).off('click', '.btn-del-row');
        $(document).on('click', '.btn-del-row', function(){
            if ($('#itemsBody tr').length <= 1) return; // keep at least 1 row
            $(this).closest('tr').remove();
            reindexKm();
            calcKmTotals();
        });

        function reindexKm(){
            $('#itemsBody tr').each(function(i){
                $(this).find('td:first').text(i + 1);
            });
        }

        // Override calc-trigger for KM form
        $(document).off('input', '.calc-trigger');
        $(document).on('input', '.calc-trigger', function(){
            const $tr = $(this).closest('tr');
            if ($tr.closest('#itemsBody').length) {
                calcKmRow($tr);
            }
            calcKmTotals();
        });

        function calcKmRow($tr){
            const opening = parseFloat($tr.find('input[name="Dist_Opening[]"]').val()) || 0;
            const closing = parseFloat($tr.find('input[name="Dist_Closing[]"]').val()) || 0;
            const km = closing - opening;
            $tr.find('input[name="Km[]"]').val(km > 0 ? km.toFixed(2) : '');

            const rate = parseFloat($('input[name="Rate_Per_KM"]').val()) || 0;
            const amount = km > 0 ? km * rate : 0;
            $tr.find('input[name="Amount[]"]').val(amount > 0 ? amount.toFixed(2) : '');
        }

        function calcKmTotals(){
            let totalKm = 0;
            let totalAmt = 0;
            const rate = parseFloat($('input[name="Rate_Per_KM"]').val()) || 0;

            $('#itemsBody tr').each(function(){
                const opening = parseFloat($(this).find('input[name="Dist_Opening[]"]').val()) || 0;
                const closing = parseFloat($(this).find('input[name="Dist_Closing[]"]').val()) || 0;
                const km = closing - opening;
                const rowKm = km > 0 ? km : 0;
                const rowAmt = rowKm * rate;

                $(this).find('input[name="Km[]"]').val(rowKm > 0 ? rowKm.toFixed(2) : '');
                $(this).find('input[name="Amount[]"]').val(rowAmt > 0 ? rowAmt.toFixed(2) : '');

                totalKm += rowKm;
                totalAmt += rowAmt;
            });

            $('#totalField').val(totalKm > 0 ? totalKm.toFixed(2) : '');
            $('#subTotal').val(totalAmt > 0 ? totalAmt.toFixed(2) : '');

            // Round Off based on Upper / Lower / None selection
            const roundType = $('input[name="Round_Off_Type"]:checked').val() || 'none';
            let grandTotal = totalAmt;
            let roundOff = 0;

            if (roundType === 'upper' && totalAmt > 0) {
                grandTotal = Math.ceil(totalAmt);
                roundOff = grandTotal - totalAmt;
            } else if (roundType === 'lower' && totalAmt > 0) {
                grandTotal = Math.floor(totalAmt);
                roundOff = totalAmt - grandTotal;
            }

            $('#roundOffField').val(roundOff !== 0 ? roundOff.toFixed(2) : '');
            $('#grandTotal').val(grandTotal > 0 ? grandTotal.toFixed(2) : '0.00');
        }

        // Recalc all rows when Rs/KM changes
        $('input[name="Rate_Per_KM"]').on('input', function(){
            calcKmTotals();
        });

        // Round Off grouped-input active state
        function updateRoundOptStyle(){
            $('.round-opt').css({background:'#fafaf9', color:'#57534e'});
            $('input[name="Round_Off_Type"]:checked').closest('.round-opt').css({background:'#7f1d1d', color:'#fff'});
        }
        updateRoundOptStyle(); // set initial state

        // Recalc on Round Off type change
        $('input[name="Round_Off_Type"]').on('change', function(){
            updateRoundOptStyle();
            calcKmTotals();
        });
    }

    // ========== Hired Vehicle Form — Calculation ==========
    if ($('input[name="Opening_Reading"]').length && $('input[name="Closing_Reading"]').length && $('input[name="Per_KM_Rate"]').length) {
        function calcHiredVehicle(){
            const opening = parseFloat($('input[name="Opening_Reading"]').val()) || 0;
            const closing = parseFloat($('input[name="Closing_Reading"]').val()) || 0;
            const rate = parseFloat($('input[name="Per_KM_Rate"]').val()) || 0;
            const otherCharge = parseFloat($('input[name="Other_Charge"]').val()) || 0;

            const totalKm = closing > opening ? closing - opening : 0;
            const totalAmount = (totalKm * rate) + otherCharge;

            $('input[name="Total_KM"]').val(totalKm > 0 ? totalKm.toFixed(2) : '');
            $('#grandTotal').val(totalAmount > 0 ? totalAmount.toFixed(2) : '0.00');
        }

        $(document).on('input', '.hv-calc', calcHiredVehicle);
    }

    // ========== Local Conveyance Form — Calculation ==========
    if ($('input[name="Rate_Per_KM"].lc-calc').length) {
        // Toggle between KM_Base and Fixed mode
        function toggleCalMode(){
            var mode = $('#calBySelect').val();
            if (mode === 'Fixed') {
                $('#lcTripSection').hide();
                $('#rateLabel').text('Fixed Amount ');
                $('#rateLabel').append('<span style="color:#dc2626">*</span>');
                // In fixed mode: Grand Total = Fixed Amount directly
                calcLcFixed();
            } else {
                $('#lcTripSection').show();
                $('#rateLabel').text('Per KM Rate ');
                $('#rateLabel').append('<span style="color:#dc2626">*</span>');
                calcLcTotals();
            }
        }
        $('#calBySelect').on('change', toggleCalMode);
        toggleCalMode(); // init on page load

        function calcLcFixed(){
            var fixedAmt = parseFloat($('input[name="Rate_Per_KM"]').val()) || 0;
            var discount = parseFloat($('#lcDiscount').val()) || 0;
            var afterDiscount = fixedAmt - discount;
            $('#lcTotalKm').val('');
            $('#lcSubTotal').val(fixedAmt > 0 ? fixedAmt.toFixed(2) : '');

            // Round Off
            var roundType = $('input[name="Round_Off_Type"]:checked').val() || 'none';
            var grandTotal = afterDiscount;
            var roundOff = 0;
            if (roundType === 'upper' && afterDiscount > 0) {
                grandTotal = Math.ceil(afterDiscount);
                roundOff = grandTotal - afterDiscount;
            } else if (roundType === 'lower' && afterDiscount > 0) {
                grandTotal = Math.floor(afterDiscount);
                roundOff = afterDiscount - grandTotal;
            }
            $('#lcRoundOff').val(roundOff !== 0 ? roundOff.toFixed(2) : '');
            $('#grandTotal').val(grandTotal > 0 ? grandTotal.toFixed(2) : '0.00');
        }

        function calcLcRow($tr){
            const opening = parseFloat($tr.find('input[name="Dist_Opening[]"]').val()) || 0;
            const closing = parseFloat($tr.find('input[name="Dist_Closing[]"]').val()) || 0;
            const km = closing > opening ? closing - opening : 0;
            const rate = parseFloat($('input[name="Rate_Per_KM"]').val()) || 0;
            const amount = km * rate;
            $tr.find('input[name="Km[]"]').val(km > 0 ? km.toFixed(2) : '');
            $tr.find('input[name="Amount[]"]').val(amount > 0 ? amount.toFixed(2) : '');
        }

        function calcLcTotals(){
            let totalKm = 0;
            let totalAmt = 0;
            const rate = parseFloat($('input[name="Rate_Per_KM"]').val()) || 0;

            $('#itemsBody tr').each(function(){
                const opening = parseFloat($(this).find('input[name="Dist_Opening[]"]').val()) || 0;
                const closing = parseFloat($(this).find('input[name="Dist_Closing[]"]').val()) || 0;
                const km = closing > opening ? closing - opening : 0;
                const rowAmt = km * rate;

                $(this).find('input[name="Km[]"]').val(km > 0 ? km.toFixed(2) : '');
                $(this).find('input[name="Amount[]"]').val(rowAmt > 0 ? rowAmt.toFixed(2) : '');

                totalKm += km;
                totalAmt += rowAmt;
            });

            $('#lcTotalKm').val(totalKm > 0 ? totalKm.toFixed(2) : '');
            $('#lcSubTotal').val(totalAmt > 0 ? totalAmt.toFixed(2) : '');

            // Discount
            const discount = parseFloat($('#lcDiscount').val()) || 0;
            const afterDiscount = totalAmt - discount;

            // Round Off
            const roundType = $('input[name="Round_Off_Type"]:checked').val() || 'none';
            let grandTotal = afterDiscount;
            let roundOff = 0;

            if (roundType === 'upper' && afterDiscount > 0) {
                grandTotal = Math.ceil(afterDiscount);
                roundOff = grandTotal - afterDiscount;
            } else if (roundType === 'lower' && afterDiscount > 0) {
                grandTotal = Math.floor(afterDiscount);
                roundOff = afterDiscount - grandTotal;
            }

            $('#lcRoundOff').val(roundOff !== 0 ? roundOff.toFixed(2) : '');
            $('#grandTotal').val(grandTotal > 0 ? grandTotal.toFixed(2) : '0.00');
        }

        $(document).on('input', '.lc-calc', function(){
            var mode = $('#calBySelect').val();
            if (mode === 'Fixed') {
                calcLcFixed();
            } else {
                const $tr = $(this).closest('tr');
                if ($tr.length) calcLcRow($tr);
                calcLcTotals();
            }
        });

        // Round Off type change
        $('input[name="Round_Off_Type"]').on('change', function(){
            $('.round-opt').css({background:'#fafaf9', color:'#57534e'});
            $(this).closest('.round-opt').css({background:'#7f1d1d', color:'#fff'});
            var mode = $('#calBySelect').val();
            if (mode === 'Fixed') calcLcFixed(); else calcLcTotals();
        });

        // Init active state
        (function(){
            $('.round-opt').css({background:'#fafaf9', color:'#57534e'});
            $('input[name="Round_Off_Type"]:checked').closest('.round-opt').css({background:'#7f1d1d', color:'#fff'});
        })();

        // Add row for local conveyance
        $(document).off('click', '.btn-add-row');
        $(document).on('click', '.btn-add-row', function(){
            var count = $('#itemsBody tr').length + 1;
            var tr = `<tr>
                <td>${count}</td>
                <td><input type="date" name="Date[]"></td>
                <td><input type="text" name="Dist_Opening[]" class="lc-calc" inputmode="decimal"></td>
                <td><input type="text" name="Dist_Closing[]" class="lc-calc" inputmode="decimal"></td>
                <td><input type="text" name="Km[]" readonly></td>
                <td><input type="text" name="Amount[]" readonly></td>
                <td><button type="button" class="btn-del-row">−</button></td>
            </tr>`;
            $('#itemsBody').append(tr);
        });

        $(document).off('click', '.btn-del-row');
        $(document).on('click', '.btn-del-row', function(){
            if ($('#itemsBody tr').length <= 1) return;
            $(this).closest('tr').remove();
            $('#itemsBody tr').each(function(i){ $(this).find('td:first').text(i + 1); });
            calcLcTotals();
        });
    }

    // ========== Electricity Bill: Period date range ==========

    // ========== Lodging Form — Calculation & Employee management ==========
    if ($('input[name="Room_Rate"].lodging-calc').length) {
        function calcLodging(){
            const rooms = parseFloat($('input[name="No_Room"]').val()) || 1;
            const rate = parseFloat($('input[name="Room_Rate"]').val()) || 0;
            const otherCharge = parseFloat($('input[name="Other_Charge"]').val()) || 0;
            const discount = parseFloat($('input[name="Discount"]').val()) || 0;
            const gstPct = parseFloat($('input[name="Gst"]').val()) || 0;

            // Duration calc
            const arrival = $('input[name="Arrival_Date"]').val();
            const departure = $('input[name="Departure_Date"]').val();
            if (arrival && departure) {
                const days = Math.ceil((new Date(departure) - new Date(arrival)) / (1000*60*60*24));
                $('#lodgingDuration').val(days > 0 ? days + ' Night(s)' : '');
            }

            // Amount = Rooms * Rate * Duration(days)
            const days = arrival && departure ? Math.ceil((new Date(departure) - new Date(arrival)) / (1000*60*60*24)) : 1;
            const amount = rooms * rate * (days > 0 ? days : 1);
            $('#lodgingAmount').val(amount > 0 ? amount.toFixed(2) : '');

            // After discount
            const afterDiscount = amount + otherCharge - discount;
            // GST
            const gstAmt = afterDiscount * gstPct / 100;
            const total = afterDiscount + gstAmt;

            // Round Off
            const roundType = $('input[name="Round_Off_Type"]:checked').val() || 'none';
            let grandTotal = total;
            let roundOff = 0;
            if (roundType === 'upper' && total > 0) { grandTotal = Math.ceil(total); roundOff = grandTotal - total; }
            else if (roundType === 'lower' && total > 0) { grandTotal = Math.floor(total); roundOff = total - grandTotal; }

            $('#lodgingRoundOff').val(roundOff !== 0 ? roundOff.toFixed(2) : '');
            $('#grandTotal').val(grandTotal > 0 ? grandTotal.toFixed(2) : '0.00');
        }

        $(document).on('input', '.lodging-calc', calcLodging);
        $('input[name="Arrival_Date"], input[name="Departure_Date"], input[name="No_Room"]').on('change input', calcLodging);

        $('input[name="Round_Off_Type"]').on('change', function(){
            $('.round-opt').css({background:'#fafaf9', color:'#57534e'});
            $(this).closest('.round-opt').css({background:'#7f1d1d', color:'#fff'});
            calcLodging();
        });
        (function(){
            $('.round-opt').css({background:'#fafaf9', color:'#57534e'});
            $('input[name="Round_Off_Type"]:checked').closest('.round-opt').css({background:'#7f1d1d', color:'#fff'});
        })();

        // Hotel Select2 for lodging
        if ($('#selHotel').length) {
            $('#selHotel').select2({
                placeholder: 'Search Hotel',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: R.selHotels,
                    dataType: 'json',
                    delay: 200,
                    data: function(p){ return { q: p.term || '', page: p.page || 1 }; },
                    processResults: function(d){ return { results: d.results, pagination: d.pagination }; }
                },
                templateResult: function(item){
                    if(item.loading) return item.text;
                    return $('<span>').text(item.text + (item.city_name ? ' (' + item.city_name + ')' : ''));
                }
            });
            $('#selHotel').on('select2:select', function(e){
                $('input[name="Hotel_Address"]').val(e.params.data.address || '');
            });
            $('#selHotel').on('select2:clear', function(){
                $('input[name="Hotel_Address"]').val('');
            });
        }

        // Employee Select2 for lodging
        function initLodgingEmpSel(sel){
            $(sel).select2({
                placeholder: 'Search Employee',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: R.selEmployees,
                    dataType: 'json',
                    delay: 200,
                    data: function(p){ return { q: p.term || '', page: p.page || 1 }; },
                    processResults: function(d){ return { results: d.results, pagination: d.pagination }; }
                },
                templateResult: function(item){
                    if(item.loading) return item.text;
                    return $('<span>').text(item.text + (item.emp_code ? ' [' + item.emp_code + ']' : ''));
                }
            }).on('select2:select', function(e){
                $(this).closest('tr').find('input[name="EmpCode[]"]').val(e.params.data.emp_code || '');
            }).on('select2:clear', function(){
                $(this).closest('tr').find('input[name="EmpCode[]"]').val('');
            });
        }
        initLodgingEmpSel('.lodging-emp-sel');

        // Add/remove employee rows
        $(document).on('click', '.btn-add-emp', function(){
            var count = $('#lodgingEmpBody tr').length + 1;
            var tr = `<tr>
                <td>${count}</td>
                <td><select name="Employee[]" class="lodging-emp-sel" style="width:100%"><option value="">Select</option></select></td>
                <td><input type="text" name="EmpCode[]" readonly></td>
                <td><button type="button" class="btn-del-emp">−</button></td>
            </tr>`;
            $('#lodgingEmpBody').append(tr);
            initLodgingEmpSel('#lodgingEmpBody tr:last .lodging-emp-sel');
        });
        $(document).on('click', '.btn-del-emp', function(){
            if ($('#lodgingEmpBody tr').length <= 1) return;
            $(this).closest('tr').remove();
            $('#lodgingEmpBody tr').each(function(i){ $(this).find('td:first').text(i + 1); });
        });
    }

    // ========== Meals Form — Hotel & Employee Select2 ==========
    if ($('#selMealsHotel').length) {
        $('#selMealsHotel').select2({
            placeholder: 'Search Hotel / Restaurant',
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: R.selHotels,
                dataType: 'json',
                delay: 200,
                data: function(p){ return { q: p.term || '', page: p.page || 1 }; },
                processResults: function(d){ return { results: d.results, pagination: d.pagination }; }
            },
            templateResult: function(item){
                if(item.loading) return item.text;
                return $('<span>').text(item.text + (item.city_name ? ' (' + item.city_name + ')' : ''));
            }
        });
        $('#selMealsHotel').on('select2:select', function(e){
            $('input[name="Hotel_Address"]').val(e.params.data.address || '');
        });
        $('#selMealsHotel').on('select2:clear', function(){
            $('input[name="Hotel_Address"]').val('');
        });

        // Meals Employee Select2 & add/remove
        function initMealsEmpSel(sel){
            $(sel).select2({
                placeholder: 'Search Employee',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: R.selEmployees,
                    dataType: 'json',
                    delay: 200,
                    data: function(p){ return { q: p.term || '', page: p.page || 1 }; },
                    processResults: function(d){ return { results: d.results, pagination: d.pagination }; }
                },
                templateResult: function(item){
                    if(item.loading) return item.text;
                    return $('<span>').text(item.text + (item.emp_code ? ' [' + item.emp_code + ']' : ''));
                }
            }).on('select2:select', function(e){
                $(this).closest('tr').find('input[name="EmpCode[]"]').val(e.params.data.emp_code || '');
            }).on('select2:clear', function(){
                $(this).closest('tr').find('input[name="EmpCode[]"]').val('');
            });
        }
        initMealsEmpSel('.meals-emp-sel');

        $(document).on('click', '.btn-add-meals-emp', function(){
            var count = $('#mealsEmpBody tr').length + 1;
            var tr = `<tr>
                <td>${count}</td>
                <td><select name="Employee[]" class="meals-emp-sel" style="width:100%"><option value="">Select</option></select></td>
                <td><input type="text" name="EmpCode[]" readonly></td>
                <td><button type="button" class="btn-del-meals-emp">−</button></td>
            </tr>`;
            $('#mealsEmpBody').append(tr);
            initMealsEmpSel('#mealsEmpBody tr:last .meals-emp-sel');
        });
        $(document).on('click', '.btn-del-meals-emp', function(){
            if ($('#mealsEmpBody tr').length <= 1) return;
            $(this).closest('tr').remove();
            $('#mealsEmpBody tr').each(function(i){ $(this).find('td:first').text(i + 1); });
        });
    }

    // ========== Electricity Bill: Period date range (continued) ==========

    // ========== Machine Operation — Trips × Rate = Total Amount ==========
    if ($('input[name="Trip"].mo-calc').length) {
        function calcMachineTotal(){
            var trips = parseFloat($('input[name="Trip"]').val()) || 0;
            var rate = parseFloat($('input[name="Rate"]').val()) || 0;
            $('#grandTotal').val(trips > 0 && rate > 0 ? (trips * rate).toFixed(2) : '0.00');
        }
        $(document).on('input', '.mo-calc', calcMachineTotal);
    }

    // ========== Labour Payment — Add/Remove Heads & SubTotal ==========
    if ($('#labourHeadsBody').length) {
        function initLabourHeadSel(sel){
            $(sel).select2({
                placeholder: 'Search Head',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: R.selLedgers,
                    dataType: 'json',
                    delay: 200,
                    data: function(p){ return { q: p.term || '', page: p.page || 1 }; },
                    processResults: function(d){ return { results: d.results, pagination: d.pagination }; }
                }
            });
        }
        initLabourHeadSel('.labour-head-sel');

        function calcLabourSubTotal(){
            var total = 0;
            $('#labourHeadsBody tr').each(function(){
                total += parseFloat($(this).find('input[name="Amount[]"]').val()) || 0;
            });
            $('#labourSubTotal').val(total > 0 ? total.toFixed(2) : '0.00');
        }
        $(document).on('input', '.labour-amt', calcLabourSubTotal);

        $(document).on('click', '.btn-add-labour', function(){
            var count = $('#labourHeadsBody tr').length + 1;
            var tr = `<tr>
                <td>${count}</td>
                <td><select name="Head[]" class="labour-head-sel" style="width:100%"><option value="">Select</option></select></td>
                <td><input type="text" name="Amount[]" class="f-input labour-amt" inputmode="decimal"></td>
                <td><button type="button" class="btn-del-labour">−</button></td>
            </tr>`;
            $('#labourHeadsBody').append(tr);
            initLabourHeadSel('#labourHeadsBody tr:last .labour-head-sel');
        });
        $(document).on('click', '.btn-del-labour', function(){
            if ($('#labourHeadsBody tr').length <= 1) return;
            $(this).closest('tr').remove();
            $('#labourHeadsBody tr').each(function(i){ $(this).find('td:first').text(i + 1); });
            calcLabourSubTotal();
        });
    }

    // ========== Electricity Bill: Period date range (original) ==========
    if ($('input[name="Period_From"]').length && $('input[name="Period_To"]').length) {
        $('input[name="Period_From"], input[name="Period_To"]').on('change', function(){
            var from = $('input[name="Period_From"]').val();
            var to = $('input[name="Period_To"]').val();
            $('input[name="Period"]').val(from && to ? from + ' to ' + to : (from || to || ''));
        });
    }

    // ========== Electricity Bill: Auto-fill Previous Reading from BP No ==========
    if ($('input[name="BP_No"]').length && $('input[name="Previous_Reading"]').length) {
        function fetchLastReading(){
            var bpNo = $('input[name="BP_No"]').val().trim();
            if (!bpNo) { $('#prevReadingHint').hide(); return; }
            $.ajax({
                url: R.selLastReading,
                method: 'GET',
                data: { bp_no: bpNo },
                dataType: 'json'
            }).done(function(res){
                if (res.reading) {
                    $('#prevReadingHint').text('Last reading: ' + res.reading).show().css('cursor','pointer');
                    if (!$('input[name="Previous_Reading"]').val().trim()) {
                        $('input[name="Previous_Reading"]').val(res.reading);
                    }
                } else {
                    $('#prevReadingHint').hide();
                }
            });
        }
        $('input[name="BP_No"]').on('blur change', fetchLastReading);
        // Click hint to apply
        $(document).on('click', '#prevReadingHint', function(){
            var val = $(this).text().replace('Last reading: ', '');
            $('input[name="Previous_Reading"]').val(val);
        });
    }

    // ========== Load Existing Items via AJAX (only for invoice-type forms) ==========
    var kmForms = ['two-four-wheeler','hired-vehicle','local-conveyance','lodging','meals','miscellaneous','labour-payment','cash-receipt','machine-operation'];
    if (kmForms.indexOf('{{ $formPartial }}') === -1) {
    $.ajax({
        url: R.items,
        method: 'GET',
        headers: {'X-CSRF-TOKEN': CSRF},
        dataType: 'json'
    }).done(function(resp){
        var data = resp.data || resp;
        if(data && data.length > 0){
            $('#itemsBody').empty();
            rowCount = 0;
            data.forEach(function(item){
                rowCount++;
                const tr = `<tr>
                    <td>${rowCount}</td>
                    <td><select name="Particular[]" class="particular-sel" style="width:100%"><option value="${item.Particular || ''}" selected>${item.Particular || ''}</option></select></td>
                    <td><input type="text" name="HSN[]" value="${item.HSN || ''}" style="min-width:60px"></td>
                    <td><input type="text" name="Qty[]" class="calc-trigger" value="${item.Qty || ''}" style="min-width:50px"></td>
                    <td><select name="Unit[]" class="unit-sel" style="min-width:70px"></select></td>
                    <td><input type="text" name="MRP[]" class="calc-trigger" value="${item.MRP || ''}" style="min-width:65px"></td>
                    <td><input type="text" name="Discount[]" class="calc-trigger" value="${item.Discount || ''}" style="min-width:55px"></td>
                    <td><input type="text" name="Price[]" readonly value="${item.Price || ''}" style="min-width:65px"></td>
                    <td><input type="text" name="Amount[]" class="amt-field" readonly value="${item.Amount || ''}" style="min-width:75px"></td>
                    <td><input type="text" name="GST[]" class="calc-trigger" value="${item.GST || ''}" style="min-width:45px"></td>
                    <td><input type="text" name="SGST[]" readonly value="${item.SGST || ''}" style="min-width:45px"></td>
                    <td><input type="text" name="IGST[]" class="calc-trigger" value="${item.IGST || ''}" style="min-width:45px"></td>
                    <td><input type="text" name="Cess[]" class="calc-trigger" value="${item.Cess || ''}" style="min-width:45px"></td>
                    <td><input type="text" name="TAmount[]" class="total-field" readonly value="${item.Total_Amount || ''}" style="min-width:75px"></td>
                    <td><button type="button" class="btn-del-row">−</button></td>
                </tr>`;
                $('#itemsBody').append(tr);
                initParticular('#itemsBody tr:last .particular-sel');
                initUnitSelect2('#itemsBody tr:last .unit-sel', item.Unit || '', item.Unit || '');
            });
            // Add a blank row with + button
            addRow();
            // Replace last row's delete button with add button
            $('#itemsBody tr:last .btn-del-row').removeClass('btn-del-row').addClass('btn-add-row').text('+');
        }
    });
    } // end if not km-form

    // ========== Form-Specific Validation Rules ==========
    const FORM_KEY = '{{ $formPartial }}';

    function validateForm(){
        let missing = [];

        // Helper to check a value is not empty
        function chk(val){ return val && val.trim() !== ''; }

        switch(FORM_KEY){
            case 'two-four-wheeler':
                if(!chk($('#selEmployee').val())) missing.push('Employee / Payee');
                if(!chk($('input[name="Bill_Date"]').val())) missing.push('Bill Date');
                if(!chk($('input[name="Vehicle_No"]').val())) missing.push('Vehicle No');
                if(!chk($('#selLocation').val())) missing.push('Location');
                if(!chk($('input[name="Rate_Per_KM"]').val()) || parseFloat($('input[name="Rate_Per_KM"]').val()) <= 0) missing.push('Rs/KM (must be greater than 0)');
                if(!chk($('textarea[name="Remark"]').val())) missing.push('Remark');
                // Validate KM rows: at least one filled, and any added row must be complete
                var hasKmRow = false;
                var hasIncomplete = false;
                $('#itemsBody tr').each(function(idx){
                    var $tr = $(this);
                    var op = $tr.find('input[name="Dist_Opening[]"]').val().trim();
                    var cl = $tr.find('input[name="Dist_Closing[]"]').val().trim();
                    var hasAny = (op !== '' || cl !== '');
                    var hasBoth = (op !== '' && cl !== '');

                    if(hasBoth) hasKmRow = true;

                    // If row has partial data, mark incomplete
                    if(hasAny && !hasBoth){
                        hasIncomplete = true;
                        $tr.find('input[name="Dist_Opening[]"], input[name="Dist_Closing[]"]').each(function(){
                            if(!$(this).val().trim()) $(this).css('border-color','#dc2626');
                        });
                    } else {
                        $tr.find('input[name="Dist_Opening[]"], input[name="Dist_Closing[]"]').css('border-color','');
                    }
                });
                if(!hasKmRow) missing.push('At least one KM Detail row');
                if(hasIncomplete) missing.push('Fill Opening & Closing KM in all rows or remove empty rows');
                break;

            case 'invoice':
                if(!chk($('input[name="Bill_No"]').val())) missing.push('Invoice No');
                if(!chk($('input[name="Bill_Date"]').val())) missing.push('Invoice Date');
                if(!chk($('#selBuyer').val())) missing.push('Buyer');
                if(!chk($('#selVendor').val())) missing.push('Vendor');
                if(!chk($('#selLocation').val())) missing.push('Location');
                if(!chk($('textarea[name="Remark"]').val())) missing.push('Remark');
                // Validate line items - at least 1 row with Particular + Qty + MRP
                var hasItem = false;
                var hasIncompleteItem = false;
                $('#itemsBody tr').each(function(){
                    var $tr = $(this);
                    var p = ($tr.find('select[name="Particular[]"]').val() || $tr.find('input[name="Particular[]"]').val() || '').trim();
                    var qty = $tr.find('input[name="Qty[]"]').val().trim();
                    var mrp = $tr.find('input[name="MRP[]"]').val().trim();
                    var hasAny = (p !== '' || qty !== '' || mrp !== '');
                    var hasAll = (p !== '' && qty !== '' && mrp !== '');

                    if(hasAll) hasItem = true;

                    // If row has partial data, highlight empty fields
                    if(hasAny && !hasAll){
                        hasIncompleteItem = true;
                        if(!p) $tr.find('select[name="Particular[]"]').next('.select2-container').find('.select2-selection').css('border-color','#dc2626');
                        if(!qty) $tr.find('input[name="Qty[]"]').css('border-color','#dc2626');
                        if(!mrp) $tr.find('input[name="MRP[]"]').css('border-color','#dc2626');
                    } else {
                        $tr.find('select[name="Particular[]"]').next('.select2-container').find('.select2-selection').css('border-color','');
                        $tr.find('input[name="Qty[]"], input[name="MRP[]"]').css('border-color','');
                    }
                });
                if(!hasItem) missing.push('At least one Line Item (Particular, Qty, MRP required)');
                if(hasIncompleteItem) missing.push('Fill Particular, Qty & MRP in all rows or remove empty rows');
                break;

            case 'credit-note':
                if(!chk($('input[name="CreditNo"]').val())) missing.push('Credit Note No');
                if(!chk($('input[name="CreditDate"]').val())) missing.push('Credit Note Date');
                if(!chk($('input[name="Bill_Date"]').val())) missing.push('Invoice Date');
                if(!chk($('#selBuyer').val())) missing.push('Buyer');
                if(!chk($('#selVendor').val())) missing.push('Vendor');
                if(!chk($('#selLocation').val())) missing.push('Location');
                if(!chk($('textarea[name="Remark"]').val())) missing.push('Remark');
                // Validate line items - at least 1 row with Particular + Qty + MRP
                var hasItem = false;
                var hasIncompleteItem = false;
                $('#itemsBody tr').each(function(){
                    var $tr = $(this);
                    var p = ($tr.find('select[name="Particular[]"]').val() || $tr.find('input[name="Particular[]"]').val() || '').trim();
                    var qty = $tr.find('input[name="Qty[]"]').val().trim();
                    var mrp = $tr.find('input[name="MRP[]"]').val().trim();
                    var hasAny = (p !== '' || qty !== '' || mrp !== '');
                    var hasAll = (p !== '' && qty !== '' && mrp !== '');

                    if(hasAll) hasItem = true;

                    // If row has partial data, highlight empty fields
                    if(hasAny && !hasAll){
                        hasIncompleteItem = true;
                        if(!p) $tr.find('select[name="Particular[]"]').next('.select2-container').find('.select2-selection').css('border-color','#dc2626');
                        if(!qty) $tr.find('input[name="Qty[]"]').css('border-color','#dc2626');
                        if(!mrp) $tr.find('input[name="MRP[]"]').css('border-color','#dc2626');
                    } else {
                        $tr.find('select[name="Particular[]"]').next('.select2-container').find('.select2-selection').css('border-color','');
                        $tr.find('input[name="Qty[]"], input[name="MRP[]"]').css('border-color','');
                    }
                });
                if(!hasItem) missing.push('At least one Line Item (Particular, Qty, MRP required)');
                if(hasIncompleteItem) missing.push('Fill Particular, Qty & MRP in all rows or remove empty rows');
                break;

            case 'hired-vehicle':
                if(!chk($('#selVendor').val())) missing.push('Agency Name');
                if(!chk($('#selBuyer').val())) missing.push('Billing Name');
                if(!chk($('input[name="Vehicle_No"]').val())) missing.push('Vehicle No');
                if(!chk($('input[name="Invoice_No"]').val())) missing.push('Invoice No');
                if(!chk($('input[name="Invoice_Date"]').val())) missing.push('Invoice Date');
                if(!chk($('input[name="Per_KM_Rate"]').val()) || parseFloat($('input[name="Per_KM_Rate"]').val()) <= 0) missing.push('Per KM Rate (must be greater than 0)');
                if(!chk($('input[name="Journey_Start"]').val())) missing.push('Booking Date');
                if(!chk($('input[name="Journey_End"]').val())) missing.push('End Date');
                if(!chk($('input[name="Opening_Reading"]').val())) missing.push('Start Reading');
                if(!chk($('input[name="Closing_Reading"]').val())) missing.push('Closing Reading');
                if(!chk($('#selLocation').val())) missing.push('Location');
                if(!chk($('textarea[name="Remark"]').val())) missing.push('Remark');
                break;

            case 'vehicle-fuel':
                if(!chk($('#selVendor').val())) missing.push('Vendor Name');
                if(!chk($('#selBuyer').val())) missing.push('Billing To');
                if(!chk($('input[name="Bill_No"]').val())) missing.push('Invoice No');
                if(!chk($('input[name="Bill_Date"]').val())) missing.push('Invoice Date');
                if(!chk($('input[name="Vehicle_No"]').val())) missing.push('Vehicle No');
                if(!chk($('input[name="Liters"]').val())) missing.push('Liter');
                break;

            case 'vehicle-maintenance':
                if(!chk($('#selVendor').val())) missing.push('Vendor Name');
                if(!chk($('#selBuyer').val())) missing.push('Billing To');
                if(!chk($('input[name="InvoiceNo"]').val())) missing.push('Invoice No');
                if(!chk($('input[name="Bill_Date"]').val())) missing.push('Invoice Date');
                break;

            case 'cash-voucher':
                if(!chk($('input[name="Voucher_No"]').val())) missing.push('Voucher No');
                if(!chk($('input[name="Voucher_Date"]').val())) missing.push('Voucher Date');
                if(!chk($('input[name="Payee"]').val())) missing.push('Payee');
                if(!chk($('input[name="Payer"]').val())) missing.push('Payer');
                if(!chk($('input[name="Particular"]').val())) missing.push('Particular');
                if(!chk($('#selLocation').val())) missing.push('Location');
                if(!chk($('input[name="Amount"]').val()) || parseFloat($('input[name="Amount"]').val()) <= 0) missing.push('Amount (must be greater than 0)');
                if(!chk($('textarea[name="Remark"]').val())) missing.push('Remark');
                break;

            case 'cash-receipt':
                if(!chk($('input[name="Receipt_No"]').val())) missing.push('Receipt No');
                if(!chk($('input[name="Receipt_Date"]').val())) missing.push('Receipt Date');
                if(!chk($('#selBuyer').val())) missing.push('Company');
                if(!chk($('input[name="ReceivedFrom"]').val())) missing.push('Received From');
                if(!chk($('input[name="Receiver"]').val())) missing.push('Receiver');
                if(!chk($('#selLocation').val())) missing.push('Location');
                if(!chk($('input[name="Amount"]').val()) || parseFloat($('input[name="Amount"]').val()) <= 0) missing.push('Amount (must be greater than 0)');
                if(!chk($('textarea[name="Remark"]').val())) missing.push('Remark');
                break;

            case 'cash-deposits':
                if(!chk($('select[name="Type"]').val())) missing.push('Type');
                if(!chk($('input[name="Date"]').val())) missing.push('Date');
                if(!chk($('input[name="Bank_Name"]').val())) missing.push('Bank Name');
                if(!chk($('input[name="Branch"]').val())) missing.push('Branch');
                if(!chk($('input[name="Account_No"]').val())) missing.push('Account No');
                if(!chk($('input[name="Beneficiary_Name"]').val())) missing.push('Beneficiary Name');
                if(!chk($('input[name="Amount"]').val()) || parseFloat($('input[name="Amount"]').val()) <= 0) missing.push('Amount (must be greater than 0)');
                if(!chk($('textarea[name="Remark"]').val())) missing.push('Remark');
                break;

            case 'miscellaneous':
                if(!chk($('input[name="VoucherNo"]').val())) missing.push('Voucher No');
                if(!chk($('input[name="Voucher_Date"]').val())) missing.push('Voucher Date');
                if(!chk($('input[name="File_Date"]').val())) missing.push('Date');
                if(!chk($('#selBuyer').val())) missing.push('Company (From)');
                if(!chk($('#selVendor').val())) missing.push('Vendor (To)');
                if(!chk($('#selLocation').val())) missing.push('Location');
                if(!chk($('input[name="Particular"]').val())) missing.push('Particular / Description');
                if(!chk($('input[name="Amount"]').val()) || parseFloat($('input[name="Amount"]').val()) <= 0) missing.push('Amount (must be greater than 0)');
                if(!chk($('textarea[name="Remark"]').val())) missing.push('Remark');
                break;

            case 'machine-operation':
                if(!chk($('#selBuyer').val())) missing.push('Company');
                if(!chk($('#selVendor').val())) missing.push('Vendor');
                if(!chk($('input[name="VehicleRegNo"]').val())) missing.push('Vehicle No');
                if(!chk($('select[name="Vehicle_Type"]').val())) missing.push('Vehicle Type');
                if(!chk($('#selLocation').val())) missing.push('Location');
                if(!chk($('input[name="Invoice_Date"]').val())) missing.push('Invoice Date');
                if(!chk($('input[name="Trip"]').val()) || parseFloat($('input[name="Trip"]').val()) <= 0) missing.push('Trips (must be greater than 0)');
                if(!chk($('input[name="Rate"]').val()) || parseFloat($('input[name="Rate"]').val()) <= 0) missing.push('Rate per Trip (must be greater than 0)');
                if(!chk($('textarea[name="Remark"]').val())) missing.push('Remark');
                break;

            case 'local-conveyance':
                if(!chk($('select[name="Travel_Mode"]').val())) missing.push('Mode');
                if(!chk($('#selLocation').val())) missing.push('Location');
                if(!chk($('#selLocalEmployee').val())) missing.push('Employee');
                if(!chk($('input[name="Vehicle_No"]').val())) missing.push('Vehicle No');
                if(!chk($('select[name="Month"]').val())) missing.push('Month');
                if(!chk($('input[name="Rate_Per_KM"]').val()) || parseFloat($('input[name="Rate_Per_KM"]').val()) <= 0) missing.push($('#calBySelect').val() === 'Fixed' ? 'Fixed Amount (must be greater than 0)' : 'Per KM Rate (must be greater than 0)');
                if(!chk($('textarea[name="Remark"]').val())) missing.push('Remark');
                // For KM_Base mode: at least one trip row required
                if ($('#calBySelect').val() !== 'Fixed') {
                    var hasTrip = false;
                    var hasIncompleteTrip = false;
                    $('#itemsBody tr').each(function(){
                        var $tr = $(this);
                        var dt = $tr.find('input[name="Date[]"]').val().trim();
                        var op = $tr.find('input[name="Dist_Opening[]"]').val().trim();
                        var cl = $tr.find('input[name="Dist_Closing[]"]').val().trim();
                        var hasAny = (dt !== '' || op !== '' || cl !== '');
                        var hasAll = (dt !== '' && op !== '' && cl !== '');
                        if(hasAll) hasTrip = true;
                        if(hasAny && !hasAll){
                            hasIncompleteTrip = true;
                            if(!dt) $tr.find('input[name="Date[]"]').css('border-color','#dc2626');
                            if(!op) $tr.find('input[name="Dist_Opening[]"]').css('border-color','#dc2626');
                            if(!cl) $tr.find('input[name="Dist_Closing[]"]').css('border-color','#dc2626');
                        } else {
                            $tr.find('input[name="Date[]"], input[name="Dist_Opening[]"], input[name="Dist_Closing[]"]').css('border-color','');
                        }
                    });
                    if(!hasTrip) missing.push('At least one Trip Detail row (Date, Opening, Closing)');
                    if(hasIncompleteTrip) missing.push('Fill Date, Opening & Closing in all rows or remove empty rows');
                }
                break;

            case 'air':
                if(!chk($('input[name="Agent_Name"]:visible, select[name="Agent_Name"]:visible').val())) missing.push('Agent Name');
                if(!chk($('input[name="PNR_Number"]').val())) missing.push('PNR Number');
                if(!chk($('input[name="Booking_Date"]').val())) missing.push('Booking Date');
                if(!chk($('input[name="Journey_Date"]').val())) missing.push('Journey Date');
                if(!chk($('input[name="Airline"]:visible, select[name="Airline"]:visible').val())) missing.push('Airline');
                if(!chk($('input[name="Ticket_Number"]').val())) missing.push('Ticket Number');
                if(!chk($('input[name="Journey_From"]').val())) missing.push('Journey From');
                if(!chk($('input[name="Journey_To"]').val())) missing.push('Journey To');
                if(!chk($('select[name="Travel_Class"]').val())) missing.push('Travel Class');
                if(!chk($('#selLocation').val())) missing.push('Location');
                if(!chk($('input[name="Total_Amount"]').val()) || parseFloat($('input[name="Total_Amount"]').val()) <= 0) missing.push('Total Amount (must be greater than 0)');
                // At least one employee
                var hasAirEmp = false;
                $('#airEmpBody tr').each(function(){
                    var empVal = $(this).find('select[name="Employee[]"]').val();
                    if(empVal && empVal.trim() !== '') hasAirEmp = true;
                });
                if(!hasAirEmp) missing.push('At least one Employee');
                if(!chk($('textarea[name="Remark"]').val())) missing.push('Remark');
                break;

            case 'rail':
                if(!chk($('input[name="Agent_Name"]:visible, select[name="Agent_Name"]:visible').val())) missing.push('Agent Name');
                if(!chk($('input[name="Train_Number"]').val())) missing.push('Train Number');
                if(!chk($('input[name="PNR_Number"]').val())) missing.push('PNR Number');
                if(!chk($('input[name="Booking_Date"]').val())) missing.push('Booking Date');
                if(!chk($('input[name="Journey_Date"]').val())) missing.push('Journey Date');
                if(!chk($('input[name="Booking_Id"]').val())) missing.push('Booking ID');
                if(!chk($('input[name="Journey_From"]').val())) missing.push('Journey From');
                if(!chk($('input[name="Journey_To"]').val())) missing.push('Journey To');
                if(!chk($('select[name="Travel_Class"]').val())) missing.push('Travel Class');
                if(!chk($('#selLocation').val())) missing.push('Location');
                if(!chk($('input[name="Total_Amount"]').val()) || parseFloat($('input[name="Total_Amount"]').val()) <= 0) missing.push('Total Amount (must be greater than 0)');
                // At least one employee
                var hasRailEmp = false;
                $('#railEmpBody tr').each(function(){
                    var empVal = $(this).find('select[name="Employee[]"]').val();
                    if(empVal && empVal.trim() !== '') hasRailEmp = true;
                });
                if(!hasRailEmp) missing.push('At least one Employee');
                if(!chk($('textarea[name="Remark"]').val())) missing.push('Remark');
                break;

            case 'sale-bill':
                if(!chk($('input[name="Bill_No"]').val())) missing.push('Invoice No');
                if(!chk($('input[name="Bill_Date"]').val())) missing.push('Invoice Date');
                if(!chk($('#selBuyer').val())) missing.push('Vendor');
                if(!chk($('#selVendor').val())) missing.push('Buyer');
                if(!chk($('textarea[name="Remark"]').val())) missing.push('Remark');
                // Validate line items - at least 1 row with Particular + Qty + MRP
                var hasItem = false;
                var hasIncompleteItem = false;
                $('#itemsBody tr').each(function(){
                    var $tr = $(this);
                    var p = ($tr.find('select[name="Particular[]"]').val() || $tr.find('input[name="Particular[]"]').val() || '').trim();
                    var qty = $tr.find('input[name="Qty[]"]').val().trim();
                    var mrp = $tr.find('input[name="MRP[]"]').val().trim();
                    var hasAny = (p !== '' || qty !== '' || mrp !== '');
                    var hasAll = (p !== '' && qty !== '' && mrp !== '');

                    if(hasAll) hasItem = true;

                    // If row has partial data, highlight empty fields
                    if(hasAny && !hasAll){
                        hasIncompleteItem = true;
                        if(!p) $tr.find('select[name="Particular[]"]').next('.select2-container').find('.select2-selection').css('border-color','#dc2626');
                        if(!qty) $tr.find('input[name="Qty[]"]').css('border-color','#dc2626');
                        if(!mrp) $tr.find('input[name="MRP[]"]').css('border-color','#dc2626');
                    } else {
                        $tr.find('select[name="Particular[]"]').next('.select2-container').find('.select2-selection').css('border-color','');
                        $tr.find('input[name="Qty[]"], input[name="MRP[]"]').css('border-color','');
                    }
                });
                if(!hasItem) missing.push('At least one Line Item (Particular, Qty, MRP required)');
                if(hasIncompleteItem) missing.push('Fill Particular, Qty & MRP in all rows or remove empty rows');
                break;

            case 'electricity-bill':
                if(!chk($('#selLocation').val())) missing.push('Location');
                if(!chk($('input[name="PaymentDate"]').val())) missing.push('Payment Date');
                if(!chk($('input[name="Biller_Name"]').val())) missing.push('Biller Name');
                if(!chk($('input[name="BP_No"]').val())) missing.push('BP No');
                if(!chk($('input[name="Period"]').val())) missing.push('Bill Period');
                if(!chk($('input[name="Meter_No"]').val())) missing.push('Meter Number');
                if(!chk($('input[name="Bill_Date"]').val())) missing.push('Bill Date');
                if(!chk($('input[name="Bill_No"]').val())) missing.push('Bill No');
                if(!chk($('input[name="Previous_Reading"]').val())) missing.push('Previous Reading');
                if(!chk($('input[name="Current_Reading"]').val())) missing.push('Current Reading');
                if(!chk($('input[name="Unit_Consumed"]').val())) missing.push('Unit Consumed');
                if(!chk($('input[name="Bill_Amount"]').val()) || parseFloat($('input[name="Bill_Amount"]').val()) <= 0) missing.push('Bill Amount (must be greater than 0)');
                if(!chk($('textarea[name="Remark"]').val())) missing.push('Remark');
                break;

            case 'telephone-bill':
                // Required fields
                if(!chk($('input[name="Bill_Date"]').val())) missing.push('Bill Date');
                if(!chk($('input[name="Invoice_No"]').val())) missing.push('Invoice No');
                if(!chk($('input[name="Biller_Name"]').val())) missing.push('Biller Name');
                if(!chk($('input[name="Phone_No"]').val())) missing.push('Phone No');
                if(!chk($('input[name="Amount_Outstanding"]').val())) missing.push('Total Amount Outstanding');
                if(!chk($('textarea[name="Remark"]').val())) missing.push('Remark');
                break;

            case 'gst-challan':
                // Required fields
                if(!chk($('input[name="CPIN"]').val())) missing.push('CPIN');
                if(!chk($('input[name="Deposit_Date"]').val())) missing.push('Deposit Date');
                if(!chk($('input[name="GSTIN"]').val())) missing.push('GSTIN');
                if(!chk($('#grandTotal').val())) missing.push('Total Challan Amount');
                if(!chk($('textarea[name="Remark"]').val())) missing.push('Remark');
                break;

            case 'income-tax':
                if(!chk($('select[name="Section"]').val())) missing.push('Section');
                if(!chk($('#selBuyer').val())) missing.push('Company');
                if(!chk($('select[name="Payment_Nature"]').val())) missing.push('Nature of Payment');
                if(!chk($('select[name="Assessment_Year"]').val())) missing.push('Assessment Year');
                if(!chk($('input[name="Bank_Name"]').val())) missing.push('Bank Name');
                if(!chk($('input[name="BSR_Code"]').val())) missing.push('BSR Code');
                if(!chk($('input[name="Challan_No"]').val())) missing.push('Challan No');
                if(!chk($('input[name="Challan_Date"]').val())) missing.push('Challan Date');
                if(!chk($('input[name="Ref_No"]').val())) missing.push('Bank Reference No');
                if(!chk($('input[name="Amount"]').val()) || parseFloat($('input[name="Amount"]').val()) <= 0) missing.push('Amount (must be greater than 0)');
                if(!chk($('textarea[name="Remark"]').val())) missing.push('Remark');
                break;

            case 'insurance':
                if(!chk($('select[name="Insurance_Type"]').val())) missing.push('Insurance Type');
                if(!chk($('input[name="Insurance_Company"]').val())) missing.push('Insurance Company');
                if(!chk($('input[name="Policy_Number"]').val())) missing.push('Policy Number');
                if(!chk($('input[name="Policy_Date"]').val())) missing.push('Policy Date');
                if(!chk($('input[name="From_Date"]').val())) missing.push('From Date');
                if(!chk($('input[name="To_Date"]').val())) missing.push('To Date');
                if(!chk($('#selLocation').val())) missing.push('Location');
                if(!chk($('input[name="Premium_Amount"]').val()) || parseFloat($('input[name="Premium_Amount"]').val()) <= 0) missing.push('Premium Amount (must be greater than 0)');
                if(!chk($('textarea[name="Remark"]').val())) missing.push('Remark');
                break;

            case 'lodging':
                if(!chk($('#selLocation').val())) missing.push('Location');
                if(!chk($('input[name="Bill_No"]').val())) missing.push('Bill No');
                if(!chk($('input[name="Bill_Date"]').val())) missing.push('Bill Date');
                if(!chk($('#selBuyer').val())) missing.push('Billing Name');
                if(!chk($('#selHotel').val())) missing.push('Hotel Name');
                if(!chk($('input[name="Arrival_Date"]').val())) missing.push('Arrival Date');
                if(!chk($('input[name="Departure_Date"]').val())) missing.push('Departure Date');
                if(!chk($('input[name="No_Room"]').val())) missing.push('No. of Rooms');
                if(!chk($('input[name="Room_Rate"]').val()) || parseFloat($('input[name="Room_Rate"]').val()) <= 0) missing.push('Room Rate (must be greater than 0)');
                if(!chk($('textarea[name="Remark"]').val())) missing.push('Remark');
                break;

            case 'meals':
                if(!chk($('#selMealsHotel').val())) missing.push('Hotel / Restaurant');
                if(!chk($('input[name="Date"]').val())) missing.push('Bill Date');
                if(!chk($('#selLocation').val())) missing.push('Location');
                if(!chk($('select[name="Detail"]').val())) missing.push('Occasion / Purpose');
                if(!chk($('input[name="Amount"]').val()) || parseFloat($('input[name="Amount"]').val()) <= 0) missing.push('Amount (must be greater than 0)');
                // At least one employee
                var hasEmp = false;
                $('#mealsEmpBody tr').each(function(){
                    var empVal = $(this).find('select[name="Employee[]"]').val();
                    if(empVal && empVal.trim() !== '') hasEmp = true;
                });
                if(!hasEmp) missing.push('At least one Employee');
                if(!chk($('textarea[name="Remark"]').val())) missing.push('Remark');
                break;

            case 'labour-payment':
                if(!chk($('input[name="Voucher_No"]').val())) missing.push('Voucher No');
                if(!chk($('input[name="Payment_Date"]').val())) missing.push('Payment Date');
                if(!chk($('input[name="Payee"]').val())) missing.push('Payee');
                if(!chk($('#selLocation').val())) missing.push('Location');
                if(!chk($('input[name="Total_Amount"]').val()) || parseFloat($('input[name="Total_Amount"]').val()) <= 0) missing.push('Total Amount (must be greater than 0)');
                if(!chk($('textarea[name="Remark"]').val())) missing.push('Remark');
                // At least one head row required, and if head filled then amount required & vice versa
                var hasHead = false;
                var hasIncompleteHead = false;
                $('#labourHeadsBody tr').each(function(){
                    var $tr = $(this);
                    var head = ($tr.find('select[name="Head[]"]').val() || '').trim();
                    var amt = $tr.find('input[name="Amount[]"]').val().trim();
                    if(head && amt) hasHead = true;
                    if(head && !amt){
                        hasIncompleteHead = true;
                        $tr.find('input[name="Amount[]"]').css('border-color','#dc2626');
                    } else if(!head && amt){
                        hasIncompleteHead = true;
                        $tr.find('select[name="Head[]"]').next('.select2-container').find('.select2-selection').css('border-color','#dc2626');
                    } else {
                        $tr.find('input[name="Amount[]"]').css('border-color','');
                        $tr.find('select[name="Head[]"]').next('.select2-container').find('.select2-selection').css('border-color','');
                    }
                });
                if(!hasHead) missing.push('At least one Payment Head with Amount');
                if(hasIncompleteHead) missing.push('Fill both Head & Amount in each row');
                break;

            case 'ticket-cancellation':
                // Required fields
                if(!chk($('input[name="BillDate"]').val())) missing.push('Date');
                if(!chk($('#selTicketCancelAgent').val())) missing.push('Agent Name');
                if(!chk($('input[name="File_Date"]').val())) missing.push('Cancelled Date');
                if(!chk($('#ticketCancelSubTotal').val())) missing.push('Sub Total');
                if(!chk($('#ticketCancelGrandTotal').val())) missing.push('Grand Total');
                if(!chk($('textarea[name="Remark"]').val())) missing.push('Remark');
                
                // Check at least one employee row
                var hasEmployee = false;
                var hasIncompleteRow = false;
                $('#ticketCancelItemsBody tr').each(function(){
                    var $tr = $(this);
                    var emp = ($tr.find('select[name="Employee[]"]').val() || '').trim();
                    var pnr = $tr.find('input[name="PNR[]"]').val().trim();
                    var amt = $tr.find('input[name="Amount[]"]').val().trim();
                    if(emp && pnr && amt) hasEmployee = true;
                    if(emp && (!pnr || !amt)){
                        hasIncompleteRow = true;
                        if(!pnr) $tr.find('input[name="PNR[]"]').css('border-color','#dc2626');
                        if(!amt) $tr.find('input[name="Amount[]"]').css('border-color','#dc2626');
                    } else if(!emp && (pnr || amt)){
                        hasIncompleteRow = true;
                        $tr.find('select[name="Employee[]"]').next('.select2-container').find('.select2-selection').css('border-color','#dc2626');
                    } else {
                        $tr.find('input[name="PNR[]"]').css('border-color','');
                        $tr.find('input[name="Amount[]"]').css('border-color','');
                        $tr.find('select[name="Employee[]"]').next('.select2-container').find('.select2-selection').css('border-color','');
                    }
                });
                if(!hasEmployee) missing.push('At least one Employee with PNR and Amount');
                if(hasIncompleteRow) missing.push('Fill Employee, PNR & Amount in each row');
                break;

            default:
                // Fallback — check common required fields
                if($('input[name="Bill_No"]').length && !chk($('input[name="Bill_No"]').val())) missing.push('Bill No');
                if($('input[name="Bill_Date"]').length && !chk($('input[name="Bill_Date"]').val())) missing.push('Bill Date');
                if($('#selVendor').length && !chk($('#selVendor').val())) missing.push('Vendor');
                if($('#selLocation').length && !chk($('#selLocation').val())) missing.push('Location');
                break;
        }

        return missing;
    }

    // ========== Save (Draft / Final Submit) ==========
    function showAlert(msg, type){
        $('#alertBox').removeClass('error success').addClass(type).text(msg).show();
        setTimeout(function(){ $('#alertBox').fadeOut(); }, 5000);
    }

    function save(action){
        // Form-specific validation on final submit
        if(action === 'final_submit'){
            const missing = validateForm();
            if (missing.length) {
                showAlert('Please fill required fields: ' + missing.join(', '), 'error');
                return;
            }
            // Show custom confirmation modal
            showConfirmModal();
            return;
        }

        doSave(action);
    }

    function doSave(action){

        const formData = $('#entryForm').serializeArray();
        formData.push({ name: 'action', value: action });

        // Disable buttons & show loader
        $('#btnDraft, #btnSubmit').prop('disabled', true).css('opacity', '0.6');
        const $btn = action === 'final_submit' ? $('#btnSubmit') : $('#btnDraft');
        const origText = $btn.text();
        $btn.html('<span class="spinner"></span> Processing...');

        $.ajax({
            url: R.save,
            method: 'POST',
            headers: {'X-CSRF-TOKEN': CSRF},
            data: $.param(formData),
            dataType: 'json'
        }).done(function(res){
            if(res.success){
                showAlert(res.message || 'Saved successfully!', 'success');
                if(action === 'final_submit'){
                    setTimeout(function(){
                        window.location.href = '{{ route("workflow.punching.index") }}';
                    }, 1000);
                } else {
                    // Re-enable buttons after draft save
                    $('#btnDraft, #btnSubmit').prop('disabled', false).css('opacity', '1');
                    $btn.text(origText);
                }
            } else {
                showAlert(res.message || 'Save failed.', 'error');
                $('#btnDraft, #btnSubmit').prop('disabled', false).css('opacity', '1');
                $btn.text(origText);
            }
        }).fail(function(xhr){
            const msg = xhr.responseJSON?.message || 'An error occurred.';
            showAlert(msg, 'error');
            $('#btnDraft, #btnSubmit').prop('disabled', false).css('opacity', '1');
            $btn.text(origText);
        });
    }

    $('#btnDraft').on('click', function(){ save('draft'); });
    $('#btnSubmit').on('click', function(){ save('final_submit'); });

    // ========== Custom Confirm Modal ==========
    function showConfirmModal(){
        $('#confirmOverlay').css('display','flex');
    }
    function hideConfirmModal(){
        $('#confirmOverlay').css('display','none');
    }
    $('#confirmCancel').on('click', hideConfirmModal);

    $('#confirmOverlay').on('click', function(e){
        if(e.target === this) hideConfirmModal();
    });

    $('#confirmSubmit').on('click', function(){
        hideConfirmModal();
        doSave('final_submit');
    });

    // ========== Air Travel Form — Employee Select2 & Add/Remove ==========
    if ($('#airEmpBody').length) {
        // Agent Name Select2 with "Other" option
        if ($('#selAirAgent').length) {
            $('#selAirAgent').select2({
                placeholder: 'Select Agent',
                allowClear: true,
                minimumInputLength: 0,
                tags: true, // Allow custom values
                ajax: {
                    url: R.selAgents,
                    dataType: 'json',
                    delay: 200,
                    data: function(p){ return { q: p.term || '', page: p.page || 1, doc_type_id: 51 }; },
                    processResults: function(d){ return { results: d.results, pagination: d.pagination }; }
                }
            }).on('select2:select', function(e){
                var val = e.params.data.id;
                if (val === '__other__') {
                    // Clear selection and show input for manual entry
                    $(this).val(null).trigger('change');
                    $('#airAgentInput').show().focus().prop('required', true);
                    $(this).hide();
                } else {
                    $('#airAgentInput').val(val).hide().prop('required', false);
                }
            });
        }

        // Airline Select2 with "Other" option
        if ($('#selAirline').length) {
            $('#selAirline').select2({
                placeholder: 'Select Airline',
                allowClear: true,
                minimumInputLength: 0,
                tags: true, // Allow custom values
                ajax: {
                    url: R.selAirlines,
                    dataType: 'json',
                    delay: 200,
                    data: function(p){ return { q: p.term || '', page: p.page || 1 }; },
                    processResults: function(d){ return { results: d.results, pagination: d.pagination }; }
                }
            }).on('select2:select', function(e){
                var val = e.params.data.id;
                if (val === '__other__') {
                    // Clear selection and show input for manual entry
                    $(this).val(null).trigger('change');
                    $('#airlineInput').show().focus().prop('required', true);
                    $(this).hide();
                } else {
                    $('#airlineInput').val(val).hide().prop('required', false);
                }
            });
        }

        function initAirEmpSel(sel){
            $(sel).select2({
                placeholder: 'Search Employee',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: R.selEmployees,
                    dataType: 'json',
                    delay: 200,
                    data: function(p){ return { q: p.term || '', page: p.page || 1 }; },
                    processResults: function(d){ return { results: d.results, pagination: d.pagination }; }
                },
                templateResult: function(item){
                    if(item.loading) return item.text;
                    return $('<span>').text(item.text + (item.emp_code ? ' [' + item.emp_code + ']' : ''));
                }
            }).on('select2:select', function(e){
                $(this).closest('tr').find('input[name="EmpCode[]"]').val(e.params.data.emp_code || '');
            }).on('select2:clear', function(){
                $(this).closest('tr').find('input[name="EmpCode[]"]').val('');
            });
        }
        initAirEmpSel('.air-emp-sel');

        // Add/remove employee rows
        $(document).on('click', '#airEmpBody .btn-add-emp', function(){
            var count = $('#airEmpBody tr').length + 1;
            var tr = `<tr>
                <td>${count}</td>
                <td><select name="Employee[]" class="air-emp-sel" style="width:100%"><option value="">Select</option></select></td>
                <td><input type="text" name="EmpCode[]" readonly></td>
                <td><button type="button" class="btn-del-emp">−</button></td>
            </tr>`;
            $('#airEmpBody').append(tr);
            initAirEmpSel('#airEmpBody tr:last .air-emp-sel');
        });
        $(document).on('click', '#airEmpBody .btn-del-emp', function(){
            if ($('#airEmpBody tr').length <= 1) return;
            $(this).closest('tr').remove();
            $('#airEmpBody tr').each(function(i){ $(this).find('td:first').text(i + 1); });
        });

        // Auto-calculate Air total
        function calcAirTotal(){
            var baseFare = parseFloat($('input[name="Base_Fare"]').val()) || 0;
            var gst = parseFloat($('input[name="GST"]').val()) || 0;
            var surcharge = parseFloat($('input[name="Surcharge"]').val()) || 0;
            var cuteCharge = parseFloat($('input[name="Cute_Charge"]').val()) || 0;
            var extraLuggage = parseFloat($('input[name="Extra_Luggage"]').val()) || 0;
            var other = parseFloat($('input[name="Other"]').val()) || 0;
            var total = baseFare + gst + surcharge + cuteCharge + extraLuggage + other;
            $('#grandTotal').val(total > 0 ? total.toFixed(2) : '0.00');
        }
        $(document).on('input', '.calc-trigger', function(){
            if ($('[name="Cute_Charge"]').length) calcAirTotal();
        });
    }

    // ========== Rail Travel Form — Employee Select2 & Add/Remove ==========
    if ($('#railEmpBody').length) {
        // Agent Name Select2 with "Other" option
        if ($('#selRailAgent').length) {
            $('#selRailAgent').select2({
                placeholder: 'Select Agent',
                allowClear: true,
                minimumInputLength: 0,
                tags: true, // Allow custom values
                ajax: {
                    url: R.selAgents,
                    dataType: 'json',
                    delay: 200,
                    data: function(p){ return { q: p.term || '', page: p.page || 1, doc_type_id: 52 }; },
                    processResults: function(d){ return { results: d.results, pagination: d.pagination }; }
                }
            }).on('select2:select', function(e){
                var val = e.params.data.id;
                if (val === '__other__') {
                    // Clear selection and show input for manual entry
                    $(this).val(null).trigger('change');
                    $('#railAgentInput').show().focus().prop('required', false);
                    $(this).hide();
                } else {
                    $('#railAgentInput').val(val).hide().prop('required', false);
                }
            });
        }

        function initRailEmpSel(sel){
            $(sel).select2({
                placeholder: 'Search Employee',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: R.selEmployees,
                    dataType: 'json',
                    delay: 200,
                    data: function(p){ return { q: p.term || '', page: p.page || 1 }; },
                    processResults: function(d){ return { results: d.results, pagination: d.pagination }; }
                },
                templateResult: function(item){
                    if(item.loading) return item.text;
                    return $('<span>').text(item.text + (item.emp_code ? ' [' + item.emp_code + ']' : ''));
                }
            }).on('select2:select', function(e){
                $(this).closest('tr').find('input[name="EmpCode[]"]').val(e.params.data.emp_code || '');
            }).on('select2:clear', function(){
                $(this).closest('tr').find('input[name="EmpCode[]"]').val('');
            });
        }
        initRailEmpSel('.rail-emp-sel');

        // Add/remove employee rows
        $(document).on('click', '#railEmpBody .btn-add-emp', function(){
            var count = $('#railEmpBody tr').length + 1;
            var tr = `<tr>
                <td>${count}</td>
                <td><select name="Employee[]" class="rail-emp-sel" style="width:100%"><option value="">Select</option></select></td>
                <td><input type="text" name="EmpCode[]" readonly></td>
                <td><button type="button" class="btn-del-emp">−</button></td>
            </tr>`;
            $('#railEmpBody').append(tr);
            initRailEmpSel('#railEmpBody tr:last .rail-emp-sel');
        });
        $(document).on('click', '#railEmpBody .btn-del-emp', function(){
            if ($('#railEmpBody tr').length <= 1) return;
            $(this).closest('tr').remove();
            $('#railEmpBody tr').each(function(i){ $(this).find('td:first').text(i + 1); });
        });

        // Auto-calculate Rail total
        function calcRailTotal(){
            var baseFare = parseFloat($('input[name="Base_Fare"]').val()) || 0;
            var gst = parseFloat($('input[name="GST"]').val()) || 0;
            var surcharge = parseFloat($('input[name="Surcharge"]').val()) || 0;
            var other = parseFloat($('input[name="Other"]').val()) || 0;
            var total = baseFare + gst + surcharge + other;
            $('#grandTotal').val(total > 0 ? total.toFixed(2) : '0.00');
        }
        $(document).on('input', '.calc-trigger', function(){
            if ($('[name="Train_Number"]').length) calcRailTotal();
        });
    }

    // ========== Ticket Cancellation Form ==========
    if ($('#ticketCancelItemsBody').length) {
        // Agent Name Select2
        $('#selTicketCancelAgent').select2({
            placeholder: 'Select Agent',
            allowClear: true,
            ajax: {
                url: R.selAgents,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term,
                        page: params.page || 1,
                        doc_type_id: {{ $scanData->DocType_Id }}
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results,
                        pagination: data.pagination
                    };
                }
            }
        });

        // Employee Select2 initialization
        function initTicketCancelEmpSel(sel){
            $(sel).select2({
                placeholder: 'Search Employee',
                allowClear: true,
                ajax: {
                    url: R.selEmployees,
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term,
                            page: params.page || 1
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results,
                            pagination: data.pagination
                        };
                    }
                },
                templateResult: function(item){
                    if(item.loading) return item.text;
                    return $('<span>').text(item.text + (item.emp_code ? ' [' + item.emp_code + ']' : ''));
                }
            });
        }
        initTicketCancelEmpSel('.ticket-cancel-emp-sel');

        // Add/remove rows
        $(document).off('click', '.btn-add-row').on('click', '.btn-add-row', function(){
            if(!$('#ticketCancelItemsBody').length) return; // Only for ticket cancellation
            var count = $('#ticketCancelItemsBody tr').length + 1;
            var tr = `<tr>
                <td>${count}</td>
                <td><select name="Employee[]" class="ticket-cancel-emp-sel" style="width:100%" required><option value="">Select</option></select></td>
                <td><input type="text" name="PNR[]" required></td>
                <td><input type="text" name="Amount[]" class="calc-trigger" inputmode="decimal" required></td>
                <td><button type="button" class="btn-del-row">−</button></td>
            </tr>`;
            $('#ticketCancelItemsBody').append(tr);
            initTicketCancelEmpSel('#ticketCancelItemsBody tr:last .ticket-cancel-emp-sel');
        });
        $(document).on('click', '.btn-del-row', function(){
            if(!$('#ticketCancelItemsBody').length) return; // Only for ticket cancellation
            if ($('#ticketCancelItemsBody tr').length <= 1) return;
            $(this).closest('tr').remove();
            $('#ticketCancelItemsBody tr').each(function(i){ $(this).find('td:first').text(i + 1); });
            calcTicketCancelTotal();
        });

        // Auto-calculate totals
        function calcTicketCancelTotal(){
            var subTotal = parseFloat($('#ticketCancelSubTotal').val()) || 0;
            var other = parseFloat($('#ticketCancelOther').val()) || 0;
            var refund = parseFloat($('#ticketCancelRefund').val()) || 0;
            var grandTotal = subTotal + other - refund;
            $('#ticketCancelGrandTotal').val(grandTotal > 0 ? grandTotal.toFixed(2) : '0.00');
        }
        $(document).on('input', '#ticketCancelSubTotal, #ticketCancelOther, #ticketCancelRefund', calcTicketCancelTotal);
        
        // Initial calculation
        calcTicketCancelTotal();
    }

    // ========== History Offcanvas ==========
    $('#btnHistory').on('click', function(){
        openHistory();
    });

    @if($viewMode && $canApprove)
    // ========== Approve/Reject Actions ==========
    let currentAction = null;
    
    $('#btnApprove').on('click', function(){
        currentAction = 'approve';
        $('#approvalModalTitle').text('Approve Scan').css('color', '#7f1d1d');
        $('#remarkRequired').hide();
        $('#editPermissionSection').hide();
        $('#approvalRemark').attr('placeholder', 'Enter approval remark (optional)...');
        $('#approvalModalConfirm').css('background', '#7f1d1d').text('Approve');
        $('#approvalOverlay').css('display', 'flex');
        $('#approvalRemark').val('').focus();
    });

    $('#btnReject').on('click', function(){
        currentAction = 'reject';
        $('#approvalModalTitle').text('Reject Scan').css('color', '#dc2626');
        $('#remarkRequired').show();
        $('#editPermissionSection').show();
        $('#approvalRemark').attr('placeholder', 'Enter rejection reason (required)...');
        $('#approvalModalConfirm').css('background', '#dc2626').text('Reject');
        $('#approvalOverlay').css('display', 'flex');
        $('#approvalRemark').val('').focus();
        $('#editPermissionCheck').prop('checked', false);
    });

    $('#approvalModalCancel').on('click', function(){
        $('#approvalOverlay').hide();
        currentAction = null;
    });

    $('#approvalModalConfirm').on('click', function(){
        const remark = $('#approvalRemark').val().trim();
        const editPermission = $('#editPermissionCheck').is(':checked') ? 'Y' : 'N';
        
        // Validation: reject requires remark
        if(currentAction === 'reject' && !remark){
            showApprovalAlert('Rejection remark is required.', 'error');
            return;
        }

        // Disable button and show spinner
        const $btn = $(this);
        const originalText = $btn.text();
        $btn.prop('disabled', true).html('<span class="spinner"></span> Processing...');

        const url = currentAction === 'approve' 
            ? `/workflow/punch-approval/{{ $scanData->Scan_Id }}/approve`
            : `/workflow/punch-approval/{{ $scanData->Scan_Id }}/reject`;

        $.ajax({
            url: url,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF },
            data: currentAction === 'approve' 
                ? { remark: remark }
                : { remark: remark, edit_permission: editPermission },
            dataType: 'json'
        }).done(function(res){
            if(res.success){
                showApprovalAlert(res.message, 'success');
                // Set flag in sessionStorage to trigger refresh on previous page
                sessionStorage.setItem('refreshPunchApproval', 'true');
                console.log('Set refreshPunchApproval flag');
                setTimeout(function(){
                    window.history.back();
                }, 1500);
            } else {
                showApprovalAlert(res.message || 'Operation failed.', 'error');
                $btn.prop('disabled', false).text(originalText);
            }
        }).fail(function(xhr){
            let msg = 'An error occurred. Please try again.';
            if(xhr.responseJSON && xhr.responseJSON.message){
                msg = xhr.responseJSON.message;
            }
            showApprovalAlert(msg, 'error');
            $btn.prop('disabled', false).text(originalText);
        });
    });

    function showApprovalAlert(message, type){
        const $box = $('#approvalAlertBox');
        $box.removeClass('alert-success alert-error');
        if(type === 'success'){
            $box.addClass('alert-success').css({background: '#dcfce7', color: '#166534', border: '1px solid #bbf7d0'});
        } else {
            $box.addClass('alert-error').css({background: '#fee2e2', color: '#991b1b', border: '1px solid #fecaca'});
        }
        $box.text(message).show();
    }
    @endif
});

function openHistory(){
    $('#historyOverlay').show();
    $('#historyPanel').css('right', '0');
    // Load history via AJAX
    $.ajax({
        url: `/workflow/punching/entry/{{ $scanData->Scan_Id }}/history`,
        method: 'GET',
        dataType: 'json'
    }).done(function(res){
        if(res.html){
            $('#historyContent').html(res.html);
        }
    }).fail(function(){
        $('#historyContent').html('<div style="text-align:center;color:#b91c1c;font-size:.7rem;padding:2rem 0">Failed to load history.</div>');
    });
}

function closeHistory(){
    $('#historyPanel').css('right', '-380px');
    $('#historyOverlay').hide();
}

</script>
@endpush
