@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('breadcrumb')
    <span class="text-stone-600">Home</span>
@endsection

@section('content')

{{-- ── Stat Cards ─────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">

    {{-- Total Predictions --}}
    <div class="bg-white border border-stone-200 rounded-xl p-4">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-medium text-stone-500">Total Predictions</span>
            <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
        </div>
        <p class="text-2xl font-bold text-stone-800">{{ number_format($totalPredictions) }}</p>
        <p class="text-xs text-stone-400 mt-1">{{ $todayPredictions }} today</p>
    </div>

    {{-- Avg Confidence --}}
    <div class="bg-white border border-stone-200 rounded-xl p-4">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-medium text-stone-500">Avg Confidence</span>
            <div class="w-8 h-8 bg-green-50 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
        </div>
        <p class="text-2xl font-bold text-stone-800">{{ $avgConfidence ? round($avgConfidence, 1) . '%' : '—' }}</p>
        <p class="text-xs text-stone-400 mt-1">across all predictions</p>
    </div>

    {{-- Document Types --}}
    <div class="bg-white border border-stone-200 rounded-xl p-4">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-medium text-stone-500">Document Types</span>
            <div class="w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5l5 5v11a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h2z"/>
                </svg>
            </div>
        </div>
        <p class="text-2xl font-bold text-stone-800">{{ $totalDocTypes }}</p>
        <p class="text-xs text-stone-400 mt-1">active types</p>
    </div>

    {{-- Users --}}
    <div class="bg-white border border-stone-200 rounded-xl p-4">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-medium text-stone-500">Active Users</span>
            <div class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
        </div>
        <p class="text-2xl font-bold text-stone-800">{{ $totalUsers }}</p>
        <p class="text-xs text-stone-400 mt-1">registered users</p>
    </div>

</div>

{{-- ── Middle Row ──────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-4">

    {{-- Monthly Trend --}}
    <div class="lg:col-span-2 bg-white border border-stone-200 rounded-xl p-4">
        <h3 class="text-sm font-semibold text-stone-700 mb-4">Prediction Trend (Last 6 Months)</h3>
        <div class="flex items-end gap-2 h-32">
            @php $maxCount = $monthlyTrend->max('count') ?: 1; @endphp
            @foreach ($monthlyTrend as $m)
                @php $height = max(round(($m['count'] / $maxCount) * 100), 4); @endphp
                <div class="flex-1 flex flex-col items-center gap-1">
                    <span class="text-[10px] text-stone-500">{{ $m['count'] }}</span>
                    <div class="w-full bg-blue-500 rounded-t-md transition-all" style="height: {{ $height }}%"></div>
                    <span class="text-[10px] text-stone-400">{{ $m['month'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Status Breakdown --}}
    <div class="bg-white border border-stone-200 rounded-xl p-4">
        <h3 class="text-sm font-semibold text-stone-700 mb-4">Prediction Status</h3>
        @php
            $statusColors = [
                'pending'   => 'bg-stone-200 text-stone-600',
                'predicted' => 'bg-blue-100 text-blue-700',
                'confirmed' => 'bg-green-100 text-green-700',
                'corrected' => 'bg-amber-100 text-amber-700',
                'failed'    => 'bg-red-100 text-red-700',
            ];
        @endphp
        @if ($predictionsByStatus->isEmpty())
            <p class="text-xs text-stone-400 text-center py-8">No predictions yet</p>
        @else
            <div class="space-y-2">
                @foreach ($predictionsByStatus as $status => $count)
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$status] ?? 'bg-stone-100 text-stone-600' }}">
                            {{ ucfirst($status) }}
                        </span>
                        <span class="text-sm font-semibold text-stone-700">{{ number_format($count) }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($fyProgress !== null)
            <div class="mt-5 pt-4 border-t border-stone-100">
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

    {{-- Recent Predictions --}}
    <div class="bg-white border border-stone-200 rounded-xl overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-stone-100">
            <h3 class="text-sm font-semibold text-stone-700">Recent Predictions</h3>
            <a href="{{ route('document-ai.logs') }}" class="text-xs text-red-700 hover:underline">View all →</a>
        </div>
        @if ($recentPredictions->isEmpty())
            <p class="text-xs text-stone-400 text-center py-8">No predictions yet</p>
        @else
            <div class="divide-y divide-stone-50">
                @foreach ($recentPredictions as $p)
                    <div class="flex items-center gap-3 px-4 py-2.5 hover:bg-stone-50 transition-colors">
                        <div class="w-7 h-7 bg-stone-100 rounded-lg flex items-center justify-center shrink-0">
                            <span class="text-[9px] font-bold text-stone-500 uppercase">{{ $p->file_extension }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-stone-700 truncate">{{ $p->original_filename }}</p>
                            <p class="text-[10px] text-stone-400">{{ $p->predictedType?->label ?? 'Unclassified' }}</p>
                        </div>
                        @php
                            $sc = ['pending'=>'bg-stone-100 text-stone-500','predicted'=>'bg-blue-100 text-blue-600','confirmed'=>'bg-green-100 text-green-600','corrected'=>'bg-amber-100 text-amber-600','failed'=>'bg-red-100 text-red-600'];
                        @endphp
                        <span class="text-[10px] px-1.5 py-0.5 rounded-full font-medium {{ $sc[$p->status] ?? 'bg-stone-100 text-stone-500' }}">
                            {{ ucfirst($p->status) }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Top Document Types --}}
    <div class="bg-white border border-stone-200 rounded-xl overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-stone-100">
            <h3 class="text-sm font-semibold text-stone-700">Top Document Types</h3>
            <a href="{{ route('document-ai.settings') }}" class="text-xs text-red-700 hover:underline">Manage →</a>
        </div>
        @if ($topDocTypes->isEmpty())
            <p class="text-xs text-stone-400 text-center py-8">No data yet</p>
        @else
            @php $maxDt = $topDocTypes->max('count') ?: 1; @endphp
            <div class="divide-y divide-stone-50">
                @foreach ($topDocTypes as $dt)
                    <div class="flex items-center gap-3 px-4 py-2.5">
                        <span class="text-xs text-stone-600 w-28 truncate">{{ $dt->label }}</span>
                        <div class="flex-1 bg-stone-100 rounded-full h-1.5">
                            <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ round(($dt->count / $maxDt) * 100) }}%"></div>
                        </div>
                        <span class="text-xs font-semibold text-stone-700 w-6 text-right">{{ $dt->count }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>

@endsection
