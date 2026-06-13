{{-- Product list with filters --}}
<div class="bg-white border border-stone-200 overflow-hidden">
    <div class="px-4 py-2.5 border-b border-stone-100 flex items-center justify-between gap-2 min-h-[48px]">
        <div class="flex items-center gap-2">
            <select id="filter-group" class="h-7 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10">
                <option value="">All Groups</option>
                @foreach($groups as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach
            </select>
            <select id="filter-unit" class="h-7 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10">
                <option value="">All Units</option>
                @foreach($units as $u)<option value="{{ $u->id }}">{{ $u->name }} ({{ $u->symbol }})</option>@endforeach
            </select>
            <select id="filter-type" class="h-7 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10">
                <option value="">All Types</option>
                <option value="goods">Goods</option>
                <option value="service">Service</option>
            </select>
            <select id="filter-status" class="h-7 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
        <button @click="openModal()" class="tb-btn tb-btn-add">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            Add Product
        </button>
    </div>
    <div class="overflow-x-auto">
        <table id="product-table" class="w-full">
            <thead><tr>
                <th style="width:80px;">Code</th>
                <th>Name</th>
                <th style="width:80px;">Type</th>
                <th style="width:110px;">Sale Price</th>
                <th style="width:110px;">Purchase Price</th>
                <th style="width:80px;">Tax %</th>
                <th style="width:110px;">Created By</th>
                <th class="dt-center" style="width:80px;">Status</th>
                <th class="dt-center" style="width:80px;">Actions</th>
            </tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<div x-show="modalOpen" x-transition class="fixed inset-0 z-50 flex items-start justify-end" style="display:none">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeModal()"></div>
    <div class="relative bg-white h-full w-full max-w-2xl shadow-2xl flex flex-col overflow-hidden" x-transition>
        <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between shrink-0">
            <h3 class="text-sm font-semibold text-stone-800" x-text="editId ? 'Edit Product' : 'Add Product'"></h3>
            <button @click="closeModal()" class="act-btn act-edit"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div x-show="toast.show" x-transition :class="toast.type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'" class="mx-5 mt-4 px-4 py-3 rounded-xl border text-sm font-medium flex items-center gap-2 shrink-0" style="display:none">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" x-bind:d="toast.type === 'error' ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'"/></svg>
            <span x-text="toast.message"></span>
        </div>
        <div class="flex-1 overflow-y-auto p-5">
            <div class="flex gap-1 border-b border-stone-200 mb-5">
                <button @click="tab = 'basic'" :class="tab === 'basic' ? 'border-b-2 border-red-700 text-red-700 font-semibold' : 'text-stone-500 hover:text-stone-700'" class="px-3 py-2 text-xs transition-colors -mb-px">Basic</button>
                <button @click="tab = 'pricing'" :class="tab === 'pricing' ? 'border-b-2 border-red-700 text-red-700 font-semibold' : 'text-stone-500 hover:text-stone-700'" class="px-3 py-2 text-xs transition-colors -mb-px">Pricing</button>
                <button @click="tab = 'inventory'" :class="tab === 'inventory' ? 'border-b-2 border-red-700 text-red-700 font-semibold' : 'text-stone-500 hover:text-stone-700'" class="px-3 py-2 text-xs transition-colors -mb-px">Inventory</button>
            </div>
            <div x-show="tab === 'basic'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="block text-xs font-semibold text-stone-600 mb-1.5">Code <span class="text-red-600">*</span></label><input type="text" x-model="form.code" placeholder="e.g. PROD-001" class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors" :class="errors.code ? 'border-red-400' : ''"><p x-show="errors.code" x-text="errors.code" class="mt-1 text-xs text-red-600"></p></div>
                <div><label class="block text-xs font-semibold text-stone-600 mb-1.5">Name <span class="text-red-600">*</span></label><input type="text" x-model="form.name" placeholder="Product name" class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors" :class="errors.name ? 'border-red-400' : ''"><p x-show="errors.name" x-text="errors.name" class="mt-1 text-xs text-red-600"></p></div>
                <div class="sm:col-span-2"><label class="block text-xs font-semibold text-stone-600 mb-1.5">Description</label><textarea x-model="form.description" rows="2" placeholder="Optional description..." class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors resize-none"></textarea></div>
                <div><label class="block text-xs font-semibold text-stone-600 mb-1.5">Type <span class="text-red-600">*</span></label><select x-model="form.type" class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors"><option value="goods">Goods</option><option value="service">Service</option></select></div>
                <div><label class="block text-xs font-semibold text-stone-600 mb-1.5">Item Group</label><select x-model="form.item_group_id" class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors"><option value="">— None —</option>@foreach($groups as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach</select></div>
                <div><label class="block text-xs font-semibold text-stone-600 mb-1.5">Unit</label><select x-model="form.unit_id" class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors"><option value="">— None —</option>@foreach($units as $u)<option value="{{ $u->id }}">{{ $u->name }} ({{ $u->symbol }})</option>@endforeach</select></div>
                <div><label class="block text-xs font-semibold text-stone-600 mb-1.5">HSN/SAC</label><input type="text" x-model="form.hsn_sac" placeholder="e.g. 1234" class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors"></div>
                <div class="sm:col-span-2 flex items-center gap-3"><label class="relative inline-flex items-center cursor-pointer"><input type="checkbox" x-model="form.is_active" class="sr-only peer"><div class="w-9 h-5 bg-stone-200 peer-focus:ring-2 peer-focus:ring-red-700/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-stone-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-red-700"></div></label><span class="text-sm text-stone-600 font-medium">Active</span></div>
            </div>
            <div x-show="tab === 'pricing'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="block text-xs font-semibold text-stone-600 mb-1.5">Sale Price</label><input type="number" x-model="form.sale_price" placeholder="0.00" step="0.01" class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors"></div>
                <div><label class="block text-xs font-semibold text-stone-600 mb-1.5">Purchase Price</label><input type="number" x-model="form.purchase_price" placeholder="0.00" step="0.01" class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors"></div>
                <div><label class="block text-xs font-semibold text-stone-600 mb-1.5">Tax Rate (%)</label><input type="number" x-model="form.tax_rate" placeholder="0.00" step="0.01" min="0" max="100" class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors"></div>
            </div>
            <div x-show="tab === 'inventory'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="block text-xs font-semibold text-stone-600 mb-1.5">Opening Stock</label><input type="number" x-model="form.opening_stock" placeholder="0.000" step="0.001" class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors"></div>
                <div><label class="block text-xs font-semibold text-stone-600 mb-1.5">Reorder Level</label><input type="number" x-model="form.reorder_level" placeholder="0.000" step="0.001" class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors"></div>
                <div class="sm:col-span-2 flex items-center gap-3"><label class="relative inline-flex items-center cursor-pointer"><input type="checkbox" x-model="form.track_inventory" class="sr-only peer"><div class="w-9 h-5 bg-stone-200 peer-focus:ring-2 peer-focus:ring-red-700/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-stone-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-red-700"></div></label><span class="text-sm text-stone-600 font-medium">Track Inventory</span></div>
            </div>
        </div>
        <div class="px-5 py-4 border-t border-stone-100 flex items-center justify-end gap-2 shrink-0">
            <button @click="closeModal()" class="tb-btn tb-btn-edit">Cancel</button>
            <button @click="save()" :disabled="saving" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-800 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-semibold transition-colors">
                <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span x-text="editId ? 'Save' : 'Add'"></span>
            </button>
        </div>
    </div>
</div>
