@extends('layouts.app')

@section('title', 'Invoice #' . $invoice->invoice_number)
@section('page-title', 'Invoice #' . $invoice->invoice_number)

@section('breadcrumb')
    <a href="{{ route('sales.invoices') }}" class="hover:text-stone-600">Sales</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('sales.invoices') }}" class="hover:text-stone-600">Invoices</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-stone-600">{{ $invoice->invoice_number }}</span>
@endsection

@section('content')
<div x-data="invoiceShow()" x-init="init()">

    {{-- Toast --}}
    <div x-show="toast.show" x-cloak x-transition
         :class="toast.type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'"
         class="fixed bottom-5 right-5 z-50 px-4 py-3 rounded-xl border text-xs font-semibold flex items-center gap-2 shadow-lg">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  :d="toast.type === 'error' ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'"/>
        </svg>
        <span x-text="toast.message"></span>
    </div>

    {{-- ── ACTION BAR ──────────────────────────────────────────────────────── --}}
    <div class="bg-white border border-stone-200 rounded-xl mb-3 px-4 py-2.5 flex items-center justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-2.5">
            @php $badge = $invoice->statusBadge(); @endphp
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $badge['class'] }}">
                {{ $badge['label'] }}
            </span>
            <span class="text-[11px] text-stone-400">
                Created {{ $invoice->created_at->format('d M Y, h:i A') }} by {{ $invoice->creator->name ?? 'System' }}
            </span>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('sales.invoices') }}"
               class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-stone-600 border border-stone-200 rounded-lg hover:bg-stone-50 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back
            </a>

            {{-- PDF Download --}}
            <button @click="pdfModal.open = true"
                    title="Download PDF"
                    class="inline-flex items-center justify-center h-8 w-8 text-stone-600 border border-stone-200 rounded-lg hover:bg-red-50 hover:text-red-700 hover:border-red-200 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 11v6m-3-3h6"/>
                </svg>
            </button>

            @if($invoice->canEdit())
            <a href="{{ route('sales.invoices.edit', $invoice) }}"
               class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-stone-600 border border-stone-200 rounded-lg hover:bg-stone-50 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit
            </a>
            @endif

            @if($invoice->canSubmit())
            <button @click="submit()"
                    class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-white rounded-lg transition-colors"
                    style="background:#2563eb;" onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='#2563eb'">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                Submit for Approval
            </button>
            @endif

            @if($invoice->canApprove())
            <button @click="approve()"
                    class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-white rounded-lg transition-colors"
                    style="background:#16a34a;" onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Approve
            </button>
            @endif

            @if($invoice->canReject())
            <button @click="openReject()"
                    class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-white rounded-lg transition-colors"
                    style="background:#dc2626;" onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                Reject
            </button>
            @endif

            @if($invoice->canCancel())
            <button @click="openCancel()"
                    class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-stone-700 border border-stone-200 rounded-lg hover:bg-stone-50 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                Cancel Invoice
            </button>
            @endif
        </div>
    </div>

    {{-- ── INVOICE CARD ─────────────────────────────────────────────────────── --}}
    <div class="bg-white border border-stone-200 rounded-xl overflow-hidden">

        {{-- Header: From / Bill To / Meta --}}
        <div class="px-5 py-4 border-b border-stone-100">
            <div class="grid grid-cols-2 gap-6 mb-5">

                {{-- From --}}
                <div>
                    <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-1.5">From</p>
                    <p class="text-sm font-bold text-stone-800">{{ $invoice->company->name }}</p>
                    @if($invoice->company->legal_name && $invoice->company->legal_name !== $invoice->company->name)
                        <p class="text-xs text-stone-500">{{ $invoice->company->legal_name }}</p>
                    @endif
                    @if($invoice->company->address_line1)
                        <p class="text-xs text-stone-600 mt-1">{{ $invoice->company->address_line1 }}</p>
                        @if($invoice->company->address_line2)
                            <p class="text-xs text-stone-600">{{ $invoice->company->address_line2 }}</p>
                        @endif
                        <p class="text-xs text-stone-600">{{ implode(', ', array_filter([$invoice->company->city, $invoice->company->state, $invoice->company->pincode])) }}</p>
                    @endif
                    @if($invoice->company->gstin)
                        <p class="text-[10px] text-stone-400 mt-1 font-mono">GSTIN: {{ $invoice->company->gstin }}</p>
                    @endif
                </div>

                {{-- Bill To --}}
                <div>
                    <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-1.5">Bill To</p>
                    <p class="text-sm font-bold text-stone-800">{{ $invoice->party->display_name ?? $invoice->party->name }}</p>
                    @if($invoice->billing_address)
                        <p class="text-xs text-stone-600 mt-1 whitespace-pre-line">{{ $invoice->billing_address }}</p>
                    @endif
                    @if($invoice->party->gstin)
                        <p class="text-[10px] text-stone-400 mt-1 font-mono">GSTIN: {{ $invoice->party->gstin }}</p>
                    @endif
                </div>
            </div>

            {{-- Meta row --}}
            <div class="flex flex-wrap gap-6 pt-4 border-t border-stone-100">
                <div>
                    <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Invoice #</p>
                    <p class="text-sm text-stone-800 mt-0.5 font-mono">{{ $invoice->invoice_number }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Invoice Date</p>
                    <p class="text-sm text-stone-800 mt-0.5">{{ $invoice->invoice_date->format('d M Y') }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Due Date</p>
                    <p class="text-sm mt-0.5 {{ $invoice->due_date && $invoice->due_date->isPast() && !$invoice->isApproved() ? 'text-red-600' : 'text-stone-800' }}">
                        {{ $invoice->due_date ? $invoice->due_date->format('d M Y') : '—' }}
                    </p>
                </div>
                @if($invoice->reference_number)
                <div>
                    <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Reference</p>
                    <p class="text-sm  text-stone-800 mt-0.5">{{ $invoice->reference_number }}</p>
                </div>
                @endif
                @if($invoice->place_of_supply)
                <div>
                    <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Place of Supply</p>
                    <p class="text-sm  text-stone-800 mt-0.5">{{ $invoice->place_of_supply }}</p>
                </div>
                @endif
                <div>
                    <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide">Tax Type</p>
                    <p class="text-sm text-stone-800 mt-0.5">{{ $invoice->is_igst ? 'IGST' : 'CGST + SGST' }}</p>
                </div>
            </div>
        </div>

        {{-- Line Items --}}
        <div class="overflow-x-auto">
            <table class="w-full text-xs" style="table-layout:fixed;">
                <colgroup>
                    <col style="width:36px;">
                    <col>
                    <col style="width:80px;">
                    <col style="width:64px;">
                    <col style="width:52px;">
                    <col style="width:90px;">
                    <col style="width:90px;">
                    <col style="width:80px;">
                    <col style="width:90px;">
                    <col style="width:64px;">
                    <col style="width:100px;">
                </colgroup>
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
                    @foreach($invoice->items as $idx => $item)
                    <tr style="border-bottom:1px solid #f5f5f4;">
                        <td class="py-2.5 px-2 text-[10px] text-stone-400 font-mono">{{ $idx + 1 }}</td>
                        <td class="py-2.5 px-2">
                            <p class="font-medium text-stone-800 text-xs">{{ $item->description }}</p>
                            @if($item->product && $item->product->name !== $item->description)
                                <p class="text-[10px] text-stone-400 mt-0.5">{{ $item->product->name }}</p>
                            @endif
                        </td>
                        <td class="py-2.5 px-2 text-[10px] text-stone-500 font-mono">{{ $item->hsn_sac ?: '—' }}</td>
                        <td class="py-2.5 px-2 text-right text-xs font-semibold text-stone-800">{{ rtrim(rtrim(number_format((float)$item->qty, 3), '0'), '.') }}</td>
                        <td class="py-2.5 px-2 text-xs text-stone-600">{{ $item->unit ?: '—' }}</td>
                        <td class="py-2.5 px-2 text-right text-xs text-stone-700">{{ number_format((float)$item->unit_price, 2) }}</td>
                        <td class="py-2.5 px-2 text-right text-xs text-stone-700">{{ number_format((float)$item->qty * (float)$item->unit_price, 2) }}</td>
                        <td class="py-2.5 px-2 text-right text-xs text-stone-500">
                            {{ $item->discount_amount > 0 ? number_format((float)$item->discount_amount, 2) : '—' }}
                        </td>
                        <td class="py-2.5 px-2 text-right text-xs font-semibold text-stone-800">{{ number_format((float)$item->taxable_amount, 2) }}</td>
                        <td class="py-2.5 px-2 text-right text-xs text-stone-500">
                            @if($invoice->is_igst)
                                {{ number_format((float)$item->igst_rate, 2) }}%
                            @else
                                {{ number_format((float)$item->cgst_rate + (float)$item->sgst_rate, 2) }}%
                            @endif
                        </td>
                        <td class="py-2.5 px-2 text-right text-xs font-bold text-stone-800">{{ number_format((float)$item->line_total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Totals + Notes --}}
        <div class="flex items-stretch divide-x divide-stone-100 border-t border-stone-200">

            {{-- Notes / Terms / Narration --}}
            <div class="flex-1 px-5 py-4 flex flex-col gap-3">
                @if($invoice->notes)
                <div>
                    <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-1">Notes</p>
                    <p class="text-xs text-stone-600 whitespace-pre-line">{{ $invoice->notes }}</p>
                </div>
                @endif
                @if($invoice->terms)
                <div>
                    <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-1">Terms &amp; Conditions</p>
                    <p class="text-xs text-stone-600 whitespace-pre-line">{{ $invoice->terms }}</p>
                </div>
                @endif
                @if($invoice->narration)
                <div>
                    <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-1">Narration</p>
                    <p class="text-xs text-stone-500 italic whitespace-pre-line">{{ $invoice->narration }}</p>
                </div>
                @endif
            </div>

            {{-- Totals --}}
            <div class="px-5 py-4 min-w-[260px] flex flex-col justify-end gap-1">
                <div class="flex justify-between text-xs text-stone-600">
                    <span>Subtotal</span>
                    <span class="font-medium text-stone-800">₹ {{ number_format((float)$invoice->subtotal, 2) }}</span>
                </div>
                @if($invoice->discount_amount > 0)
                <div class="flex justify-between text-xs text-stone-600">
                    <span>Discount</span>
                    <span class="font-medium text-red-600">− ₹ {{ number_format((float)$invoice->discount_amount, 2) }}</span>
                </div>
                @endif
                <div class="flex justify-between text-xs text-stone-600">
                    <span>Taxable</span>
                    <span class="font-medium text-stone-800">₹ {{ number_format((float)$invoice->taxable_amount, 2) }}</span>
                </div>
                @if($invoice->is_igst)
                    @if($invoice->igst_amount > 0)
                    <div class="flex justify-between text-xs text-stone-600">
                        <span>IGST</span>
                        <span class="font-medium text-stone-800">₹ {{ number_format((float)$invoice->igst_amount, 2) }}</span>
                    </div>
                    @endif
                @else
                    @if($invoice->cgst_amount > 0)
                    <div class="flex justify-between text-xs text-stone-600">
                        <span>CGST</span>
                        <span class="font-medium text-stone-800">₹ {{ number_format((float)$invoice->cgst_amount, 2) }}</span>
                    </div>
                    @endif
                    @if($invoice->sgst_amount > 0)
                    <div class="flex justify-between text-xs text-stone-600">
                        <span>SGST</span>
                        <span class="font-medium text-stone-800">₹ {{ number_format((float)$invoice->sgst_amount, 2) }}</span>
                    </div>
                    @endif
                @endif
                <div class="flex justify-between items-center pt-2 mt-1 border-t border-stone-200">
                    <span class="text-sm font-bold text-stone-800">Grand Total</span>
                    <span class="text-base font-bold" style="color:#7f1d1d;">₹ {{ number_format((float)$invoice->grand_total, 2) }}</span>
                </div>
                @php
                    $advanceAmt = (float)$invoice->advance_amount;
                    $totalPaid = (float)$invoice->amount_paid;
                    $additionalPaid = max($totalPaid - $advanceAmt, 0);
                @endphp
                @if($advanceAmt > 0)
                <div class="flex justify-between text-xs text-stone-600 mt-1">
                    <span>Advance Payment</span>
                    <span class="font-semibold text-blue-700">− ₹ {{ number_format($advanceAmt, 2) }}</span>
                </div>
                @endif
                @if($additionalPaid > 0)
                <div class="flex justify-between text-xs text-stone-600 mt-1">
                    <span>Additional Payment</span>
                    <span class="font-semibold text-green-700">− ₹ {{ number_format($additionalPaid, 2) }}</span>
                </div>
                @endif
                @if($advanceAmt > 0 || $additionalPaid > 0)
                <div class="flex justify-between text-xs font-bold mt-1">
                    <span class="text-stone-700">Amount Due</span>
                    <span style="color:#7f1d1d;">₹ {{ number_format((float)$invoice->amount_due, 2) }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Workflow history --}}
        <div class="px-5 py-3 border-t border-stone-100" style="background:#fafaf9;">
            <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-2">Workflow History</p>
            <div class="flex flex-wrap gap-x-6 gap-y-1 text-[11px]">
                <span class="text-stone-500">Created: <span class="font-semibold text-stone-700">{{ $invoice->created_at->format('d M Y, h:i A') }}</span> by {{ $invoice->creator->name ?? 'System' }}</span>
                @if($invoice->submitted_at)
                <span class="text-stone-500">Submitted: <span class="font-semibold text-blue-700">{{ $invoice->submitted_at->format('d M Y, h:i A') }}</span> by {{ $invoice->submitter->name ?? '—' }}</span>
                @endif
                @if($invoice->approved_at)
                <span class="text-stone-500">Approved: <span class="font-semibold text-green-700">{{ $invoice->approved_at->format('d M Y, h:i A') }}</span> by {{ $invoice->approver->name ?? '—' }}</span>
                @endif
                @if($invoice->rejected_at)
                <span class="text-stone-500">Rejected: <span class="font-semibold text-red-700">{{ $invoice->rejected_at->format('d M Y, h:i A') }}</span> by {{ $invoice->rejecter->name ?? '—' }}
                    @if($invoice->rejection_reason) — <em>{{ $invoice->rejection_reason }}</em>@endif
                </span>
                @endif
                @if($invoice->cancelled_at)
                <span class="text-stone-500">Cancelled: <span class="font-semibold text-amber-700">{{ $invoice->cancelled_at->format('d M Y, h:i A') }}</span>
                    @if($invoice->cancel_reason) — <em>{{ $invoice->cancel_reason }}</em>@endif
                </span>
                @endif
            </div>
        </div>

        {{-- Digital Signatures --}}
        @php
            $approvalLogs = \App\Models\ApprovalLog::where('document_type', 'invoice')
                ->where('document_id', $invoice->id)
                ->whereNotNull('signature_path')
                ->where('action', 'approved')
                ->with('user')
                ->orderBy('level')
                ->get();
        @endphp
        @include('panel.sales._digital-signatures', ['approvalLogs' => $approvalLogs])

    </div>{{-- /invoice card --}}

    {{-- PDF Template Picker Modal --}}
    <div x-show="pdfModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="pdfModal.open=false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

            {{-- Modal header --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-stone-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background:#fef2f2;">
                        <svg class="w-4 h-4" style="color:#b91c1c;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-stone-800">Download Invoice PDF</p>
                        <p class="text-[11px] text-stone-400">Choose a template style</p>
                    </div>
                </div>
                <button @click="pdfModal.open=false" class="w-7 h-7 flex items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Template grid --}}
            <div class="p-5 grid grid-cols-3 gap-3">

                {{-- Template 1: Classic Professional --}}
                <button @click="downloadPdf(1)"
                        class="group relative flex flex-col rounded-xl border-2 transition-all duration-150 overflow-hidden text-left"
                        :class="pdfModal.selected===1 ? 'border-stone-800 shadow-md' : 'border-stone-200 hover:border-stone-400'">
                    {{-- Mini preview --}}
                    <div class="w-full h-24 bg-white flex flex-col p-2 gap-1 pointer-events-none">
                        <div class="flex justify-between items-start">
                            <div class="flex flex-col gap-0.5">
                                <div class="h-2 w-16 rounded-sm bg-stone-800"></div>
                                <div class="h-1 w-10 rounded-sm bg-stone-300"></div>
                            </div>
                            <div class="flex flex-col items-end gap-0.5">
                                <div class="h-3 w-12 rounded-sm bg-stone-800"></div>
                                <div class="h-1 w-8 rounded-sm bg-stone-400"></div>
                            </div>
                        </div>
                        <div class="h-px bg-stone-800 mt-1"></div>
                        <div class="flex flex-col gap-0.5 mt-1">
                            <div class="h-1 w-full rounded-sm bg-stone-800"></div>
                            <div class="h-1 w-full rounded-sm bg-stone-100"></div>
                            <div class="h-1 w-full rounded-sm bg-stone-800"></div>
                            <div class="h-1 w-full rounded-sm bg-stone-100"></div>
                        </div>
                        <div class="flex justify-end mt-1">
                            <div class="h-2 w-14 rounded-sm bg-stone-800"></div>
                        </div>
                    </div>
                    <div class="px-2.5 py-2 border-t border-stone-100 bg-stone-50">
                        <p class="text-[11px] font-semibold text-stone-700">Classic Professional</p>
                        <p class="text-[10px] text-stone-400 mt-0.5">Black &amp; white, formal</p>
                    </div>
                    <div x-show="pdfModal.selected===1" class="absolute top-1.5 right-1.5 w-4 h-4 rounded-full bg-stone-800 flex items-center justify-center">
                        <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </div>
                </button>

                {{-- Template 2: Modern Blue --}}
                <button @click="downloadPdf(2)"
                        class="group relative flex flex-col rounded-xl border-2 transition-all duration-150 overflow-hidden text-left"
                        :class="pdfModal.selected===2 ? 'border-blue-600 shadow-md' : 'border-stone-200 hover:border-blue-300'">
                    <div class="w-full h-24 bg-white flex flex-col overflow-hidden pointer-events-none">
                        <div class="h-1.5 w-full bg-blue-600"></div>
                        <div class="flex flex-col p-2 gap-1 flex-1">
                            <div class="flex justify-between items-start">
                                <div class="flex flex-col gap-0.5">
                                    <div class="h-2 w-16 rounded-sm bg-stone-700"></div>
                                    <div class="h-1 w-10 rounded-sm bg-stone-300"></div>
                                </div>
                                <div class="h-8 w-14 rounded bg-blue-600 flex flex-col items-center justify-center gap-0.5 p-1">
                                    <div class="h-1.5 w-8 rounded-sm bg-white opacity-90"></div>
                                    <div class="h-1 w-6 rounded-sm bg-blue-200"></div>
                                </div>
                            </div>
                            <div class="flex flex-col gap-0.5 mt-1">
                                <div class="h-1 w-full rounded-sm bg-blue-600"></div>
                                <div class="h-1 w-full rounded-sm bg-blue-50"></div>
                                <div class="h-1 w-full rounded-sm bg-blue-600"></div>
                            </div>
                            <div class="flex justify-end mt-1">
                                <div class="h-2.5 w-16 rounded bg-blue-600"></div>
                            </div>
                        </div>
                    </div>
                    <div class="px-2.5 py-2 border-t border-stone-100 bg-stone-50">
                        <p class="text-[11px] font-semibold text-stone-700">Modern Blue</p>
                        <p class="text-[10px] text-stone-400 mt-0.5">Blue accent, corporate</p>
                    </div>
                    <div x-show="pdfModal.selected===2" class="absolute top-1.5 right-1.5 w-4 h-4 rounded-full bg-blue-600 flex items-center justify-center">
                        <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </div>
                </button>

                {{-- Template 3: Elegant Gold --}}
                <button @click="downloadPdf(3)"
                        class="group relative flex flex-col rounded-xl border-2 transition-all duration-150 overflow-hidden text-left"
                        :class="pdfModal.selected===3 ? 'border-amber-500 shadow-md' : 'border-stone-200 hover:border-amber-300'">
                    <div class="w-full h-24 bg-white flex flex-col overflow-hidden pointer-events-none">
                        <div class="h-1.5 w-full" style="background:#b45309;"></div>
                        <div class="flex flex-col p-2 gap-1 flex-1">
                            <div class="flex justify-between items-start">
                                <div class="flex flex-col gap-0.5">
                                    <div class="h-2 w-16 rounded-sm bg-stone-800"></div>
                                    <div class="h-1 w-10 rounded-sm bg-stone-300"></div>
                                </div>
                                <div class="flex flex-col items-end gap-0.5">
                                    <div class="h-2.5 w-12 rounded-sm" style="color:#b45309; border-bottom:2px solid #b45309;"></div>
                                    <div class="h-1 w-8 rounded-sm bg-stone-300"></div>
                                </div>
                            </div>
                            <div class="h-px mt-1" style="background:#b45309;"></div>
                            <div class="flex flex-col gap-0.5 mt-0.5">
                                <div class="h-1 w-full rounded-sm" style="border-bottom:1px solid #b45309;"></div>
                                <div class="h-1 w-full rounded-sm bg-amber-50"></div>
                                <div class="h-1 w-full rounded-sm bg-stone-200"></div>
                            </div>
                            <div class="flex justify-end mt-1">
                                <div class="h-2 w-14 rounded-sm" style="border-top:2px solid #b45309;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="px-2.5 py-2 border-t border-stone-100 bg-stone-50">
                        <p class="text-[11px] font-semibold text-stone-700">Elegant Gold</p>
                        <p class="text-[10px] text-stone-400 mt-0.5">White, gold accent lines</p>
                    </div>
                    <div x-show="pdfModal.selected===3" class="absolute top-1.5 right-1.5 w-4 h-4 rounded-full flex items-center justify-center" style="background:#b45309;">
                        <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </div>
                </button>

                {{-- Template 4: Minimal Clean --}}
                <button @click="downloadPdf(4)"
                        class="group relative flex flex-col rounded-xl border-2 transition-all duration-150 overflow-hidden text-left"
                        :class="pdfModal.selected===4 ? 'border-gray-400 shadow-md' : 'border-stone-200 hover:border-gray-400'">
                    <div class="w-full h-24 bg-white flex flex-col p-2 gap-1 pointer-events-none">
                        <div class="flex justify-between items-start">
                            <div class="flex flex-col gap-0.5">
                                <div class="h-2 w-16 rounded-sm bg-gray-800"></div>
                                <div class="h-1 w-10 rounded-sm bg-gray-200"></div>
                            </div>
                            <div class="flex flex-col items-end gap-0.5">
                                <div class="h-4 w-12 rounded-sm bg-gray-900"></div>
                                <div class="h-1 w-8 rounded-sm bg-gray-300"></div>
                            </div>
                        </div>
                        <div class="h-px bg-gray-900 mt-1"></div>
                        <div class="flex flex-col gap-1 mt-1">
                            <div class="h-1 w-full rounded-sm bg-gray-200"></div>
                            <div class="h-1 w-full rounded-sm bg-white"></div>
                            <div class="h-1 w-full rounded-sm bg-gray-200"></div>
                        </div>
                        <div class="flex justify-end mt-1">
                            <div class="h-px w-full bg-gray-900"></div>
                        </div>
                        <div class="flex justify-between">
                            <div class="h-2 w-10 rounded-sm bg-gray-800"></div>
                            <div class="h-2 w-12 rounded-sm bg-gray-800"></div>
                        </div>
                    </div>
                    <div class="px-2.5 py-2 border-t border-stone-100 bg-stone-50">
                        <p class="text-[11px] font-semibold text-stone-700">Minimal Clean</p>
                        <p class="text-[10px] text-stone-400 mt-0.5">Ultra-minimal, spacious</p>
                    </div>
                    <div x-show="pdfModal.selected===4" class="absolute top-1.5 right-1.5 w-4 h-4 rounded-full bg-gray-700 flex items-center justify-center">
                        <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </div>
                </button>

                {{-- Template 5: Green GST --}}
                <button @click="downloadPdf(5)"
                        class="group relative flex flex-col rounded-xl border-2 transition-all duration-150 overflow-hidden text-left"
                        :class="pdfModal.selected===5 ? 'border-green-600 shadow-md' : 'border-stone-200 hover:border-green-300'">
                    <div class="w-full h-24 bg-white flex flex-col overflow-hidden pointer-events-none">
                        <div class="flex flex-col p-2 gap-1 flex-1 border-l-4 border-green-600">
                            <div class="flex justify-between items-start">
                                <div class="flex flex-col gap-0.5">
                                    <div class="h-2 w-16 rounded-sm bg-stone-800"></div>
                                    <div class="h-1 w-10 rounded-sm bg-stone-300"></div>
                                </div>
                                <div class="flex flex-col items-end gap-0.5">
                                    <div class="h-1 w-8 rounded-sm bg-green-600"></div>
                                    <div class="h-2 w-12 rounded-sm bg-stone-800"></div>
                                </div>
                            </div>
                            <div class="h-px bg-green-600 mt-1"></div>
                            <div class="flex flex-col gap-0.5 mt-0.5">
                                <div class="h-1 w-full rounded-sm bg-stone-200"></div>
                                <div class="h-1 w-full rounded-sm bg-stone-50"></div>
                                <div class="h-1 w-full rounded-sm bg-stone-200"></div>
                            </div>
                            <div class="flex justify-end mt-1">
                                <div class="h-2 w-14 rounded-sm" style="border-top:2px solid #16a34a;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="px-2.5 py-2 border-t border-stone-100 bg-stone-50">
                        <p class="text-[11px] font-semibold text-stone-700">Green GST</p>
                        <p class="text-[10px] text-stone-400 mt-0.5">White, green border accent</p>
                    </div>
                    <div x-show="pdfModal.selected===5" class="absolute top-1.5 right-1.5 w-4 h-4 rounded-full bg-green-600 flex items-center justify-center">
                        <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </div>
                </button>

                {{-- Template 6: Bold Red --}}
                <button @click="downloadPdf(6)"
                        class="group relative flex flex-col rounded-xl border-2 transition-all duration-150 overflow-hidden text-left"
                        :class="pdfModal.selected===6 ? 'border-red-600 shadow-md' : 'border-stone-200 hover:border-red-300'">
                    <div class="w-full h-24 bg-white flex flex-col overflow-hidden pointer-events-none">
                        <div class="h-1.5 w-full bg-red-600"></div>
                        <div class="flex flex-col p-2 gap-1 flex-1">
                            <div class="flex justify-between items-start">
                                <div class="flex flex-col gap-0.5 pr-2" style="border-right:1px solid #fecaca;">
                                    <div class="h-2 w-14 rounded-sm bg-stone-800"></div>
                                    <div class="h-1 w-9 rounded-sm bg-stone-300"></div>
                                </div>
                                <div class="flex flex-col items-end gap-0.5">
                                    <div class="h-2.5 w-10 rounded-sm bg-red-600"></div>
                                    <div class="h-1 w-7 rounded-sm bg-stone-300"></div>
                                </div>
                            </div>
                            <div class="h-px bg-red-600 mt-1"></div>
                            <div class="flex flex-col gap-0.5 mt-0.5">
                                <div class="h-1 w-full rounded-sm bg-stone-200"></div>
                                <div class="h-1 w-full rounded-sm bg-stone-50"></div>
                                <div class="h-1 w-full rounded-sm bg-stone-200"></div>
                            </div>
                            <div class="flex justify-end mt-1">
                                <div class="h-2 w-14 rounded-sm" style="border-top:2px solid #dc2626;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="px-2.5 py-2 border-t border-stone-100 bg-stone-50">
                        <p class="text-[11px] font-semibold text-stone-700">Bold Red</p>
                        <p class="text-[10px] text-stone-400 mt-0.5">White, red accent lines</p>
                    </div>
                    <div x-show="pdfModal.selected===6" class="absolute top-1.5 right-1.5 w-4 h-4 rounded-full bg-red-600 flex items-center justify-center">
                        <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </div>
                </button>

            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between px-5 py-3.5 border-t border-stone-100 bg-stone-50 rounded-b-2xl">
                <p class="text-[11px] text-stone-400">Click a template to download instantly</p>
                <button @click="pdfModal.open=false"
                        class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-stone-600 border border-stone-200 rounded-lg hover:bg-white transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- Reject modal --}}
    <div x-show="rejectModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="rejectModal.open=false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <h3 class="text-sm font-semibold text-stone-800 mb-4">Reject Invoice</h3>
            <label class="block text-xs font-semibold text-stone-600 mb-1.5">Reason <span class="text-red-600">*</span></label>
            <textarea x-model="rejectModal.reason" rows="3" placeholder="Reason for rejection…"
                      class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors resize-none"></textarea>
            <div class="flex items-center justify-end gap-2 mt-4">
                <button @click="rejectModal.open=false"
                        class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-stone-600 border border-stone-200 rounded-lg hover:bg-stone-50 transition-colors">
                    Cancel
                </button>
                <button @click="confirmReject()"
                        class="inline-flex items-center gap-1.5 h-8 px-4 text-xs font-semibold text-white rounded-lg transition-colors"
                        style="background:#7f1d1d;" onmouseover="this.style.background='#991b1b'" onmouseout="this.style.background='#7f1d1d'">
                    Reject Invoice
                </button>
            </div>
        </div>
    </div>

    {{-- Cancel modal --}}
    <div x-show="cancelModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="cancelModal.open=false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <h3 class="text-sm font-semibold text-stone-800 mb-4">Cancel Invoice</h3>
            <label class="block text-xs font-semibold text-stone-600 mb-1.5">Reason (optional)</label>
            <textarea x-model="cancelModal.reason" rows="3" placeholder="Reason for cancellation…"
                      class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors resize-none"></textarea>
            <div class="flex items-center justify-end gap-2 mt-4">
                <button @click="cancelModal.open=false"
                        class="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-stone-600 border border-stone-200 rounded-lg hover:bg-stone-50 transition-colors">
                    Back
                </button>
                <button @click="confirmCancel()"
                        class="inline-flex items-center gap-1.5 h-8 px-4 text-xs font-semibold text-stone-700 border border-stone-200 rounded-lg hover:bg-stone-50 transition-colors">
                    Cancel Invoice
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function invoiceShow() {
    return {
        toast: { show: false, type: 'success', message: '' },
        rejectModal: { open: false, reason: '' },
        cancelModal: { open: false, reason: '' },
        pdfModal:    { open: false, selected: null },

        init() {},

        showToast(type, message) {
            this.toast = { show: true, type, message };
            setTimeout(() => { this.toast.show = false; }, 4000);
        },

        openReject()  { this.rejectModal = { open: true, reason: '' }; },
        openCancel()  { this.cancelModal = { open: true, reason: '' }; },

        downloadPdf(template) {
            this.pdfModal.selected = template;
            const url = '/sales/invoices/{{ $invoice->id }}/pdf?template=' + template;
            window.open(url, '_blank');
            // Close modal after short delay so user sees the selection
            setTimeout(() => {
                this.pdfModal.open = false;
                this.pdfModal.selected = null;
            }, 800);
        },

        async submit() {
            if (!confirm('Submit this invoice for approval?')) return;
            await this.action('/sales/invoices/{{ $invoice->id }}/submit', 'POST');
        },

        async approve() {
            if (!confirm('Approve this invoice?')) return;
            await this.action('/sales/invoices/{{ $invoice->id }}/approve', 'POST');
        },

        async confirmReject() {
            if (!this.rejectModal.reason.trim()) {
                this.showToast('error', 'Please enter a reason.');
                return;
            }
            await this.action('/sales/invoices/{{ $invoice->id }}/reject', 'POST', { reason: this.rejectModal.reason });
            this.rejectModal.open = false;
        },

        async confirmCancel() {
            await this.action('/sales/invoices/{{ $invoice->id }}/cancel', 'POST', { reason: this.cancelModal.reason });
            this.cancelModal.open = false;
        },

        async action(url, method, body = {}) {
            try {
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type':  'application/json',
                        'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]').content,
                        'Accept':        'application/json',
                    },
                    body: JSON.stringify(body),
                });
                const json = await res.json();
                if (json.success) {
                    this.showToast('success', json.message ?? 'Done.');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    this.showToast('error', json.message ?? 'Something went wrong.');
                }
            } catch (e) {
                this.showToast('error', 'Network error. Please try again.');
                console.error(e);
            }
        },
    };
}
</script>
@endpush
