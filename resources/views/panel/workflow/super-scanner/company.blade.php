@extends('layouts.app')
@section('title', 'Company Scanning - ' . $company->name)
@section('page-title', 'Company Scanning - ' . $company->name)
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

        s .badge {
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

        .filter-input,
        .filter-select {
            height: 2rem;
            padding: 0 .65rem;
            font-size: .72rem;
            border: 1px solid #d6d3d1;
            border-radius: .375rem;
            background: #fff;
            outline: none;
            color: #292524
        }

        .filter-input:focus,
        .filter-select:focus {
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

        .drop-zone {
            border: 2px dashed #d6d3d1;
            border-radius: .75rem;
            padding: 1.2rem;
            cursor: pointer;
            transition: all .2s;
            text-align: center
        }

        .drop-zone:hover,
        .drop-zone.dragover {
            border-color: #b91c1c;
            background: rgba(185, 28, 28, .04)
        }

        .drop-zone.has-file {
            border-color: #16a34a;
            background: rgba(22, 163, 74, .04)
        }

        .wizard-steps {
            display: flex;
            align-items: center;
            gap: .3rem
        }

        .ws-item {
            display: flex;
            align-items: center;
            gap: .4rem;
            flex-shrink: 0
        }

        .ws-dot {
            width: 1.6rem;
            height: 1.6rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .65rem;
            font-weight: 700;
            transition: all .25s;
            flex-shrink: 0
        }

        .ws-dot.active {
            background: #7f1d1d;
            color: #fff;
            box-shadow: 0 0 0 3px rgba(127, 29, 29, .18)
        }

        .ws-dot.pending {
            background: #e7e5e4;
            color: #a8a29e
        }

        .ws-line {
            flex: 1;
            height: 2px;
            background: #e7e5e4;
            margin: 0 .6rem;
            min-width: 60px
        }

        .ws-label {
            font-size: .68rem;
            font-weight: 500;
            white-space: nowrap
        }

        .ws-label.active {
            color: #7f1d1d
        }

        .ws-label.pending {
            color: #a8a29e
        }

        .ws-dot.done {
            background: #16a34a;
            color: #fff
        }

        .ws-label.done {
            color: #16a34a
        }

        .ws-line.done {
            background: #16a34a
        }

        .upload-progress {
            height: 4px;
            background: #e7e5e4;
            border-radius: 2px;
            overflow: hidden
        }

        .upload-progress-bar {
            height: 100%;
            background: #7f1d1d;
            transition: width .3s;
            width: 0%
        }

        .file-viewer {
            width: 100%;
            background: #1c1917;
            border-radius: .75rem;
            overflow: hidden;
            position: relative;
            min-height: 480px;
            display: flex;
            flex-direction: column
        }

        .file-viewer-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            padding: .5rem .875rem;
            background: rgba(0, 0, 0, .35);
            flex-shrink: 0
        }

        .file-viewer-body {
            flex: 1;
            position: relative;
            min-height: 440px
        }

        .file-viewer-body iframe,
        .file-viewer-body img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            border: none;
            object-fit: contain;
            background: #1c1917
        }

        .viewer-placeholder {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .75rem;
            color: #57534e
        }

        .col-scroll {
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #d6d3d1 transparent
        }

        .col-scroll::-webkit-scrollbar {
            width: 4px
        }

        .col-scroll::-webkit-scrollbar-track {
            background: transparent
        }

        .col-scroll::-webkit-scrollbar-thumb {
            background: #d6d3d1;
            border-radius: 2px
        }

        .sf-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .6rem 1rem;
            border-bottom: 1px solid #f5f5f4
        }

        .sf-row:last-child {
            border-bottom: none
        }

        .step2-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.25rem;
            align-items: start
        }

        @media(min-width:1024px) {
            .step2-grid {
                grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr)
            }
        }

        #scansTable,
        .drop-zone.dragover {
            border-color: #b91c1c;
            background: rgba(185, 28, 28, .04)
        }

        .drop-zone.has-file {
            border-color: #16a34a;
            background: rgba(22, 163, 74, .04)
        }

        #scansTable,
        #pendingNamingTable,
        #pendingVerifyTable {
            border-collapse: collapse;
            width: 100% !important;
            table-layout: fixed
        }

        #scansTable thead th,
        #pendingNamingTable thead th,
        #pendingVerifyTable thead th {
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

        #scansTable tbody td,
        #pendingNamingTable tbody td,
        #pendingVerifyTable tbody td {
            padding: .55rem .75rem;
            border-bottom: 1px solid #f0eeec;
            color: #292524;
            vertical-align: middle;
            font-size: .73rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap
        }

        #scansTable tbody tr:hover td,
        #pendingNamingTable tbody tr:hover td,
        #pendingVerifyTable tbody tr:hover td {
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

        .dt-actions {
            display: flex;
            align-items: center;
            gap: .2rem;
            justify-content: center
        }

        .dt-btn {
            width: 1.65rem;
            height: 1.65rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: .325rem;
            transition: all .15s;
            border: none;
            background: transparent;
            cursor: pointer;
            color: #a8a29e;
            flex-shrink: 0
        }

        .dt-btn.blue:hover {
            background: #eff6ff;
            color: #2563eb
        }

        .dt-btn.green:hover {
            background: #f0fdf4;
            color: #16a34a
        }

        .dt-btn.red:hover {
            background: #fef2f2;
            color: #dc2626
        }
    </style>
