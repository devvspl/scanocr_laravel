@extends('layouts.app')

@section('title', 'Numbering')
@section('page-title', 'Numbering')

@section('content')
<div x-data="numberingPage()" x-init="init()">

    @include('panel.settings._nav')

    <div class="bg-white border border-stone-200 rounded-1xl overflow-hidden">
        <div class="flex" style="min-height: 520px;">

            {{-- ── Left col: document type list ── --}}
            <div class="w-64 shrink-0 border-r border-stone-100 flex flex-col" style="max-height: 80vh; overflow-y: auto;">
                <div class="px-3 py-2.5 border-b border-stone-100 bg-stone-50 shrink-0">
                    <p class="text-[10px] font-bold text-stone-400 uppercase tracking-widest mb-2">Document</p>
                    <div class="flex items-center gap-2 bg-white border border-stone-200 rounded-lg px-2.5 py-1.5 focus-within:border-red-700 focus-within:ring-1 focus-within:ring-red-700/10 transition">
                        <svg class="w-3 h-3 text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" x-model="search" placeholder="Search…" class="flex-1 text-xs bg-transparent outline-none border-none p-0 text-stone-700 placeholder-stone-400 min-w-0">
                        <button x-show="search" @click="search = ''" class="text-stone-300 hover:text-stone-500 transition shrink-0">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto py-1">
                    @foreach($documentTypes as $dt)
                    <button @click="activeDoc = '{{ $dt->key }}'; search = ''"
                            x-show="!search || '{{ strtolower($dt->label) }}'.includes(search.toLowerCase())"
                            :class="activeDoc === '{{ $dt->key }}' ? 'bg-red-50 text-red-700 font-semibold border-r-2 border-red-700' : 'text-stone-600 hover:bg-stone-50 hover:text-stone-800'"
                            class="w-full flex items-center gap-2.5 px-4 py-2.5 text-xs transition-colors text-left">
                        @if($dt->icon_path)<svg class="w-3.5 h-3.5 shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $dt->icon_path }}"/></svg>@else<span class="w-3.5 h-3.5 shrink-0"></span>@endif
                        <span class="truncate">{{ $dt->label }}</span>
                        @php $s = $settings->get($dt->key); @endphp
                        @if($s)<span class="ml-auto font-mono text-[10px] text-stone-400 shrink-0">{{ $s->prefix ?: $dt->default_prefix }}</span>@endif
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- ── Right col: numbering settings panel ── --}}
            <div class="flex-1 min-w-0 overflow-y-auto" style="max-height:80vh;">
                @foreach($documentTypes as $dt)
                @php $setting = $settings->get($dt->key); @endphp
                <div x-show="activeDoc === '{{ $dt->key }}'" style="display:none">
                    <div class="p-6" x-data="docSettings({{ $setting ? $setting->id : 'null' }}, @js($setting ? $setting->toArray() : null), '{{ $dt->default_prefix }}')" x-init="initDoc()">

                        {{-- Header --}}
                        <div class="flex items-center gap-3 mb-6">
                            @if($dt->icon_path)
                            <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
                                <svg class="w-4.5 h-4.5 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $dt->icon_path }}"/></svg>
                            </div>
                            @endif
                            <div>
                                <h3 class="text-sm font-semibold text-stone-800">{{ $dt->label }}</h3>
                                <p class="text-xs text-stone-400">Configure numbering for {{ strtolower($dt->label) }}</p>
                            </div>
                        </div>

                        {{-- Preview banner --}}
                        <div class="mb-6 p-4 bg-gradient-to-r from-stone-50 to-stone-100 border border-stone-200 rounded-xl flex items-center justify-between gap-4">
                            <div>
                                <p class="text-[10px] font-bold text-stone-400 uppercase tracking-widest mb-1">Preview</p>
                                <p class="text-3xl font-bold text-stone-800 font-mono tracking-wider" x-text="preview"></p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-[10px] text-stone-400 uppercase tracking-wide">Next number</p>
                                <p class="text-2xl font-bold text-red-700 font-mono" x-text="form.next_number"></p>
                            </div>
                        </div>

                        {{-- Format & Date settings --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <h4 class="text-xs font-bold text-stone-400 uppercase tracking-widest">Format</h4>
                                <div class="grid grid-cols-3 gap-3">
                                    <div><label class="form-label">Prefix</label><input type="text" x-model="form.prefix" @input="updatePreview()" maxlength="20" placeholder="{{ $dt->default_prefix }}" class="form-input font-mono"></div>
                                    <div><label class="form-label">Separator</label><select x-model="form.separator" @change="updatePreview()" class="form-input"><option value="/">/</option><option value="-">-</option><option value=".">.</option><option value="_">_</option><option value="">None</option></select></div>
                                    <div><label class="form-label">Suffix</label><input type="text" x-model="form.suffix" @input="updatePreview()" maxlength="20" class="form-input font-mono"></div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div><label class="form-label">Next Number</label><input type="number" x-model="form.next_number" @input="updatePreview()" min="1" class="form-input font-mono"></div>
                                    <div><label class="form-label">Zero Padding</label><select x-model="form.pad_length" @change="updatePreview()" class="form-input"><option value="1">1 → 1</option><option value="2">2 → 01</option><option value="3">3 → 001</option><option value="4">4 → 0001</option><option value="5">5 → 00001</option><option value="6">6 → 000001</option></select></div>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <h4 class="text-xs font-bold text-stone-400 uppercase tracking-widest">Date & Reset</h4>
                                <div><label class="form-label">Reset Frequency</label><select x-model="form.reset_frequency" class="form-input"><option value="never">Never reset</option><option value="yearly">Reset every year</option><option value="monthly">Reset every month</option></select></div>
                                <div class="p-4 bg-stone-50 border border-stone-200 rounded-xl space-y-3">
                                    <label class="flex items-center gap-3 cursor-pointer"><input type="checkbox" x-model="form.include_date" @change="updatePreview()" class="w-4 h-4 rounded border-stone-300 text-red-700 focus:ring-red-700"><span class="text-sm font-medium text-stone-700">Include date in number</span></label>
                                    <div x-show="form.include_date" class="pl-7"><label class="form-label">Date Format</label><select x-model="form.date_format" @change="updatePreview()" class="form-input"><option value="YYYY-MM">YYYY-MM</option><option value="YYYY">YYYY</option><option value="YYYY-MM-DD">YYYY-MM-DD</option><option value="MM-YYYY">MM-YYYY</option><option value="YYYYMM">YYYYMM</option></select></div>
                                </div>
                            </div>
                        </div>

                        {{-- Save button --}}
                        <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-stone-100">
                            <div x-show="saved" x-transition class="flex items-center gap-1.5 text-xs text-green-700 font-semibold" style="display:none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Saved
                            </div>
                            <button @click="saveNumbering()" :disabled="saving" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-red-800 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-semibold transition-colors">
                                <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Save Numbering
                            </button>
                        </div>

                    </div>
                </div>
                @endforeach
            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function numberingPage() {
    return {
        activeDoc: '{{ $documentTypes->first()?->key ?? "" }}',
        search: '',
        init() {},
    };
}

function docSettings(settingId, setting, defaultPrefix) {
    return {
        settingId,
        saving: false,
        saved: false,
        preview: '',
        form: {
            prefix:          (setting && setting.prefix !== '' && setting.prefix !== null) ? setting.prefix : (defaultPrefix ?? ''),
            suffix:          setting?.suffix ?? '',
            next_number:     setting?.next_number ?? 1,
            pad_length:      String(setting?.pad_length ?? 4),
            reset_frequency: setting?.reset_frequency ?? 'yearly',
            include_date:    setting?.include_date ?? false,
            date_format:     setting?.date_format ?? 'YYYY-MM',
            separator:       (setting && setting.separator !== null && setting.separator !== undefined) ? setting.separator : '/',
        },

        initDoc() {
            if (!this.form.prefix && defaultPrefix) this.form.prefix = defaultPrefix;
            this.updatePreview();
        },

        updatePreview() {
            const num = String(this.form.next_number || 1).padStart(parseInt(this.form.pad_length) || 4, '0');
            const sep = this.form.separator !== undefined ? this.form.separator : '/';
            const parts = [];
            if (this.form.prefix) parts.push(this.form.prefix);
            if (this.form.include_date) {
                const now = new Date(), yyyy = now.getFullYear(), mm = String(now.getMonth()+1).padStart(2,'0'), dd = String(now.getDate()).padStart(2,'0');
                parts.push((this.form.date_format||'YYYY-MM').replace('YYYY',yyyy).replace('MM',mm).replace('DD',dd));
            }
            parts.push(num);
            if (this.form.suffix) parts.push(this.form.suffix);
            this.preview = parts.join(sep);
        },

        _toast(message, type = 'success') {
            const el = document.createElement('div');
            const isErr = type === 'error';
            el.style.cssText = `position:fixed;bottom:1.25rem;right:1.25rem;z-index:9999;padding:.6rem 1rem;border-radius:.75rem;font-size:.75rem;font-weight:500;display:flex;align-items:center;gap:.5rem;box-shadow:0 4px 16px rgba(0,0,0,.1);background:${isErr ? '#fef2f2' : '#f0fdf4'};border:1px solid ${isErr ? '#fecaca' : '#bbf7d0'};color:${isErr ? '#991b1b' : '#166534'};`;
            el.innerHTML = `<span>${message}</span>`;
            document.body.appendChild(el);
            setTimeout(() => el.remove(), 3500);
        },

        async saveNumbering() {
            if (!this.settingId) return;
            this.saving = true;
            try {
                const res = await fetch(`/settings/numbering/${this.settingId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ ...this.form, include_date: this.form.include_date ? 1 : 0 }),
                });
                const json = await res.json();
                if (json.success) {
                    if (json.preview) this.preview = json.preview;
                    this.saved = true;
                    setTimeout(() => { this.saved = false; }, 3000);
                } else {
                    this._toast(json.message || 'Save failed', 'error');
                }
            } catch (err) {
                this._toast('Network error', 'error');
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
@endpush
