@extends('layouts.app')

@section('title', 'Bill Date Sync')
@section('page-title', 'Bill Date Sync')

@section('content')
<div x-data="billDateSync()" x-init="init()">

    {{-- Settings nav --}}
    @include('panel.settings._nav')

    {{-- Main Card --}}
    <div class="bg-white border border-stone-200 rounded-1xl overflow-hidden">

        {{-- Card Header --}}
        <div class="px-5 py-4 border-b border-stone-100 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-sm font-semibold text-stone-800">Bill Date & Financial Year Sync</h3>
                <p class="text-xs text-stone-400 mt-0.5">Sync bill dates from punch tables and map to financial years</p>
            </div>
            {{-- Export button --}}
           <a href="{{ route('settings.bill-date-sync.export') }}"
            class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-stone-900 hover:bg-stone-800 border border-stone-900 rounded-lg transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export
            </a>
        </div>

        {{-- Statistics Cards --}}
        <div class="p-5 border-b border-stone-100">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">

                {{-- Total Scans --}}
                <div class="bg-stone-50 border border-stone-200 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] font-bold text-stone-400 uppercase tracking-widest">Total Scans</span>
                        <div class="w-7 h-7 bg-stone-200 rounded-lg flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-stone-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-stone-800">{{ number_format($totalScans) }}</p>
                    <p class="text-xs text-stone-400 mt-1">Active scan records</p>
                </div>

                {{-- With Bill Date --}}
                <div class="bg-stone-50 border border-stone-200 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] font-bold text-stone-400 uppercase tracking-widest">With Bill Date</span>
                        <div class="w-7 h-7 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-stone-800">{{ number_format($withBillDate) }}</p>
                    <p class="text-xs text-stone-400 mt-1">
                        <span class="text-green-700 font-semibold">{{ $totalScans > 0 ? round(($withBillDate / $totalScans) * 100, 1) : 0 }}%</span> completed
                    </p>
                </div>

                {{-- With Year ID --}}
                <div class="bg-stone-50 border border-stone-200 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] font-bold text-stone-400 uppercase tracking-widest">FY Mapped</span>
                        <div class="w-7 h-7 bg-red-50 rounded-lg flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-stone-800">{{ number_format($withYearId) }}</p>
                    <p class="text-xs text-stone-400 mt-1">
                        <span class="text-red-700 font-semibold">{{ $totalScans > 0 ? round(($withYearId / $totalScans) * 100, 1) : 0 }}%</span> mapped
                    </p>
                </div>

                {{-- Pending Sync --}}
                <div class="bg-stone-50 border border-stone-200 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] font-bold text-stone-400 uppercase tracking-widest">Pending</span>
                        <div class="w-7 h-7 bg-amber-100 rounded-lg flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-stone-800">{{ number_format($pendingSync) }}</p>
                    <p class="text-xs text-stone-400 mt-1">Records to process</p>
                </div>

            </div>
        </div>

        {{-- Sync Process Section --}}
        <div class="p-5 border-b border-stone-100">
            <h4 class="text-xs font-bold text-stone-400 uppercase tracking-widest mb-4">Sync Process</h4>

            {{-- Steps + Action: stacked on mobile, side-by-side on md+ --}}
            <div class="flex flex-col md:flex-row md:items-start gap-5">

                {{-- Steps list --}}
                <div class="flex-1 space-y-2.5">
                    @foreach([
                        ['Copy bill dates from', 'punchfile.BillDate', 'or', 'punchfile2.RegPurDate', 'into scan_file.bill_date'],
                        ['Map each scan to the correct financial year based on bill date', null, null, null, null],
                        ['Process records in chunks of 1,000 for optimal performance', null, null, null, null],
                    ] as $i => $step)
                    <div class="flex items-start gap-3">
                        <div class="w-5 h-5 rounded-full bg-red-50 border border-red-200 flex items-center justify-center shrink-0 mt-0.5">
                            <span class="text-[10px] font-bold text-red-700">{{ $i + 1 }}</span>
                        </div>
                        <p class="text-xs text-stone-600 leading-relaxed">
                            @if($step[1])
                                {{ $step[0] }} <code class="px-1.5 py-0.5 bg-stone-100 rounded text-[11px] font-mono text-stone-700 break-all">{{ $step[1] }}</code>
                                {{ $step[2] }} <code class="px-1.5 py-0.5 bg-stone-100 rounded text-[11px] font-mono text-stone-700 break-all">{{ $step[3] }}</code>
                                {{ $step[4] }}
                            @else
                                {{ $step[0] }}
                            @endif
                        </p>
                    </div>
                    @endforeach
                </div>

                {{-- Action panel --}}
                <div class="w-full md:w-56 shrink-0">
                    {{-- Progress bar --}}
                    <div x-show="processing" class="mb-3" style="display: none;">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-medium text-stone-600" x-text="progressText">Processing...</span>
                            <span class="text-xs font-mono text-stone-500" x-text="progressPercentage">0%</span>
                        </div>
                        <div class="w-full h-1.5 bg-stone-200 rounded-full overflow-hidden">
                            <div class="h-full bg-red-700 transition-all duration-500 rounded-full"
                                 :style="'width: ' + progressPercentage"></div>
                        </div>
                    </div>

                    {{-- Start button --}}
                    <button @click="startSync()"
                            :disabled="processing"
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-red-800 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-xl transition-colors">
                        <svg x-show="!processing" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <svg x-show="processing" class="w-4 h-4 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24" style="display:none;">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="processing ? 'Processing...' : 'Start Sync'">Start Sync</span>
                    </button>

                    <p class="text-[11px] text-stone-400 text-center mt-2">May take several minutes for large datasets</p>
                </div>

            </div>
        </div>

        {{-- Results --}}
        <div x-show="completed" class="p-5 border-b border-stone-100" style="display: none;">
            <div class="flex items-start gap-3 p-4 bg-green-50 border border-green-200 rounded-xl">
                <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-stone-800 mb-3">Sync completed successfully</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-white border border-stone-200 rounded-lg p-3 text-center">
                            <p class="text-lg font-bold text-stone-800" x-text="results.total_processed">0</p>
                            <p class="text-[10px] text-stone-400 font-medium uppercase tracking-wide mt-0.5">Processed</p>
                        </div>
                        <div class="bg-white border border-stone-200 rounded-lg p-3 text-center">
                            <p class="text-lg font-bold text-green-700" x-text="results.bill_date_updated">0</p>
                            <p class="text-[10px] text-stone-400 font-medium uppercase tracking-wide mt-0.5">Bill Dates</p>
                        </div>
                        <div class="bg-white border border-stone-200 rounded-lg p-3 text-center">
                            <p class="text-lg font-bold text-red-700" x-text="results.year_id_updated">0</p>
                            <p class="text-[10px] text-stone-400 font-medium uppercase tracking-wide mt-0.5">FY Mapped</p>
                        </div>
                        <div class="bg-white border border-stone-200 rounded-lg p-3 text-center">
                            <p class="text-lg font-bold text-amber-700" x-text="results.failed">0</p>
                            <p class="text-[10px] text-stone-400 font-medium uppercase tracking-wide mt-0.5">Failed</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Error --}}
        <div x-show="error" class="p-5 border-b border-stone-100" style="display: none;">
            <div class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-xl">
                <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-stone-800">Sync failed</p>
                    <p class="text-xs text-red-700 mt-1" x-text="errorMessage"></p>
                </div>
            </div>
        </div>

        {{-- Notes --}}
        <div class="p-5">
            <div class="flex items-start gap-3 p-4 bg-stone-50 border border-stone-200 rounded-xl">
                <svg class="w-4 h-4 text-stone-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-xs text-stone-500 space-y-1">
                    <p class="font-semibold text-stone-600">Notes</p>
                    <ul class="space-y-0.5 list-disc list-inside">
                        <li>Only records without <code class="px-1 bg-stone-100 rounded font-mono">bill_date</code> or <code class="px-1 bg-stone-100 rounded font-mono">year_id</code> will be updated</li>
                        <li>Refresh the page after completion to see updated statistics</li>
                        <li>The process is safe to run multiple times</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
