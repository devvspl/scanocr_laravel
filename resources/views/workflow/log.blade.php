@extends('layouts.app')

@section('title', 'Workflow Log')
@section('page-title', 'Workflow Log')

@section('breadcrumb')
    <a href="{{ route('master.workflow.index') }}" class="text-stone-400 hover:text-stone-600">Workflows</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('master.workflow.designer', $workflow->id) }}" class="text-stone-400 hover:text-stone-600">{{ $workflow->name }}</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-stone-600">Log</span>
@endsection

@section('content')
<div>
    {{-- Filters --}}
    <div class="bg-white border border-stone-200 rounded-xl overflow-hidden mb-4">
        <div class="px-4 py-2.5 flex items-center gap-3">
            <input type="text" id="filter-document-ref" placeholder="Document Ref" class="h-8 px-2.5 text-xs border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 w-36">
            <input type="text" id="filter-action-key" placeholder="Action Key" class="h-8 px-2.5 text-xs border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 w-36">
            <input type="date" id="filter-date-from" class="h-8 px-2.5 text-xs border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 w-36">
            <input type="date" id="filter-date-to" class="h-8 px-2.5 text-xs border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 w-36">
            <button onclick="applyFilters()" class="tb-btn tb-btn-add h-8 text-xs">Apply</button>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white border border-stone-200 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-stone-100 bg-stone-50">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wide w-8">#</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wide">Document</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wide">Stage</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wide">Action</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wide">Performed By</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wide">From → To</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wide">Remark</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wide">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse($logs as $i => $log)
                    <tr class="hover:bg-stone-50 transition-colors">
                        <td class="px-4 py-3 text-xs text-stone-400">{{ $logs->firstItem() + $i }}</td>
                        <td class="px-4 py-3 text-xs font-mono text-stone-700">{{ $log->document_ref }}</td>
                        <td class="px-4 py-3 text-xs text-stone-600">{{ ucwords(str_replace('_', ' ', $log->system_key)) }}</td>
                        <td class="px-4 py-3 text-xs text-stone-600">{{ ucwords(str_replace('_', ' ', $log->action_key)) }}</td>
                        <td class="px-4 py-3 text-xs text-stone-600">{{ $log->performedBy?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-stone-500">
                            @if($log->from_stage_key && $log->to_stage_key)
                                {{ ucwords(str_replace('_', ' ', $log->from_stage_key)) }} → {{ ucwords(str_replace('_', ' ', $log->to_stage_key)) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-stone-500 max-w-xs truncate">{{ $log->remark ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-stone-500">{{ $log->performed_at->format('d M Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-sm text-stone-400">No logs found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div class="px-4 py-3 border-t border-stone-100">
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>

<script>
function applyFilters() {
    const params = new URLSearchParams();
    const docRef = document.getElementById('filter-document-ref').value;
    const actionKey = document.getElementById('filter-action-key').value;
    const dateFrom = document.getElementById('filter-date-from').value;
    const dateTo = document.getElementById('filter-date-to').value;

    if (docRef) params.set('document_ref', docRef);
    if (actionKey) params.set('action_key', actionKey);
    if (dateFrom) params.set('date_from', dateFrom);
    if (dateTo) params.set('date_to', dateTo);

    window.location.search = params.toString();
}
</script>
@endsection
