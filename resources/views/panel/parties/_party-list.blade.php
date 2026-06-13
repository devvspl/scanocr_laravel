{{--
    Params:
      $type       — 'customer' | 'vendor'
      $dataRoute  — DataTables ajax URL
      $storeRoute — base REST URL  e.g. /master/customers
      $groups     — AccountGroup collection
--}}
@php $label = ucfirst($type); @endphp

{{-- Main card --}}
<div class="bg-white border border-stone-200 overflow-hidden">

    {{-- Toolbar --}}
    <div class="px-4 py-2.5 border-b border-stone-100 flex items-center justify-between gap-2 min-h-[48px]">
        <div class="flex items-center gap-2">
            <select id="filter-status"
                    class="h-7 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50
                           focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
        <button @click="openModal()"
                class="tb-btn tb-btn-add">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            Add {{ $label }}
        </button>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table id="party-table" class="w-full">
            <thead>
                <tr>
                    <th style="width:80px;">Code</th>
                    <th>Name</th>
                    <th style="width:130px;">Phone</th>
                    <th style="width:180px;">Email</th>
                    <th style="width:130px;">Opening Bal.</th>
                    <th style="width:110px;">Created By</th>
                    <th class="dt-center" style="width:80px;">Status</th>
                    <th class="dt-center" style="width:80px;">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    {{-- Empty state --}}
    <div id="empty-state" class="hidden flex-col items-center justify-center py-16 text-center">
        <div class="w-12 h-12 rounded-xl bg-stone-100 flex items-center justify-center mb-3 mx-auto">
            <svg class="w-6 h-6 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <p class="text-sm font-medium text-stone-600">No {{ strtolower($label) }}s yet</p>
        <p class="text-xs text-stone-400 mt-1">Click "Add {{ $label }}" to get started.</p>
    </div>

