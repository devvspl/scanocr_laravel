@extends('layouts.app')

@section('title', 'Sales Register')
@section('page-title', 'Sales Register')

@section('breadcrumb')
    <span>Sales</span>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-stone-600">Sales Register</span>
@endsection

@section('content')
<div>
    {{-- Summary Cards --}}
    <div id="summary-cards" class="grid grid-cols-4 gap-3 mb-4">
        <div class="bg-white border border-stone-200 rounded-xl px-4 py-3">
            <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Total Sales</p>
            <p id="sum-grand" class="text-lg font-bold text-stone-800 mt-0.5">₹0.00</p>
        </div>
        <div class="bg-white border border-stone-200 rounded-xl px-4 py-3">
            <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Taxable Amount</p>
            <p id="sum-taxable" class="text-lg font-bold text-stone-800 mt-0.5">₹0.00</p>
        </div>
        <div class="bg-white border border-stone-200 rounded-xl px-4 py-3">
            <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Total Tax</p>
            <p id="sum-tax" class="text-lg font-bold text-blue-700 mt-0.5">₹0.00</p>
        </div>
        <div class="bg-white border border-stone-200 rounded-xl px-4 py-3">
            <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Amount Due</p>
            <p id="sum-due" class="text-lg font-bold text-red-700 mt-0.5">₹0.00</p>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="bg-white border border-stone-200 rounded-xl overflow-hidden mb-4">
        <div class="px-4 py-2.5 flex items-center justify-between gap-3 min-h-[52px]">
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-1.5">
                    <span class="text-[10px] text-stone-400 font-medium whitespace-nowrap">From</span>
                    <input type="date" id="filter-date-from"
                        value="{{ $fy?->start_date?->format('Y-m-d') ?? '' }}"
                        class="h-8 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 w-36">
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="text-[10px] text-stone-400 font-medium whitespace-nowrap">To</span>
                    <input type="date" id="filter-date-to"
                        value="{{ $fy?->end_date?->format('Y-m-d') ?? '' }}"
                        class="h-8 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 w-36">
                </div>
                <select id="filter-status"
                    class="h-8 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 w-36">
                    <option value="">All Status</option>
                    <option value="approved">Approved</option>
                    <option value="submitted">Submitted</option>
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
            <table id="register-table" class="w-full">
                <thead><tr>
                    <th style="width:130px;">Invoice #</th>
                    <th style="width:90px;">Date</th>
                    <th>Customer</th>
                    <th style="width:100px;" class="dt-right">Taxable</th>
                    <th style="width:70px;" class="dt-right">CGST</th>
                    <th style="width:70px;" class="dt-right">SGST</th>
                    <th style="width:70px;" class="dt-right">IGST</th>
                    <th style="width:90px;" class="dt-right">Total Tax</th>
                    <th style="width:100px;" class="dt-right">Grand Total</th>
                    <th style="width:80px;" class="dt-center">Status</th>
                    <th style="width:90px;" class="dt-right">Due</th>
                </tr></thead>
                <tbody></tbody>
            </table>
        </div>
        <div id="empty-state" class="hidden flex-col items-center justify-center py-16 text-center">
            <div class="w-12 h-12 rounded-xl bg-stone-100 flex items-center justify-center mb-3 mx-auto">
                <svg class="w-6 h-6 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <p class="text-sm font-semibold text-stone-600">No sales data</p>
            <p class="text-xs text-stone-400 mt-1">Adjust your date range or create invoices to see the register.</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const table = $('#register-table').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: '{{ route("sales.register.data") }}',
            data(d) {
                d.date_from = $('#filter-date-from').val();
                d.date_to = $('#filter-date-to').val();
                d.status = $('#filter-status').val();
            },
            dataSrc(json) {
                if (json.totals) {
                    $('#sum-grand').text('₹' + json.totals.grand);
                    $('#sum-taxable').text('₹' + json.totals.taxable);
                    $('#sum-tax').text('₹' + json.totals.tax);
                    $('#sum-due').text('₹' + json.totals.due);
                }
                return json.data;
            },
        },
        columns: [
            { data: 'invoice_number', render(v, t, row) { return `<a href="/sales/invoices/${row.id}" class="font-mono text-xs font-semibold text-red-700 hover:underline">${v}</a>`; } },
            { data: 'invoice_date', className: 'text-xs' },
            { data: 'party_name', render(v, t, row) { const g = row.party_gstin ? `<span class="text-[10px] text-stone-400 font-mono block">${row.party_gstin}</span>` : ''; return `<span class="text-xs font-medium text-stone-800">${v}</span>${g}`; } },
            { data: 'taxable_amount', className: 'dt-right text-xs font-mono' },
            { data: 'cgst', className: 'dt-right text-xs font-mono text-stone-500' },
            { data: 'sgst', className: 'dt-right text-xs font-mono text-stone-500' },
            { data: 'igst', className: 'dt-right text-xs font-mono text-stone-500' },
            { data: 'total_tax', className: 'dt-right text-xs font-mono font-semibold text-blue-700' },
            { data: 'grand_total', className: 'dt-right text-xs font-mono font-bold text-stone-800' },
            { data: 'status', className: 'dt-center', render(v) { const cls = v === 'approved' ? 'bg-green-50 text-green-700' : 'bg-blue-50 text-blue-700'; const lbl = v === 'approved' ? 'Approved' : 'Submitted'; return `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold ${cls}">${lbl}</span>`; } },
            { data: 'amount_due', className: 'dt-right text-xs font-mono', render(v) { const n = parseFloat(v.replace(/,/g,'')); return `<span class="${n > 0 ? 'text-red-700 font-semibold' : 'text-green-700'}">₹${v}</span>`; } },
        ],
        order: [[1, 'desc']],
        pageLength: 25,
        pagingType: 'simple_numbers',
        dom: '<"top"lf>t<"bottom"ip>',
        drawCallback() {
            const total = this.api().page.info().recordsTotal;
            if (total === 0) { $('#register-table').closest('.overflow-x-auto').hide(); $('#empty-state').removeClass('hidden').addClass('flex'); }
            else { $('#register-table').closest('.overflow-x-auto').show(); $('#empty-state').removeClass('flex').addClass('hidden'); }
        },
    });

    $('#filter-date-from, #filter-date-to, #filter-status').on('change', () => table.ajax.reload(null, false));

    $('#btn-export').on('click', function (e) {
        e.preventDefault();
        const params = new URLSearchParams({
            date_from: $('#filter-date-from').val(),
            date_to: $('#filter-date-to').val(),
            status: $('#filter-status').val(),
        });
        window.location.href = '{{ route("sales.register.export") }}?' + params.toString();
    });
});
</script>
@endpush
