@extends('layouts.app')
@section('title', 'Company Scanning - ' . $company->name)
@section('page-title', 'Company Scanning - ' . $company->name)
@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" defer></script>
    <style>
        /* Reuse styles from direct-scan */
        .select2-container .select2-selection--single {
            height: 34px !important;
            border: 1px solid #d6d3d1 !important;
            border-radius: 0.5rem !important;
            background: #fafaf9 !important;
            padding: 0 !important
        }
        .select2-container .select2-selection--single .select2-selection__rendered {
            padding: 0 0 0 12px !important;
            line-height: 34px !important;
            font-size: .75rem !important;
            color: #292524 !important
        }
        .select2-container .select2-selection--single .select2-selection__arrow {
            height: 34px !important;
            right: 8px !important
        }
        .select2-container--default .select2-results__option {
            font-size: .75rem;
            padding: .4rem .75rem
        }
        .select2-container--default .select2-results__option--highlighted {
            background: #7f1d1d;
            color: #fff
        }
        .select2-dropdown {
            border: 1px solid #d6d3d1;
            border-radius: .5rem;
            box-shadow: 0 4px 16px rgba(0,0,0,.08)
        }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: .18rem .55rem;
            border-radius: 9999px;
            font-size: .62rem;
            font-weight: 600;
            white-space: nowrap
        }
        .badge-approved {
            background: #dcfce7;
            color: #15803d
        }
        .badge-pending {
            background: #fef9c3;
            color: #a16207
        }
        .badge-rejected {
            background: #fee2e2;
            color: #b91c1c
        }
        .tabs {
            display: flex;
            gap: .25rem;
            border-bottom: 2px solid #e7e5e4;
            padding: 0 1.25rem;
            background: #fff
        }
        .tab-btn {
            padding: .625rem 1rem;
            font-size: .72rem;
            font-weight: 600;
            color: #78716c;
            background: transparent;
            border: none;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            cursor: pointer;
            transition: all .15s;
            position: relative
        }
        .tab-btn:hover {
            color: #292524;
            background: #fafaf9
        }
        .tab-btn.active {
            color: #7f1d1d;
            border-bottom-color: #7f1d1d
        }
        .tab-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.25rem;
            height: 1.25rem;
            padding: 0 .35rem;
            border-radius: 9999px;
            background: #e7e5e4;
            color: #57534e;
            font-size: .6rem;
            font-weight: 700;
            margin-left: .35rem
        }
        .tab-btn.active .tab-badge {
            background: #7f1d1d;
            color: #fff
        }
        .filter-bar {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .875rem 1.25rem;
            background: #fafaf9;
            border-bottom: 1px solid #e7e5e4;
            flex-wrap: wrap
        }
        .filter-input, .filter-select {
            height: 2rem;
            padding: 0 .65rem;
            font-size: .72rem;
            border: 1px solid #d6d3d1;
            border-radius: .375rem;
            background: #fff;
            outline: none;
            color: #292524
        }
        .filter-input:focus, .filter-select:focus {
            border-color: #7f1d1d;
            box-shadow: 0 0 0 3px rgba(127, 29, 29, .08)
        }
        .filter-btn {
            height: 2rem;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: .72rem;
            font-weight: 600;
            border-radius: .375rem;
            cursor: pointer;
            transition: all .15s
        }
        .filter-btn-primary {
            background: #7f1d1d;
            color: #fff;
            border: none;
            padding: 0 .875rem
        }
        .filter-btn-primary:hover {
            background: #6b1a1a
        }
        .filter-btn-secondary {
            background: #fff;
            color: #57534e;
            border: 1px solid #d6d3d1;
            padding: 0 .75rem
        }
        .filter-btn-secondary:hover {
            background: #f5f5f4
        }
        #scansTable, #pendingNamingTable, #pendingVerifyTable {
            border-collapse: collapse;
            width: 100% !important;
            table-layout: fixed
        }
        #scansTable thead th, #pendingNamingTable thead th, #pendingVerifyTable thead th {
            background: #fafaf9;
            color: #78716c;
            font-size: .65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding: .55rem .75rem;
            border-bottom: 2px solid #e7e5e4;
            white-space: nowrap;
            text-align: left
        }
        #scansTable tbody td, #pendingNamingTable tbody td, #pendingVerifyTable tbody td {
            padding: .55rem .75rem;
            border-bottom: 1px solid #f0eeec;
            color: #292524;
            vertical-align: middle;
            font-size: .73rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap
        }
        #scansTable tbody tr:hover td, #pendingNamingTable tbody tr:hover td, #pendingVerifyTable tbody tr:hover td {
            background: #fafaf9
        }
        .dt-ctrl-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .75rem 1.25rem;
            flex-wrap: wrap
        }
        .dt-length-sel {
            height: 1.9rem;
            padding: 0 1.4rem 0 .5rem;
            font-size: .72rem;
            border: 1px solid #d6d3d1;
            border-radius: .375rem;
            background: #fafaf9;
            color: #292524;
            appearance: none;
            cursor: pointer
        }
        .dt-search-input {
            height: 1.9rem;
            padding: 0 .65rem 0 1.85rem;
            font-size: .72rem;
            border: 1px solid #d6d3d1;
            border-radius: .375rem;
            background: #fafaf9;
            outline: none;
            width: 170px;
            color: #292524
        }
        .dt-search-input:focus {
            border-color: #7f1d1d;
            box-shadow: 0 0 0 3px rgba(127, 29, 29, .08)
        }
        .modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .55);
            z-index: 300;
            backdrop-filter: blur(2px)
        }
        .modal-backdrop.open {
            display: block
        }
        .modal-container {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 301;
            padding: 1.5rem;
            overflow-y: auto
        }
        .modal-container.open {
            display: flex;
            align-items: center;
            justify-content: center
        }
    </style>
