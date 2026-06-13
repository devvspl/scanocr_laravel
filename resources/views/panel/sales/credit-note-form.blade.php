@extends('layouts.app')

@section('title', isset($creditNote) ? 'Edit Credit Note — ' . $creditNote->credit_note_number : 'New Credit Note')
@section('page-title', isset($creditNote) ? 'Edit Credit Note' : 'New Credit Note')

@section('breadcrumb')
    <span>Sales</span>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('sales.credit-notes') }}" class="hover:text-stone-600 transition-colors">Credit Notes</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-stone-600">{{ isset($creditNote) ? $creditNote->credit_note_number : 'New' }}</span>
@endsection

@section('content')
    <div x-data="creditNoteForm()" x-init="init()"
        class="flex flex-col bg-white border border-stone-200 rounded-xl overflow-hidden"
        style="height: calc(100vh - 7.5rem);">

        {{-- ── HEADER BAND ── --}}
        <div class="shrink-0 border-b border-stone-200 bg-stone-50 px-3 py-2">
            <div class="flex items-end gap-2 min-w-0 flex-wrap">
                <div class="shrink-0 w-36">
                    <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-0.5">Credit Note
                        #</label>
                    <input type="text" :value="form.credit_note_number" readonly
                        class="w-full h-7 px-2 text-xs font-mono bg-stone-100 border border-stone-300 rounded-lg text-stone-500 cursor-not-allowed focus:outline-none">
                </div>
                <div class="shrink-0 w-32">
                    <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-0.5">Date <span
                            class="text-red-600">*</span></label>
                    <input type="date" x-model="form.credit_note_date" required
                        class="w-full h-7 px-2 text-xs border border-stone-300 rounded-lg text-stone-800 bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10">
                </div>
                <div class="flex-1 min-w-[180px]" x-data="customerSearch()" @keydown.escape="close()">
                    <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-0.5">Customer
                        <span class="text-red-600">*</span></label>
                    <div class="relative">
                        <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="search()"
                            @keydown.arrow-down.prevent="moveDown()" @keydown.arrow-up.prevent="moveUp()"
                            @keydown.enter.prevent="selectHighlighted()" :placeholder="selectedName || 'Search customer…'"
                            :class="selectedName && !query ? 'font-medium' : ''" autocomplete="new-password"
                            spellcheck="false"
                            class="w-full h-7 px-2 pr-6 text-xs border border-stone-300 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 placeholder-stone-400 text-stone-800">
                        <button x-show="selectedName" @click="clearCustomer()" type="button"
                            class="absolute right-1.5 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600"><svg
                                class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg></button>
                        <div x-show="open" x-cloak @click.outside="close()" x-ref="customerDropdown"
                            class="absolute top-full left-0 right-0 mt-1 bg-white rounded-xl z-50 max-h-64 overflow-y-auto"
                            style="border:1px solid #e7e5e4;box-shadow:0 4px 16px rgba(0,0,0,.07);">
                            <template x-if="loading">
                                <div class="px-3 py-4 text-center text-xs text-stone-400">Searching…</div>
                            </template>
                            <template x-if="!loading && suggestions.length === 0">
                                <div class="px-3 py-4 text-center text-xs text-stone-400">No customers found</div>
                            </template>
                            <template x-if="!loading && suggestions.length > 0">
                                <div><template x-for="(c, ci) in suggestions" :key="c.id"><button type="button"
                                            @click="select(c)" @mouseenter="highlightIdx=ci"
                                            :class="highlightIdx === ci ? 'bg-red-50' : ''"
                                            class="w-full text-left px-3 py-2 hover:bg-red-50 transition-colors"
                                            style="border:none;border-bottom:1px solid #f5f5f4;outline:none;background:transparent;">
                                            <div class="flex items-center justify-between gap-2"><span
                                                    class="text-xs font-medium text-stone-800" x-text="c.name"></span><span
                                                    class="text-[10px] text-stone-400 font-mono shrink-0"
                                                    x-text="c.gstin"></span></div>
                                            <div class="text-[10px] text-stone-400 mt-0.5"
                                                x-text="[c.city,c.state].filter(Boolean).join(', ')"></div>
                                        </button></template></div>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="shrink-0 w-24">
                    <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-0.5">Reference
                        #</label>
                    <input type="text" x-model="form.reference_number" placeholder="Ref."
                        class="w-full h-7 px-2 text-xs border border-stone-300 rounded-lg text-stone-800 bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 placeholder-stone-400">
                </div>
                <div class="shrink-0 w-32">
                    <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-0.5">Reason
                        <span class="text-red-600">*</span></label>
                    <select x-model="form.reason" required
                        class="w-full h-7 px-2 text-xs border border-stone-300 rounded-lg text-stone-800 bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10">
                        <option value="">— Select —</option>
                        <option value="return">Goods Returned</option>
                        <option value="billing_error">Billing Error</option>
                        <option value="discount">Post-sale Discount</option>
                        <option value="deficiency">Deficiency</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="shrink-0">
                    <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-0.5">&nbsp;</label>
                    <button type="button" @click="invoicePickerOpen=true;searchInvoices()" class="h-7 px-3 text-[10px] font-semibold text-red-700 border border-red-200 rounded-lg hover:bg-red-50 transition-colors whitespace-nowrap">Pick from Invoice</button>
                </div>
            </div>
            <div class="flex items-center gap-3 mt-1.5">
                <button type="button" @click="form.is_igst = !form.is_igst; items.forEach((_,i)=>computeLineTotal(i))"
                    class="flex items-center gap-1.5 cursor-pointer select-none focus:outline-none">
                    <div class="relative w-8 h-4 rounded-full shrink-0"
                        :style="form.is_igst ? 'background:#7f1d1d' : 'background:#d6d3d1'">
                        <div class="absolute top-0.5 w-3 h-3 bg-white rounded-full shadow"
                            :style="form.is_igst ? 'left:18px' : 'left:2px'"></div>
                    </div>
                    <span class="text-[10px] font-semibold uppercase tracking-wide"
                        :style="form.is_igst ? 'color:#7f1d1d' : 'color:#78716c'"
                        x-text="form.is_igst ? 'IGST (Inter-state)' : 'CGST + SGST (Intra-state)'"></span>
                </button>
            </div>
        </div>

        {{-- ── LINE ITEMS ── --}}
        <div class="flex-1 overflow-y-auto overflow-x-hidden min-h-0">
            <table class="w-full text-xs border-collapse" style="table-layout:fixed;">
                <colgroup>
                    <col style="width:24px">
                    <col style="width:25%">
                    <col style="width:70px">
                    <col style="width:54px">
                    <col style="width:48px">
                    <col style="width:80px">
                    <col style="width:94px">
                    <col style="width:54px">
                    <col style="width:80px">
                    <col style="width:24px">
                </colgroup>
                <thead class="sticky top-0 z-10 bg-stone-100 border-b border-stone-200">
                    <tr>
                        <th class="px-2 py-1.5 text-left text-[10px] font-semibold text-stone-500 uppercase">#</th>
                        <th class="px-2 py-1.5 text-left text-[10px] font-semibold text-stone-500 uppercase">Product /
                            Description</th>
                        <th class="px-1 py-1.5 text-left text-[10px] font-semibold text-stone-500 uppercase">HSN/SAC</th>
                        <th class="px-1 py-1.5 text-right text-[10px] font-semibold text-stone-500 uppercase">Qty</th>
                        <th class="px-1 py-1.5 text-left text-[10px] font-semibold text-stone-500 uppercase">Unit</th>
                        <th class="px-1 py-1.5 text-right text-[10px] font-semibold text-stone-500 uppercase">Price (₹)
                        </th>
                        <th class="px-1 py-1.5 text-right text-[10px] font-semibold text-stone-500 uppercase">Discount</th>
                        <th class="px-1 py-1.5 text-right text-[10px] font-semibold text-stone-500 uppercase">Tax %</th>
                        <th class="px-1 py-1.5 text-right text-[10px] font-semibold text-stone-500 uppercase">Total (₹)
                        </th>
                        <th class="py-1.5"></th>
                    </tr>
                </thead>
                <tbody><template x-for="(item,idx) in items" :key="idx">
                        <tr class="border-b border-stone-100 hover:bg-stone-50/50 align-top">
                            <td class="px-2 pt-2 text-[10px] text-stone-400 font-mono" x-text="idx+1"></td>
                            <td class="px-1 py-1.5" style="overflow:visible;">
                                <div class="relative" @keydown.escape="item.productOpen=false">
                                    <input type="text" x-model="item.productQuery"
                                        @input.debounce.300ms="searchProductsForItem(idx)"
                                        @focus="if(!item.productOpen) searchProductsForItem(idx)"
                                        :placeholder="item.productName || 'Search product…'"
                                        :class="item.productName && !item.productQuery ? 'font-medium text-stone-800' :
                                            'text-stone-800'"
                                        autocomplete="new-password" spellcheck="false"
                                        class="w-full h-6 px-2 text-xs border border-stone-200 rounded-md bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 placeholder-stone-400">
                                    <div x-show="item.productOpen" x-cloak @click.outside="item.productOpen=false"
                                        class="absolute top-full left-0 w-72 mt-1 bg-white rounded-xl z-40 max-h-52 overflow-y-auto"
                                        style="border:1px solid #e7e5e4;box-shadow:0 4px 16px rgba(0,0,0,.07);">
                                        <template x-if="item.productLoading">
                                            <div class="px-3 py-3 text-center text-xs text-stone-400">Searching…</div>
                                        </template>
                                        <template x-if="!item.productLoading&&item.productSuggestions.length===0">
                                            <div class="px-3 py-3 text-center text-xs text-stone-400">No products found
                                            </div>
                                        </template>
                                        <template x-if="!item.productLoading&&item.productSuggestions.length>0">
                                            <div><template x-for="p in item.productSuggestions"
                                                    :key="p.id"><button type="button"
                                                        @click="selectProduct(idx,p)"
                                                        class="w-full text-left px-3 py-1.5 hover:bg-red-50"
                                                        style="border:none;border-bottom:1px solid #f5f5f4;outline:none;background:transparent;">
                                                        <div class="flex items-center justify-between gap-2"><span
                                                                class="text-xs font-medium text-stone-800"
                                                                x-text="p.name"></span><span
                                                                class="text-[10px] text-stone-400 font-mono"
                                                                x-text="p.code"></span></div>
                                                        <div class="text-[10px] text-stone-400"
                                                            x-text="'₹'+p.unit_price.toFixed(2)"></div>
                                                    </button></template></div>
                                        </template>
                                    </div>
                                </div>
                                <input type="text" x-model="item.description" placeholder="Description…"
                                    class="w-full h-5 mt-0.5 px-2 text-[10px] border border-stone-100 rounded bg-transparent focus:bg-white focus:border-stone-300 focus:outline-none placeholder-stone-300 text-stone-600">
                            </td>
                            <td class="px-1 py-1.5"><input type="text" x-model="item.hsn_sac" placeholder="HSN"
                                    class="w-full h-6 px-1 text-xs border border-stone-200 rounded-md bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 placeholder-stone-400 text-stone-800">
                            </td>
                            <td class="px-1 py-1.5"><input type="number" x-model="item.qty"
                                    @input="computeLineTotal(idx)" min="0.001" step="0.001" placeholder="1"
                                    class="w-full h-6 px-1 text-xs text-right border border-stone-200 rounded-md bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 placeholder-stone-400 text-stone-800">
                            </td>
                            <td class="px-1 py-1.5"><input type="text" x-model="item.unit" placeholder="pcs"
                                    class="w-full h-6 px-1 text-xs border border-stone-200 rounded-md bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 placeholder-stone-400 text-stone-800">
                            </td>
                            <td class="px-1 py-1.5"><input type="number" x-model="item.unit_price"
                                    @input="computeLineTotal(idx)" min="0" step="0.01" placeholder="0.00"
                                    class="w-full h-6 px-1 text-xs text-right border border-stone-200 rounded-md bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 placeholder-stone-400 text-stone-800">
                            </td>
                            <td class="px-1 py-1.5">
                                <div class="flex items-center"><input type="number" x-model="item.discount_value"
                                        @input="computeLineTotal(idx)" min="0" step="0.01" placeholder="0"
                                        class="w-full h-6 px-1 text-xs text-right border border-stone-200 rounded-l-md bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 placeholder-stone-400 text-stone-800"><button
                                        type="button"
                                        @click="item.discount_type=item.discount_type==='percentage'?'amount':'percentage';computeLineTotal(idx)"
                                        class="h-6 w-6 shrink-0 text-[10px] font-semibold border border-l-0 border-stone-200 rounded-r-md bg-stone-50 hover:bg-stone-100 text-center"
                                        style="color:#78716c" x-text="item.discount_type==='percentage'?'%':'₹'"></button>
                                </div>
                            </td>
                            <td class="px-1 py-1.5"><input type="number" x-model="item.tax_rate"
                                    @input="computeLineTotal(idx)" min="0" max="100" step="0.01"
                                    placeholder="0"
                                    class="w-full h-6 px-1 text-xs text-right border border-stone-200 rounded-md bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 placeholder-stone-400 text-stone-800">
                            </td>
                            <td class="px-1 py-1.5 text-right"><span class="text-xs font-semibold text-stone-800"
                                    x-text="fmt(item._lineTotal||0)"></span></td>
                            <td class="px-1 py-1.5 text-center"><button type="button" @click="removeItem(idx)"
                                    :disabled="items.length <= 1" class="w-5 h-5 flex items-center justify-center rounded"
                                    :style="items.length > 1 ? 'color:#d6d3d1;cursor:pointer' :
                                        'color:#e7e5e4;cursor:not-allowed'"><svg
                                        class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg></button></td>
                        </tr>
                    </template></tbody>
            </table>
            <div class="px-3 py-2 border-t border-stone-100"><button type="button" @click="addItem()"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-red-700 hover:text-red-800 hover:bg-red-50 px-2.5 py-1 rounded-lg"><svg
                        class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>Add Line</button></div>
        </div>

        {{-- ── FOOTER ── --}}
        <div class="shrink-0 border-t border-stone-200 bg-white">
            <div class="flex items-stretch gap-0 divide-x divide-stone-200">
                <div class="flex-1 px-3 py-2 flex flex-col gap-1.5 border-none">
                    <div class="flex gap-2">
                        <div class="flex-1"><label
                                class="block text-[10px] font-semibold text-stone-500 uppercase mb-0.5">Notes</label>
                            <textarea x-model="form.notes" rows="2" placeholder="Notes…"
                                class="w-full px-2 py-1 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 placeholder-stone-400 text-stone-700 resize-none"></textarea>
                        </div>
                        <div class="flex-1"><label
                                class="block text-[10px] font-semibold text-stone-500 uppercase mb-0.5">Terms</label>
                            <textarea x-model="form.terms" rows="2" placeholder="Terms…"
                                class="w-full px-2 py-1 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 placeholder-stone-400 text-stone-700 resize-none"></textarea>
                        </div>
                        <div class="flex-1"><label
                                class="block text-[10px] font-semibold text-stone-500 uppercase mb-0.5">Narration</label>
                            <textarea x-model="form.narration" rows="2" placeholder="Internal narration…"
                                class="w-full px-2 py-1 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 placeholder-stone-400 text-stone-700 resize-none"></textarea>
                        </div>
                    </div>
                </div>
                <div class="px-3 py-2 flex flex-col border-none justify-center gap-1 min-w-[160px]"
                    style="background:#fef2f2;">
                    <label class="block text-[10px] font-semibold uppercase" style="color:#7f1d1d">Bill Discount</label>
                    <div class="flex items-center"><input type="number" x-model="form.bill_discount_value"
                            @input="computeGrandTotal()" min="0" step="0.01" placeholder="0"
                            class="w-full h-7 px-2 text-xs text-right border border-stone-200 rounded-l-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 placeholder-stone-400 text-stone-800"><button
                            type="button"
                            @click="form.bill_discount_type=form.bill_discount_type==='percentage'?'amount':'percentage';computeGrandTotal()"
                            class="h-7 w-8 shrink-0 text-xs font-semibold border border-l-0 border-stone-200 rounded-r-lg bg-stone-50 hover:bg-stone-100 text-center"
                            style="color:#7f1d1d" x-text="form.bill_discount_type==='percentage'?'%':'₹'"></button></div>
                </div>
                <div class="px-4 py-2 border-none min-w-[200px] flex flex-col justify-center gap-0.5">
                    <div class="flex items-center justify-between gap-4"><span
                            class="text-[10px] text-stone-500">Subtotal</span><span
                            class="text-xs font-medium text-stone-700" x-text="'₹ '+fmt(totals.subtotal)"></span></div>
                    <div class="flex items-center justify-between gap-4" x-show="totals.discount>0"><span
                            class="text-[10px] text-stone-500">Discount</span><span
                            class="text-xs font-medium text-red-600" x-text="'− ₹ '+fmt(totals.discount)"></span></div>
                    <div class="flex items-center justify-between gap-4"><span
                            class="text-[10px] text-stone-500">Taxable</span><span
                            class="text-xs font-medium text-stone-700" x-text="'₹ '+fmt(totals.taxable)"></span></div>
                    <template x-if="!form.is_igst">
                        <div>
                            <div class="flex items-center justify-between gap-4" x-show="totals.cgst>0"><span
                                    class="text-[10px] text-stone-500">CGST</span><span class="text-xs text-stone-600"
                                    x-text="'₹ '+fmt(totals.cgst)"></span></div>
                            <div class="flex items-center justify-between gap-4" x-show="totals.sgst>0"><span
                                    class="text-[10px] text-stone-500">SGST</span><span class="text-xs text-stone-600"
                                    x-text="'₹ '+fmt(totals.sgst)"></span></div>
                        </div>
                    </template>
                    <template x-if="form.is_igst">
                        <div class="flex items-center justify-between gap-4" x-show="totals.igst>0"><span
                                class="text-[10px] text-stone-500">IGST</span><span class="text-xs text-stone-600"
                                x-text="'₹ '+fmt(totals.igst)"></span></div>
                    </template>
                    <div class="flex items-center justify-between gap-4 pt-1 mt-0.5 border-t border-stone-200"><span
                            class="text-xs font-bold text-stone-800">Credit Total</span><span
                            class="text-sm font-bold text-red-700" x-text="'₹ '+fmt(totals.grandTotal)"></span></div>
                </div>
                <div class="px-3 py-2 flex flex-col justify-center gap-1.5 min-w-[150px]">
                    <a href="{{ route('sales.credit-notes') }}"
                        class="w-full h-7 flex items-center justify-center text-xs font-semibold text-stone-600 border border-stone-200 rounded-lg hover:bg-stone-50 px-3">Cancel</a>
                    <button type="button" @click="save(false)" :disabled="saving"
                        class="w-full h-7 flex items-center justify-center text-xs font-semibold text-stone-700 border border-stone-200 rounded-lg hover:bg-stone-50 disabled:opacity-50 px-3"><span
                            x-text="saving&&!submitAfterSave?'Saving…':'Save Draft'"></span></button>
                    <button type="button" @click="save(true)" :disabled="saving"
                        class="w-full h-7 flex items-center justify-center text-xs font-semibold text-white rounded-lg disabled:opacity-50 px-3"
                        style="background:#7f1d1d"><span
                            x-text="saving&&submitAfterSave?'Submitting…':'Create & Submit'"></span></button>
                </div>
            </div>
            <div x-show="errorMsg" x-cloak class="px-4 py-2 bg-red-50 border-t border-red-200 flex items-center gap-2">
                <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg><span class="text-xs text-red-700 flex-1" x-text="errorMsg"></span><button @click="errorMsg=''"
                    class="text-red-400 hover:text-red-600"><svg class="w-3.5 h-3.5" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg></button></div>
        </div>

        {{-- Invoice Picker Modal --}}
        <div x-show="invoicePickerOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="invoicePickerOpen=false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg" x-transition>
                <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-stone-800">Pick from Invoice / Proforma</h3>
                    <button @click="invoicePickerOpen=false" class="text-stone-400 hover:text-stone-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <div class="px-5 py-3"><input type="text" x-model="invoiceSearchQuery" @input.debounce.300ms="searchInvoices()" placeholder="Search by invoice number or customer…" class="w-full h-8 px-3 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 placeholder-stone-400 text-stone-800"></div>
                <div class="px-5 pb-4 max-h-64 overflow-y-auto">
                    <template x-if="invoiceSearchLoading"><div class="py-6 text-center text-xs text-stone-400">Searching…</div></template>
                    <template x-if="!invoiceSearchLoading && invoiceResults.length===0"><div class="py-6 text-center text-xs text-stone-400">No approved invoices found</div></template>
                    <template x-if="!invoiceSearchLoading && invoiceResults.length>0"><div class="space-y-1"><template x-for="inv in invoiceResults" :key="inv.type+'-'+inv.id"><button type="button" @click="pickInvoice(inv)" class="w-full text-left px-3 py-2.5 rounded-lg hover:bg-red-50 transition-colors" style="border:none;outline:none;background:transparent;"><div class="flex items-center justify-between gap-2"><div class="flex items-center gap-2"><span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase" :class="inv.type==='invoice'?'bg-blue-50 text-blue-700':'bg-purple-50 text-purple-700'" x-text="inv.type==='invoice'?'INV':'PRO'"></span><span class="text-xs font-mono font-semibold text-stone-800" x-text="inv.number"></span></div><span class="text-[10px] text-stone-400" x-text="inv.date"></span></div><div class="flex items-center justify-between mt-1"><span class="text-[11px] text-stone-600" x-text="inv.party_name"></span><span class="text-[11px] font-semibold text-stone-700" x-text="'₹ '+inv.grand_total"></span></div><div class="text-[10px] text-stone-400 mt-0.5" x-text="inv.items.length+' item(s)'"></div></button></template></div></template>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function customerSearch() {
            return {
                query: '',
                selectedName: '',
                open: false,
                loading: false,
                suggestions: [],
                highlightIdx: -1,
                setCustomer(n) {
                    this.selectedName = n;
                    this.query = '';
                },
                async search() {
                    this.open = true;
                    this.loading = true;
                    this.highlightIdx = -1;
                    try {
                        const r = await fetch(
                            `/sales/credit-notes/search-customers?q=${encodeURIComponent(this.query)}`, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': _csrf()
                                }
                            });
                        const d = await r.json();
                        this.suggestions = [...(d.recent ?? []), ...(d.suggestions ?? [])];
                    } catch (e) {} finally {
                        this.loading = false;
                    }
                },
                select(c) {
                    this.selectedName = c.name;
                    this.query = '';
                    this.open = false;
                    this.highlightIdx = -1;
                    this.$dispatch('customer-selected', c);
                },
                clearCustomer() {
                    this.selectedName = '';
                    this.query = '';
                    this.suggestions = [];
                    this.highlightIdx = -1;
                    this.$dispatch('customer-selected', null);
                },
                close() {
                    this.open = false;
                    this.highlightIdx = -1;
                },
                moveDown() {
                    if (!this.open || !this.suggestions.length) return;
                    this.highlightIdx = (this.highlightIdx + 1) % this.suggestions.length;
                    this.$nextTick(() => {
                        const dd = this.$refs.customerDropdown;
                        if (dd) {
                            const b = dd.querySelectorAll('button');
                            if (b[this.highlightIdx]) b[this.highlightIdx].scrollIntoView({
                                block: 'nearest'
                            });
                        }
                    });
                },
                moveUp() {
                    if (!this.open || !this.suggestions.length) return;
                    this.highlightIdx = this.highlightIdx <= 0 ? this.suggestions.length - 1 : this.highlightIdx - 1;
                },
                selectHighlighted() {
                    if (this.highlightIdx >= 0 && this.highlightIdx < this.suggestions.length) this.select(this.suggestions[
                        this.highlightIdx]);
                }
            };
        }

        function creditNoteForm() {
            return {
                creditNoteId: {{ isset($creditNote) ? $creditNote->id : 'null' }},
                saving: false,
                submitAfterSave: false,
                errorMsg: '',
                invoicePickerOpen: false, invoiceSearchQuery: '', invoiceSearchLoading: false, invoiceResults: [],
                form: {
                    credit_note_number: '{{ $nextNumber ?? '' }}',
                    credit_note_date: '{{ isset($creditNote) ? $creditNote->credit_note_date->format('Y-m-d') : now()->format('Y-m-d') }}',
                    party_id: {{ isset($creditNote) ? $creditNote->party_id : 'null' }},
                    reference_number: '{{ isset($creditNote) ? $creditNote->reference_number ?? '' : '' }}',
                    reason: '{{ isset($creditNote) ? $creditNote->reason ?? '' : '' }}',
                    place_of_supply: '{{ isset($creditNote) ? $creditNote->place_of_supply ?? '' : '' }}',
                    is_igst: {{ isset($creditNote) && $creditNote->is_igst ? 'true' : 'false' }},
                    notes: `{!! isset($creditNote) ? addslashes($creditNote->notes ?? '') : '' !!}`,
                    terms: `{!! isset($creditNote) ? addslashes($creditNote->terms ?? '') : '' !!}`,
                    narration: `{!! isset($creditNote) ? addslashes($creditNote->narration ?? '') : '' !!}`,
                    bill_discount_type: 'percentage',
                    bill_discount_value: 0
                },
                items: (function() {
                    const l = [];
                    @if (isset($creditNote) && $creditNote->items->count()) @foreach ($creditNote->items as $item)l.push({product_id:{{ $item->product_id ?? 'null' }},productName:'{{ addslashes($item->product?->name ?? '') }}',productQuery:'',productOpen:false,productLoading:false,productSuggestions:[],description:`{!! addslashes($item->description ?? '') !!}`,hsn_sac:'{{ $item->hsn_sac ?? '' }}',qty:{{ $item->qty }},unit:'{{ $item->unit ?? '' }}',unit_price:{{ $item->unit_price }},discount_type:'{{ $item->discount_amount > 0 && $item->discount_pct == 0 ? 'amount' : 'percentage' }}',discount_value:{{ $item->discount_pct > 0 ? $item->discount_pct : $item->discount_amount }},tax_rate:{{ $item->tax_rate }},_lineGross:0,_discAmt:0,_taxable:0,_cgst:0,_sgst:0,_igst:0,_lineTotal:0});@endforeach @else l.push({product_id:null,productName:'',productQuery:'',productOpen:false,productLoading:false,productSuggestions:[],description:'',hsn_sac:'',qty:1,unit:'',unit_price:0,discount_type:'percentage',discount_value:0,tax_rate:0,_lineGross:0,_discAmt:0,_taxable:0,_cgst:0,_sgst:0,_igst:0,_lineTotal:0}); @endif
                    return l;
                })(),
                totals: {
                    subtotal: 0,
                    discount: 0,
                    taxable: 0,
                    cgst: 0,
                    sgst: 0,
                    igst: 0,
                    grandTotal: 0
                },
                init() {
                    this.items.forEach((_, i) => this.computeLineTotal(i));
                    this.$el.addEventListener('customer-selected', (e) => this.onCustomerSelected(e.detail));
                    @if (isset($creditNote) && $creditNote->party)
                        this.$nextTick(() => {
                            const cs = this.$el.querySelector('[x-data*="customerSearch"]');
                            if (cs && cs._x_dataStack) cs._x_dataStack[0].setCustomer(
                                '{{ addslashes($creditNote->party->display_name ?? $creditNote->party->name) }}');
                        });
                    @endif
                },
                makeItem() {
                    return {
                        product_id: null,
                        productName: '',
                        productQuery: '',
                        productOpen: false,
                        productLoading: false,
                        productSuggestions: [],
                        description: '',
                        hsn_sac: '',
                        qty: 1,
                        unit: '',
                        unit_price: 0,
                        discount_type: 'percentage',
                        discount_value: 0,
                        tax_rate: 0,
                        _lineGross: 0,
                        _discAmt: 0,
                        _taxable: 0,
                        _cgst: 0,
                        _sgst: 0,
                        _igst: 0,
                        _lineTotal: 0
                    };
                },
                addItem() {
                    this.items.push(this.makeItem());
                },
                removeItem(idx) {
                    if (this.items.length <= 1) return;
                    this.items.splice(idx, 1);
                    this.computeGrandTotal();
                },
                onCustomerSelected(c) {
                    if (!c) {
                        this.form.party_id = null;
                        return;
                    }
                    this.form.party_id = c.id;
                    const cs = '{{ optional($company)->state ?? '' }}';
                    if (cs && c.state) this.form.is_igst = c.state.trim().toLowerCase() !== cs.trim().toLowerCase();
                    this.items.forEach((_, i) => this.computeLineTotal(i));
                },
                async searchProductsForItem(idx) {
                    const item = this.items[idx];
                    item.productLoading = true;
                    item.productOpen = true;
                    try {
                        const r = await fetch(
                            `/sales/credit-notes/search-products?q=${encodeURIComponent(item.productQuery??'')}`, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': _csrf()
                                }
                            });
                        const d = await r.json();
                        item.productSuggestions = [...(d.recent ?? []), ...(d.suggestions ?? [])];
                    } catch (e) {} finally {
                        item.productLoading = false;
                    }
                },
                selectProduct(idx, p) {
                    const item = this.items[idx];
                    item.product_id = p.id;
                    item.productName = p.name;
                    item.productQuery = '';
                    item.productOpen = false;
                    item.description = item.description || p.description || p.name;
                    item.hsn_sac = p.hsn_sac ?? '';
                    item.unit = p.unit ?? '';
                    item.unit_price = p.unit_price ?? 0;
                    item.tax_rate = p.tax_rate ?? 0;
                    this.computeLineTotal(idx);
                },
                async searchInvoices() {
                    this.invoiceSearchLoading = true;
                    try {
                        const res = await fetch(`/sales/delivery/search-invoices?q=${encodeURIComponent(this.invoiceSearchQuery)}`, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': _csrf() } });
                        const data = await res.json();
                        this.invoiceResults = data.results ?? [];
                    } catch (e) {} finally { this.invoiceSearchLoading = false; }
                },
                pickInvoice(inv) {
                    this.form.party_id = inv.party_id;
                    this.form.reference_number = inv.number;
                    const csEl = this.$el.querySelector('[x-data*="customerSearch"]');
                    if (csEl && csEl._x_dataStack) csEl._x_dataStack[0].setCustomer(inv.party_name);
                    this.items = inv.items.map(i => ({
                        product_id: i.product_id || null, productName: i.description || '', productQuery: '', productOpen: false, productLoading: false, productSuggestions: [],
                        description: i.description || '', hsn_sac: i.hsn_sac || '', qty: i.qty || 1, unit: i.unit || '',
                        unit_price: i.unit_price || 0, discount_type: 'percentage', discount_value: 0, tax_rate: i.tax_rate || 0,
                        _lineGross: 0, _discAmt: 0, _taxable: 0, _cgst: 0, _sgst: 0, _igst: 0, _lineTotal: 0
                    }));
                    if (this.items.length === 0) this.items = [this.makeItem()];
                    this.items.forEach((_, i) => this.computeLineTotal(i));
                    this.invoicePickerOpen = false;
                },
                computeLineTotal(idx) {
                    const item = this.items[idx],
                        qty = parseFloat(item.qty) || 0,
                        price = parseFloat(item.unit_price) || 0,
                        taxRate = parseFloat(item.tax_rate) || 0,
                        discVal = parseFloat(item.discount_value) || 0;
                    const gross = qty * price,
                        discAmt = item.discount_type === 'amount' ? Math.min(discVal, gross) : gross * discVal / 100,
                        taxable = gross - discAmt;
                    item._lineGross = gross;
                    item._discAmt = discAmt;
                    item._taxable = taxable;
                    if (this.form.is_igst) {
                        item._igst = taxable * taxRate / 100;
                        item._cgst = 0;
                        item._sgst = 0;
                    } else {
                        item._cgst = taxable * (taxRate / 2) / 100;
                        item._sgst = taxable * (taxRate / 2) / 100;
                        item._igst = 0;
                    }
                    item._lineTotal = taxable + item._cgst + item._sgst + item._igst;
                    this.computeGrandTotal();
                },
                computeGrandTotal() {
                    let sub = 0,
                        di = 0,
                        tax = 0,
                        cg = 0,
                        sg = 0,
                        ig = 0;
                    this.items.forEach(i => {
                        sub += i._lineGross || 0;
                        di += i._discAmt || 0;
                        tax += i._taxable || 0;
                        cg += i._cgst || 0;
                        sg += i._sgst || 0;
                        ig += i._igst || 0;
                    });
                    const bdv = parseFloat(this.form.bill_discount_value) || 0;
                    let bd = 0;
                    if (bdv > 0) bd = this.form.bill_discount_type === 'amount' ? Math.min(bdv, tax) : tax * bdv / 100;
                    const at = tax - bd,
                        td = di + bd;
                    let ac = cg,
                        as2 = sg,
                        ai = ig;
                    if (bd > 0 && tax > 0) {
                        const r = at / tax;
                        ac = cg * r;
                        as2 = sg * r;
                        ai = ig * r;
                    }
                    this.totals = {
                        subtotal: this.r2(sub),
                        discount: this.r2(td),
                        taxable: this.r2(at),
                        cgst: this.r2(ac),
                        sgst: this.r2(as2),
                        igst: this.r2(ai),
                        grandTotal: this.r2(at + ac + as2 + ai)
                    };
                },
                async save(andSubmit) {
                    this.errorMsg = '';
                    this.saving = true;
                    this.submitAfterSave = andSubmit;
                    if (!this.form.credit_note_date) {
                        this.errorMsg = 'Date is required.';
                        this.saving = false;
                        return;
                    }
                    if (!this.form.party_id) {
                        this.errorMsg = 'Please select a customer.';
                        this.saving = false;
                        return;
                    }
                    if (!this.form.reason) {
                        this.errorMsg = 'Please select a reason.';
                        this.saving = false;
                        return;
                    }
                    if (!this.items.some(i => (parseFloat(i.qty) || 0) > 0 && i.description.trim())) {
                        this.errorMsg = 'At least one item required.';
                        this.saving = false;
                        return;
                    }
                    const payload = {
                        credit_note_date: this.form.credit_note_date,
                        party_id: this.form.party_id,
                        reference_number: this.form.reference_number || '',
                        reason: this.form.reason,
                        place_of_supply: this.form.place_of_supply || '',
                        is_igst: this.form.is_igst ? 1 : 0,
                        notes: this.form.notes || '',
                        terms: this.form.terms || '',
                        narration: this.form.narration || '',
                        bill_discount_type: this.form.bill_discount_type,
                        bill_discount_value: parseFloat(this.form.bill_discount_value) || 0,
                        items: this.items.filter(i => i.description.trim() || i.product_id).map(i => ({
                            product_id: i.product_id || null,
                            description: i.description || '',
                            hsn_sac: i.hsn_sac || '',
                            qty: parseFloat(i.qty) || 0,
                            unit: i.unit || '',
                            unit_price: parseFloat(i.unit_price) || 0,
                            discount_type: i.discount_type,
                            discount_value: parseFloat(i.discount_value) || 0,
                            tax_rate: parseFloat(i.tax_rate) || 0
                        }))
                    };
                    try {
                        const isEdit = !!this.creditNoteId,
                            url = isEdit ? `/sales/credit-notes/${this.creditNoteId}` : '/sales/credit-notes',
                            method = isEdit ? 'PUT' : 'POST';
                        const res = await fetch(url, {
                            method,
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': _csrf()
                            },
                            body: JSON.stringify(payload)
                        });
                        const json = await res.json();
                        if (!res.ok || !json.success) {
                            this.errorMsg = json.errors ? Object.values(json.errors).flat().join(' | ') : (json
                                .message ?? 'Error.');
                            return;
                        }
                        if (andSubmit && json.id) {
                            const sr = await fetch(`/sales/credit-notes/${json.id}/submit`, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': _csrf()
                                }
                            });
                            const sj = await sr.json();
                            if (!sj.success) {
                                this.errorMsg = sj.message ?? 'Saved but could not submit.';
                                return;
                            }
                            window.location.href = '/sales/credit-notes';
                            return;
                        }
                        if (!this.creditNoteId && json.redirect) {
                            window.location.href = json.redirect;
                        } else {
                            if (json.id) this.creditNoteId = json.id;
                            this._toast('Draft saved.');
                        }
                    } catch (e) {
                        this.errorMsg = 'Network error.';
                    } finally {
                        this.saving = false;
                    }
                },
                r2(n) {
                    return Math.round((n + Number.EPSILON) * 100) / 100;
                },
                fmt(n) {
                    return (parseFloat(n) || 0).toLocaleString('en-IN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                },
                _toast(m) {
                    const el = document.createElement('div');
                    el.style.cssText =
                        'position:fixed;bottom:1.25rem;right:1.25rem;z-index:9999;padding:.6rem 1rem;border-radius:.75rem;font-size:.75rem;font-weight:500;display:flex;align-items:center;gap:.5rem;box-shadow:0 4px 16px rgba(0,0,0,.1);background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;';
                    el.innerHTML = `<span>${m}</span>`;
                    document.body.appendChild(el);
                    setTimeout(() => el.remove(), 3000);
                }
            };
        }

        function _csrf() {
            return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        }
    </script>
@endpush
