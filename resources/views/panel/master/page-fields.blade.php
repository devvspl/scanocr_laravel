@extends('panel.master')
@php $tab = 'page-builder'; @endphp
@section('master-content')
    @php
        $fieldTypes = [
            // ── Text & Content ──
            'title' => ['Title', 'Short text', 'M4 6h16M4 10h16M4 14h8', 'text'],
            'content' => ['Content', 'Long textarea', 'M4 6h16M4 10h16M4 14h16M4 18h12', 'textarea'],
            // ── Numbers ──
            'number' => ['Number', 'Integer', 'M7 20l4-16m2 16l4-16M6 9h14M4 15h14', 'number'],
            'decimal' => ['Decimal', 'Float / decimal', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'number'],
            'currency' => ['Currency', 'Money / price', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'number'],
            'rating' => ['Rating', 'Star rating 1-5', 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z', 'number'],
            'slider' => ['Slider', 'Numeric range', 'M4 12h16M8 8v8M16 8v8', 'number'],
            // ── Contact ──
            'email' => ['Email', 'Email address', 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'email'],
            'phone' => ['Phone', 'Phone number', 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z', 'tel'],
            'url' => ['URL', 'Website link', 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1', 'url'],
            'password' => ['Password', 'Masked field', 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', 'password'],
            // ── Date & Time ──
            'date' => ['Date', 'Date picker', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'date'],
            'datetime' => ['Date & Time', 'Date + time', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'datetime-local'],
            'time' => ['Time', 'Time picker', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'time'],
            'date_range' => ['Date Range', 'From-to dates', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'date'],
            // ── Selection ──
            'select' => ['Select', 'Dropdown', 'M8 9l4-4 4 4m0 6l-4 4-4-4', 'select'],
            'multi_select' => ['Multi Select', 'Tag-style multi', 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z', 'select'],
            'radio' => ['Radio Group', 'Single choice', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'radio'],
            'checkbox' => ['Checkbox', 'On/off toggle', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'checkbox'],
            'toggle' => ['Toggle', 'Boolean switch', 'M8 9l4-4 4 4m0 6l-4 4-4-4', 'checkbox'],
            // ── Calculation ──
            'formula' => ['Formula', 'Calculated field', 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z', 'formula'],
            'tax_group' => ['Tax Group', 'GST calculation', 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z', 'tax'],
            'summary' => ['Summary Block', 'Invoice totals', 'M4 6h16M4 10h16M4 14h16M4 18h16', 'summary'],
            // ── Media & Files ──
            'image' => ['Image', 'Image upload', 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', 'file'],
            'file' => ['File', 'Document upload', 'M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13', 'file'],
            'signature' => ['Signature', 'Draw signature', 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z', 'signature'],
            // ── Layout ──
            'divider' => ['Divider', 'Section separator', 'M4 12h16', 'divider'],
            'color' => ['Color', 'Color picker', 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01', 'color'],
            // ── Data ──
            'json' => ['JSON', 'Raw JSON data', 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4', 'textarea'],
            'repeater' => ['Repeater', 'Multi-row table', 'M3 10h18M3 14h18M10 3v18M14 3v18M3 6a3 3 0 013-3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6z', 'repeater'],
        ];
    @endphp

    {{-- ══════════════════════════════════════════════════════
    MOBILE PALETTE DRAWER TOGGLE (visible on small screens)
    ══════════════════════════════════════════════════════ --}}
    <div x-data="{
            settingsOpen: false,
            activeField: null,
            search: '',
            paletteOpen: false,
            reorderMode: false,
            reorderSaving: false,
            columns: [],
            selectionOptions: [],
            dynamicOptions: { enabled: false, table: '', label_col: '', value_col: '' },
            fetchedColumns: [],
            _loadingDynamic: false,
            autoFillEnabled: false,
            autoFillMappings: [],
            init() {
                this.$watch('activeField', value => {
                    if (value) {
                        this.selectionOptions = JSON.parse(JSON.stringify((value.options && value.options.static) ? value.options.static : []));
                        this.columns = JSON.parse(JSON.stringify(value.repeater_columns || []));
                        
                        // Load auto-fill settings
                        let af = value.auto_fill || { enabled: false, mappings: [] };
                        this.autoFillEnabled = af.enabled || false;
                        this.autoFillMappings = JSON.parse(JSON.stringify(af.mappings || []));

                        let dyn = (value.options && value.options.dynamic) ? value.options.dynamic : { enabled: false, table: '', label_col: '', value_col: '' };
                        
                        this._loadingDynamic = true;
                        this.dynamicOptions = JSON.parse(JSON.stringify(dyn));
                        
                        this.fetchedColumns = [];
                        if (this.dynamicOptions.enabled && this.dynamicOptions.table) {
                            this.fetchColumns(this.dynamicOptions.table, dyn.label_col, dyn.value_col);
                        } else {
                            this.$nextTick(() => { this._loadingDynamic = false; });
                        }
                    } else {
                        this.fetchedColumns = [];
                    }
                });
                
                this.$watch('dynamicOptions.table', (table) => {
                    if (this._loadingDynamic) return;
                    if (table) {
                        this.fetchColumns(table);
                        this.dynamicOptions.label_col = '';
                        this.dynamicOptions.value_col = '';
                    } else {
                        this.fetchedColumns = [];
                    }
                });
            },
            async fetchColumns(table, label = null, value = null) {
                if (!table) return;
                try {
                    const res = await fetch(`/master/page-builder/get-columns?table=${table}`);
                    this.fetchedColumns = await res.json();
                    
                    // Re-apply prefilled entries after Alpine has rendered the <option> tags
                    // Use setTimeout to ensure x-for has fully rendered options in the DOM
                    if (label || value) {
                        setTimeout(() => {
                            this._loadingDynamic = true;
                            if (label) this.dynamicOptions.label_col = label;
                            if (value) this.dynamicOptions.value_col = value;
                            this.$nextTick(() => { this._loadingDynamic = false; });
                        }, 100);
                    }
                } catch(e) {
                    console.error('Fetch columns failed', e);
                    this.fetchedColumns = [];
                }
            },
            addOption() {
                this.selectionOptions.push({ label: '', value: '' });
            },
            removeOption(idx) {
                this.selectionOptions.splice(idx, 1);
            },
            addColumn() {
                 this.columns.push({
                    key: 'col_' + (this.columns.length + 1),
                    label: 'Column ' + (this.columns.length + 1),
                    type: 'text',
                    required: false,
                    default: '',
                    options: []
                });
            },
            removeColumn(idx) {
                if (this.columns.length <= 1) return;
                this.columns.splice(idx, 1);
            },
            slugify(str) {
                return str.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '') || 'col';
            },
            submitRepeater(e) {
                const form = e.target;
                form.querySelectorAll('input[name^=\'columns\']').forEach(el => el.remove());
                this.columns.forEach((col, i) => {
                    const fields = {
                        key: col.key,
                        label: col.label,
                        type: col.type,
                        required: col.required ? '1' : '',
                        default: col.default || '',
                        formula: col.formula || '',
                        show_summary: col.show_summary ? '1' : '',
                        options: col.options ? JSON.stringify(col.options) : '',
                        dynamic: col.dynamic ? JSON.stringify(col.dynamic) : '',
                        auto_fill_enabled: col.auto_fill_enabled ? '1' : '',
                        auto_fill_mappings: col.auto_fill_mappings ? JSON.stringify(col.auto_fill_mappings) : '',
                    };
                    Object.entries(fields).forEach(([k, v]) => {
                        const inp = document.createElement('input');
                        inp.type = 'hidden';
                        inp.name = 'columns[' + i + '][' + k + ']';
                        inp.value = v;
                        form.appendChild(inp);
                    });
                });
                form.submit();
            },
        }" class="flex flex-col lg:flex-row gap-3 lg:gap-4" style="height: calc(100vh - 120px); min-height: 0;">

        {{-- ══ MOBILE: top bar with palette toggle ══ --}}
        <div class="flex items-center justify-between lg:hidden px-1 shrink-0">
            <button type="button" @click="paletteOpen = !paletteOpen"
                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-stone-900 text-white text-xs font-semibold shadow-md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Field
            </button>
            <span class="text-xs text-stone-500">{{ $fields->count() }} {{ Str::plural('field', $fields->count()) }}</span>
        </div>

        {{-- ══ MOBILE palette drawer overlay ══ --}}
        <div x-show="paletteOpen" x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="paletteOpen = false"
            class="fixed inset-0 bg-black/60 z-40 lg:hidden" style="display:none"></div>

        {{-- ══ LEFT: Field Type Palette ══ --}}
        {{-- Mobile: slides up from bottom / Desktop: fixed sidebar --}}
        <div class="
                {{-- Mobile: fixed bottom drawer --}}
                fixed bottom-0 left-0 right-0 z-50 rounded-t-2xl max-h-[80vh] flex flex-col
                transition-transform duration-300
                lg:static lg:z-auto lg:rounded-1xl lg:max-h-none lg:w-64 lg:shrink-0 lg:translate-y-0
                bg-stone-900 shadow-2xl lg:shadow-lg overflow-hidden
            " :class="paletteOpen ? 'translate-y-0' : 'translate-y-full lg:translate-y-0'" style="height: auto;"
            :style="'height:' + (window.innerWidth >= 1024 ? '100%' : 'auto')">
            {{-- Mobile drag handle --}}
            <div class="flex justify-center pt-3 pb-1 lg:hidden shrink-0">
                <div class="w-10 h-1 rounded-full bg-white/20"></div>
            </div>

            {{-- Header --}}
            <div class="px-4 py-3 border-b border-white/10 shrink-0">
                <div class="flex items-center justify-between mb-2.5">
                    <p class="text-xs font-semibold text-white">Field Types</p>
                    <button @click="paletteOpen = false"
                        class="lg:hidden w-6 h-6 flex items-center justify-center rounded-lg text-stone-400 hover:bg-white/10">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="relative">
                    <input type="text" x-model="search" placeholder="Search…" class="w-full pl-7 pr-3 py-1.5 text-xs bg-white/10 border border-white/15 rounded-lg
                                  outline-none text-white placeholder-stone-600 focus:border-red-500 transition">
                </div>
            </div>

            {{-- Field list --}}
            <div class="flex-1 overflow-y-auto p-2 space-y-0.5">
                @foreach($fieldTypes as $type => [$label, $desc, $icon, $inputType])
                    <form method="POST" action="{{ route('master.page-builder.fields.store', $page) }}"
                        x-show="search === '' || '{{ strtolower($label . ' ' . $desc) }}'.includes(search.toLowerCase())">
                        @csrf
                        <input type="hidden" name="field_type" value="{{ $type }}">
                        <div class="group flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-white/8 transition-colors cursor-pointer"
                            onclick="this.closest('form').querySelector('input[name=field_name]').focus()">
                            <div
                                class="w-7 h-7 rounded-lg bg-white/8 flex items-center justify-center shrink-0 group-hover:bg-red-800/60 transition-colors">
                                <svg class="w-3.5 h-3.5 text-stone-400 group-hover:text-white transition-colors" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-stone-300 leading-tight">{{ $label }}</p>
                                <p class="text-[10px] text-stone-600 leading-tight truncate">{{ $desc }}</p>
                            </div>
                            <input type="text" name="field_name" placeholder="Name" onclick="event.stopPropagation()" class="w-0 group-hover:w-20 opacity-0 group-hover:opacity-100 transition-all duration-200
                                          px-2 py-1 text-[10px] bg-white/10 border border-white/20 rounded-md outline-none
                                          text-white placeholder-stone-600 focus:border-red-500 focus:w-20 focus:opacity-100">
                            <button type="submit" onclick="event.stopPropagation()"
                                class="w-0 group-hover:w-auto opacity-0 group-hover:opacity-100 transition-all duration-200
                                           px-2 py-1 rounded-md bg-red-800 hover:bg-red-700 text-white text-[10px] font-semibold shrink-0 overflow-hidden">
                                Add
                            </button>
                        </div>
                        @error('field_name_' . $type)
                            <p class="px-3 text-[10px] text-red-400">{{ $message }}</p>
                        @enderror
                    </form>
                @endforeach
            </div>
        </div>

        {{-- ══ RIGHT: Form Preview ══ --}}
        <div class="flex-1 min-w-0 flex flex-col bg-white border border-stone-200 rounded-1xl overflow-hidden">

            {{-- Toolbar --}}
            <div
                class="px-4 sm:px-5 py-3 sm:py-3.5 border-b border-stone-100 flex flex-wrap items-center justify-between gap-2 shrink-0">
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-stone-800 truncate">{{ $page->page_name }}</h3>
                    <p class="text-xs text-stone-400 mt-0.5">{{ $fields->count() }}
                        {{ Str::plural('field', $fields->count()) }} added</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">

                    {{-- Reorder toggle --}}
                    @if(!$fields->isEmpty())
                        <button type="button" @click="reorderMode = !reorderMode"
                            :class="reorderMode ? 'bg-amber-500 hover:bg-amber-400 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <span x-text="reorderMode ? 'Done' : 'Reorder'"></span>
                        </button>
                    @endif

                    @if(!$fields->isEmpty())
                        <a href="{{ route('master.page-builder.preview', $page) }}" target="_blank"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-sky-600 hover:bg-sky-700 text-white transition-colors shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <span class="hidden sm:inline">Preview</span>
                        </a>
                        <form method="POST" class="mb-0" action="{{ route('master.page-builder.generate', $page) }}"
                            onsubmit="return confirm('Generate for \'{{ $page->page_name }}\'?')">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold
                                           {{ $page->is_generated ? 'bg-stone-600 hover:bg-stone-700' : 'bg-green-700 hover:bg-green-600' }}
                                           text-white transition-colors shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <span class="hidden sm:inline">{{ $page->is_generated ? 'Re-Generate' : 'Generate' }}</span>
                                <span class="sm:hidden">{{ $page->is_generated ? 'Re-Gen' : 'Gen' }}</span>
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('master.page-builder') }}"
                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-stone-100 text-stone-600 hover:bg-stone-200 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span class="hidden sm:inline">Back</span>
                    </a>
                </div>
            </div>

            {{-- Reorder hint banner --}}
            <div x-show="reorderMode" x-transition:enter="transition-all duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition-all duration-150" x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2"
                class="mx-4 mt-3 px-3 py-2 rounded-xl bg-amber-50 border border-amber-200 flex items-center gap-2 shrink-0"
                style="display:none">
                <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4" />
                </svg>
                <p class="text-xs text-amber-700">Drag fields to reorder. Changes save automatically.</p>
                <div x-show="reorderSaving" class="ml-auto">
                    <svg class="w-3.5 h-3.5 text-amber-500 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                </div>
            </div>

            {{-- Form canvas --}}
            <div class="flex-1 overflow-y-auto p-4 sm:p-6">
                @if($fields->isEmpty())
                    <div class="flex flex-col items-center justify-center h-full text-center py-20">
                        <div class="w-16 h-16 rounded-1xl bg-stone-100 flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-stone-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M4 6h16M4 12h8m-8 6h16" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-stone-400">No fields yet</p>
                        <p class="text-xs text-stone-300 mt-1 max-w-xs">Pick a field type from the left panel and give it a
                            name.</p>
                        {{-- Mobile CTA --}}
                        <button type="button" @click="paletteOpen = true"
                            class="mt-4 lg:hidden inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-stone-900 text-white text-xs font-semibold">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add your first field
                        </button>
                    </div>
                @else
                    {{-- Sortable grid --}}
                    <div id="sortable-fields" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-4 gap-y-4"
                        data-reorder-url="{{ route('master.page-builder.fields.reorder', $page) }}">
                        @foreach($fields as $field)
                            @php
                                $fieldData = [
                                    'id' => $field->id,
                                    'field_name' => $field->field_name,
                                    'field_type' => $field->field_type,
                                    'label' => $field->label ?? $field->field_name,
                                    'column_name' => $field->column_name ?? '',
                                    'placeholder' => $field->placeholder ?? '',
                                    'default_value' => $field->default_value ?? '',
                                    'col_span' => $field->col_span ?? 1,
                                    'is_required' => $field->is_required ? 'true' : 'false',
                                    'is_unique' => $field->is_unique ? 'true' : 'false',
                                    'is_nullable' => $field->is_nullable ? 'true' : 'false',
                                    'column_length' => $field->column_length ?? '',
                                    'description' => $field->description ?? '',
                                    'repeater_columns' => $field->repeater_columns ?? [],
                                    'options' => $field->options ?? [],
                                    'formula' => $field->formula ?? null,
                                    'auto_fill' => $field->auto_fill ?? null,
                                    'visibility_rules' => $field->visibility_rules ?? null,
                                    'field_key' => $field->field_key ?? '',
                                    'settings_url' => route('master.page-builder.fields.settings', [$page, $field]),
                                    'repeater_url' => route('master.page-builder.fields.repeater', [$page, $field]),
                                    'destroy_url' => route('master.page-builder.fields.destroy', [$page, $field]),
                                ];
                                // col-span applies on lg (3-col grid); on smaller screens all are 1-col
                                $lgSpan = match ((int) ($field->col_span ?? 1)) { 2 => 'lg:col-span-2', 3 => 'lg:col-span-3', default => 'lg:col-span-1'};
                                $smSpan = match ((int) ($field->col_span ?? 1)) { 3 => 'sm:col-span-2', default => 'sm:col-span-1'};
                            @endphp

                            <div class="group relative {{ $smSpan }} {{ $lgSpan }} field-item" data-id="{{ $field->id }}">

                                {{-- Drag handle (visible in reorder mode) --}}
                                <div x-show="reorderMode" class="drag-handle absolute -left-1 top-1/2 -translate-y-1/2 z-10 cursor-grab active:cursor-grabbing
                                            w-5 h-8 flex items-center justify-center rounded-md bg-stone-100 border border-stone-200
                                            text-stone-400 hover:text-stone-600 hover:bg-stone-200 transition-colors shadow-sm"
                                    style="display:none">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                        <circle cx="9" cy="5" r="1.5" />
                                        <circle cx="15" cy="5" r="1.5" />
                                        <circle cx="9" cy="12" r="1.5" />
                                        <circle cx="15" cy="12" r="1.5" />
                                        <circle cx="9" cy="19" r="1.5" />
                                        <circle cx="15" cy="19" r="1.5" />
                                    </svg>
                                </div>

                                {{-- Reorder mode overlay highlight --}}
                                <div x-show="reorderMode"
                                    class="absolute inset-0 rounded-lg ring-2 ring-amber-400/40 bg-amber-50/30 pointer-events-none z-0 transition-opacity"
                                    style="display:none"></div>

                                {{-- Label row --}}
                                <div class="relative z-1 flex items-center justify-between mb-1">
                                    <label class="text-xs font-medium text-stone-600 leading-tight">
                                        {{ $field->label ?? $field->field_name }}
                                        @if($field->is_required)<span class="text-red-500 ml-0.5">*</span>@endif
                                        @if($field->is_unique)<span
                                        class="ml-1 text-[9px] font-semibold text-blue-500 bg-blue-50 px-1 py-0.5 rounded">U</span>@endif
                                    </label>
                                    <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity"
                                        x-show="!reorderMode">
                                        <button type="button"
                                            @click="activeField = {{ json_encode($fieldData) }}; settingsOpen = true"
                                            class="w-6 h-6 flex items-center justify-center rounded text-stone-400 hover:text-stone-700 hover:bg-stone-100 transition-colors touch-manipulation">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </button>
                                        <form method="POST"
                                            action="{{ route('master.page-builder.fields.destroy', [$page, $field]) }}"
                                            onsubmit="return confirm('Remove this field?')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="w-6 h-6 flex items-center justify-center rounded text-stone-400 hover:text-red-500 hover:bg-red-50 transition-colors touch-manipulation">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                {{-- Input preview --}}
                                <div class="relative z-1">
                                    @if($field->field_type === 'repeater')
                                        @php $rCols = $field->repeater_columns ?? [['key' => 'item', 'label' => 'Item', 'type' => 'text']]; @endphp
                                        <div class="border border-stone-200 rounded-lg overflow-hidden overflow-x-auto">
                                            <table class="w-full text-xs min-w-max">
                                                <thead class="bg-stone-800 text-white">
                                                    <tr>
                                                        <th class="px-2 py-1.5 text-left font-medium w-6">#</th>
                                                        @foreach($rCols as $rc)
                                                            <th class="px-2 py-1.5 text-left font-medium whitespace-nowrap">
                                                                {{ $rc['label'] }}@if(!empty($rc['required']))<span
                                                                class="text-red-400 ml-0.5">*</span>@endif</th>
                                                        @endforeach
                                                        <th class="px-2 py-1.5 w-8"></th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-stone-100">
                                                    <tr class="bg-white">
                                                        <td class="px-2 py-1.5 text-stone-400">1</td>
                                                        @foreach($rCols as $rc)
                                                            <td class="px-2 py-1.5">
                                                                @if(in_array($rc['type'], ['select']))
                                                                    <select disabled
                                                                        class="w-full px-1.5 py-1 border border-stone-200 rounded bg-stone-50 text-stone-400 text-xs pointer-events-none">
                                                                        <option>—</option>
                                                                    </select>
                                                                @elseif($rc['type'] === 'checkbox')
                                                                    <input type="checkbox" disabled
                                                                        class="w-3.5 h-3.5 border-stone-300 pointer-events-none">
                                                                @else
                                                                    <input type="text" disabled placeholder="{{ $rc['default'] ?? '' }}"
                                                                        class="w-full px-1.5 py-1 border border-stone-200 rounded bg-stone-50 text-stone-400 text-xs pointer-events-none">
                                                                @endif
                                                            </td>
                                                        @endforeach
                                                        <td class="px-2 py-1.5 text-center"><span
                                                                class="inline-flex w-5 h-5 items-center justify-center rounded bg-green-600 text-white text-xs font-bold">+</span>
                                                        </td>
                                                    </tr>
                                                    <tr class="bg-stone-50/50">
                                                        <td class="px-2 py-1.5 text-stone-400">2</td>
                                                        @foreach($rCols as $rc)
                                                            <td class="px-2 py-1.5"><input type="text" disabled
                                                                    class="w-full px-1.5 py-1 border border-stone-200 rounded bg-white text-stone-400 text-xs pointer-events-none">
                                                            </td>
                                                        @endforeach
                                                        <td class="px-2 py-1.5 text-center"><span
                                                                class="inline-flex w-5 h-5 items-center justify-center rounded bg-red-600 text-white text-xs font-bold">−</span>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    @elseif($field->field_type === 'content' || $field->field_type === 'json')
                                        <textarea disabled
                                            placeholder="{{ $field->placeholder ?: ($field->label ?? $field->field_name) }}"
                                            class="w-full px-3 py-2 text-xs border border-stone-200 rounded-lg bg-stone-50 text-stone-400 resize-none h-16 pointer-events-none"></textarea>
                                    @elseif($field->field_type === 'checkbox' || $field->field_type === 'toggle')
                                        <div
                                            class="flex items-center gap-2 px-3 py-2 border border-stone-200 rounded-lg bg-stone-50 pointer-events-none">
                                            <input type="checkbox" disabled class="w-3.5 h-3.5 rounded border-stone-300">
                                            <span
                                                class="text-xs text-stone-400">{{ $field->placeholder ?: ($field->label ?? $field->field_name) }}</span>
                                        </div>
                                    @elseif($field->field_type === 'select')
                                        <select disabled
                                            class="w-full px-3 py-2 text-xs border border-stone-200 rounded-lg bg-stone-50 text-stone-400 pointer-events-none">
                                            <option>{{ $field->placeholder ?: '— Select —' }}</option>
                                        </select>
                                    @elseif(in_array($field->field_type, ['image', 'file']))
                                        <div
                                            class="w-full px-3 py-2 text-xs border border-stone-200 rounded-lg bg-stone-50 text-stone-400 flex items-center gap-2 pointer-events-none">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="{{ $fieldTypes[$field->field_type][2] }}" />
                                            </svg>
                                            <span>Choose {{ $field->field_type }}</span>
                                        </div>
                                    @elseif($field->field_type === 'color')
                                        <div
                                            class="flex items-center gap-2 px-3 py-2 border border-stone-200 rounded-lg bg-stone-50 pointer-events-none">
                                            <div class="w-4 h-4 rounded border border-stone-300 bg-red-800"></div>
                                            <span class="text-xs text-stone-400">#7f1d1d</span>
                                        </div>
                                    @else
                                        <input type="{{ $fieldTypes[$field->field_type][3] ?? 'text' }}" disabled
                                            placeholder="{{ $field->placeholder ?: ($field->label ?? $field->field_name) }}"
                                            class="w-full px-3 py-2 text-xs border border-stone-200 rounded-lg bg-stone-50 text-stone-400 pointer-events-none">
                                    @endif
                                </div>

                                @if($field->description)
                                    <p class="mt-1 text-[10px] text-stone-400 italic relative z-1">{{ $field->description }}</p>
                                @endif

                                {{-- Hover ring --}}
                                <div class="absolute inset-0 rounded-lg pointer-events-none opacity-0 group-hover:opacity-100 ring-2 ring-red-700/25 transition-opacity"
                                    x-show="!reorderMode"></div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════
        FIELD SETTINGS offcanvas
        ══════════════════════════════════════════════════════ --}}
        <div x-show="settingsOpen" x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="settingsOpen = false"
            style="position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9998" class="touch-none"></div>

        <div x-show="settingsOpen" x-transition:enter="transition-transform duration-300 ease-out"
            x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition-transform duration-200 ease-in" x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            style="position:fixed;top:0;right:0;height:100%;width:100%;max-width:26rem;z-index:9999"
            class="bg-stone-900 shadow-2xl flex flex-col overflow-x-hidden">

            <div class="flex items-center justify-between px-5 py-4 border-b border-white/10 shrink-0">
                <div class="min-w-0">
                    <p class="text-[10px] text-stone-500 uppercase tracking-wider">Settings For</p>
                    <h3 class="text-sm font-semibold text-white truncate"
                        x-text="activeField?.label || activeField?.field_name"></h3>
                </div>
                <button @click="settingsOpen = false"
                    class="w-7 h-7 flex items-center justify-center rounded-lg text-stone-400 hover:bg-white/10 hover:text-white transition-colors shrink-0 ml-3 touch-manipulation">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto overflow-x-hidden p-5">
                <template x-if="activeField">
                    <div class="space-y-4">

                        {{-- ── Standard settings form ── --}}
                        <template x-if="activeField.field_type !== 'repeater'">
                            <form method="POST" :action="activeField.settings_url" class="space-y-4">
                                @csrf @method('PUT')
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-stone-400 mb-1.5">Label Name</label>
                                        <input type="text" name="label" :value="activeField.label"
                                            class="w-full px-3 py-2 text-sm bg-white/10 border border-white/20 rounded-lg outline-none text-white placeholder-stone-500 focus:border-red-500 transition">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-stone-400 mb-1.5">Field Key <span class="text-yellow-500 text-[9px]">(for formulas)</span></label>
                                        <input type="text" name="field_key" :value="activeField.field_key"
                                            placeholder="e.g. qty, rate, total"
                                            class="w-full px-3 py-2 text-sm bg-white/10 border border-white/20 rounded-lg outline-none text-yellow-300 placeholder-stone-600 focus:border-yellow-500 transition font-mono">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-stone-400 mb-1.5">Column Name</label>
                                        <input type="text" name="column_name" :value="activeField.column_name"
                                            class="w-full px-3 py-2 text-sm bg-white/10 border border-white/20 rounded-lg outline-none text-white placeholder-stone-500 focus:border-red-500 transition">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-stone-400 mb-1.5">Placeholder</label>
                                        <input type="text" name="placeholder" :value="activeField.placeholder"
                                            class="w-full px-3 py-2 text-sm bg-white/10 border border-white/20 rounded-lg outline-none text-white placeholder-stone-500 focus:border-red-500 transition">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-stone-400 mb-1.5">Default Value</label>
                                        <input type="text" name="default_value" :value="activeField.default_value"
                                            class="w-full px-3 py-2 text-sm bg-white/10 border border-white/20 rounded-lg outline-none text-white placeholder-stone-500 focus:border-red-500 transition">
                                    </div>
                                </div>

                                {{-- Column Span --}}
                                <div>
                                    <label class="block text-xs font-medium text-stone-400 mb-2">Column Width</label>
                                    <div class="grid grid-cols-3 gap-2">
                                        <label class="cursor-pointer">
                                            <input type="radio" name="col_span" value="1"
                                                :checked="(activeField.col_span ?? 1) == 1" class="sr-only peer">
                                            <div
                                                class="flex flex-col items-center gap-1.5 px-3 py-2.5 rounded-lg border border-white/15 bg-white/5 peer-checked:border-red-500 peer-checked:bg-red-900/30 transition-colors text-center">
                                                <div class="flex gap-0.5 w-full">
                                                    <div class="h-2 rounded-sm bg-red-500 w-1/3"></div>
                                                    <div class="h-2 rounded-sm bg-white/10 w-1/3"></div>
                                                    <div class="h-2 rounded-sm bg-white/10 w-1/3"></div>
                                                </div>
                                                <span class="text-[10px] text-stone-400 font-medium">1 / 3</span>
                                            </div>
                                        </label>
                                        <label class="cursor-pointer">
                                            <input type="radio" name="col_span" value="2"
                                                :checked="(activeField.col_span ?? 1) == 2" class="sr-only peer">
                                            <div
                                                class="flex flex-col items-center gap-1.5 px-3 py-2.5 rounded-lg border border-white/15 bg-white/5 peer-checked:border-red-500 peer-checked:bg-red-900/30 transition-colors text-center">
                                                <div class="flex gap-0.5 w-full">
                                                    <div class="h-2 rounded-sm bg-red-500 w-2/3"></div>
                                                    <div class="h-2 rounded-sm bg-white/10 w-1/3"></div>
                                                </div>
                                                <span class="text-[10px] text-stone-400 font-medium">2 / 3</span>
                                            </div>
                                        </label>
                                        <label class="cursor-pointer">
                                            <input type="radio" name="col_span" value="3"
                                                :checked="(activeField.col_span ?? 1) == 3" class="sr-only peer">
                                            <div
                                                class="flex flex-col items-center gap-1.5 px-3 py-2.5 rounded-lg border border-white/15 bg-white/5 peer-checked:border-red-500 peer-checked:bg-red-900/30 transition-colors text-center">
                                                <div class="flex gap-0.5 w-full">
                                                    <div class="h-2 rounded-sm bg-red-500 w-full"></div>
                                                </div>
                                                <span class="text-[10px] text-stone-400 font-medium">Full</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-stone-400 mb-2">Validation</label>
                                    <div class="flex flex-wrap gap-4">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" name="is_required" value="1"
                                                :checked="activeField.is_required === 'true'"
                                                class="w-4 h-4 rounded border-white/20 bg-white/10 text-red-700 focus:ring-red-700 cursor-pointer">
                                            <span class="text-sm text-stone-300">Required</span>
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" name="is_unique" value="1"
                                                :checked="activeField.is_unique === 'true'"
                                                class="w-4 h-4 rounded border-white/20 bg-white/10 text-red-700 focus:ring-red-700 cursor-pointer">
                                            <span class="text-sm text-stone-300">Unique</span>
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" name="is_nullable" value="1"
                                                :checked="activeField.is_nullable === 'true'"
                                                class="w-4 h-4 rounded border-white/20 bg-white/10 text-red-700 focus:ring-red-700 cursor-pointer">
                                            <span class="text-sm text-stone-300">Nullable</span>
                                        </label>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-stone-400 mb-1.5">Column Length</label>
                                    <input type="number" name="column_length" :value="activeField.column_length" min="1"
                                        max="65535"
                                        class="w-full px-3 py-2 text-sm bg-white/10 border border-white/20 rounded-lg outline-none text-white placeholder-stone-500 focus:border-red-500 transition">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-stone-400 mb-1.5">Description (Help Text)</label>
                                    <textarea name="description" rows="2" :value="activeField.description"
                                        class="w-full px-3 py-2 text-sm bg-white/10 border border-white/20 rounded-lg outline-none text-white placeholder-stone-500 focus:border-red-500 transition resize-none"></textarea>
                                </div>

                                {{-- Options for Date/DateTime/Time fields --}}
                                <template x-if="['date', 'datetime', 'time'].includes(activeField.field_type)">
                                    <div class="mt-6 pt-6 border-t border-white/10 space-y-4">
                                        <h4 class="text-xs font-semibold text-stone-300 uppercase tracking-wider">Date Options</h4>
                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input type="checkbox" name="use_current_date" value="1"
                                                :checked="activeField.options && activeField.options.use_current_date"
                                                class="w-4 h-4 rounded border-white/20 bg-white/10 text-red-700 focus:ring-red-700 cursor-pointer">
                                            <span class="text-sm text-stone-300" x-text="activeField.field_type === 'time' ? 'Set current time as default' : (activeField.field_type === 'datetime' ? 'Set current date & time as default' : 'Set current date as default')"></span>
                                        </label>
                                    </div>
                                </template>

                                {{-- Formula field settings --}}
                                <template x-if="activeField.field_type === 'formula'">
                                    <div class="mt-6 pt-6 border-t border-white/10 space-y-4">
                                        <h4 class="text-xs font-semibold text-yellow-400 uppercase tracking-wider flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                            Formula Configuration
                                        </h4>
                                        <div>
                                            <label class="block text-[10px] text-stone-400 mb-1">Formula Expression</label>
                                            <input type="text" name="formula_expression"
                                                :value="activeField.formula?.expression || ''"
                                                placeholder="e.g. SUM({items.total_amt}) or {qty} * {rate}"
                                                class="w-full px-3 py-2 text-sm bg-black/20 border border-yellow-700/40 rounded-lg outline-none text-yellow-200 placeholder-yellow-700/60 focus:border-yellow-500 transition font-mono">
                                            <p class="mt-1.5 text-[9px] text-stone-500 leading-relaxed">
                                                Use <code class="text-yellow-500 bg-black/20 px-1 rounded">{field_key}</code> to reference fields.<br>
                                                Use <code class="text-yellow-500 bg-black/20 px-1 rounded">{table_key.column_key}</code> for table column aggregates.<br>
                                                Functions: <code class="text-yellow-500">SUM()</code>, <code class="text-yellow-500">AVG()</code>, <code class="text-yellow-500">ROUND(val, decimals)</code>, <code class="text-yellow-500">IF(cond, true, false)</code>
                                            </p>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] text-stone-400 mb-1">Display Format</label>
                                            <select name="formula_format"
                                                class="w-full px-2.5 py-1.5 text-xs bg-white/10 border border-white/15 rounded-lg outline-none text-white focus:border-red-500 transition cursor-pointer">
                                                <option value="currency" class="bg-stone-900 text-white" :selected="(activeField.formula?.format || 'currency') === 'currency'">₹ Currency</option>
                                                <option value="number" class="bg-stone-900 text-white" :selected="(activeField.formula?.format) === 'number'">Number</option>
                                                <option value="percentage" class="bg-stone-900 text-white" :selected="(activeField.formula?.format) === 'percentage'">Percentage (%)</option>
                                            </select>
                                        </div>
                                        <div class="p-2 bg-stone-800/50 rounded-lg">
                                            <p class="text-[9px] text-stone-400 font-semibold mb-1">Available Field Keys:</p>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($fields->where('field_type', '!=', 'formula')->where('field_type', '!=', 'summary') as $f)
                                                    <span class="px-1.5 py-0.5 text-[9px] bg-stone-700 text-stone-300 rounded font-mono">{{ '{' . ($f->field_key ?? \Illuminate\Support\Str::snake($f->field_name)) . '}' }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                        <input type="hidden" name="formula_json" :value="JSON.stringify({expression: document.querySelector('[name=formula_expression]')?.value || '', format: document.querySelector('[name=formula_format]')?.value || 'currency'})">
                                    </div>
                                </template>

                                {{-- Options for Select/Checkbox/etc --}}
                                <template x-if="['select', 'checkbox', 'toggle'].includes(activeField.field_type)">
                                    <div class="mt-6 pt-6 border-t border-white/10 space-y-4">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-xs font-semibold text-stone-300 uppercase tracking-wider">Selection Options</h4>
                                            <div class="flex items-center gap-2">
                                                <button type="button" @click="dynamicOptions.enabled = !dynamicOptions.enabled"
                                                    :class="dynamicOptions.enabled ? 'bg-red-800 text-white' : 'bg-white/10 text-stone-400'"
                                                    class="px-2 py-1 rounded-lg text-[9px] font-bold uppercase transition-colors">
                                                    <span x-text="dynamicOptions.enabled ? 'Database' : 'Static'"></span>
                                                </button>
                                                <template x-if="!dynamicOptions.enabled">
                                                    <button type="button" @click="addOption()"
                                                        class="px-2 py-1 rounded-lg bg-white/10 hover:bg-white/15 text-white text-[10px] font-medium transition-colors">
                                                        Add Option
                                                    </button>
                                                </template>
                                            </div>
                                        </div>

                                        {{-- Static List --}}
                                        <template x-if="!dynamicOptions.enabled">
                                            <div class="space-y-2">
                                                <template x-for="(opt, oIdx) in selectionOptions" :key="oIdx">
                                                    <div class="grid grid-cols-[1fr,1fr,auto] items-center gap-2">
                                                        <input type="text" x-model="opt.label" placeholder="Label"
                                                            class="w-full px-2.5 py-1.5 text-xs bg-white/10 border border-white/15 rounded-lg outline-none text-white focus:border-red-500 transition">
                                                        <input type="text" x-model="opt.value" placeholder="Value"
                                                            class="w-full px-2.5 py-1.5 text-xs bg-white/10 border border-white/15 rounded-lg outline-none text-white focus:border-red-500 transition">
                                                        <button type="button" @click="removeOption(oIdx)"
                                                            class="w-7 h-7 flex items-center justify-center rounded-lg bg-red-900/30 text-red-400 hover:bg-red-800 hover:text-white transition-colors">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>

                                        {{-- Dynamic Table --}}
                                        <template x-if="dynamicOptions.enabled">
                                            <div class="space-y-3 bg-white/5 p-3 rounded-xl border border-white/5">
                                                <div>
                                                    <label class="block text-[10px] text-stone-500 mb-1">Source Table</label>
                                                    <select x-model="dynamicOptions.table"
                                                        class="w-full px-2.5 py-1.5 text-xs bg-white/10 border border-white/15 rounded-lg outline-none text-white focus:border-red-500 transition cursor-pointer">
                                                        <option value="" class="bg-stone-900 text-white">-- Choose Table --</option>
                                                        @foreach($tables as $table)
                                                            <option value="{{ $table }}" class="bg-stone-900 text-white">{{ $table }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="grid grid-cols-2 gap-2">
                                                    <div>
                                                        <label class="block text-[10px] text-stone-500 mb-1">Label Column</label>
                                                        <select x-model="dynamicOptions.label_col"
                                                            class="w-full px-2.5 py-1.5 text-xs bg-white/10 border border-white/15 rounded-lg outline-none text-white focus:border-red-500 transition cursor-pointer">
                                                            <option value="" class="bg-stone-900 text-white">-- Choose Column --</option>
                                                            <template x-for="col in fetchedColumns" :key="col">
                                                                <option :value="col" x-text="col" :selected="col === dynamicOptions.label_col" class="bg-stone-900 text-white"></option>
                                                            </template>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="block text-[10px] text-stone-500 mb-1">Value Column</label>
                                                        <select x-model="dynamicOptions.value_col"
                                                            class="w-full px-2.5 py-1.5 text-xs bg-white/10 border border-white/15 rounded-lg outline-none text-white focus:border-red-500 transition cursor-pointer">
                                                            <option value="" class="bg-stone-900 text-white">-- Choose Column --</option>
                                                            <template x-for="col in fetchedColumns" :key="col">
                                                                <option :value="col" x-text="col" :selected="col === dynamicOptions.value_col" class="bg-stone-900 text-white"></option>
                                                            </template>
                                                        </select>
                                                    </div>
                                                </div>

                                                {{-- Loading Mode --}}
                                                <div>
                                                    <label class="block text-[10px] text-stone-500 mb-1">Loading Mode</label>
                                                    <div class="grid grid-cols-3 gap-1.5">
                                                        <label class="cursor-pointer">
                                                            <input type="radio" x-model="dynamicOptions.load_mode" value="preload" class="sr-only peer">
                                                            <div class="px-2 py-1.5 text-center text-[9px] font-medium rounded-lg border border-white/15 bg-white/5 peer-checked:border-red-500 peer-checked:bg-red-900/30 peer-checked:text-white text-stone-400 transition-colors">
                                                                Preload All
                                                            </div>
                                                        </label>
                                                        <label class="cursor-pointer">
                                                            <input type="radio" x-model="dynamicOptions.load_mode" value="server_search" class="sr-only peer">
                                                            <div class="px-2 py-1.5 text-center text-[9px] font-medium rounded-lg border border-white/15 bg-white/5 peer-checked:border-red-500 peer-checked:bg-red-900/30 peer-checked:text-white text-stone-400 transition-colors">
                                                                Server Search
                                                            </div>
                                                        </label>
                                                        <label class="cursor-pointer">
                                                            <input type="radio" x-model="dynamicOptions.load_mode" value="select2" class="sr-only peer">
                                                            <div class="px-2 py-1.5 text-center text-[9px] font-medium rounded-lg border border-white/15 bg-white/5 peer-checked:border-red-500 peer-checked:bg-red-900/30 peer-checked:text-white text-stone-400 transition-colors">
                                                                Select2
                                                            </div>
                                                        </label>
                                                    </div>
                                                    <p class="mt-1.5 text-[9px] text-stone-500">
                                                        <span x-show="!dynamicOptions.load_mode || dynamicOptions.load_mode === 'preload'">Loads all options on page load. Best for small lists (&lt;100 items).</span>
                                                        <span x-show="dynamicOptions.load_mode === 'server_search'">Fetches options from server as user types. Best for large lists.</span>
                                                        <span x-show="dynamicOptions.load_mode === 'select2'">Searchable dropdown (Select2 style) with server-side filtering. Best for large datasets.</span>
                                                    </p>
                                                </div>

                                                {{-- Search Column (for server search modes) --}}
                                                <template x-if="dynamicOptions.load_mode === 'server_search' || dynamicOptions.load_mode === 'select2'">
                                                    <div>
                                                        <label class="block text-[10px] text-stone-500 mb-1">Search Column(s)</label>
                                                        <input type="text" x-model="dynamicOptions.search_cols" placeholder="name,code,email"
                                                            class="w-full px-2.5 py-1.5 text-xs bg-white/10 border border-white/15 rounded-lg outline-none text-white placeholder-stone-600 focus:border-red-500 transition font-mono">
                                                        <p class="mt-1 text-[9px] text-stone-500">Comma-separated column names to search in. Default: label column.</p>
                                                    </div>
                                                </template>

                                                {{-- Min chars for search --}}
                                                <template x-if="dynamicOptions.load_mode === 'server_search' || dynamicOptions.load_mode === 'select2'">
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <div>
                                                            <label class="block text-[10px] text-stone-500 mb-1">Min Characters</label>
                                                            <input type="number" x-model="dynamicOptions.min_chars" min="1" max="10" placeholder="2"
                                                                class="w-full px-2.5 py-1.5 text-xs bg-white/10 border border-white/15 rounded-lg outline-none text-white placeholder-stone-600 focus:border-red-500 transition">
                                                        </div>
                                                        <div>
                                                            <label class="block text-[10px] text-stone-500 mb-1">Max Results</label>
                                                            <input type="number" x-model="dynamicOptions.max_results" min="5" max="100" placeholder="20"
                                                                class="w-full px-2.5 py-1.5 text-xs bg-white/10 border border-white/15 rounded-lg outline-none text-white placeholder-stone-600 focus:border-red-500 transition">
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>

                                        <input type="hidden" name="options_json" :value="JSON.stringify({static: selectionOptions, dynamic: dynamicOptions})">

                                        {{-- Auto-Fill Configuration --}}
                                        <template x-if="dynamicOptions.enabled && dynamicOptions.table">
                                            <div class="mt-4 pt-4 border-t border-white/10 space-y-3">
                                                <div class="flex items-center justify-between">
                                                    <h4 class="text-xs font-semibold text-red-400 uppercase tracking-wider flex items-center gap-1">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                                        Auto-Fill on Select
                                                    </h4>
                                                </div>
                                                <p class="text-[9px] text-stone-500">When user selects a value, auto-fill other fields from the same table row.</p>
                                                <div class="space-y-2" x-data="{}">
                                                    <label class="flex items-center gap-2 cursor-pointer">
                                                        <input type="checkbox" x-model="autoFillEnabled" class="w-3.5 h-3.5 rounded border-white/20 bg-white/10 text-red-700 focus:ring-red-700 cursor-pointer">
                                                        <span class="text-[11px] text-stone-300">Enable auto-fill</span>
                                                    </label>
                                                    <template x-if="autoFillEnabled">
                                                        <div class="space-y-2">
                                                            <template x-for="(mapping, mi) in autoFillMappings" :key="mi">
                                                                <div class="grid grid-cols-[1fr,auto,1fr,auto,auto] items-center gap-1.5">
                                                                    <select x-model="mapping.source_column"
                                                                        class="px-2 py-1.5 text-[10px] bg-stone-900 border border-white/20 rounded-lg outline-none text-white focus:border-red-500 cursor-pointer">
                                                                        <option value="">— Source Col —</option>
                                                                        <template x-for="col in fetchedColumns" :key="col">
                                                                            <option :value="col" x-text="col" :selected="col === mapping.source_column"></option>
                                                                        </template>
                                                                    </select>
                                                                    <span class="text-[9px] text-stone-500">→</span>
                                                                    <select x-model="mapping.target_field_key"
                                                                        class="px-2 py-1.5 text-[10px] bg-stone-900 border border-white/20 rounded-lg outline-none text-red-300 focus:border-red-500 cursor-pointer font-mono">
                                                                        <option value="">— Target Field —</option>
                                                                        @foreach($fields as $f)
                                                                            @if($f->field_key)
                                                                                <option value="{{ $f->field_key }}">{{ $f->field_key }} ({{ $f->label ?? $f->field_name }})</option>
                                                                            @endif
                                                                        @endforeach
                                                                    </select>
                                                                    <label class="flex items-center gap-1 cursor-pointer" title="Read-only (lock after auto-fill)">
                                                                        <input type="checkbox" x-model="mapping.readonly" class="w-3 h-3 rounded border-white/20 bg-white/10 text-red-700 focus:ring-red-700 cursor-pointer">
                                                                        <svg class="w-3 h-3" :class="mapping.readonly ? 'text-red-400' : 'text-stone-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                                                    </label>
                                                                    <button type="button" @click="autoFillMappings.splice(mi, 1)" class="w-5 h-5 flex items-center justify-center rounded bg-red-900/30 text-red-400 hover:bg-red-800 text-[9px]">×</button>
                                                                </div>
                                                            </template>
                                                            <button type="button" @click="autoFillMappings.push({source_column:'', target_field_key:''})"
                                                                class="text-[10px] text-red-400 hover:text-red-300 font-medium">+ Add Mapping</button>
                                                        </div>
                                                    </template>
                                                    <input type="hidden" name="auto_fill_json" :value="JSON.stringify({enabled: autoFillEnabled, source_table: dynamicOptions.table, mappings: autoFillMappings})">
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                {{-- Visibility Rules (show/hide based on conditions) --}}
                                <div class="mt-6 pt-6 border-t border-white/10 space-y-3" x-data="{ visRules: activeField.visibility_rules?.rules || [], visLogic: activeField.visibility_rules?.logic || 'AND' }">
                                    <h4 class="text-xs font-semibold text-stone-300 uppercase tracking-wider flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                        Visibility Rules
                                    </h4>
                                    <p class="text-[9px] text-stone-500">Show this field only when conditions are met. Leave empty to always show.</p>

                                    <template x-if="visRules.length > 0">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-[10px] text-stone-400">Match:</span>
                                            <select x-model="visLogic" class="px-2 py-1 text-[10px] bg-stone-900 border border-white/20 rounded-lg outline-none text-white cursor-pointer">
                                                <option value="AND">ALL rules (AND)</option>
                                                <option value="OR">ANY rule (OR)</option>
                                            </select>
                                        </div>
                                    </template>

                                    <div class="space-y-2">
                                        <template x-for="(rule, ri) in visRules" :key="ri">
                                            <div class="grid grid-cols-[1fr,auto,1fr,auto] items-center gap-1.5">
                                                <select x-model="rule.field" class="px-2 py-1.5 text-[10px] bg-stone-900 border border-white/20 rounded-lg outline-none text-white cursor-pointer">
                                                    <option value="">— Field —</option>
                                                    @foreach($fields as $f)
                                                        @if($f->field_key)
                                                            <option value="{{ $f->field_key }}">{{ $f->field_key }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                                <select x-model="rule.operator" class="px-1.5 py-1.5 text-[10px] bg-stone-900 border border-white/20 rounded-lg outline-none text-white cursor-pointer">
                                                    <option value="==">==</option>
                                                    <option value="!=">!=</option>
                                                    <option value=">">></option>
                                                    <option value="<"><</option>
                                                    <option value=">=">>=</option>
                                                    <option value="<="><=</option>
                                                    <option value="contains">contains</option>
                                                    <option value="is_empty">is empty</option>
                                                    <option value="is_not_empty">is not empty</option>
                                                </select>
                                                <input type="text" x-model="rule.value" placeholder="Value"
                                                    x-show="!['is_empty','is_not_empty'].includes(rule.operator)"
                                                    class="px-2 py-1.5 text-[10px] bg-stone-900 border border-white/20 rounded-lg outline-none text-white placeholder-stone-600">
                                                <button type="button" @click="visRules.splice(ri, 1)" class="w-5 h-5 flex items-center justify-center rounded bg-red-900/30 text-red-400 hover:bg-red-800 text-[9px]">×</button>
                                            </div>
                                        </template>
                                    </div>
                                    <button type="button" @click="visRules.push({field:'', operator:'==', value:''})"
                                        class="text-[10px] text-stone-400 hover:text-stone-300 font-medium">+ Add Rule</button>
                                    <input type="hidden" name="visibility_rules_json" :value="JSON.stringify({logic: visLogic, rules: visRules})">
                                </div>

                                <button type="submit"
                                    class="w-full mt-6 py-2.5 rounded-xl bg-red-800 hover:bg-red-700 text-white text-sm font-semibold transition-colors shadow-lg">
                                    Save Changes
                                </button>
                            </form>
                        </template>

                        {{-- ── Repeater column configurator ── --}}
                        <template x-if="activeField.field_type === 'repeater'">
                            <div class="space-y-4">
                                <form method="POST" :action="activeField.settings_url"
                                    class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    @csrf @method('PUT')
                                    <div>
                                        <label class="block text-xs font-medium text-stone-400 mb-1.5">Section Label</label>
                                        <input type="text" name="label" :value="activeField.label"
                                            class="w-full px-3 py-2 text-sm bg-white/10 border border-white/20 rounded-lg outline-none text-white focus:border-red-500 transition">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-stone-400 mb-1.5">Column Name
                                            (key)</label>
                                        <input type="text" name="column_name" :value="activeField.column_name"
                                            class="w-full px-3 py-2 text-sm bg-white/10 border border-white/20 rounded-lg outline-none text-white focus:border-red-500 transition">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-medium text-stone-400 mb-2">Column Width</label>
                                        <div class="grid grid-cols-3 gap-2">
                                            <label class="cursor-pointer">
                                                <input type="radio" name="col_span" value="1"
                                                    :checked="(activeField.col_span ?? 1) == 1" class="sr-only peer">
                                                <div
                                                    class="flex flex-col items-center gap-1.5 px-3 py-2.5 rounded-lg border border-white/15 bg-white/5 peer-checked:border-red-500 peer-checked:bg-red-900/30 transition-colors text-center">
                                                    <div class="flex gap-0.5 w-full">
                                                        <div class="h-2 rounded-sm bg-red-500 w-1/3"></div>
                                                        <div class="h-2 rounded-sm bg-white/10 w-1/3"></div>
                                                        <div class="h-2 rounded-sm bg-white/10 w-1/3"></div>
                                                    </div>
                                                    <span class="text-[10px] text-stone-400 font-medium">1 / 3</span>
                                                </div>
                                            </label>
                                            <label class="cursor-pointer">
                                                <input type="radio" name="col_span" value="2"
                                                    :checked="(activeField.col_span ?? 1) == 2" class="sr-only peer">
                                                <div
                                                    class="flex flex-col items-center gap-1.5 px-3 py-2.5 rounded-lg border border-white/15 bg-white/5 peer-checked:border-red-500 peer-checked:bg-red-900/30 transition-colors text-center">
                                                    <div class="flex gap-0.5 w-full">
                                                        <div class="h-2 rounded-sm bg-red-500 w-2/3"></div>
                                                        <div class="h-2 rounded-sm bg-white/10 w-1/3"></div>
                                                    </div>
                                                    <span class="text-[10px] text-stone-400 font-medium">2 / 3</span>
                                                </div>
                                            </label>
                                            <label class="cursor-pointer">
                                                <input type="radio" name="col_span" value="3"
                                                    :checked="(activeField.col_span ?? 1) == 3" class="sr-only peer">
                                                <div
                                                    class="flex flex-col items-center gap-1.5 px-3 py-2.5 rounded-lg border border-white/15 bg-white/5 peer-checked:border-red-500 peer-checked:bg-red-900/30 transition-colors text-center">
                                                    <div class="flex gap-0.5 w-full">
                                                        <div class="h-2 rounded-sm bg-red-500 w-full"></div>
                                                    </div>
                                                    <span class="text-[10px] text-stone-400 font-medium">Full</span>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-2">
                                        {{-- Visibility Rules for Repeater --}}
                                        <div class="mt-3 pt-3 border-t border-white/10 space-y-3" x-data="{ visRules: activeField.visibility_rules?.rules || [], visLogic: activeField.visibility_rules?.logic || 'AND' }">
                                            <h4 class="text-xs font-semibold text-stone-300 uppercase tracking-wider flex items-center gap-1.5">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                                Visibility Rules
                                            </h4>
                                            <p class="text-[9px] text-stone-500">Show this field only when conditions are met.</p>
                                            <template x-if="visRules.length > 0">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <span class="text-[10px] text-stone-400">Match:</span>
                                                    <select x-model="visLogic" class="px-2 py-1 text-[10px] bg-stone-900 border border-white/20 rounded-lg outline-none text-white cursor-pointer">
                                                        <option value="AND">ALL (AND)</option>
                                                        <option value="OR">ANY (OR)</option>
                                                    </select>
                                                </div>
                                            </template>
                                            <div class="space-y-1.5">
                                                <template x-for="(rule, ri) in visRules" :key="ri">
                                                    <div class="flex items-center gap-1">
                                                        <select x-model="rule.field" class="flex-1 px-2 py-1 text-[10px] bg-stone-900 border border-white/20 rounded-lg outline-none text-white cursor-pointer">
                                                            <option value="">— Field —</option>
                                                            @foreach($fields as $f)
                                                                @if($f->field_key)
                                                                    <option value="{{ $f->field_key }}">{{ $f->field_key }}</option>
                                                                @endif
                                                            @endforeach
                                                        </select>
                                                        <select x-model="rule.operator" class="w-14 px-1 py-1 text-[10px] bg-stone-900 border border-white/20 rounded-lg outline-none text-white cursor-pointer">
                                                            <option value="==">==</option>
                                                            <option value="!=">!=</option>
                                                            <option value=">">></option>
                                                            <option value="<"><</option>
                                                            <option value="is_empty">empty</option>
                                                            <option value="is_not_empty">filled</option>
                                                        </select>
                                                        <input type="text" x-model="rule.value" placeholder="val" x-show="!['is_empty','is_not_empty'].includes(rule.operator)"
                                                            class="flex-1 px-2 py-1 text-[10px] bg-stone-900 border border-white/20 rounded-lg outline-none text-white placeholder-stone-600">
                                                        <button type="button" @click="visRules.splice(ri, 1)" class="w-5 h-5 flex items-center justify-center rounded bg-red-900/30 text-red-400 hover:bg-red-800 text-[9px]">×</button>
                                                    </div>
                                                </template>
                                            </div>
                                            <button type="button" @click="visRules.push({field:'', operator:'==', value:''})"
                                                class="text-[10px] text-stone-400 hover:text-stone-300 font-medium">+ Add Rule</button>
                                            <input type="hidden" name="visibility_rules_json" :value="JSON.stringify({logic: visLogic, rules: visRules})">
                                        </div>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <button type="submit"
                                            class="w-full py-2 rounded-lg bg-white/10 hover:bg-white/15 text-white text-xs font-medium transition-colors">
                                            Save Label / Key
                                        </button>
                                    </div>
                                </form>

                                <div class="border-t border-white/10 pt-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <p class="text-xs font-semibold text-stone-300">Columns</p>
                                        <button type="button" @click="addColumn()"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-800 hover:bg-red-700 text-white text-xs font-medium transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4v16m8-8H4" />
                                            </svg>
                                            Add Column
                                        </button>
                                    </div>

                                    <div class="space-y-2" id="columns-sortable">
                                        <template x-for="(col, idx) in columns" :key="idx">
                                            <div class="bg-white/5 border border-white/10 rounded-xl p-3 space-y-2" draggable="true"
                                                @dragstart="$event.dataTransfer.setData('text/plain', idx); $event.target.classList.add('opacity-50')"
                                                @dragend="$event.target.classList.remove('opacity-50')"
                                                @dragover.prevent="$event.target.closest('[draggable]')?.classList.add('border-red-500')"
                                                @dragleave="$event.target.closest('[draggable]')?.classList.remove('border-red-500')"
                                                @drop.prevent="
                                                    $event.target.closest('[draggable]')?.classList.remove('border-red-500');
                                                    const fromIdx = parseInt($event.dataTransfer.getData('text/plain'));
                                                    const toIdx = idx;
                                                    if (fromIdx !== toIdx) {
                                                        const item = columns.splice(fromIdx, 1)[0];
                                                        columns.splice(toIdx, 0, item);
                                                    }
                                                ">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center gap-2">
                                                        {{-- Drag handle --}}
                                                        <div class="cursor-grab active:cursor-grabbing text-stone-600 hover:text-stone-400 touch-manipulation">
                                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><circle cx="9" cy="5" r="1.5"/><circle cx="15" cy="5" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="19" r="1.5"/><circle cx="15" cy="19" r="1.5"/></svg>
                                                        </div>
                                                        <span class="text-[10px] font-semibold text-stone-400 uppercase tracking-wider" x-text="'Column ' + (idx + 1)"></span>
                                                    </div>
                                                    <div class="flex items-center gap-1">
                                                        {{-- Move up --}}
                                                        <button type="button" @click="if(idx > 0) { const item = columns.splice(idx, 1)[0]; columns.splice(idx-1, 0, item); }"
                                                            :class="idx === 0 ? 'opacity-30 cursor-not-allowed' : 'hover:text-white hover:bg-white/10'"
                                                            class="w-5 h-5 flex items-center justify-center rounded text-stone-500 transition-colors">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                                        </button>
                                                        {{-- Move down --}}
                                                        <button type="button" @click="if(idx < columns.length-1) { const item = columns.splice(idx, 1)[0]; columns.splice(idx+1, 0, item); }"
                                                            :class="idx === columns.length-1 ? 'opacity-30 cursor-not-allowed' : 'hover:text-white hover:bg-white/10'"
                                                            class="w-5 h-5 flex items-center justify-center rounded text-stone-500 transition-colors">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                        </button>
                                                        {{-- Delete --}}
                                                        <button type="button" @click="removeColumn(idx)"
                                                            class="w-5 h-5 flex items-center justify-center rounded text-stone-500 hover:text-red-400 hover:bg-red-900/30 transition-colors">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="grid grid-cols-2 gap-2">
                                                    <div>
                                                        <label class="block text-[10px] text-stone-500 mb-1">Label</label>
                                                        <input type="text" x-model="col.label"
                                                            @input="col.key = slugify(col.label)"
                                                            placeholder="e.g. Item Name"
                                                            class="w-full px-2 py-1.5 text-xs bg-white/10 border border-white/15 rounded-lg outline-none text-white placeholder-stone-600 focus:border-red-500 transition">
                                                    </div>
                                                    <div>
                                                        <label class="block text-[10px] text-stone-500 mb-1">Key <span
                                                                class="text-stone-600">(auto)</span></label>
                                                        <input type="text" x-model="col.key" placeholder="e.g. item_name"
                                                            class="w-full px-2 py-1.5 text-xs bg-white/10 border border-white/15 rounded-lg outline-none text-stone-400 placeholder-stone-600 focus:border-red-500 transition">
                                                    </div>
                                                </div>
                                                <div class="grid grid-cols-2 gap-2">
                                                    <div>
                                                        <label class="block text-[10px] text-stone-500 mb-1">Type</label>
                                                        <select x-model="col.type"
                                                            class="w-full px-2 py-1.5 text-xs bg-white/10 border border-white/15 rounded-lg outline-none text-white focus:border-red-500 transition cursor-pointer">
                                                            <option value="text" class="bg-stone-900 text-white">Text</option>
                                                            <option value="number" class="bg-stone-900 text-white">Number</option>
                                                            <option value="decimal" class="bg-stone-900 text-white">Decimal</option>
                                                            <option value="email" class="bg-stone-900 text-white">Email</option>
                                                            <option value="date" class="bg-stone-900 text-white">Date</option>
                                                            <option value="datetime" class="bg-stone-900 text-white">Date & Time</option>
                                                            <option value="time" class="bg-stone-900 text-white">Time</option>
                                                            <option value="select" class="bg-stone-900 text-white">Select</option>
                                                            <option value="textarea" class="bg-stone-900 text-white">Textarea</option>
                                                            <option value="checkbox" class="bg-stone-900 text-white">Checkbox</option>
                                                            <option value="formula" class="bg-stone-900 text-yellow-400">Formula (Auto-Calculate)</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="block text-[10px] text-stone-500 mb-1">Default</label>
                                                        <input type="text" x-model="col.default" placeholder="Default value"
                                                            class="w-full px-2 py-1.5 text-xs bg-white/10 border border-white/15 rounded-lg outline-none text-white placeholder-stone-600 focus:border-red-500 transition">
                                                    </div>
                                                </div>

                                                {{-- Formula expression input (shown when type is formula) --}}
                                                <template x-if="col.type === 'formula'">
                                                    <div class="mt-2 p-2 bg-yellow-900/20 border border-yellow-700/30 rounded-lg space-y-1.5">
                                                        <label class="block text-[10px] text-yellow-400 font-semibold">Formula Expression</label>
                                                        <input type="text" x-model="col.formula" placeholder="e.g. {qty} * {mrp} - {dis_flat}"
                                                            class="w-full px-2 py-1.5 text-xs bg-black/20 border border-yellow-700/40 rounded-lg outline-none text-yellow-200 placeholder-yellow-700 focus:border-yellow-500 transition font-mono">
                                                        <p class="text-[9px] text-stone-500">Use <code class="text-yellow-500">{column_key}</code> to reference other columns in the same row. Supports: +, -, *, /, (), SUM(), IF()</p>
                                                        <div class="flex flex-wrap gap-1 mt-1">
                                                            <template x-for="(otherCol, oi) in columns.filter(c => c.key !== col.key && c.type !== 'formula')" :key="oi">
                                                                <button type="button" @click="col.formula = (col.formula || '') + '{' + otherCol.key + '}'"
                                                                    class="px-1.5 py-0.5 text-[9px] bg-stone-700 text-stone-300 rounded hover:bg-stone-600 font-mono"
                                                                    x-text="'{' + otherCol.key + '}'"></button>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>

                                                {{-- Summary toggle for numeric/formula columns --}}
                                                <template x-if="['number', 'decimal', 'formula'].includes(col.type)">
                                                    <div class="mt-2 flex items-center gap-2">
                                                        <label class="flex items-center gap-1.5 cursor-pointer">
                                                            <input type="checkbox" x-model="col.show_summary" class="w-3 h-3 rounded border-white/20 bg-white/10 text-red-700 focus:ring-red-700 cursor-pointer">
                                                            <span class="text-[10px] text-stone-400">Show column total (SUM) in footer</span>
                                                        </label>
                                                    </div>
                                                </template>

                                                <div x-show="['select', 'checkbox'].includes(col.type)" x-cloak class="mt-2 space-y-2">
                                                        {{-- Mode toggle: Static vs Database --}}
                                                        <div class="flex items-center justify-between">
                                                            <span class="text-[9px] font-semibold text-stone-500 uppercase">Options Source</span>
                                                            <button type="button" @click="if(!col.dynamic) col.dynamic = {enabled:false,table:'',label_col:'',value_col:'',load_mode:'preload'}; col.dynamic.enabled = !col.dynamic.enabled"
                                                                :class="col.dynamic?.enabled ? 'bg-red-800 text-white' : 'bg-white/10 text-stone-400'"
                                                                class="px-2 py-0.5 rounded text-[8px] font-bold uppercase transition-colors">
                                                                <span x-text="col.dynamic?.enabled ? 'Database' : 'Static'"></span>
                                                            </button>
                                                        </div>

                                                        {{-- Static options --}}
                                                        <template x-if="!col.dynamic?.enabled">
                                                            <div class="space-y-1">
                                                                <template x-for="(opt, oIdx) in (col.options || [])" :key="oIdx">
                                                                    <div class="flex items-center gap-1">
                                                                        <input type="text" x-model="opt.label" placeholder="Label"
                                                                            class="flex-1 px-1.5 py-1 text-[10px] bg-white/5 border border-white/10 rounded outline-none text-white focus:border-red-500">
                                                                        <input type="text" x-model="opt.value" placeholder="Value"
                                                                            class="flex-1 px-1.5 py-1 text-[10px] bg-white/5 border border-white/10 rounded outline-none text-white focus:border-red-500">
                                                                        <button type="button" @click="col.options.splice(oIdx,1)"
                                                                            class="text-stone-600 hover:text-red-400">×</button>
                                                                    </div>
                                                                </template>
                                                                <button type="button" @click="if(!col.options) col.options = []; col.options.push({label:'', value:''})"
                                                                    class="text-[9px] text-red-400 hover:text-red-300">+ Add Option</button>
                                                            </div>
                                                        </template>

                                                        {{-- Database options --}}
                                                        <template x-if="col.dynamic?.enabled">
                                                            <div class="space-y-2 bg-white/5 p-2 rounded-lg border border-white/5" x-data="{ colCols: [] }" x-init="
                                                                if(col.dynamic?.table) { fetch('/master/page-builder/get-columns?table=' + col.dynamic.table).then(r=>r.json()).then(d => colCols = d); }
                                                                $watch('col.dynamic.table', (t) => { if(t) fetch('/master/page-builder/get-columns?table=' + t).then(r=>r.json()).then(d => colCols = d); else colCols = []; })
                                                            ">
                                                                <div>
                                                                    <label class="block text-[9px] text-stone-500 mb-0.5">Table</label>
                                                                    <select x-model="col.dynamic.table" class="w-full px-2 py-1 text-[10px] bg-stone-900 border border-white/20 rounded-lg outline-none text-white cursor-pointer">
                                                                        <option value="">— Table —</option>
                                                                        @foreach($tables as $table)
                                                                            <option value="{{ $table }}">{{ $table }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="grid grid-cols-2 gap-1.5">
                                                                    <div>
                                                                        <label class="block text-[9px] text-stone-500 mb-0.5">Label Col</label>
                                                                        <select x-model="col.dynamic.label_col" class="w-full px-2 py-1 text-[10px] bg-stone-900 border border-white/20 rounded-lg outline-none text-white cursor-pointer">
                                                                            <option value="">— Column —</option>
                                                                            <template x-for="c in colCols" :key="c">
                                                                                <option :value="c" x-text="c" :selected="c === col.dynamic.label_col"></option>
                                                                            </template>
                                                                        </select>
                                                                    </div>
                                                                    <div>
                                                                        <label class="block text-[9px] text-stone-500 mb-0.5">Value Col</label>
                                                                        <select x-model="col.dynamic.value_col" class="w-full px-2 py-1 text-[10px] bg-stone-900 border border-white/20 rounded-lg outline-none text-white cursor-pointer">
                                                                            <option value="">— Column —</option>
                                                                            <template x-for="c in colCols" :key="c">
                                                                                <option :value="c" x-text="c" :selected="c === col.dynamic.value_col"></option>
                                                                            </template>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                {{-- Loading Mode --}}
                                                                <div>
                                                                    <label class="block text-[9px] text-stone-500 mb-0.5">Load Mode</label>
                                                                    <div class="grid grid-cols-3 gap-1">
                                                                        <label class="cursor-pointer">
                                                                            <input type="radio" x-model="col.dynamic.load_mode" value="preload" class="sr-only peer">
                                                                            <div class="px-1.5 py-1 text-center text-[8px] font-medium rounded border border-white/15 bg-white/5 peer-checked:border-red-500 peer-checked:bg-red-900/30 peer-checked:text-white text-stone-500 transition-colors">Preload</div>
                                                                        </label>
                                                                        <label class="cursor-pointer">
                                                                            <input type="radio" x-model="col.dynamic.load_mode" value="server_search" class="sr-only peer">
                                                                            <div class="px-1.5 py-1 text-center text-[8px] font-medium rounded border border-white/15 bg-white/5 peer-checked:border-red-500 peer-checked:bg-red-900/30 peer-checked:text-white text-stone-500 transition-colors">Server</div>
                                                                        </label>
                                                                        <label class="cursor-pointer">
                                                                            <input type="radio" x-model="col.dynamic.load_mode" value="select2" class="sr-only peer">
                                                                            <div class="px-1.5 py-1 text-center text-[8px] font-medium rounded border border-white/15 bg-white/5 peer-checked:border-red-500 peer-checked:bg-red-900/30 peer-checked:text-white text-stone-500 transition-colors">Select2</div>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                                {{-- Search cols for server modes --}}
                                                                <template x-if="col.dynamic.load_mode === 'server_search' || col.dynamic.load_mode === 'select2'">
                                                                    <div>
                                                                        <label class="block text-[9px] text-stone-500 mb-0.5">Search Cols</label>
                                                                        <input type="text" x-model="col.dynamic.search_cols" placeholder="name,code"
                                                                            class="w-full px-2 py-1 text-[10px] bg-stone-900 border border-white/20 rounded-lg outline-none text-white placeholder-stone-600 font-mono">
                                                                    </div>
                                                                </template>
                                                                {{-- Auto-fill within row --}}
                                                                <div class="pt-1.5 border-t border-white/10">
                                                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                                                        <input type="checkbox" x-model="col.auto_fill_enabled" class="w-3 h-3 rounded border-white/20 bg-white/10 text-red-700 focus:ring-red-700 cursor-pointer">
                                                                        <span class="text-[9px] text-stone-400">Auto-fill other columns in same row</span>
                                                                    </label>
                                                                    <template x-if="col.auto_fill_enabled">
                                                                        <div class="mt-1.5 space-y-1">
                                                                            <template x-for="(af, afi) in (col.auto_fill_mappings || [])" :key="afi">
                                                                                <div class="flex items-center gap-1">
                                                                                    <select x-model="af.source_col" class="flex-1 px-1.5 py-0.5 text-[9px] bg-stone-900 border border-white/20 rounded outline-none text-white cursor-pointer">
                                                                                        <option value="">— DB Col —</option>
                                                                                        <template x-for="c in colCols" :key="c">
                                                                                            <option :value="c" x-text="c"></option>
                                                                                        </template>
                                                                                    </select>
                                                                                    <span class="text-[8px] text-stone-600">→</span>
                                                                                    <select x-model="af.target_col_key"
                                                                                        class="flex-1 px-1.5 py-0.5 text-[9px] bg-stone-900 border border-white/20 rounded outline-none text-red-300 cursor-pointer">
                                                                                        <option value="">— Col Key —</option>
                                                                                        <template x-for="(otherCol, oci) in columns.filter(c => c.key !== col.key)" :key="oci">
                                                                                            <option :value="otherCol.key" x-text="otherCol.key + ' (' + otherCol.label + ')'"></option>
                                                                                        </template>
                                                                                    </select>
                                                                                    <button type="button" @click="col.auto_fill_mappings.splice(afi,1)" class="text-stone-600 hover:text-red-400 text-[9px]">×</button>
                                                                                </div>
                                                                            </template>
                                                                            <button type="button" @click="if(!col.auto_fill_mappings) col.auto_fill_mappings = []; col.auto_fill_mappings.push({source_col:'',target_col_key:''})"
                                                                                class="text-[9px] text-red-400 hover:text-red-300">+ Add</button>
                                                                        </div>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>

                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="checkbox" x-model="col.required"
                                                        class="w-3.5 h-3.5 rounded border-white/20 bg-white/10 text-red-700 focus:ring-red-700 cursor-pointer">
                                                    <span class="text-xs text-stone-400">Required</span>
                                                </label>
                                            </div>
                                        </template>
                                    </div>

                                    <form method="POST" :action="activeField.repeater_url" class="mt-4"
                                        @submit.prevent="submitRepeater($event)">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="columns_json" :value="JSON.stringify(columns)">
                                        <button type="submit"
                                            class="w-full py-2.5 rounded-xl bg-red-800 hover:bg-red-700 text-white text-sm font-semibold transition-colors">
                                            Save Columns
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </template>

                    </div>
                </template>
            </div>
        </div>

    </div>

@endsection

{{-- ══════════════════════════════════════════════════
SCRIPTS
══════════════════════════════════════════════════ --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>

    /* ─── Drag & Reorder with SortableJS ─── */
    document.addEventListener('DOMContentLoaded', () => {
        const grid = document.getElementById('sortable-fields');
        if (!grid) return;

        const reorderUrl = grid.dataset.reorderUrl;

        // Watch Alpine's reorderMode to enable/disable sorting
        const getAlpine = () => {
            const root = document.querySelector('[x-data]');
            return root ? Alpine.$data(root) : null;
        };

        let sortable = null;

        const initSortable = () => {
            sortable = Sortable.create(grid, {
                animation: 180,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                handle: '.drag-handle',
                disabled: true, // start disabled; enabled when reorderMode=true

                onEnd(evt) {
                    // Collect new order
                    const ids = [...grid.querySelectorAll('.field-item')].map(el => el.dataset.id);

                    // Show saving spinner via Alpine
                    const data = getAlpine();
                    if (data) data.reorderSaving = true;

                    // POST new order to server
                    fetch(reorderUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ order: ids }),
                    })
                        .then(res => res.ok ? res.json() : Promise.reject(res))
                        .catch(err => console.error('Reorder failed', err))
                        .finally(() => {
                            if (data) data.reorderSaving = false;
                        });
                }
            });
        };

        initSortable();

        // Watch reorderMode toggle via MutationObserver + Alpine magic
        // We observe the hint banner visibility as a proxy for reorderMode
        const hintBanner = document.querySelector('[x-show="reorderMode"]');
        if (hintBanner && sortable) {
            const observer = new MutationObserver(() => {
                const isReordering = hintBanner.style.display !== 'none';
                sortable.option('disabled', !isReordering);
            });
            observer.observe(hintBanner, { attributes: true, attributeFilter: ['style'] });
        }
    });
</script>

<style>
    /* Ghost placeholder while dragging */
    .sortable-ghost {
        opacity: 0.4;
        background: #fef3c7;
        border-radius: 0.5rem;
        border: 2px dashed #f59e0b;
    }

    /* Chosen item (before lift) */
    .sortable-chosen {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        border-radius: 0.5rem;
    }

    /* Dragging item */
    .sortable-drag {
        opacity: 0.95;
        transform: rotate(1deg);
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.18);
    }

    /* Smooth column transitions during sort */
    #sortable-fields .field-item {
        transition: transform 0.15s ease;
    }

    /* Touch-friendly tap targets */
    @media (max-width: 1023px) {
        .group:hover .opacity-0 {
            opacity: 1 !important;
        }
    }
</style>