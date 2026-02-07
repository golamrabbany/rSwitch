<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'rSwitch') }} - Client</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen bg-gray-50">
            {{-- Mobile sidebar overlay --}}
            <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300"
                 x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-300"
                 x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false">
            </div>

            {{-- Sidebar --}}
            <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                   class="fixed inset-y-0 left-0 z-50 w-72 bg-sidebar transition-transform duration-300 ease-in-out lg:translate-x-0 flex flex-col">
                {{-- Logo --}}
                <div class="flex h-16 items-center justify-between px-6 border-b border-white/10">
                    <a href="{{ route('client.dashboard') }}" class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-sky-500 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <span class="text-lg font-semibold text-white">rSwitch</span>
                    </a>
                    <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Navigation --}}
                <nav class="flex-1 overflow-y-auto py-4 px-4 space-y-1">
                    <a href="{{ route('client.dashboard') }}"
                       class="{{ request()->routeIs('client.dashboard') ? 'nav-item-active' : 'nav-item-inactive' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0h4"/>
                        </svg>
                        Dashboard
                    </a>

                    <a href="{{ route('client.sip-accounts.index') }}"
                       class="{{ request()->routeIs('client.sip-accounts.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        SIP Accounts
                    </a>

                    <a href="{{ route('client.dids.index') }}"
                       class="{{ request()->routeIs('client.dids.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                        </svg>
                        DIDs
                    </a>

                    <a href="{{ route('client.cdr.index') }}"
                       class="{{ request()->routeIs('client.cdr.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        CDR / Reports
                    </a>

                    <div class="pt-4 mt-4 border-t border-white/10">
                        <p class="px-3 mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Billing</p>
                    </div>

                    <a href="{{ route('client.transactions.index') }}"
                       class="{{ request()->routeIs('client.transactions.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        Transactions
                    </a>

                    <a href="{{ route('client.invoices.index') }}"
                       class="{{ request()->routeIs('client.invoices.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Invoices
                    </a>

                    <a href="{{ route('client.payments.create') }}"
                       class="{{ request()->routeIs('client.payments.*') ? 'nav-item-active' : 'nav-item-inactive' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Add Funds
                    </a>
                </nav>

                {{-- Sidebar footer --}}
                <div class="border-t border-white/10 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-sky-400 to-sky-600 flex items-center justify-center text-sm font-semibold text-white">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-sky-400 font-medium">${{ number_format(auth()->user()->balance, 2) }}</p>
                        </div>
                    </div>
                </div>
            </aside>

            {{-- Main content --}}
            <div class="lg:pl-72">
                {{-- Top bar --}}
                <header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-gray-200 bg-white/95 backdrop-blur px-4 sm:px-6 lg:px-8">
                    <button @click="sidebarOpen = true" class="-m-2.5 p-2.5 text-gray-500 lg:hidden hover:text-gray-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    @if (isset($header))
                        <h1 class="text-lg font-semibold text-gray-900">{{ $header }}</h1>
                    @endif

                    <div class="flex items-center gap-2 ml-auto">
                        <a href="{{ route('profile') }}" class="btn-ghost text-sm">Profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn-ghost text-sm text-red-600 hover:text-red-700 hover:bg-red-50">Log Out</button>
                        </form>
                    </div>
                </header>

                {{-- KYC Banner --}}
                @if(auth()->user()->kyc_status !== 'approved')
                    <div class="mx-4 mt-4 sm:mx-6 lg:mx-8">
                        <div class="flex items-center gap-3 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3">
                            <svg class="h-5 w-5 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm font-medium text-amber-800">
                                @if(auth()->user()->kyc_status === 'pending')
                                    Your KYC verification is under review.
                                @elseif(auth()->user()->kyc_status === 'rejected')
                                    Your KYC was rejected. <a href="{{ route('kyc.show') }}" class="underline font-semibold">Please resubmit</a>.
                                @else
                                    Please <a href="{{ route('kyc.show') }}" class="underline font-semibold">complete your KYC verification</a> to access all features.
                                @endif
                            </p>
                        </div>
                    </div>
                @endif

                {{-- Flash messages --}}
                @if (session('success'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                         x-transition:leave="transition ease-in duration-300"
                         x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
                         class="mx-4 mt-4 sm:mx-6 lg:mx-8">
                        <div class="flex items-center gap-3 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3">
                            <svg class="h-5 w-5 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
                        </div>
                    </div>
                @endif

                @if (session('warning'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 8000)"
                         class="mx-4 mt-4 sm:mx-6 lg:mx-8">
                        <div class="flex items-center gap-3 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3">
                            <svg class="h-5 w-5 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm font-medium text-amber-800">{{ session('warning') }}</p>
                        </div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mx-4 mt-4 sm:mx-6 lg:mx-8">
                        <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3">
                            <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                {{-- Page content --}}
                <main class="py-6 px-4 sm:px-6 lg:px-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