function billDateSync() {
    return {
        processing: false,
        completed: false,
        error: false,
        errorMessage: '',
        progressText: 'Processing...',
        progressPercentage: '0%',
        results: {
            total_processed: 0,
            bill_date_updated: 0,
            year_id_updated: 0,
            failed: 0,
        },

        init() {},

        async startSync() {
            if (this.processing) return;
            if (!confirm('Start the bill date sync process? This may take several minutes.')) return;

            this.processing = true;
            this.completed  = false;
            this.error      = false;
            this.errorMessage  = '';
            this.progressText  = 'Starting sync...';
            this.progressPercentage = '0%';

            // Animate progress bar while waiting
            let pct = 0;
            const ticker = setInterval(() => {
                if (pct < 90) { pct += Math.random() * 8; this.progressPercentage = Math.min(pct, 90).toFixed(0) + '%'; }
            }, 800);

            try {
                const response = await fetch('{{ route("settings.bill-date-sync.process") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });

                clearInterval(ticker);
                const data = await response.json();

                if (data.success) {
                    this.progressPercentage = '100%';
                    this.progressText       = 'Sync completed!';
                    this.completed = true;
                    this.results   = data.stats;
                    setTimeout(() => window.location.reload(), 3000);
                } else {
                    this.error        = true;
                    this.errorMessage = data.message || 'An error occurred during sync.';
                }
            } catch (err) {
                clearInterval(ticker);
                this.error        = true;
                this.errorMessage = 'Failed to connect to server: ' + err.message;
            } finally {
                this.processing = false;
            }
        },
    };
}
</script>
@endpush
