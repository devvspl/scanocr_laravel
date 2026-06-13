@extends('layouts.app')

@section('title', 'AI Analytics')
@section('page-title', 'Document AI — Analytics')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="hover:text-stone-600">Home</a>
    <span>/</span>
    <a href="{{ route('document-ai.playground') }}" class="hover:text-stone-600">AI Doc Predictor</a>
    <span>/</span>
    <span class="text-stone-600">Analytics</span>
@endsection

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-stone-800">AI Prediction Analytics</h2>
            <p class="text-sm text-stone-500 mt-1">Performance metrics and decision-making insights</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('document-ai.playground') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs text-stone-600 bg-white border border-stone-200 rounded-lg hover:bg-stone-50">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Playground
            </a>
            <a href="{{ route('document-ai.logs') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs text-stone-600 bg-white border border-stone-200 rounded-lg hover:bg-stone-50">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Logs
            </a>
            <a href="{{ route('document-ai.analytics') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-red-700 rounded-lg">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Analytics
            </a>
            <a href="{{ route('document-ai.settings') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs text-stone-600 bg-white border border-stone-200 rounded-lg hover:bg-stone-50">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Settings
            </a>
        </div>
    </div>

    {{-- Date Filter --}}
    <div class="bg-white border border-stone-200 rounded-xl p-4 mb-6">
        <div class="flex items-end gap-4">
            <div>
                <label class="text-[10px] text-stone-500 uppercase font-medium">From</label>
                <input type="date" id="analytics-from" class="text-xs border border-stone-200 rounded-lg px-3 py-1.5 mt-0.5" value="{{ now()->subDays(30)->toDateString() }}">
            </div>
            <div>
                <label class="text-[10px] text-stone-500 uppercase font-medium">To</label>
                <input type="date" id="analytics-to" class="text-xs border border-stone-200 rounded-lg px-3 py-1.5 mt-0.5" value="{{ now()->toDateString() }}">
            </div>
            <button id="btn-load" class="px-4 py-1.5 text-xs font-medium text-white bg-red-700 rounded-lg hover:bg-red-800">Load</button>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white border border-stone-200 rounded-xl p-5 cursor-pointer hover:shadow-md transition-shadow" onclick="scrollToSection('chart-daily')">
            <div class="flex items-center justify-between">
                <p class="text-[10px] text-stone-500 uppercase font-medium tracking-wide">Total Predictions</p>
                <span class="w-8 h-8 bg-red-50 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </span>
            </div>
            <p id="kpi-total" class="text-3xl font-bold text-stone-800 mt-2">-</p>
            <p id="kpi-today" class="text-xs text-green-600 mt-1 font-medium"></p>
        </div>
        <div class="bg-white border border-stone-200 rounded-xl p-5 cursor-pointer hover:shadow-md transition-shadow" onclick="scrollToSection('chart-confidence')">
            <div class="flex items-center justify-between">
                <p class="text-[10px] text-stone-500 uppercase font-medium tracking-wide">Avg Confidence</p>
                <span class="w-8 h-8 bg-green-50 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </span>
            </div>
            <p id="kpi-avg-conf" class="text-3xl font-bold text-green-700 mt-2">-</p>
            <p id="kpi-conf-trend" class="text-xs mt-1 font-medium"></p>
        </div>
        <div class="bg-white border border-stone-200 rounded-xl p-5 cursor-pointer hover:shadow-md transition-shadow" onclick="scrollToSection('section-accuracy')">
            <div class="flex items-center justify-between">
                <p class="text-[10px] text-stone-500 uppercase font-medium tracking-wide">Accuracy Rate</p>
                <span class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <p id="kpi-accuracy" class="text-3xl font-bold text-blue-700 mt-2">-</p>
            <p class="text-xs text-stone-400 mt-1"><span id="kpi-confirmed">0</span> confirmed · <span id="kpi-corrected">0</span> corrected</p>
        </div>
        <div class="bg-white border border-stone-200 rounded-xl p-5 cursor-pointer hover:shadow-md transition-shadow" onclick="scrollToSection('section-status')">
            <div class="flex items-center justify-between">
                <p class="text-[10px] text-stone-500 uppercase font-medium tracking-wide">Status Pipeline</p>
                <span class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                </span>
            </div>
            <div id="kpi-status-pipeline" class="mt-2 flex rounded-full h-3 overflow-hidden bg-stone-100"></div>
            <div id="kpi-status-labels" class="flex flex-wrap gap-x-3 gap-y-0.5 mt-2"></div>
        </div>
    </div>

    {{-- Charts Row 1 --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white border border-stone-200 rounded-xl p-5" id="chart-by-type">
            <h3 class="text-sm font-semibold text-stone-700 mb-4">Predictions by Document Type</h3>
            <div id="type-bars" class="space-y-2.5"></div>
        </div>
        <div class="bg-white border border-stone-200 rounded-xl p-5">
            <h3 class="text-sm font-semibold text-stone-700 mb-4">Predictions by Department</h3>
            <div id="dept-bars" class="space-y-2.5"></div>
        </div>
    </div>

    {{-- Charts Row 2 --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white border border-stone-200 rounded-xl p-5" id="chart-confidence">
            <h3 class="text-sm font-semibold text-stone-700 mb-4">Confidence Distribution</h3>
            <div id="conf-bars" class="space-y-2.5"></div>
        </div>
        <div class="bg-white border border-stone-200 rounded-xl p-5" id="chart-daily">
            <h3 class="text-sm font-semibold text-stone-700 mb-3">Daily Trend</h3>
            <div id="daily-insight" class="hidden mb-3 p-2.5 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800"></div>
            <div id="daily-bars" class="space-y-1 max-h-56 overflow-y-auto"></div>
        </div>
    </div>

    {{-- Row 3: Error Analysis + Reviewer Activity --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6" id="section-accuracy">
        <div class="bg-white border border-stone-200 rounded-xl p-5">
            <h3 class="text-sm font-semibold text-stone-700 mb-1">Error Analysis</h3>
            <p class="text-[10px] text-stone-400 mb-4">What the AI predicted vs what users corrected — helps improve training</p>
            <div id="error-analysis" class="space-y-2"></div>
        </div>
        <div class="bg-white border border-stone-200 rounded-xl p-5">
            <h3 class="text-sm font-semibold text-stone-700 mb-1">Reviewer Activity</h3>
            <p class="text-[10px] text-stone-400 mb-4">Who confirmed predictions — team accountability</p>
            <div id="reviewer-activity" class="space-y-2"></div>
        </div>
    </div>

    {{-- Status Section --}}
    <div id="section-status" class="bg-white border border-stone-200 rounded-xl p-5 mb-6">
        <h3 class="text-sm font-semibold text-stone-700 mb-4">Status Breakdown</h3>
        <div id="status-detail" class="grid grid-cols-2 md:grid-cols-4 gap-3"></div>
    </div>
</div>

{{-- Detail Modal --}}
<div id="analytics-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" style="display:none">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[80vh] overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-5 py-3 border-b border-stone-200 bg-stone-50">
            <h3 id="modal-title" class="text-sm font-semibold text-stone-800"></h3>
            <button onclick="$('#analytics-modal').hide()" class="text-stone-400 hover:text-stone-600">✕</button>
        </div>
        <div class="flex-1 overflow-y-auto p-5" id="modal-body"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    loadAnalytics();
    $('#btn-load').on('click', loadAnalytics);
});

function loadAnalytics() {
    $.get('{{ route("document-ai.analytics.data") }}', {
        date_from: $('#analytics-from').val(),
        date_to: $('#analytics-to').val()
    }, function(d) {
        // KPIs
        $('#kpi-total').text(d.total_predictions);
        $('#kpi-today').html(d.today_count > 0 ? '↑ ' + d.today_count + ' today' : '');
        $('#kpi-avg-conf').text(d.avg_confidence + '%');
        var trendClass = d.conf_trend >= 0 ? 'text-green-600' : 'text-red-600';
        var trendIcon = d.conf_trend >= 0 ? '↑' : '↓';
        $('#kpi-conf-trend').attr('class', 'text-xs mt-1 font-medium ' + trendClass).html(d.conf_trend !== 0 ? trendIcon + ' ' + Math.abs(d.conf_trend) + '% vs yesterday' : '');
        $('#kpi-accuracy').text(d.accuracy_rate + '%');
        $('#kpi-confirmed').text(d.confirmed_count);
        $('#kpi-corrected').text(d.corrected_count);

        // Status pipeline bar
        var statusColors = { pending: '#a8a29e', predicted: '#3b82f6', confirmed: '#22c55e', corrected: '#f59e0b' };
        var pipeHtml = '', labelHtml = '';
        var total = d.total_predictions || 1;
        $.each(d.by_status, function(s, c) {
            var pct = Math.round((c / total) * 100);
            pipeHtml += '<div style="width:' + pct + '%;background:' + (statusColors[s]||'#d6d3d1') + '" title="' + s + ': ' + c + '"></div>';
            labelHtml += '<span class="text-[10px] text-stone-500"><span class="inline-block w-2 h-2 rounded-full mr-0.5" style="background:' + (statusColors[s]||'#d6d3d1') + '"></span>' + s + ' <strong>' + pct + '%</strong></span>';
        });
        $('#kpi-status-pipeline').html(pipeHtml);
        $('#kpi-status-labels').html(labelHtml);

        // Status detail cards
        var sdHtml = '';
        $.each(d.by_status, function(s, c) {
            var pct = Math.round((c / total) * 100);
            var colors = { pending:'stone', predicted:'blue', confirmed:'green', corrected:'amber' };
            var col = colors[s] || 'stone';
            sdHtml += '<div class="bg-' + col + '-50 border border-' + col + '-200 rounded-lg p-3 cursor-pointer" onclick="showStatusDetail(\'' + s + '\')">';
            sdHtml += '<p class="text-[10px] text-' + col + '-600 uppercase font-medium">' + s + '</p>';
            sdHtml += '<p class="text-xl font-bold text-' + col + '-800 mt-1">' + c + '</p>';
            sdHtml += '<p class="text-[10px] text-' + col + '-500 mt-0.5">' + pct + '% of total</p>';
            sdHtml += '</div>';
        });
        $('#status-detail').html(sdHtml || '<p class="text-xs text-stone-400 col-span-4">No data</p>');

        // By Type bars (color-coded confidence)
        var typeHtml = '';
        if (d.by_type && Object.keys(d.by_type).length) {
            var maxType = Math.max(...Object.values(d.by_type).map(function(v) { return v.count; }));
            $.each(d.by_type, function(label, info) {
                var pct = Math.round((info.count / maxType) * 100);
                var barColor = info.avg_conf >= 80 ? 'bg-green-500' : info.avg_conf >= 65 ? 'bg-amber-500' : 'bg-red-400';
                typeHtml += '<div class="flex items-center gap-2 cursor-pointer hover:bg-stone-50 rounded p-1 -mx-1" onclick="showTypeDetail(\'' + label.replace(/'/g,'\\\'') + '\',' + info.count + ',' + info.avg_conf + ')">';
                typeHtml += '<span class="w-28 text-xs text-stone-600 truncate">' + label + '</span>';
                typeHtml += '<div class="flex-1 bg-stone-100 rounded-full h-4 relative overflow-hidden">';
                typeHtml += '<div class="' + barColor + ' h-4 rounded-full transition-all" style="width:' + pct + '%"></div>';
                typeHtml += '</div>';
                typeHtml += '<span class="w-8 text-xs font-bold text-stone-700 text-right">' + info.count + '</span>';
                typeHtml += '<span class="w-12 text-[10px] text-stone-400 text-right">' + info.avg_conf + '%</span>';
                typeHtml += '</div>';
            });
        } else { typeHtml = '<p class="text-xs text-stone-400">No data</p>'; }
        $('#type-bars').html(typeHtml);

        // Department bars
        var deptHtml = '';
        if (d.by_department && d.by_department.length) {
            var maxDept = Math.max(...d.by_department.map(function(r) { return r.count; }));
            d.by_department.forEach(function(r) {
                var pct = Math.round((r.count / maxDept) * 100);
                deptHtml += '<div class="flex items-center gap-2">';
                deptHtml += '<span class="w-28 text-xs text-stone-600 truncate">' + r.department_name + ' <span class="text-stone-400">(' + r.department_code + ')</span></span>';
                deptHtml += '<div class="flex-1 bg-stone-100 rounded-full h-4"><div class="bg-blue-500 h-4 rounded-full" style="width:' + pct + '%"></div></div>';
                deptHtml += '<span class="w-8 text-xs font-bold text-stone-700 text-right">' + r.count + '</span>';
                deptHtml += '</div>';
            });
        } else { deptHtml = '<p class="text-xs text-stone-400">No data</p>'; }
        $('#dept-bars').html(deptHtml);

        // Confidence distribution
        var confColors = { '90-100%':'bg-green-500', '80-89%':'bg-emerald-400', '70-79%':'bg-amber-400', '60-69%':'bg-orange-400', '<60%':'bg-red-400' };
        var confHtml = '';
        $.each(d.confidence_distribution, function(range, count) {
            var pct = total > 0 ? Math.round((count / total) * 100) : 0;
            confHtml += '<div class="flex items-center gap-2">';
            confHtml += '<span class="w-16 text-xs text-stone-600">' + range + '</span>';
            confHtml += '<div class="flex-1 bg-stone-100 rounded-full h-4 overflow-hidden"><div class="' + (confColors[range]||'bg-stone-400') + ' h-4 rounded-full" style="width:' + (count > 0 ? Math.max(pct, 5) : 0) + '%"></div></div>';
            confHtml += '<span class="w-10 text-xs font-bold text-stone-700 text-right">' + count + '</span>';
            confHtml += '<span class="w-10 text-[10px] text-stone-400 text-right">' + pct + '%</span>';
            confHtml += '</div>';
        });
        $('#conf-bars').html(confHtml);

        // Daily trend with insight
        var dailyHtml = '';
        if (d.daily_trend && d.daily_trend.length) {
            var maxDay = Math.max(...d.daily_trend.map(function(r) { return r.count; }));
            var minDay = Math.min(...d.daily_trend.map(function(r) { return r.count; }));
            if (maxDay > 0 && minDay > 0 && maxDay / minDay >= 3) {
                $('#daily-insight').html('<svg class="w-4 h-4 text-amber-600 inline mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg><strong>Spike detected:</strong> Peak day had ' + maxDay + ' predictions (' + Math.round(maxDay/minDay) + '&times; the minimum). Check if batch uploads or new document types caused this.').show();
            } else { $('#daily-insight').hide(); }
            d.daily_trend.forEach(function(row) {
                var pct = maxDay > 0 ? Math.round((row.count / maxDay) * 100) : 0;
                var barColor = row.avg_conf >= 80 ? 'bg-green-500' : row.avg_conf >= 65 ? 'bg-amber-400' : 'bg-red-400';
                dailyHtml += '<div class="flex items-center gap-2">';
                dailyHtml += '<span class="w-20 text-[10px] text-stone-500">' + row.date + '</span>';
                dailyHtml += '<div class="flex-1 bg-stone-100 rounded-full h-3 overflow-hidden"><div class="' + barColor + ' h-3 rounded-full" style="width:' + pct + '%"></div></div>';
                dailyHtml += '<span class="w-6 text-[10px] font-bold text-stone-700 text-right">' + row.count + '</span>';
                dailyHtml += '<span class="w-10 text-[10px] text-stone-400 text-right">' + (row.avg_conf ? Math.round(row.avg_conf) + '%' : '-') + '</span>';
                dailyHtml += '</div>';
            });
        } else { dailyHtml = '<p class="text-xs text-stone-400">No data</p>'; $('#daily-insight').hide(); }
        $('#daily-bars').html(dailyHtml);

        // Error analysis
        var errHtml = '';
        if (d.corrections && d.corrections.length) {
            d.corrections.forEach(function(c) {
                errHtml += '<div class="flex items-center gap-2 p-2 bg-red-50/50 border border-red-100 rounded-lg cursor-pointer hover:bg-red-50" onclick="showCorrectionDetail(\'' + c.predicted_label.replace(/'/g,'\\\'') + '\',\'' + c.confirmed_label.replace(/'/g,'\\\'') + '\',' + c.count + ')">';
                errHtml += '<span class="text-xs text-red-700 line-through">' + c.predicted_label + '</span>';
                errHtml += '<svg class="w-3 h-3 text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>';
                errHtml += '<span class="text-xs text-green-700 font-medium">' + c.confirmed_label + '</span>';
                errHtml += '<span class="ml-auto text-[10px] bg-red-100 text-red-700 px-1.5 py-0.5 rounded-full font-bold">' + c.count + '×</span>';
                errHtml += '</div>';
            });
        } else { errHtml = '<p class="text-xs text-stone-400 italic">No corrections yet — AI is performing well or needs more reviews</p>'; }
        $('#error-analysis').html(errHtml);

        // Reviewer activity
        var revHtml = '';
        if (d.reviewer_activity && d.reviewer_activity.length) {
            d.reviewer_activity.forEach(function(r, idx) {
                var medalSvg = '';
                if (idx === 0) medalSvg = '<svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
                else if (idx === 1) medalSvg = '<svg class="w-5 h-5 text-stone-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
                else if (idx === 2) medalSvg = '<svg class="w-5 h-5 text-orange-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
                else medalSvg = '<svg class="w-5 h-5 text-stone-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>';
                revHtml += '<div class="flex items-center gap-3 p-2 bg-stone-50 rounded-lg">';
                revHtml += '<span>' + medalSvg + '</span>';
                revHtml += '<div class="flex-1"><p class="text-xs font-medium text-stone-800">' + r.name + '</p>';
                revHtml += '<p class="text-[10px] text-stone-400">' + r.confirmed + ' confirmed · ' + r.corrected + ' corrected</p></div>';
                revHtml += '<span class="text-sm font-bold text-stone-700">' + r.total + '</span>';
                revHtml += '</div>';
            });
        } else { revHtml = '<p class="text-xs text-stone-400 italic">No review activity yet</p>'; }
        $('#reviewer-activity').html(revHtml);
    });
}

function scrollToSection(id) {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function showTypeDetail(label, count, avgConf) {
    var color = avgConf >= 80 ? 'green' : avgConf >= 65 ? 'amber' : 'red';
    var html = '<div class="space-y-3">';
    html += '<div class="bg-' + color + '-50 border border-' + color + '-200 rounded-lg p-4 text-center">';
    html += '<p class="text-2xl font-bold text-' + color + '-800">' + count + ' predictions</p>';
    html += '<p class="text-sm text-' + color + '-600 mt-1">Avg confidence: ' + avgConf + '%</p>';
    html += '</div>';
    html += '<p class="text-xs text-stone-500">This document type accounts for a portion of your total predictions. ';
    html += avgConf >= 80 ? 'The AI is performing well on this type.' : 'Consider adding more training samples to improve accuracy.';
    html += '</p></div>';
    $('#modal-title').text(label + ' — Breakdown');
    $('#modal-body').html(html);
    $('#analytics-modal').show();
}

function showCorrectionDetail(predicted, confirmed, count) {
    var html = '<div class="space-y-3">';
    html += '<div class="bg-red-50 border border-red-200 rounded-lg p-4">';
    html += '<p class="text-xs text-red-600 uppercase font-medium">AI Predicted</p>';
    html += '<p class="text-lg font-bold text-red-800 line-through">' + predicted + '</p>';
    html += '</div>';
    html += '<div class="flex justify-center"><svg class="w-5 h-5 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg></div>';
    html += '<div class="bg-green-50 border border-green-200 rounded-lg p-4">';
    html += '<p class="text-xs text-green-600 uppercase font-medium">User Corrected To</p>';
    html += '<p class="text-lg font-bold text-green-800">' + confirmed + '</p>';
    html += '</div>';
    html += '<div class="bg-amber-50 border border-amber-200 rounded-lg p-3">';
    html += '<p class="text-xs text-amber-700"><strong>Happened ' + count + ' time(s).</strong> To fix this, go to Training Settings and add more samples for "' + confirmed + '" with keywords that distinguish it from "' + predicted + '".</p>';
    html += '</div></div>';
    $('#modal-title').text('Error Analysis: ' + predicted + ' → ' + confirmed);
    $('#modal-body').html(html);
    $('#analytics-modal').show();
}

function showStatusDetail(status) {
    var descriptions = {
        pending: 'Documents uploaded but not yet processed by the AI.',
        predicted: 'AI has made a prediction but no user has reviewed it yet.',
        confirmed: 'User confirmed the AI prediction was correct.',
        corrected: 'User corrected the AI prediction — this data helps improve the model.'
    };
    var html = '<div class="space-y-3">';
    html += '<p class="text-sm text-stone-700">' + (descriptions[status] || '') + '</p>';
    html += '<a href="{{ route("document-ai.logs") }}?status=' + status + '" class="inline-flex items-center gap-1 text-xs text-red-600 font-medium hover:text-red-800">View all ' + status + ' predictions →</a>';
    html += '</div>';
    $('#modal-title').text('Status: ' + status.charAt(0).toUpperCase() + status.slice(1));
    $('#modal-body').html(html);
    $('#analytics-modal').show();
}
</script>
@endpush
