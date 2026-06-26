<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    @include('partials.cdn-assets')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="{{ asset('main/css/custom.css') }}?v={{ time() }}">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    @stack('head')
</head>

<body class="h-full font-sans antialiased bg-stone-100 text-stone-900" x-data="layoutApp()" x-init="init()">

    {{-- Mobile backdrop --}}
    <div id="mob-backdrop" x-show="mobileOpen" @click="closeAll()" x-transition:enter="transition-opacity duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity duration-150" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" style="display:none"></div>

    {{-- Submenu outside-click overlay --}}
    <div id="sub-overlay" @click="closeSubmenu()"></div>

    {{-- ═══════════════════════════════════
     ICON RAIL
═══════════════════════════════════ --}}
    <aside id="icon-rail" :class="mobileOpen ? 'mob-open' : ''" role="navigation" aria-label="Main navigation">

        {{-- Brand --}}
        <div class="rail-brand">
            <div class="rail-logo">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                </svg>
            </div>
        </div>

        {{-- Nav tiles --}}
        <div class="rail-nav">

            @php
                use App\Helpers\MenuPermission;

                $railItems = [
                    [
                        'id' => 'home',
                        'label' => 'Home',
                        'route' => 'dashboard',
                        'role'  => 'Super Admin',
                        'icon' =>
                            'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                    ],
                    [
                        'id' => 'temp-scanning',
                        'label' => 'Temp Scanning',
                        'route' => 'workflow.temp-scan.index',
                        'role'  => 'Temp Scanning',
                        'icon' => 'M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z M15 13a3 3 0 11-6 0 3 3 0 016 0z',
                        'children' => [
                            ['section' => 'Temp Scan'],
                            ['label' => 'Upload Scan',  'route' => 'workflow.temp-scan.index'],
                        ],
                    ],
                    [
                        'id' => 'super-scanner',
                        'label' => 'Super Scanner',
                        'route' => 'workflow.super-scanner.index',
                        'role'  => 'Super Scanner',
                        'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                        'children' => [
                            ['section' => 'Super Scanner'],
                            ['label' => 'Scan Summary', 'route' => 'workflow.super-scanner.index'],
                        ],
                    ],
                    [
                        'id' => 'direct-scanning',
                        'label' => 'Direct Scanning',
                        'route' => 'workflow.direct-scan.index',
                        'role'  => 'Direct Scanning',
                        'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
                        'children' => [
                            ['section' => 'Direct Scan'],
                            ['label' => 'Upload Scan',  'route' => 'workflow.direct-scan.index'],
                        ],
                    ],
                    [
                        'id' => 'document-naming',
                        'label' => 'Document Naming',
                        'route' => '#',
                        'role'  => 'Document Naming',
                        'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a2 2 0 012-2h2z',
                    ],
                    [
                        'id' => 'bill-approval',
                        'label' => 'Bill Approval',
                        'route' => 'workflow.bill-approval.index',
                        'role'  => 'Bill Approval',
                        'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
                        'children' => [
                            ['section' => 'Bill Approval'],
                            ['label' => 'Pending Bills', 'route' => 'workflow.bill-approval.index'],
                        ],
                    ],
                    [
                        'id' => 'classification',
                        'label' => 'Classification',
                        'route' => 'workflow.classification.index',
                        'role'  => 'Classification',
                        'icon' => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
                        'children' => [
                            ['section' => 'Classification'],
                            ['label' => 'Classify Documents', 'route' => 'workflow.classification.index'],
                        ],
                    ],
                    [
                        'id' => 'punching',
                        'label' => 'Data Punching',
                        'route' => 'workflow.punching.index',
                        'role'  => 'Data Punching',
                        'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
                        'children' => [
                            ['section' => 'Punching'],
                            ['label' => 'Data Punching', 'route' => 'workflow.punching.index'],
                        ],
                    ],
                    [
                        'id' => 'punch-approval',
                        'label' => 'Punch Approval',
                        'route' => '#',
                        'role'  => 'Punch Approval',
                        'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
                    ],
                    [
                        'id' => 'reports',
                        'label' => 'Reports',
                        'route' => '#',
                        'role'  => 'Super Admin',
                        'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                    ],
                    [
                        'id' => 'document-ai',
                        'label' => 'AI Predictor',
                        'route' => 'document-ai',
                        'role'  => 'Super Admin',
                        'icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
                        'children' => [
                            ['section' => 'Document AI'],
                            ['label' => 'Playground',        'route' => 'document-ai.playground'],
                            ['label' => 'Prediction Logs',   'route' => 'document-ai.logs'],
                            ['label' => 'Analytics',         'route' => 'document-ai.analytics'],
                            ['label' => 'Training Settings', 'route' => 'document-ai.settings'],
                        ],
                    ],
                    [
                        'id' => 'settings',
                        'label' => 'Settings',
                        'route' => 'settings',
                        'role'  => 'Super Admin',
                        'icon' =>
                            'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
                        'children' => [
                            ['section' => 'General'],
                            ['label' => 'Company',   'route' => 'settings.company',       'permission' => 'company.view'],
                            ['label' => 'Financial Year', 'route' => 'settings.financial-year', 'permission' => 'financial-year.view'],
                            ['label' => 'Numbering',      'route' => 'settings.numbering',      'permission' => 'numbering.view'],
                            ['label' => 'Document',        'route' => 'settings.document-types',    'permission' => 'document-types.view'],
                            ['section' => 'Ext Master'],
                            ['label' => 'API Control',     'route' => 'settings.ext-api-control',   'permission' => 'ext-api-control.view'],
                            ['label' => 'Field Mappings',  'route' => 'settings.ext-field-mappings','permission' => 'ext-field-mappings.view'],
                            ['section' => 'Tools'],
                            ['label' => 'Bill Date Sync', 'route' => 'settings.bill-date-sync', 'permission' => 'bill-date-sync.view'],
                            ['label' => 'Core API Sync',  'route' => 'settings.core-api-sync',  'permission' => 'core-api-sync.view'],
                            ['section' => 'Users & Access'],
                            ['label' => 'Users',       'route' => 'settings.users',       'permission' => 'users.view'],
                            ['label' => 'Roles',       'route' => 'settings.roles',       'permission' => 'roles.view'],
                            ['label' => 'Permissions', 'route' => 'settings.permissions', 'permission' => 'permissions.view'],
                        ],
                    ],
                ];

                // ── Top-level role gate ───────────────────────────────────────────────
                // Items with a 'role' key are only shown to users who have that exact role.
                // Items without a 'role' key are shown to all authenticated users.
                $__authUser = auth()->user();
                $railItems = array_values(array_filter($railItems, function ($item) use ($__authUser) {
                    if (! isset($item['role'])) return true;
                    return $__authUser?->hasRole($item['role']) ?? false;
                }));

                // ── Children permission filter ────────────────────────────────────────
                // Remove link items the user cannot access, and remove orphaned section
                // headers (sections with no visible links after them).
                foreach ($railItems as &$item) {
                    if (empty($item['children'])) continue;

                    $filtered = [];
                    $pendingSection = null;

                    foreach ($item['children'] as $child) {
                        if (isset($child['section'])) {
                            // Hold the section — only add it if a visible link follows
                            $pendingSection = $child;
                        } else {
                            // If an explicit permission key is provided, check it directly;
                            // otherwise fall back to deriving from the route name.
                            if (isset($child['permission'])) {
                                $allowed = auth()->user()?->can($child['permission']) ?? false;
                            } else {
                                $allowed = !isset($child['route'])
                                    || MenuPermission::canAccess($child['route']);
                            }

                            if ($allowed) {
                                if ($pendingSection !== null) {
                                    $filtered[] = $pendingSection;
                                    $pendingSection = null;
                                }
                                $filtered[] = $child;
                            }
                        }
                    }

                    $item['children'] = $filtered;
                }
                unset($item);
            @endphp

            @foreach ($railItems as $item)
                @php
                    $isActive = $item['route'] !== '#' && (Request::routeIs($item['route']) || Request::routeIs($item['route'] . '.*'));
                    // Also mark active for child routes (e.g. supporting page marks the parent tile active)
                    if (!$isActive && !empty($item['children'])) {
                        foreach ($item['children'] as $child) {
                            if (!empty($child['route']) && (Request::routeIs($child['route']) || Request::routeIs($child['route'] . '.*'))) {
                                $isActive = true;
                                break;
                            }
                        }
                    }
                    $hasChildren = !empty($item['children']);
                @endphp

                @if ($hasChildren)
                    <button type="button" class="rail-tile {{ $isActive ? 'active' : '' }}"
                        data-id="{{ $item['id'] }}"
                        @click="toggleSubmenu('{{ $item['id'] }}', '{{ addslashes($item['icon']) }}', '{{ $item['label'] }}')">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                d="{{ $item['icon'] }}" />
                        </svg>
                        <span class="rail-tile-label">{{ $item['label'] }}</span>
                        @if (!empty($item['badge']))
                            <span class="rail-badge">{{ $item['badge'] }}</span>
                        @endif
                    </button>
                @else
                    <a href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}"
                        class="rail-tile {{ $isActive ? 'active' : '' }}" data-id="{{ $item['id'] }}"
                        @click="closeSubmenu(); if(window.innerWidth < 1024) mobileOpen = false">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                d="{{ $item['icon'] }}" />
                        </svg>
                        <span class="rail-tile-label">{{ $item['label'] }}</span>
                    </a>
                @endif
            @endforeach

        </div>

        {{-- User avatar --}}
        <div class="rail-user" x-data="{ open: false }">
            <button @click="open = !open" class="relative flex items-center justify-center w-9 h-9">
                <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'Admin') }}&background=7f1d1d&color=fca5a5&size=80"
                    alt="Avatar" class="w-9 h-9 rounded-full border-2 border-white/20">
                <span
                    class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-400 border-2 border-stone-900 rounded-full"></span>
            </button>

            <div x-show="open" x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95" @click.outside="open=false"
                class="absolute bottom-16 left-2 w-52 bg-stone-800 border border-white/10
                    rounded-xl shadow-2xl overflow-hidden z-[100]">
                <div class="px-4 py-3 border-b border-white/10 bg-white/5">
                    <p class="text-sm font-semibold text-stone-100 truncate">{{ auth()->user()->name ?? 'Admin User' }}
                    </p>
                    <p class="text-xs text-stone-400 truncate mt-0.5">
                        {{ auth()->user()->email ?? 'admin@ScanOCR.com' }}</p>
                </div>
                <a href="{{ route('profile') }}"
                    class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-stone-300 hover:bg-white/5 hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Profile
                </a>
                <a href="{{ route('settings') }}"
                    class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-stone-300 hover:bg-white/5 hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Settings
                </a>
                <div class="border-t border-white/10"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-400 hover:bg-red-950/50 hover:text-red-300 transition-colors text-left">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Sign out
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ═══════════════════════════════════
     SUBMENU PANEL
