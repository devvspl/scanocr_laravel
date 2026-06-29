@extends('layouts.app')

@section('title', 'Notification Logs')
@section('page-title', 'Notification Logs')

@section('content')
<div x-data="notifLogsPage()" x-init="load()">
    <div class="bg-white border border-stone-200 rounded-xl overflow-hidden">
        {{-- Toolbar --}}
        <div class="px-4 py-2.5 border-b border-stone-100 flex items-center justify-between gap-2 flex-wrap">
            <div class="flex items-center gap-2">
                <select id="nf-status" class="h-7 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50">
                    <option value="">All Status</option>
                    <option value="sent">Sent</option>
                    <option value="failed">Failed</option>
                    <option value="no_token">No Token</option>
                </select>
                <select id="nf-type" class="h-7 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50">
                    <option value="">All Types</option>
                    <option value="bill_assigned">Bill Assigned</option>
                    <option value="bill_approved">Bill Approved</option>
                    <option value="bill_rejected">Bill Rejected</option>
                    <option value="general">General</option>
                </select>
                <input type="text" id="nf-search" placeholder="Search user, title, scan ID..." class="h-7 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50 w-48">
                <button @click="load()" class="h-7 px-3 text-[11px] font-semibold bg-red-800 text-white rounded-md">Filter</button>
            </div>
            <div class="text-[10px] text-stone-400" x-text="'Total: ' + total + ' logs'"></div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto" style="max-height:500px;overflow-y:auto">
            <table class="w-full text-xs">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-stone-50 text-stone-500 uppercase text-[10px]">
                        <th class="px-3 py-2 text-left">User</th>
                        <th class="px-3 py-2 text-left">Type</th>
                        <th class="px-3 py-2 text-left">Title</th>
                        <th class="px-3 py-2 text-left">Body</th>
                        <th class="px-3 py-2 text-center">Scan ID</th>
                        <th class="px-3 py-2 text-center">Status</th>
                        <th class="px-3 py-2 text-left">Error</th>
                        <th class="px-3 py-2 text-left">Sent At</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="loading">
                        <tr><td colspan="8" class="text-center py-8 text-stone-400">Loading...</td></tr>
                    </template>
                    <template x-if="!loading && rows.length === 0">
                        <tr><td colspan="8" class="text-center py-8 text-stone-400">No notification logs found</td></tr>
                    </template>
                    <template x-for="r in rows" :key="r.id">
                        <tr class="border-b border-stone-50 hover:bg-stone-50">
                            <td class="px-3 py-2 font-medium" x-text="r.user_name || '—'"></td>
                            <td class="px-3 py-2">
                                <span class="inline-flex px-1.5 py-0.5 rounded text-[9px] font-semibold"
                                      :class="{
                                          'bg-blue-50 text-blue-700': r.type === 'bill_assigned',
                                          'bg-green-50 text-green-700': r.type === 'bill_approved',
                                          'bg-red-50 text-red-700': r.type === 'bill_rejected',
                                          'bg-stone-100 text-stone-600': !['bill_assigned','bill_approved','bill_rejected'].includes(r.type),
                                      }" x-text="r.type"></span>
                            </td>
                            <td class="px-3 py-2" x-text="r.title"></td>
                            <td class="px-3 py-2 max-w-[200px] truncate" x-text="r.body"></td>
                            <td class="px-3 py-2 text-center font-mono" x-text="r.scan_id || '—'"></td>
                            <td class="px-3 py-2 text-center">
                                <span class="inline-flex px-1.5 py-0.5 rounded text-[9px] font-bold"
                                      :class="{
                                          'bg-green-100 text-green-700': r.status === 'sent',
                                          'bg-red-100 text-red-700': r.status === 'failed',
                                          'bg-amber-100 text-amber-700': r.status === 'no_token',
                                      }" x-text="r.status"></span>
                            </td>
                            <td class="px-3 py-2 text-red-600 max-w-[150px] truncate" x-text="r.error_message || '—'"></td>
                            <td class="px-3 py-2 text-stone-400 whitespace-nowrap" x-text="r.sent_at"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-4 py-2.5 border-t border-stone-100 flex items-center justify-between">
            <button @click="if(page>1){page--;load()}" :disabled="page<=1" class="text-[11px] px-2.5 py-1 border border-stone-200 rounded disabled:opacity-40">Prev</button>
            <span class="text-[11px] text-stone-500" x-text="'Page ' + page + ' of ' + totalPages"></span>
            <button @click="if(page<totalPages){page++;load()}" :disabled="page>=totalPages" class="text-[11px] px-2.5 py-1 border border-stone-200 rounded disabled:opacity-40">Next</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function notifLogsPage() {
    return {
        rows: [], loading: true, total: 0, page: 1, totalPages: 1,
        async load() {
            this.loading = true;
            const params = new URLSearchParams({
                status: document.getElementById('nf-status').value,
                type: document.getElementById('nf-type').value,
                search: document.getElementById('nf-search').value,
                page: this.page.toString(),
            });
            [...params.entries()].forEach(([k,v]) => { if (!v) params.delete(k); });
            const res = await fetch('/notification-logs/data?' + params, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
            const json = await res.json();
            this.rows = json.data || [];
            this.total = json.total || 0;
            this.totalPages = json.total_pages || 1;
            this.loading = false;
        }
    };
}
</script>
@endpush