@endpush

@section('content')
<div class="mb-4">
    <a href="{{ route('workflow.super-scanner.index') }}" class="text-xs text-stone-600 hover:text-stone-800 flex items-center gap-1">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Summary
    </a>
</div>

<div class="bg-white border border-stone-200 rounded-xl flex flex-col">
    {{-- Header --}}
    <div class="px-5 py-3.5 border-b border-stone-100 flex items-center justify-between gap-3 flex-shrink-0 flex-wrap">
        <div>
            <h2 class="text-sm font-semibold text-stone-800">{{ $company->name }} - Scanning Management</h2>
            <p class="text-xs text-stone-400 mt-0.5">Manage all scanning operations for this company</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="tabs">
        <button class="tab-btn active" data-tab="scans">All Scans<span class="tab-badge" id="badge-all">0</span></button>
        <button class="tab-btn" data-tab="scans" data-filter="pending">Pending<span class="tab-badge" id="badge-pending">0</span></button>
        <button class="tab-btn" data-tab="scans" data-filter="approved">Approved<span class="tab-badge" id="badge-approved">0</span></button>
        <button class="tab-btn" data-tab="scans" data-filter="rejected">Rejected<span class="tab-badge" id="badge-rejected">0</span></button>
        <button class="tab-btn" data-tab="scan-document">Direct Scan</button>
        <button class="tab-btn" data-tab="pending-naming">Pending Naming<span class="tab-badge" id="badge-pending-naming">0</span></button>
        <button class="tab-btn" data-tab="pending-verify">Pending Verification<span class="tab-badge" id="badge-pending-verify">0</span></button>
    </div>

    {{-- Tab Content: All Scans --}}
    <div class="tab-content" id="tab-scans">
        <div class="filter-bar">
            <div class="flex items-center gap-2">
                <label class="text-[11px] font-semibold text-stone-600 uppercase">Scanned By</label>
                <select id="filterScannedBy" class="filter-select" style="width:180px"></select>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-[11px] font-semibold text-stone-600 uppercase">From</label>
                <input type="date" id="filterFromDate" class="filter-input" style="width:140px">
            </div>
            <div class="flex items-center gap-2">
                <label class="text-[11px] font-semibold text-stone-600 uppercase">To</label>
                <input type="date" id="filterToDate" class="filter-input" style="width:140px">
            </div>
            <button id="btnApplyFilters" class="filter-btn filter-btn-primary">Apply</button>
            <button id="btnResetFilters" class="filter-btn filter-btn-secondary">Reset</button>
        </div>
        <div class="dt-ctrl-bar border-b border-stone-100">
            <div class="flex items-center gap-2 text-xs text-stone-500">
                <span>Show</span>
                <select class="dt-length-sel" id="dtLength">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span>entries</span>
            </div>
            <div class="dt-search-wrap">
                <input type="text" class="dt-search-input" id="dtSearch" placeholder="Search…">
            </div>
        </div>
        <div class="overflow-x-auto">
            <table id="scansTable" style="width:100%">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Location</th>
                        <th>File</th>
                        <th style="width:100px">Scan Date</th>
                        <th style="width:100px">Status</th>
                        <th>Scanned By</th>
                        <th>Approver</th>
                        <th style="width:80px">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="dt-ctrl-bar border-t border-stone-100">
            <div id="dtInfo"></div>
            <div id="dtPaginate"></div>
        </div>
    </div>

    {{-- Tab Content: Direct Scan --}}
    <div class="tab-content" id="tab-scan-document" style="display:none">
        <div class="p-5">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                {{-- Left: Scan Form --}}
                <div class="bg-stone-50 border border-stone-200 rounded-xl p-5">
                    <h3 class="text-sm font-semibold text-stone-800 mb-1">Scan Document</h3>
                    <p class="text-xs text-stone-400 mb-4">Upload a new scan for {{ $company->name }}</p>
                    
                    <div id="scanAlert" class="hidden px-3.5 py-2.5 rounded-xl border text-xs mb-4"></div>
                    
                    <form id="scanForm" class="flex flex-col gap-3.5">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-stone-600 mb-1">Location <span class="text-red-500">*</span></label>
                            <select id="sel-location" name="location" style="width:100%"></select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-stone-600 mb-1">Bill Approver <span class="text-red-500">*</span></label>
                            <select id="sel-approver" name="bill_approver" style="width:100%" disabled></select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-stone-600 mb-1">Bill Date</label>
                                <input type="date" id="bill-date" name="bill_date" class="h-9 px-3 text-xs border border-stone-300 rounded-lg bg-white focus:border-stone-800 outline-none transition-colors w-full">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-stone-600 mb-1">Bill No</label>
                                <input type="text" id="bill-no" name="bill_no" placeholder="Enter bill number" class="h-9 px-3 text-xs border border-stone-300 rounded-lg bg-white focus:border-stone-800 outline-none transition-colors w-full">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-stone-600 mb-1">Vendor Name</label>
                            <select id="sel-vendor" name="vendor_id" style="width:100%"></select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-stone-600 mb-1">Document Name <span class="text-red-500">*</span></label>
                            <input type="text" id="document-name" name="document_name" placeholder="Auto-generated document name" class="h-9 px-3 text-xs border border-stone-300 rounded-lg bg-white focus:border-stone-800 outline-none transition-colors w-full" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-stone-600 mb-1">File <span class="text-red-500">*</span></label>
                            <input type="file" id="mainFile" name="main_file" accept=".jpg,.jpeg,.png,.pdf" class="block w-full text-xs text-stone-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-stone-800 file:text-white hover:file:bg-stone-900" required>
                            <p class="text-[10px] text-stone-400 mt-1">JPG, PNG, PDF — max 15 MB</p>
                        </div>
                        <button type="submit" id="scanBtn" class="w-full h-9 bg-stone-800 hover:bg-stone-900 text-white text-xs font-semibold rounded-lg transition-colors flex items-center justify-center gap-2 disabled:opacity-50 mt-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            Scan Document
                        </button>
                    </form>
                </div>

                {{-- Right: Recent Scans --}}
                <div class="lg:col-span-2 bg-white border border-stone-200 rounded-xl flex flex-col">
                    <div class="px-5 py-3.5 border-b border-stone-100 flex items-center justify-between gap-3 flex-shrink-0">
                        <h3 class="text-sm font-semibold text-stone-800">Recent Scans</h3>
                        <span class="text-xs text-stone-400" id="recentScansCount">0 scans</span>
                    </div>
                    <div class="dt-ctrl-bar border-b border-stone-100">
                        <div class="flex items-center gap-2 text-xs text-stone-500">
                            <span>Show</span>
                            <select class="dt-length-sel" id="dtLengthRecent">
                                <option value="10">10</option>
                                <option value="25">25</option>
                            </select>
                            <span>entries</span>
                        </div>
                        <div class="dt-search-wrap">
                            <input type="text" class="dt-search-input" id="dtSearchRecent" placeholder="Search…">
                        </div>
                    </div>
                    <div class="overflow-x-auto flex-1">
                        <table id="recentScansTable" style="width:100%">
                            <thead>
                                <tr>
                                    <th style="width:40px">#</th>
                                    <th>Location</th>
                                    <th>File</th>
                                    <th style="width:100px">Scan Date</th>
                                    <th style="width:80px">Status</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    <div class="dt-ctrl-bar border-t border-stone-100">
                        <div id="dtInfoRecent"></div>
                        <div id="dtPaginateRecent"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab Content: Pending Naming --}}
    <div class="tab-content" id="tab-pending-naming" style="display:none">
        <div class="filter-bar">
            <div class="flex items-center gap-2">
                <label class="text-[11px] font-semibold text-stone-600 uppercase">From</label>
                <input type="date" id="filterPNFromDate" class="filter-input" style="width:140px">
            </div>
            <div class="flex items-center gap-2">
                <label class="text-[11px] font-semibold text-stone-600 uppercase">To</label>
                <input type="date" id="filterPNToDate" class="filter-input" style="width:140px">
            </div>
            <button id="btnApplyPN" class="filter-btn filter-btn-primary">Apply</button>
            <button id="btnResetPN" class="filter-btn filter-btn-secondary">Reset</button>
        </div>
        <div class="dt-ctrl-bar border-b border-stone-100">
            <div class="flex items-center gap-2 text-xs text-stone-500">
                <span>Show</span>
                <select class="dt-length-sel" id="dtLengthPN">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
                <span>entries</span>
            </div>
            <div class="dt-search-wrap">
                <input type="text" class="dt-search-input" id="dtSearchPN" placeholder="Search…">
            </div>
        </div>
        <div class="overflow-x-auto">
            <table id="pendingNamingTable" style="width:100%">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Location</th>
                        <th>File</th>
                        <th style="width:100px">Scan Date</th>
                        <th>Scanned By</th>
                        <th style="width:80px">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="dt-ctrl-bar border-t border-stone-100">
            <div id="dtInfoPN"></div>
            <div id="dtPaginatePN"></div>
        </div>
    </div>

    {{-- Tab Content: Pending Verification --}}
    <div class="tab-content" id="tab-pending-verify" style="display:none">
        <div class="filter-bar">
            <div class="flex items-center gap-2">
                <label class="text-[11px] font-semibold text-stone-600 uppercase">From</label>
                <input type="date" id="filterPVFromDate" class="filter-input" style="width:140px">
            </div>
            <div class="flex items-center gap-2">
                <label class="text-[11px] font-semibold text-stone-600 uppercase">To</label>
                <input type="date" id="filterPVToDate" class="filter-input" style="width:140px">
            </div>
            <button id="btnApplyPV" class="filter-btn filter-btn-primary">Apply</button>
            <button id="btnResetPV" class="filter-btn filter-btn-secondary">Reset</button>
        </div>
        <div class="dt-ctrl-bar border-b border-stone-100">
            <div class="flex items-center gap-2 text-xs text-stone-500">
                <span>Show</span>
                <select class="dt-length-sel" id="dtLengthPV">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
                <span>entries</span>
            </div>
            <div class="dt-search-wrap">
                <input type="text" class="dt-search-input" id="dtSearchPV" placeholder="Search…">
            </div>
        </div>
        <div class="overflow-x-auto">
            <table id="pendingVerifyTable" style="width:100%">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Location</th>
                        <th>File</th>
                        <th>Document Name</th>
                        <th style="width:100px">Scan Date</th>
                        <th>Scanned By</th>
                        <th style="width:80px">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="dt-ctrl-bar border-t border-stone-100">
            <div id="dtInfoPV"></div>
            <div id="dtPaginatePV"></div>
        </div>
    </div>
