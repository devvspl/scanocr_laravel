@extends('layouts.app')

@section('title', 'Reports')
@section('page-title', 'Reports')

@push('head')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
.filter-section { transition: max-height .3s ease, opacity .2s ease; overflow: hidden; }
.filter-section.collapsed { max-height: 0; opacity: 0; }
.filter-section.expanded { max-height: 500px; opacity: 1; }
</style>
@endpush

@section('content')
<div x-data="reportsPage()" x-init="init()" class="flex gap-4 h-[calc(100vh-120px)]">

    {{-- ═══ LEFT PANEL — Report Selector & Filters ═══ --}}
    <div class="shrink-0 bg-white border border-stone-200 rounded-xl overflow-hidden flex flex-col transition-all duration-300"
         :class="panelOpen ? 'w-72' : 'w-10'">

        {{-- Collapse Toggle --}}
        <div class="px-2 py-2 border-b border-stone-100 flex items-center" :class="panelOpen ? 'justify-between px-3' : 'justify-center'">
            <span x-show="panelOpen" class="text-[10px] font-bold text-stone-500 uppercase tracking-wide">Reports</span>
            <button @click="panelOpen = !panelOpen" class="w-6 h-6 flex items-center justify-center rounded hover:bg-stone-100 text-stone-400 hover:text-stone-600 transition">
                <svg class="w-4 h-4 transition-transform" :class="panelOpen ? '' : 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
            </button>
        </div>

        <div x-show="panelOpen" x-transition class="flex flex-col flex-1 overflow-hidden">

        {{-- Report Type Selector --}}
        <div class="p-3 border-b border-stone-100">
            <label class="block text-[10px] font-bold text-stone-500 uppercase tracking-wide mb-1.5">Select Report</label>
            <select id="report-type-select" class="w-full">
                <option value="">— Choose Report —</option>
                @php $groups = []; foreach($reports as $k => $r) { $groups[$r['group']][$k] = $r; } @endphp
                @foreach($groups as $group => $items)
                    <optgroup label="{{ $group }}">
                        @foreach($items as $key => $report)
                            <option value="{{ $key }}">{{ $report['label'] }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>

        {{-- Filters (Collapsible) --}}
        <div class="flex-1 overflow-y-auto">

            {{-- Toggle Header --}}
            <div class="px-3 py-2 border-b border-stone-100 flex items-center justify-between cursor-pointer select-none hover:bg-stone-50"
                 @click="filtersOpen = !filtersOpen">
                <span class="text-[10px] font-bold text-stone-500 uppercase tracking-wide flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filters
                </span>
                <svg class="w-4 h-4 text-stone-400 transition-transform" :class="filtersOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>

            {{-- Filter Fields --}}
            <div class="filter-section p-3 space-y-3" :class="filtersOpen ? 'expanded' : 'collapsed'">

                <div>
                    <label class="block text-[10px] font-bold text-stone-500 uppercase tracking-wide mb-1">Company</label>
                    <select id="filter-company" class="w-full"></select>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-stone-500 uppercase tracking-wide mb-1">Financial Year</label>
                    <select id="filter-fy" class="w-full"></select>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-stone-500 uppercase tracking-wide mb-1">Location</label>
                    <select id="filter-location" class="w-full"></select>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-stone-500 uppercase tracking-wide mb-1">Document Type</label>
                    <select id="filter-doctype" class="w-full"></select>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-stone-500 uppercase tracking-wide mb-1">Vendor</label>
                    <select id="filter-vendor" class="w-full"></select>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-stone-500 uppercase tracking-wide mb-1">User</label>
                    <select id="filter-user" class="w-full"></select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[10px] font-bold text-stone-500 uppercase tracking-wide mb-1">From</label>
                        <input type="date" id="filter-from" class="w-full border border-stone-200 rounded-lg px-2 py-1.5 text-xs text-stone-700 focus:outline-none focus:border-red-700">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-stone-500 uppercase tracking-wide mb-1">To</label>
                        <input type="date" id="filter-to" class="w-full border border-stone-200 rounded-lg px-2 py-1.5 text-xs text-stone-700 focus:outline-none focus:border-red-700">
                    </div>
                </div>

                {{-- Extra filters per report type --}}
                <div x-show="reportType === 'bill_approval' || reportType === 'punch_approval'">
                    <label class="block text-[10px] font-bold text-stone-500 uppercase tracking-wide mb-1">Status</label>
                    <select id="filter-status" class="w-full border border-stone-200 rounded-lg px-2.5 py-1.5 text-xs text-stone-700 focus:outline-none focus:border-red-700">
                        <option value="">All</option>
                        <option value="Y">Approved</option>
                        <option value="N">Pending</option>
                        <option value="R">Rejected</option>
                    </select>
                </div>

                <div x-show="reportType === 'action_log'">
                    <label class="block text-[10px] font-bold text-stone-500 uppercase tracking-wide mb-1">Scan ID</label>
                    <input type="number" id="filter-scanid" placeholder="Enter Scan ID" class="w-full border border-stone-200 rounded-lg px-2.5 py-1.5 text-xs text-stone-700 focus:outline-none focus:border-red-700">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-stone-500 uppercase tracking-wide mb-1">Scan ID</label>
                    <input type="number" id="filter-scan-id" placeholder="Filter by Scan ID" class="w-full border border-stone-200 rounded-lg px-2.5 py-1.5 text-xs text-stone-700 focus:outline-none focus:border-red-700">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-stone-500 uppercase tracking-wide mb-1">Document Name</label>
                    <input type="text" id="filter-doc-name" placeholder="Search document name" class="w-full border border-stone-200 rounded-lg px-2.5 py-1.5 text-xs text-stone-700 focus:outline-none focus:border-red-700">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-stone-500 uppercase tracking-wide mb-1">File Name</label>
                    <input type="text" id="filter-file-name" placeholder="Search file name" class="w-full border border-stone-200 rounded-lg px-2.5 py-1.5 text-xs text-stone-700 focus:outline-none focus:border-red-700">
                </div>

                {{-- Clear Filters --}}
                <button @click="clearFilters()" class="w-full text-xs text-stone-500 hover:text-red-700 font-medium py-1 transition-colors">
                    Clear All Filters
                </button>
            </div>
        </div>

        {{-- Generate Button --}}
        <div class="p-3 border-t border-stone-100">
            <button @click="generate()" :disabled="!reportType || loading"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-red-800 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold transition-colors">
                <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span x-text="loading ? 'Generating...' : 'Search Data'"></span>
            </button>

            <a x-show="fileUrl" :href="fileUrl" download
               class="mt-2 w-full flex items-center justify-center gap-2 px-4 py-2 rounded-lg border border-green-600 text-green-700 text-xs font-semibold hover:bg-green-50 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Download Excel
            </a>
        </div>
        </div>{{-- end x-show panelOpen --}}
    </div>

    {{-- ═══ RIGHT PANEL — Excel Embed Viewer ═══ --}}
    <div class="flex-1 bg-white border border-stone-200 rounded-xl overflow-hidden flex flex-col">

        {{-- Toolbar --}}
        <div class="px-4 py-2.5 border-b border-stone-100 flex items-center justify-between min-h-[44px]">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="text-sm font-semibold text-stone-800" x-text="fileUrl ? fileName : 'Report Viewer'"></span>
                <span x-show="cached" class="text-[10px] bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded font-semibold">CACHED</span>
            </div>
            <div class="flex items-center gap-3">
                <div x-show="rowCount > 0" class="text-xs text-stone-500">
                    <span x-text="rowCount"></span> rows
                </div>
                <button @click="openHistory()" class="flex items-center gap-1 text-xs text-stone-500 hover:text-red-700 font-medium transition-colors" title="Export History">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    History
                </button>
            </div>
        </div>

        {{-- Viewer --}}
        <div class="flex-1 relative">
            <div x-show="!fileUrl && !loading && !errorMsg" class="absolute inset-0 flex flex-col items-center justify-center text-stone-400">
                <svg class="w-16 h-16 mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <p class="text-sm font-medium">Select a report and click "Search Data"</p>
                <p class="text-xs mt-1">The generated Excel file will display here</p>
            </div>

            <div x-show="loading" class="absolute inset-0 flex flex-col items-center justify-center text-stone-500">
                <svg class="w-10 h-10 animate-spin text-red-700 mb-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                <p class="text-sm font-medium">Generating report...</p>
            </div>

            <iframe x-show="fileUrl && !loading"
                    :src="embedUrl"
                    class="w-full h-full border-0"
                    style="min-height:500px"
                    frameborder="0"></iframe>

            <div x-show="errorMsg && !loading" class="absolute inset-0 flex flex-col items-center justify-center">
                <div class="bg-red-50 border border-red-200 rounded-xl px-6 py-4 text-center max-w-sm">
                    <svg class="w-8 h-8 text-red-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm text-red-700 font-medium" x-text="errorMsg"></p>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ OFFCANVAS — Export History ═══ --}}
    <div x-show="historyOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50" style="display:none">
        <div class="absolute inset-0 bg-black/30" @click="historyOpen = false"></div>
        <div class="absolute right-0 top-0 h-full w-96 bg-white shadow-2xl flex flex-col" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
            {{-- Header --}}
            <div class="px-5 py-4 border-b border-stone-200 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-stone-800 flex items-center gap-2">
                    <svg class="w-4 h-4 text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Export History
                </h3>
                <button @click="historyOpen = false" class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-stone-100 text-stone-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            {{-- Body --}}
            <div class="flex-1 overflow-y-auto divide-y divide-stone-100">
                <template x-if="historyLoading">
                    <div class="flex items-center justify-center py-12"><svg class="w-6 h-6 animate-spin text-red-700" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg></div>
                </template>
                <template x-if="!historyLoading && historyLogs.length === 0">
                    <div class="py-12 text-center text-sm text-stone-400">No export history found</div>
                </template>
                <template x-for="log in historyLogs" :key="log.id">
                    <div class="px-5 py-3 hover:bg-stone-50 transition-colors">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-semibold text-stone-700 capitalize" x-text="log.model.replace('_', ' ')"></span>
                            <span class="text-[10px] text-stone-400" x-text="log.created_at"></span>
                        </div>
                        <p class="text-[11px] text-stone-500 truncate mb-1.5" x-text="log.file_name"></p>
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] text-stone-400"><span x-text="log.row_count"></span> rows</span>
                            <template x-if="log.available">
                                <div class="flex items-center gap-2">
                                    <button @click="loadFromHistory(log)" class="text-[10px] font-semibold text-blue-600 hover:text-blue-800 flex items-center gap-0.5">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        View
                                    </button>
                                    <a :href="log.file_url" download class="text-[10px] font-semibold text-green-600 hover:text-green-800 flex items-center gap-0.5">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        Download
                                    </a>
                                </div>
                            </template>
                            <template x-if="!log.available">
                                <span class="text-[10px] text-stone-400 italic">File expired</span>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
