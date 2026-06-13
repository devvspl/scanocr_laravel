@props(['field', 'value' => null, 'inputClass' => 'w-full px-3 py-2.5 border border-stone-300 rounded-lg text-sm focus:ring-2 focus:ring-red-700/20 focus:border-red-700 outline-none transition-all'])

@php
    use App\Helpers\PageFieldHelper;

    $inputName = PageFieldHelper::inputName($field);
    $defaultValue = PageFieldHelper::defaultValue($field, $value);
    $placeholder = $field->placeholder ?? '';
    $required = $field->is_required ? 'required' : '';
@endphp

@switch($field->field_type)
    {{-- ── Text-based inputs ── --}}
    @case('title')
    @case('phone')
    @case('url')
    @case('slug')
    @case('email')
    @case('password')
        <input type="{{ PageFieldHelper::htmlInputType($field->field_type) }}" name="{{ $inputName }}" class="{{ $inputClass }}" placeholder="{{ $placeholder }}" value="{{ $defaultValue }}" {{ $required }}>
        @break

    {{-- ── Textarea fields ── --}}
    @case('content')
    @case('json')
        <textarea name="{{ $inputName }}" placeholder="{{ $placeholder }}" class="{{ $inputClass }} resize-none min-h-[20px]" {{ $required }}>{{ $defaultValue }}</textarea>
        @break

    {{-- ── Numeric inputs ── --}}
    @case('number')
    @case('decimal')
    @case('currency')
    @case('rating')
        <input type="number" name="{{ $inputName }}" class="{{ $inputClass }}" placeholder="{{ $placeholder }}" value="{{ $defaultValue }}" step="{{ PageFieldHelper::stepValue($field->field_type) }}" {{ $required }} {{ $field->field_type === 'rating' ? 'min="1" max="5"' : '' }}>
        @break

    {{-- ── Date / Time inputs ── --}}
    @case('date')
    @case('datetime')
    @case('time')
        <input type="{{ PageFieldHelper::htmlInputType($field->field_type) }}" name="{{ $inputName }}" class="{{ $inputClass }}" value="{{ $defaultValue }}" {{ $required }}>
        @break

    {{-- ── Select dropdown ── --}}
    @case('select')
        <select name="{{ $inputName }}" class="{{ $inputClass }} bg-white" {{ $required }}>
            <option value="">— Select —</option>
            @foreach(PageFieldHelper::resolveOptions($field) as $opt)
                @php
                    $optValue = is_array($opt) ? ($opt['value'] ?? '') : $opt;
                    $optLabel = is_array($opt) ? ($opt['label'] ?? $opt['value'] ?? '') : $opt;
                @endphp
                <option value="{{ $optValue }}" {{ ((string)$defaultValue === (string)$optValue) ? 'selected' : '' }}>{{ $optLabel }}</option>
            @endforeach
        </select>
        @break

    {{-- ── Radio buttons ── --}}
    @case('radio')
        <div class="flex flex-wrap gap-4 mt-1">
            @foreach(PageFieldHelper::resolveOptions($field) as $opt)
                @php
                    $optValue = is_array($opt) ? ($opt['value'] ?? '') : $opt;
                    $optLabel = is_array($opt) ? ($opt['label'] ?? $opt['value'] ?? '') : $opt;
                @endphp
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="{{ $inputName }}" value="{{ $optValue }}" class="text-red-700 focus:ring-red-700" {{ ((string)$defaultValue === (string)$optValue) ? 'checked' : '' }}>
                    <span class="text-sm text-stone-600">{{ $optLabel }}</span>
                </label>
            @endforeach
        </div>
        @break

    {{-- ── Checkbox / Toggle ── --}}
    @case('checkbox')
    @case('toggle')
        <label class="flex items-center gap-2.5 mt-1 cursor-pointer">
            <input type="checkbox" name="{{ $inputName }}" class="rounded text-red-700 w-4 h-4 focus:ring-red-700" value="1" {{ $defaultValue ? 'checked' : '' }}>
            <span class="text-sm text-stone-600">{{ $placeholder ?: 'Yes' }}</span>
        </label>
        @break

    {{-- ── Color picker ── --}}
    @case('color')
        <input type="color" name="{{ $inputName }}" value="{{ $defaultValue ?: '#000000' }}" class="w-full h-10 border border-stone-300 rounded-lg cursor-pointer">
        @break

    {{-- ── File / Image upload ── --}}
    @case('file')
    @case('image')
        <div class="relative">
            <div class="border-2 border-dashed border-stone-300 rounded-xl p-6 text-center hover:border-red-400 hover:bg-red-50/30 transition-all cursor-pointer group" onclick="this.querySelector('input[type=file]').click()">
                <input type="file" name="{{ $inputName }}[]" class="hidden" {{ $required }} {{ $field->field_type === 'image' ? 'accept="image/*"' : '' }} multiple onchange="this.closest('.relative').querySelector('.file-name').textContent = Array.from(this.files).map(f => f.name).join(', ') || 'No file selected'">
                <svg class="w-8 h-8 mx-auto text-stone-300 group-hover:text-red-400 transition-colors mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <p class="text-xs font-medium text-stone-500 group-hover:text-red-600">Drag & drop or <span class="text-red-700 font-semibold">browse</span></p>
                <p class="file-name text-[10px] text-stone-400 mt-1">No file selected</p>
            </div>
        </div>
        @break

    {{-- ── Repeater (multi-row table) ── --}}
    @case('repeater')
        @php $repeaterCols = $field->repeater_columns ?? []; @endphp
        <div class="border border-stone-200 rounded-lg overflow-hidden">
            <table class="w-full text-xs">
                <thead class="bg-stone-50">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold text-stone-500 w-8">#</th>
                        @foreach($repeaterCols as $col)
                            <th class="px-3 py-2 text-left font-semibold text-stone-500">{{ $col['label'] }}</th>
                        @endforeach
                        <th class="px-3 py-2 w-8"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-t border-stone-100">
                        <td class="px-3 py-2 text-stone-400">1</td>
                        @foreach($repeaterCols as $col)
                            <td class="px-3 py-2">
                                <input type="text" name="{{ $inputName }}[0][{{ $col['key'] }}]" class="w-full px-2 py-1.5 border border-stone-200 rounded text-xs focus:border-red-700 outline-none" placeholder="{{ $col['label'] }}">
                            </td>
                        @endforeach
                        <td class="px-3 py-2"><button type="button" class="text-stone-400 hover:text-red-600">✕</button></td>
                    </tr>
                </tbody>
            </table>
            <div class="px-3 py-2 border-t border-stone-100 bg-stone-50">
                <button type="button" class="text-xs text-red-700 font-semibold hover:underline">+ Add Row</button>
            </div>
        </div>
        @break

    {{-- ── Fallback ── --}}
    @default
        <input type="text" name="{{ $inputName }}" class="{{ $inputClass }}" placeholder="{{ $placeholder }}" value="{{ $defaultValue }}" {{ $required }}>
@endswitch
