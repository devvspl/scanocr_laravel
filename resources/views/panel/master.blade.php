@extends('layouts.app')

@section('title', 'Master')
@section('page-title', 'Master')

@section('content')
<div class="mx-auto space-y-6">

    {{-- Tab header --}}
    <div class="bg-white border border-stone-200 rounded-1xl overflow-hidden mb-3">
        <div class="flex gap-1 px-0">
            @php
            $tabs = [
                'page-builder' => ['Page Builder', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
            ];
            $activeTab = $tab ?? 'page-builder';
            @endphp

            @foreach($tabs as $slug => [$label, $icon])
            <a href="{{ route('master.' . $slug) }}"
               class="flex items-center gap-1.5 px-4 py-3 text-sm transition-colors -mb-px whitespace-nowrap
                      {{ $activeTab === $slug ? 'border-b-2 border-red-700 text-red-700 font-semibold' : 'text-stone-500 hover:text-stone-700' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $icon }}"/>
                </svg>
                {{ $label }}
            </a>
            @endforeach
        </div>
    </div>

    {{-- Tab content --}}
    @yield('master-content')

</div>
@endsection
