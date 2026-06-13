@extends('layouts.app')

@section('title', 'Profile')
@section('page-title', 'My Profile')

@section('content')
<div class="max-w-3xl mx-auto space-y-6" x-data="{ tab: 'info' }">

    {{-- Profile header card --}}
    <div class="bg-white border border-stone-200 rounded-1xl overflow-hidden">
        <div class="h-24 bg-gradient-to-r from-stone-900 via-red-950 to-stone-900"></div>
        <div class="px-6 pb-0">
            {{-- Avatar + name row --}}
            <div class="flex flex-wrap items-start gap-4 -mt-10 mb-5">
                <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=7f1d1d&color=fca5a5&size=160"
                     alt="Avatar"
                     class="w-20 h-20 rounded-1xl border-4 border-white shadow-lg shrink-0 mt-0">
                <div class="pt-12 flex-1 min-w-0">
                    <h2 class="text-lg font-bold text-stone-900 break-words">{{ $user->name }}</h2>
                    <p class="text-sm text-stone-500 break-all">{{ $user->email }}</p>
                </div>
                <span class="mt-12 inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-700 border border-green-200 shrink-0">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                    Active
                </span>
            </div>

            {{-- Tab nav — same style as settings page --}}
            <div class="flex gap-1">
                @foreach([
                    'info'     => ['Account Info',    'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                    'password' => ['Change Password', 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'],
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
    </div>

    {{-- Account Info tab --}}
    <div x-show="tab === 'info'" x-transition:enter="transition-opacity duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <form method="POST" action="{{ route('profile.update.info') }}"
              class="bg-white border border-stone-200 rounded-1xl overflow-hidden">
            @csrf
            <div class="px-6 py-5 border-b border-stone-100">
                <h3 class="text-sm font-semibold text-stone-800">Personal Information</h3>
                <p class="text-xs text-stone-400 mt-0.5">Update your name and email address.</p>
            </div>
            <div class="p-6 space-y-5">
                <div>
                    <label for="name" class="block text-sm font-medium text-stone-700 mb-1.5">Full Name</label>
                    <input type="text" id="name" name="name"
                           value="{{ old('name', $user->name) }}"
                           class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition
                                  border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10
                                  @error('name') border-red-400 bg-red-50 @enderror">
                    @error('name')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-stone-700 mb-1.5">Email Address</label>
                    <input type="email" id="email" name="email"
                           value="{{ old('email', $user->email) }}"
                           class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition
                                  border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10
                                  @error('email') border-red-400 bg-red-50 @enderror">
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Member Since</label>
                    <input type="text" value="{{ $user->created_at->format('d M Y') }}" disabled
                           class="w-full px-3.5 py-2.5 text-sm border border-stone-200 rounded-xl bg-stone-50 text-stone-400 cursor-not-allowed">
                </div>
            </div>
            <div class="px-6 py-1 bg-stone-50 border-t border-stone-100 flex justify-end">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-red-800 hover:bg-red-700
                               text-white text-sm font-medium transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    {{-- Change Password tab --}}
    <div x-show="tab === 'password'" x-transition:enter="transition-opacity duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <form method="POST" action="{{ route('profile.update.password') }}"
              class="bg-white border border-stone-200 rounded-1xl overflow-hidden">
            @csrf
            <div class="px-6 py-5 border-b border-stone-100">
                <h3 class="text-sm font-semibold text-stone-800">Change Password</h3>
                <p class="text-xs text-stone-400 mt-0.5">Use a strong password with at least 8 characters.</p>
            </div>
            <div class="p-6 space-y-5">
                <div>
                    <label for="current_password" class="block text-sm font-medium text-stone-700 mb-1.5">Current Password</label>
                    <input type="password" id="current_password" name="current_password"
                           class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition
                                  border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10
                                  @error('current_password') border-red-400 bg-red-50 @enderror">
                    @error('current_password')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-stone-700 mb-1.5">New Password</label>
                    <input type="password" id="password" name="password"
                           class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition
                                  border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10
                                  @error('password') border-red-400 bg-red-50 @enderror">
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-stone-700 mb-1.5">Confirm New Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="w-full px-3.5 py-2.5 text-sm border rounded-xl outline-none transition
                                  border-stone-300 focus:border-red-700 focus:ring-2 focus:ring-red-700/10">
                </div>

                <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl flex gap-3">
                    <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <p class="text-xs text-amber-700">You will remain logged in after changing your password.</p>
                </div>
            </div>
            <div class="px-6 py-1 bg-stone-50 border-t border-stone-100 flex justify-end">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-red-800 hover:bg-red-700
                               text-white text-sm font-medium transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Update Password
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