</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     OFFCANVAS MODAL
══════════════════════════════════════════════════════════════════════════ --}}
<div x-show="modalOpen"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 flex items-start justify-end"
     style="display:none">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeModal()"></div>

    {{-- Panel --}}
    <div class="relative bg-white h-full w-full max-w-2xl shadow-2xl flex flex-col overflow-hidden"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full">

        {{-- Header --}}
        <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between shrink-0">
            <h3 class="text-sm font-semibold text-stone-800"
                x-text="editId ? 'Edit {{ $label }}' : 'Add {{ $label }}'"></h3>
            <button @click="closeModal()" class="act-btn act-edit">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Toast --}}
        <div x-show="toast.show" x-transition
             :class="toast.type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'"
             class="mx-5 mt-4 px-4 py-3 rounded-xl border text-sm font-medium flex items-center gap-2 shrink-0"
             style="display:none">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      x-bind:d="toast.type === 'error'
                        ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
                        : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'"/>
            </svg>
            <span x-text="toast.message"></span>
        </div>

        {{-- Scrollable body --}}
        <div class="flex-1 overflow-y-auto p-5">

            {{-- Tab nav --}}
            <div class="flex gap-1 border-b border-stone-200 mb-5">
                <button @click="tab = 'basic'"
                        :class="tab === 'basic' ? 'border-b-2 border-red-700 text-red-700 font-semibold' : 'text-stone-500 hover:text-stone-700'"
                        class="px-3 py-2 text-xs transition-colors -mb-px">Basic Info</button>
                <button @click="tab = 'address'"
                        :class="tab === 'address' ? 'border-b-2 border-red-700 text-red-700 font-semibold' : 'text-stone-500 hover:text-stone-700'"
                        class="px-3 py-2 text-xs transition-colors -mb-px">Address</button>
                <button @click="tab = 'financial'"
                        :class="tab === 'financial' ? 'border-b-2 border-red-700 text-red-700 font-semibold' : 'text-stone-500 hover:text-stone-700'"
                        class="px-3 py-2 text-xs transition-colors -mb-px">Financial</button>
            </div>

            {{-- ── BASIC INFO ── --}}
            <div x-show="tab === 'basic'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                {{-- Code --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1.5">
                        Code <span class="text-red-600">*</span>
                    </label>
                    <input type="text" x-model="form.code" placeholder="e.g. CUST-001"
                           class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400
                                  focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors"
                           :class="errors.code ? 'border-red-400' : ''">
                    <p x-show="errors.code" x-text="errors.code" class="mt-1 text-xs text-red-600"></p>
                </div>

                {{-- Name --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1.5">
                        Name <span class="text-red-600">*</span>
                    </label>
                    <input type="text" x-model="form.name" placeholder="Full legal name"
                           class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400
                                  focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors"
                           :class="errors.name ? 'border-red-400' : ''">
                    <p x-show="errors.name" x-text="errors.name" class="mt-1 text-xs text-red-600"></p>
                </div>

                {{-- Display Name --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1.5">Display Name</label>
                    <input type="text" x-model="form.display_name" placeholder="Short / trade name"
                           class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400
                                  focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors">
                </div>

                {{-- Email --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1.5">Email</label>
                    <input type="email" x-model="form.email" placeholder="contact@example.com"
                           class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400
                                  focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors"
                           :class="errors.email ? 'border-red-400' : ''">
                    <p x-show="errors.email" x-text="errors.email" class="mt-1 text-xs text-red-600"></p>
                </div>

                {{-- Phone --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1.5">Phone</label>
                    <input type="text" x-model="form.phone" placeholder="+91 98765 43210"
                           class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400
                                  focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors">
                </div>

                {{-- Mobile --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1.5">Mobile</label>
                    <input type="text" x-model="form.mobile" placeholder="+91 98765 43210"
                           class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400
                                  focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors">
                </div>

                {{-- GSTIN --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1.5">GSTIN</label>
                    <input type="text" x-model="form.gstin" placeholder="22AAAAA0000A1Z5"
                           class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400
                                  focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors uppercase">
                </div>

                {{-- PAN --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1.5">PAN</label>
                    <input type="text" x-model="form.pan" placeholder="AAAAA0000A"
                           class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400
                                  focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors uppercase">
                </div>

                {{-- Notes --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-stone-600 mb-1.5">Notes</label>
                    <textarea x-model="form.notes" rows="2" placeholder="Internal notes..."
                              class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400
                                     focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors resize-none"></textarea>
                </div>

                {{-- Active toggle --}}
                <div class="sm:col-span-2 flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="form.is_active" class="sr-only peer">
                        <div class="w-9 h-5 bg-stone-200 peer-focus:ring-2 peer-focus:ring-red-700/20 rounded-full peer
                                    peer-checked:after:translate-x-full peer-checked:after:border-white
                                    after:content-[''] after:absolute after:top-0.5 after:left-[2px]
                                    after:bg-white after:border-stone-300 after:border after:rounded-full
                                    after:h-4 after:w-4 after:transition-all peer-checked:bg-red-700"></div>
                    </label>
                    <span class="text-sm text-stone-600 font-medium">Active</span>
                </div>

            </div>

            {{-- ── ADDRESS ── --}}
            <div x-show="tab === 'address'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-stone-600 mb-1.5">Billing Address</label>
                    <textarea x-model="form.billing_address" rows="2" placeholder="Street, area..."
                              class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400
                                     focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors resize-none"></textarea>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-stone-600 mb-1.5">Shipping Address</label>
                    <textarea x-model="form.shipping_address" rows="2" placeholder="Same as billing or different..."
                              class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400
                                     focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors resize-none"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1.5">City</label>
                    <input type="text" x-model="form.city" placeholder="Mumbai"
                           class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400
                                  focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1.5">State</label>
                    <input type="text" x-model="form.state" placeholder="Maharashtra"
                           class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400
                                  focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1.5">Country</label>
                    <input type="text" x-model="form.country" placeholder="India"
                           class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400
                                  focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1.5">Pincode</label>
                    <input type="text" x-model="form.pincode" placeholder="400001"
                           class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400
                                  focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors">
                </div>

            </div>

            {{-- ── FINANCIAL ── --}}
            <div x-show="tab === 'financial'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1.5">Opening Balance</label>
                    <input type="number" x-model="form.opening_balance" placeholder="0.00" step="0.01"
                           class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400
                                  focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1.5">Balance Type <span class="text-red-600">*</span></label>
                    <select x-model="form.balance_type"
                            class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800
                                   focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors">
                        <option value="debit">Debit</option>
                        <option value="credit">Credit</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1.5">Credit Limit</label>
                    <input type="text" x-model="form.credit_limit" placeholder="e.g. 50000"
                           class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400
                                  focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1.5">Credit Days</label>
                    <input type="number" x-model="form.credit_days" placeholder="30" min="0"
                           class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400
                                  focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-stone-600 mb-1.5">Account Group</label>
                    <select x-model="form.account_group_id"
                            class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800
                                   focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors">
                        <option value="">— None —</option>
                        @foreach($groups as $g)
                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>

            </div>

        </div>

        {{-- Footer --}}
        <div class="px-5 py-4 border-t border-stone-100 flex items-center justify-end gap-2 shrink-0">
            <button @click="closeModal()" class="tb-btn tb-btn-edit">Cancel</button>
            <button @click="save()" :disabled="saving"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-800 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-semibold transition-colors">
                <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span x-text="editId ? 'Save Changes' : 'Add {{ $label }}'"></span>
            </button>
        </div>

    </div>
</div>
