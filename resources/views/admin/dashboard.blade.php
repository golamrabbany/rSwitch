<x-admin-layout>
    <x-slot name="header">Dashboard</x-slot>

    {{-- Page Header with Greeting --}}
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    @php
                        $hour = now()->hour;
                        $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
                    @endphp
                    {{ $greeting }}, {{ auth()->user()->name }}
                </h1>
                <p class="text-gray-500 mt-1">Here's what's happening with your platform today.</p>
            </div>
            <div class="text-right hidden sm:block">
                <p class="text-sm font-medium text-gray-900">{{ now()->format('l, F j, Y') }}</p>
                <p class="text-sm text-gray-500">{{ now()->format('g:i A') }}</p>
            </div>
        </div>
    </div>

    {{-- Main Stats Grid --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        {{-- Total Revenue Card --}}
        <div class="dashboard-card dashboard-card-highlight">
            <div class="flex items-center justify-between mb-4">
                <div class="dashboard-card-icon bg-gradient-to-br from-indigo-500 to-purple-600">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-indigo-600 bg-indigo-50 px-2 py-1 rounded-full">7 Days</span>
            </div>
            <p class="text-3xl font-bold text-gray-900">${{ number_format($weekStats['total_cost'], 2) }}</p>
            <p class="text-sm text-gray-500 mt-1">Total Revenue</p>
            <div class="mt-3 pt-3 border-t border-gray-100">
                <div class="flex items-center text-sm">
                    <span class="text-gray-500">Today:</span>
                    <span class="ml-auto font-semibold text-gray-900">${{ number_format($todayStats['today_cost'], 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Total Calls Card --}}
        <div class="dashboard-card">
            <div class="flex items-center justify-between mb-4">
                <div class="dashboard-card-icon bg-gradient-to-br from-emerald-500 to-teal-600">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-1 rounded-full">7 Days</span>
            </div>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($weekStats['total_calls']) }}</p>
            <p class="text-sm text-gray-500 mt-1">Total Calls</p>
            <div class="mt-3 pt-3 border-t border-gray-100">
                <div class="flex items-center text-sm">
                    <span class="text-gray-500">Today:</span>
                    <span class="ml-auto font-semibold text-gray-900">{{ number_format($todayStats['today_calls']) }}</span>
                </div>
            </div>
        </div>

        {{-- Answer Rate Card --}}
        <div class="dashboard-card">
            <div class="flex items-center justify-between mb-4">
                <div class="dashboard-card-icon bg-gradient-to-br from-sky-500 to-blue-600">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium {{ $weekStats['asr'] >= 50 ? 'text-emerald-600 bg-emerald-50' : 'text-amber-600 bg-amber-50' }} px-2 py-1 rounded-full">ASR</span>
            </div>
            <p class="text-3xl font-bold text-gray-900">{{ $weekStats['asr'] }}%</p>
            <p class="text-sm text-gray-500 mt-1">Answer Success Rate</p>
            <div class="mt-3 pt-3 border-t border-gray-100">
                <div class="flex items-center text-sm">
                    <span class="text-gray-500">Answered:</span>
                    <span class="ml-auto font-semibold text-emerald-600">{{ number_format($weekStats['answered_calls']) }}</span>
                </div>
            </div>
        </div>

        {{-- Total Duration Card --}}
        @php
            $totalDur = $weekStats['total_duration'];
            $hours = intdiv($totalDur, 3600);
            $mins = intdiv($totalDur % 3600, 60);
        @endphp
        <div class="dashboard-card">
            <div class="flex items-center justify-between mb-4">
                <div class="dashboard-card-icon bg-gradient-to-br from-amber-500 to-orange-600">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-amber-600 bg-amber-50 px-2 py-1 rounded-full">Duration</span>
            </div>
            <p class="text-3xl font-bold text-gray-900 font-mono">{{ $hours }}h {{ $mins }}m</p>
            <p class="text-sm text-gray-500 mt-1">Total Talk Time</p>
            <div class="mt-3 pt-3 border-t border-gray-100">
                <div class="flex items-center text-sm">
                    <span class="text-gray-500">Billable:</span>
                    @php $billDur = $weekStats['total_billable']; @endphp
                    <span class="ml-auto font-semibold text-gray-900">{{ intdiv($billDur, 3600) }}h {{ intdiv($billDur % 3600, 60) }}m</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Two Column Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {{-- Entity Overview (Left - 2 cols) --}}
        <div class="lg:col-span-2">
            <div class="dashboard-section">
                <div class="dashboard-section-header">
                    <h2 class="dashboard-section-title">Platform Overview</h2>
                    <a href="{{ route('admin.users.index') }}" class="dashboard-section-link">
                        View All
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 p-5">
                    <a href="{{ route('admin.users.index', ['role' => 'reseller']) }}" class="entity-card group">
                        <div class="entity-card-icon bg-emerald-100 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2"/>
                                <circle cx="12" cy="7" r="4" stroke-width="2"/>
                            </svg>
                        </div>
                        <div class="entity-card-content">
                            <p class="entity-card-value">{{ number_format($entityCounts['resellers']) }}</p>
                            <p class="entity-card-label">Resellers</p>
                        </div>
                    </a>

                    <a href="{{ route('admin.users.index', ['role' => 'client']) }}" class="entity-card group">
                        <div class="entity-card-icon bg-sky-100 text-sky-600 group-hover:bg-sky-600 group-hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                                <circle cx="9" cy="7" r="4" stroke-width="2"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                            </svg>
                        </div>
                        <div class="entity-card-content">
                            <p class="entity-card-value">{{ number_format($entityCounts['clients']) }}</p>
                            <p class="entity-card-label">Clients</p>
                        </div>
                    </a>

                    <a href="{{ route('admin.sip-accounts.index') }}" class="entity-card group">
                        <div class="entity-card-icon bg-violet-100 text-violet-600 group-hover:bg-violet-600 group-hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </div>
                        <div class="entity-card-content">
                            <p class="entity-card-value">{{ number_format($entityCounts['sip_accounts']) }}</p>
                            <p class="entity-card-label">SIP Accounts</p>
                        </div>
                    </a>

                    <a href="{{ route('admin.trunks.index') }}" class="entity-card group">
                        <div class="entity-card-icon bg-indigo-100 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div class="entity-card-content">
                            <p class="entity-card-value">{{ number_format($entityCounts['active_trunks']) }}</p>
                            <p class="entity-card-label">Active Trunks</p>
                        </div>
                    </a>

                    <a href="{{ route('admin.dids.index') }}" class="entity-card group">
                        <div class="entity-card-icon bg-cyan-100 text-cyan-600 group-hover:bg-cyan-600 group-hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                            </svg>
                        </div>
                        <div class="entity-card-content">
                            <p class="entity-card-value">{{ number_format($entityCounts['active_dids']) }}</p>
                            <p class="entity-card-label">Active DIDs</p>
                        </div>
                    </a>

                    <a href="{{ route('admin.kyc.index') }}?status=pending" class="entity-card group {{ $entityCounts['pending_kyc'] > 0 ? 'ring-2 ring-amber-200' : '' }}">
                        <div class="entity-card-icon {{ $entityCounts['pending_kyc'] > 0 ? 'bg-amber-100 text-amber-600 group-hover:bg-amber-600' : 'bg-gray-100 text-gray-400 group-hover:bg-gray-600' }} group-hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div class="entity-card-content">
                            <p class="entity-card-value {{ $entityCounts['pending_kyc'] > 0 ? 'text-amber-600' : '' }}">{{ number_format($entityCounts['pending_kyc']) }}</p>
                            <p class="entity-card-label">Pending KYC</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        {{-- Quick Actions (Right - 1 col) --}}
        <div class="lg:col-span-1">
            <div class="dashboard-section h-full">
                <div class="dashboard-section-header">
                    <h2 class="dashboard-section-title">Quick Actions</h2>
                </div>
                <div class="p-4 space-y-3">
                    <a href="{{ route('admin.users.create') }}" class="quick-action-card group">
                        <div class="quick-action-card-icon bg-indigo-100 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-900 group-hover:text-indigo-600">Create User</p>
                            <p class="text-xs text-gray-500">Add reseller or client</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-300 group-hover:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    <a href="{{ route('admin.sip-accounts.create') }}" class="quick-action-card group">
                        <div class="quick-action-card-icon bg-emerald-100 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-900 group-hover:text-emerald-600">New SIP Account</p>
                            <p class="text-xs text-gray-500">Create SIP endpoint</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-300 group-hover:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    <a href="{{ route('admin.kyc.index') }}?status=pending" class="quick-action-card group">
                        <div class="quick-action-card-icon bg-amber-100 text-amber-600 group-hover:bg-amber-600 group-hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-900 group-hover:text-amber-600">Review KYC</p>
                            <p class="text-xs text-gray-500">Pending verifications</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-300 group-hover:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    <a href="{{ route('admin.cdr.index') }}" class="quick-action-card group">
                        <div class="quick-action-card-icon bg-sky-100 text-sky-600 group-hover:bg-sky-600 group-hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-900 group-hover:text-sky-600">View CDR</p>
                            <p class="text-xs text-gray-500">Call detail records</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-300 group-hover:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    <a href="{{ route('admin.rate-groups.index') }}" class="quick-action-card group">
                        <div class="quick-action-card-icon bg-violet-100 text-violet-600 group-hover:bg-violet-600 group-hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-900 group-hover:text-violet-600">Manage Rates</p>
                            <p class="text-xs text-gray-500">Rate groups & pricing</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-300 group-hover:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Calls Table --}}
    <div class="dashboard-section">
        <div class="dashboard-section-header">
            <h2 class="dashboard-section-title">Recent Calls</h2>
            <a href="{{ route('admin.cdr.index') }}" class="dashboard-section-link">
                View All
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Time</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Caller</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Callee</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Duration</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($recentCalls as $call)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-4 whitespace-nowrap">
                                <a href="{{ route('admin.cdr.show', ['uuid' => $call->uuid, 'date' => $call->call_start?->format('Y-m-d')]) }}"
                                   class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                    {{ $call->call_start?->format('H:i:s') }}
                                </a>
                                <p class="text-xs text-gray-400">{{ $call->call_start?->format('M d') }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-sm font-mono text-gray-900">{{ $call->caller }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-sm font-mono text-gray-900">{{ $call->callee }}</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <span class="text-sm font-mono text-gray-700 tabular-nums">
                                    {{ sprintf('%d:%02d', intdiv($call->duration, 60), $call->duration % 60) }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                @switch($call->disposition)
                                    @case('ANSWERED')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                            Answered
                                        </span>
                                        @break
                                    @case('NO ANSWER')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                                            No Answer
                                        </span>
                                        @break
                                    @case('BUSY')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-orange-500 mr-1.5"></span>
                                            Busy
                                        </span>
                                        @break
                                    @case('FAILED')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1.5"></span>
                                            Failed
                                        </span>
                                        @break
                                    @default
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                            {{ $call->disposition }}
                                        </span>
                                @endswitch
                            </td>
                            <td class="px-5 py-4">
                                @if($call->user)
                                    <a href="{{ route('admin.users.show', $call->user) }}" class="text-sm text-gray-700 hover:text-indigo-600">
                                        {{ $call->user->name }}
                                    </a>
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 font-medium">No calls yet today</p>
                                    <p class="text-sm text-gray-400 mt-1">Call records will appear here</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
