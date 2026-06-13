@extends('layouts.app')

@section('title', 'Units of Measure')
@section('page-title', 'Units of Measure')

@section('content')
<div class="mx-auto space-y-4" x-data="simpleListPage()" x-init="init()">

    {{-- Tab header --}}
    <div class="bg-white border border-stone-200 rounded-1xl overflow-hidden mb-3">
        <div class="flex gap-1 px-0">
            <a href="{{ route('master.products') }}"
               class="flex items-center gap-1.5 px-4 py-3 text-sm transition-colors -mb-px whitespace-nowrap text-stone-500 hover:text-stone-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                Products
            </a>
            <a href="{{ route('master.item-groups') }}"
               class="flex items-center gap-1.5 px-4 py-3 text-sm transition-colors -mb-px whitespace-nowrap text-stone-500 hover:text-stone-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                Item Groups
            </a>
            <a href="{{ route('master.units') }}"
               class="flex items-center gap-1.5 px-4 py-3 text-sm transition-colors -mb-px whitespace-nowrap border-b-2 border-red-700 text-red-700 font-semibold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                </svg>
                Units of Measure
            </a>
        </div>
    </div>

    @include('panel.items._simple-list', [
        'title'     => 'Units of Measure',
        'dataRoute' => route('master.units.data'),
        'storeUrl'  => '/master/units',
        'columns'   => [
            ['data' => 'name', 'title' => 'Name'],
            ['data' => 'symbol', 'title' => 'Symbol'],
            ['data' => 'type', 'title' => 'Type'],
            ['data' => 'created_by_name', 'title' => 'Created By'],
            ['data' => 'is_active', 'title' => 'Status', 'center' => true],
        ],
        'fields'    => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'placeholder' => 'e.g. Kilogram'],
            ['name' => 'symbol', 'label' => 'Symbol', 'type' => 'text', 'required' => true, 'placeholder' => 'e.g. kg'],
            ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'options' => [
                ['value' => 'weight', 'label' => 'Weight'],
                ['value' => 'volume', 'label' => 'Volume'],
                ['value' => 'length', 'label' => 'Length'],
                ['value' => 'quantity', 'label' => 'Quantity'],
                ['value' => 'time', 'label' => 'Time'],
            ]],
        ],
    ])

</div>
@endsection

@include('panel.items._simple-scripts', [
    'dataRoute' => route('master.units.data'),
    'storeUrl'  => '/master/units',
    'columns'   => [
        ['data' => 'name', 'title' => 'Name'],
        ['data' => 'symbol', 'title' => 'Symbol'],
        ['data' => 'type', 'title' => 'Type'],
        ['data' => 'created_by_name', 'title' => 'Created By'],
        ['data' => 'is_active', 'title' => 'Status', 'center' => true],
    ],
])
