@extends('layouts.app')
@section('title', 'Preview: ' . $page->page_name)
@section('content')
@php
    use App\Helpers\PageFieldHelper;
    $settings = $page->settings ?? [];
    $currency = $settings['currency'] ?? '₹';
    $precision = $settings['decimal_precision'] ?? 2;
@endphp

<div class="max-w-5xl mx-auto">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="text-lg font-bold text-stone-800">{{ $page->page_name }} — Preview</h2>
            <p class="text-xs text-stone-400 mt-0.5">Live preview with formula calculations</p>
        </div>
        <a href="{{ route('master.page-builder.fields', $page) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-stone-100 text-stone-600 hover:bg-stone-200 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Builder
        </a>
    </div>

    {{-- Form --}}
    <div class="bg-white border border-stone-200 rounded-xl overflow-hidden shadow-sm">
        <div class="px-6 py-4 border-b border-stone-100 bg-stone-50">
            <h3 class="text-sm font-semibold text-stone-700">{{ $settings['title'] ?? $page->page_name }}</h3>
            @if(!empty($settings['description']))
                <p class="text-xs text-stone-400 mt-0.5">{{ $settings['description'] }}</p>
            @endif
        </div>

        <div class="p-6">
            <div class="grid grid-cols-3 gap-5" id="form-preview">
                @foreach($fields as $field)
                    @php
                        $fieldKey = $field->field_key ?? PageFieldHelper::inputName($field);
                        $colSpan = 'col-span-' . ($field->col_span ?? 1);
                        $isFormula = $field->field_type === 'formula';
                        $isSummary = $field->field_type === 'summary';
                        $visRules = $field->visibility_rules ?? null;
                        $hasVisibility = !empty($visRules) && !empty($visRules['rules']);
                    @endphp

                    @if($hasVisibility)
                    <div class="{{ $colSpan }} visibility-field" data-visibility-rules="{{ json_encode($visRules) }}" data-field-wrapper="{{ $fieldKey }}">
                    @endif

                    @if($isSummary)
                        {{-- Summary Block --}}
                        <div class="{{ $colSpan }}">
                            <div class="bg-stone-50 border border-stone-200 rounded-xl p-4 ml-auto" style="max-width: 380px; float: right;">
                                <h4 class="text-xs font-bold text-stone-500 uppercase tracking-wider mb-3">{{ $field->label ?? 'Summary' }}</h4>
                                @foreach(($field->summary_config['lines'] ?? []) as $line)
                                    <div class="flex items-center justify-between py-1.5 {{ $line['style'] === 'bold' ? 'border-t-2 border-stone-300 pt-2 mt-1' : '' }}">
                                        <span class="text-{{ $line['style'] === 'bold' ? 'sm font-bold' : ($line['style'] === 'small' ? '[11px]' : 'xs') }} text-stone-600">{{ $line['label'] }}</span>
                                        <span class="text-{{ $line['style'] === 'bold' ? 'sm font-bold' : 'xs' }} text-stone-800 formula-display" data-formula="{{ $line['formula'] }}">{{ $currency }} 0.00</span>
                                    </div>
                                @endforeach
                            </div>
                            <div class="clear-both"></div>
                        </div>
                    @elseif($isFormula)
                        {{-- Formula Field (read-only calculated) --}}
                        <div class="{{ $colSpan }}">
                            <label class="block text-sm font-medium text-stone-700 mb-1.5">{{ $field->label ?? $field->field_name }}</label>
                            <div class="w-full px-3.5 py-2.5 text-sm border rounded-xl bg-stone-50 border-stone-200 text-stone-800 font-semibold formula-display" data-field-key="{{ $fieldKey }}" data-formula="{{ $field->formula['expression'] ?? '' }}">
                                {{ $currency }} 0.00
                            </div>
                        </div>
                    @elseif($field->field_type === 'repeater')
                        {{-- Repeater/Table --}}
                        <div class="{{ $colSpan }}">
                            <label class="block text-sm font-medium text-stone-700 mb-1.5">{{ $field->label ?? $field->field_name }}</label>
                            <div class="border border-stone-200 rounded-xl overflow-hidden" x-data="repeaterCalc('{{ $fieldKey }}')">
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead class="bg-stone-800 text-white">
                                            <tr>
                                                <th class="px-2 py-2 text-xs font-semibold w-8">#</th>
                                                @foreach($field->repeater_columns ?? [] as $col)
                                                    <th class="px-2 py-2 text-xs font-semibold whitespace-nowrap">
                                                        {{ $col['label'] }}
                                                        @if(!empty($col['required'])) <span class="text-red-400">*</span> @endif
                                                    </th>
                                                @endforeach
                                                <th class="px-2 py-2 w-8"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="repeater-{{ $fieldKey }}-body">
                                            <template x-for="(row, idx) in rows" :key="idx">
                                                <tr class="border-t border-stone-100">
                                                    <td class="px-2 py-1.5 text-stone-400 text-xs" x-text="idx + 1"></td>
                                                    @foreach($field->repeater_columns ?? [] as $col)
                                                        @if(($col['type'] ?? 'text') === 'formula')
                                                            <td class="px-1 py-1">
                                                                <div class="w-full h-[30px] px-2 py-1.5 text-xs border border-stone-200 rounded-lg bg-stone-50 text-stone-700 font-medium text-right flex items-center justify-end"
                                                                    x-text="calcRowFormula(idx, '{{ addslashes($col['formula'] ?? '') }}', '{{ $col['key'] }}')">
                                                                </div>
                                                                <input type="hidden" :name="'{{ $fieldKey }}[' + idx + '][{{ $col['key'] }}]'" :value="rows[idx]?.{{ $col['key'] }} || 0">
                                                            </td>
                                                        @elseif(($col['type'] ?? 'text') === 'select')
                                                            @php
                                                                $colDynamic = $col['dynamic'] ?? null;
                                                                $colHasDynamic = !empty($colDynamic['enabled']) && !empty($colDynamic['table']);
                                                                $colLoadMode = $colDynamic['load_mode'] ?? 'preload';
                                                                $colSelectOptions = [];
                                                                if ($colHasDynamic && $colLoadMode === 'preload') {
                                                                    $colTable = $colDynamic['table'];
                                                                    $colLabelCol = $colDynamic['label_col'] ?? 'name';
                                                                    $colValueCol = $colDynamic['value_col'] ?? 'id';
                                                                    if (\Illuminate\Support\Facades\Schema::hasTable($colTable)) {
                                                                        $colSelectOptions = \Illuminate\Support\Facades\DB::table($colTable)
                                                                            ->select($colValueCol, $colLabelCol)
                                                                            ->limit(100)
                                                                            ->get()
                                                                            ->map(fn($r) => ['value' => $r->$colValueCol, 'label' => $r->$colLabelCol])
                                                                            ->toArray();
                                                                    }
                                                                } elseif (!$colHasDynamic) {
                                                                    $colSelectOptions = $col['options'] ?? [];
                                                                }
                                                                $colIsServerMode = $colHasDynamic && in_array($colLoadMode, ['server_search', 'select2']);
                                                            @endphp

                                                            @if($colIsServerMode)
                                                                {{-- Server search mode for repeater column --}}
                                                                <td class="px-1 py-1">
                                                                    <div class="relative" x-data="{ srOpen: false, srOptions: [], srText: '' }">
                                                                        <input type="text" x-model="srText"
                                                                            @input.debounce.300ms="
                                                                                if(srText.length >= {{ $colDynamic['min_chars'] ?? 2 }}) {
                                                                                    fetch('/master/page-builder/search-options?table={{ $colDynamic['table'] }}&search=' + srText + '&label_col={{ $colDynamic['label_col'] ?? 'name' }}&value_col={{ $colDynamic['value_col'] ?? 'id' }}&search_cols={{ $colDynamic['search_cols'] ?? $colDynamic['label_col'] ?? 'name' }}&limit={{ $colDynamic['max_results'] ?? 20 }}')
                                                                                    .then(r=>r.json()).then(d => { srOptions = d; srOpen = true; });
                                                                                } else { srOptions = []; srOpen = false; }
                                                                            "
                                                                            @focus="if(srOptions.length) srOpen = true"
                                                                            @click.away="srOpen = false"
                                                                            placeholder="Search..."
                                                                            class="w-full h-[30px] px-2 py-1 text-xs border border-stone-300 rounded-lg outline-none focus:border-red-700">
                                                                        <div x-show="srOpen && srOptions.length > 0" x-cloak
                                                                            class="absolute z-50 mt-0.5 w-48 bg-white border border-stone-200 rounded-lg shadow-lg max-h-32 overflow-y-auto">
                                                                            <template x-for="opt in srOptions" :key="opt.id">
                                                                                <div @click="rows[idx].{{ $col['key'] }} = opt.id; srText = opt.text; srOpen = false; {{ !empty($col['auto_fill_enabled']) ? 'handleColAutoFill(idx, \'' . $col['key'] . '\', opt.id)' : 'recalcAll()' }}"
                                                                                    class="px-2 py-1.5 text-xs text-stone-700 hover:bg-red-50 hover:text-red-700 cursor-pointer" x-text="opt.text"></div>
                                                                            </template>
                                                                        </div>
                                                                        <input type="hidden" :name="'{{ $fieldKey }}[' + idx + '][{{ $col['key'] }}]'" :value="rows[idx].{{ $col['key'] }}">
                                                                    </div>
                                                                </td>
                                                            @else
                                                                {{-- Preload / static select --}}
                                                                <td class="px-1 py-1">
                                                                    <select x-model="rows[idx].{{ $col['key'] }}" @change="recalcAll(); {{ !empty($col['auto_fill_enabled']) ? 'handleColAutoFill(idx, \'' . $col['key'] . '\', $event.target.value)' : '' }}"
                                                                        :name="'{{ $fieldKey }}[' + idx + '][{{ $col['key'] }}]'"
                                                                        class="w-full h-[30px] px-2 py-1 text-xs border border-stone-300 rounded-lg outline-none focus:border-red-700">
                                                                        <option value="">—</option>
                                                                        @foreach($colSelectOptions as $opt)
                                                                            <option value="{{ is_array($opt) ? ($opt['value'] ?? '') : $opt }}">{{ is_array($opt) ? ($opt['label'] ?? $opt['value'] ?? '') : $opt }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </td>
                                                            @endif
                                                        @elseif(in_array($col['type'] ?? 'text', ['number', 'decimal']))
                                                            <td class="px-1 py-1">
                                                                <input type="number" step="{{ ($col['type'] ?? '') === 'decimal' ? '0.01' : '1' }}"
                                                                    x-model.number="rows[idx].{{ $col['key'] }}" @input="recalcAll()"
                                                                    :name="'{{ $fieldKey }}[' + idx + '][{{ $col['key'] }}]'"
                                                                    placeholder="{{ $col['default'] ?? '0' }}"
                                                                    class="w-full h-[30px] px-2 py-1 text-xs border border-stone-300 rounded-lg outline-none focus:border-red-700 text-right">
                                                            </td>
                                                        @elseif(in_array($col['type'] ?? 'text', ['date', 'datetime', 'time']))
                                                            <td class="px-1 py-1">
                                                                <input type="{{ $col['type'] === 'datetime' ? 'datetime-local' : $col['type'] }}"
                                                                    x-model="rows[idx].{{ $col['key'] }}" @input="recalcAll()"
                                                                    :name="'{{ $fieldKey }}[' + idx + '][{{ $col['key'] }}]'"
                                                                    class="w-full h-[30px] px-2 py-1 text-xs border border-stone-300 rounded-lg outline-none focus:border-red-700">
                                                            </td>
                                                        @else
                                                            <td class="px-1 py-1">
                                                                <input type="text"
                                                                    x-model="rows[idx].{{ $col['key'] }}" @input="recalcAll()"
                                                                    :name="'{{ $fieldKey }}[' + idx + '][{{ $col['key'] }}]'"
                                                                    placeholder="{{ $col['default'] ?? '' }}"
                                                                    class="w-full h-[30px] px-2 py-1 text-xs border border-stone-300 rounded-lg outline-none focus:border-red-700">
                                                            </td>
                                                        @endif
                                                    @endforeach
                                                    <td class="px-1 py-1 text-center">
                                                        <button type="button" @click="removeRow(idx)" class="w-6 h-6 inline-flex items-center justify-center rounded bg-red-100 hover:bg-red-200 text-red-600 text-xs">−</button>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                        {{-- Summary row --}}
                                        <tfoot class="bg-stone-50 border-t-2 border-stone-300">
                                            <tr>
                                                <td class="px-2 py-2 text-xs font-bold text-stone-500" colspan="1"></td>
                                                @foreach($field->repeater_columns ?? [] as $col)
                                                    @if(($col['type'] ?? 'text') === 'formula' || in_array($col['type'] ?? '', ['number', 'decimal']))
                                                        <td class="px-1 py-2 text-right">
                                                            <span class="text-xs font-bold text-stone-700" x-text="colSum('{{ $col['key'] }}').toFixed({{ $precision }})"></span>
                                                        </td>
                                                    @else
                                                        <td class="px-1 py-2"></td>
                                                    @endif
                                                @endforeach
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <div class="px-3 py-2 bg-stone-50 border-t border-stone-100">
                                    <button type="button" @click="addRow()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-stone-800 hover:bg-stone-700 text-white text-xs font-medium transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Add Row
                                    </button>
                                </div>
                            </div>
                        </div>
                    @elseif($field->field_type === 'divider')
                        <div class="col-span-3 border-t border-stone-200 my-2"></div>
                    @elseif($field->field_type === 'content')
                        <div class="{{ $colSpan }}">
                            <label class="block text-sm font-medium text-stone-700 mb-1.5">{{ $field->label ?? $field->field_name }}</label>
                            <textarea placeholder="{{ $field->placeholder ?? '' }}" data-field-key="{{ $fieldKey }}" oninput="window.recalcFormulas()" class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10 resize-none min-h-[80px]"></textarea>
                        </div>
                    @elseif($field->field_type === 'select')
                        @php
                            $autoFill = $field->auto_fill ?? null;
                            $dynOpts = $field->options['dynamic'] ?? [];
                            $loadMode = $dynOpts['load_mode'] ?? 'preload';
                            $isServerSearch = in_array($loadMode, ['server_search', 'select2']);
                        @endphp
                        <div class="{{ $colSpan }}">
                            <label class="block text-sm font-medium text-stone-700 mb-1.5">{{ $field->label ?? $field->field_name }} @if($field->is_required)<span class="text-red-500">*</span>@endif</label>

                            @if($isServerSearch)
                                {{-- Server-side searchable select --}}
                                <div class="relative" x-data="searchSelect_{{ Str::camel($fieldKey) }}()" @click.away="open = false">
                                    <input type="text" x-model="searchText" @input.debounce.300ms="fetchOptions()" @focus="open = true"
                                        placeholder="Type to search..."
                                        class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10">
                                    <input type="hidden" data-field-key="{{ $fieldKey }}" :value="selectedId"
                                        @if($autoFill && !empty($autoFill['enabled']))
                                            data-autofill-table="{{ $autoFill['source_table'] ?? '' }}"
                                            data-autofill-mappings="{{ json_encode($autoFill['mappings'] ?? []) }}"
                                        @endif
                                    >
                                    {{-- Dropdown --}}
                                    <div x-show="open && options.length > 0" x-cloak
                                        class="absolute z-50 mt-1 w-full bg-white border border-stone-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                        <template x-for="opt in options" :key="opt.id">
                                            <div @click="selectOption(opt)" class="px-3 py-2 text-sm text-stone-700 hover:bg-red-50 hover:text-red-700 cursor-pointer transition-colors" x-text="opt.text"></div>
                                        </template>
                                    </div>
                                    <div x-show="open && searching" class="absolute z-50 mt-1 w-full bg-white border border-stone-200 rounded-xl shadow-lg px-3 py-3 text-xs text-stone-400">Searching...</div>
                                    {{-- Selected badge --}}
                                    <div x-show="selectedId && !open" class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1">
                                        <span class="text-[10px] px-2 py-0.5 bg-red-50 text-red-700 rounded-full font-medium" x-text="selectedText"></span>
                                        <button type="button" @click="clearSelection()" class="text-stone-400 hover:text-red-600">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </div>
                            @else
                                {{-- Standard preload select --}}
                                <select data-field-key="{{ $fieldKey }}"
                                    @if($autoFill && !empty($autoFill['enabled']))
                                        data-autofill-table="{{ $autoFill['source_table'] ?? '' }}"
                                        data-autofill-mappings="{{ json_encode($autoFill['mappings'] ?? []) }}"
                                        onchange="window.handleAutoFill(this); window.recalcFormulas()"
                                    @else
                                        onchange="window.recalcFormulas()"
                                    @endif
                                    class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10">
                                    <option value="">— Select —</option>
                                    @foreach(PageFieldHelper::resolveOptions($field) as $opt)
                                        <option value="{{ is_array($opt) ? ($opt['value'] ?? '') : $opt }}">{{ is_array($opt) ? ($opt['label'] ?? '') : $opt }}</option>
                                    @endforeach
                                </select>
                            @endif

                            @if($autoFill && !empty($autoFill['enabled']))
                                <p class="mt-1 text-[10px] text-stone-400">Auto-fills: {{ collect($autoFill['mappings'] ?? [])->pluck('target_field_key')->implode(', ') }}</p>
                            @endif
                        </div>
                    @elseif($field->field_type === 'radio')
                        <div class="{{ $colSpan }}">
                            <label class="block text-sm font-medium text-stone-700 mb-1.5">{{ $field->label ?? $field->field_name }}</label>
                            <div class="flex flex-wrap gap-4 mt-1">
                                @foreach(PageFieldHelper::resolveOptions($field) as $opt)
                                    @php $optVal = is_array($opt) ? ($opt['value'] ?? '') : $opt; $optLabel = is_array($opt) ? ($opt['label'] ?? '') : $opt; @endphp
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="{{ $fieldKey }}" value="{{ $optVal }}" class="text-red-700 focus:ring-red-700" {{ ($field->default_value ?? '') === $optVal ? 'checked' : '' }}>
                                        <span class="text-sm text-stone-600">{{ $optLabel }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @else
                        {{-- Standard input fields --}}
                        <div class="{{ $colSpan }}">
                            <label class="block text-sm font-medium text-stone-700 mb-1.5">{{ $field->label ?? $field->field_name }} @if($field->is_required)<span class="text-red-500">*</span>@endif</label>
                            <input type="{{ PageFieldHelper::htmlInputType($field->field_type) }}"
                                data-field-key="{{ $fieldKey }}"
                                oninput="window.recalcFormulas()"
                                placeholder="{{ $field->placeholder ?? '' }}"
                                value="{{ PageFieldHelper::defaultValue($field) }}"
                                {{ $field->field_type === 'number' || $field->field_type === 'decimal' || $field->field_type === 'currency' ? 'step=' . PageFieldHelper::stepValue($field->field_type) : '' }}
                                class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10">
                        </div>
                    @endif

                    @if($hasVisibility)
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="/js/formula-engine.js"></script>
<script>
document.addEventListener('alpine:init', () => {
    @php
        // Build a map of repeater field keys to their columns for JS
        $repeaterColumnsMap = $fields->where('field_type', 'repeater')->mapWithKeys(function($f) {
            $key = $f->field_key ?? \App\Helpers\PageFieldHelper::inputName($f);
            return [$key => $f->repeater_columns ?? []];
        })->toArray();
    @endphp

    {{-- Server-side search select components --}}
    @foreach($fields->where('field_type', 'select') as $sf)
        @php
            $sfKey = $sf->field_key ?? \App\Helpers\PageFieldHelper::inputName($sf);
            $sfDyn = $sf->options['dynamic'] ?? [];
            $sfMode = $sfDyn['load_mode'] ?? 'preload';
            $sfAutoFill = $sf->auto_fill ?? null;
        @endphp
        @if(in_array($sfMode, ['server_search', 'select2']))
    Alpine.data('searchSelect_{{ Str::camel($sfKey) }}', () => ({
        open: false,
        searching: false,
        searchText: '',
        options: [],
        selectedId: '',
        selectedText: '',

        async fetchOptions() {
            const q = this.searchText.trim();
            if (q.length < {{ $sfDyn['min_chars'] ?? 2 }}) { this.options = []; return; }
            this.searching = true;
            try {
                const params = new URLSearchParams({
                    table: '{{ $sfDyn['table'] ?? '' }}',
                    search: q,
                    label_col: '{{ $sfDyn['label_col'] ?? 'name' }}',
                    value_col: '{{ $sfDyn['value_col'] ?? 'id' }}',
                    search_cols: '{{ $sfDyn['search_cols'] ?? $sfDyn['label_col'] ?? 'name' }}',
                    limit: '{{ $sfDyn['max_results'] ?? 20 }}',
                });
                const res = await fetch('/master/page-builder/search-options?' + params);
                this.options = await res.json();
            } catch(e) { this.options = []; }
            this.searching = false;
            this.open = true;
        },

        selectOption(opt) {
            this.selectedId = opt.id;
            this.selectedText = opt.text;
            this.searchText = opt.text;
            this.open = false;
            this.options = [];
            // Trigger auto-fill if configured
            @if($sfAutoFill && !empty($sfAutoFill['enabled']))
            this.$nextTick(() => {
                const table = '{{ $sfAutoFill['source_table'] ?? '' }}';
                const mappings = @json($sfAutoFill['mappings'] ?? []);
                if (table && opt.id && mappings.length > 0) {
                    const cols = mappings.map(m => m.source_column).join(',');
                    fetch('/master/page-builder/lookup?table=' + table + '&id=' + opt.id + '&columns=' + cols)
                        .then(r => r.json())
                        .then(data => {
                            if (data && Object.keys(data).length > 0) {
                                mappings.forEach(m => {
                                    const target = document.querySelector('[data-field-key="' + m.target_field_key + '"]');
                                    if (target && data[m.source_column] !== undefined) {
                                        target.value = data[m.source_column] || '';
                                        if (m.readonly) {
                                            target.readOnly = true;
                                            target.classList.add('bg-stone-100');
                                        }
                                        target.dispatchEvent(new Event('input'));
                                    }
                                });
                            }
                            window.recalcFormulas();
                        });
                }
            });
            @else
            window.recalcFormulas();
            @endif
        },

        clearSelection() {
            this.selectedId = '';
            this.selectedText = '';
            this.searchText = '';
            this.options = [];
            // Clear auto-filled fields
            @if($sfAutoFill && !empty($sfAutoFill['enabled']))
            const mappings = @json($sfAutoFill['mappings'] ?? []);
            mappings.forEach(m => {
                const target = document.querySelector('[data-field-key="' + m.target_field_key + '"]');
                if (target) {
                    target.value = '';
                    target.readOnly = false;
                    target.classList.remove('bg-stone-100');
                    target.dispatchEvent(new Event('input'));
                }
            });
            @endif
            window.recalcFormulas();
        }
    }));
        @endif
    @endforeach

    Alpine.data('repeaterCalc', (fieldKey) => ({
        rows: [{}],
        columns: @json($repeaterColumnsMap)[fieldKey] || [],
        _tick: 0,

        init() {
            // Initialize first row with defaults
            this.rows = [this.makeRow()];
            // Listen for form-level value changes to recalculate row formulas
            document.addEventListener('form-values-changed', () => {
                this._tick++;
            });
        },

        makeRow() {
            const row = {};
            this.columns.forEach(col => {
                row[col.key] = col.type === 'number' || col.type === 'decimal' ? (parseFloat(col.default) || 0) : (col.default || '');
            });
            return row;
        },

        addRow() {
            this.rows.push(this.makeRow());
            this.$nextTick(() => this.recalcAll());
        },

        removeRow(idx) {
            if (this.rows.length > 1) {
                this.rows.splice(idx, 1);
                this.$nextTick(() => this.recalcAll());
            }
        },

        calcRowFormula(idx, formula, colKey) {
            if (!formula) return '0.00';
            // Access rows[idx] properties directly so Alpine tracks them
            const row = this.rows[idx];
            if (!row) return '0.00';
            
            // Build values from row data
            const values = {};
            this.columns.forEach(c => {
                const v = row[c.key];
                values[c.key] = (v !== undefined && v !== '' && !isNaN(v)) ? Number(v) : (v || 0);
            });
            
            // Add form-level field values
            document.querySelectorAll('[data-field-key]').forEach(el => {
                const key = el.dataset.fieldKey;
                if (!(key in values)) {
                    const v = el.value;
                    values[key] = (v !== '' && !isNaN(v)) ? Number(v) : (v || 0);
                }
            });

            // Reference _tick for form-level reactivity
            void this._tick;
            
            const engine = new FormulaEngine({ precision: {{ $precision }} });
            const result = engine.evaluate(formula, values);
            
            // Store result back in row for dependent formulas
            if (result !== null && colKey) {
                row[colKey] = result;
            }
            
            return result !== null ? result.toFixed({{ $precision }}) : '0.00';
        },

        colSum(key) {
            return this.rows.reduce((sum, row) => sum + (parseFloat(row[key]) || 0), 0);
        },

        recalcAll() {
            // Trigger formula recalculation for all rows, then form-level
            this.$nextTick(() => {
                if (!window._recalcLock) {
                    window._recalcLock = true;
                    setTimeout(() => {
                        window.recalcFormulas();
                        window._recalcLock = false;
                    }, 20);
                }
            });
        },

        async handleColAutoFill(rowIdx, colKey, selectedValue) {
            // Find the column config
            const col = this.columns.find(c => c.key === colKey);
            if (!col || !col.auto_fill_enabled || !col.auto_fill_mappings || !col.dynamic?.table || !selectedValue) return;

            const table = col.dynamic.table;
            const valueCol = col.dynamic.value_col || 'id';
            const mappings = col.auto_fill_mappings;
            const sourceCols = mappings.map(m => m.source_col).join(',');

            try {
                const res = await fetch(`/master/page-builder/lookup?table=${table}&id=${selectedValue}&columns=${sourceCols}`);
                const data = await res.json();
                if (data && Object.keys(data).length > 0) {
                    mappings.forEach(m => {
                        if (m.target_col_key && data[m.source_col] !== undefined) {
                            this.rows[rowIdx][m.target_col_key] = data[m.source_col] ?? '';
                        }
                    });
                    this.recalcAll();
                }
            } catch(e) { console.warn('Col auto-fill failed:', e); }
        },

        getColumnValues(colKey) {
            return this.rows.map(r => parseFloat(r[colKey]) || 0);
        }
    }));
});

// Auto-fill handler: fetch related data when a dropdown changes
window.handleAutoFill = async function(selectEl) {
    const table = selectEl.dataset.autofillTable;
    const mappings = JSON.parse(selectEl.dataset.autofillMappings || '[]');
    const selectedId = selectEl.value;

    if (!table || !selectedId || mappings.length === 0) {
        // Clear target fields if nothing selected
        mappings.forEach(m => {
            const target = document.querySelector(`[data-field-key="${m.target_field_key}"]`);
            if (target) {
                target.value = '';
                target.readOnly = false;
                target.classList.remove('bg-stone-100');
                target.dispatchEvent(new Event('input'));
            }
        });
        return;
    }

    try {
        const cols = mappings.map(m => m.source_column).join(',');
        const res = await fetch(`/master/page-builder/lookup?table=${table}&id=${selectedId}&columns=${cols}`);
        const data = await res.json();

        if (data && Object.keys(data).length > 0) {
            mappings.forEach(m => {
                const target = document.querySelector(`[data-field-key="${m.target_field_key}"]`);
                if (target && data[m.source_column] !== undefined) {
                    target.value = data[m.source_column] || '';
                    // Set readonly if configured
                    if (m.readonly) {
                        target.readOnly = true;
                        target.classList.add('bg-stone-100');
                    } else {
                        target.readOnly = false;
                        target.classList.remove('bg-stone-100');
                    }
                    target.dispatchEvent(new Event('input'));
                }
            });
        }
    } catch (e) {
        console.warn('Auto-fill lookup failed:', e);
    }

    window.recalcFormulas();
};

// Evaluate visibility rules for conditional show/hide
function evaluateVisibility(config, values) {
    if (!config || !config.rules || config.rules.length === 0) return true;
    
    const logic = (config.logic || 'AND').toUpperCase();
    const results = config.rules.map(rule => {
        const fieldVal = values[rule.field] ?? '';
        const ruleVal = rule.value ?? '';
        
        switch (rule.operator) {
            case '==': return String(fieldVal) == String(ruleVal);
            case '!=': return String(fieldVal) != String(ruleVal);
            case '>': return Number(fieldVal) > Number(ruleVal);
            case '<': return Number(fieldVal) < Number(ruleVal);
            case '>=': return Number(fieldVal) >= Number(ruleVal);
            case '<=': return Number(fieldVal) <= Number(ruleVal);
            case 'contains': return String(fieldVal).toLowerCase().includes(String(ruleVal).toLowerCase());
            case 'is_empty': return !fieldVal || fieldVal === '';
            case 'is_not_empty': return fieldVal && fieldVal !== '';
            default: return true;
        }
    });
    
    return logic === 'OR' ? results.some(r => r) : results.every(r => r);
}

// Form-level formula recalculation
window.recalcFormulas = function() {
    const engine = new FormulaEngine({ precision: {{ $precision }}, currency: '{{ $currency }}' });

    // Collect all field values
    const values = {};
    document.querySelectorAll('[data-field-key]').forEach(el => {
        const key = el.dataset.fieldKey;
        if (el.tagName === 'SELECT') {
            values[key] = el.value;
        } else if (el.type === 'number') {
            values[key] = parseFloat(el.value) || 0;
        } else if (el.type === 'radio') {
            if (el.checked) values[key] = el.value;
        } else {
            values[key] = el.value || '';
        }
    });
    // Also check radio buttons by name
    document.querySelectorAll('input[type="radio"]:checked').forEach(el => {
        if (el.name && !(el.name in values)) values[el.name] = el.value;
    });

    // Evaluate visibility rules
    document.querySelectorAll('.visibility-field').forEach(el => {
        const rules = JSON.parse(el.dataset.visibilityRules || '{}');
        const visible = evaluateVisibility(rules, values);
        el.style.display = visible ? '' : 'none';
    });

    // Collect repeater column sums
    document.querySelectorAll('[x-data*="repeaterCalc"]').forEach(el => {
        try {
            // Alpine v3: access data via Alpine.evaluate or _x_dataStack
            const data = Alpine.$data(el);
            if (data && data.rows && data.columns) {
                const match = el.getAttribute('x-data')?.match(/'([^']+)'/);
                const fieldKey = match ? match[1] : null;
                if (fieldKey) {
                    data.columns.forEach(col => {
                        values[fieldKey + '.' + col.key] = data.rows.map(r => parseFloat(r[col.key]) || 0);
                    });
                }
            }
        } catch(e) { /* skip if Alpine not ready */ }
    });

    // Evaluate all formula displays
    document.querySelectorAll('.formula-display').forEach(el => {
        const formula = el.dataset.formula;
        if (!formula) return;
        const result = engine.evaluate(formula, values);
        const fieldKey = el.dataset.fieldKey;
        if (fieldKey) values[fieldKey] = result ?? 0; // Store for dependent formulas
        el.textContent = result !== null ? engine.format(result) : '{{ $currency }} 0.00';
    });

    // Dispatch event so repeaters can recalculate (they may reference form-level fields)
    if (!window._recalcLock) {
        document.dispatchEvent(new CustomEvent('form-values-changed'));
    }
};

// Initial calculation
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => window.recalcFormulas(), 200);
});
</script>
@endpush
@endsection
