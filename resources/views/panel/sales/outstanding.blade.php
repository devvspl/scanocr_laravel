@extends('layouts.app')

@section('title', 'Outstanding')
@section('page-title', 'Outstanding')

@section('breadcrumb')
    <span>Sales</span>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-stone-600">Outstanding</span>
@endsection

@section('content')
<div>
    {{-- Summary --}}
    <div class="grid grid-cols-2 gap-3 mb-4">
        <div class="bg-white border border-stone-200 rounded-xl px-4 py-3">
            <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Total Outstanding</p>
            <p id="sum-outstanding" class="text-xl font-bold text-red-700 mt-0.5">₹0.00</p>
        </div>
        <div class="bg-white border border-stone-200 rounded-xl px-4 py-3">
            <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Invoices Pending</p>
            <p id="sum-count" class="text-xl font-bold text-stone-800 mt-0.5">0</p>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="bg-white border border-stone-200 rounded-xl overflow-hidden mb-4">
        <div class="px-4 py-2.5 flex items-center justify-between gap-3 flex-wrap min-h-[52px]">
            <div class="flex items-center gap-2 flex-wrap">
                <select id="filter-ageing"
                    class="h-7 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10">
                    <option value="">All Ageing</option>
                    <option value="0-30">0–30 days</option>
                    <option value="31-60">31–60 days</option>
                    <option value="61-90">61–90 days</option>
                    <option value="90+">90+ days</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <a href="#" id="btn-export" class="inline-flex items-center gap-1.5 h-9 px-3 text-xs font-semibold text-stone-600 border border-stone-200 rounded-lg hover:bg-stone-50 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export Excel
                </a>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white border border-stone-200 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table id="outstanding-table" class="w-full">
                <thead><tr>
                    <th style="width:130px;">Invoice #</th>
                    <th style="width:90px;">Inv. Date</th>
                    <th style="width:90px;">Due Date</th>
                    <th>Customer</th>
                    <th style="width:100px;" class="dt-right">Total</th>
                    <th style="width:90px;" class="dt-right">Paid</th>
                    <th style="width:100px;" class="dt-right">Due</th>
                    <th style="width:80px;" class="dt-center">Overdue</th>
                    <th style="width:90px;" class="dt-center">Ageing</th>
                </tr></thead>
                <tbody></tbody>
            </table>
        </div>
        <div id="empty-state" class="hidden flex-col items-center justify-center py-16 text-center">
            <div class="w-12 h-12 rounded-xl bg-green-50 flex items-center justify-center mb-3 mx-auto">
                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="text-sm font-semibold text-stone-600">All clear!</p>
            <p class="text-xs text-stone-400 mt-1">No outstanding invoices. All payments are up to date.</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const table = $('#outstanding-table').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: '{{ route("sales.outstanding.data") }}',
            data(d) {
                d.ageing = $('#filter-ageing').val();
            },
            dataSrc(json) {
                if (json.summary) {
                    $('#sum-outstanding').text('₹' + json.summary.total_outstanding);
                    $('#sum-count').text(json.summary.count);
                }
                return json.data;
            },
        },
        columns: [
            { data: 'invoice_number', render(v, t, row) { return `<a href="/sales/invoices/${row.id}" class="font-mono text-xs font-semibold text-red-700 hover:underline">${v}</a>`; } },
            { data: 'invoice_date', className: 'text-xs' },
            { data: 'due_date', className: 'text-xs' },
            { data: 'party_name', render(v, t, row) { const p = row.party_phone ? `<span class="text-[10px] text-stone-400 block">${row.party_phone}</span>` : ''; return `<span class="text-xs font-medium text-stone-800">${v}</span>${p}`; } },
            { data: 'grand_total', className: 'dt-right text-xs font-mono' },
            { data: 'amount_paid', className: 'dt-right text-xs font-mono text-green-700' },
            { data: 'amount_due', className: 'dt-right text-xs font-mono font-bold text-red-700' },
            { data: 'overdue_days', className: 'dt-center text-xs', render(v) { return v > 0 ? `<span class="font-semibold text-red-600">${v}d</span>` : '<span class="text-green-600">—</span>'; } },
            { data: 'ageing_label', className: 'dt-center', render(v, t, row) { return `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold ${row.ageing_class}">${v}</span>`; } },
        ],
        order: [[6, 'desc']],
        pageLength: 25,
        pagingType: 'simple_numbers',
        dom: '<"top"lf>t<"bottom"ip>',
        drawCallback() {
            const total = this.api().page.info().recordsTotal;
            if (total === 0) { $('#outstanding-table').closest('.overflow-x-auto').hide(); $('#empty-state').removeClass('hidden').addClass('flex'); }
            else { $('#outstanding-table').closest('.overflow-x-auto').show(); $('#empty-state').removeClass('flex').addClass('hidden'); }
        },
    });

    $('#filter-ageing').on('change', () => table.ajax.reload(null, false));

    $('#btn-export').on('click', function (e) {
        e.preventDefault();
        const params = new URLSearchParams({ ageing: $('#filter-ageing').val() });
        window.location.href = '{{ route("sales.outstanding.export") }}?' + params.toString();
    });
});
</script>
@endpush
