<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-stone-50 dark:bg-stone-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }} @yield('title')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @include('partials.cdn-assets')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="h-full font-sans antialiased text-stone-900 dark:text-stone-100">
    <div class="flex min-h-screen">
        <!-- Left Pane: Branding -->
        <div class="hidden lg:flex lg:flex-1 lg:flex-col lg:justify-center relative bg-gradient-to-br from-stone-800 via-red-950 to-black overflow-hidden">
            <!-- Decorative elements -->
            <div class="absolute inset-0 bg-black/30"></div>
            <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-white/5 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 w-full h-1/2 bg-gradient-to-t from-black/70 to-transparent"></div>

            <div class="relative z-10 flex flex-col items-center justify-center px-12 xl:px-24 h-full text-center">
                <!-- Icon -->
                <div class="flex items-center justify-center w-24 h-24 bg-white/10 backdrop-blur-md rounded-1xl mb-8 shadow-2xl border border-white/10">
                    <svg class="w-12 h-12 text-stone-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                    </svg>
                </div>
                
                <h1 class="text-4xl md:text-5xl font-bold tracking-tight text-white mb-6">Welcome to <span class="text-transparent bg-clip-text bg-gradient-to-r from-stone-300 to-red-300">{{ config('app.name') }}</span></h1>
                <p class="text-lg md:text-xl text-stone-300 max-w-lg mb-8 leading-relaxed">
                    Unleash your reading potential. Track, discover, and organize your favorite books in one stunning dashboard.
                </p>
                
                <div class="mt-8 flex items-center space-x-6">
                    <div class="flex -space-x-3">
                        <img class="w-10 h-10 rounded-full border-2 border-red-950" src="https://ui-avatars.com/api/?name=Alice&background=random" alt="User">
                        <img class="w-10 h-10 rounded-full border-2 border-red-950" src="https://ui-avatars.com/api/?name=Bob&background=random" alt="User">
                        <img class="w-10 h-10 rounded-full border-2 border-red-950" src="https://ui-avatars.com/api/?name=Charlie&background=random" alt="User">
                    </div>
                    <p class="text-sm font-medium text-stone-400">Join 10,000+ readers today.</p>
                </div>
            </div>
        </div>

        <!-- Right Pane: Form Container -->
        <div class="flex flex-1 flex-col justify-center px-6 py-12 lg:px-8 bg-white dark:bg-stone-900 shadow-2xl z-20">
            @yield('content')
        </div>
    </div>
</body>
</html>
