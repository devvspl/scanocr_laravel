<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-stone-50 dark:bg-stone-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - Help & Support</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @include('partials.cdn-assets')
</head>
<body class="font-sans antialiased text-stone-900 dark:text-stone-100 min-h-screen flex flex-col">

    {{-- Navbar --}}
    <nav class="bg-white dark:bg-stone-900 border-b border-stone-200 dark:border-stone-800 shadow-sm sticky top-0 z-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-red-900">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </div>
                    <a href="{{ route('login') }}" class="font-bold text-xl tracking-tight text-stone-900 dark:text-white">
                        {{ config('app.name') }}
                    </a>
                </div>
                <div class="flex items-center">
                    <a href="{{ route('login') }}" class="text-sm font-medium text-red-900 hover:text-red-800 dark:text-red-400">Back to Login</a>
                </div>
            </div>
        </div>
    </nav>

    {{-- Content --}}
    <main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 w-full">
        <div class="bg-white dark:bg-stone-900 shadow-xl rounded-xl p-8 sm:p-12 border border-stone-100 dark:border-stone-800">

            {{-- Header --}}
            <div class="flex items-start justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-extrabold text-stone-900 dark:text-white mb-2">Help & Support</h1>
                    <p class="text-stone-500 text-sm">Everything you need to get started with {{ config('app.name') }}.</p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-50 border border-red-200 text-red-700 font-semibold text-xs">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd"/>
                    </svg>
                    v1.0 — First Release
                </span>
            </div>

            <div class="space-y-10 text-stone-700 dark:text-stone-300 leading-relaxed">

                {{-- Getting Started --}}
                <section>
                    <h2 class="text-xl font-bold text-stone-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="flex items-center justify-center w-7 h-7 rounded-full bg-red-100 text-red-700 text-sm font-bold">1</span>
                        Getting Started
                    </h2>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div class="p-4 rounded-lg border border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-stone-800">
                            <h3 class="font-semibold text-stone-800 dark:text-white mb-1">Set Up Your Company</h3>
                            <p class="text-sm">Go to <strong>Settings → Company</strong> to enter your business name, address, logo, and financial year.</p>
                        </div>
                        <div class="p-4 rounded-lg border border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-stone-800">
                            <h3 class="font-semibold text-stone-800 dark:text-white mb-1">Add Users & Roles</h3>
                            <p class="text-sm">Go to <strong>Settings → Users</strong> to invite team members and assign roles with specific permissions.</p>
                        </div>
                        <div class="p-4 rounded-lg border border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-stone-800">
                            <h3 class="font-semibold text-stone-800 dark:text-white mb-1">Create Accounts & Groups</h3>
                            <p class="text-sm">Use <strong>Master → Accounts</strong> to set up your chart of accounts and account groups.</p>
                        </div>
                        <div class="p-4 rounded-lg border border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-stone-800">
                            <h3 class="font-semibold text-stone-800 dark:text-white mb-1">Add Products & Customers</h3>
                            <p class="text-sm">Use <strong>Master → Products</strong> and <strong>Master → Customers</strong> to build your master data before creating invoices.</p>
                        </div>
                    </div>
                </section>

                {{-- Key Features --}}
                <section>
                    <h2 class="text-xl font-bold text-stone-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="flex items-center justify-center w-7 h-7 rounded-full bg-red-100 text-red-700 text-sm font-bold">2</span>
                        Key Features
                    </h2>
                    <ul class="space-y-3 text-sm">
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-red-700 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span><strong>Sales Invoices & Proforma</strong> — Create, send, and track invoices with GST/tax support and digital signatures.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-red-700 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span><strong>Credit Notes & Delivery Notes</strong> — Manage returns and deliveries linked to your invoices.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-red-700 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span><strong>Journal Entries</strong> — Record manual accounting entries with full debit/credit control.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-red-700 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span><strong>Reports</strong> — Sales register, outstanding reports, and more — exportable to Excel.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-red-700 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span><strong>Data Import</strong> — Import Excel, CSV, or SQL files into any table with column mapping and conflict handling.</span>
                        </li>
                    </ul>
                </section>

                {{-- FAQ --}}
                <section>
                    <h2 class="text-xl font-bold text-stone-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="flex items-center justify-center w-7 h-7 rounded-full bg-red-100 text-red-700 text-sm font-bold">3</span>
                        Frequently Asked Questions
                    </h2>
                    <div class="space-y-4 text-sm">
                        <div class="border border-stone-200 dark:border-stone-700 rounded-lg p-4">
                            <p class="font-semibold text-stone-800 dark:text-white mb-1">How do I change my financial year?</p>
                            <p>Click the financial year selector in the top-right corner of the app. You can create and switch between multiple financial years under <strong>Settings → Financial Year</strong>.</p>
                        </div>
                        <div class="border border-stone-200 dark:border-stone-700 rounded-lg p-4">
                            <p class="font-semibold text-stone-800 dark:text-white mb-1">How do I reset my password?</p>
                            <p>On the login page, click <strong>Forgot Password</strong>. An OTP will be sent to your registered email address.</p>
                        </div>
                        <div class="border border-stone-200 dark:border-stone-700 rounded-lg p-4">
                            <p class="font-semibold text-stone-800 dark:text-white mb-1">Can I import data from another accounting software?</p>
                            <p>Yes. Use <strong>Master → Import</strong> to upload Excel or CSV files and map columns to the target table.</p>
                        </div>
                        <div class="border border-stone-200 dark:border-stone-700 rounded-lg p-4">
                            <p class="font-semibold text-stone-800 dark:text-white mb-1">Is my data secure?</p>
                            <p>All data is stored on your own server. Passwords are hashed and never stored in plain text. See our <a href="{{ route('privacy') }}" class="text-red-700 hover:underline">Privacy Policy</a> for full details.</p>
                        </div>
                    </div>
                </section>

                {{-- Contact --}}
                <section class="bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 rounded-xl p-6">
                    <h2 class="text-lg font-bold text-red-900 dark:text-red-300 mb-2">Still need help?</h2>
                    <p class="text-sm text-red-800 dark:text-red-400">
                        Reach out to your system administrator or the team that set up your {{ config('app.name') }} instance.
                        This is version <strong>1.0</strong> — the first release of the software.
                    </p>
                </section>

            </div>
        </div>
    </main>

    {{-- Footer --}}
    <footer class="py-6 text-center text-xs text-stone-400">
        <div class="flex items-center justify-center gap-4">
            <span>© {{ date('Y') }} {{ config('app.name') }} Accounting</span>
            <a href="{{ route('privacy') }}" class="hover:text-stone-600">Privacy</a>
            <a href="{{ route('terms') }}" class="hover:text-stone-600">Terms</a>
        </div>
    </footer>

</body>
</html>