function reportsPage() {
    return {
        reportType: '',
        panelOpen: true,
        filtersOpen: true,
        loading: false,
        fileUrl: '',
        fileName: '',
        embedUrl: '',
        rowCount: 0,
        errorMsg: '',
        cached: false,
        historyOpen: false,
        historyLoading: false,
        historyLogs: [],

        init() {
            this.initSelect2();
        },

        initSelect2() {
            const self = this;
            const s2Opts = (url, placeholder) => ({
                placeholder,
                allowClear: true,
                ajax: { url, dataType: 'json', delay: 250, data: p => ({ q: p.term, page: p.page || 1 }), processResults: d => d },
                width: '100%',
            });

            // Report type — local search
            $('#report-type-select').select2({ placeholder: '— Choose Report —', allowClear: true, width: '100%' }).on('change', function() {
                self.reportType = $(this).val() || '';
                self.fileUrl = ''; self.embedUrl = ''; self.errorMsg = ''; self.rowCount = 0;
            });

            // Server-side Select2
            $('#filter-company').select2(s2Opts('/reports/select/companies', 'All Companies'));
            $('#filter-fy').select2(s2Opts('/reports/select/financial-years', 'All Financial Years'));
            $('#filter-location').select2(s2Opts('/reports/select/locations', 'All Locations'));
            $('#filter-doctype').select2(s2Opts('/reports/select/doc-types', 'All Document Types'));
            $('#filter-vendor').select2(s2Opts('/reports/select/vendors', 'All Vendors'));
            $('#filter-user').select2(s2Opts('/reports/select/users', 'All Users'));

            // Pre-select "All Years" by default
            const fyOption = new Option('— All Years —', 'all', true, true);
            $('#filter-fy').append(fyOption).trigger('change');
        },

        getFilters() {
            return {
                report_type:     this.reportType,
                company_id:      $('#filter-company').val() || '',
                fy_id:           $('#filter-fy').val() || '',
                location_id:     $('#filter-location').val() || '',
                doc_type_id:     $('#filter-doctype').val() || '',
                vendor_id:       $('#filter-vendor').val() || '',
                user_id:         $('#filter-user').val() || '',
                from_date:       $('#filter-from').val() || '',
                to_date:         $('#filter-to').val() || '',
                approval_status: $('#filter-status').val() || '',
                scan_id:         $('#filter-scan-id').val() || $('#filter-scanid').val() || '',
                document_name:   $('#filter-doc-name').val() || '',
                file_name:       $('#filter-file-name').val() || '',
            };
        },

        clearFilters() {
            $('#filter-company').val(null).trigger('change');
            $('#filter-fy').val(null).trigger('change');
            $('#filter-location').val(null).trigger('change');
            $('#filter-doctype').val(null).trigger('change');
            $('#filter-vendor').val(null).trigger('change');
            $('#filter-user').val(null).trigger('change');
            $('#filter-from').val('');
            $('#filter-to').val('');
            $('#filter-status').val('');
            $('#filter-scanid').val('');
            $('#filter-scan-id').val('');
            $('#filter-doc-name').val('');
            $('#filter-file-name').val('');
        },

        async generate() {
            if (!this.reportType) return;
            this.loading = true;
            this.errorMsg = '';
            this.fileUrl = '';
            this.embedUrl = '';
            this.cached = false;

            try {
                const res = await fetch('/reports/generate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.getFilters()),
                });

                const json = await res.json();

                if (!res.ok || !json.success) {
                    this.errorMsg = json.message || 'Failed to generate report.';
                    return;
                }

                this.fileUrl  = json.file_url;
                this.fileName = json.file_name;
                this.rowCount = json.row_count;
                this.cached   = json.cached || false;
                this.embedUrl = `https://view.officeapps.live.com/op/embed.aspx?wdDownloadButton=True&src=${encodeURIComponent(json.file_url)}`;

            } catch (e) {
                this.errorMsg = 'Network error. Please try again.';
            } finally {
                this.loading = false;
            }
        },

        async openHistory() {
            this.historyOpen = true;
            this.historyLoading = true;
            try {
                const res = await fetch('/reports/export-logs', {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                const json = await res.json();
                this.historyLogs = json.data || [];
            } catch (e) {
                this.historyLogs = [];
            } finally {
                this.historyLoading = false;
            }
        },

        loadFromHistory(log) {
            this.fileUrl  = log.file_url;
            this.fileName = log.file_name;
            this.rowCount = log.row_count;
            this.cached   = true;
            this.embedUrl = `https://view.officeapps.live.com/op/embed.aspx?wdDownloadButton=True&src=${encodeURIComponent(log.file_url)}`;
            this.historyOpen = false;
            this.errorMsg = '';
        },
    };
}
</script>
@endpush