═══════════════════════════════════ --}}
    <div id="submenu-panel" :class="subOpen ? 'open' : ''">
        <div class="sub-head">
            <div class="sub-head-icon">
                <svg id="sub-hd-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path id="sub-hd-path" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                        d="" />
                </svg>
            </div>
            <span id="sub-hd-title" class="sub-head-title"></span>
        </div>
        <div id="sub-body" class="sub-body"></div>
    </div>

    {{-- ═══════════════════════════════════
     MAIN CONTENT
═══════════════════════════════════ --}}
    <div id="main-wrap" :class="subOpen ? 'shifted' : ''">

        {{-- TOPBAR --}}
        <header id="topbar" x-data="{ mobileSearch: false }">

            <button @click="mobileOpen=true"
                class="lg:hidden w-9 h-9 flex items-center justify-center rounded-lg
                       text-stone-500 hover:bg-stone-100 transition shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <div class="flex-1 min-w-0" :class="mobileSearch ? 'hidden' : 'block'">
                <h1 class="text-sm font-semibold text-stone-800 truncate">@yield('page-title', 'Dashboard')</h1>
                @hasSection('breadcrumb')
                    <nav class="hidden sm:flex items-center gap-1 text-xs text-stone-400 mt-0.5">@yield('breadcrumb')</nav>
                @endif
            </div>

            <div x-show="mobileSearch"
                class="flex-1 flex items-center gap-2 bg-stone-100 border border-stone-200 rounded-lg px-3 py-1.5 md:hidden">
                <svg class="w-4 h-4 text-stone-400 shrink-0" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" placeholder="Search…" autofocus
                    class="bg-transparent flex-1 text-sm outline-none border-none p-0 placeholder-stone-400">
                <button @click="mobileSearch=false"><svg class="w-4 h-4 text-stone-400" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg></button>
            </div>

            <div class="flex items-center gap-1.5 shrink-0">
                <div
                    class="hidden md:flex items-center gap-2 bg-stone-100 border border-stone-200 rounded-lg px-3 py-1.5 w-52 xl:w-72">
                    <svg class="w-4 h-4 text-stone-400 shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" placeholder="Search invoices, clients…"
                        class="bg-transparent flex-1 text-sm text-stone-700 placeholder-stone-400 outline-none border-none p-0">
                    <span
                        class="text-[10px] text-stone-400 font-mono bg-stone-200 px-1.5 py-0.5 rounded hidden xl:inline">⌘K</span>
                </div>

                <button @click="mobileSearch=true" x-show="!mobileSearch"
                    class="md:hidden w-9 h-9 flex items-center justify-center rounded-lg text-stone-500 hover:bg-stone-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>

                {{-- Notifications --}}
                <div x-data="{ open: false }" class="relative">
                    <button @click="open=!open"
                        class="relative w-9 h-9 flex items-center justify-center rounded-lg
                               text-stone-500 hover:bg-stone-100 hover:text-stone-800 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span
                            class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
                    </button>
                    <div x-show="open" x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                        @click.outside="open=false"
                        class="absolute right-0 mt-2 w-[min(320px,calc(100vw-1.5rem))] bg-white border border-stone-200
                            rounded-1xl shadow-2xl overflow-hidden z-50 origin-top-right">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-stone-100">
                            <h3 class="text-sm font-semibold text-stone-800">Notifications</h3>
                            <span class="text-xs text-red-700 font-medium bg-red-50 px-2 py-0.5 rounded-full">3
                                new</span>
                        </div>
                        <div class="divide-y divide-stone-100 max-h-64 overflow-y-auto">
                            @foreach ([['Invoice #INV-0042 overdue', 'Pending since 3 days ago', 'red'], ['Payment received from Acme Ltd', '₹48,000 credited', 'green'], ['New vendor added', 'TechSupply Co. onboarded', 'blue']] as $notif)
                                <div class="flex gap-3 px-4 py-3 hover:bg-stone-50 cursor-pointer transition-colors">
                                    <div class="mt-2 w-2 h-2 rounded-full bg-{{ $notif[2] }}-500 shrink-0"></div>
                                    <div>
                                        <p class="text-sm font-medium text-stone-800">{{ $notif[0] }}</p>
                                        <p class="text-xs text-stone-400 mt-0.5">{{ $notif[1] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="px-4 py-3 border-t border-stone-100">
                            <a href="#" class="text-xs text-red-700 font-medium hover:underline">View all
                                notifications →</a>
                        </div>
                    </div>
                </div>

                {{-- Company & FY Switcher --}}
                @php
                    $__user        = auth()->user();
                    $__isSuperAdmin = $__user?->hasRole('Super Admin') ?? false;

                    $__companies   = $__user
                        ? \App\Services\UserAccessService::allowedCompanies($__user->id, $__isSuperAdmin)
                        : collect();

                    $__currentCompany = \App\Models\Company::getDefault()
                                        ?? $__companies->first();
                    $__currentFy      = \App\Models\FinancialYear::getCurrent();
                    $__fys            = \App\Models\FinancialYear::orderByDesc('start_date')->get(['id', 'label', 'is_current']);
                @endphp
                <div x-data="{ cfOpen: false }" class="relative">
                    <button @click="cfOpen = !cfOpen" @click.outside="cfOpen = false"
                        class="flex items-center gap-1.5 h-8 px-2.5 text-xs border border-stone-200 rounded-lg bg-white hover:bg-stone-50 transition-colors">
                        <svg class="w-3.5 h-3.5 text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span class="hidden sm:inline font-medium text-stone-700 max-w-[100px] truncate">{{ $__currentCompany->name ?? 'No Company' }}</span>
                        <span class="hidden sm:inline text-stone-300">|</span>
                        <span class="text-stone-500 whitespace-nowrap">{{ $__currentFy->label ?? 'No FY' }}</span>
                        <svg class="w-3 h-3 text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="cfOpen" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                        class="absolute right-0 top-full mt-1 w-72 bg-white border border-stone-200 rounded-xl shadow-xl z-50 overflow-hidden">
                        <div class="px-3 pt-3 pb-2">
                            <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-1.5">Company</p>
                            <div class="space-y-0.5 max-h-32 overflow-y-auto">
                                @foreach($__companies as $co)
                                @php $__isActive = $__currentCompany && $__currentCompany->id === $co->id; @endphp
                                <button onclick="switchCompany({{ $co->id }})" class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs hover:bg-stone-50 transition-colors {{ $__isActive ? 'bg-red-50 text-red-700 font-semibold' : 'text-stone-700' }}">
                                    <span class="truncate">{{ $co->name }}</span>
                                    @if($__isActive)<svg class="w-3.5 h-3.5 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>@endif
                                </button>
                                @endforeach
                            </div>
                        </div>
                        <hr class="border-stone-100">
                        <div class="px-3 pt-2 pb-3">
                            <p class="text-[10px] font-semibold text-stone-400 uppercase tracking-wide mb-1.5">Financial Year</p>
                            <div class="space-y-0.5 max-h-32 overflow-y-auto">
                                @foreach($__fys as $fy)
                                @php $__isFyActive = $__currentFy && $__currentFy->id === $fy->id; @endphp
                                <button onclick="switchFY({{ $fy->id }})" class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs hover:bg-stone-50 transition-colors {{ $__isFyActive ? 'bg-red-50 text-red-700 font-semibold' : 'text-stone-700' }}">
                                    <span>{{ $fy->label }}</span>
                                    @if($__isFyActive)<svg class="w-3.5 h-3.5 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>@endif
                                </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'Admin') }}&background=7f1d1d&color=fca5a5&size=80"
                    alt="Avatar"
                    class="lg:hidden w-8 h-8 rounded-full border-2 border-stone-200 cursor-pointer shrink-0">
            </div>
        </header>

        {{-- PAGE CONTENT --}}
        <main class="flex-1 p-1 md:p-3 xl:p-4">

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition
                    class="mb-5 flex items-start gap-3 p-4 bg-green-50 border border-green-200 rounded-xl text-sm text-green-800">
                    <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="flex-1">{{ session('success') }}</p>
                    <button @click="show=false"><svg class="w-4 h-4 text-green-600" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg></button>
                </div>
            @endif

            @if (session('error'))
                <div x-data="{ show: true }" x-show="show" x-transition
                    class="mb-5 flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-800">
                    <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="flex-1">{{ session('error') }}</p>
                    <button @click="show=false"><svg class="w-4 h-4 text-red-600" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg></button>
                </div>
            @endif

            @yield('content')
        </main>

        {{-- FOOTER --}}
        <footer
            class="shrink-0 px-6 py-1 border-t border-stone-200
                   flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-stone-400">
            <div class="flex items-center gap-2">
                <span>© {{ date('Y') }} {{ config('app.name') }} Application. All rights reserved.</span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-50 border border-red-200 text-red-700 font-semibold text-[10px] leading-none">
                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd"/></svg>
                    v1.0 — First Release
                </span>
            </div>
            <div class="flex items-center gap-5">
                <a href="{{ route('privacy') }}" target="_blank" class="hover:text-stone-600 transition-colors">Privacy</a>
                <a href="{{ route('terms') }}" target="_blank" class="hover:text-stone-600 transition-colors">Terms</a>
                <a href="{{ route('help') }}" target="_blank" class="hover:text-stone-600 transition-colors">Help</a>
            </div>
        </footer>
    </div>

    {{-- ═══════════════════════════════════
     JS — submenu data + Alpine app
═══════════════════════════════════ --}}
    @php
        $submenus = [];
        foreach ($railItems as $item) {
            if (!empty($item['children'])) {
                $menuItems = [];
                foreach ($item['children'] as $child) {
                    if (isset($child['section'])) {
                        $menuItems[] = [
                            'type' => 'section',
                            'label' => $child['section'],
                        ];
                    } else {
                        $childRoute = $child['route'];
                        $childParams = $child['params'] ?? [];
                        $menuItems[] = [
                            'type' => 'link',
                            'label' => $child['label'],
                            'url' => Route::has($childRoute) ? route($childRoute, $childParams) : '#',
                            'active' => request()->routeIs($childRoute) || request()->routeIs($childRoute . '.*'),
                        ];
                    }
                }
                $submenus[$item['id']] = [
                    'label' => $item['label'],
                    'icon' => $item['icon'],
                    'items' => $menuItems,
                ];
            }
        }
    @endphp

    <script>
        const SUBMENUS = @json($submenus);

        async function switchCompany(id) {
            // Uses session-based switch — no global DB change, per-user only
            const res = await fetch(`/settings/company/${id}/switch`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            });
            const json = await res.json();
            if (json.success) window.location.reload();
        }

        async function switchFY(id) {
            // Uses session-based switch — no global DB change, per-user only
            const res = await fetch(`/settings/financial-year/${id}/switch`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            });
            const json = await res.json();
            if (json.success) window.location.reload();
        }

        function layoutApp() {
            return {
                mobileOpen: false,
                subOpen: false,
                activeId: null,

                init() {
                    window.addEventListener('resize', () => {
                        if (window.innerWidth >= 1024) this.mobileOpen = false;
                    });
                },

                toggleSubmenu(id, icon, label) {
                    if (this.subOpen && this.activeId === id) {
                        this.closeSubmenu();
                        return;
                    }
                    this._renderSubmenu(id);
                },

                _renderSubmenu(id) {
                    const data = SUBMENUS[id];
                    if (!data) return;

                    // Highlight active rail tile
                    document.querySelectorAll('.rail-tile').forEach(el => {
                        el.classList.toggle('active', el.dataset.id === id);
                    });

                    // Fill panel header
                    document.getElementById('sub-hd-path').setAttribute('d', data.icon);
                    document.getElementById('sub-hd-title').textContent = data.label;

                    // Fill panel body
                    const body = document.getElementById('sub-body');
                    body.innerHTML = data.items.map(item => {
                        if (item.type === 'section') {
                            return `<div class="sub-section-label">${item.label}</div>`;
                        }
                        return `<a href="${item.url}" class="sub-link ${item.active ? 'active' : ''}">
                    <span class="sub-dot"></span>${item.label}
                </a>`;
                    }).join('');

                    this.activeId = id;
                    this.subOpen = true;
                    document.getElementById('sub-overlay').classList.add('on');
                },

                closeSubmenu() {
                    this.subOpen = false;
                    this.activeId = null;
                    document.getElementById('sub-overlay').classList.remove('on');
                },

                closeAll() {
                    this.closeSubmenu();
                    this.mobileOpen = false;
                }
            };
        }
    </script>

    @stack('scripts')
    @stack('modals')
    {{-- Tailwind safelist --}}
    <div class="hidden bg-red-500 bg-green-500 bg-blue-500 col-span-1 col-span-2 col-span-3 grid-cols-3"></div>
</body>

</html>
