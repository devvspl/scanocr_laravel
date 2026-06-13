@extends('layouts.app')

@section('title', isset($delivery) ? 'Edit Delivery Note — ' . $delivery->delivery_number : 'New Delivery Note')
@section('page-title', isset($delivery) ? 'Edit Delivery Note' : 'New Delivery Note')

@section('breadcrumb')
    <span>Sales</span>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <a href="{{ route('sales.delivery') }}" class="hover:text-stone-600 transition-colors">Delivery Notes</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
    <span class="text-stone-600">{{ isset($delivery) ? $delivery->delivery_number : 'New' }}</span>
@endsection

@section('content')
    <div x-data="deliveryForm()" x-init="init()"
        class="flex flex-col bg-white border border-stone-200 rounded-xl overflow-hidden"
        style="height: calc(100vh - 7.5rem);">

        {{-- ── HEADER BAND ──────────────────────────────────────────────────── --}}
        <div class="shrink-0 border-b border-stone-200 bg-stone-50 px-3 py-2">
            <div class="flex items-end gap-2 min-w-0 flex-wrap">

                <div class="shrink-0 w-36">
                    <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-0.5">Delivery #</label>
                    <input type="text" :value="form.delivery_number" readonly autocomplete="off"
                        class="w-full h-7 px-2 text-xs font-mono bg-stone-100 border border-stone-300 rounded-lg text-stone-500 cursor-not-allowed focus:outline-none">
                </div>

                <div class="shrink-0 w-32">
                    <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-0.5">
                        Dispatch Date <span class="text-red-600">*</span>
                    </label>
                    <input type="date" x-model="form.dispatch_date" required autocomplete="off"
                        class="w-full h-7 px-2 text-xs border border-stone-300 rounded-lg text-stone-800 bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors">
                </div>

                <div class="flex-1 min-w-[200px]" x-data="customerSearch()" @keydown.escape="close()">
                    <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-0.5">
                        Customer <span class="text-red-600">*</span>
                    </label>
                    <div class="relative">
                        <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="search()"
                            @click="if(!open) search()"
                            @keydown.arrow-down.prevent="moveDown()"
                            @keydown.arrow-up.prevent="moveUp()"
                            @keydown.enter.prevent="selectHighlighted()"
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
                            x-ref="customerDropdown"
                            style="border:1px solid #e7e5e4;box-shadow:0 4px 16px rgba(0,0,0,.07);">
                            <template x-if="loading">
                                <div class="px-3 py-4 text-center text-xs text-stone-400">Searching…</div>
                            </template>
                            <template x-if="!loading && suggestions.length === 0">
                                <div class="px-3 py-4 text-center text-xs text-stone-400">No customers found</div>
                            </template>
                            <template x-if="!loading && suggestions.length > 0">
                                <div>
                                    <template x-for="(c, ci) in suggestions" :key="c.id">
                                        <button type="button" @click="select(c)" @mouseenter="highlightIdx = ci"
                                            :class="highlightIdx === ci ? 'bg-red-50' : ''"
                                            class="w-full text-left px-3 py-2 hover:bg-red-50 transition-colors" style="border:none;border-bottom:1px solid #f5f5f4;outline:none;background:transparent;">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-xs font-medium text-stone-800" x-text="c.name"></span>
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
                    <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-0.5">Order / PO #</label>
                    <input type="text" x-model="form.order_number" placeholder="PO ref." autocomplete="off"
                        class="w-full h-7 px-2 text-xs border border-stone-300 rounded-lg text-stone-800 bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400">
                </div>

                <div class="shrink-0">
                    <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-0.5">&nbsp;</label>
                    <button type="button" @click="invoicePickerOpen = true; searchInvoices()" class="h-7 px-3 text-[10px] font-semibold text-red-700 border border-red-200 rounded-lg hover:bg-red-50 transition-colors whitespace-nowrap">
                        Pick from Invoice
                    </button>
                </div>
            </div>
        </div>

        {{-- ── RECEIVER & TRANSPORT DETAILS ────────────────────────────────────── --}}
        <div class="shrink-0 border-b border-stone-200 bg-white px-3 py-2">
            <div class="grid grid-cols-2 gap-4">
                {{-- Receiver --}}
                <div>
                    <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-1.5">Receiver / Delivery Details</p>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <input type="text" x-model="form.receiver_name" placeholder="Receiver name"
                                class="w-full h-7 px-2 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800">
                        </div>
                        <div>
                            <input type="text" x-model="form.receiver_phone" placeholder="Receiver phone"
                                class="w-full h-7 px-2 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800">
                        </div>
                    </div>
                    <textarea x-model="form.delivery_address" rows="2" placeholder="Delivery address…"
                        class="w-full mt-1.5 px-2 py-1 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-700 resize-none"></textarea>
                </div>
                {{-- Transport --}}
                <div>
                    <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-1.5">Transport / Carrier Details <span class="text-red-600">*</span></p>
                    {{-- Row 1: Mode + Transporter (server search like customer) --}}
                    <div class="grid grid-cols-3 gap-2">
                        <select x-model="form.transport_mode" @change="onModeChange()"
                            class="h-7 px-2 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors text-stone-800">
                            <option value="">— Select Mode —</option>
                            <option value="Road">Road</option>
                            <option value="Rail">Rail</option>
                            <option value="Air">Air</option>
                            <option value="Sea">Sea</option>
                            <option value="Courier">Courier</option>
                            <option value="Hand Delivery">Hand Delivery</option>
                        </select>
                        <div class="col-span-2" x-data="transporterSearch()" @keydown.escape="close()">
                            <div class="relative">
                                <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="search()"
                                    @click="if(!open) search()"
                                    @keydown.arrow-down.prevent="moveDown()"
                                    @keydown.arrow-up.prevent="moveUp()"
                                    @keydown.enter.prevent="selectHighlighted()"
                                    :placeholder="selectedName || 'Search transporter / vendor…'"
                                    :class="selectedName && !query ? 'text-stone-800 font-medium' : 'text-stone-800'"
                                    autocomplete="new-password" spellcheck="false"
                                    class="w-full h-7 px-2 pr-6 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400">
                                <button x-show="selectedName" @click="clearTransporter()" type="button"
                                    class="absolute right-1.5 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                                <div x-show="open" x-cloak @click.outside="close()"
                                    class="absolute top-full left-0 right-0 mt-1 bg-white rounded-xl z-50 max-h-56 overflow-y-auto"
                                    x-ref="transporterDropdown"
                                    style="border:1px solid #e7e5e4;box-shadow:0 4px 16px rgba(0,0,0,.07);">
                                    <template x-if="loading">
                                        <div class="px-3 py-4 text-center text-xs text-stone-400">Searching…</div>
                                    </template>
                                    <template x-if="!loading && suggestions.length === 0">
                                        <div class="px-3 py-4 text-center text-xs text-stone-400">No transporters found</div>
                                    </template>
                                    <template x-if="!loading && suggestions.length > 0">
                                        <div>
                                            <template x-for="(v, vi) in suggestions" :key="v.id">
                                                <button type="button" @click="select(v)" @mouseenter="highlightIdx = vi"
                                                    :class="highlightIdx === vi ? 'bg-red-50' : ''"
                                                    class="w-full text-left px-3 py-2 hover:bg-red-50 transition-colors" style="border:none;border-bottom:1px solid #f5f5f4;outline:none;background:transparent;">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <span class="text-xs font-medium text-stone-800" x-text="v.name"></span>
                                                        <span class="text-[10px] text-stone-400 shrink-0" x-text="v.phone"></span>
                                                    </div>
                                                    <div class="text-[10px] text-stone-400 mt-0.5" x-text="[v.city, v.state].filter(Boolean).join(', ')"></div>
                                                </button>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Row 2: Conditional fields based on mode --}}
                    <div class="mt-1.5" x-show="form.transport_mode" x-cloak>
                        {{-- Road: Vehicle No, Driver, Driver Phone --}}
                        <div x-show="form.transport_mode === 'Road'" class="grid grid-cols-3 gap-2">
                            <input type="text" x-model="form.vehicle_number" placeholder="Vehicle No. (e.g. MH12AB1234)"
                                class="h-7 px-2 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800">
                            <input type="text" x-model="form.driver_name" placeholder="Driver name"
                                class="h-7 px-2 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800">
                            <input type="text" x-model="form.driver_phone" placeholder="Driver phone"
                                class="h-7 px-2 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800">
                        </div>

                        {{-- Rail: Train/Wagon No, Tracking --}}
                        <div x-show="form.transport_mode === 'Rail'" class="grid grid-cols-2 gap-2">
                            <input type="text" x-model="form.vehicle_number" placeholder="Train / Wagon No."
                                class="h-7 px-2 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800">
                            <input type="text" x-model="form.tracking_number" placeholder="RR No. / Tracking"
                                class="h-7 px-2 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800">
                        </div>

                        {{-- Air: Flight/AWB --}}
                        <div x-show="form.transport_mode === 'Air'" class="grid grid-cols-2 gap-2">
                            <input type="text" x-model="form.vehicle_number" placeholder="Flight No."
                                class="h-7 px-2 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800">
                            <input type="text" x-model="form.tracking_number" placeholder="AWB No."
                                class="h-7 px-2 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800">
                        </div>

                        {{-- Sea: Vessel/BL --}}
                        <div x-show="form.transport_mode === 'Sea'" class="grid grid-cols-2 gap-2">
                            <input type="text" x-model="form.vehicle_number" placeholder="Vessel / Container No."
                                class="h-7 px-2 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800">
                            <input type="text" x-model="form.tracking_number" placeholder="B/L No."
                                class="h-7 px-2 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800">
                        </div>

                        {{-- Courier: Docket/Tracking --}}
                        <div x-show="form.transport_mode === 'Courier'" class="grid grid-cols-2 gap-2">
                            <input type="text" x-model="form.tracking_number" placeholder="Docket / Tracking No."
                                class="h-7 px-2 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800">
                            <input type="text" x-model="form.vehicle_number" placeholder="Courier ref. (optional)"
                                class="h-7 px-2 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800">
                        </div>

                        {{-- Hand Delivery: Person name/phone --}}
                        <div x-show="form.transport_mode === 'Hand Delivery'" class="grid grid-cols-2 gap-2">
                            <input type="text" x-model="form.driver_name" placeholder="Delivered by (person name)"
                                class="h-7 px-2 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800">
                            <input type="text" x-model="form.driver_phone" placeholder="Phone"
                                class="h-7 px-2 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800">
                        </div>
                    </div>

                    {{-- Row 3: LR (Road only) + Packages & Weight --}}
                    <div class="grid grid-cols-3 gap-2 mt-1.5">
                        <input type="text" x-model="form.tracking_number" x-show="form.transport_mode === 'Road'" placeholder="LR / GR No."
                            class="h-7 px-2 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800">
                        <div x-show="form.transport_mode !== 'Road'"></div>
                        <input type="number" x-model="form.total_packages" placeholder="No. of Packages" min="0"
                            class="h-7 px-2 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800">
                        <input type="text" x-model="form.total_weight" placeholder="Total Weight (e.g. 25 Kg)"
                            class="h-7 px-2 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800">
                    </div>
                </div>
            </div>
        </div>

        {{-- ── LINE ITEMS (scrollable middle) ──────────────────────────────────── --}}
        <div class="flex-1 overflow-y-auto overflow-x-hidden min-h-0">
            <table class="w-full text-xs border-collapse" style="table-layout:fixed;">
                <colgroup>
                    <col style="width:32px;"><col style="width:32%;"><col style="width:90px;">
                    <col style="width:70px;"><col style="width:55px;"><col style="width:90px;">
                    <col style="width:22%;"><col style="width:28px;">
                </colgroup>
                <thead class="sticky top-0 z-10 bg-stone-100 border-b border-stone-200">
                    <tr>
                        <th class="px-2 py-2 text-left text-[10px] font-bold text-stone-600 uppercase tracking-wide">Sr</th>
                        <th class="px-2 py-2 text-left text-[10px] font-bold text-stone-600 uppercase tracking-wide">Item Description</th>
                        <th class="px-2 py-2 text-left text-[10px] font-bold text-stone-600 uppercase tracking-wide">Product Code</th>
                        <th class="px-2 py-2 text-center text-[10px] font-bold text-stone-600 uppercase tracking-wide">Qty</th>
                        <th class="px-2 py-2 text-center text-[10px] font-bold text-stone-600 uppercase tracking-wide">Unit</th>
                        <th class="px-2 py-2 text-left text-[10px] font-bold text-stone-600 uppercase tracking-wide">Weight</th>
                        <th class="px-2 py-2 text-left text-[10px] font-bold text-stone-600 uppercase tracking-wide">Remarks</th>
                        <th class="py-2 text-center"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, idx) in items" :key="idx">
                        <tr class="border-b border-stone-100 hover:bg-red-50/30 group align-top transition-colors">
                            <td class="px-2 pt-3 text-xs text-stone-500 font-bold" x-text="idx + 1"></td>
                            <td class="px-2 py-2" style="overflow:visible;">
                                <div class="relative" @keydown.escape="item.productOpen = false">
                                    <input type="text" x-model="item.productQuery"
                                        @input.debounce.300ms="searchProductsForItem(idx)"
                                        @focus="if(!item.productOpen) searchProductsForItem(idx)"
                                        :placeholder="item.description || 'Search product…'"
                                        :class="item.description && !item.productQuery ? 'font-semibold text-stone-800' : 'text-stone-800'"
                                        autocomplete="new-password" spellcheck="false"
                                        class="w-full h-7 px-2.5 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400">
                                    <div x-show="item.productOpen" x-cloak @click.outside="item.productOpen = false"
                                        class="absolute top-full left-0 w-80 mt-1 bg-white rounded-xl z-40 max-h-52 overflow-y-auto"
                                        style="border:1px solid #e7e5e4;box-shadow:0 4px 16px rgba(0,0,0,.07);">
                                        <template x-if="item.productLoading">
                                            <div class="px-3 py-3 text-center text-xs text-stone-400">Searching…</div>
                                        </template>
                                        <template x-if="!item.productLoading && item.productSuggestions.length === 0">
                                            <div class="px-3 py-3 text-center text-xs text-stone-400">No products found</div>
                                        </template>
                                        <template x-if="!item.productLoading && item.productSuggestions.length > 0">
                                            <div>
                                                <template x-for="p in item.productSuggestions" :key="p.id">
                                                    <button type="button" @click="selectProduct(idx, p)" class="w-full text-left px-3 py-2 hover:bg-red-50 transition-colors" style="border:none;border-bottom:1px solid #f5f5f4;outline:none;background:transparent;">
                                                        <div class="flex items-center justify-between gap-2">
                                                            <span class="text-xs font-medium text-stone-800" x-text="p.name"></span>
                                                            <span class="text-[10px] text-stone-400 font-mono shrink-0" x-text="p.code"></span>
                                                        </div>
                                                        <div class="text-[10px] text-stone-400" x-text="'Unit: ' + (p.unit || '—')"></div>
                                                    </button>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                <input type="text" x-model="item.description" placeholder="Item description…"
                                    class="w-full h-6 mt-1 px-2.5 text-[11px] border border-stone-100 rounded-md bg-stone-50 focus:bg-white focus:border-stone-300 focus:outline-none transition-colors placeholder-stone-300 text-stone-700">
                            </td>
                            <td class="px-2 py-2"><input type="text" x-model="item.product_code" placeholder="Code" class="w-full h-7 px-2 text-xs font-mono border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800"></td>
                            <td class="px-2 py-2"><input type="number" x-model="item.qty" min="0.001" step="0.001" placeholder="1" class="w-full h-7 px-2 text-xs text-center font-semibold border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800"></td>
                            <td class="px-2 py-2"><input type="text" x-model="item.unit" placeholder="pcs" class="w-full h-7 px-2 text-xs text-center border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800"></td>
                            <td class="px-2 py-2"><input type="text" x-model="item.weight" placeholder="e.g. 5 Kg" class="w-full h-7 px-2 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800"></td>
                            <td class="px-2 py-2"><input type="text" x-model="item.remarks" placeholder="Remarks" class="w-full h-7 px-2 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-800"></td>
                            <td class="px-1 py-2 text-center">
                                <button type="button" @click="removeItem(idx)" :disabled="items.length <= 1"
                                    class="w-5 h-5 flex items-center justify-center rounded-full transition-colors hover:bg-red-100"
                                    :style="items.length > 1 ? 'color:#ef4444;cursor:pointer;' : 'color:#e7e5e4;cursor:not-allowed;'">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <div class="px-3 py-2.5 border-t border-stone-100">
                <button type="button" @click="addItem()" class="inline-flex items-center gap-1.5 text-xs font-semibold text-red-700 hover:text-red-800 hover:bg-red-50 px-3 py-1.5 rounded-lg transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    Add Item
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
                            <textarea x-model="form.notes" rows="2" placeholder="Notes visible on delivery note…" class="w-full px-2 py-1 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-700 resize-none"></textarea>
                        </div>
                        <div class="flex-1">
                            <label class="block text-[10px] font-semibold text-stone-500 uppercase tracking-wide mb-0.5">Narration (Internal)</label>
                            <textarea x-model="form.narration" rows="2" placeholder="Internal remarks…" class="w-full px-2 py-1 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 transition-colors placeholder-stone-400 text-stone-700 resize-none"></textarea>
                        </div>
                    </div>
                </div>

                <div class="px-4 py-2 border-none min-w-[180px] flex flex-col justify-center gap-0.5">
                    <div class="flex items-center justify-between gap-4"><span class="text-[10px] text-stone-500">Total Items</span><span class="text-xs font-semibold text-stone-800" x-text="items.length"></span></div>
                    <div class="flex items-center justify-between gap-4"><span class="text-[10px] text-stone-500">Total Qty</span><span class="text-xs font-semibold text-stone-800" x-text="totalQty()"></span></div>
                    <div class="flex items-center justify-between gap-4" x-show="form.total_packages"><span class="text-[10px] text-stone-500">Packages</span><span class="text-xs font-semibold text-stone-800" x-text="form.total_packages"></span></div>
                    <div class="flex items-center justify-between gap-4" x-show="form.total_weight"><span class="text-[10px] text-stone-500">Weight</span><span class="text-xs font-semibold text-stone-800" x-text="form.total_weight"></span></div>
                </div>

                <div class="px-3 py-2 flex flex-col justify-center gap-1.5 min-w-[160px]">
                    <a href="{{ route('sales.delivery') }}" class="w-full h-7 flex items-center justify-center gap-1.5 text-xs font-semibold text-stone-600 border border-stone-200 rounded-lg hover:bg-stone-50 transition-colors px-3">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Cancel
                    </a>
                    <button type="button" @click="save(false)" :disabled="saving" class="w-full h-7 flex items-center justify-center gap-1.5 text-xs font-semibold text-stone-700 border border-stone-200 rounded-lg hover:bg-stone-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed px-3">
                        <span x-text="saving && !submitAfterSave ? 'Saving…' : 'Save Draft'"></span>
                    </button>
                    <button type="button" @click="save(true)" :disabled="saving" class="w-full h-7 flex items-center justify-center gap-1.5 text-xs font-semibold text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed px-3" style="background:#7f1d1d;">
                        <span x-text="saving && submitAfterSave ? 'Submitting…' : 'Create & Dispatch'"></span>
                    </button>
                </div>
            </div>

            <div x-show="errorMsg" x-cloak class="px-4 py-2 bg-red-50 border-t border-red-200 flex items-center gap-2">
                <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-xs text-red-700 flex-1" x-text="errorMsg"></span>
                <button @click="errorMsg = ''" class="text-red-400 hover:text-red-600"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
        </div>

        {{-- Invoice Picker Modal --}}
        <div x-show="invoicePickerOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="invoicePickerOpen=false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg" x-transition>
                <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-stone-800">Pick from Invoice / Proforma</h3>
                    <button @click="invoicePickerOpen=false" class="text-stone-400 hover:text-stone-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <div class="px-5 py-3">
                    <input type="text" x-model="invoiceSearchQuery" @input.debounce.300ms="searchInvoices()" placeholder="Search by invoice number or customer…" class="w-full h-8 px-3 text-xs border border-stone-200 rounded-lg bg-white focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 placeholder-stone-400 text-stone-800">
                </div>
                <div class="px-5 pb-4 max-h-64 overflow-y-auto">
                    <template x-if="invoiceSearchLoading"><div class="py-6 text-center text-xs text-stone-400">Searching…</div></template>
                    <template x-if="!invoiceSearchLoading && invoiceResults.length === 0"><div class="py-6 text-center text-xs text-stone-400">No approved invoices found</div></template>
                    <template x-if="!invoiceSearchLoading && invoiceResults.length > 0"><div class="space-y-1">
                        <template x-for="inv in invoiceResults" :key="inv.type+'-'+inv.id">
                            <button type="button" @click="pickInvoice(inv)" class="w-full text-left px-3 py-2.5 rounded-lg hover:bg-red-50 transition-colors" style="border:none;outline:none;background:transparent;">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase" :class="inv.type==='invoice'?'bg-blue-50 text-blue-700':'bg-purple-50 text-purple-700'" x-text="inv.type==='invoice'?'INV':'PRO'"></span>
                                        <span class="text-xs font-mono font-semibold text-stone-800" x-text="inv.number"></span>
                                    </div>
                                    <span class="text-[10px] text-stone-400" x-text="inv.date"></span>
                                </div>
                                <div class="flex items-center justify-between mt-1">
                                    <span class="text-[11px] text-stone-600" x-text="inv.party_name"></span>
                                    <span class="text-[11px] font-semibold text-stone-700" x-text="'₹ '+inv.grand_total"></span>
                                </div>
                                <div class="text-[10px] text-stone-400 mt-0.5" x-text="inv.items.length + ' item(s)'"></div>
                            </button>
                        </template>
                    </div></template>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function customerSearch() {
    return {
        query: '', selectedName: '', open: false, loading: false, suggestions: [], highlightIdx: -1,
        setCustomer(name) { this.selectedName = name; this.query = ''; },
        async search() {
            this.open = true; this.loading = true; this.highlightIdx = -1;
            try {
                const res = await fetch(`/sales/delivery/search-customers?q=${encodeURIComponent(this.query)}`, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': _csrf() } });
                const data = await res.json();
                this.suggestions = data.suggestions ?? [];
            } catch (e) { console.error('Customer search error', e); } finally { this.loading = false; }
        },
        select(customer) { this.selectedName = customer.name; this.query = ''; this.open = false; this.highlightIdx = -1; this.$dispatch('customer-selected', customer); },
        clearCustomer() { this.selectedName = ''; this.query = ''; this.suggestions = []; this.highlightIdx = -1; this.$dispatch('customer-selected', null); },
        close() { this.open = false; this.highlightIdx = -1; },
        moveDown() { if (!this.open || this.suggestions.length === 0) return; this.highlightIdx = (this.highlightIdx + 1) % this.suggestions.length; this.scrollToHighlighted('customerDropdown'); },
        moveUp() { if (!this.open || this.suggestions.length === 0) return; this.highlightIdx = this.highlightIdx <= 0 ? this.suggestions.length - 1 : this.highlightIdx - 1; this.scrollToHighlighted('customerDropdown'); },
        selectHighlighted() { if (this.highlightIdx >= 0 && this.highlightIdx < this.suggestions.length) { this.select(this.suggestions[this.highlightIdx]); } },
        scrollToHighlighted(refName) { this.$nextTick(() => { const dd = this.$refs[refName]; if (dd) { const items = dd.querySelectorAll('button'); if (items[this.highlightIdx]) { items[this.highlightIdx].scrollIntoView({ block: 'nearest' }); } } }); },
    };
}

function transporterSearch() {
    return {
        query: '', selectedName: '', open: false, loading: false, suggestions: [], highlightIdx: -1,
        setTransporter(name) { this.selectedName = name; this.query = ''; },
        async search() {
            this.open = true; this.loading = true; this.highlightIdx = -1;
            try {
                const res = await fetch(`/sales/delivery/search-transporters?q=${encodeURIComponent(this.query)}`, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': _csrf() } });
                const data = await res.json();
                this.suggestions = data.suggestions ?? [];
            } catch (e) { console.error('Transporter search error', e); } finally { this.loading = false; }
        },
        select(vendor) {
            this.selectedName = vendor.name; this.query = ''; this.open = false; this.highlightIdx = -1;
            this.$dispatch('transporter-selected', vendor);
        },
        clearTransporter() { this.selectedName = ''; this.query = ''; this.suggestions = []; this.highlightIdx = -1; this.$dispatch('transporter-selected', null); },
        close() { this.open = false; this.highlightIdx = -1; },
        moveDown() { if (!this.open || this.suggestions.length === 0) return; this.highlightIdx = (this.highlightIdx + 1) % this.suggestions.length; this.scrollToHighlighted('transporterDropdown'); },
        moveUp() { if (!this.open || this.suggestions.length === 0) return; this.highlightIdx = this.highlightIdx <= 0 ? this.suggestions.length - 1 : this.highlightIdx - 1; this.scrollToHighlighted('transporterDropdown'); },
        selectHighlighted() { if (this.highlightIdx >= 0 && this.highlightIdx < this.suggestions.length) { this.select(this.suggestions[this.highlightIdx]); } },
        scrollToHighlighted(refName) { this.$nextTick(() => { const dd = this.$refs[refName]; if (dd) { const items = dd.querySelectorAll('button'); if (items[this.highlightIdx]) { items[this.highlightIdx].scrollIntoView({ block: 'nearest' }); } } }); },
    };
}

function deliveryForm() {
    return {
        deliveryId: {{ isset($delivery) ? $delivery->id : 'null' }},
        saving: false, submitAfterSave: false, errorMsg: '',
        invoicePickerOpen: false, invoiceSearchQuery: '', invoiceSearchLoading: false, invoiceResults: [],

        form: {
            delivery_number: '{{ $nextNumber ?? '' }}',
            dispatch_date: '{{ isset($delivery) ? $delivery->dispatch_date->format("Y-m-d") : now()->format("Y-m-d") }}',
            party_id: {{ isset($delivery) ? $delivery->party_id : 'null' }},
            receiver_name: `{!! isset($delivery) ? addslashes($delivery->receiver_name ?? '') : '' !!}`,
            receiver_phone: '{{ isset($delivery) ? $delivery->receiver_phone ?? '' : '' }}',
            delivery_address: `{!! isset($delivery) ? addslashes($delivery->delivery_address ?? '') : '' !!}`,
            order_number: '{{ isset($delivery) ? $delivery->order_number ?? '' : '' }}',
            transport_mode: '{{ isset($delivery) ? $delivery->transport_mode ?? '' : '' }}',
            transporter_name: `{!! isset($delivery) ? addslashes($delivery->transporter_name ?? '') : '' !!}`,
            vehicle_number: '{{ isset($delivery) ? $delivery->vehicle_number ?? '' : '' }}',
            driver_name: `{!! isset($delivery) ? addslashes($delivery->driver_name ?? '') : '' !!}`,
            driver_phone: '{{ isset($delivery) ? $delivery->driver_phone ?? '' : '' }}',
            tracking_number: '{{ isset($delivery) ? $delivery->tracking_number ?? '' : '' }}',
            total_packages: {{ isset($delivery) && $delivery->total_packages ? $delivery->total_packages : 'null' }},
            total_weight: '{{ isset($delivery) ? $delivery->total_weight ?? '' : '' }}',
            notes: `{!! isset($delivery) ? addslashes($delivery->notes ?? '') : '' !!}`,
            narration: `{!! isset($delivery) ? addslashes($delivery->narration ?? '') : '' !!}`,
        },

        items: (function() {
            const list = [];
            @if (isset($delivery) && $delivery->items->count())
                @foreach ($delivery->items as $item)
                list.push({
                    product_id: {{ $item->product_id ?? 'null' }},
                    productQuery: '', productOpen: false, productLoading: false, productSuggestions: [],
                    description: `{!! addslashes($item->description ?? '') !!}`,
                    product_code: '{{ $item->product_code ?? '' }}',
                    hsn_sac: '{{ $item->hsn_sac ?? '' }}',
                    qty: {{ $item->qty }},
                    unit: '{{ $item->unit ?? '' }}',
                    weight: '{{ $item->weight ?? '' }}',
                    remarks: `{!! addslashes($item->remarks ?? '') !!}`,
                });
                @endforeach
            @else
                list.push({ product_id: null, productQuery: '', productOpen: false, productLoading: false, productSuggestions: [], description: '', product_code: '', hsn_sac: '', qty: 1, unit: '', weight: '', remarks: '' });
            @endif
            return list;
        })(),

        init() {
            this.$el.addEventListener('customer-selected', (e) => { this.onCustomerSelected(e.detail); });
            this.$el.addEventListener('transporter-selected', (e) => { this.onTransporterSelected(e.detail); });
            @if (isset($delivery) && $delivery->party)
                this.$nextTick(() => {
                    const csEl = this.$el.querySelector('[x-data*="customerSearch"]');
                    if (csEl && csEl._x_dataStack) { csEl._x_dataStack[0].setCustomer('{{ addslashes($delivery->party->display_name ?? $delivery->party->name) }}'); }
                });
            @endif
            @if (isset($delivery) && $delivery->transporter_name)
                this.$nextTick(() => {
                    const tsEl = this.$el.querySelector('[x-data*="transporterSearch"]');
                    if (tsEl && tsEl._x_dataStack) { tsEl._x_dataStack[0].setTransporter(`{!! addslashes($delivery->transporter_name) !!}`); }
                });
            @endif
        },

        makeItem() {
            return { product_id: null, productQuery: '', productOpen: false, productLoading: false, productSuggestions: [], description: '', product_code: '', hsn_sac: '', qty: 1, unit: '', weight: '', remarks: '' };
        },
        addItem() { this.items.push(this.makeItem()); },
        removeItem(idx) { if (this.items.length <= 1) return; this.items.splice(idx, 1); },

        totalQty() {
            return this.items.reduce((sum, i) => sum + (parseFloat(i.qty) || 0), 0);
        },

        onCustomerSelected(customer) {
            if (!customer) { this.form.party_id = null; this.form.delivery_address = ''; this.form.receiver_phone = ''; return; }
            this.form.party_id = customer.id;
            this.form.delivery_address = customer.shipping_address ?? '';
            this.form.receiver_phone = customer.phone ?? '';
        },

        onTransporterSelected(vendor) {
            if (!vendor) { this.form.transporter_name = ''; return; }
            this.form.transporter_name = vendor.name;
        },

        onModeChange() {
            // Clear mode-specific fields when mode changes
            this.form.vehicle_number = '';
            this.form.driver_name = '';
            this.form.driver_phone = '';
            this.form.tracking_number = '';
        },

        async searchInvoices() {
            this.invoiceSearchLoading = true;
            try {
                const res = await fetch(`/sales/delivery/search-invoices?q=${encodeURIComponent(this.invoiceSearchQuery)}`, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': _csrf() } });
                const data = await res.json();
                this.invoiceResults = data.results ?? [];
            } catch (e) { console.error(e); } finally { this.invoiceSearchLoading = false; }
        },

        pickInvoice(inv) {
            // Fill customer
            this.form.party_id = inv.party_id;
            this.form.delivery_address = inv.party_address || '';
            this.form.receiver_phone = inv.party_phone || '';
            this.form.order_number = inv.number;

            // Set customer name in the search component
            const csEl = this.$el.querySelector('[x-data*="customerSearch"]');
            if (csEl && csEl._x_dataStack) { csEl._x_dataStack[0].setCustomer(inv.party_name); }

            // Fill items from invoice
            this.items = inv.items.map(i => ({
                product_id: i.product_id || null,
                productQuery: '', productOpen: false, productLoading: false, productSuggestions: [],
                description: i.description || '',
                product_code: i.product_code || '',
                hsn_sac: i.hsn_sac || '',
                qty: i.qty || 1,
                unit: i.unit || '',
                weight: '',
                remarks: '',
            }));

            if (this.items.length === 0) this.items = [this.makeItem()];
            this.invoicePickerOpen = false;
        },

        async searchProductsForItem(idx) {
            const item = this.items[idx]; item.productLoading = true; item.productOpen = true;
            try {
                const res = await fetch(`/sales/delivery/search-products?q=${encodeURIComponent(item.productQuery ?? '')}`, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': _csrf() } });
                const data = await res.json(); item.productSuggestions = data.suggestions ?? [];
            } catch (e) { console.error('Product search error', e); } finally { item.productLoading = false; }
        },
        selectProduct(idx, product) {
            const item = this.items[idx];
            item.product_id = product.id;
            item.productQuery = '';
            item.productOpen = false;
            item.description = item.description || product.description || product.name;
            item.product_code = product.code ?? '';
            item.hsn_sac = product.hsn_sac ?? '';
            item.unit = product.unit ?? '';
        },

        async save(andSubmit) {
            this.errorMsg = ''; this.saving = true; this.submitAfterSave = andSubmit;
            if (!this.form.dispatch_date) { this.errorMsg = 'Dispatch date is required.'; this.saving = false; return; }
            if (!this.form.party_id) { this.errorMsg = 'Please select a customer.'; this.saving = false; return; }
            if (!this.form.delivery_address.trim()) { this.errorMsg = 'Delivery address is required.'; this.saving = false; return; }
            if (!this.form.transport_mode) { this.errorMsg = 'Transport mode is required.'; this.saving = false; return; }
            if (!this.form.transporter_name.trim()) { this.errorMsg = 'Please select or enter a transporter.'; this.saving = false; return; }
            const hasItems = this.items.some(i => (parseFloat(i.qty) || 0) > 0 && i.description.trim());
            if (!hasItems) { this.errorMsg = 'At least one item with description and quantity is required.'; this.saving = false; return; }

            const payload = {
                dispatch_date: this.form.dispatch_date,
                party_id: this.form.party_id,
                receiver_name: this.form.receiver_name || '',
                receiver_phone: this.form.receiver_phone || '',
                delivery_address: this.form.delivery_address || '',
                order_number: this.form.order_number || '',
                transport_mode: this.form.transport_mode || '',
                transporter_name: this.form.transporter_name || '',
                vehicle_number: this.form.vehicle_number || '',
                driver_name: this.form.driver_name || '',
                driver_phone: this.form.driver_phone || '',
                tracking_number: this.form.tracking_number || '',
                total_packages: parseInt(this.form.total_packages) || null,
                total_weight: this.form.total_weight || '',
                notes: this.form.notes || '',
                narration: this.form.narration || '',
                items: this.items.filter(i => i.description.trim() || i.product_id).map(i => ({
                    product_id: i.product_id || null,
                    description: i.description || '',
                    product_code: i.product_code || '',
                    hsn_sac: i.hsn_sac || '',
                    qty: parseFloat(i.qty) || 1,
                    unit: i.unit || '',
                    weight: i.weight || '',
                    remarks: i.remarks || '',
                })),
            };

            try {
                const isEdit = !!this.deliveryId;
                const url = isEdit ? `/sales/delivery/${this.deliveryId}` : '/sales/delivery';
                const method = isEdit ? 'PUT' : 'POST';
                const res = await fetch(url, { method, headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': _csrf() }, body: JSON.stringify(payload) });
                const json = await res.json();
                if (!res.ok || !json.success) {
                    this.errorMsg = json.errors ? Object.values(json.errors).flat().join(' | ') : (json.message ?? 'Something went wrong.');
                    return;
                }
                if (andSubmit && json.id) {
                    const subRes = await fetch(`/sales/delivery/${json.id}/submit`, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': _csrf() } });
                    const subJson = await subRes.json();
                    if (!subJson.success) { this.errorMsg = subJson.message ?? 'Saved but could not dispatch.'; return; }
                    window.location.href = '/sales/delivery'; return;
                }
                if (!this.deliveryId && json.redirect) { window.location.href = json.redirect; }
                else { if (json.id) this.deliveryId = json.id; this._showToast('Draft saved.'); }
            } catch (err) { console.error(err); this.errorMsg = 'Network error.'; } finally { this.saving = false; }
        },

        _showToast(message, type = 'success') {
            const el = document.createElement('div'); const isErr = type === 'error';
            el.style.cssText = `position:fixed;bottom:1.25rem;right:1.25rem;z-index:9999;padding:.6rem 1rem;border-radius:.75rem;font-size:.75rem;font-weight:500;display:flex;align-items:center;gap:.5rem;box-shadow:0 4px 16px rgba(0,0,0,.1);background:${isErr ? '#fef2f2' : '#f0fdf4'};border:1px solid ${isErr ? '#fecaca' : '#bbf7d0'};color:${isErr ? '#991b1b' : '#166534'};`;
            el.innerHTML = `<span>${message}</span>`; document.body.appendChild(el); setTimeout(() => el.remove(), 3000);
        },
    };
}

function _csrf() { return document.querySelector('meta[name="csrf-token"]')?.content ?? ''; }
</script>
@endpush
