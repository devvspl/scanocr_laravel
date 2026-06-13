@extends('layouts.app')

@section('title', 'Proforma #' . $proforma->proforma_number)
@section('page-title', 'Proforma #' . $proforma->proforma_number)

@section('breadcrumb')
    <a href="{{ route('sales.proforma') }}" class="hover:text-stone-600">Sales</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('sales.proforma') }}" class="hover:text-stone-600">Proforma</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-stone-600">{{ $proforma->proforma_number }}</span>
@endsection

@section('content')
<div x-data="proformaShow()" x-init="init()">

    <div x-show="toast.show" x-cloak x-transition
         :class="toast.type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'"
         class="fixed bottom-5 right-5 z-50 px-4 py-3 rounded-xl border text-xs font-semibold flex items-center gap-2 shadow-lg">
        <span x-text="toast.message"></span>
    </div>

    {{-- ── ACTION BAR ── --}}
    <div class="bg-white border border-stone-200 rounded-xl mb-3 px-4 py-2.5 flex items-center justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-2.5">
            @php $badge = $proforma->statusBadge(); @endphp
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $badge['class'] }}">{{ $badge['label'] }}</span>
            @if($proforma->is_converted)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-purple-50 text-purple-700">Converted</span>
            @endif
            <span class="text-[11px] text-stone-400">Created {{ $proforma->created_at->format('d M Y, h:i A') }} by {{ $proforma->creator->name ?? 'System' }}</span>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('sales.proforma') }}" class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-stone-600 border border-stone-200 rounded-lg hover:bg-stone-50 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg> Back
            </a>

            <button @click="pdfModal.open = true" title="Download PDF" class="inline-flex items-center justify-center h-8 w-8 text-stone-600 border border-stone-200 rounded-lg hover:bg-red-50 hover:text-red-700 hover:border-red-200 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            </button>

            @if($proforma->canEdit())
            <a href="{{ route('sales.proforma.edit', $proforma) }}" class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-stone-600 border border-stone-200 rounded-lg hover:bg-stone-50 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg> Edit
            </a>
            @endif

            @if($proforma->canSubmit())
            <button @click="submit()" class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-white rounded-lg transition-colors" style="background:#2563eb;">Submit for Approval</button>
            @endif

            @if($proforma->canConvert())
            <button @click="convert()" class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-white rounded-lg transition-colors" style="background:#7c3aed;">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Convert to Invoice
            </button>
            @endif

            @if($proforma->canCancel())
            <button @click="openCancel()" class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-stone-700 border border-stone-200 rounded-lg hover:bg-stone-50 transition-colors">Cancel Proforma</button>
            @endif
        </div>
    </div>

    {{-- ── PROFORMA CARD ── --}}
    <div class="bg-white border border-stone-200 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100">
            <div class="grid grid-cols-2 gap-6 mb-5">
                <div>
                    <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-1.5">From</p>
                    <p class="text-sm font-bold text-stone-800">{{ $proforma->company->name }}</p>
                    @if($proforma->company->legal_name && $proforma->company->legal_name !== $proforma->company->name)
                        <p class="text-xs text-stone-500">{{ $proforma->company->legal_name }}</p>
                    @endif
                    @if($proforma->company->address_line1)
                        <p class="text-xs text-stone-600 mt-1">{{ $proforma->company->address_line1 }}</p>
                        @if($proforma->company->address_line2)<p class="text-xs text-stone-600">{{ $proforma->company->address_line2 }}</p>@endif
                        <p class="text-xs text-stone-600">{{ implode(', ', array_filter([$proforma->company->city, $proforma->company->state, $proforma->company->pincode])) }}</p>
                    @endif
                    @if($proforma->company->gstin)<p class="text-[10px] text-stone-400 mt-1 font-mono">GSTIN: {{ $proforma->company->gstin }}</p>@endif
                </div>
                <div>
                    <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-1.5">Bill To</p>
                    <p class="text-sm font-bold text-stone-800">{{ $proforma->party->display_name ?? $proforma->party->name }}</p>
                    @if($proforma->billing_address)<p class="text-xs text-stone-600 mt-1 whitespace-pre-line">{{ $proforma->billing_address }}</p>@endif
                    @if($proforma->party->gstin)<p class="text-[10px] text-stone-400 mt-1 font-mono">GSTIN: {{ $proforma->party->gstin }}</p>@endif
                </div>
            </div>
            <div class="flex flex-wrap gap-6 pt-4 border-t border-stone-100">
                <div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Proforma #</p><p class="text-sm text-stone-800 mt-0.5 font-mono">{{ $proforma->proforma_number }}</p></div>
                <div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Date</p><p class="text-sm text-stone-800 mt-0.5">{{ $proforma->proforma_date->format('d M Y') }}</p></div>
                <div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Due Date</p><p class="text-sm text-stone-800 mt-0.5">{{ $proforma->due_date ? $proforma->due_date->format('d M Y') : '—' }}</p></div>
                @if($proforma->reference_number)<div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Reference</p><p class="text-sm text-stone-800 mt-0.5">{{ $proforma->reference_number }}</p></div>@endif
                @if($proforma->place_of_supply)<div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Place of Supply</p><p class="text-sm text-stone-800 mt-0.5">{{ $proforma->place_of_supply }}</p></div>@endif
                <div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Tax Type</p><p class="text-sm text-stone-800 mt-0.5">{{ $proforma->is_igst ? 'IGST' : 'CGST + SGST' }}</p></div>
                @if($proforma->is_converted && $proforma->convertedInvoice)
                <div><p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Converted To</p><p class="text-sm mt-0.5"><a href="{{ route('sales.invoices.show', $proforma->converted_to_invoice_id) }}" class="text-red-700 font-mono hover:underline">{{ $proforma->convertedInvoice->invoice_number }}</a></p></div>
                @endif
            </div>
        </div>

        {{-- Line Items --}}
        <div class="overflow-x-auto">
            <table class="w-full text-xs" style="table-layout:fixed;">
                <colgroup><col style="width:36px;"><col><col style="width:80px;"><col style="width:64px;"><col style="width:52px;"><col style="width:90px;"><col style="width:90px;"><col style="width:80px;"><col style="width:90px;"><col style="width:64px;"><col style="width:100px;"></colgroup>
                <thead style="background:#f5f5f4;border-bottom:1px solid #e7e5e4;">
                    <tr>
                        <th class="py-2 px-2 text-left text-[10px] font-semibold text-stone-500 uppercase tracking-wide">#</th>
                        <th class="py-2 px-2 text-left text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Description</th>
                        <th class="py-2 px-2 text-left text-[10px] font-semibold text-stone-500 uppercase tracking-wide">HSN/SAC</th>
                        <th class="py-2 px-2 text-right text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Qty</th>
                        <th class="py-2 px-2 text-left text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Unit</th>
                        <th class="py-2 px-2 text-right text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Rate (₹)</th>
                        <th class="py-2 px-2 text-right text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Amount (₹)</th>
                        <th class="py-2 px-2 text-right text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Disc (₹)</th>
                        <th class="py-2 px-2 text-right text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Taxable (₹)</th>
                        <th class="py-2 px-2 text-right text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Tax %</th>
                        <th class="py-2 px-2 text-right text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Total (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($proforma->items as $idx => $item)
                    <tr style="border-bottom:1px solid #f5f5f4;">
                        <td class="py-2.5 px-2 text-[10px] text-stone-400 font-mono">{{ $idx + 1 }}</td>
                        <td class="py-2.5 px-2"><p class="font-medium text-stone-800 text-xs">{{ $item->description }}</p></td>
                        <td class="py-2.5 px-2 text-[10px] text-stone-500 font-mono">{{ $item->hsn_sac ?: '—' }}</td>
                        <td class="py-2.5 px-2 text-right text-xs font-semibold text-stone-800">{{ rtrim(rtrim(number_format((float)$item->qty, 3), '0'), '.') }}</td>
                        <td class="py-2.5 px-2 text-xs text-stone-600">{{ $item->unit ?: '—' }}</td>
                        <td class="py-2.5 px-2 text-right text-xs text-stone-700">{{ number_format((float)$item->unit_price, 2) }}</td>
                        <td class="py-2.5 px-2 text-right text-xs text-stone-700">{{ number_format((float)$item->qty * (float)$item->unit_price, 2) }}</td>
                        <td class="py-2.5 px-2 text-right text-xs text-stone-500">{{ $item->discount_amount > 0 ? number_format((float)$item->discount_amount, 2) : '—' }}</td>
                        <td class="py-2.5 px-2 text-right text-xs font-semibold text-stone-800">{{ number_format((float)$item->taxable_amount, 2) }}</td>
                        <td class="py-2.5 px-2 text-right text-xs text-stone-500">@if($proforma->is_igst){{ number_format((float)$item->igst_rate, 2) }}%@else{{ number_format((float)$item->cgst_rate + (float)$item->sgst_rate, 2) }}%@endif</td>
                        <td class="py-2.5 px-2 text-right text-xs font-bold text-stone-800">{{ number_format((float)$item->line_total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Digital Signatures --}}
        @php
            $approvalLogs = \App\Models\ApprovalLog::where('document_type', 'proforma')
                ->where('document_id', $proforma->id)
                ->whereNotNull('signature_path')
                ->where('action', 'approved')
                ->with('user')
                ->orderBy('level')
                ->get();
        @endphp
        @include('panel.sales._digital-signatures', ['approvalLogs' => $approvalLogs])

    </div>{{-- /proforma card --}}
</div>
@endsection
