@extends('panel.master')

@php $tab = 'page-builder'; @endphp

@section('master-content')
<div class="bg-white border border-stone-200 rounded-1xl overflow-hidden">
    <div class="px-6 py-5 border-b border-stone-100 flex items-center justify-between">
        <div>
            <h3 class="text-sm font-semibold text-stone-800">{{ $page ? 'Edit Page' : 'New Page' }}</h3>
            <p class="text-xs text-stone-400 mt-0.5">{{ $page ? 'Update the page name.' : 'Add a new page to the builder.' }}</p>
        </div>
        <a href="{{ route('master.page-builder') }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
                  bg-stone-100 text-stone-600 hover:bg-stone-200 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back
        </a>
    </div>

    <form method="POST"
          action="{{ $page ? route('master.page-builder.update', $page) : route('master.page-builder.store') }}">
        @csrf
        @if($page) @method('PUT') @endif

        <div class="p-6">
            <label for="page_name" class="block text-sm font-medium text-stone-700 mb-1.5">Page Name</label>
            <input type="text" id="page_name" name="page_name"
                   value="{{ old('page_name', $page?->page_name) }}"
                   placeholder="e.g. Home, About Us"
                   autofocus
                   class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition
                          border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10
                          @error('page_name') border-red-400 bg-red-50 @enderror">
            @error('page_name')
                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="px-6 py-3 bg-stone-50 border-t border-stone-100 flex items-center justify-end gap-3">
            <a href="{{ route('master.page-builder') }}"
               class="px-4 py-1.5 rounded-xl text-sm font-medium text-stone-600
                      bg-white border border-stone-300 hover:bg-stone-50 transition-colors">
                Cancel
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-1.5 rounded-xl bg-red-800 hover:bg-red-700
                           text-white text-sm font-medium transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ $page ? 'Update Page' : 'Create Page' }}
            </button>
        </div>
    </form>
</div>
@endsection
