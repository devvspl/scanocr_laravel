@php
    $page = $stage->page ?? null;
    $fields = $page ? $page->fields : collect();
    $useGrid = $fields->count() > 3;
@endphp

@if($fields->count() > 0)
    <form id="stage-form-{{ $stage->id }}" class="stage-form {{ $useGrid ? 'grid grid-cols-3 gap-x-4 gap-y-5' : 'space-y-5' }}">
        @foreach($fields as $field)
            @php
                $inputName = $field->column_name ?: \Illuminate\Support\Str::snake(preg_replace('/[^a-zA-Z0-9\s]/', '', $field->field_name));
                $colClass = in_array($field->field_type, ['file', 'image', 'repeater']) ? 'col-span-3' : 'col-span-' . ($field->col_span ?? 3);
            @endphp
            <div class="{{ $useGrid ? $colClass : '' }}">
                <label class="block text-[12px] font-semibold text-stone-600 mb-1.5">
                    {{ $field->label ?? $field->field_name }}
                    @if($field->is_required) <span class="text-red-500">*</span> @endif
                </label>

                @switch($field->field_type)
                    @case('title')
                    @case('content')
                    @case('phone')
                    @case('url')
                    @case('slug')
                        <input type="text" name="{{ $inputName }}" class="w-full px-3 py-2.5 border border-stone-300 rounded-lg text-sm focus:ring-2 focus:ring-red-700/20 focus:border-red-700 outline-none transition-all" placeholder="{{ $field->placeholder ?? '' }}" value="{{ $field->default_value ?? '' }}" {{ $field->is_required ? 'required' : '' }}>
                        @break
                    @case('number')
                    @case('decimal')
                    @case('currency')
                        <input type="number" name="{{ $inputName }}" class="w-full px-3 py-2.5 border border-stone-300 rounded-lg text-sm focus:ring-2 focus:ring-red-700/20 focus:border-red-700 outline-none transition-all" placeholder="{{ $field->placeholder ?? '' }}" step="{{ $field->field_type === 'number' ? '1' : '0.01' }}" {{ $field->is_required ? 'required' : '' }}>
                        @break
                    @case('email')
                        <input type="email" name="{{ $inputName }}" class="w-full px-3 py-2.5 border border-stone-300 rounded-lg text-sm focus:ring-2 focus:ring-red-700/20 focus:border-red-700 outline-none transition-all" placeholder="{{ $field->placeholder ?? '' }}" {{ $field->is_required ? 'required' : '' }}>
                        @break
                    @case('date')
                        <input type="date" name="{{ $inputName }}" class="w-full px-3 py-2.5 border border-stone-300 rounded-lg text-sm focus:ring-2 focus:ring-red-700/20 focus:border-red-700 outline-none transition-all" {{ $field->is_required ? 'required' : '' }}>
                        @break
                    @case('select')
                        <select name="{{ $inputName }}" class="w-full px-3 py-2.5 border border-stone-300 rounded-lg text-sm focus:ring-2 focus:ring-red-700/20 focus:border-red-700 outline-none transition-all bg-white" {{ $field->is_required ? 'required' : '' }}>
                            <option value="">— Select —</option>
                            @foreach($field->options ?? [] as $opt)
                                <option value="{{ is_array($opt) ? ($opt['value'] ?? $opt) : $opt }}">{{ is_array($opt) ? ($opt['label'] ?? $opt['value'] ?? $opt) : $opt }}</option>
                            @endforeach
                        </select>
                        @break
                    @case('file')
                    @case('image')
                        <div class="relative">
                            <div class="border-2 border-dashed border-stone-300 rounded-xl p-6 text-center hover:border-red-400 hover:bg-red-50/30 transition-all cursor-pointer group" onclick="this.querySelector('input[type=file]').click()">
                                <input type="file" name="{{ $inputName }}" class="hidden" {{ $field->is_required ? 'required' : '' }} {{ $field->field_type === 'image' ? 'accept="image/*"' : '' }} onchange="this.closest('.relative').querySelector('.file-name').textContent = this.files[0]?.name || 'No file selected'">
                                <svg class="w-8 h-8 mx-auto text-stone-300 group-hover:text-red-400 transition-colors mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                <p class="text-xs font-medium text-stone-500 group-hover:text-red-600">Drag & drop or <span class="text-red-700 font-semibold">browse</span></p>
                                <p class="file-name text-[10px] text-stone-400 mt-1">No file selected</p>
                            </div>
                        </div>
                        @break
                    @case('checkbox')
                    @case('toggle')
                        <label class="flex items-center gap-2.5 mt-1 cursor-pointer">
                            <input type="checkbox" name="{{ $inputName }}" class="rounded text-red-700 w-4 h-4 focus:ring-red-700" value="1">
                            <span class="text-sm text-stone-600">{{ $field->placeholder ?? 'Yes' }}</span>
                        </label>
                        @break
                    @default
                        <input type="text" name="{{ $inputName }}" class="w-full px-3 py-2.5 border border-stone-300 rounded-lg text-sm focus:ring-2 focus:ring-red-700/20 focus:border-red-700 outline-none transition-all" placeholder="{{ $field->placeholder ?? '' }}" {{ $field->is_required ? 'required' : '' }}>
                @endswitch
            </div>
        @endforeach
    </form>
@else
    <div class="text-center py-8">
        <p class="text-sm text-stone-400">No form linked to this sub-stage.</p>
        <p class="text-xs text-stone-300 mt-1">Link a form in the Workflow Designer.</p>
    </div>
@endif
