<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-stone-50 dark:bg-stone-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - Terms of Service</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @include('partials.cdn-assets')
</head>
<body class="font-sans antialiased text-stone-900 dark:text-stone-100 min-h-screen flex flex-col">
    <!-- Simple Navbar -->
    <nav class="bg-white dark:bg-stone-900 border-b border-stone-200 dark:border-stone-800 shadow-sm sticky top-0 z-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="shrink-0 flex items-center gap-3">
                        <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-red-900">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                            </svg>
                        </div>
                        <a href="{{ route('login') }}" class="font-bold text-xl tracking-tight text-stone-900 dark:text-white">
                            {{ config('app.name') }}
                        </a>
                    </div>
                </div>
                <div class="flex items-center">
                    <a href="{{ route('login') }}" class="text-sm font-medium text-red-900 hover:text-red-800 dark:text-red-400">Back to Login</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 w-full">
        <div class="bg-white dark:bg-stone-900 shadow-xl rounded-1xl p-8 sm:p-12 border border-stone-100 dark:border-stone-800">
            <h1 class="text-3xl font-extrabold text-stone-900 dark:text-white mb-6">Terms of Service</h1>
            <p class="text-sm text-stone-500 mb-8">Last updated: {{ date('F j, Y') }}</p>
            
            <div class="space-y-6 text-stone-700 dark:text-stone-300 leading-relaxed">
                <section>
                    <h2 class="text-xl font-bold text-stone-900 dark:text-white mb-3">1. Acceptance of Terms</h2>
                    <p>By accessing and using {{ config('app.name') }}, you accept and agree to be bound by the terms and provision of this agreement.</p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-stone-900 dark:text-white mb-3">2. User Accounts</h2>
                    <p>To use certain features, you must register for an account using OTP verification. You are responsible for maintaining the confidentiality of your account credentials.</p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-stone-900 dark:text-white mb-3">3. Acceptable Use</h2>
                    <p>You agree not to use the application for any unlawful purpose or in any way that interrupts, damages, or impairs the service.</p>
                </section>
                
                <section>
                    <h2 class="text-xl font-bold text-stone-900 dark:text-white mb-3">4. Intellectual Property</h2>
                    <p>The application and its original content, features, and functionality are owned by {{ config('app.name') }} and are protected by international copyright and intellectual property laws.</p>
                </section>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white dark:bg-stone-900 border-t border-stone-200 dark:border-stone-800 py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm text-stone-500">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </footer>
</body>
</html>
