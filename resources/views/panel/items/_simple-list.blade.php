{{-- Simple list for Item Groups & Units --}}
<div class="bg-white border border-stone-200 overflow-hidden">
    <div class="px-4 py-2.5 border-b border-stone-100 flex items-center justify-between gap-2 min-h-[48px]">
        <div class="flex items-center
            <select id="filter-status" class="h-7 px-2.5 text-[11px] border border-stone-200 rounded-md text-stone-600 bg-stone-50 focus:outline-none focus:border-red-700 focus:ring-1 focus:ring-red-700/10 w-36">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </s{ $title ?? 'Item' }}
        </button>
    </div>
    <div class="overflow-x-auto">
        <table id="data-table" class="w-full">
            <thead><tr>
                @foreach($columns as $col)
                <th @if($col['center'] ?? false) class="dt-center" @endif>{{ $col['title'] }}</th>
                @endforeach
                <th class="dt-center" style="width:80px;">Actions</th>
            </tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<div x-show="modalOpen" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden" x-transition>
        <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-stone-800" x-text="editId ? 'Edit' : 'Add'"></h3>
            <button @click="closeModal()" class="act-btn act-edit"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div class="p-5">
            <div x-show="toast.show" x-transition :class="toast.type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'" class="mb-4 px-4 py-3 rounded-xl border text-sm font-medium flex items-center gap-2" style="display:none">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" x-bind:d="toast.type === 'error' ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'"/></svg>
                <span x-text="toast.message"></span>
            </div>
            <div class="space-y-4">
                @foreach($fields as $field)
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1.5">{{ $field['label'] }} @if($field['required'] ?? false)<span class="text-red-600">*</span>@endif</label>
                    @if($field['type'] === 'textarea')
                    <textarea x-model="form.{{ $field['name'] }}" rows="2" placeholder="{{ $field['placeholder'] ?? '' }}" class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors resize-none" :class="errors.{{ $field['name'] }} ? 'border-red-400' : ''"></textarea>
                    @elseif($field['type'] === 'select')
                    <select x-model="form.{{ $field['name'] }}" class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors" :class="errors.{{ $field['name'] }} ? 'border-red-400' : ''">
                        <option value="">Select {{ $field['label'] }}</option>
                        @foreach($field['options'] ?? [] as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    @else
                    <input type="{{ $field['type'] }}" x-model="form.{{ $field['name'] }}" placeholder="{{ $field['placeholder'] ?? '' }}" class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors" :class="errors.{{ $field['name'] }} ? 'border-red-400' : ''">
                    @endif
                    <p x-show="errors.{{ $field['name'] }}" x-text="errors.{{ $field['name'] }}" class="mt-1 text-xs text-red-600"></p>
                </div>
                @endforeach
                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="form.is_active" class="sr-only peer">
                        <div class="w-9 h-5 bg-stone-200 peer-focus:ring-2 peer-focus:ring-red-700/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-stone-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-red-700"></div>
                    </label>
                    <span class="text-sm text-stone-600 font-medium">Active</span>
                </div>
            </div>
        </div>
        <div class="px-5 py-4 border-t border-stone-100 flex items-center justify-end gap-2">
            <button @click="closeModal()" class="tb-btn tb-btn-edit">Cancel</button>
            <button @click="save()" :disabled="saving" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-800 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-semibold transition-colors">
                <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span x-text="editId ? 'Save' : 'Add'"></span>
            </button>
        </div>
    </div>
</div>
