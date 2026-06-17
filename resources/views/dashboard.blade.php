@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <style>
        #main-wrap {
            padding-top: 32px
        }
    </style>
    <div class="flex flex-col gap-3">
        {{-- ── Stat Cards ─────────────────────────────────────────────────────────── --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">

            {{-- Total Scans --}}
            <div class="bg-white border border-stone-200 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-stone-500">Total Scans</span>
                    <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-stone-800">{{ number_format($totalScans) }}</p>
                <p class="text-xs text-stone-400 mt-1">{{ $todayScans }} scanned today</p>
            </div>

            {{-- Total Companies --}}
            <div class="bg-white border border-stone-200 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-stone-500">Companies</span>
                    <div class="w-8 h-8 bg-green-50 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-stone-800">{{ number_format($totalCompanies) }}</p>
                <p class="text-xs text-stone-400 mt-1">accessible companies</p>
            </div>

            {{-- Temp Scanning --}}
            <div class="bg-white border border-stone-200 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-stone-500">Temp Scanning</span>
                    <div class="w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-stone-800">{{ number_format($workflowStats['temp_total']) }}</p>
                <p class="text-xs text-stone-400 mt-1">
                    <span class="text-amber-600">{{ $workflowStats['temp_pending'] }} pending</span>
                </p>
            </div>

            {{-- Direct Scanning --}}
            <div class="bg-white border border-stone-200 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-stone-500">Direct Scanning</span>
                    <div class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-stone-800">{{ number_format($workflowStats['direct_total']) }}</p>
                <p class="text-xs text-stone-400 mt-1">
                    <span class="text-purple-600">{{ $workflowStats['direct_pending'] }} pending</span>
                </p>
            </div>

        </div>

        {{-- ── Middle Row ──────────────────────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">

            {{-- Monthly Scanning Trend --}}
            <div class="lg:col-span-2 bg-white border border-stone-200 rounded-xl p-4">
                <h3 class="text-sm font-semibold text-stone-700 mb-4">Scanning Activity (Last 6 Months)</h3>
                <div class="flex items-end gap-2 h-32">
                    @php $maxCount = $monthlyTrend->max('count') ?: 1; @endphp
                    @foreach ($monthlyTrend as $m)
                        @php $height = max(round(($m['count'] / $maxCount) * 100), 4); @endphp
                        <div class="flex-1 flex flex-col items-center gap-1">
                            <span class="text-[10px] text-stone-500">{{ $m['count'] }}</span>
                            <div class="w-full bg-red-500 rounded-t-md transition-all" style="height: {{ $height }}%"></div>
                            <span class="text-[10px] text-stone-400">{{ $m['month'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Workflow Status Summary --}}
            <div class="bg-white border border-stone-200 rounded-xl p-4">
                <h3 class="text-sm font-semibold text-stone-700 mb-4">Workflow Status</h3>
                <div class="space-y-2">
                    {{-- Temp Workflow --}}
                    <div class="flex items-center justify-between">
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                            Temp Approved
                        </span>
                        <span
                            class="text-sm font-semibold text-stone-700">{{ number_format($workflowStats['temp_approved']) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                            Temp Rejected
                        </span>
                        <span
                            class="text-sm font-semibold text-stone-700">{{ number_format($workflowStats['temp_rejected']) }}</span>
                    </div>

                    {{-- Direct Workflow --}}
                    <div class="flex items-center justify-between">
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                            Direct Approved
                        </span>
                        <span
                            class="text-sm font-semibold text-stone-700">{{ number_format($workflowStats['direct_approved']) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-stone-100 text-stone-600">
                            Direct Rejected
                        </span>
                        <span
                            class="text-sm font-semibold text-stone-700">{{ number_format($workflowStats['direct_rejected']) }}</span>
                    </div>
                </div>

                {{-- Processing Status --}}
                <div class="mt-4 pt-3 border-t border-stone-100">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] text-stone-500 uppercase tracking-wide">Processing</span>
                    </div>
                    <div class="space-y-1">
                        <div class="flex justify-between text-xs">
                            <span class="text-stone-600">Pending Naming</span>
                            <span class="font-semibold">{{ $workflowStats['pending_naming'] }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-stone-600">Pending Verification</span>
                            <span class="font-semibold">{{ $workflowStats['pending_verification'] }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-stone-600">Final Submitted</span>
                            <span class="font-semibold">{{ $workflowStats['final_submitted'] }}</span>
                        </div>
                    </div>
                </div>

                @if ($fyProgress !== null)
                    <div class="mt-4 pt-3 border-t border-stone-100">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs text-stone-500">FY Progress</span>
                            <span class="text-xs font-semibold text-stone-700">{{ $fyProgress }}%</span>
                        </div>
                        <div class="w-full bg-stone-100 rounded-full h-1.5">
                            <div class="bg-red-500 h-1.5 rounded-full" style="width: {{ $fyProgress }}%"></div>
                        </div>
                        @if ($currentFY)
                            <p class="text-[10px] text-stone-400 mt-1">{{ $currentFY->label }}</p>
                        @endif
                    </div>
                @endif
            </div>

        </div>

        {{-- ── Bottom Row ──────────────────────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">

            {{-- Recent Scanning Activity --}}
            <div class="bg-white border border-stone-200 rounded-xl overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-stone-100">
                    <h3 class="text-sm font-semibold text-stone-700">Recent Scanning Activity</h3>
                    <a href="{{ route('workflow.super-scanner.index') }}" class="text-xs text-red-700 hover:underline">View
                        all →</a>
                </div>
                @if ($recentScans->isEmpty())
                    <p class="text-xs text-stone-400 text-center py-8">No scans yet</p>
                @else
                    <div class="divide-y divide-stone-50">
                        @foreach ($recentScans as $scan)
                            <div class="flex items-center gap-3 px-4 py-2.5 hover:bg-stone-50 transition-colors">
                                <div class="w-7 h-7 bg-stone-100 rounded-lg flex items-center justify-center shrink-0">
                                    <span
                                        class="text-[9px] font-bold text-stone-500 uppercase">{{ $scan->File_Ext ?? 'DOC' }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-stone-700 truncate">{{ $scan->File ?? 'Unnamed File' }}</p>
                                    <p class="text-[10px] text-stone-400">{{ $scan->company_name }} • {{ $scan->scanned_by }}</p>
                                </div>
                                @php
                                    $status = 'pending';
                                    $statusClass = 'bg-stone-100 text-stone-500';

                                    if ($scan->temp_scan_reject === 'Y' || $scan->Bill_Approved === 'R') {
                                        $status = 'rejected';
                                        $statusClass = 'bg-red-100 text-red-600';
                                    } elseif ($scan->Bill_Approved === 'Y') {
                                        $status = 'approved';
                                        $statusClass = 'bg-green-100 text-green-600';
                                    } elseif ($scan->Temp_Scan === 'Y') {
                                        $status = 'temp';
                                        $statusClass = 'bg-amber-100 text-amber-600';
                                    }
                                @endphp
                                <span class="text-[10px] px-1.5 py-0.5 rounded-full font-medium {{ $statusClass }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Top Scanning Companies --}}
            <div class="bg-white border border-stone-200 rounded-xl overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-stone-100">
                    <h3 class="text-sm font-semibold text-stone-700">Top Scanning Companies</h3>
                    <a href="{{ route('workflow.super-scanner.index') }}" class="text-xs text-red-700 hover:underline">View
                        details →</a>
                </div>
                @if ($topCompanies->isEmpty())
                    <p class="text-xs text-stone-400 text-center py-8">No scanning data yet</p>
                @else
                    @php $maxScans = $topCompanies->max('scan_count') ?: 1; @endphp
                    <div class="divide-y divide-stone-50">
                        @foreach ($topCompanies as $company)
                            <div class="flex items-center gap-3 px-4 py-2.5">
                                <span class="text-xs text-stone-600 w-28 truncate">{{ $company->company_name }}</span>
                                <div class="flex-1 bg-stone-100 rounded-full h-1.5">
                                    <div class="bg-red-500 h-1.5 rounded-full"
                                        style="width: {{ round(($company->scan_count / $maxScans) * 100) }}%"></div>
                                </div>
                                <span class="text-xs font-semibold text-stone-700 w-8 text-right">{{ $company->scan_count }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>
@endsection