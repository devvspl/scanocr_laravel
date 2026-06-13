@extends('layouts.app')

@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')
<div class="max-w-3xl mx-auto space-y-6" x-data="{ tab: 'general' }">

    {{-- Tab header --}}
    <div class="bg-white border border-stone-200 rounded-1xl overflow-hidden">
        <div class="px-6 py-5 border-b border-stone-100">
            <h2 class="text-sm font-semibold text-stone-800">Application Settings</h2>
            <p class="text-xs text-stone-400 mt-0.5">Manage your workspace and appearance preferences.</p>
        </div>
        <div class="flex gap-1 px-0">
            @foreach([
                'general'    => ['General',    'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
                'appearance' => ['Appearance', 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01'],
            ] as $key => [$label, $icon])
            <button @click="tab = '{{ $key }}'"
                    :class="tab === '{{ $key }}'
                        ? 'border-b-2 border-red-700 text-red-700 font-semibold'
                        : 'text-stone-500 hover:text-stone-700'"
                    class="flex items-center gap-1.5 px-4 py-3 text-sm transition-colors -mb-px whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $icon }}"/>
                </svg>
                {{ $label }}
            </button>
            @endforeach
        </div>
    </div>

    <form method="POST" action="{{ route('settings.update') }}">
        @csrf

        {{-- ── General ── --}}
        <div x-show="tab === 'general'"
             x-transition:enter="transition-opacity duration-150"
             x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             class="bg-white border border-stone-200 rounded-1xl overflow-hidden">
            <div class="px-6 py-5 border-b border-stone-100">
                <h3 class="text-sm font-semibold text-stone-800">General</h3>
                <p class="text-xs text-stone-400 mt-0.5">Core workspace properties.</p>
            </div>
            <div class="p-6 space-y-5">
                <div>
                    <label for="app_name" class="block text-sm font-medium text-stone-700 mb-1.5">Workspace Name</label>
                    <input type="text" id="app_name" name="app_name"
                           value="{{ old('app_name', $settings->app_name) }}"
                           class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition
                                  border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10
                                  @error('app_name') border-red-400 bg-red-50 @enderror">
                    @error('app_name')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Administrator Email</label>
                    <input type="email" value="{{ Auth::user()->email }}" disabled
                           class="w-full px-3.5 py-2.5 text-sm border border-stone-200 rounded-xl bg-stone-50 text-stone-400 cursor-not-allowed">
                    <p class="mt-1.5 text-xs text-stone-400">Change this from your <a href="{{ route('profile') }}" class="text-red-700 hover:underline">Profile page</a>.</p>
                </div>

                <div>
                    <label for="timezone" class="block text-sm font-medium text-stone-700 mb-1.5">Timezone</label>
                    <select id="timezone" name="timezone"
                            class="w-full px-3.5 py-2.5 text-sm border border-stone-300 rounded-xl outline-none
                                   focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition bg-white">
                        @foreach(['UTC', 'Asia/Kolkata (IST)', 'America/New_York (EST)', 'Europe/London (GMT)', 'Asia/Dubai (GST)'] as $tz)
                            <option {{ old('timezone', $settings->timezone) === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="date_format" class="block text-sm font-medium text-stone-700 mb-1.5">Date Format</label>
                    <select id="date_format" name="date_format"
                            class="w-full px-3.5 py-2.5 text-sm border border-stone-300 rounded-xl outline-none
                                   focus:border-red-700 focus:ring-2 focus:ring-red-700/10 transition bg-white">
                        @foreach(['DD/MM/YYYY', 'MM/DD/YYYY', 'YYYY-MM-DD'] as $fmt)
                            <option {{ old('date_format', $settings->date_format) === $fmt ? 'selected' : '' }}>{{ $fmt }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="px-6 py-1 bg-stone-50 border-t border-stone-100 flex justify-end">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-red-800 hover:bg-red-700
                               text-white text-sm font-medium transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save Settings
                </button>
            </div>
        </div>

        {{-- ── Appearance ── --}}
        <div x-show="tab === 'appearance'"
             x-transition:enter="transition-opacity duration-150"
             x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             class="bg-white border border-stone-200 rounded-1xl overflow-hidden">
            <div class="px-6 py-5 border-b border-stone-100">
                <h3 class="text-sm font-semibold text-stone-800">Appearance</h3>
                <p class="text-xs text-stone-400 mt-0.5">Customize the look of your workspace.</p>
            </div>
            <div class="p-6 space-y-6">
                <div>
                    <label class="block text-sm font-medium text-stone-700 mb-3">Color Theme</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @foreach([
                            ['wolf_red',      'Wolf Red',      'from-stone-900 via-red-950 to-stone-950'],
                            ['deep_ocean',    'Deep Ocean',    'from-slate-900 via-blue-950 to-slate-950'],
                            ['graphite_mono', 'Graphite Mono', 'from-zinc-900 via-zinc-800 to-zinc-950'],
                        ] as [$value, $name, $gradient])
                        <label class="cursor-pointer">
                            <input type="radio" name="theme_color" value="{{ $value }}"
                                   {{ old('theme_color', $settings->theme_color) === $value ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="flex items-center gap-3 p-3.5 rounded-xl border-2 transition-all
                                        border-stone-200 peer-checked:border-red-700 peer-checked:bg-red-50
                                        hover:border-stone-300">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br {{ $gradient }} shrink-0"></div>
                                <span class="text-sm font-medium text-stone-700">{{ $name }}</span>
                                <svg class="w-4 h-4 text-red-700 ml-auto opacity-0 peer-checked:opacity-100 transition-opacity"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-start gap-3 p-4 bg-stone-50 border border-stone-200 rounded-xl">
                    <input type="checkbox" id="dense_view" name="dense_view" value="1"
                           {{ old('dense_view', $settings->dense_view) ? 'checked' : '' }}
                           class="mt-0.5 w-4 h-4 rounded border-stone-300 text-red-700 focus:ring-red-700 cursor-pointer">
                    <div>
                        <label for="dense_view" class="text-sm font-medium text-stone-800 cursor-pointer">Dense view</label>
                        <p class="text-xs text-stone-400 mt-0.5">Reduce spacing to show more content on screen.</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-1 bg-stone-50 border-t border-stone-100 flex justify-end">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-red-800 hover:bg-red-700
                               text-white text-sm font-medium transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save Settings
                </button>
            </div>
        </div>

    </form>
</div>
@endsection
