@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<style>#main-wrap { padding-top: 32px }</style>
<div class="flex flex-col gap-3">

    {{-- ══ Filter Bar ═════════════════════════════════════════════════════════ --}}
    <div class="bg-white border border-stone-200 rounded-xl overflow-hidden" style="width:80%">
        <form method="GET" action="{{ route('dashboard') }}" style="display:flex;align-items:center;gap:.5rem;padding:.5rem 1rem;background:#fafaf9;flex-wrap:nowrap">
            <svg class="shrink-0" style="width:14px;height:14px;color:#a8a29e" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
            <select name="company_id" style="height:28px;padding:0 .5rem;font-size:.7rem;border:1px solid #d6d3d1;border-radius:.375rem;background:#fff;outline:none;color:#292524;width:30%">
                <option value="">All Companies</option>
                @foreach($companies as $co)
                    <option value="{{ $co->id }}" {{ $filterComp == $co->id ? 'selected' : '' }}>{{ $co->name }}</option>
                @endforeach
            </select>
            <select name="fy_id" style="height:28px;padding:0 .5rem;font-size:.7rem;border:1px solid #d6d3d1;border-radius:.375rem;background:#fff;outline:none;color:#292524;width:30%">
                <option value="">All Years</option>
                @foreach($financialYears as $fy)
                    <option value="{{ $fy->id }}" {{ $filterFy == $fy->id ? 'selected' : '' }}>{{ $fy->label }}</option>
                @endforeach
            </select>
            <button type="submit" style="height:28px;display:inline-flex;align-items:center;gap:.25rem;font-size:.7rem;font-weight:600;border-radius:.375rem;cursor:pointer;background:#7f1d1d;color:#fff;border:none;padding:0 .75rem;white-space:nowrap">
                <svg style="width:12px;height:12px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>Apply
            </button>
            @if($filterFy || $filterComp)
                <a href="{{ route('dashboard') }}" style="height:28px;display:inline-flex;align-items:center;font-size:.7rem;font-weight:600;border-radius:.375rem;background:#fff;color:#57534e;border:1px solid #d6d3d1;padding:0 .6rem;text-decoration:none;white-space:nowrap">Reset</a>
            @endif
        </form>
    </div>

    {{-- ══ Row 1: Top Stat Cards ══════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">

        <div class="bg-white border border-stone-200 rounded-xl p-4">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-stone-500">Total Scans</span>
                <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-stone-800">{{ number_format($totalScans) }}</p>
            <p class="text-xs text-stone-400 mt-1">{{ $todayScans }} scanned today</p>
        </div>

        <div class="bg-white border border-stone-200 rounded-xl p-4">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-stone-500">Completed</span>
                <div class="w-8 h-8 bg-green-50 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-green-700">{{ number_format($pipeline->completed ?? 0) }}</p>
            <p class="text-xs text-stone-400 mt-1">fully processed</p>
        </div>

        <div class="bg-white border border-stone-200 rounded-xl p-4">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-stone-500">In Progress</span>
                <div class="w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            @php $inProgress = ($pipeline->total ?? 0) - ($pipeline->completed ?? 0) - ($pipeline->rejected ?? 0); @endphp
            <p class="text-2xl font-bold text-amber-700">{{ number_format(max($inProgress, 0)) }}</p>
            <p class="text-xs text-stone-400 mt-1">in workflow pipeline</p>
        </div>

        <div class="bg-white border border-stone-200 rounded-xl p-4">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-stone-500">Rejected</span>
                <div class="w-8 h-8 bg-red-50 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-red-700">{{ number_format($pipeline->rejected ?? 0) }}</p>
            <p class="text-xs text-stone-400 mt-1">at any stage</p>
        </div>
    </div>

    {{-- ══ Row 2: Workflow Pipeline ═══════════════════════════════════════════ --}}
    <div class="bg-white border border-stone-200 rounded-xl p-4">
        <h3 class="text-sm font-semibold text-stone-700 mb-4">Workflow Pipeline — Stage Wise Pending</h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            @php
                $stages = [
                    ['label' => 'Bill Approval', 'count' => $pipeline->pending_bill_approval ?? 0, 'color' => 'amber', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                    ['label' => 'Classification', 'count' => $pipeline->pending_classification ?? 0, 'color' => 'blue', 'icon' => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01'],
                    ['label' => 'Punching', 'count' => $pipeline->pending_punching ?? 0, 'color' => 'purple', 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
                    ['label' => 'Punch Approval', 'count' => $pipeline->pending_punch_approval ?? 0, 'color' => 'orange', 'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'],
                    ['label' => 'Completed', 'count' => $pipeline->completed ?? 0, 'color' => 'green', 'icon' => 'M5 13l4 4L19 7'],
                ];
            @endphp
            @foreach($stages as $stage)
                <div class="border border-{{ $stage['color'] }}-200 bg-{{ $stage['color'] }}-50/50 rounded-xl p-3 text-center">
                    <div class="w-8 h-8 mx-auto mb-2 bg-{{ $stage['color'] }}-100 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-{{ $stage['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $stage['icon'] }}"/></svg>
                    </div>
                    <p class="text-xl font-bold text-{{ $stage['color'] }}-700">{{ number_format($stage['count']) }}</p>
                    <p class="text-[10px] font-medium text-{{ $stage['color'] }}-600 mt-0.5">{{ $stage['label'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Pipeline Progress Bar --}}
        @php
            $total = max($pipeline->total ?? 1, 1);
            $pBill = round((($pipeline->pending_bill_approval ?? 0) / $total) * 100, 1);
            $pClass = round((($pipeline->pending_classification ?? 0) / $total) * 100, 1);
            $pPunch = round((($pipeline->pending_punching ?? 0) / $total) * 100, 1);
            $pApproval = round((($pipeline->pending_punch_approval ?? 0) / $total) * 100, 1);
            $pDone = round((($pipeline->completed ?? 0) / $total) * 100, 1);
            $pRej = round((($pipeline->rejected ?? 0) / $total) * 100, 1);
        @endphp
        <div class="mt-4 flex rounded-full h-3 overflow-hidden bg-stone-100">
            <div class="bg-amber-400" style="width:{{ $pBill }}%" title="Bill Approval: {{ $pBill }}%"></div>
            <div class="bg-blue-400" style="width:{{ $pClass }}%" title="Classification: {{ $pClass }}%"></div>
            <div class="bg-purple-400" style="width:{{ $pPunch }}%" title="Punching: {{ $pPunch }}%"></div>
            <div class="bg-orange-400" style="width:{{ $pApproval }}%" title="Punch Approval: {{ $pApproval }}%"></div>
            <div class="bg-green-500" style="width:{{ $pDone }}%" title="Completed: {{ $pDone }}%"></div>
            <div class="bg-red-400" style="width:{{ $pRej }}%" title="Rejected: {{ $pRej }}%"></div>
        </div>
        <div class="flex flex-wrap gap-3 mt-2 text-[10px] text-stone-500">
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>Bill Approval ({{ $pBill }}%)</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-blue-400"></span>Classification ({{ $pClass }}%)</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-purple-400"></span>Punching ({{ $pPunch }}%)</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-orange-400"></span>Punch Approval ({{ $pApproval }}%)</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>Completed ({{ $pDone }}%)</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>Rejected ({{ $pRej }}%)</span>
        </div>
    </div>

    {{-- ══ Row 3: Chart + Today Activity + FY ════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">

        {{-- Monthly Trend --}}
        <div class="lg:col-span-2 bg-white border border-stone-200 rounded-xl p-4">
            <h3 class="text-sm font-semibold text-stone-700 mb-4">Scanning Activity (Last 6 Months)</h3>
            <div class="flex items-end gap-2 h-32">
                @php $maxCount = $monthlyTrend->max('count') ?: 1; @endphp
                @foreach ($monthlyTrend as $m)
                    @php $height = max(round(($m['count'] / $maxCount) * 100), 4); @endphp
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <span class="text-[10px] text-stone-500 font-medium">{{ number_format($m['count']) }}</span>
                        <div class="w-full bg-red-700 rounded-t-md transition-all" style="height: {{ $height }}%"></div>
                        <span class="text-[10px] text-stone-400">{{ $m['month'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Today's Activity + Quick Stats --}}
        <div class="bg-white border border-stone-200 rounded-xl p-4">
            <h3 class="text-sm font-semibold text-stone-700 mb-3">Today's Activity</h3>
            <div class="space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-stone-600 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>Scanned
                    </span>
                    <span class="text-sm font-bold text-stone-800">{{ $todayActivity->scanned_today ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-stone-600 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>Bill Approved
                    </span>
                    <span class="text-sm font-bold text-stone-800">{{ $todayActivity->approved_today ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-stone-600 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-purple-500"></span>Punched
                    </span>
                    <span class="text-sm font-bold text-stone-800">{{ $todayActivity->punched_today ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-stone-600 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span>Final Approved
                    </span>
                    <span class="text-sm font-bold text-stone-800">{{ $todayActivity->final_approved_today ?? 0 }}</span>
                </div>
            </div>

            {{-- Scan Type Breakdown --}}
            <div class="mt-4 pt-3 border-t border-stone-100">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-bold text-stone-500 uppercase tracking-wide">Scan Source</span>
                </div>
                <div class="flex gap-2">
                    <div class="flex-1 bg-amber-50 border border-amber-200 rounded-lg p-2 text-center">
                        <p class="text-sm font-bold text-amber-700">{{ number_format($scanTypes->temp_count ?? 0) }}</p>
                        <p class="text-[10px] text-amber-600">Temp Scan</p>
                    </div>
                    <div class="flex-1 bg-purple-50 border border-purple-200 rounded-lg p-2 text-center">
                        <p class="text-sm font-bold text-purple-700">{{ number_format($scanTypes->direct_count ?? 0) }}</p>
                        <p class="text-[10px] text-purple-600">Direct Scan</p>
                    </div>
                </div>
            </div>

            {{-- FY Progress --}}
            @if ($fyProgress !== null)
            <div class="mt-4 pt-3 border-t border-stone-100">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-xs text-stone-500">FY Progress</span>
                    <span class="text-xs font-semibold text-stone-700">{{ $fyProgress }}%</span>
                </div>
                <div class="w-full bg-stone-100 rounded-full h-1.5">
                    <div class="bg-red-700 h-1.5 rounded-full" style="width: {{ $fyProgress }}%"></div>
                </div>
                @if ($currentFY)
                    <p class="text-[10px] text-stone-400 mt-1">{{ $currentFY->label }}</p>
                @endif
            </div>
            @endif
        </div>
    </div>

    {{-- ══ Row 4: Recent Activity + Top Companies ═════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">

        {{-- Recent Scanning Activity --}}
        <div class="bg-white border border-stone-200 rounded-xl overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-stone-100">
                <h3 class="text-sm font-semibold text-stone-700">Recent Activity</h3>
                <a href="{{ route('workflow.super-scanner.index') }}" class="text-xs text-red-700 hover:underline">View all</a>
            </div>
            @if ($recentScans->isEmpty())
                <p class="text-xs text-stone-400 text-center py-8">No scans yet</p>
            @else
                <div class="divide-y divide-stone-50">
                    @foreach ($recentScans as $scan)
                        <div class="flex items-center gap-3 px-4 py-2.5 hover:bg-stone-50 transition-colors">
                            <div class="w-7 h-7 bg-stone-100 rounded-lg flex items-center justify-center shrink-0">
                                <span class="text-[9px] font-bold text-stone-500 uppercase">{{ $scan->File_Ext ?? 'DOC' }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-stone-700 truncate">{{ $scan->Document_name ?: ($scan->File ?? 'Unnamed') }}</p>
                                <p class="text-[10px] text-stone-400">{{ $scan->company_name }} &bull; {{ $scan->scanned_by ?? '—' }} &bull; {{ $scan->doc_type ?? '—' }}</p>
                            </div>
                            @php
                                if ($scan->File_Approved === 'Y') { $badge = 'Completed'; $cls = 'bg-green-100 text-green-700'; }
                                elseif ($scan->Is_Rejected === 'Y' || $scan->Bill_Approved === 'R' || $scan->temp_scan_reject === 'Y') { $badge = 'Rejected'; $cls = 'bg-red-100 text-red-600'; }
                                elseif ($scan->File_Punched === 'Y') { $badge = 'Punched'; $cls = 'bg-purple-100 text-purple-600'; }
                                elseif ($scan->Bill_Approved === 'Y') { $badge = 'Classified'; $cls = 'bg-blue-100 text-blue-600'; }
                                else { $badge = 'Pending'; $cls = 'bg-amber-100 text-amber-600'; }
                            @endphp
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full font-medium {{ $cls }} shrink-0">{{ $badge }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Top Companies --}}
        <div class="bg-white border border-stone-200 rounded-xl overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-stone-100">
                <h3 class="text-sm font-semibold text-stone-700">Top Companies</h3>
                <span class="text-[10px] text-stone-400">{{ $totalCompanies }} companies</span>
            </div>
            @if ($topCompanies->isEmpty())
                <p class="text-xs text-stone-400 text-center py-8">No data yet</p>
            @else
                @php $maxScans = $topCompanies->max('scan_count') ?: 1; @endphp
                <div class="divide-y divide-stone-50">
                    @foreach ($topCompanies as $co)
                        <div class="flex items-center gap-3 px-4 py-2.5">
                            <span class="text-xs text-stone-600 w-32 truncate font-medium">{{ $co->company_name }}</span>
                            <div class="flex-1 bg-stone-100 rounded-full h-2">
                                <div class="bg-red-700 h-2 rounded-full" style="width: {{ round(($co->scan_count / $maxScans) * 100) }}%"></div>
                            </div>
                            <span class="text-xs font-bold text-stone-700 w-10 text-right">{{ number_format($co->scan_count) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
