@php $config = $widget->config ?? []; @endphp
<div class="bg-white rounded-xl border border-stone-200 p-5">
    <h3 class="text-sm font-bold text-stone-700 mb-4">{{ $widget->title }}</h3>
    <div class="h-48 flex items-center justify-center bg-stone-50 rounded-lg border border-dashed border-stone-300">
        <div class="text-center text-stone-400">
            <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <p class="text-xs">{{ ucfirst($config['chart_type'] ?? 'bar') }} Chart</p>
            <p class="text-[10px] mt-1">Data will appear once entries are submitted</p>
        </div>
    </div>
</div>
