@extends('layouts.app')
@section('title', 'Temp Scan Upload')
@section('page-title', 'Temp Scan Upload')
@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" defer></script>
    <style>
        .wizard-steps {
            display: flex;
            align-items: center
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

        .ws-dot.done {
            background: #16a34a;
            color: #fff
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
            margin: 0 .6rem
        }

        .ws-line.done {
            background: #16a34a
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

        .ws-label.done {
            color: #16a34a
        }

        .wizard-panel {
            display: none
        }

        .wizard-panel.active {
            display: block
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

        .badge {
            display: inline-flex;
            align-items: center;
            padding: .18rem .55rem;
            border-radius: 9999px;
            font-size: .62rem;
            font-weight: 600;
            white-space: nowrap
        }

        .badge-yes,
        .badge-approved {
            background: #dcfce7;
            color: #15803d
        }

        .badge-no {
            background: #f5f5f4;
            color: #78716c
        }

        .badge-rejected {
            background: #fee2e2;
            color: #b91c1c
        }

        .badge-pending {
            background: #fef9c3;
            color: #a16207
        }

        #scansTable {
            border-collapse: collapse;
            width: 100% !important;
            table-layout: fixed
        }

        #scansTable thead th {
            background: #fafaf9;
            color: #78716c;
            font-size: .65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding: .55rem .75rem;
            border-bottom: 2px solid #e7e5e4;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            position: sticky;
            top: 0;
            z-index: 1
        }

        #scansTable thead th.sorting:after,
        #scansTable thead th.sorting_asc:after,
        #scansTable thead th.sorting_desc:after {
            font-size: .55rem;
            opacity: .6;
            margin-left: .2rem
        }

        #scansTable tbody td {
            padding: .55rem .75rem;
            border-bottom: 1px solid #f0eeec;
            color: #292524;
            vertical-align: middle;
            font-size: .73rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap
        }

        #scansTable tbody tr:hover td {
            background: #fafaf9
        }

        #scansTable tbody tr:last-child td {
            border-bottom: none
        }

        #scansTable th:nth-child(1),
        #scansTable td:nth-child(1) {
            width: 42px;
            text-align: center
        }

        #scansTable th:nth-child(4),
        #scansTable td:nth-child(4) {
            width: 90px
        }

        #scansTable th:nth-child(5),
        #scansTable td:nth-child(5) {
            width: 88px;
            text-align: center
        }

        #scansTable th:nth-child(6),
        #scansTable td:nth-child(6) {
            width: 95px;
            text-align: center
        }

        #scansTable th:nth-child(9),
        #scansTable td:nth-child(9) {
            width: 88px;
            text-align: center
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
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%23a8a29e' stroke-width='2.5'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right .35rem center
        }

        .dt-search-wrap {
            position: relative
        }

        .dt-search-wrap svg {
            position: absolute;
            left: .5rem;
            top: 50%;
            transform: translateY(-50%);
            color: #a8a29e;
            pointer-events: none;
            width: 13px;
            height: 13px
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

        .dataTables_info {
            font-size: .68rem;
            color: #a8a29e
        }

        .dataTables_paginate {
            display: flex;
            gap: .2rem
        }

        .dataTables_paginate .paginate_button {
            height: 1.65rem;
            min-width: 1.65rem;
            padding: 0 .35rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: .35rem;
            font-size: .68rem;
            cursor: pointer;
            border: 1px solid #e7e5e4;
            background: #fff;
            color: #292524;
            user-select: none
        }

        .dataTables_paginate .paginate_button:hover:not(.disabled) {
            background: #f5f5f4
        }

        .dataTables_paginate .paginate_button.current {
            background: #7f1d1d;
            color: #fff;
            border-color: #7f1d1d
        }

        .dataTables_paginate .paginate_button.disabled {
            opacity: .35;
            cursor: default
        }

        .dataTables_processing {
            font-size: .72rem;
            color: #7f1d1d;
            padding: .5rem 0;
            text-align: center
        }

        #scansTable_wrapper>.dataTables_length,
        #scansTable_wrapper>.dataTables_filter,
        #scansTable_wrapper>.dataTables_info,
        #scansTable_wrapper>.dataTables_paginate {
            display: none !important
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
            color: #7f1d1d
        }

        .dt-btn.green:hover {
            background: #f0fdf4;
            color: #16a34a
        }

        .dt-btn.red:hover {
            background: #fef2f2;
            color: #dc2626
        }

        .export-menu {
            position: relative;
            display: inline-block
        }

        .export-drop {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + .3rem);
            z-index: 60;
            background: #fff;
            border: 1px solid #e7e5e4;
            border-radius: .5rem;
            box-shadow: 0 6px 20px rgba(0, 0, 0, .1);
            min-width: 150px;
            overflow: hidden
        }

        .export-menu.open .export-drop {
            display: block
        }

        .export-drop a,
        .export-drop button {
            display: flex;
            align-items: center;
            gap: .5rem;
            padding: .5rem .875rem;
            font-size: .73rem;
            color: #292524;
            text-decoration: none;
            background: none;
            border: none;
            width: 100%;
            cursor: pointer;
            text-align: left
        }

        .export-drop a:hover,
        .export-drop button:hover {
            background: #f5f5f4
        }

        #logCanvas {
            position: fixed;
            top: 0;
            right: -420px;
            width: 420px;
            height: 100vh;
            background: #fff;
            z-index: 200;
            box-shadow: -4px 0 24px rgba(0, 0, 0, .12);
            transition: right .3s cubic-bezier(.4, 0, .2, 1);
            display: flex;
            flex-direction: column
        }

        #logCanvas.open {
            right: 0
        }

        #logCanvasBackdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .35);
            z-index: 199
        }

        #logCanvasBackdrop.open {
            display: block
        }

        .lc-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .875rem 1.25rem;
            border-bottom: 1px solid #f0eeec;
            flex-shrink: 0
        }

        .lc-body {
            flex: 1;
            overflow-y: auto;
            padding: 1rem 1.25rem
        }

        .log-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .6rem 0;
            border-bottom: 1px solid #f5f5f4
        }

        .log-row:last-child {
            border-bottom: none
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
                grid-template-columns: minmax(0, 1.8fr) minmax(0, 1fr)
            }
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
            max-height: calc(100vh - 180px);
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

        .modal-dialog {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 20px 50px rgba(0, 0, 0, .25);
            width: 100%;
            max-width: 1200px;
            max-height: 90vh;
            display: flex;
            flex-direction: column
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e7e5e4;
            flex-shrink: 0
        }

        .modal-body {
            flex: 1;
            overflow: hidden;
            display: flex;
            flex-direction: column
        }

        .modal-viewer-section {
            flex: 1;
            min-height: 0;
            display: flex;
            flex-direction: column;
            background: #1c1917
        }

        .modal-viewer-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .5rem 1rem;
            background: rgba(0, 0, 0, .4);
            flex-shrink: 0
        }

        .modal-viewer-body {
            flex: 1;
            position: relative;
            min-height: 400px
        }

        .modal-viewer-body iframe,
        .modal-viewer-body img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            border: none;
            object-fit: contain;
            background: #1c1917
        }

        .modal-tabs-bar {
            display: flex;
            align-items: center;
            gap: 0;
            padding: 0 1rem;
            background: #fafaf9;
            border-bottom: 1px solid #e7e5e4;
            flex-shrink: 0;
            flex-wrap: wrap;
            position: relative
        }

        .modal-tab {
            padding: .55rem .85rem;
            font-size: .68rem;
            font-weight: 600;
            color: #78716c;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            transition: all .15s;
            white-space: nowrap
        }

        .modal-tab:hover {
            color: #292524
        }

        .modal-tab.active {
            color: #7f1d1d;
            border-bottom-color: #7f1d1d
        }

        .modal-tab-files {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 10;
            background: #fff;
            border-bottom: 1px solid #e7e5e4;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .08);
            padding: .4rem .75rem;
            max-height: 150px;
            overflow-y: auto
        }

        .modal-tab-files.open {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .3rem
        }

        .modal-tab-files .file-group-label {
            font-size: .6rem;
            font-weight: 700;
            color: #78716c;
            text-transform: uppercase;
            letter-spacing: .03em;
            padding: .25rem 0;
            margin-top: .35rem
        }

        .modal-tab-files .file-group-label:first-child {
            margin-top: 0
        }

        .modal-tab-files .file-link {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .25rem .55rem;
            border-radius: .35rem;
            cursor: pointer;
            font-size: .67rem;
            color: #292524;
            transition: background .12s;
            border: 1px solid #e7e5e4;
            background: #fafaf9;
            white-space: nowrap
        }

        .modal-tab-files .file-link:hover {
            background: #f5f5f4;
            border-color: #d6d3d1
        }

        .modal-tab-files .file-link.active {
            background: #fef2f2;
            color: #7f1d1d;
            font-weight: 600;
            border-color: #7f1d1d
        }

        .modal-tab-files .file-ext {
            width: 1.1rem;
            height: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f5f4;
            border-radius: .2rem;
            font-size: 6px;
            font-weight: 700;
            color: #78716c;
            text-transform: uppercase;
            flex-shrink: 0;
            border: 1px solid #e7e5e4
        }

        /* ── Tabs ──────────────────────────────────────── */
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

        /* ── Filters ───────────────────────────────────── */
        .filter-bar {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .875rem 1.25rem;
            background: #fafaf9;
            border-bottom: 1px solid #e7e5e4;
            flex-wrap: wrap
        }

        .filter-input {
            height: 2rem;
            padding: 0 .65rem;
            font-size: .72rem;
            border: 1px solid #d6d3d1;
            border-radius: .375rem;
            background: #fff;
            outline: none;
            color: #292524
        }

        .filter-input:focus {
            border-color: #7f1d1d;
            box-shadow: 0 0 0 3px rgba(127, 29, 29, .08)
        }

        .filter-btn {
            height: 2rem;
            px-3;
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
    </style>
@endpush
@section('content')
    <div id="tempScanApp">
        <div class="wizard-panel active" id="step1">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="bg-white border border-stone-200 rounded-xl p-5 flex flex-col gap-4">
                    <div>
                        <h2 class="text-sm font-semibold text-stone-800">Scan File</h2>
                        <p class="text-xs text-stone-400 mt-0.5">Upload a new temp scan document</p>
                    </div>
                    <div class="wizard-steps bg-stone-50 rounded-lg border border-stone-100 px-3 py-2.5">
                        <div class="ws-item">
                            <div class="ws-dot active" id="ws-dot-1">1</div><span class="ws-label active"
                                id="ws-lbl-1">Upload Scan</span>
                        </div>
                        <div class="ws-line" id="ws-line-1"></div>
                        <div class="ws-item">
                            <div class="ws-dot pending" id="ws-dot-2">2</div><span class="ws-label pending"
                                id="ws-lbl-2">Supporting Files</span>
                        </div>
                    </div>
                    <div id="uploadAlert" class="hidden px-3.5 py-2.5 rounded-xl border text-xs"></div>
                    <form id="uploadForm" class="flex flex-col gap-3.5" novalidate>
                        @csrf
                        <div><label class="block text-xs font-medium text-stone-600 mb-1">Location <span
                                    class="text-red-500">*</span></label><select id="sel-location" name="location"
                                style="width:100%">
                                <option value="">Select Location</option>
                            </select></div>
                        <div><label class="block text-xs font-medium text-stone-600 mb-1">Bill Approver <span
                                    class="text-red-500">*</span></label><select id="sel-approver" name="bill_approver"
                                style="width:100%" disabled>
                                <option value="">Select Approver</option>
                            </select></div>
                        <div><label class="block text-xs font-medium text-stone-600 mb-1">Bill Date/Voucher Date<span
                                    class="text-red-500">*</span></label><input type="date" id="bill_date" onfocus="if (this.showPicker) this.showPicker(); else this.click();"  name="bill_date" required
                                class="w-full h-8 px-3 text-xs border border-stone-300 rounded-lg bg-stone-50 focus:border-stone-800 focus:ring focus:ring-stone-800 focus:ring-opacity-10 outline-none"
                                @if(\App\Helpers\BillDateValidator::getCurrentFyRange())
                                    min="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['start'] }}"
                                    max="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['end'] }}"
                                @endif
                                required></div>
                        <div><label class="block text-xs font-medium text-stone-600 mb-1">File <span
                                    class="text-red-500">*</span></label>
                            <div class="drop-zone" id="dropZone">
                                <input type="file" id="mainFile" name="main_file" accept=".jpg,.jpeg,.png,.pdf"
                                    class="hidden">
                                <svg class="w-7 h-7 text-stone-300 mx-auto mb-1.5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="text-xs text-stone-500" id="dropLabel">Drag &amp; drop or click</p>
                                <p class="text-[10px] text-stone-400 mt-0.5">JPG, PNG, PDF — max 15 MB</p>
                            </div>
                        </div>
                        <div id="uploadProgressWrap" class="hidden">
                            <div class="upload-progress">
                                <div class="upload-progress-bar" id="uploadProgressBar"></div>
                            </div>
                            <p class="text-[10px] text-stone-400 mt-1" id="uploadProgressText">Uploading…</p>
                        </div>
                        <button type="submit" id="uploadBtn"
                            class="w-full h-9 bg-stone-800 hover:bg-stone-900 text-white text-xs font-semibold rounded-lg transition-colors flex items-center justify-center gap-2 disabled:opacity-50 mt-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>Save &amp; Continue
                        </button>
                    </form>
                </div>
                <div class="lg:col-span-2 bg-white border border-stone-200 rounded-xl flex flex-col">
                    <div
                        class="px-5 py-3.5 border-b border-stone-100 flex items-center justify-between gap-3 flex-shrink-0">
                        <h2 class="text-sm font-semibold text-stone-800">Latest Scan Files</h2>
                        <div class="flex items-center gap-2">
                            <button id="btnOpenLog"
                                class="h-8 px-3 flex items-center gap-1.5 text-xs font-medium border border-stone-200 rounded-lg text-stone-600 hover:bg-stone-50 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>Export Log
                            </button>
                            <div class="export-menu" id="exportMenu">
                                <button id="btnExportToggle"
                                    class="h-8 px-3 flex items-center gap-1.5 text-xs font-medium bg-stone-800 hover:bg-stone-900 text-white rounded-lg transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>Export
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div class="export-drop">
                                    <a href="{{ route('workflow.temp-scan.export.excel') }}" id="exportExcel"><svg
                                            class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9v10a2 2 0 01-2 2z" />
                                        </svg>Export Excel</a>
                                    <a href="{{ route('workflow.temp-scan.export.pdf') }}" id="exportPdf"><svg
                                            class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>Export PDF</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Tabs --}}
                    <div class="tabs">
                        <button class="tab-btn active" data-tab="all">All Scans<span class="tab-badge"
                                id="badge-all">0</span></button>
                        <button class="tab-btn" data-tab="pending">Pending<span class="tab-badge"
                                id="badge-pending">0</span></button>
                        <button class="tab-btn" data-tab="approved">Approved<span class="tab-badge"
                                id="badge-approved">0</span></button>
                        <button class="tab-btn" data-tab="rejected">Rejected<span class="tab-badge"
                                id="badge-rejected">0</span></button>
                    </div>
                    {{-- Filters --}}
                    <div class="filter-bar">
                        <div class="flex items-center gap-2">
                            <label class="text-[11px] font-semibold text-stone-600 uppercase">From</label>
                            <input type="date" id="filterFromDate" class="filter-input" style="width:140px">
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-[11px] font-semibold text-stone-600 uppercase">To</label>
                            <input type="date" id="filterToDate" class="filter-input" style="width:140px">
                        </div>
                        <button id="btnApplyFilters" class="filter-btn filter-btn-primary">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Apply
                        </button>
                        <button id="btnResetFilters" class="filter-btn filter-btn-secondary">Reset</button>
                    </div>
                    <div class="dt-ctrl-bar border-b border-stone-100 flex-shrink-0">
                        <div class="flex items-center gap-2 text-xs text-stone-500"><span>Show</span><select
                                class="dt-length-sel" id="dtLength">
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select><span>entries</span></div>
                        <div class="dt-search-wrap"><input type="text" class="dt-search-input" id="dtSearch"
                                placeholder="Search…"></div>
                    </div>
                    <div class="overflow-x-auto flex-1">
                        <table id="scansTable" style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Location</th>
                                    <th>File</th>
                                    <th>Scan Date</th>
                                    <th>Final Submit</th>
                                    <th>Bill Approved</th>
                                    <th>Approver</th>
                                    <th>Remark</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    <div class="dt-ctrl-bar border-t border-stone-100 flex-shrink-0" id="dtBottomBar">
                        <div id="dtInfo" class="dataTables_info"></div>
                        <div id="dtPaginate"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="wizard-panel" id="step2">
            <div
                class="bg-white border border-stone-200 rounded-xl px-4 py-3 flex items-center justify-between gap-4 mb-4 flex-wrap">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="flex items-center gap-1.5 shrink-0">
                        <div class="ws-dot done text-[.6rem]">✓</div><span class="ws-label done text-[.68rem]">Upload
                            Scan</span>
                        <div class="ws-line done w-8"></div>
                        <div class="ws-dot active text-[.65rem]">2</div><span
                            class="ws-label active text-[.68rem]">Supporting Files</span>
                    </div>
                    <div class="w-px h-5 bg-stone-200 shrink-0 hidden sm:block"></div>
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-7 h-7 flex items-center justify-center bg-red-50 rounded-lg text-red-700 shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586 a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-stone-800 leading-tight" id="bannerScanId">—</p>
                            <p class="text-[10px] text-stone-400 leading-tight truncate" id="bannerScanMeta">—</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button id="btnFinalSubmit"
                        class="h-8 px-3 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded-lg transition-colors flex items-center gap-1.5"><svg
                            class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>Final Submit</button>
                    <button id="btnBackToStep1"
                        class="h-8 px-3 border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-medium rounded-lg transition-colors flex items-center gap-1"><svg
                            class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>Back</button>
                </div>
            </div>
            <div class="step2-grid">
                <div class="file-viewer">
                    <div class="file-viewer-toolbar"><span
                            class="text-[10px] font-semibold text-stone-300 uppercase tracking-wide">Scan Preview</span><a
                            id="viewerOpenLink" href="#" target="_blank"
                            class="text-[10px] text-stone-400 hover:text-white transition-colors flex items-center gap-1"><svg
                                class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>Open in new tab</a></div>
                    <div class="file-viewer-body" id="fileViewerBody">
                        <div class="viewer-placeholder" id="viewerPlaceholder"><svg class="w-12 h-12" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586 a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
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
                        <div id="supportAlert" class="hidden px-3 py-2 rounded-lg border text-xs"></div>
                        <form id="supportForm" class="flex flex-col gap-3" novalidate>
                            @csrf
                            <div><label class="block text-xs font-medium text-stone-600 mb-1">Document Type</label><select
                                    id="sel-doctype" name="doc_type_id" style="width:100%">
                                    <option value="">— Select —</option>
                                </select></div>
                            <div><label class="block text-xs font-medium text-stone-600 mb-1">File <span
                                        class="text-red-500">*</span></label>
                                <div class="drop-zone" id="supportDropZone">
                                    <input type="file" id="supportFile" name="support_file" accept=".jpg,.jpeg,.png,.pdf"
                                        class="hidden">
                                    <svg class="w-6 h-6 text-stone-300 mx-auto mb-1" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <p class="text-xs text-stone-500" id="supportDropLabel">Drag &amp; drop or click</p>
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
                                class="w-full h-9 bg-stone-800 hover:bg-stone-900 text-white text-xs font-semibold rounded-lg transition-colors flex items-center justify-center gap-2 disabled:opacity-50"><svg
                                    class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>Upload File</button>
                        </form>
                    </div>
                    <div class="bg-white border border-stone-200 rounded-xl overflow-hidden">
                        <div class="px-4 py-3 border-b border-stone-100 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-6 h-6 flex items-center justify-center bg-stone-100 rounded-md text-stone-500 shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586 a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-semibold text-stone-800">Supporting Files</h3>
                            </div>
                            <span id="supportCountBadge"
                                class="hidden text-[10px] font-semibold bg-stone-100 text-stone-600 px-2 py-0.5 rounded-full"></span>
                        </div>
                        <div id="supportList">
                            <div class="flex flex-col items-center justify-center py-10 gap-2 text-stone-400"><svg
                                    class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"
                                        d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8 a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                                </svg>
                                <p class="text-xs">No supporting files yet</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop" id="resubmitModalBackdrop"></div>
    <div class="modal-container" id="resubmitModal">
        <div class="modal-dialog" style="max-width:500px">
            <div class="modal-header">
                <div>
                    <h3 class="text-sm font-semibold text-stone-800">Resubmit for Approval</h3>
                    <p class="text-xs text-stone-400 mt-0.5">Review and change bill approver if needed</p>
                </div>
                <button id="btnCloseResubmitModal"
                    class="w-7 h-7 flex items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 transition-colors"><svg
                        class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg></button>
            </div>
            <div class="modal-body" style="padding:1.25rem">
                <div id="resubmitAlert" class="hidden px-3 py-2 rounded-lg border text-xs mb-3"></div>
                <div class="mb-4"><label class="block text-xs font-medium text-stone-600 mb-2">Bill Approver <span
                            class="text-[10px] text-stone-400">(Change if needed)</span></label><select
                        id="resubmitApprover" style="width:100%">
                        <option value="">Select approver</option>
                    </select></div>
                <div class="flex items-center gap-2 justify-end">
                    <button id="btnCancelResubmit"
                        class="h-9 px-4 border border-stone-200 text-stone-600 hover:bg-stone-50 text-xs font-medium rounded-lg transition-colors">Cancel</button>
                    <button id="btnConfirmResubmit"
                        class="h-9 px-4 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition-colors flex items-center gap-2"><svg
                            class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>Resubmit</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop" id="viewScanModalBackdrop"></div>
    <div class="modal-container" id="viewScanModal">
        <div class="modal-dialog">
            <div class="modal-header">
                <div>
                    <h3 class="text-sm font-semibold text-stone-800" id="modalScanTitle">—</h3>
                    <p class="text-xs text-stone-400 mt-0.5" id="modalScanMeta">—</p>
                </div>
                <button id="btnCloseModal"
                    class="w-7 h-7 flex items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 transition-colors"><svg
                        class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg></button>
            </div>
            <div class="modal-body">
                <div class="modal-tabs-bar" id="modalTabsBar"></div>
                <div class="modal-viewer-section">
                    <div class="modal-viewer-toolbar"><span id="modalViewingFileName"
                            class="text-[10px] font-semibold text-stone-300">Main Scan</span></div>
                    <div class="modal-viewer-body" id="modalViewerBody"></div>
                </div>
            </div>
        </div>
    </div>
    <div id="logCanvasBackdrop"></div>
    <div id="logCanvas">
        <div class="lc-header">
            <div>
                <h3 class="text-sm font-semibold text-stone-800">Export Log</h3>
                <p class="text-xs text-stone-400 mt-0.5">Your recent temp scan exports</p>
            </div>
            <button id="btnCloseLog"
                class="w-7 h-7 flex items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 transition-colors"><svg
                    class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg></button>
        </div>
        <div class="lc-body" id="logBody">
            <p class="text-center text-xs text-stone-400 py-8">Loading…</p>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        $(function () {
            const CSRF = $('meta[name="csrf-token"]').attr('content');
            const R = { locations: '{{route("workflow.temp-scan.locations")}}', approvers: '{{route("workflow.temp-scan.bill-approvers")}}', docTypes: '{{route("workflow.temp-scan.doc-types")}}', companies: '', financialYears: '', store: '{{route("workflow.temp-scan.store")}}', data: '{{route("workflow.temp-scan.data")}}', tabCounts: '{{route("workflow.temp-scan.tab-counts")}}', exportLogs: '{{route("workflow.temp-scan.export.logs")}}', supportStore: (id) => `/workflow/temp-scan/${id}/supporting`, supportList: (id) => `/workflow/temp-scan/${id}/support-list`, finalSubmit: (id) => `/workflow/temp-scan/${id}/final-submit`, resubmit: (id) => `/workflow/temp-scan/${id}/resubmit`, destroy: (id) => `/workflow/temp-scan/${id}`, supportDel: (id, sid) => `/workflow/temp-scan/${id}/support/${sid}`, scanDetail: (id) => `/workflow/temp-scan/${id}` };
            let activeScan = null;
            let currentTab = 'all';
            let currentFilters = { from_date: '', to_date: '' };
            function s2(selector, url, extra) { $(selector).select2({ placeholder: $(selector).data('ph') || 'Search…', allowClear: true, minimumInputLength: 0, ajax: { url, dataType: 'json', delay: 250, data: (p) => Object.assign({ q: p.term || '', page: p.page || 1 }, extra || {}), processResults: (d) => ({ results: d.results, pagination: d.pagination }), cache: true } }) }
            s2('#sel-location', R.locations);
            function onLocationChange() { const loc = $(this).val(); const $a = $('#sel-approver'); if ($a.data('select2')) $a.select2('destroy'); $a.empty().append('<option value="">Select Approver</option>'); if (loc) { $a.select2({ placeholder: 'Search approver…', allowClear: true, minimumInputLength: 0, ajax: { url: R.approvers, dataType: 'json', delay: 250, cache: false, data: (p) => ({ q: p.term || '', page: p.page || 1, location_id: loc }), processResults: (d) => ({ results: d.results, pagination: d.pagination }) } }).prop('disabled', false) } else { initApprover() } }
            $('#sel-location').on('change.select2', onLocationChange);
            function initApprover() { const $a = $('#sel-approver'); if ($a.data('select2')) $a.select2('destroy'); $a.empty().append('<option value="">Select Approver</option>'); s2('#sel-approver', R.approvers); $a.prop('disabled', true) }
            initApprover(); s2('#sel-doctype', R.docTypes);
            function dropZone(zId, iId, lId) { const $z = $('#' + zId), $i = $('#' + iId), $l = $('#' + lId); $z.on('click', function (e) { if ($(e.target).is($i)) return; e.stopPropagation(); $i[0].click() }); $i.on('change', function () { if (this.files[0]) { $l.text(this.files[0].name); $z.addClass('has-file') } }); $z.on('dragover', (e) => { e.preventDefault(); $z.addClass('dragover') }); $z.on('dragleave', () => $z.removeClass('dragover')); $z.on('drop', (e) => { e.preventDefault(); $z.removeClass('dragover'); const f = e.originalEvent.dataTransfer.files[0]; if (f) { const dt = new DataTransfer(); dt.items.add(f); $i[0].files = dt.files; $l.text(f.name); $z.addClass('has-file') } }) }
            dropZone('dropZone', 'mainFile', 'dropLabel'); dropZone('supportDropZone', 'supportFile', 'supportDropLabel');
            const dt = $('#scansTable').DataTable({ serverSide: true, processing: true, ajax: { url: R.data, type: 'GET', data: function (d) { d.tab = currentTab; d.from_date = currentFilters.from_date; d.to_date = currentFilters.to_date } }, order: [[3, 'desc']], pageLength: 5, dom: 'rtp', columns: [{ data: 'DT_RowIndex', orderable: false, searchable: false }, { data: 'location_name', defaultContent: '—' }, { data: 'File', render: (d, t, r) => `<a href="javascript:void(0)" class="text-blue-600 hover:underline block truncate btn-file-view" data-id="${r.Scan_Id}" title="${esc(d)}">${esc(d)}</a>` }, { data: 'Temp_Scan_Date', defaultContent: '—' }, { data: 'final_submit_badge', orderable: false, className: 'text-center' }, { data: 'bill_approved_badge', orderable: false, className: 'text-center' }, { data: 'approver_name', defaultContent: '—' }, { data: 'Bill_Approver_Remark', defaultContent: '—', render: (d) => d ? `<span title="${esc(d)}" class="block truncate">${esc(d)}</span>` : '—' }, { data: 'actions', orderable: false, searchable: false, className: 'text-center', render: (d, t, r) => buildBtns(r) }], language: { emptyTable: 'No scan files found', zeroRecords: 'No matching records', processing: '<span style="font-size:.72rem;color:#7f1d1d">Loading…</span>' }, drawCallback: attachTblEvents });
            $('.tab-btn').on('click', function () { const tab = $(this).data('tab'); currentTab = tab; $('.tab-btn').removeClass('active'); $(this).addClass('active'); dt.ajax.reload() });
            $('#btnApplyFilters').on('click', function () { currentFilters.from_date = $('#filterFromDate').val(); currentFilters.to_date = $('#filterToDate').val(); dt.ajax.reload() });
            $('#btnResetFilters').on('click', function () { $('#filterFromDate,#filterToDate').val(''); currentFilters = { from_date: '', to_date: '' }; dt.ajax.reload() });
            $('#dtLength').on('change', function () { dt.page.len(+$(this).val()).draw() });
            let st; $('#dtSearch').on('input', function () { clearTimeout(st); const v = $(this).val(); st = setTimeout(() => dt.search(v).draw(), 350) });
            dt.on('draw', function () { const $p = $('#scansTable_wrapper .dataTables_paginate').first(); const $i = $('#scansTable_wrapper .dataTables_info').first(); if ($p.length) $p.appendTo('#dtPaginate'); if ($i.length) $i.appendTo('#dtInfo'); updateTabBadges() });
            async function updateTabBadges() { try { const userId = '{{Auth::id()}}'; const baseQuery = { Temp_Scan: 'Y', Temp_Scan_By: userId, Is_Deleted: 'N' }; const counts = await $.getJSON('{{route("workflow.temp-scan.tab-counts")}}'); $('#badge-all').text(counts.all || 0); $('#badge-pending').text(counts.pending || 0); $('#badge-approved').text(counts.approved || 0); $('#badge-rejected').text(counts.rejected || 0) } catch (e) { console.error('Failed to load tab counts', e) } }
            function buildBtns(r) { const canDelete = (r.Bill_Approved === 'R' || r.temp_scan_reject === 'Y' || r.Final_Submit !== 'Y'); const canAttachSupport = (r.Final_Submit !== 'Y'); const isRejected = (r.Bill_Approved === 'R' || r.temp_scan_reject === 'Y'); let h = `<div class="dt-actions">`; h += `<button class="dt-btn blue btn-view-modal" title="View Scan" data-id="${r.Scan_Id}"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button>`; if (canAttachSupport) { h += `<button class="dt-btn blue btn-s2" title="Add Supporting Files" data-id="${r.Scan_Id}" data-file="${esc(r.File)}" data-url="${esc(r.File_Location)}" data-date="${esc(r.Temp_Scan_Date)}"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg></button>` } if (isRejected) { h += `<button class="dt-btn blue btn-resubmit" title="Resubmit for Approval" data-id="${r.Scan_Id}"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></button>` } if (r.Final_Submit !== 'Y') { h += `<button class="dt-btn green btn-fs" title="Final Submit" data-id="${r.Scan_Id}"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></button>` } if (canDelete) { h += `<button class="dt-btn red btn-del" title="Delete" data-id="${r.Scan_Id}"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>` } return h + '</div>' }
            function attachTblEvents() { $('#scansTable').off('click', '.btn-view-modal').on('click', '.btn-view-modal', async function () { await openViewModal($(this).data('id')) }).off('click', '.btn-file-view').on('click', '.btn-file-view', async function (e) { e.preventDefault(); await openViewModal($(this).data('id')) }).off('click', '.btn-s2').on('click', '.btn-s2', function () { const $b = $(this); goStep2({ id: $b.data('id'), file: $b.data('file'), file_url: $b.data('url'), scan_date: $b.data('date') }) }).off('click', '.btn-resubmit').on('click', '.btn-resubmit', async function () { openResubmitModal($(this).data('id')) }).off('click', '.btn-fs').on('click', '.btn-fs', async function () { if (!confirm('Mark as final submitted?')) return; await $.ajax({ url: R.finalSubmit($(this).data('id')), method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF } }); dt.ajax.reload(null, false) }).off('click', '.btn-del').on('click', '.btn-del', async function () { if (!confirm('Delete this scan?')) return; await $.ajax({ url: R.destroy($(this).data('id')), method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF } }); dt.ajax.reload(null, false) }) }
            $('#btnExportToggle').on('click', function (e) { e.stopPropagation(); $('#exportMenu').toggleClass('open') });
            $(document).on('click', function (e) { if (!$(e.target).closest('#exportMenu').length) $('#exportMenu').removeClass('open') });
            $('#exportExcel,#exportPdf').on('click', function () { $('#exportMenu').removeClass('open') });
            function openLog() { $('#logCanvas,#logCanvasBackdrop').addClass('open'); loadLogEntries() }
            function closeLog() { $('#logCanvas,#logCanvasBackdrop').removeClass('open') }
            $('#btnOpenLog').on('click', openLog); $('#btnCloseLog,#logCanvasBackdrop').on('click', closeLog);
            async function loadLogEntries() { $('#logBody').html('<p class="text-center text-xs text-stone-400 py-8">Loading…</p>'); try { const res = await $.getJSON(R.exportLogs); if (!res.data.length) { $('#logBody').html('<p class="text-center text-xs text-stone-400 py-8">No exports yet.</p>'); return } const rows = res.data.map(l => `<div class="log-row"><div class="min-w-0"><p class="text-xs font-medium text-stone-700 truncate" title="${esc(l.file_name)}">${esc(l.file_name)}</p><p class="text-[10px] text-stone-400 mt-0.5">${l.row_count} rows &bull; ${fmtDate(l.created_at)}</p></div><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold flex-shrink-0 ${l.file_name.endsWith('.xlsx') ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'}">${l.file_name.endsWith('.xlsx') ? 'Excel' : 'PDF'}</span></div>`).join(''); $('#logBody').html(rows) } catch (e) { $('#logBody').html('<p class="text-center text-xs text-red-500 py-8">Failed to load logs.</p>') } }
            function fmtDate(s) { if (!s) return '—'; const d = new Date(s); return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' + d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' }) }
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
            
            $('#uploadForm').on('submit', async function (e) { e.preventDefault(); const loc = $("#sel-location").val(), apv = $("#sel-approver").val(), billDate = $('#bill_date').val(), fl = $('#mainFile')[0].files[0]; if (!loc) { return alert2('uploadAlert', 'error', 'Please select a location.') } if (!apv) { return alert2('uploadAlert', 'error', 'Please select a bill approver.') } if (!billDate) { return alert2('uploadAlert', 'error', 'Please select a bill date.') } if (!fl) { return alert2('uploadAlert', 'error', 'Please select a file.') } const fd = new FormData(); fd.append('_token', CSRF); fd.append('location', loc); fd.append('bill_approver', apv); fd.append('bill_date', billDate); fd.append('main_file', fl); setUpState(true); try { const res = await $.ajax({ url: R.store, method: 'POST', data: fd, processData: false, contentType: false, xhr: () => { const x = new XMLHttpRequest(); x.upload.addEventListener('progress', (ev) => { if (ev.lengthComputable) { const p = Math.round(ev.loaded / ev.total * 100); $('#uploadProgressBar').css('width', p + '%'); $('#uploadProgressText').text(`Uploading… ${p}%`) } }); return x } }); if (res.success) { dt.ajax.reload(null, false); resetUpForm(); goStep2(res.scan) } else alert2('uploadAlert', 'error', getUserFriendlyErrorMessage(res.message) || 'Upload failed.') } catch (err) { let errorMsg = 'Upload failed.'; if (err.responseJSON) { if (err.responseJSON.message) { errorMsg = err.responseJSON.message; } else if (err.responseJSON.errors) { const errors = err.responseJSON.errors; if (errors.bill_date && errors.bill_date[0]) { errorMsg = errors.bill_date[0]; } else if (errors.main_file && errors.main_file[0]) { errorMsg = errors.main_file[0]; } else if (errors.location && errors.location[0]) { errorMsg = errors.location[0]; } else if (errors.bill_approver && errors.bill_approver[0]) { errorMsg = errors.bill_approver[0]; } else { errorMsg = Object.values(errors).flat()[0] || 'Validation failed.'; } } } alert2('uploadAlert', 'error', getUserFriendlyErrorMessage(errorMsg)); } finally { setUpState(false) } });
            function setUpState(on) { $('#uploadBtn').prop('disabled', on); $('#uploadProgressWrap').toggleClass('hidden', !on); if (!on) { $('#uploadProgressBar').css('width', '0%'); $('#uploadProgressText').text('Uploading…') } }
            function resetUpForm() { if ($('#sel-location').data('select2')) $('#sel-location').select2('destroy'); $('#sel-location').empty().append('<option value="">Select Location</option>'); s2('#sel-location', R.locations); $('#sel-location').off('change.select2').on('change.select2', onLocationChange); initApprover(); $('#bill_date').val(''); $('#mainFile').val(''); $('#dropLabel').text('Drag & drop or click'); $('#dropZone').removeClass('has-file'); $('#uploadAlert').addClass('hidden') }
            function loadViewer(url) { const $body = $('#fileViewerBody'); const isPdf = url.toLowerCase().includes('.pdf') || url.toLowerCase().endsWith('pdf'); const isImg = /\.(jpe?g|png|gif|webp)(\?|$)/i.test(url); $('#viewerPlaceholder').remove(); $body.find('iframe,img').remove(); $('#viewerOpenLink').attr('href', url); if (isPdf) { $body.append(`<iframe src="${esc(url)}" title="Scan Preview"></iframe>`) } else if (isImg) { $body.append(`<img src="${esc(url)}" alt="Scan Preview">`) } else { $body.append(`<iframe src="${esc(url)}" title="Scan Preview"></iframe>`) } }
            function goStep2(scan) { activeScan = scan; $('#bannerScanId').text(`Scan #${scan.id}`); $('#bannerScanMeta').text(`${scan.file}  •  ${scan.scan_date || ''}`); $('#ws-dot-1').removeClass('active pending').addClass('done').html('✓'); $('#ws-lbl-1').removeClass('active pending').addClass('done'); $('#ws-line-1').addClass('done'); $('#ws-dot-2').removeClass('pending').addClass('active'); $('#ws-lbl-2').removeClass('pending').addClass('active'); $('#step1').removeClass('active'); $('#step2').addClass('active'); loadViewer(scan.file_url); loadSupport() }
            $('#btnBackToStep1').on('click', function () { activeScan = null; $('#step2').removeClass('active'); $('#step1').addClass('active'); $('#ws-dot-1').removeClass('done pending').addClass('active').html('1'); $('#ws-lbl-1').removeClass('done pending').addClass('active'); $('#ws-line-1').removeClass('done'); $('#ws-dot-2').removeClass('active done').addClass('pending'); $('#ws-lbl-2').removeClass('active done').addClass('pending'); $('#fileViewerBody').find('iframe,img').remove(); $('#fileViewerBody').append(`<div class="viewer-placeholder" id="viewerPlaceholder"><svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586 a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><p class="text-xs">No file loaded</p></div>`); resetSupForm() });
            $('#supportForm').on('submit', async function (e) { e.preventDefault(); if (!activeScan) return; const fl = $('#supportFile')[0].files[0]; if (!fl) { return alert2('supportAlert', 'error', 'Please select a file.') } const fd = new FormData(); fd.append('_token', CSRF); fd.append('doc_type_id', $('#sel-doctype').val() || ''); fd.append('support_file', fl); setSupState(true); try { const res = await $.ajax({ url: R.supportStore(activeScan.id), method: 'POST', data: fd, processData: false, contentType: false, xhr: () => { const x = new XMLHttpRequest(); x.upload.addEventListener('progress', (ev) => { if (ev.lengthComputable) { const p = Math.round(ev.loaded / ev.total * 100); $('#supportProgressBar').css('width', p + '%'); $('#supportProgressText').text(`Uploading… ${p}%`) } }); return x } }); if (res.success) { resetSupForm(); loadSupport(); dt.ajax.reload(null, false); alert2('supportAlert', 'success', 'Supporting file uploaded successfully.') } else { alert2('supportAlert', 'error', res.message || 'Upload failed.') } } catch (err) { alert2('supportAlert', 'error', err.responseJSON?.message || err.responseJSON?.errors?.support_file?.[0] || 'Upload failed.') } finally { setSupState(false) } });
            function setSupState(on) { $('#supportUploadBtn').prop('disabled', on); $('#supportProgressWrap').toggleClass('hidden', !on); if (!on) { $('#supportProgressBar').css('width', '0%'); $('#supportProgressText').text('Uploading…') } }
            function resetSupForm() { $('#sel-doctype').val(null).trigger('change'); $('#supportFile').val(''); $('#supportDropLabel').text('Drag & drop or click'); $('#supportDropZone').removeClass('has-file') }
            async function loadSupport() { if (!activeScan) return; try { const res = await $.getJSON(R.supportList(activeScan.id)); renderSupport(res.data) } catch { $('#supportList').html('<p class="px-4 py-4 text-center text-xs text-red-500">Failed to load.</p>') } }
            function renderSupport(files) { const $badge = $('#supportCountBadge'); if (files.length) { $badge.text(files.length).removeClass('hidden') } else { $badge.addClass('hidden') } if (!files.length) { $('#supportList').html(`<div class="flex flex-col items-center justify-center py-10 gap-2 text-stone-400"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8 a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg><p class="text-xs">No supporting files yet</p></div>`); return } $('#supportList').html(files.map((f, i) => `<div class="sf-row" id="sf-${f.Support_Id}"><div class="flex items-center gap-2.5 min-w-0"><span class="text-[10px] text-stone-400 w-4 text-right shrink-0 font-medium">${i + 1}</span><div class="w-8 h-8 flex items-center justify-center bg-stone-100 rounded-lg text-stone-500 text-[9px] font-bold uppercase shrink-0 border border-stone-200">${esc(f.File_Ext || '?')}</div><div class="min-w-0"><p class="text-xs font-medium text-stone-700 truncate leading-tight">${esc(f.File)}</p>${f.doc_type_name ? `<p class="text-[10px] text-stone-400 leading-tight mt-0.5">${esc(f.doc_type_name)}</p>` : `<p class="text-[10px] text-stone-300 leading-tight mt-0.5 italic">No type</p>`}</div></div><div class="flex items-center gap-1.5 shrink-0"><a href="${esc(f.File_Location)}" target="_blank" class="w-6 h-6 flex items-center justify-center rounded text-blue-400 hover:bg-blue-50 transition-colors" title="View file"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4 M14 4h6m0 0v6m0-6L10 14"/></svg></a><button class="w-6 h-6 flex items-center justify-center rounded text-red-400 hover:bg-red-50 transition-colors btn-del-sup" data-sid="${f.Support_Id}" title="Remove"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858 L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></div></div>`).join('')) }
            $(document).on('click', '.btn-del-sup', async function () { if (!activeScan || !confirm('Remove this supporting file?')) return; const sid = $(this).data('sid'); await $.ajax({ url: R.supportDel(activeScan.id, sid), method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF } }); $(`#sf-${sid}`).slideUp(150, function () { $(this).remove(); const n = $('#supportList .sf-row').length; const $badge = $('#supportCountBadge'); if (n) { $badge.text(n) } else { $badge.addClass('hidden'); $('#supportList').html(`<div class="flex flex-col items-center justify-center py-10 gap-2 text-stone-400"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8 a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg><p class="text-xs">No supporting files yet</p></div>`) } }) });
            $('#btnFinalSubmit').on('click', async function () { if (!activeScan || !confirm('Mark this scan as final submitted?')) return; await $.ajax({ url: R.finalSubmit(activeScan.id), method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF } }); dt.ajax.reload(null, false); $('#btnBackToStep1').click() });
            async function openViewModal(scanId) { try { const [supportRes] = await Promise.all([$.getJSON(R.supportList(scanId))]); const scanData = dt.rows().data().toArray().find(r => r.Scan_Id == scanId); if (!scanData) { alert('Scan not found'); return } $('#modalScanTitle').text(`Scan #${scanId}`); $('#modalScanMeta').text(scanData.Temp_Scan_Date || '—'); const mainUrl = scanData.File_Location; const mainName = scanData.File; window.__modalMainUrl = mainUrl; window.__modalMainName = mainName; let tabsHtml = `<button class="modal-tab active" data-tab="main" data-url="${esc(mainUrl)}" data-name="${esc(mainName)}">Main Scan</button>`; if (supportRes.data.length) { const grouped = {}; supportRes.data.forEach(f => { const g = f.doc_type_name || 'Other'; if (!grouped[g]) grouped[g] = []; grouped[g].push(f) }); Object.keys(grouped).forEach(gn => { tabsHtml += `<button class="modal-tab" data-tab="group" data-group="${esc(gn)}">${esc(gn)} (${grouped[gn].length})</button>` }); window.__modalGroups = grouped } else { window.__modalGroups = {} } $('#modalTabsBar').html(tabsHtml + '<div class="modal-tab-files" id="tabFilesPanel"></div>'); $('#viewScanModal,#viewScanModalBackdrop').addClass('open'); loadModalViewer(mainUrl, mainName) } catch (e) { console.error(e); alert('Failed to load scan details') } }
            $(document).on('click', '.modal-tab[data-tab="main"]', function () { $('.modal-tab').removeClass('active'); $(this).addClass('active'); $('#tabFilesPanel').removeClass('open').empty(); loadModalViewer($(this).data('url'), $(this).data('name')) });
            $(document).on('click', '.modal-tab[data-tab="group"]', function () { const gn = $(this).data('group'); const files = window.__modalGroups[gn] || []; $('.modal-tab').removeClass('active'); $(this).addClass('active'); let html = ''; files.forEach(f => { html += `<div class="file-link" data-url="${esc(f.File_Location)}" data-name="${esc(f.File)}"><span class="file-ext">${esc(f.File_Ext || '?')}</span><span>${esc(f.File)}</span></div>` }); $('#tabFilesPanel').html(html).addClass('open') });
            $(document).on('click', '#tabFilesPanel .file-link', function () { const url = $(this).data('url'); const name = $(this).data('name'); $('#tabFilesPanel .file-link').removeClass('active'); $(this).addClass('active'); loadModalViewer(url, name) });
            function loadModalViewer(url, name) { const $body = $('#modalViewerBody'); $body.find('iframe,img').remove(); $('#modalViewingFileName').text(name); const isPdf = url.toLowerCase().includes('.pdf'); const isImg = /\.(jpe?g|png|gif|webp)(\?|$)/i.test(url); if (isPdf) { $body.append(`<iframe src="${esc(url)}"></iframe>`) } else if (isImg) { $body.append(`<img src="${esc(url)}" alt="${esc(name)}">`) } else { $body.append(`<iframe src="${esc(url)}"></iframe>`) } }
            $('#btnCloseModal,#viewScanModalBackdrop').on('click', function () { $('#viewScanModal,#viewScanModalBackdrop').removeClass('open'); $('#modalTabsBar').empty(); window.__modalMainUrl = null; window.__modalMainName = null; window.__modalGroups = {} });
            let resubmitScanId = null;
            function openResubmitModal(scanId) { resubmitScanId = scanId; const scanData = dt.rows().data().toArray().find(r => r.Scan_Id == scanId); if (!scanData) { alert('Scan not found'); return } const $sel = $('#resubmitApprover'); if ($sel.data('select2')) $sel.select2('destroy'); $sel.empty(); if (scanData.Bill_Approver && scanData.approver_name) { $sel.append(new Option(scanData.approver_name, scanData.Bill_Approver, true, true)) } else { $sel.append('<option value="">Select approver</option>') } $sel.select2({ placeholder: 'Search approver…', allowClear: true, dropdownParent: $('#resubmitModal'), ajax: { url: R.approvers, dataType: 'json', delay: 250, data: (p) => ({ q: p.term || '', page: p.page || 1, location_id: scanData.Location || '' }), processResults: (d) => ({ results: d.results, pagination: d.pagination }) } }); $('#resubmitModal,#resubmitModalBackdrop').addClass('open') }
            $('#btnCloseResubmitModal,#btnCancelResubmit,#resubmitModalBackdrop').on('click', function () { $('#resubmitModal,#resubmitModalBackdrop').removeClass('open'); resubmitScanId = null });
            $('#btnConfirmResubmit').on('click', async function () { if (!resubmitScanId) return; const scanData = dt.rows().data().toArray().find(r => r.Scan_Id == resubmitScanId); const selectedApprover = $('#resubmitApprover').val(); if (!selectedApprover) { alert2('resubmitAlert', 'error', 'Please select a bill approver'); return } try { const data = { _token: CSRF }; if (selectedApprover !== String(scanData.Bill_Approver)) { data.bill_approver = selectedApprover } await $.ajax({ url: R.resubmit(resubmitScanId), method: 'POST', data, headers: { 'X-CSRF-TOKEN': CSRF } }); $('#resubmitModal,#resubmitModalBackdrop').removeClass('open'); dt.ajax.reload(null, false); alert2('uploadAlert', 'success', 'Scan resubmitted for approval successfully') } catch (err) { alert2('resubmitAlert', 'error', err.responseJSON?.message || 'Failed to resubmit') } });
            function alert2(id, type, msg) { const $e = $('#' + id).removeClass('hidden border-red-200 bg-red-50 text-red-700 border-green-200 bg-green-50 text-green-700'); $e.addClass(type === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-green-200 bg-green-50 text-green-700').text(msg).removeClass('hidden'); setTimeout(() => $e.addClass('hidden'), 6000) }
            function esc(s) { return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;') }
        });
    </script>
@endpush