@endpush

@section('content')
    <div class="bg-white border border-stone-200 rounded-xl flex flex-col">
        {{-- Header --}}
        <div class="px-5 py-3.5 border-b border-stone-100 flex items-center justify-between gap-3 flex-shrink-0 flex-wrap">
            <div>
                <h2 class="text-sm font-semibold text-stone-800">{{ $company->name }} - Scanning Management</h2>
                <p class="text-xs text-stone-400 mt-0.5">Manage all scanning operations for this company</p>
            </div>
            <a href="{{ route('workflow.super-scanner.index') }}"
                class="h-8 px-3 flex items-center gap-1.5 text-xs font-medium border border-stone-200 rounded-lg text-stone-600 hover:bg-stone-50 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to Summary
            </a>
        </div>

        {{-- Tabs --}}
        <div class="tabs">
            <button class="tab-btn active" data-tab="scan-document">Sacnning File</button>
            <button class="tab-btn" data-tab="scans">All Scans<span class="tab-badge" id="badge-all">0</span></button>
            <button class="tab-btn" data-tab="scans" data-filter="pending">Pending<span class="tab-badge"
                    id="badge-pending">0</span></button>
            <button class="tab-btn" data-tab="scans" data-filter="approved">Approved<span class="tab-badge"
                    id="badge-approved">0</span></button>
            <button class="tab-btn" data-tab="scans" data-filter="rejected">Rejected<span class="tab-badge"
                    id="badge-rejected">0</span></button>
            <button class="tab-btn" data-tab="pending-naming">Pending Naming<span class="tab-badge"
                    id="badge-pending-naming">0</span></button>
            <button class="tab-btn" data-tab="pending-verify">Pending Verification<span class="tab-badge"
                    id="badge-pending-verify">0</span></button>
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
                <button id="btnApplyFilters" class="filter-btn filter-btn-primary"><svg class="w-3.5 h-3.5" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
                        </path>
                    </svg> Apply</button>
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

        {{-- Tab Content: Sacnning File --}}
        <div class="tab-content" id="tab-scan-document" style="display:none">
            <div class="p-5">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    {{-- Left: Scan Form --}}
                    <div class="bg-stone-50 border border-stone-200 rounded-xl p-5">
                        <div class="wizard-steps bg-white rounded-lg border border-stone-100 px-3 py-2.5 mb-4">
                            <div class="ws-item">
                                <div class="ws-dot active">1</div>
                                <span class="ws-label active">Upload Scan</span>
                            </div>
                            <div class="ws-line"></div>
                            <div class="ws-item">
                                <div class="ws-dot pending">2</div>
                                <span class="ws-label pending">Supporting Files</span>
                            </div>
                        </div>

                        <div id="scanAlert" class="hidden px-3.5 py-2.5 rounded-xl border text-xs font-medium mb-4"></div>

                        <form id="scanForm" class="flex flex-col gap-3.5">
                            @csrf
                            <div>
                                <label class="block text-xs font-medium text-stone-600 mb-1">Location <span
                                        class="text-red-500">*</span></label>
                                <select id="sel-location" name="location" style="width:100%"></select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-stone-600 mb-1">Bill Approver <span
                                        class="text-red-500">*</span></label>
                                <select id="sel-approver" name="bill_approver" style="width:100%" disabled></select>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-stone-600 mb-1">Bill Date <span
                                            class="text-red-500">*</span></label>
                                    <input type="date" id="bill-date" onfocus="this.showPicker()"  name="bill_date" required
                                        class="h-9 px-3 text-xs border border-stone-300 rounded-lg bg-white focus:border-stone-800 outline-none transition-colors w-full"
                                        @if(\App\Helpers\BillDateValidator::getCurrentFyRange())
                                            min="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['start'] }}"
                                            max="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['end'] }}"
                                        @endif>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-stone-600 mb-1">Bill No <span
                                            class="text-red-500">*</span></label>
                                    <input type="text" id="bill-no" name="bill_no" placeholder="Enter bill number" required
                                        class="h-9 px-3 text-xs border border-stone-300 rounded-lg bg-white focus:border-stone-800 outline-none transition-colors w-full">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-stone-600 mb-1">Vendor Name <span
                                        class="text-red-500">*</span></label>
                                <select id="sel-vendor" name="vendor_id" style="width:100%" required></select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-stone-600 mb-1">Document Name <span
                                        class="text-red-500">*</span></label>
                                <input type="text" id="document-name" name="document_name"
                                    placeholder="Auto-generated document name"
                                    class="h-9 px-3 text-xs border border-stone-300 rounded-lg bg-white focus:border-stone-800 outline-none transition-colors w-full"
                                    required>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-stone-600 mb-1">File <span
                                        class="text-red-500">*</span></label>
                                <div class="drop-zone" id="dropZone">
                                    <input type="file" id="mainFile" name="main_file" accept=".jpg,.jpeg,.png,.pdf"
                                        class="hidden" required>
                                    <svg class="w-7 h-7 text-stone-300 mx-auto mb-1.5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <p class="text-xs text-stone-500" id="dropLabel">Drag & drop or click</p>
                                    <p class="text-[10px] text-stone-400 mt-0.5">JPG, PNG, PDF — max 15 MB</p>
                                </div>
                            </div>
                            <button type="submit" id="scanBtn"
                                class="w-full h-9 bg-stone-800 hover:bg-stone-900 text-white text-xs font-semibold rounded-lg transition-colors flex items-center justify-center gap-2 disabled:opacity-50 mt-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                Scan Document
                            </button>
                        </form>
                    </div>

                    {{-- Right: Recent Scans --}}
                    <div class="lg:col-span-2 bg-white border border-stone-200 rounded-xl flex flex-col">
                        <div
                            class="px-5 py-3.5 border-b border-stone-100 flex items-center justify-between gap-3 flex-shrink-0">
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

        {{-- Tab Content: Step 2 - Supporting Files --}}
        <div class="tab-content wizard-panel" id="scan-step-2" style="display:none">
            <div class="p-5">
            {{-- Step banner --}}
            <div
                class="bg-white border border-stone-200 rounded-xl px-4 py-3 flex items-center justify-between gap-4 mb-4 flex-wrap">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="flex items-center gap-1.5 shrink-0">
                        <div class="ws-dot done text-[.6rem]">✓</div>
                        <span class="ws-label done text-[.68rem]">Upload Scan</span>
                        <div class="ws-line done w-8"></div>
                        <div class="ws-dot active text-[.65rem]">2</div>
                        <span class="ws-label active text-[.68rem]">Supporting Files</span>
                    </div>
                    <div class="w-px h-5 bg-stone-200 shrink-0 hidden sm:block"></div>
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-7 h-7 flex items-center justify-center bg-red-50 rounded-lg text-red-700 shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-stone-800 leading-tight" id="step2ScanId">—</p>
                            <p class="text-[10px] text-stone-400 leading-tight truncate" id="step2ScanMeta">—</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button id="btnFinalSubmit"
                        class="h-8 px-3 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded-lg transition-colors flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Final Submit
                    </button>
                    <button id="btnBackToScanForm"
                        class="h-8 px-3 border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-medium rounded-lg transition-colors flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Back
                    </button>
                </div>
            </div>
            <div class="step2-grid p-5 pt-0">
                <div class="file-viewer" style="min-height:520px">
                    <div class="file-viewer-toolbar">
                        <span class="text-[10px] font-semibold text-stone-300 uppercase tracking-wide">Scan Preview</span>
                        <a id="viewerOpenLink" href="#" target="_blank"
                            class="text-[10px] text-stone-400 hover:text-white transition-colors flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            Open in new tab
                        </a>
                    </div>
                    <div class="file-viewer-body" id="fileViewerBody">
                        <div class="viewer-placeholder" id="viewerPlaceholder">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-xs">No file loaded</p>
                        </div>
                    </div>
                </div>
                <div class="col-scroll">
                    <div class="bg-white border border-stone-200 rounded-xl p-4 flex flex-col gap-3.5 mb-4">
                        <div class="flex items-center gap-2">
                            <div
                                class="w-6 h-6 flex items-center justify-center bg-stone-100 rounded-md text-stone-500 shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-semibold text-stone-800">Add Supporting File</h3>
                        </div>
                        <div id="supportAlert" class="hidden px-3 py-2 rounded-lg border text-xs font-medium"></div>
                        <form id="supportForm" class="flex flex-col gap-3">
                            @csrf
                            <div>
                                <label class="block text-xs font-medium text-stone-600 mb-1">Document Type</label>
                                <select id="sel-doctype" name="doc_type_id" style="width:100%">
                                    <option value="">— Select —</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-stone-600 mb-1">File <span
                                        class="text-red-500">*</span></label>
                                <div class="drop-zone" id="supportDropZone">
                                    <input type="file" id="supportFile" name="support_file" accept=".jpg,.jpeg,.png,.pdf"
                                        class="hidden">
                                    <svg class="w-6 h-6 text-stone-300 mx-auto mb-1" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <p class="text-xs text-stone-500" id="supportDropLabel">Drag & drop or click</p>
                                    <p class="text-[10px] text-stone-400 mt-0.5">JPG, PNG, PDF — max 15 MB</p>
                                </div>
                            </div>
                            <div id="supportProgressWrap" class="hidden">
                                <div class="upload-progress">
                                    <div class="upload-progress-bar" id="supportProgressBar"></div>
                                </div>
                                <p class="text-[10px] text-stone-400 mt-1" id="supportProgressText">Uploading…</p>
                            </div>
                            <button type="submit" id="supportUploadBtn"
                                class="w-full h-9 bg-stone-800 hover:bg-stone-900 text-white text-xs font-semibold rounded-lg transition-colors flex items-center justify-center gap-2 disabled:opacity-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                Upload File
                            </button>
                        </form>
                    </div>
                    <div class="bg-white border border-stone-200 rounded-xl overflow-hidden">
                        <div class="px-4 py-3 border-b border-stone-100 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-6 h-6 flex items-center justify-center bg-stone-100 rounded-md text-stone-500 shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-semibold text-stone-800">Supporting Files</h3>
                            </div>
                            <span class="text-xs text-stone-400" id="supportFilesCount">0 files</span>
                        </div>
                        <div id="supportFilesList" class="min-h-[120px]">
                            <div class="px-4 py-8 text-center">
                                <svg class="w-10 h-10 text-stone-300 mx-auto mb-2" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-xs text-stone-400">No supporting files yet</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>{{-- end p-5 --}}
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
                <button id="btnApplyPN" class="filter-btn filter-btn-primary"><svg class="w-3.5 h-3.5" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
                        </path>
                    </svg> Apply</button>
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
                <button id="btnApplyPV" class="filter-btn filter-btn-primary"><svg class="w-3.5 h-3.5" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
                        </path>
                    </svg> Apply</button>
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
            <div id="verifyAlert" class="hidden px-3 py-2 rounded-lg border text-xs font-medium mb-3"></div>
            <form id="verifyForm">
                <input type="hidden" id="verify-scan-id">
                <div class="mb-4">
                    <label class="block text-xs font-medium text-stone-600 mb-1">Document Received Date <span
                            class="text-red-500">*</span></label>
                    <input type="date" id="document-received-date"
                        class="h-9 px-3 text-xs border border-stone-300 rounded-lg bg-white focus:border-stone-800 outline-none transition-colors w-full"
                        required>
                </div>
                <div class="flex items-center gap-2 justify-end">
                    <button type="button" id="btnCancelVerify"
                        class="h-9 px-4 border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-medium rounded-lg transition-colors">Cancel</button>
                    <button type="submit"
                        class="h-9 px-4 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded-lg transition-colors">Verify</button>
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
                scansData: `/workflow/super-scanner/company/${COMPANY_ID}/scans-data`,
                pendingNaming: `/workflow/super-scanner/company/${COMPANY_ID}/pending-naming`,
                pendingVerify: `/workflow/super-scanner/company/${COMPANY_ID}/pending-verify`,
                tabCounts: `/workflow/super-scanner/company/${COMPANY_ID}/tab-counts`,
                scan: `/workflow/super-scanner/company/${COMPANY_ID}/scan`,
                verifyDocument: `/workflow/super-scanner/company/${COMPANY_ID}/verify-document`,
                finalSubmit: `/workflow/super-scanner/company/${COMPANY_ID}/scan/SCAN_ID/final-submit`,
                locations: '{{ route("workflow.super-scanner.select.locations") }}',
                billApprovers: '{{ route("workflow.super-scanner.select.bill-approvers") }}',
                vendors: '{{ route("workflow.super-scanner.select.vendors") }}',
                users: '{{ route("workflow.super-scanner.select.users") }}',
                docTypes: '{{ route("workflow.super-scanner.select.doc-types") }}',
            };

            let currentScan = null; // Store current scan data for Step 2

            let currentTab = 'scan-document';
            let currentFilter = '';
            let scansFilters = { scanned_by: '', from_date: '', to_date: '' };
            let pnFilters = { from_date: '', to_date: '' };
            let pvFilters = { from_date: '', to_date: '' };

            // ── Drop Zone ─────────────────────────────────────────────────────────────
            function dropZone(zId, iId, lId) {
                const $z = $('#' + zId), $i = $('#' + iId), $l = $('#' + lId);
                $z.on('click', function (e) {
                    if ($(e.target).is($i)) return;
                    e.stopPropagation();
                    $i[0].click();
                });
                $i.on('change', function () {
                    if (this.files[0]) {
                        $l.text(this.files[0].name);
                        $z.addClass('has-file');
                    }
                });
                $z.on('dragover', (e) => { e.preventDefault(); $z.addClass('dragover'); });
                $z.on('dragleave', () => $z.removeClass('dragover'));
                $z.on('drop', (e) => {
                    e.preventDefault();
                    $z.removeClass('dragover');
                    const f = e.originalEvent.dataTransfer.files[0];
                    if (f) {
                        const dt = new DataTransfer();
                        dt.items.add(f);
                        $i[0].files = dt.files;
                        $l.text(f.name);
                        $z.addClass('has-file');
                    }
                });
            }
            dropZone('dropZone', 'mainFile', 'dropLabel');
            dropZone('supportDropZone', 'supportFile', 'supportDropLabel');

            // ── Tabs ──────────────────────────────────────────────────────────────────
            $('.tab-btn').on('click', function () {
                const $btn = $(this);
                const tab  = $btn.data('tab');
                const filter = $btn.data('filter') || '';

                // hide step-2 if visible
                $('#scan-step-2').hide();
                // clear viewer
                $('#fileViewerBody').find('iframe, img').remove();
                $('#viewerPlaceholder').show();
                currentScan = null;

                $('.tab-btn').removeClass('active');
                $btn.addClass('active');
                $('.tab-content').hide();
                $(`#tab-${tab}`).show();
                currentTab   = tab;
                currentFilter = filter;
                if (tab === 'scans')          scansTable.ajax.reload(null, false);
                else if (tab === 'pending-naming')  pendingNamingTable.ajax.reload(null, false);
                else if (tab === 'pending-verify')  pendingVerifyTable.ajax.reload(null, false);
            });

            // Show Sacnning File tab by default
            $('#tab-scan-document').show();
            $('#tab-scans, #tab-pending-naming, #tab-pending-verify').hide();

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
            s2('#filterScannedBy', R.users);
            s2('#sel-doctype', R.docTypes);

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
                    { data: 'actions', orderable: false, searchable: false, render: function(d, t, r) {
                        const canDelete    = (r.Bill_Approved === 'R' || r.temp_scan_reject === 'Y' || r.Final_Submit !== 'Y');
                        const canSupport   = (r.Final_Submit !== 'Y');
                        const scan_date    = r.Temp_Scan_Date || '';

                        let h = '<div class="dt-actions">';
                        h += `<button class="dt-btn blue btn-view-scan" title="View Scan" data-url="${r.File_Location || ''}">
                              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                              </svg></button>`;
                        if (canSupport) {
                            h += `<button class="dt-btn blue btn-add-support" title="Add Supporting Files"
                                    data-id="${r.Scan_Id}" data-file="${r.File || ''}" data-url="${r.File_Location || ''}" data-date="${scan_date}">
                                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                  </svg></button>`;
                        }
                        if (r.Final_Submit !== 'Y') {
                            h += `<button class="dt-btn green btn-final-submit" title="Final Submit" data-id="${r.Scan_Id}">
                                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                  </svg></button>`;
                        }
                        if (canDelete) {
                            h += `<button class="dt-btn red btn-delete-scan" title="Delete" data-id="${r.Scan_Id}">
                                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                  </svg></button>`;
                        }
                        return h + '</div>';
                    }},
                ],
                createdRow: function(row) { $(row).find('.dt-actions').parent().css('white-space','nowrap'); },
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
                    { data: 'actions', orderable: false, searchable: false, render: function(d, t, r) {
                        let h = '<div class="dt-actions">';
                        h += `<button class="dt-btn blue btn-view-scan" title="View Scan" data-url="${r.File_Location || ''}">
                              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                              </svg></button>`;
                        h += `<button class="dt-btn blue btn-add-support" title="Add Supporting Files"
                                data-id="${r.Scan_Id}" data-file="${r.File || ''}" data-url="${r.File_Location || ''}">
                              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                              </svg></button>`;
                        return h + '</div>';
                    }},
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
                    { data: 'actions', orderable: false, searchable: false, render: function(d, t, r) {
                        let h = '<div class="dt-actions">';
                        h += `<button class="dt-btn blue btn-view-scan" title="View Scan" data-url="${r.File_Location || ''}">
                              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                              </svg></button>`;
                        h += `<button class="dt-btn green btn-verify-doc" title="Verify Document" data-id="${r.Scan_Id}">
                              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                              </svg></button>`;
                        return h + '</div>';
                    }},
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

            function getUserFriendlyErrorMessage(errorMsg) {
                // Convert technical validation messages to user-friendly ones
                if (!errorMsg) return 'An error occurred. Please try again.';
                
                const friendlyMessages = {
                    // Bill date validation messages
                    'The bill date field must be a date before or equal to': 'Bill date must be within the current financial year period. Please select a date before',
                    'The bill date field must be a date after or equal to': 'Bill date must be within the current financial year period. Please select a date after',
                    'The bill date field is required': 'Please select a bill date.',
                    'The bill date does not match the format': 'Please enter a valid date format.',
                    'No active financial year is configured': 'System configuration error: No active financial year found. Please contact your administrator.',
                    
                    // File validation messages
                    'The main file field is required': 'Please select a file to upload.',
                    'The main file must be a file': 'Please select a valid file.',
                    'The main file may not be greater than': 'File size is too large. Maximum allowed size is 15 MB.',
                    'The main file must be a file of type': 'Invalid file format. Please upload JPG, PNG, or PDF files only.',
                    
                    // Location validation messages
                    'The location field is required': 'Please select a location.',
                    'The selected location is invalid': 'Selected location is not valid. Please choose a different location.',
                    
                    // Bill approver validation messages
                    'The bill approver field is required': 'Please select a bill approver.',
                    'The selected bill approver is invalid': 'Selected bill approver is not valid. Please choose a different approver.',
                    
                    // Vendor validation messages
                    'The vendor id field is required': 'Please select a vendor.',
                    'The selected vendor id is invalid': 'Selected vendor is not valid. Please choose a different vendor.',
                    
                    // Bill number validation messages
                    'The bill no field is required': 'Please enter a bill number.',
                    'The bill no may not be greater than': 'Bill number is too long. Maximum 100 characters allowed.',
                    
                    // Document name validation messages
                    'The document name field is required': 'Please enter a document name.',
                    'The document name may not be greater than': 'Document name is too long. Maximum 255 characters allowed.'
                };
                
                // Check for partial matches and return friendly message
                for (const [key, friendlyMsg] of Object.entries(friendlyMessages)) {
                    if (errorMsg.toLowerCase().includes(key.toLowerCase())) {
                        // For date range messages, extract the actual date
                        if (key.includes('before or equal to') || key.includes('after or equal to')) {
                            const dateMatch = errorMsg.match(/(\d{4}-\d{2}-\d{2})/);
                            if (dateMatch) {
                                const date = new Date(dateMatch[1]).toLocaleDateString('en-GB', {
                                    day: '2-digit', 
                                    month: 'short', 
                                    year: 'numeric'
                                });
                                return friendlyMsg + ' ' + date + '.';
                            }
                        }
                        return friendlyMsg;
                    }
                }
                
                // If no specific match found, return a generic friendly message
                if (errorMsg.toLowerCase().includes('validation')) {
                    return 'Please check your input and try again.';
                }
                
                // Return original message if it's already user-friendly
                return errorMsg;
            }

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
                        showAlert('#scanAlert', 'success', 'Document scanned successfully! Adding supporting files...');
                        currentScan = res.scan;
                        setTimeout(() => goToStep2(res.scan), 1000);
                    },
                    error: (x) => {
                        let errorMsg = 'Scan failed';
                        if (x.responseJSON) {
                            if (x.responseJSON.message) {
                                errorMsg = x.responseJSON.message;
                            } else if (x.responseJSON.errors) {
                                const errors = x.responseJSON.errors;
                                if (errors.bill_date && errors.bill_date[0]) {
                                    errorMsg = errors.bill_date[0];
                                } else if (errors.main_file && errors.main_file[0]) {
                                    errorMsg = errors.main_file[0];
                                } else if (errors.location && errors.location[0]) {
                                    errorMsg = errors.location[0];
                                } else if (errors.bill_approver && errors.bill_approver[0]) {
                                    errorMsg = errors.bill_approver[0];
                                } else if (errors.vendor_id && errors.vendor_id[0]) {
                                    errorMsg = errors.vendor_id[0];
                                } else if (errors.bill_no && errors.bill_no[0]) {
                                    errorMsg = errors.bill_no[0];
                                } else if (errors.document_name && errors.document_name[0]) {
                                    errorMsg = errors.document_name[0];
                                } else {
                                    errorMsg = Object.values(errors).flat()[0] || 'Validation failed.';
                                }
                            }
                        }
                        showAlert('#scanAlert', 'error', getUserFriendlyErrorMessage(errorMsg));
                    },
                    complete: () => $btn.prop('disabled', false).html('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg> Scan Document')
                });
            });

            // ── Go to Step 2 ──────────────────────────────────────────────────────────
            function goToStep2(scan) {
                currentScan = scan;
                // hide all tab content, show step 2
                $('.tab-content').hide();
                $('#scan-step-2').show();

                // Update banner
                $('#step2ScanId').text(scan.document_name || `Scan #${scan.id}`);
                $('#step2ScanMeta').text(`Scanned: ${scan.scan_date}`);

                // Load file in viewer
                if (scan.file_url) {
                    loadFileViewer(scan.file_url, scan.file || '');
                }

                // Re-initialise select2 on doc-type (it was on a hidden element before)
                if ($('#sel-doctype').data('select2')) {
                    $('#sel-doctype').select2('destroy');
                }
                s2('#sel-doctype', R.docTypes);

                // Load supporting files list
                loadSupportingFiles();

                // Reset support form
                $('#supportForm')[0].reset();
                $('#supportDropZone').removeClass('has-file');
                $('#supportDropLabel').text('Drag & drop or click');
                $('#supportProgressWrap').addClass('hidden');
                $('#supportProgressBar').css('width', '0%');
                $('#supportAlert').addClass('hidden');
            }

            // ── Load File Viewer ──────────────────────────────────────────────────────
            function loadFileViewer(url, filename) {
                const $body = $('#fileViewerBody');
                $body.find('iframe, img').remove();

                if (!url) {
                    $('#viewerPlaceholder').show();
                    $('#viewerOpenLink').attr('href', '#');
                    return;
                }

                $('#viewerPlaceholder').hide();
                $('#viewerOpenLink').attr('href', url);
                const ext = (filename || url).split('.').pop().toLowerCase();

                if (ext === 'pdf') {
                    $body.append(`<iframe src="${url}" style="position:absolute;inset:0;width:100%;height:100%;border:none;background:#1c1917"></iframe>`);
                } else {
                    $body.append(`<img src="${url}" alt="scan" style="position:absolute;inset:0;width:100%;height:100%;object-fit:contain;background:#1c1917">`);
                }
            }

            // ── Load Supporting Files ─────────────────────────────────────────────────
            function loadSupportingFiles() {
                if (!currentScan) return;

                $.getJSON(`/workflow/super-scanner/company/${COMPANY_ID}/scan/${currentScan.id}/support-list`)
                    .done(data => {
                        const files = data.data || [];
                        $('#supportFilesCount').text(`${files.length} file${files.length !== 1 ? 's' : ''}`);

                        if (files.length === 0) {
                            $('#supportFilesList').html(`
                                    <div class="px-4 py-8 text-center">
                                        <svg class="w-10 h-10 text-stone-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-xs text-stone-400">No supporting files yet</p>
                                    </div>
                                `);
                        } else {
                            let html = '';
                            files.forEach(f => {
                                const ext = f.File_Ext || 'file';
                                html += `
                                        <div class="sf-row">
                                            <div class="flex items-center gap-2 min-w-0 flex-1">
                                                <div class="w-7 h-7 flex items-center justify-center bg-stone-100 rounded text-stone-600 text-[9px] font-bold uppercase flex-shrink-0 border border-stone-200">
                                                    ${ext}
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-xs text-stone-800 truncate">${f.File}</p>
                                                    <p class="text-[10px] text-stone-400">${f.doc_type_name || 'No type'}</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-1 flex-shrink-0">
                                                <a href="${f.File_Location}" target="_blank" class="w-7 h-7 flex items-center justify-center rounded hover:bg-blue-50 text-stone-400 hover:text-blue-600 transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                </a>
                                                <button onclick="deleteSupportFile(${f.Support_Id})" class="w-7 h-7 flex items-center justify-center rounded hover:bg-red-50 text-stone-400 hover:text-red-600 transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    `;
                            });
                            $('#supportFilesList').html(html);
                        }
                    });
            }

            // ── Upload Supporting File ────────────────────────────────────────────────
            $('#supportForm').on('submit', function (e) {
                e.preventDefault();
                if (!currentScan) return;

                const fd = new FormData(this);
                const $btn = $('#supportUploadBtn');
                $btn.prop('disabled', true).text('Uploading...');
                $('#supportProgressWrap').removeClass('hidden');

                $.ajax({
                    url: `/workflow/super-scanner/company/${COMPANY_ID}/scan/${currentScan.id}/supporting`,
                    method: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false,
                    headers: { 'X-CSRF-TOKEN': CSRF },
                    xhr: function () {
                        const xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener('progress', function (e) {
                            if (e.lengthComputable) {
                                const pct = Math.round((e.loaded / e.total) * 100);
                                $('#supportProgressBar').css('width', pct + '%');
                                $('#supportProgressText').text(`Uploading... ${pct}%`);
                            }
                        }, false);
                        return xhr;
                    },
                    success: (res) => {
                        showAlert('#supportAlert', 'success', 'Supporting file uploaded successfully!');
                        $('#supportForm')[0].reset();
                        if ($('#sel-doctype').data('select2')) $('#sel-doctype').val(null).trigger('change');
                        $('#supportDropZone').removeClass('has-file');
                        $('#supportDropLabel').text('Drag & drop or click');
                        loadSupportingFiles();
                    },
                    error: (x) => {
                        let msg = 'Upload failed';
                        
                        if (x.status === 403) {
                            msg = 'Access denied. Please check your permissions for this company.';
                        } else if (x.responseJSON?.message) {
                            msg = x.responseJSON.message;
                        }
                        
                        showAlert('#supportAlert', 'error', msg);
                        console.error('Supporting file upload error:', x);
                    },
                    complete: () => {
                        $btn.prop('disabled', false).html('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg> Upload File');
                        setTimeout(() => $('#supportProgressWrap').addClass('hidden'), 500);
                        $('#supportProgressBar').css('width', '0%');
                    }
                });
            });

            // ── Delete Supporting File ────────────────────────────────────────────────
            window.deleteSupportFile = function (supportId) {
                if (!currentScan || !confirm('Delete this supporting file?')) return;

                $.ajax({
                    url: `/workflow/super-scanner/company/${COMPANY_ID}/scan/${currentScan.id}/support/${supportId}`,
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF },
                    success: () => {
                        showAlert('#supportAlert', 'success', 'File deleted successfully!');
                        loadSupportingFiles();
                    },
                    error: () => {
                        showAlert('#supportAlert', 'error', 'Failed to delete file');
                    }
                });
            };

            // ── Final Submit (Step 2 banner button) ───────────────────────────────────
            $('#btnFinalSubmit').on('click', function() {
                if (!currentScan || !confirm('Are you sure you want to submit this scan for final processing?')) return;

                const $btn = $(this);
                $btn.prop('disabled', true).html('<svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="3" stroke="currentColor" stroke-dasharray="31.4" stroke-dashoffset="10"/></svg> Submitting...');

                $.ajax({
                    url: R.finalSubmit.replace('SCAN_ID', currentScan.id),
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF },
                    success: () => {
                        // Navigate back via tab trigger then show toast
                        $('.tab-btn[data-tab="scan-document"]').trigger('click');
                        showToast('Scan submitted for final processing', 'success');
                        scansTable.ajax.reload(null, false);
                        recentScansTable.ajax.reload(null, false);
                        loadTabCounts();
                    },
                    error: (x) => {
                        alert2('supportAlert', 'error', x.responseJSON?.message || 'Final submit failed');
                    },
                    complete: () => {
                        $btn.prop('disabled', false).html('<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Final Submit');
                    }
                });
            });

            // ── Back to Scan Form ─────────────────────────────────────────────────────
            $('#btnBackToScanForm').on('click', function () {
                $('#scan-step-2').hide();
                // Clear viewer
                $('#fileViewerBody').find('iframe, img').remove();
                $('#viewerPlaceholder').show();
                currentScan = null;

                // If we came from the scan-document tab, go back there
                // Otherwise restore the scan-document tab as default
                $('.tab-btn[data-tab="scan-document"]').trigger('click');

                // Reload tables
                scansTable.ajax.reload(null, false);
                recentScansTable.ajax.reload(null, false);
                loadTabCounts();
            });

            // ── Recent Scans Table (for Sacnning File tab) ──────────────────────────────
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

            // ── Action Button Events ──────────────────────────────────────────────────

            // View Scan — open in new tab
            $(document).on('click', '.btn-view-scan', function () {
                const url = $(this).data('url');
                if (url) window.open(url, '_blank');
            });

            // Add Supporting Files — jump to Step 2
            $(document).on('click', '.btn-add-support', function () {
                const $b = $(this);
                goToStep2({
                    id:            $b.data('id'),
                    file:          $b.data('file') || '',
                    file_url:      $b.data('url')  || '',
                    document_name: `Scan #${$b.data('id')}`,
                    scan_date:     $b.data('date') || '',
                });
            });

            // Final Submit from table row
            $(document).on('click', '.btn-final-submit', function () {
                const scanId = $(this).data('id');
                if (!confirm('Mark this scan as final submitted?')) return;
                $.ajax({
                    url: `/workflow/super-scanner/company/${COMPANY_ID}/scan/${scanId}/final-submit`,
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF },
                    success: () => {
                        showToast('Scan marked as final submitted', 'success');
                        scansTable.ajax.reload(null, false);
                        loadTabCounts();
                    },
                    error: (x) => showToast(x.responseJSON?.message || 'Failed to submit scan', 'error'),
                });
            });

            // Delete Scan from table row
            $(document).on('click', '.btn-delete-scan', function () {
                const scanId = $(this).data('id');
                if (!confirm('Delete this scan? This cannot be undone.')) return;
                $.ajax({
                    url: `/workflow/super-scanner/company/${COMPANY_ID}/scan/${scanId}`,
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF },
                    success: () => {
                        showToast('Scan deleted successfully', 'success');
                        scansTable.ajax.reload(null, false);
                        recentScansTable.ajax.reload(null, false);
                        loadTabCounts();
                    },
                    error: (x) => showToast(x.responseJSON?.message || 'Failed to delete scan', 'error'),
                });
            });

            // Verify Document button in pending-verify table
            $(document).on('click', '.btn-verify-doc', function () {
                openVerifyModal($(this).data('id'));
            });

            // ── Alert helper (matches direct-scan alert2 style) ───────────────────────
            function alert2(id, type, msg) {
                const $e = $('#' + id).removeClass(
                    'hidden border-red-200 bg-red-50 text-red-700 border-green-200 bg-green-50 text-green-700'
                );
                $e.addClass(type === 'error'
                    ? 'border-red-200 bg-red-50 text-red-700'
                    : 'border-green-200 bg-green-50 text-green-700'
                ).text(msg).removeClass('hidden');
                setTimeout(() => $e.addClass('hidden'), 6000);
            }

            // keep showAlert as alias (used in several places via CSS selector)
            function showAlert(selector, type, msg) {
                alert2(selector.replace('#', ''), type, msg);
            }

            // ── Toast helper (for table row actions, no inline alert available) ────────
            function showToast(msg, type) {
                const isSuccess = type === 'success';
                const $t = $(`<div style="
                    position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;
                    display:flex;align-items:center;gap:.5rem;
                    padding:.55rem 1rem;border-radius:.5rem;
                    background:${isSuccess ? '#f0fdf4' : '#fef2f2'};
                    color:${isSuccess ? '#15803d' : '#b91c1c'};
                    border:1px solid ${isSuccess ? '#bbf7d0' : '#fecaca'};
                    font-size:.72rem;font-weight:600;
                    box-shadow:0 4px 16px rgba(0,0,0,.1);
                    opacity:0;transition:opacity .2s">
                    <svg style="width:14px;height:14px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        ${isSuccess
                            ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'
                            : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>'}
                    </svg>
                    ${msg}
                </div>`);
                $('body').append($t);
                setTimeout(() => $t.css('opacity', 1), 10);
                setTimeout(() => { $t.css('opacity', 0); setTimeout(() => $t.remove(), 250); }, 3500);
            }
        });
    </script>
@endpush