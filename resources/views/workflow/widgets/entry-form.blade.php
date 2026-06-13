@php
    use App\Helpers\PageFieldHelper;

    $config = $widget ? ($widget->config ?? []) : [];
    $page = $stage->page ?? null;
    $fields = $page ? $page->fields : collect();
@endphp

<div class="bg-white rounded-xl border border-stone-200 overflow-hidden">
    <div class="px-5 py-3 border-b border-stone-100 flex items-center justify-between">
        <h3 class="text-sm font-bold text-stone-800">{{ $widget ? $widget->title : $stage->display_name }}</h3>
        @if($page)
            <span class="text-[10px] px-2 py-0.5 bg-stone-100 rounded-full text-stone-500 font-medium">{{ $page->page_name }}</span>
        @endif
    </div>

    @if($fields->count() > 0)
        <div class="p-5">
            @php
                $useGrid = $fields->count() > 3;
            @endphp
            <form id="stage-form" class="{{ $useGrid ? 'grid grid-cols-3 gap-x-4 gap-y-5' : 'space-y-5' }}">
                @foreach($fields as $field)
                    <div class="{{ PageFieldHelper::colSpanClass($field, $useGrid) }}">
                        <label class="block text-[12px] font-semibold text-stone-600 mb-1.5">
                            {{ $field->label ?? $field->field_name }}
                            @if($field->is_required) <span class="text-red-500">*</span> @endif
                        </label>

                        <x-page-field-input :field="$field" />
                    </div>
                @endforeach
            </form>
        </div>
    @else
        <div class="p-8 text-center">
            <svg class="w-10 h-10 mx-auto mb-2 text-stone-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <p class="text-sm text-stone-400">No form linked to this stage.</p>
        </div>
    @endif
</div>