</div>

{{-- Verify Document Modal --}}
<div class="modal-backdrop" id="verifyModalBackdrop"></div>
<div class="modal-container" id="verifyModal">
    <div class="bg-white rounded-xl p-5 max-w-md">
        <h3 class="text-sm font-semibold text-stone-800 mb-3">Verify Document</h3>
        <div id="verifyAlert" class="hidden px-3 py-2 rounded-lg border text-xs mb-3"></div>
        <form id="verifyForm">
            <input type="hidden" id="verify-scan-id">
            <div class="mb-4">
                <label class="block text-xs font-medium text-stone-600 mb-1">Document Received Date <span class="text-red-500">*</span></label>
                <input type="date" id="document-received-date" class="h-9 px-3 text-xs border border-stone-300 rounded-lg bg-white focus:border-stone-800 outline-none transition-colors w-full" required>
            </div>
            <div class="flex items-center gap-2 justify-end">
                <button type="button" id="btnCancelVerify" class="h-9 px-4 border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-medium rounded-lg transition-colors">Cancel</button>
                <button type="submit" class="h-9 px-4 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded-lg transition-colors">Verify</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function () {
    const COMPANY_ID = {{ $company->id }};
    const CSRF = $('meta[name="csrf-token"]').attr('content');
    const R = {
        scansData:       `/workflow/super-scanner/company/${COMPANY_ID}/scans-data`,
        pendingNaming:   `/workflow/super-scanner/company/${COMPANY_ID}/pending-naming`,
        pendingVerify:   `/workflow/super-scanner/company/${COMPANY_ID}/pending-verify`,
        tabCounts:       `/workflow/super-scanner/company/${COMPANY_ID}/tab-counts`,
        scan:            `/workflow/super-scanner/company/${COMPANY_ID}/scan`,
        verifyDocument:  `/workflow/super-scanner/company/${COMPANY_ID}/verify-document`,
        locations:       '{{ route("workflow.super-scanner.select.locations") }}',
        billApprovers:   '{{ route("workflow.super-scanner.select.bill-approvers") }}',
        vendors:         '{{ route("workflow.super-scanner.select.vendors") }}',
        users:           '{{ route("workflow.super-scanner.select.users") }}',
    };

    let currentTab = 'scans';
    let currentFilter = '';
    let scansFilters = { scanned_by: '', from_date: '', to_date: '' };
    let pnFilters = { from_date: '', to_date: '' };
    let pvFilters = { from_date: '', to_date: '' };

    // ── Tabs ──────────────────────────────────────────────────────────────────
    $('.tab-btn').on('click', function () {
        const $btn = $(this);
        const tab = $btn.data('tab');
        const filter = $btn.data('filter') || '';
        $('.tab-btn').removeClass('active');
        $btn.addClass('active');
        $('.tab-content').hide();
        $(`#tab-${tab}`).show();
        currentTab = tab;
        currentFilter = filter;
        if (tab === 'scans') {
            scansTable.ajax.reload();
        } else if (tab === 'pending-naming') {
            pendingNamingTable.ajax.reload();
        } else if (tab === 'pending-verify') {
            pendingVerifyTable.ajax.reload();
        }
    });

    // ── Select2 helpers ───────────────────────────────────────────────────────
    function s2(selector, url, extra) {
        $(selector).select2({
            placeholder: 'Select…',
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url, dataType: 'json', delay: 250,
                data: (p) => Object.assign({ q: p.term || '', page: p.page || 1 }, extra || {}),
                processResults: (d) => ({ results: d.results, pagination: d.pagination }),
                cache: true
            }
        });
    }

    s2('#sel-location', R.locations);
    s2('#sel-vendor', R.vendors);
    s2('#filterScannedBy', R.users, { company_id: COMPANY_ID });

    $('#sel-location').on('change.select2', function () {
        const loc = $(this).val();
        const $a = $('#sel-approver');
        if ($a.data('select2')) $a.select2('destroy');
        $a.empty().append('<option value="">Select Approver</option>');
        if (loc) {
            $a.select2({
                placeholder: 'Select Approver',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: R.billApprovers, dataType: 'json', delay: 250,
                    data: (p) => ({ q: p.term || '', page: p.page || 1, location_id: loc }),
                    processResults: (d) => ({ results: d.results, pagination: d.pagination })
                }
            }).prop('disabled', false);
        } else {
            s2('#sel-approver', R.billApprovers);
            $a.prop('disabled', true);
        }
    });

    // ── Document name auto-generation ─────────────────────────────────────────
    function generateDocumentName() {
        const billDate = $('#bill-date').val();
        const vendorData = $('#sel-vendor').select2('data')[0];
        const vendorText = vendorData?.firm_name_clean || '';
        const billNo = $('#bill-no').val().trim();
        let docName = '';
        if (billDate || vendorText || billNo) {
            const dateStr = billDate ? billDate.replace(/-/g, '').substring(2) : '';
            const vendorClean = vendorText ? vendorText.substring(0, 30) : '';
            const billClean = billNo ? billNo.replace(/[^A-Za-z0-9]/g, '') : '';
            const parts = [dateStr, vendorClean, billClean].filter(p => p);
            docName = parts.join('_');
        }
        $('#document-name').val(docName);
    }

    $('#bill-date, #bill-no').on('input change', generateDocumentName);
    $('#sel-vendor').on('change.select2', generateDocumentName);

    // ── Tab Counts ────────────────────────────────────────────────────────────
    function loadTabCounts() {
        $.getJSON(R.tabCounts, { from_date: scansFilters.from_date, to_date: scansFilters.to_date })
            .done(t => {
                $('#badge-all').text(t.all || 0);
                $('#badge-pending').text(t.pending || 0);
                $('#badge-approved').text(t.approved || 0);
                $('#badge-rejected').text(t.rejected || 0);
                $('#badge-pending-naming').text(t.pending_naming || 0);
                $('#badge-pending-verify').text(t.pending_verify || 0);
            });
    }
    loadTabCounts();

    // ── All Scans DataTable ───────────────────────────────────────────────────
    const scansTable = $('#scansTable').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: R.scansData,
            type: 'GET',
            data: d => Object.assign(d, scansFilters, { tab: currentFilter || 'all' }),
        },
        order: [[3, 'desc']],
        pageLength: 25,
        dom: 'rt',
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'location_name', defaultContent: '—' },
            { data: 'File', defaultContent: '—' },
            { data: 'Temp_Scan_Date', defaultContent: '—' },
            { data: 'status_badge', orderable: false },
            { data: 'scanned_by', defaultContent: '—' },
            { data: 'approver_name', defaultContent: '—' },
            { data: 'actions', orderable: false, searchable: false },
        ],
        drawCallback: function () {
            $('#scansTable_wrapper .dataTables_paginate').appendTo('#dtPaginate');
            $('#scansTable_wrapper .dataTables_info').appendTo('#dtInfo');
            loadTabCounts();
        },
    });

    $('#dtLength').on('change', function () { scansTable.page.len(+$(this).val()).draw(); });
    let st;
    $('#dtSearch').on('input', function () { clearTimeout(st); const v = $(this).val(); st = setTimeout(() => scansTable.search(v).draw(), 350); });

    $('#btnApplyFilters').on('click', function () {
        scansFilters.scanned_by = $('#filterScannedBy').val();
        scansFilters.from_date = $('#filterFromDate').val();
        scansFilters.to_date = $('#filterToDate').val();
        scansTable.ajax.reload();
    });
    $('#btnResetFilters').on('click', function () {
        $('#filterScannedBy, #filterFromDate, #filterToDate').val('');
        scansFilters = { scanned_by: '', from_date: '', to_date: '' };
        scansTable.ajax.reload();
    });

    // ── Pending Naming DataTable ──────────────────────────────────────────────
    const pendingNamingTable = $('#pendingNamingTable').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: R.pendingNaming,
            type: 'GET',
            data: d => Object.assign(d, pnFilters),
        },
        order: [[3, 'desc']],
        pageLength: 25,
        dom: 'rt',
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'location_name', defaultContent: '—' },
            { data: 'File', defaultContent: '—' },
            { data: 'Temp_Scan_Date', defaultContent: '—' },
            { data: 'scanned_by', defaultContent: '—' },
            { data: 'actions', orderable: false, searchable: false },
        ],
        drawCallback: function () {
            $('#pendingNamingTable_wrapper .dataTables_paginate').appendTo('#dtPaginatePN');
            $('#pendingNamingTable_wrapper .dataTables_info').appendTo('#dtInfoPN');
        },
    });

    $('#dtLengthPN').on('change', function () { pendingNamingTable.page.len(+$(this).val()).draw(); });
    let stPN;
    $('#dtSearchPN').on('input', function () { clearTimeout(stPN); const v = $(this).val(); stPN = setTimeout(() => pendingNamingTable.search(v).draw(), 350); });

    $('#btnApplyPN').on('click', function () {
        pnFilters.from_date = $('#filterPNFromDate').val();
        pnFilters.to_date = $('#filterPNToDate').val();
        pendingNamingTable.ajax.reload();
    });
    $('#btnResetPN').on('click', function () {
        $('#filterPNFromDate, #filterPNToDate').val('');
        pnFilters = { from_date: '', to_date: '' };
        pendingNamingTable.ajax.reload();
    });

    // ── Pending Verification DataTable ────────────────────────────────────────
    const pendingVerifyTable = $('#pendingVerifyTable').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: R.pendingVerify,
            type: 'GET',
            data: d => Object.assign(d, pvFilters),
        },
        order: [[4, 'desc']],
        pageLength: 25,
        dom: 'rt',
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'location_name', defaultContent: '—' },
            { data: 'File', defaultContent: '—' },
            { data: 'Document_name', defaultContent: '—' },
            { data: 'Temp_Scan_Date', defaultContent: '—' },
            { data: 'scanned_by', defaultContent: '—' },
            { data: 'actions', orderable: false, searchable: false, render: (d, t, r) => `<button class="text-xs px-2 py-1 bg-green-600 text-white rounded hover:bg-green-700" onclick="openVerifyModal(${r.Scan_Id})">Verify</button>` },
        ],
        drawCallback: function () {
            $('#pendingVerifyTable_wrapper .dataTables_paginate').appendTo('#dtPaginatePV');
            $('#pendingVerifyTable_wrapper .dataTables_info').appendTo('#dtInfoPV');
        },
    });

    $('#dtLengthPV').on('change', function () { pendingVerifyTable.page.len(+$(this).val()).draw(); });
    let stPV;
    $('#dtSearchPV').on('input', function () { clearTimeout(stPV); const v = $(this).val(); stPV = setTimeout(() => pendingVerifyTable.search(v).draw(), 350); });

    $('#btnApplyPV').on('click', function () {
        pvFilters.from_date = $('#filterPVFromDate').val();
        pvFilters.to_date = $('#filterPVToDate').val();
        pendingVerifyTable.ajax.reload();
    });
    $('#btnResetPV').on('click', function () {
        $('#filterPVFromDate, #filterPVToDate').val('');
        pvFilters = { from_date: '', to_date: '' };
        pendingVerifyTable.ajax.reload();
    });

    // ── Scan Document Form ────────────────────────────────────────────────────
    $('#scanForm').on('submit', function (e) {
        e.preventDefault();
        const fd = new FormData(this);
        const $btn = $('#scanBtn');
        $btn.prop('disabled', true).text('Scanning...');

        $.ajax({
            url: R.scan,
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': CSRF },
            success: (res) => {
                showAlert('#scanAlert', 'success', 'Document scanned successfully!');
                $('#scanForm')[0].reset();
                if ($('#sel-location').data('select2')) $('#sel-location').val(null).trigger('change');
                if ($('#sel-approver').data('select2')) $('#sel-approver').val(null).trigger('change');
                if ($('#sel-vendor').data('select2')) $('#sel-vendor').val(null).trigger('change');
                scansTable.ajax.reload();
                recentScansTable.ajax.reload();
                loadTabCounts();
            },
            error: (x) => {
                const msg = x.responseJSON?.message || 'Scan failed';
                showAlert('#scanAlert', 'error', msg);
            },
            complete: () => $btn.prop('disabled', false).html('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg> Scan Document')
        });
    });

    // ── Recent Scans Table (for Direct Scan tab) ──────────────────────────────
    const recentScansTable = $('#recentScansTable').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: R.scansData,
            type: 'GET',
            data: d => Object.assign(d, { tab: 'all', page_length: 10 }),
        },
        order: [[3, 'desc']],
        pageLength: 10,
        dom: 'rt',
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'location_name', defaultContent: '—' },
            { data: 'File', defaultContent: '—', render: (d) => d ? d.substring(0, 20) + (d.length > 20 ? '...' : '') : '—' },
            { data: 'Temp_Scan_Date', defaultContent: '—' },
            { data: 'status_badge', orderable: false },
        ],
        drawCallback: function () {
            $('#recentScansTable_wrapper .dataTables_paginate').appendTo('#dtPaginateRecent');
            $('#recentScansTable_wrapper .dataTables_info').appendTo('#dtInfoRecent');
            const info = this.api().page.info();
            $('#recentScansCount').text(`${info.recordsTotal} scan${info.recordsTotal !== 1 ? 's' : ''}`);
        },
    });

    $('#dtLengthRecent').on('change', function () { recentScansTable.page.len(+$(this).val()).draw(); });
    let stRecent;
    $('#dtSearchRecent').on('input', function () { clearTimeout(stRecent); const v = $(this).val(); stRecent = setTimeout(() => recentScansTable.search(v).draw(), 350); });

    // ── Verify Document Modal ─────────────────────────────────────────────────
    window.openVerifyModal = function (scanId) {
        $('#verify-scan-id').val(scanId);
        $('#document-received-date').val('');
        $('#verifyModalBackdrop, #verifyModal').addClass('open');
    };

    $('#btnCancelVerify, #verifyModalBackdrop').on('click', function () {
        $('#verifyModalBackdrop, #verifyModal').removeClass('open');
    });

    $('#verifyForm').on('submit', function (e) {
        e.preventDefault();
        const scanId = $('#verify-scan-id').val();
        const receivedDate = $('#document-received-date').val();

        $.post(R.verifyDocument, {
            _token: CSRF,
            scan_id: scanId,
            document_received_date: receivedDate
        })
            .done(() => {
                showAlert('#verifyAlert', 'success', 'Document verified successfully!');
                setTimeout(() => {
                    $('#verifyModalBackdrop, #verifyModal').removeClass('open');
                    pendingVerifyTable.ajax.reload();
                    loadTabCounts();
                }, 1000);
            })
            .fail((x) => {
                const msg = x.responseJSON?.message || 'Verification failed';
                showAlert('#verifyAlert', 'error', msg);
            });
    });

    // ── Alert helper ──────────────────────────────────────────────────────────
    function showAlert(selector, type, msg) {
        const $a = $(selector);
        $a.removeClass('hidden border-green-200 bg-green-50 text-green-800 border-red-200 bg-red-50 text-red-800');
        if (type === 'success') {
            $a.addClass('border-green-200 bg-green-50 text-green-800');
        } else {
            $a.addClass('border-red-200 bg-red-50 text-red-800');
        }
        $a.text(msg).removeClass('hidden');
        setTimeout(() => $a.addClass('hidden'), 5000);
    }
});
</script>
@endpush
