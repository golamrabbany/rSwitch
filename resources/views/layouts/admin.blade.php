<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'rSwitch') }} - Admin</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 transform transition-transform duration-300 ease-in-out lg:translate-x-0 -translate-x-full flex flex-col" id="sidebar">
            <!-- Logo -->
            <div class="flex items-center h-16 px-5 border-b border-gray-100">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center">
                    <div class="w-9 h-9 bg-indigo-600 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-lg font-bold text-gray-800">r<span class="text-indigo-600">Switch</span></span>
                        <p class="text-[10px] text-gray-400 -mt-1">VoIP Management</p>
                    </div>
                </a>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-3 py-4 overflow-y-auto">
                <!-- Dashboard -->
                <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : 'text-gray-600' }}">
                    <svg class="nav-icon {{ request()->routeIs('admin.dashboard') ? 'text-indigo-600' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span class="nav-text">Dashboard</span>
                </a>

                <div class="my-3 border-t border-gray-100"></div>

                @php $isSuperAdmin = auth()->user()->isSuperAdmin(); @endphp

                <!-- Admin Management Menu (Super Admin Only) -->
                @if($isSuperAdmin)
                    @php
                        $adminMgmtActive = request()->routeIs('admin.super-admins.*', 'admin.admins.*', 'admin.recharge-admins.*');
                        $isSuperAdminSection = request()->routeIs('admin.super-admins.*');
                        $isAdminSection = request()->routeIs('admin.admins.*');
                        $isRechargeAdminSection = request()->routeIs('admin.recharge-admins.*');
                    @endphp
                    <div x-data="{ open: {{ $adminMgmtActive ? 'true' : 'false' }} }" class="mb-1">
                        <button @click="open = !open" class="nav-parent {{ $adminMgmtActive ? 'has-active' : 'text-gray-600' }}">
                            <div class="flex items-center">
                                <svg class="nav-icon {{ $adminMgmtActive ? 'text-indigo-600' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                <span class="nav-text">Admin Management</span>
                            </div>
                            <svg class="nav-chevron" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="open" x-collapse class="nav-children">
                            <a href="{{ route('admin.super-admins.index') }}" class="nav-child {{ $isSuperAdminSection ? 'active' : 'text-gray-500' }}">Super Admins</a>
                            <a href="{{ route('admin.admins.index') }}" class="nav-child {{ $isAdminSection ? 'active' : 'text-gray-500' }}">Regular Admins</a>
                            <a href="{{ route('admin.recharge-admins.index') }}" class="nav-child {{ $isRechargeAdminSection ? 'active' : 'text-gray-500' }}">Recharge Admins</a>
                        </div>
                    </div>

                    <div class="my-3 border-t border-gray-100"></div>
                @endif

                <!-- Accounts Menu -->
                @php
                    $usersActive = request()->routeIs('admin.users.*', 'admin.kyc.*');
                    // Check if viewing a specific user and determine their role
                    $viewingUser = null;
                    if (request()->route('user')) {
                        $viewingUser = request()->route('user');
                        if (is_numeric($viewingUser)) {
                            $viewingUser = \App\Models\User::find($viewingUser);
                        }
                    }
                    $isResellerSection = request('role') === 'reseller' || ($viewingUser && $viewingUser->role === 'reseller');
                    $isClientSection = request('role') === 'client' || ($viewingUser && $viewingUser->role === 'client');
                @endphp
                <div x-data="{ open: {{ $usersActive ? 'true' : 'false' }} }" class="mb-1">
                    <button @click="open = !open" class="nav-parent {{ $usersActive ? 'has-active' : 'text-gray-600' }}">
                        <div class="flex items-center">
                            <svg class="nav-icon {{ $usersActive ? 'text-indigo-600' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <span class="nav-text">Accounts</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="open" x-collapse class="nav-children">
                        <a href="{{ route('admin.users.index', ['role' => 'reseller']) }}" class="nav-child {{ $isResellerSection ? 'active' : 'text-gray-500' }}">Resellers</a>
                        <a href="{{ route('admin.users.index', ['role' => 'client']) }}" class="nav-child {{ $isClientSection ? 'active' : 'text-gray-500' }}">Clients</a>
                        <a href="{{ route('admin.kyc.index') }}" class="nav-child {{ request()->routeIs('admin.kyc.*') ? 'active' : 'text-gray-500' }}">KYC Review</a>
                    </div>
                </div>

                <!-- Telecom Menu -->
                @php
                    $telecomActive = request()->routeIs('admin.sip-accounts.*', 'admin.dids.*', 'admin.ring-groups.*');
                    if ($isSuperAdmin) {
                        $telecomActive = $telecomActive || request()->routeIs('admin.trunks.*', 'admin.trunk-routes.*', 'admin.rate-groups.*');
                    }
                @endphp
                <div x-data="{ open: {{ $telecomActive ? 'true' : 'false' }} }" class="mb-1">
                    <button @click="open = !open" class="nav-parent {{ $telecomActive ? 'has-active' : 'text-gray-600' }}">
                        <div class="flex items-center">
                            <svg class="nav-icon {{ $telecomActive ? 'text-indigo-600' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <span class="nav-text">Telecom</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="open" x-collapse class="nav-children">
                        <a href="{{ route('admin.sip-accounts.index') }}" class="nav-child {{ request()->routeIs('admin.sip-accounts.*') ? 'active' : 'text-gray-500' }}">SIP Accounts</a>
                        @if($isSuperAdmin)
                            <a href="{{ route('admin.trunks.index') }}" class="nav-child {{ request()->routeIs('admin.trunks.*') ? 'active' : 'text-gray-500' }}">Trunks</a>
                            <a href="{{ route('admin.trunk-routes.index') }}" class="nav-child {{ request()->routeIs('admin.trunk-routes.*') ? 'active' : 'text-gray-500' }}">Routing Rules</a>
                        @endif
                        <a href="{{ route('admin.dids.index') }}" class="nav-child {{ request()->routeIs('admin.dids.*') ? 'active' : 'text-gray-500' }}">DIDs</a>
                        <a href="{{ route('admin.ring-groups.index') }}" class="nav-child {{ request()->routeIs('admin.ring-groups.*') ? 'active' : 'text-gray-500' }}">Ring Groups</a>
                        @if($isSuperAdmin)
                            <a href="{{ route('admin.rate-groups.index') }}" class="nav-child {{ request()->routeIs('admin.rate-groups.*') ? 'active' : 'text-gray-500' }}">Rates</a>
                        @endif
                    </div>
                </div>

                <!-- CDR -->
                <a href="{{ route('admin.cdr.index') }}" class="nav-item {{ request()->routeIs('admin.cdr.*') ? 'active' : 'text-gray-600' }}">
                    <svg class="nav-icon {{ request()->routeIs('admin.cdr.*') ? 'text-indigo-600' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="nav-text">CDR / Reports</span>
                </a>

                <!-- Operational Reports -->
                @php $operationalActive = request()->routeIs('admin.operational-reports.*'); @endphp
                <div x-data="{ open: {{ $operationalActive ? 'true' : 'false' }} }" class="mb-1">
                    <button @click="open = !open" class="nav-parent {{ $operationalActive ? 'has-active' : 'text-gray-600' }}">
                        <div class="flex items-center">
                            <svg class="nav-icon {{ $operationalActive ? 'text-indigo-600' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <span class="nav-text">Operational Reports</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="open" x-collapse class="nav-children">
                        <a href="{{ route('admin.operational-reports.index') }}" class="nav-child {{ request()->routeIs('admin.operational-reports.index') ? 'active' : 'text-gray-500' }}">Dashboard</a>
                        <a href="{{ route('admin.operational-reports.active') }}" class="nav-child {{ request()->routeIs('admin.operational-reports.active') ? 'active' : 'text-gray-500' }}">Active Calls</a>
                        <a href="{{ route('admin.operational-reports.inbound') }}" class="nav-child {{ request()->routeIs('admin.operational-reports.inbound') ? 'active' : 'text-gray-500' }}">Inbound Calls</a>
                        <a href="{{ route('admin.operational-reports.outbound') }}" class="nav-child {{ request()->routeIs('admin.operational-reports.outbound') ? 'active' : 'text-gray-500' }}">Outbound Calls</a>
                        <a href="{{ route('admin.operational-reports.p2p') }}" class="nav-child {{ request()->routeIs('admin.operational-reports.p2p') ? 'active' : 'text-gray-500' }}">P2P Calls</a>
                        <a href="{{ route('admin.operational-reports.summary') }}" class="nav-child {{ request()->routeIs('admin.operational-reports.summary') ? 'active' : 'text-gray-500' }}">Call Summary</a>
                        <a href="{{ route('admin.operational-reports.daily') }}" class="nav-child {{ request()->routeIs('admin.operational-reports.daily') ? 'active' : 'text-gray-500' }}">Daily Summary</a>
                        <a href="{{ route('admin.operational-reports.monthly') }}" class="nav-child {{ request()->routeIs('admin.operational-reports.monthly') ? 'active' : 'text-gray-500' }}">Monthly Summary</a>
                        <a href="{{ route('admin.operational-reports.hourly') }}" class="nav-child {{ request()->routeIs('admin.operational-reports.hourly') ? 'active' : 'text-gray-500' }}">Hourly Summary</a>
                    </div>
                </div>

                <div class="my-3 border-t border-gray-100"></div>

                <!-- Finance Menu -->
                @php $financeActive = request()->routeIs('admin.transactions.*', 'admin.balance.*', 'admin.invoices.*', 'admin.payments.*'); @endphp
                <div x-data="{ open: {{ $financeActive ? 'true' : 'false' }} }" class="mb-1">
                    <button @click="open = !open" class="nav-parent {{ $financeActive ? 'has-active' : 'text-gray-600' }}">
                        <div class="flex items-center">
                            <svg class="nav-icon {{ $financeActive ? 'text-indigo-600' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="nav-text">Finance</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="open" x-collapse class="nav-children">
                        <a href="{{ route('admin.transactions.index') }}" class="nav-child {{ request()->routeIs('admin.transactions.*') ? 'active' : 'text-gray-500' }}">Transactions</a>
                        <a href="{{ route('admin.balance.create') }}" class="nav-child {{ request()->routeIs('admin.balance.*') ? 'active' : 'text-gray-500' }}">Balance</a>
                        <a href="{{ route('admin.invoices.index') }}" class="nav-child {{ request()->routeIs('admin.invoices.*') ? 'active' : 'text-gray-500' }}">Invoices</a>
                        <a href="{{ route('admin.payments.index') }}" class="nav-child {{ request()->routeIs('admin.payments.*') ? 'active' : 'text-gray-500' }}">Payments</a>
                    </div>
                </div>

                <!-- System Menu (Super Admin Only) -->
                @if($isSuperAdmin)
                    @php $systemActive = request()->routeIs('admin.audit-logs.*', 'admin.blacklist.*', 'admin.whitelist.*', 'admin.webhooks.*', 'admin.settings.*', 'admin.bulk-import.*', 'admin.transfer-logs.*', 'admin.rate-imports.*'); @endphp
                    <div x-data="{ open: {{ $systemActive ? 'true' : 'false' }} }" class="mb-1">
                        <button @click="open = !open" class="nav-parent {{ $systemActive ? 'has-active' : 'text-gray-600' }}">
                            <div class="flex items-center">
                                <svg class="nav-icon {{ $systemActive ? 'text-indigo-600' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span class="nav-text">System</span>
                            </div>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="open" x-collapse class="nav-children">
                            <a href="{{ route('admin.audit-logs.index') }}" class="nav-child {{ request()->routeIs('admin.audit-logs.*') ? 'active' : 'text-gray-500' }}">Audit Logs</a>
                            <a href="{{ route('admin.blacklist.index') }}" class="nav-child {{ request()->routeIs('admin.blacklist.*') ? 'active' : 'text-gray-500' }}">Destination Lists</a>
                            <a href="{{ route('admin.webhooks.index') }}" class="nav-child {{ request()->routeIs('admin.webhooks.*') ? 'active' : 'text-gray-500' }}">Webhooks</a>
                            <a href="{{ route('admin.bulk-import.index') }}" class="nav-child {{ request()->routeIs('admin.bulk-import.*') ? 'active' : 'text-gray-500' }}">Bulk Import</a>
                            <a href="{{ route('admin.settings.index') }}" class="nav-child {{ request()->routeIs('admin.settings.*') ? 'active' : 'text-gray-500' }}">System Settings</a>
                        </div>
                    </div>
                @endif
            </nav>

            <!-- Sidebar Footer -->
            <div class="p-4 border-t border-gray-100">
                <div class="flex items-center px-2 py-2 text-xs text-gray-400">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    v1.0.0 - Admin Panel
                </div>
            </div>
        </aside>

        <!-- Mobile Overlay -->
        <div class="fixed inset-0 bg-black/50 z-40 lg:hidden hidden" id="sidebar-overlay" onclick="toggleSidebar()"></div>

        <!-- Main Content -->
        <div class="flex-1 lg:ml-64">
            <!-- Top Header -->
            <header class="sticky top-0 z-30 bg-white border-b border-gray-200">
                <div class="flex items-center justify-between h-16 px-4 lg:px-6">
                    <!-- Left Side -->
                    <div class="flex items-center">
                        <button class="lg:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 mr-3" onclick="toggleSidebar()">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                        @if (isset($header))
                            <h2 class="text-lg font-semibold text-gray-800">{{ $header }}</h2>
                        @endif
                    </div>

                    <!-- Right Side -->
                    <div class="flex items-center space-x-3">
                        <!-- User Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-2 p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                                <span class="text-sm font-medium text-gray-700">{{ auth()->user()->name }}</span>
                                <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center">
                                    <span class="text-white font-semibold text-sm">{{ substr(auth()->user()->name, 0, 1) }}</span>
                                </div>
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div x-show="open"
                                 @click.away="open = false"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50">
                                <div class="px-4 py-3 border-b border-gray-100">
                                    <p class="text-sm font-medium text-gray-800">{{ auth()->user()->name }}</p>
                                    <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
                                </div>
                                <a href="{{ route('admin.profile') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    My Profile
                                </a>
                                <a href="{{ route('admin.profile') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <svg class="w-4 h-4 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                    </svg>
                                    Change Password
                                </a>
                                <div class="border-t border-gray-100 mt-2 pt-2">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                            </svg>
                                            Logout
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-4 lg:p-6">
                @if(session('success'))
                    <div class="mb-6 flash-message flash-success" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-2">
                        <div class="flash-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div class="flash-content">
                            <p class="flash-title">Success</p>
                            <p class="flash-text">{{ session('success') }}</p>
                        </div>
                        <button @click="show = false" class="flash-close">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="mb-6 flash-message flash-warning" x-data="{ show: true }" x-show="show" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-2">
                        <div class="flash-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div class="flash-content">
                            <p class="flash-title">Warning</p>
                            <p class="flash-text">{{ session('warning') }}</p>
                        </div>
                        <button @click="show = false" class="flash-close">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 flash-message flash-error" x-data="{ show: true }" x-show="show" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-2">
                        <div class="flash-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                        <div class="flash-content">
                            <p class="flash-title">Error</p>
                            <p class="flash-text">{{ session('error') }}</p>
                        </div>
                        <button @click="show = false" class="flash-close">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-6 flash-message flash-error" x-data="{ show: true }" x-show="show" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform -translate-y-2">
                        <div class="flash-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flash-content flex-1">
                            <p class="flash-title">Please fix the following errors</p>
                            <ul class="flash-list">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <button @click="show = false" class="flash-close">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }
    </script>

    @stack('scripts')

    {{-- Impersonation Banner --}}
    <x-impersonation-banner />
</body>
</html>
