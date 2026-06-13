@php $c = $current ?? null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

    {{-- Name --}}
    <div class="sm:col-span-2">
        <label class="block text-xs font-semibold text-stone-600 mb-1.5">Group Name <span class="text-red-600">*</span></label>
        <input type="text" name="name"
               value="{{ old('name', $c?->name) }}"
               placeholder="e.g. Current Assets"
               class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400
                      focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors
                      @error('name') border-red-400 @enderror">
        @error('name')
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Nature --}}
    <div>
        <label class="block text-xs font-semibold text-stone-600 mb-1.5">Nature <span class="text-red-600">*</span></label>
        <select name="nature"
                class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800
                       focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors
                       @error('nature') border-red-400 @enderror">
            <option value="">— Select Nature —</option>
            @foreach($natures ?? [] as $nature)
            <option value="{{ $nature->slug }}" {{ old('nature', $c?->nature) === $nature->slug ? 'selected' : '' }}>
                {{ $nature->name }}
            </option>
            @endforeach
        </select>
        @error('nature')
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Parent --}}
    <div>
        <label class="block text-xs font-semibold text-stone-600 mb-1.5">Parent Group</label>
        <select name="parent_id"
                class="w-full border border-stone-300 rounded-xl px-3.5 py-2.5 text-sm text-stone-800
                       focus:outline-none focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition-colors">
            <option value="">— None (Top Level) —</option>
            @foreach($groups as $g)
                @if(!$c || $g->id !== $c->id)
                <option value="{{ $g->id }}" {{ old('parent_id', $c?->parent_id) == $g->id ? 'selected' : '' }}>
                    {{ $g->name }}
                </option>
                @endif
            @endforeach
        </select>
        @error('parent_id')
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Status --}}
    <div class="sm:col-span-2 flex items-center gap-3">
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1"
                   class="sr-only peer"
                   {{ old('is_active', $c?->is_active ?? true) ? 'checked' : '' }}>
            <div class="w-9 h-5 bg-stone-200 peer-focus:ring-2 peer-focus:ring-red-700/20 rounded-full peer
                        peer-checked:after:translate-x-full peer-checked:after:border-white
                        after:content-[''] after:absolute after:top-0.5 after:left-[2px]
                        after:bg-white after:border-stone-300 after:border after:rounded-full
                        after:h-4 after:w-4 after:transition-all peer-checked:bg-red-700"></div>
        </label>
        <span class="text-sm text-stone-600 font-medium">Active</span>
    </div>

</div>
