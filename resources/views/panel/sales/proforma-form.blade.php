@extends('layouts.app')

@section('title', isset($proforma) ? 'Edit Proforma — ' . $proforma->proforma_number : 'New Proforma Invoice')
@section('page-title', isset($proforma) ? 'Edit Proforma' : 'New Proforma Invoice')

@section('breadcrumb')
    <span>Sales</span>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('sales.proforma') }}" class="hover:text-stone-600 transition-colors">Proforma</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-stone-600">{{ isset($proforma) ? $proforma->proforma_number : 'New' }}</span>
@endsection

@section('content')
    <div x-data="proformaForm()" x-init="init()"
        class="flex flex-col bg-white border border-stone-200 rounded-xl overflow-hidden"
        style="height: calc(100vh - 7.5rem);">

        {{-- ── HEADER BAND ──────────────────────────────────────────────────── --}}
        <div class="shrink-0 border-b border-stone-200 bg-stone-50 px-3 py-2">
            <div class="flex items-end gap-2 min-w-0">

                <div class="shrink-0 w-36">
                    <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-0.5">Proforma #</label>
                    <input type="text" :value="form.proforma_number" readonly autocomplete="off"
                        class="w-full h-7 px-2 text-xs font-mono bg-stone-100 border border-stone-300 rounded-lg text-stone-500 cursor-not-allowed focus:outline-none">
                </div>

                <div class="shrink-0 w-32">
                    <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-0.5">
                        Date <span class="text-red-600">*</span>
                    </label>
                    <input type="date" x-model="form.proforma_date" @change="onDateChange()" required autocomplete="off"
                        class="w-full h-7 px-2 text-xs border border-stone-300 rounded-lg text-stone-800 bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors">
                </div>

                <div class="shrink-0 w-32">
                    <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-0.5">Due Date</label>
                    <input type="date" x-model="form.due_date" autocomplete="off"
                        class="w-full h-7 px-2 text-xs border border-stone-300 rounded-lg text-stone-800 bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors">
                </div>

                <div class="flex-1 min-w-0" x-data="customerSearch()" @keydown.escape="close()">
                    <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-0.5">
                        Customer <span class="text-red-600">*</span>
                    </label>
                    <div class="relative">
                        <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="search()"
                            @click="if(!open) search()"
                            @keydown.arrow-down.prevent="navDown()"
                            @keydown.arrow-up.prevent="navUp()"
                            @keydown.enter.prevent="navSelect()"
                            :placeholder="selectedName || 'Search customer…'"
                            :class="selectedName && !query ? 'text-stone-800 font-medium' : 'text-stone-800'"
                            autocomplete="new-password" spellcheck="false"
                            class="w-full h-7 px-2 pr-6 text-xs border border-stone-300 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400">
                        <button x-show="selectedName" @click="clearCustomer()" type="button"
                            class="absolute right-1.5 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <div x-show="open" x-cloak @click.outside="close()"
                            class="absolute top-full left-0 right-0 mt-1 bg-white rounded-xl z-50 max-h-64 overflow-y-auto"
                            style="border:1px solid #e7e5e4;box-shadow:0 8px 24px rgba(0,0,0,.12);">
                            <template x-if="loading">
                                <div class="px-3 py-4 text-center text-xs text-stone-400">Searching…</div>
                            </template>
                            <template x-if="!loading && recent.length === 0 && suggestions.length === 0">
                                <div class="px-3 py-4 text-center text-xs text-stone-400">No customers found</div>
                            </template>
                            <template x-if="!loading && recent.length > 0">
                                <div>
                                    <div class="px-3 py-1.5 text-[10px] font-semibold text-stone-400 uppercase tracking-wide bg-stone-50" style="border-bottom:1px solid #f5f5f4;">Recently Used</div>
                                    <template x-for="(c, ci) in recent" :key="c.id">
                                        <button type="button" @click="select(c)"
                                            :style="navIdx === ci ? 'background:#fef2f2;' : ''"
                                            class="w-full text-left px-3 py-2 transition-colors hover:bg-red-50" style="border:none;border-bottom:1px solid #f5f5f4;outline:none;background:transparent;">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-xs font-medium text-stone-800" x-html="highlight(c.name)"></span>
                                                <span class="text-[10px] text-stone-400 font-mono shrink-0" x-text="c.gstin"></span>
                                            </div>
                                            <div class="text-[10px] text-stone-400 mt-0.5" x-text="[c.city, c.state].filter(Boolean).join(', ')"></div>
                                        </button>
                                    </template>
                                </div>
                            </template>
                            <template x-if="!loading && suggestions.length > 0">
                                <div>
                                    <div class="px-3 py-1.5 text-[10px] font-semibold text-stone-400 uppercase tracking-wide bg-stone-50" style="border-top:1px solid #f5f5f4;border-bottom:1px solid #f5f5f4;">Other Customers</div>
                                    <template x-for="(c, ci) in suggestions" :key="c.id">
                                        <button type="button" @click="select(c)"
                                            :style="navIdx === (recent.length + ci) ? 'background:#fef2f2;' : ''"
                                            class="w-full text-left px-3 py-2 transition-colors hover:bg-red-50" style="border:none;border-bottom:1px solid #f5f5f4;outline:none;background:transparent;">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-xs font-medium text-stone-800" x-html="highlight(c.name)"></span>
                                                <span class="text-[10px] text-stone-400 font-mono shrink-0" x-text="c.gstin"></span>
                                            </div>
                                            <div class="text-[10px] text-stone-400 mt-0.5" x-text="[c.city, c.state].filter(Boolean).join(', ')"></div>
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="shrink-0 w-28">
                    <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-0.5">Reference #</label>
                    <input type="text" x-model="form.reference_number" placeholder="PO / Ref no." autocomplete="off"
                        class="w-full h-7 px-2 text-xs border border-stone-300 rounded-lg text-stone-800 bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400">
                </div>

                <div class="shrink-0 w-28">
                    <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-0.5">Place of Supply</label>
                    <input type="text" x-model="form.place_of_supply" placeholder="State" autocomplete="off"
                        class="w-full h-7 px-2 text-xs border border-stone-300 rounded-lg text-stone-800 bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400">
                </div>
            </div>

            <div class="flex items-center gap-3 mt-1.5">
                <button type="button" @click="form.is_igst = !form.is_igst"
                    class="flex items-center gap-1.5 cursor-pointer select-none focus:outline-none">
                    <div class="relative w-8 h-4 rounded-full shrink-0"
                        x-bind:style="form.is_igst ? 'background:#7f1d1d;transition:background .2s' : 'background:#d6d3d1;transition:background .2s'">
                        <div class="absolute top-0.5 w-3 h-3 bg-white rounded-full shadow"
                            x-bind:style="form.is_igst ? 'left:18px;transition:left .2s;margin-top:2px' : 'left:2px;transition:left .2s;margin-top:2px'"></div>
                    </div>
                    <span class="text-[10px] font-semibold uppercase tracking-wide"
                        x-bind:style="form.is_igst ? 'color:#7f1d1d' : 'color:#78716c'"
                        x-text="form.is_igst ? 'IGST (Inter-state)' : 'CGST + SGST (Intra-state)'"></span>
                </button>
                <span x-show="form.party_id" class="text-[10px] text-stone-400">
                    Customer state: <span class="font-medium text-stone-600" x-text="customerState || '—'"></span>
                </span>
            </div>
        </div>

        {{-- ── LINE ITEMS (scrollable middle) ──────────────────────────────────── --}}
        <div class="flex-1 overflow-y-auto min-h-0" id="line-items-scroll" style="overflow-x:hidden;"
             @product-dropdown-open.window="document.getElementById('line-items-scroll').style.overflowY = 'visible'"
             @product-dropdown-close.window="document.getElementById('line-items-scroll').style.overflowY = 'auto'">
            <table class="w-full text-xs border-collapse" style="table-layout:fixed;">
                <colgroup>
                    <col style="width:24px;"><col style="width:25%;"><col style="width:70px;">
                    <col style="width:54px;"><col style="width:48px;"><col style="width:80px;">
                    <col style="width:94px;"><col style="width:54px;"><col style="width:80px;"><col style="width:24px;">
                </colgroup>
                <thead class="sticky top-0 z-10 bg-stone-100 border-b border-stone-200">
                    <tr>
                        <th class="px-2 py-1.5 text-left text-[10px] font-semibold text-stone-500 uppercase tracking-wide">#</th>
                        <th class="px-2 py-1.5 text-left text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Product / Description</th>
                        <th class="px-1 py-1.5 text-left text-[10px] font-semibold text-stone-500 uppercase tracking-wide">HSN/SAC</th>
                        <th class="px-1 py-1.5 text-right text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Qty</th>
                        <th class="px-1 py-1.5 text-left text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Unit</th>
                        <th class="px-1 py-1.5 text-right text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Price (₹)</th>
                        <th class="px-1 py-1.5 text-right text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Discount</th>
                        <th class="px-1 py-1.5 text-right text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Tax %</th>
                        <th class="px-1 py-1.5 text-right text-[10px] font-semibold text-stone-500 uppercase tracking-wide">Total (₹)</th>
                        <th class="py-1.5 text-center"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, idx) in items" :key="idx">
                        <tr class="border-b border-stone-100 hover:bg-stone-50/50 group align-top">
                            <td class="px-2 pt-2 text-[10px] text-stone-400 font-mono" x-text="idx + 1"></td>
                            <td class="px-1 py-1.5" style="overflow:visible;">
                                <div class="relative" x-data="{}" @keydown.escape="item.productOpen = false; $dispatch('product-dropdown-close')">
                                    <input type="text" x-model="item.productQuery"
                                        @input.debounce.250ms="searchProductsForItem(idx)"
                                        @focus="searchProductsForItem(idx)"
                                        @keydown.arrow-down.prevent="productNavDown(idx)"
                                        @keydown.arrow-up.prevent="productNavUp(idx)"
                                        @keydown.enter.prevent="productNavSelect(idx)"
                                        :placeholder="item.productName || 'Search product…'"
                                        :class="item.productName && !item.productQuery ? 'font-medium text-stone-800' : 'text-stone-800'"
                                        autocomplete="new-password" spellcheck="false"
                                        class="w-full h-6 px-2 text-xs border border-stone-200 rounded-md bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400">
                                    <div x-show="item.productOpen" x-cloak
                                        x-data="{ get navIdx() { return productNav[idx] ?? -1; } }"
                                        @click.outside="item.productOpen = false; $dispatch('product-dropdown-close')"
                                        class="absolute top-full left-0 w-72 mt-1 bg-white rounded-xl z-50 max-h-52 overflow-y-auto"
                                        style="border:1px solid #e7e5e4;box-shadow:0 8px 24px rgba(0,0,0,.12);">
                                        <template x-if="item.productLoading">
                                            <div class="px-3 py-3 text-center text-xs text-stone-400"><svg class="w-4 h-4 animate-spin mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg></div>
                                        </template>
                                        <template x-if="!item.productLoading && item.productRecent.length === 0 && item.productSuggestions.length === 0">
                                            <div class="px-3 py-3 text-center text-xs text-stone-400">No products found</div>
                                        </template>
                                        <template x-if="!item.productLoading && item.productRecent.length > 0">
                                            <div>
                                                <div class="px-3 py-1 text-[10px] font-semibold text-stone-400 uppercase tracking-wide bg-stone-50" style="border-bottom:1px solid #f5f5f4;">Recently Used</div>
                                                <template x-for="(p, pi) in item.productRecent" :key="p.id">
                                                    <button type="button" @click="selectProduct(idx, p)"
                                                        :style="navIdx === pi ? 'background:#fef2f2;' : ''"
                                                        class="w-full text-left px-3 py-1.5 transition-colors hover:bg-red-50" style="border:none;border-bottom:1px solid #f5f5f4;outline:none;background:transparent;">
                                                        <div class="flex items-center justify-between gap-2">
                                                            <span class="text-xs font-medium text-stone-800" x-html="highlightMatch(p.name, item.productQuery)"></span>
                                                            <span class="text-[10px] text-stone-400 font-mono shrink-0" x-text="p.code"></span>
                                                        </div>
                                                        <div class="text-[10px] text-stone-400" x-text="'HSN: ' + (p.hsn_sac || '—') + '  |  ₹' + p.unit_price.toFixed(2)"></div>
                                                    </button>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="!item.productLoading && item.productSuggestions.length > 0">
                                            <div>
                                                <div class="px-3 py-1 text-[10px] font-semibold text-stone-400 uppercase tracking-wide bg-stone-50" style="border-top:1px solid #f5f5f4;border-bottom:1px solid #f5f5f4;">Other Products</div>
                                                <template x-for="(p, pi) in item.productSuggestions" :key="p.id">
                                                    <button type="button" @click="selectProduct(idx, p)"
                                                        :style="navIdx === (item.productRecent.length + pi) ? 'background:#fef2f2;' : ''"
                                                        class="w-full text-left px-3 py-1.5 transition-colors hover:bg-red-50" style="border:none;border-bottom:1px solid #f5f5f4;outline:none;background:transparent;">
                                                        <div class="flex items-center justify-between gap-2">
                                                            <span class="text-xs font-medium text-stone-800" x-html="highlightMatch(p.name, item.productQuery)"></span>
                                                            <span class="text-[10px] text-stone-400 font-mono shrink-0" x-text="p.code"></span>
                                                        </div>
                                                        <div class="text-[10px] text-stone-400" x-text="'HSN: ' + (p.hsn_sac || '—') + '  |  ₹' + p.unit_price.toFixed(2)"></div>
                                                    </button>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                <input type="text" x-model="item.description" placeholder="Description…"
                                    class="w-full h-5 mt-0.5 px-2 text-[10px] border border-stone-100 rounded bg-transparent focus:bg-white focus:border-stone-300 focus:outline-none transition-colors placeholder-stone-300 text-stone-600">
                            </td>
                            <td class="px-1 py-1.5"><input type="text" x-model="item.hsn_sac" placeholder="HSN" class="w-full h-6 px-1 text-xs border border-stone-200 rounded-md bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800"></td>
                            <td class="px-1 py-1.5"><input type="number" x-model="item.qty" @input="computeLineTotal(idx)" min="0.001" step="0.001" placeholder="1" class="w-full h-6 px-1 text-xs text-right border border-stone-200 rounded-md bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800"></td>
                            <td class="px-1 py-1.5"><input type="text" x-model="item.unit" placeholder="pcs" class="w-full h-6 px-1 text-xs border border-stone-200 rounded-md bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800"></td>
                            <td class="px-1 py-1.5"><input type="number" x-model="item.unit_price" @input="computeLineTotal(idx)" min="0" step="0.01" placeholder="0.00" class="w-full h-6 px-1 text-xs text-right border border-stone-200 rounded-md bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800"></td>
                            <td class="px-1 py-1.5">
                                <div class="flex items-center">
                                    <input type="number" x-model="item.discount_value" @input="computeLineTotal(idx)" min="0" step="0.01" placeholder="0" class="w-full h-6 px-1 text-xs text-right border border-stone-200 rounded-l-md bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800">
                                    <button type="button" @click="item.discount_type = item.discount_type === 'percentage' ? 'amount' : 'percentage'; computeLineTotal(idx)" class="h-6 w-6 shrink-0 text-[10px] font-semibold border border-l-0 border-stone-200 rounded-r-md bg-stone-50 hover:bg-stone-100 transition-colors text-center leading-none" style="color:#78716c;" x-text="item.discount_type === 'percentage' ? '%' : '₹'"></button>
                                </div>
                            </td>
                            <td class="px-1 py-1.5"><input type="number" x-model="item.tax_rate" @input="computeLineTotal(idx)" min="0" max="100" step="0.01" placeholder="0" class="w-full h-6 px-1 text-xs text-right border border-stone-200 rounded-md bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800"></td>
                            <td class="px-1 py-1.5 text-right">
                                <span class="text-xs font-semibold text-stone-800" x-text="fmt(item._lineTotal || 0)"></span>
                                <div class="text-[10px] text-stone-400 mt-0.5 whitespace-nowrap">
                                    <template x-if="!form.is_igst && (item._cgst || 0) > 0"><span x-text="'C+S: ' + fmt((item._cgst||0) + (item._sgst||0))"></span></template>
                                    <template x-if="form.is_igst && (item._igst || 0) > 0"><span x-text="'IGST: ' + fmt(item._igst||0)"></span></template>
                                </div>
                            </td>
                            <td class="px-1 py-1.5 text-center">
                                <button type="button" @click="removeItem(idx)" :disabled="items.length <= 1"
                                    class="w-5 h-5 flex items-center justify-center rounded transition-colors"
                                    :style="items.length > 1 ? 'color:#d6d3d1;cursor:pointer;' : 'color:#e7e5e4;cursor:not-allowed;'">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <div class="px-3 py-2 border-t border-stone-100">
                <button type="button" @click="addItem()" class="inline-flex items-center gap-1.5 text-xs font-semibold text-red-700 hover:text-red-800 hover:bg-red-50 px-2.5 py-1 rounded-lg transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    Add Line
                </button>
            </div>
        </div>

        {{-- ── FOOTER (fixed) ──────────────────────────────────────────────────── --}}
        <div class="shrink-0 border-t border-stone-200 bg-white">
            <div class="flex items-stretch gap-0 divide-x divide-stone-200">
                <div class="flex-1 px-3 py-2 flex flex-col gap-1.5 border-none">
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-0.5">Notes</label>
                            <textarea x-model="form.notes" rows="2" placeholder="Notes visible on proforma…" class="w-full px-2 py-1 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-700 resize-none"></textarea>
                        </div>
                        <div class="flex-1">
                            <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-0.5">Terms &amp; Conditions</label>
                            <textarea x-model="form.terms" rows="2" placeholder="Payment terms, conditions…" class="w-full px-2 py-1 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-700 resize-none"></textarea>
                        </div>
                    </div>
                    <div class="w-full">
                        <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-0.5">Narration</label>
                        <textarea x-model="form.narration" rows="1" placeholder="Internal narration / remarks…" class="w-full px-2 py-1 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-700 resize-none"></textarea>
                    </div>
                </div>

                <div class="px-3 py-2 flex flex-col border-none justify-center gap-1 min-w-[180px]" style="background:#fef2f2;">
                    <label class="block text-[10px] font-semibold uppercase tracking-wide" style="color:#7f1d1d;">Bill Discount</label>
                    <div class="flex items-center">
                        <input type="number" x-model="form.bill_discount_value" @input="computeGrandTotal()" min="0" step="0.01" placeholder="0" class="w-full h-7 px-2 text-xs text-right border border-stone-200 rounded-l-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800">
                        <button type="button" @click="form.bill_discount_type = form.bill_discount_type === 'percentage' ? 'amount' : 'percentage'; computeGrandTotal()" class="h-7 w-8 shrink-0 text-xs font-semibold border border-l-0 border-stone-200 rounded-r-lg bg-stone-50 hover:bg-stone-100 transition-colors text-center leading-none" style="color:#7f1d1d;" x-text="form.bill_discount_type === 'percentage' ? '%' : '₹'"></button>
                    </div>
                </div>

                <div class="px-4 py-2 border-none min-w-[220px] flex flex-col justify-center gap-0.5">
                    <div class="flex items-center justify-between gap-4"><span class="text-[10px] text-stone-500">Subtotal</span><span class="text-xs font-medium text-stone-700" x-text="'₹ ' + fmt(totals.subtotal)"></span></div>
                    <div class="flex items-center justify-between gap-4" x-show="totals.discount > 0"><span class="text-[10px] text-stone-500">Discount</span><span class="text-xs font-medium text-red-600" x-text="'− ₹ ' + fmt(totals.discount)"></span></div>
                    <div class="flex items-center justify-between gap-4"><span class="text-[10px] text-stone-500">Taxable</span><span class="text-xs font-medium text-stone-700" x-text="'₹ ' + fmt(totals.taxable)"></span></div>
                    <template x-if="!form.is_igst"><div>
                        <div class="flex items-center justify-between gap-4" x-show="totals.cgst > 0"><span class="text-[10px] text-stone-500">CGST</span><span class="text-xs text-stone-600" x-text="'₹ ' + fmt(totals.cgst)"></span></div>
                        <div class="flex items-center justify-between gap-4" x-show="totals.sgst > 0"><span class="text-[10px] text-stone-500">SGST</span><span class="text-xs text-stone-600" x-text="'₹ ' + fmt(totals.sgst)"></span></div>
                    </div></template>
                    <template x-if="form.is_igst"><div class="flex items-center justify-between gap-4" x-show="totals.igst > 0"><span class="text-[10px] text-stone-500">IGST</span><span class="text-xs text-stone-600" x-text="'₹ ' + fmt(totals.igst)"></span></div></template>
                    <div class="flex items-center justify-between gap-4 pt-1 mt-0.5 border-t border-stone-200"><span class="text-xs font-bold text-stone-800">Grand Total</span><span class="text-sm font-bold text-red-700" x-text="'₹ ' + fmt(totals.grandTotal)"></span></div>
                </div>

                <div class="px-3 py-2 flex flex-col justify-center gap-1.5 min-w-[160px]">
                    <a href="{{ route('sales.proforma') }}" class="w-full h-7 flex items-center justify-center gap-1.5 text-xs font-semibold text-stone-600 border border-stone-200 rounded-lg hover:bg-stone-50 transition-colors px-3">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Cancel
                    </a>
                    <button type="button" @click="save(false)" :disabled="saving" class="w-full h-7 flex items-center justify-center gap-1.5 text-xs font-semibold text-stone-700 border border-stone-200 rounded-lg hover:bg-stone-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed px-3">
                        <span x-text="saving && !submitAfterSave ? 'Saving…' : 'Save Draft'"></span>
                    </button>
                    <button type="button" @click="save(true)" :disabled="saving" class="w-full h-7 flex items-center justify-center gap-1.5 text-xs font-semibold text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed px-3" style="background:#7f1d1d;">
                        <span x-text="saving && submitAfterSave ? 'Submitting…' : 'Create & Submit'"></span>
                    </button>
                </div>
            </div>

            <div x-show="errorMsg" x-cloak class="px-4 py-2 bg-red-50 border-t border-red-200 flex items-center gap-2">
                <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-xs text-red-700 flex-1" x-text="errorMsg"></span>
                <button @click="errorMsg = ''" class="text-red-400 hover:text-red-600"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function customerSearch() {
    return {
        query: '', selectedName: '', open: false, loading: false, recent: [], suggestions: [], navIdx: -1,
        setCustomer(name) { this.selectedName = name; this.query = ''; },
        async search() {
            this.open = true; this.loading = true; this.navIdx = -1;
            try {
                const res = await fetch(`/sales/proforma/search-customers?q=${encodeURIComponent(this.query)}`, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': _csrf() } });
                const data = await res.json();
                this.recent = data.recent ?? []; this.suggestions = data.suggestions ?? [];
            } catch (e) { console.error('Customer search error', e); } finally { this.loading = false; }
        },
        allItems() { return [...this.recent, ...this.suggestions]; },
        navDown() {
            if (!this.open) { this.search(); return; }
            const all = this.allItems();
            if (all.length === 0) return;
            this.navIdx = Math.min(this.navIdx + 1, all.length - 1);
            this._scrollToNav();
        },
        navUp() {
            this.navIdx = Math.max(this.navIdx - 1, -1);
            if (this.navIdx >= 0) this._scrollToNav();
        },
        navSelect() {
            if (!this.open) return;
            const all = this.allItems();
            if (this.navIdx >= 0 && this.navIdx < all.length) this.select(all[this.navIdx]);
        },
        _scrollToNav() {
            this.$nextTick(() => {
                const dropdown = this.$el.querySelector('[x-show*="open"]');
                if (!dropdown) return;
                const btns = dropdown.querySelectorAll('button');
                if (btns[this.navIdx]) btns[this.navIdx].scrollIntoView({ block: 'nearest' });
            });
        },
        select(customer) { this.selectedName = customer.name; this.query = ''; this.open = false; this.navIdx = -1; this.$dispatch('customer-selected', customer); },
        clearCustomer() { this.selectedName = ''; this.query = ''; this.recent = []; this.suggestions = []; this.navIdx = -1; this.$dispatch('customer-selected', null); },
        close() { this.open = false; this.navIdx = -1; },
        highlight(text) { return highlightMatch(text, this.query); },
    };
}

function proformaForm() {
    return {
        proformaId: {{ isset($proforma) ? $proforma->id : 'null' }},
        saving: false, submitAfterSave: false, errorMsg: '', customerState: '', _initialized: false,

        form: {
            proforma_number: '{{ $nextNumber ?? '' }}',
            proforma_date: '{{ isset($proforma) ? $proforma->proforma_date->format('Y-m-d') : now()->format('Y-m-d') }}',
            due_date: '{{ isset($proforma) && $proforma->due_date ? $proforma->due_date->format('Y-m-d') : '' }}',
            party_id: {{ isset($proforma) ? $proforma->party_id : 'null' }},
            billing_address: `{!! isset($proforma) ? addslashes($proforma->billing_address ?? '') : '' !!}`,
            shipping_address: `{!! isset($proforma) ? addslashes($proforma->shipping_address ?? '') : '' !!}`,
            reference_number: '{{ isset($proforma) ? $proforma->reference_number : '' }}',
            place_of_supply: '{{ isset($proforma) ? $proforma->place_of_supply : '' }}',
            is_igst: {{ isset($proforma) && $proforma->is_igst ? 'true' : 'false' }},
            notes: `{!! isset($proforma) ? addslashes($proforma->notes ?? '') : '' !!}`,
            terms: `{!! isset($proforma) ? addslashes($proforma->terms ?? '') : '' !!}`,
            bill_discount_type: '{{ isset($proforma) ? $proforma->bill_discount_type ?? 'percentage' : 'percentage' }}',
            bill_discount_value: {{ isset($proforma) ? $proforma->bill_discount_value ?? 0 : 0 }},
            narration: `{!! isset($proforma) ? addslashes($proforma->narration ?? '') : '' !!}`,
        },

        items: (function() {
            const list = [];
            @if (isset($proforma) && $proforma->items->count())
                @foreach ($proforma->items as $item)
                list.push({
                    product_id: {{ $item->product_id ?? 'null' }}, productName: '{{ addslashes($item->product?->name ?? '') }}',
                    productQuery: '', productOpen: false, productLoading: false, productRecent: [], productSuggestions: [],
                    description: `{!! addslashes($item->description ?? '') !!}`, hsn_sac: '{{ $item->hsn_sac ?? '' }}',
                    qty: {{ $item->qty }}, unit: '{{ $item->unit ?? '' }}', unit_price: {{ $item->unit_price }},
                    discount_type: '{{ $item->discount_amount > 0 && $item->discount_pct == 0 ? 'amount' : 'percentage' }}',
                    discount_value: {{ $item->discount_pct > 0 ? $item->discount_pct : $item->discount_amount }},
                    tax_rate: {{ $item->tax_rate }},
                    _lineGross: 0, _discAmt: 0, _taxable: 0, _cgst: 0, _sgst: 0, _igst: 0, _lineTotal: 0,
                });
                @endforeach
            @else
                list.push({ product_id: null, productName: '', productQuery: '', productOpen: false, productLoading: false, productRecent: [], productSuggestions: [], description: '', hsn_sac: '', qty: 1, unit: '', unit_price: 0, discount_type: 'percentage', discount_value: 0, tax_rate: 0, _lineGross: 0, _discAmt: 0, _taxable: 0, _cgst: 0, _sgst: 0, _igst: 0, _lineTotal: 0 });
            @endif
            return list;
        })(),

        totals: { subtotal: 0, discount: 0, taxable: 0, cgst: 0, sgst: 0, igst: 0, grandTotal: 0 },
        productNav: {}, // keyed by idx, value = highlighted index (-1 = none)

        init() {
            this.items.forEach((_, i) => this.computeLineTotal(i));
            this.$el.addEventListener('customer-selected', (e) => { this.onCustomerSelected(e.detail); });
            @if (isset($proforma) && $proforma->party)
                this.$nextTick(() => {
                    const csEl = this.$el.querySelector('[x-data*="customerSearch"]');
                    if (csEl && csEl._x_dataStack) { csEl._x_dataStack[0].setCustomer('{{ addslashes($proforma->party->display_name ?? $proforma->party->name) }}'); }
                });
                this.customerState = '{{ $proforma->party->state ?? '' }}';
            @endif
        },

        makeItem(overrides = {}) {
            return Object.assign({ product_id: null, productName: '', productQuery: '', productOpen: false, productLoading: false, productRecent: [], productSuggestions: [], description: '', hsn_sac: '', qty: 1, unit: '', unit_price: 0, discount_type: 'percentage', discount_value: 0, tax_rate: 0, _lineGross: 0, _discAmt: 0, _taxable: 0, _cgst: 0, _sgst: 0, _igst: 0, _lineTotal: 0 }, overrides);
        },
        addItem() { this.items.push(this.makeItem()); },
        removeItem(idx) { if (this.items.length <= 1) return; this.items.splice(idx, 1); this.computeGrandTotal(); },

        onCustomerSelected(customer) {
            if (!customer) { this.form.party_id = null; this.form.billing_address = ''; this.form.shipping_address = ''; this.form.place_of_supply = ''; this.customerState = ''; return; }
            this.form.party_id = customer.id;
            this.form.billing_address = customer.billing_address ?? '';
            this.form.shipping_address = customer.shipping_address ?? '';
            this.customerState = customer.state ?? '';
            if (!this.form.place_of_supply && customer.state) this.form.place_of_supply = customer.state;
            if (customer.credit_days > 0 && this.form.proforma_date) {
                const d = new Date(this.form.proforma_date); d.setDate(d.getDate() + parseInt(customer.credit_days));
                this.form.due_date = d.toISOString().split('T')[0];
            }
            const companyState = '{{ optional($company)->state ?? '' }}';
            if (companyState && customer.state) { this.form.is_igst = customer.state.trim().toLowerCase() !== companyState.trim().toLowerCase(); }
        },
        onDateChange() {},

        async searchProductsForItem(idx) {
            const item = this.items[idx];
            item.productLoading = true;
            item.productOpen = true;
            this.productNav[idx] = -1;
            this.$dispatch('product-dropdown-open');
            try {
                const res = await fetch(`/sales/proforma/search-products?q=${encodeURIComponent(item.productQuery ?? '')}`, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': _csrf() } });
                const data = await res.json();
                item.productRecent = data.recent ?? [];
                item.productSuggestions = data.suggestions ?? [];
            } catch (e) { console.error('Product search error', e); } finally { item.productLoading = false; }
        },
        selectProduct(idx, product) {
            const item = this.items[idx];
            item.product_id = product.id; item.productName = product.name; item.productQuery = ''; item.productOpen = false;
            this.productNav[idx] = -1;
            this.$dispatch('product-dropdown-close');
            item.description = item.description || product.description || product.name;
            item.hsn_sac = product.hsn_sac ?? ''; item.unit = product.unit ?? '';
            item.unit_price = product.unit_price ?? 0; item.tax_rate = product.tax_rate ?? 0;
            this.computeLineTotal(idx);
        },
        _productAllItems(idx) {
            const item = this.items[idx];
            return [...(item.productRecent || []), ...(item.productSuggestions || [])];
        },
        productNavDown(idx) {
            const item = this.items[idx];
            if (!item.productOpen) { this.searchProductsForItem(idx); return; }
            const all = this._productAllItems(idx);
            if (all.length === 0) return;
            const cur = this.productNav[idx] ?? -1;
            const next = Math.min(cur + 1, all.length - 1);
            this.productNav[idx] = next;
            this._scrollProductItem(idx, next);
        },
        productNavUp(idx) {
            const cur = this.productNav[idx] ?? 0;
            const prev = Math.max(cur - 1, -1);
            this.productNav[idx] = prev;
            if (prev >= 0) this._scrollProductItem(idx, prev);
        },
        productNavSelect(idx) {
            const item = this.items[idx];
            if (!item.productOpen) return;
            const all = this._productAllItems(idx);
            const ni = this.productNav[idx] ?? -1;
            if (ni >= 0 && ni < all.length) this.selectProduct(idx, all[ni]);
        },
        _scrollProductItem(idx, navIdx) {
            this.$nextTick(() => {
                const rows = this.$el.querySelectorAll('tbody tr');
                if (!rows[idx]) return;
                const dropdown = rows[idx].querySelector('[x-show*="productOpen"]');
                if (!dropdown) return;
                const btns = dropdown.querySelectorAll('button');
                if (btns[navIdx]) btns[navIdx].scrollIntoView({ block: 'nearest' });
            });
        },

        computeLineTotal(idx) {
            const item = this.items[idx]; const qty = parseFloat(item.qty) || 0; const price = parseFloat(item.unit_price) || 0;
            const taxRate = parseFloat(item.tax_rate) || 0; const discVal = parseFloat(item.discount_value) || 0;
            const gross = qty * price; let discAmt = item.discount_type === 'amount' ? Math.min(discVal, gross) : gross * discVal / 100;
            const taxable = gross - discAmt; const half = taxRate / 2;
            item._lineGross = gross; item._discAmt = discAmt; item._taxable = taxable;
            if (this.form.is_igst) { item._igst = taxable * taxRate / 100; item._cgst = 0; item._sgst = 0; }
            else { item._cgst = taxable * half / 100; item._sgst = taxable * half / 100; item._igst = 0; }
            item._lineTotal = taxable + item._cgst + item._sgst + item._igst; this.computeGrandTotal();
        },
        computeGrandTotal() {
            let subtotal = 0, discountItems = 0, taxable = 0, cgst = 0, sgst = 0, igst = 0;
            this.items.forEach(item => { subtotal += item._lineGross || 0; discountItems += item._discAmt || 0; taxable += item._taxable || 0; cgst += item._cgst || 0; sgst += item._sgst || 0; igst += item._igst || 0; });
            const bdVal = parseFloat(this.form.bill_discount_value) || 0; let bdAmt = 0;
            if (bdVal > 0) { bdAmt = this.form.bill_discount_type === 'amount' ? Math.min(bdVal, taxable) : taxable * bdVal / 100; }
            const adjTaxable = taxable - bdAmt; const totalDisc = discountItems + bdAmt;
            let adjCgst = cgst, adjSgst = sgst, adjIgst = igst;
            if (bdAmt > 0 && taxable > 0) { const ratio = adjTaxable / taxable; adjCgst = cgst * ratio; adjSgst = sgst * ratio; adjIgst = igst * ratio; }
            this.totals = { subtotal: this.round2(subtotal), discount: this.round2(totalDisc), taxable: this.round2(adjTaxable), cgst: this.round2(adjCgst), sgst: this.round2(adjSgst), igst: this.round2(adjIgst), grandTotal: this.round2(adjTaxable + adjCgst + adjSgst + adjIgst) };
        },

        async save(andSubmit) {
            this.errorMsg = ''; this.saving = true; this.submitAfterSave = andSubmit;
            if (!this.form.proforma_date) { this.errorMsg = 'Proforma date is required.'; this.saving = false; return; }
            if (!this.form.party_id) { this.errorMsg = 'Please select a customer.'; this.saving = false; return; }
            const hasItems = this.items.some(i => (parseFloat(i.qty) || 0) > 0 && i.description.trim());
            if (!hasItems) { this.errorMsg = 'At least one line item with a description and quantity is required.'; this.saving = false; return; }
            const payload = {
                proforma_date: this.form.proforma_date, due_date: this.form.due_date || null,
                party_id: this.form.party_id, billing_address: this.form.billing_address || '',
                shipping_address: this.form.shipping_address || '', reference_number: this.form.reference_number || '',
                place_of_supply: this.form.place_of_supply || '', is_igst: this.form.is_igst ? 1 : 0,
                notes: this.form.notes || '', terms: this.form.terms || '',
                bill_discount_type: this.form.bill_discount_type, bill_discount_value: parseFloat(this.form.bill_discount_value) || 0,
                narration: this.form.narration || '',
                items: this.items.filter(item => item.description.trim() || item.product_id || (parseFloat(item.qty) > 0 && parseFloat(item.unit_price) > 0)).map(item => ({
                    product_id: item.product_id || null, description: item.description || '', hsn_sac: item.hsn_sac || '',
                    qty: parseFloat(item.qty) || 0, unit: item.unit || '', unit_price: parseFloat(item.unit_price) || 0,
                    discount_type: item.discount_type, discount_value: parseFloat(item.discount_value) || 0, tax_rate: parseFloat(item.tax_rate) || 0,
                })),
            };
            try {
                const isEdit = !!this.proformaId;
                const url = isEdit ? `/sales/proforma/${this.proformaId}` : '/sales/proforma';
                const method = isEdit ? 'PUT' : 'POST';
                const res = await fetch(url, { method, headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': _csrf() }, body: JSON.stringify(payload) });
                const json = await res.json();
                if (!res.ok || !json.success) {
                    if (json.errors) { this.errorMsg = Object.values(json.errors).flat().join(' | '); }
                    else { this.errorMsg = json.message ?? 'Something went wrong.'; }
                    return;
                }
                if (andSubmit && json.id) {
                    const subRes = await fetch(`/sales/proforma/${json.id}/submit`, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': _csrf() } });
                    const subJson = await subRes.json();
                    if (!subJson.success) { this.errorMsg = subJson.message ?? 'Saved but could not submit.'; return; }
                    window.location.href = '/sales/proforma'; return;
                }
                if (!this.proformaId && json.redirect) { window.location.href = json.redirect; }
                else { if (json.id) this.proformaId = json.id; this._showToast('Draft saved successfully.'); }
            } catch (err) { console.error(err); this.errorMsg = 'Network error. Please check your connection.'; } finally { this.saving = false; }
        },

        round2(n) { return Math.round((n + Number.EPSILON) * 100) / 100; },
        fmt(n) { return (parseFloat(n) || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        _showToast(message, type = 'success') {
            const el = document.createElement('div'); const isErr = type === 'error';
            el.style.cssText = `position:fixed;bottom:1.25rem;right:1.25rem;z-index:9999;padding:.6rem 1rem;border-radius:.75rem;font-size:.75rem;font-weight:500;display:flex;align-items:center;gap:.5rem;box-shadow:0 4px 16px rgba(0,0,0,.1);background:${isErr ? '#fef2f2' : '#f0fdf4'};border:1px solid ${isErr ? '#fecaca' : '#bbf7d0'};color:${isErr ? '#991b1b' : '#166534'};`;
            el.innerHTML = `<span>${message}</span>`; document.body.appendChild(el); setTimeout(() => el.remove(), 3000);
        },
    };
}

function highlightMatch(text, query) {
    if (!query || !text) return escHtml(text || '');
    const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return escHtml(text).replace(new RegExp(`(${escaped})`, 'gi'), '<mark style="background:#fecaca;color:#7f1d1d;border-radius:2px;padding:0 1px;">$1</mark>');
}
function escHtml(str) { return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
function _csrf() { return document.querySelector('meta[name="csrf-token"]')?.content ?? ''; }
</script>
@endpush
