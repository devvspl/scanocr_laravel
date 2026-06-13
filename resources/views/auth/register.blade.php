@extends('layouts.guest')
@section('title', '- Register')

@section('content')
<div class="sm:mx-auto sm:w-full sm:max-w-md">
    <!-- Mobile header fallback -->
    <div class="lg:hidden flex justify-center mb-6">
        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-red-900 shadow-lg">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
            </svg>
        </div>
    </div>

    <h2 class="mt-2 text-center text-3xl font-extrabold tracking-tight text-stone-900 dark:text-white">
        Create an account
    </h2>
    <p class="mt-2 text-center text-sm text-stone-600 dark:text-stone-400">
        Already have an account?
        <a href="{{ route('login') }}" class="font-medium text-red-900 hover:text-red-800 dark:text-red-400 transition-colors">
            Sign in instead
        </a>
    </p>
</div>

<div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
    <div class="bg-white py-8 px-4 shadow-xl sm:rounded-1xl sm:px-10 border border-stone-100 dark:bg-stone-900/80 dark:border-stone-800/50 backdrop-blur-md">
        <form class="space-y-6" action="{{ route('register') }}" method="POST" x-data="{ submitting: false }" @submit="submitting = true">
            @csrf

            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-stone-700 dark:text-stone-300">
                    Full name
                </label>
                <div class="mt-2">
                    <input id="name" name="name" type="text" autocomplete="name" required value="{{ old('name') }}" autofocus
                        class="appearance-none block w-full px-3 py-2 text-sm border @error('name') border-red-300 text-red-900 placeholder-red-300 focus:ring-red-900 focus:border-red-900 @else border-stone-300 dark:border-stone-700 focus:ring-red-900 focus:border-red-900 dark:bg-stone-950/50 dark:text-white @enderror rounded-lg shadow-sm placeholder-stone-400 focus:outline-none focus:ring-2 transition-all duration-200">
                </div>
                @error('name')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email Address -->
            <div>
                <label for="email" class="block text-sm font-medium text-stone-700 dark:text-stone-300">
                    Email address
                </label>
                <div class="mt-2">
                    <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"
                        class="appearance-none block w-full px-3 py-2 text-sm border @error('email') border-red-300 text-red-900 placeholder-red-300 focus:ring-red-900 focus:border-red-900 @else border-stone-300 dark:border-stone-700 focus:ring-red-900 focus:border-red-900 dark:bg-stone-950/50 dark:text-white @enderror rounded-lg shadow-sm placeholder-stone-400 focus:outline-none focus:ring-2 transition-all duration-200">
                </div>
                @error('email')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-stone-700 dark:text-stone-300">
                    Password
                </label>
                <div class="mt-2">
                    <input id="password" name="password" type="password" autocomplete="new-password" required
                        class="appearance-none block w-full px-3 py-2 text-sm border @error('password') border-red-300 text-red-900 placeholder-red-300 focus:ring-red-900 focus:border-red-900 @else border-stone-300 dark:border-stone-700 focus:ring-red-900 focus:border-red-900 dark:bg-stone-950/50 dark:text-white @enderror rounded-lg shadow-sm placeholder-stone-400 focus:outline-none focus:ring-2 transition-all duration-200">
                </div>
                @error('password')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-stone-700 dark:text-stone-300">
                    Confirm Password
                </label>
                <div class="mt-2">
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                        class="appearance-none block w-full px-3 py-2 text-sm border border-stone-300 dark:border-stone-700 rounded-lg shadow-sm placeholder-stone-400 focus:outline-none focus:ring-2 focus:ring-red-900 focus:border-red-900 dark:bg-stone-950/50 dark:text-white transition-all duration-200">
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" x-bind:disabled="submitting"
                    class="w-full inline-flex justify-center items-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-red-900 hover:bg-black focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-900 dark:focus:ring-offset-stone-900 transition-colors duration-200 disabled:opacity-75 disabled:cursor-not-allowed">
                    <svg x-show="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" style="display: none;">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="submitting ? 'Registering...' : 'Register'">Register</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Legal Footer -->
    <div class="mt-8 text-center text-xs text-stone-500 dark:text-stone-400">
        By registering, you agree to our 
        <a href="{{ route('terms') }}" class="font-medium text-stone-700 hover:text-red-900 dark:text-stone-300 dark:hover:text-red-400 transition-colors">Terms of Service</a> and 
        <a href="{{ route('privacy') }}" class="font-medium text-stone-700 hover:text-red-900 dark:text-stone-300 dark:hover:text-red-400 transition-colors">Privacy Policy</a>.
    </div>
</div>
@endsection
