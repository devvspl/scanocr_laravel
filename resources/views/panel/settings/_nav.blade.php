{{-- Settings tab navigation — include at top of every settings page --}}
<div class="bg-white border border-stone-200 rounded-1xl overflow-hidden mb-4">
    <div class="flex gap-0 px-0 overflow-x-auto">
        @foreach([
            ['settings.company',           'Company',       'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 00-1-1h-2a1 1 0 00-1 1v5m4 0H9'],
            ['settings.financial-year',    'Financial Year','M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['settings.numbering',         'Numbering',     'M7 20l4-16m2 16l4-16M6 9h14M4 15h14'],
            ['settings.document-types',    'Document',      'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
            ['settings.ext-api-control',   'API Control',   'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['settings.ext-field-mappings','Field Mappings','M4 6h16M4 12h8m-8 6h16'],
            ['settings.bill-date-sync',    'Bill Date Sync','M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'],
            ['settings.core-api-sync',     'Core API Sync', 'M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z'],
        ] as [$route, $label, $icon])
        <a href="{{ Route::has($route) ? route($route) : '#' }}"
           class="flex items-center gap-1.5 px-5 py-3 text-sm transition-colors whitespace-nowrap
                  {{ request()->routeIs($route) ? 'border-b-2 border-red-700 text-red-700 font-semibold' : 'text-stone-500 hover:text-stone-700' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $icon }}"/>
            </svg>
            {{ $label }}
        </a>
        @endforeach
    </div>
</div>
