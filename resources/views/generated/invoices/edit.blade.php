@extends('layouts.app')
@section('content')
@php use Illuminate\Support\Facades\Storage; @endphp
<div class="bg-white border border-stone-200 rounded-1xl overflow-hidden">
    <div class="px-6 py-5 border-b border-stone-100 flex items-center justify-between">
        <div>
            <h3 class="text-sm font-semibold text-stone-800">Edit Invoice</h3>
            <p class="text-xs text-stone-400 mt-0.5">Update the record details.</p>
        </div>
        <a href="{{ route('generated.invoices.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-stone-100 text-stone-600 hover:bg-stone-200 transition-colors"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Back</a>
    </div>
    <form method="POST" action="{{ route('generated.invoices.update', $invoice) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="p-6">
            @if($errors->any())
            <div class="mb-5 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl">Please fix the errors below.</div>
            @endif
            <div class="grid grid-cols-3 gap-5">
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Invoice No. <span class="text-red-500">*</span></label>
                    <input type="text" name="invoice_no" value="{{ $invoice->invoice_no }}" placeholder="INV-001" class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10 @error('invoice_no') border-red-400 bg-red-50 @enderror">
                    @error('invoice_no')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Invoice Date <span class="text-red-500">*</span></label>
                    <input type="date" name="invoice_date" value="{{ $invoice->invoice_date ?? date('Y-m-d') }}" placeholder="Invoice Date" class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10 @error('invoice_date') border-red-400 bg-red-50 @enderror">
                    @error('invoice_date')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Purchase Order No.</label>
                    <input type="text" name="purchase_order_no" value="{{ $invoice->purchase_order_no }}" placeholder="PO-001" class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10 @error('purchase_order_no') border-red-400 bg-red-50 @enderror">
                    @error('purchase_order_no')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Purchase Order Date</label>
                    <input type="date" name="purchase_order_date" value="{{ $invoice->purchase_order_date }}" placeholder="Purchase Order Date" class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10 @error('purchase_order_date') border-red-400 bg-red-50 @enderror">
                    @error('purchase_order_date')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Buyer <span class="text-red-500">*</span></label>
                    <select name="buyer" class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10 @error('buyer') border-red-400 bg-red-50 @enderror"><option value="">-- Select --</option>
                        @isset($buyer_options)
                            @foreach($buyer_options as $val => $lab)
                                <option value="{{ $val }}" {{ ($invoice->buyer ?? '') == $val ? 'selected' : '' }}>{{ $lab }}</option>
                            @endforeach
                        @endisset
</select>
                    @error('buyer')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Vendor <span class="text-red-500">*</span></label>
                    <select name="vendor" class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10 @error('vendor') border-red-400 bg-red-50 @enderror"><option value="">-- Select --</option>
                        @isset($vendor_options)
                            @foreach($vendor_options as $val => $lab)
                                <option value="{{ $val }}" {{ ($invoice->vendor ?? '') == $val ? 'selected' : '' }}>{{ $lab }}</option>
                            @endforeach
                        @endisset
</select>
                    @error('vendor')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Buyer Address</label>
                    <textarea name="buyer_address" rows="4" placeholder="Address" class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10 @error('buyer_address') border-red-400 bg-red-50 @enderror resize-none">{{ $invoice->buyer_address }}</textarea>
                    @error('buyer_address')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Vendor Address</label>
                    <textarea name="vendor_address" rows="4" placeholder="Address" class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10 @error('vendor_address') border-red-400 bg-red-50 @enderror resize-none">{{ $invoice->vendor_address }}</textarea>
                    @error('vendor_address')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Dispatch Through</label>
                    <input type="text" name="dispatch_through" value="{{ $invoice->dispatch_through }}" placeholder="Transport name" class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10 @error('dispatch_through') border-red-400 bg-red-50 @enderror">
                    @error('dispatch_through')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Dispatch Date</label>
                    <input type="date" name="dispatch_date" value="{{ $invoice->dispatch_date }}" placeholder="Dispatch Date" class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10 @error('dispatch_date') border-red-400 bg-red-50 @enderror">
                    @error('dispatch_date')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Line Items</label>
                    <div x-data="repeaterField('line_items')" class="border border-stone-200 rounded-xl overflow-hidden">
                        <table class="w-full text-sm" id="repeater_line_items">
                            <thead class="bg-stone-800 text-white">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold w-8">#</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-stone-500 uppercase tracking-wider">Particular <span class=\"text-red-500\">*</span></th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-stone-500 uppercase tracking-wider">HSN</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-stone-500 uppercase tracking-wider">Qty <span class=\"text-red-500\">*</span></th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-stone-500 uppercase tracking-wider">Unit</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-stone-500 uppercase tracking-wider">MRP <span class=\"text-red-500\">*</span></th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-stone-500 uppercase tracking-wider">Dis. (₹)</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-stone-500 uppercase tracking-wider">Dis. (%)</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-stone-500 uppercase tracking-wider">Dis. On</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-stone-500 uppercase tracking-wider">Amt</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-stone-500 uppercase tracking-wider">CGST %</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-stone-500 uppercase tracking-wider">SGST %</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-stone-500 uppercase tracking-wider">IGST %</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-stone-500 uppercase tracking-wider">Cess %</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-stone-500 uppercase tracking-wider">Total Amt</th>
                                    <th class="px-3 py-2 w-10"></th>
                                </tr>
                            </thead>
                            <tbody id="repeater_line_items_body">
@if(isset($invoice) && $invoice->line_items->count())
                    @foreach($invoice->line_items as $__ri => $__row)
                    <tr>
                        <td class="px-3 py-1.5 text-stone-400 text-sm row-num">{{ $__ri + 1 }}</td>
                        <td class="px-2 py-1.5"><input type="text" name="line_items[{{ $__ri }}][particular]" value="{{ $__row->particular }}" placeholder="" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"></td>
                        <td class="px-2 py-1.5"><input type="text" name="line_items[{{ $__ri }}][hsn]" value="{{ $__row->hsn }}" placeholder="" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"></td>
                        <td class="px-2 py-1.5"><input type="number" name="line_items[{{ $__ri }}][qty]" value="{{ $__row->qty }}" placeholder="" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"></td>
                        <td class="px-2 py-1.5"><select name="line_items[{{ $__ri }}][unit]" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"><option value="">-- Select --</option><option value="PCS" {{ ($__row->unit ?? '') == 'PCS' ? 'selected' : '' }}>PCS</option><option value="PACKS" {{ ($__row->unit ?? '') == 'PACKS' ? 'selected' : '' }}>PACKS</option><option value="KG" {{ ($__row->unit ?? '') == 'KG' ? 'selected' : '' }}>KG</option><option value="LTR" {{ ($__row->unit ?? '') == 'LTR' ? 'selected' : '' }}>LTR</option><option value="MTR" {{ ($__row->unit ?? '') == 'MTR' ? 'selected' : '' }}>MTR</option><option value="BOX" {{ ($__row->unit ?? '') == 'BOX' ? 'selected' : '' }}>BOX</option><option value="NOS" {{ ($__row->unit ?? '') == 'NOS' ? 'selected' : '' }}>NOS</option></select></td>
                        <td class="px-2 py-1.5"><input type="number" name="line_items[{{ $__ri }}][mrp]" value="{{ $__row->mrp }}" placeholder="" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"></td>
                        <td class="px-2 py-1.5"><input type="number" name="line_items[{{ $__ri }}][dis_flat]" value="{{ $__row->dis_flat }}" placeholder="" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"></td>
                        <td class="px-2 py-1.5"><input type="number" name="line_items[{{ $__ri }}][dis_pct]" value="{{ $__row->dis_pct }}" placeholder="" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"></td>
                        <td class="px-2 py-1.5"><select name="line_items[{{ $__ri }}][dis_on]" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"><option value="">-- Select --</option><option value="before_tax" {{ ($__row->dis_on ?? '') == 'before_tax' ? 'selected' : '' }}>Before Tax</option><option value="on_mrp" {{ ($__row->dis_on ?? '') == 'on_mrp' ? 'selected' : '' }}>On MRP</option><option value="after_tax" {{ ($__row->dis_on ?? '') == 'after_tax' ? 'selected' : '' }}>After Tax</option></select></td>
                        <td class="px-2 py-1.5"><input type="text" name="line_items[{{ $__ri }}][amt]" value="{{ $__row->amt }}" placeholder="" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"></td>
                        <td class="px-2 py-1.5"><input type="number" name="line_items[{{ $__ri }}][cgst_pct]" value="{{ $__row->cgst_pct }}" placeholder="" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"></td>
                        <td class="px-2 py-1.5"><input type="number" name="line_items[{{ $__ri }}][sgst_pct]" value="{{ $__row->sgst_pct }}" placeholder="" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"></td>
                        <td class="px-2 py-1.5"><input type="number" name="line_items[{{ $__ri }}][igst_pct]" value="{{ $__row->igst_pct }}" placeholder="" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"></td>
                        <td class="px-2 py-1.5"><input type="number" name="line_items[{{ $__ri }}][cess_pct]" value="{{ $__row->cess_pct }}" placeholder="" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"></td>
                        <td class="px-2 py-1.5"><input type="text" name="line_items[{{ $__ri }}][total_amt]" value="{{ $__row->total_amt }}" placeholder="" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"></td>
                        <td class="px-2 py-1.5 text-center"><button type="button" onclick="this.closest('tr').remove(); window.renumberRepeater('line_items')" class="w-6 h-6 inline-flex items-center justify-center rounded bg-red-600 hover:bg-red-700 text-white text-xs font-bold">−</button></td>
                    </tr>
                    @endforeach
                    @endif
                            </tbody>
                        </table>
                        <div class="px-3 py-2 bg-stone-50 border-t border-stone-100">
                            <button type="button" @click="addRow()"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-stone-800 hover:bg-stone-700 text-white text-xs font-medium transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Row
                            </button>
                        </div>
                    </div>
                    <template id="repeater_line_items_tpl">
                        <tr>
                            <td class="px-3 py-1.5 text-stone-400 text-sm row-num"></td>
                        <td class="px-2 py-1.5"><input type="text" name="line_items[__IDX__][particular]" value="{{ '' }}" placeholder="" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"></td>
                        <td class="px-2 py-1.5"><input type="text" name="line_items[__IDX__][hsn]" value="{{ '' }}" placeholder="" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"></td>
                        <td class="px-2 py-1.5"><input type="number" name="line_items[__IDX__][qty]" value="{{ '' }}" placeholder="" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"></td>
                        <td class="px-2 py-1.5"><select name="line_items[__IDX__][unit]" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"><option value="">-- Select --</option><option value="PCS" {{ ('' ?? '') == 'PCS' ? 'selected' : '' }}>PCS</option><option value="PACKS" {{ ('' ?? '') == 'PACKS' ? 'selected' : '' }}>PACKS</option><option value="KG" {{ ('' ?? '') == 'KG' ? 'selected' : '' }}>KG</option><option value="LTR" {{ ('' ?? '') == 'LTR' ? 'selected' : '' }}>LTR</option><option value="MTR" {{ ('' ?? '') == 'MTR' ? 'selected' : '' }}>MTR</option><option value="BOX" {{ ('' ?? '') == 'BOX' ? 'selected' : '' }}>BOX</option><option value="NOS" {{ ('' ?? '') == 'NOS' ? 'selected' : '' }}>NOS</option></select></td>
                        <td class="px-2 py-1.5"><input type="number" name="line_items[__IDX__][mrp]" value="{{ '' }}" placeholder="" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"></td>
                        <td class="px-2 py-1.5"><input type="number" name="line_items[__IDX__][dis_flat]" value="{{ '' }}" placeholder="" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"></td>
                        <td class="px-2 py-1.5"><input type="number" name="line_items[__IDX__][dis_pct]" value="{{ '' }}" placeholder="" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"></td>
                        <td class="px-2 py-1.5"><select name="line_items[__IDX__][dis_on]" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"><option value="">-- Select --</option><option value="before_tax" {{ ('' ?? '') == 'before_tax' ? 'selected' : '' }}>Before Tax</option><option value="on_mrp" {{ ('' ?? '') == 'on_mrp' ? 'selected' : '' }}>On MRP</option><option value="after_tax" {{ ('' ?? '') == 'after_tax' ? 'selected' : '' }}>After Tax</option></select></td>
                        <td class="px-2 py-1.5"><input type="text" name="line_items[__IDX__][amt]" value="{{ '' }}" placeholder="" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"></td>
                        <td class="px-2 py-1.5"><input type="number" name="line_items[__IDX__][cgst_pct]" value="{{ '' }}" placeholder="" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"></td>
                        <td class="px-2 py-1.5"><input type="number" name="line_items[__IDX__][sgst_pct]" value="{{ '' }}" placeholder="" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"></td>
                        <td class="px-2 py-1.5"><input type="number" name="line_items[__IDX__][igst_pct]" value="{{ '' }}" placeholder="" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"></td>
                        <td class="px-2 py-1.5"><input type="number" name="line_items[__IDX__][cess_pct]" value="{{ '' }}" placeholder="" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"></td>
                        <td class="px-2 py-1.5"><input type="text" name="line_items[__IDX__][total_amt]" value="{{ '' }}" placeholder="" class="w-full px-2.5 py-1.5 text-sm border border-stone-300 rounded-lg outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition"></td>
                            <td class="px-2 py-1.5 text-center"><button type="button" onclick="this.closest('tr').remove(); window.renumberRepeater('line_items')" class="w-6 h-6 inline-flex items-center justify-center rounded bg-red-600 hover:bg-red-700 text-white text-xs font-bold">−</button></td>
                        </tr>
                    </template>                    @error('line_items')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Total</label>
                    <input type="text" name="subtotal" value="{{ $invoice->subtotal }}" placeholder="Total" class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10 @error('subtotal') border-red-400 bg-red-50 @enderror">
                    @error('subtotal')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Additional Discount</label>
                    <input type="number" name="additional_discount" value="{{ $invoice->additional_discount }}" placeholder="Additional Discount" class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10 @error('additional_discount') border-red-400 bg-red-50 @enderror">
                    @error('additional_discount')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Round Off</label>
                    <input type="text" name="round_off" value="{{ $invoice->round_off }}" placeholder="Round Off" class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10 @error('round_off') border-red-400 bg-red-50 @enderror">
                    @error('round_off')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Grand Total</label>
                    <input type="text" name="grand_total" value="{{ $invoice->grand_total }}" placeholder="Grand Total" class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10 @error('grand_total') border-red-400 bg-red-50 @enderror">
                    @error('grand_total')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Invoice Summary</label>
                    <input type="text" name="invoice_summary" value="{{ $invoice->invoice_summary }}" placeholder="Invoice Summary" class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10 @error('invoice_summary') border-red-400 bg-red-50 @enderror">
                    @error('invoice_summary')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Remark / Comment</label>
                    <textarea name="remark" rows="4" placeholder="Enter remarks..." class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10 @error('remark') border-red-400 bg-red-50 @enderror resize-none">{{ $invoice->remark }}</textarea>
                    @error('remark')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Auto Approve</label>
                    <input type="text" name="auto_approve" value="{{ $invoice->auto_approve }}" placeholder="Auto Approve" class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10 @error('auto_approve') border-red-400 bg-red-50 @enderror">
                    @error('auto_approve')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
        <div class="px-6 py-1 bg-stone-50 border-t border-stone-100 flex items-center justify-end gap-3">
            <a href="{{ route('generated.invoices.index') }}" class="px-4 py-2.5 rounded-xl text-sm font-medium text-stone-600 bg-white border border-stone-300 hover:bg-stone-50 transition-colors">Cancel</a>
            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-red-800 hover:bg-red-700 text-white text-sm font-medium transition-colors shadow-sm"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Update Record</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('repeaterField', (col) => ({
        addRow() {
            const tpl = document.getElementById('repeater_' + col + '_tpl');
            const body = document.getElementById('repeater_' + col + '_body');
            const clone = tpl.content.cloneNode(true);
            const idx = body.querySelectorAll('tr').length;
            clone.querySelectorAll('[name]').forEach(el => { el.name = el.name.replace(/__IDX__/g, idx); });
            body.appendChild(clone);
            window.renumberRepeater(col);
        }
    }));
});
window.renumberRepeater = function(col) {
    document.querySelectorAll('#repeater_' + col + '_body tr').forEach((tr, i) => {
        const num = tr.querySelector('.row-num');
        if (num) num.textContent = i + 1;
        tr.querySelectorAll('[name]').forEach(el => { el.name = el.name.replace(/\[\d+\]/g, '[' + i + ']'); });
    });
};
</script>
@endpush
