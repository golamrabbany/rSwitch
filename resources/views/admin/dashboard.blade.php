<x-admin-layout>
    <x-slot name="header">Dashboard</x-slot>

    {{-- Live Operations Section --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6" id="live-section">
        {{-- Concurrent Calls --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <p class="text-gray-500 text-xs font-medium uppercase tracking-wide">Live Calls</p>
                <span id="ws-status" class="w-2 h-2 rounded-full bg-gray-300" title="WebSocket: connecting..."></span>
            </div>
            <div class="flex items-center gap-2">
                <p class="text-3xl font-bold text-gray-900 tabular-nums" id="live-concurrent">0</p>
                <span id="live-pulse" class="relative flex h-3 w-3 hidden">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                </span>
            </div>
            <p class="text-xs text-gray-400 mt-1">concurrent channels</p>
        </div>

        {{-- CPS --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <p class="text-gray-500 text-xs font-medium uppercase tracking-wide">CPS</p>
                <span class="text-xs font-medium text-gray-400">calls/sec</span>
            </div>
            <p class="text-3xl font-bold text-gray-900 tabular-nums" id="live-cps">0.0</p>
            <p class="text-xs text-gray-400 mt-1">calls per second</p>
        </div>

        {{-- Live ASR --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <p class="text-gray-500 text-xs font-medium uppercase tracking-wide">Live ASR</p>
                <span id="live-asr-badge" class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">--</span>
            </div>
            <p class="text-3xl font-bold tabular-nums" id="live-asr">0.0<span class="text-lg">%</span></p>
            <p class="text-xs text-gray-400 mt-1">today's answer rate</p>
        </div>

        {{-- Today's Activity --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <p class="text-gray-500 text-xs font-medium uppercase tracking-wide">Today's Activity</p>
                <span class="text-xs font-medium text-gray-400">live</span>
            </div>
            <p class="text-3xl font-bold text-gray-900 tabular-nums" id="live-today-calls">0</p>
            <div class="flex items-center gap-3 mt-1 text-xs">
                <span class="text-emerald-600"><span id="live-today-answered">0</span> answered</span>
                <span class="text-gray-300">|</span>
                <span class="text-red-500"><span id="live-today-failed">0</span> failed</span>
            </div>
        </div>
    </div>

    {{-- Hero Section with Live Stats --}}
    <div class="mb-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    @php
                        $hour = now()->hour;
                        $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
                    @endphp
                    {{ $greeting }}, {{ auth()->user()->name }}
                </h1>
                <p class="text-gray-500 mt-1">Here's your platform overview</p>
            </div>
            <div class="flex items-center gap-4">
                {{-- Live Active Calls Indicator --}}
                <div class="flex items-center gap-2 px-4 py-2 bg-white rounded-lg border border-gray-200 shadow-sm">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $activeCalls > 0 ? 'bg-emerald-400' : 'bg-gray-300' }} opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 {{ $activeCalls > 0 ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                    </span>
                    <span class="text-sm font-semibold text-gray-900">{{ $activeCalls }}</span>
                    <span class="text-sm text-gray-500">Active {{ Str::plural('Call', $activeCalls) }}</span>
                </div>
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-medium text-gray-900">{{ now()->format('l, F j, Y') }}</p>
                    <p class="text-sm text-gray-500">{{ now()->format('g:i A') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Primary KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Revenue Card --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-gray-400">7 Days</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ format_currency($weekStats['total_cost']) }}</p>
            <p class="text-sm text-gray-500 mb-2">Revenue</p>
            <div class="flex items-center text-xs">
                @php
                    $revenueChange = $prevWeekStats['total_cost'] > 0
                        ? round((($weekStats['total_cost'] - $prevWeekStats['total_cost']) / $prevWeekStats['total_cost']) * 100, 1)
                        : 0;
                @endphp
                @if($revenueChange >= 0)
                    <span class="inline-flex items-center text-emerald-600">
                        <svg class="w-3 h-3 mr-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                        {{ abs($revenueChange) }}%
                    </span>
                @else
                    <span class="inline-flex items-center text-red-600">
                        <svg class="w-3 h-3 mr-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        {{ abs($revenueChange) }}%
                    </span>
                @endif
                <span class="text-gray-400 ml-1">vs last week</span>
            </div>
        </div>

        {{-- Total Calls Card --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-gray-400">7 Days</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($weekStats['total_calls']) }}</p>
            <p class="text-sm text-gray-500 mb-2">Total Calls</p>
            <div class="flex items-center text-xs">
                @php
                    $callsChange = $prevWeekStats['total_calls'] > 0
                        ? round((($weekStats['total_calls'] - $prevWeekStats['total_calls']) / $prevWeekStats['total_calls']) * 100, 1)
                        : 0;
                @endphp
                @if($callsChange >= 0)
                    <span class="inline-flex items-center text-emerald-600">
                        <svg class="w-3 h-3 mr-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                        {{ abs($callsChange) }}%
                    </span>
                @else
                    <span class="inline-flex items-center text-red-600">
                        <svg class="w-3 h-3 mr-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        {{ abs($callsChange) }}%
                    </span>
                @endif
                <span class="text-gray-400 ml-1">vs last week</span>
            </div>
        </div>

        {{-- ASR Card --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $weekStats['asr'] >= 50 ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600' }}">ASR</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $weekStats['asr'] }}%</p>
            <p class="text-sm text-gray-500 mb-2">Answer Rate</p>
            <div class="flex items-center text-xs gap-3">
                <span class="text-emerald-600">{{ number_format($weekStats['answered_calls']) }} answered</span>
                <span class="text-gray-300">|</span>
                <span class="text-red-500">{{ number_format($weekStats['failed_calls']) }} failed</span>
            </div>
        </div>

        {{-- ACD Card --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-gray-400">ACD</span>
            </div>
            @php
                $acdMins = intdiv($weekStats['acd'], 60);
                $acdSecs = $weekStats['acd'] % 60;
            @endphp
            <p class="text-2xl font-bold text-gray-900 tabular-nums">{{ $acdMins }}:{{ sprintf('%02d', $acdSecs) }}</p>
            <p class="text-sm text-gray-500 mb-2">Avg Call Duration</p>
            @php
                $totalHours = intdiv($weekStats['total_duration'], 3600);
                $totalMins = intdiv($weekStats['total_duration'] % 3600, 60);
            @endphp
            <div class="flex items-center text-xs text-gray-500">
                Total: {{ $totalHours }}h {{ $totalMins }}m talk time
            </div>
        </div>
    </div>

    {{-- Today's Stats Banner --}}
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl p-5 mb-6 text-white shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h3 class="text-lg font-semibold opacity-90">Today's Performance</h3>
                <p class="text-sm opacity-70">{{ now()->format('l, F j') }}</p>
            </div>
            <div class="flex items-center gap-8">
                <div class="text-center">
                    <p class="text-3xl font-bold">{{ number_format($todayStats['today_calls']) }}</p>
                    <p class="text-sm opacity-70">Calls</p>
                </div>
                <div class="w-px h-10 bg-white/20"></div>
                <div class="text-center">
                    <p class="text-3xl font-bold">{{ format_currency($todayStats['today_cost']) }}</p>
                    <p class="text-sm opacity-70">Revenue</p>
                </div>
                <div class="w-px h-10 bg-white/20"></div>
                <div class="text-center">
                    @php
                        $todayAsr = $todayStats['today_calls'] > 0
                            ? round(($todayStats['today_answered'] / $todayStats['today_calls']) * 100, 1)
                            : 0;
                    @endphp
                    <p class="text-3xl font-bold">{{ $todayAsr }}%</p>
                    <p class="text-sm opacity-70">ASR</p>
                </div>
                <div class="w-px h-10 bg-white/20 hidden lg:block"></div>
                <div class="text-center hidden lg:block">
                    @php
                        $todayDurMins = intdiv($todayStats['today_duration'], 60);
                    @endphp
                    <p class="text-3xl font-bold tabular-nums">{{ $todayDurMins }}</p>
                    <p class="text-sm opacity-70">Minutes</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-12 gap-6 mb-6">
        {{-- Call Volume Chart (7-day) --}}
        <div class="col-span-12 lg:col-span-8">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Call Volume Trend</h3>
                    <span class="text-xs text-gray-400">Last 7 days</span>
                </div>
                <div class="p-5">
                    <div class="h-48" x-data="callVolumeChart()" x-init="initChart()">
                        <canvas id="callVolumeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- System Health --}}
        <div class="col-span-12 lg:col-span-4">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm h-full">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">System Health</h3>
                </div>
                <div class="p-5 space-y-4">
                    {{-- Trunks Health --}}
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg {{ $systemHealth['trunks']['unhealthy'] > 0 ? 'bg-amber-100' : 'bg-emerald-100' }} flex items-center justify-center">
                                <svg class="w-5 h-5 {{ $systemHealth['trunks']['unhealthy'] > 0 ? 'text-amber-600' : 'text-emerald-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Trunks</p>
                                <p class="text-xs text-gray-500">{{ $systemHealth['trunks']['active'] }}/{{ $systemHealth['trunks']['total'] }} active</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold {{ $systemHealth['trunks']['health_pct'] >= 80 ? 'text-emerald-600' : ($systemHealth['trunks']['health_pct'] >= 50 ? 'text-amber-600' : 'text-red-600') }}">{{ $systemHealth['trunks']['health_pct'] }}%</p>
                            @if($systemHealth['trunks']['unhealthy'] > 0)
                                <p class="text-xs text-amber-600">{{ $systemHealth['trunks']['unhealthy'] }} unhealthy</p>
                            @else
                                <p class="text-xs text-emerald-600">All healthy</p>
                            @endif
                        </div>
                    </div>

                    {{-- SIP Accounts --}}
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-indigo-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">SIP Accounts</p>
                                <p class="text-xs text-gray-500">{{ $systemHealth['sip_accounts']['active'] }}/{{ $systemHealth['sip_accounts']['total'] }} active</p>
                            </div>
                        </div>
                        <div class="text-right">
                            @php $sipPct = $systemHealth['sip_accounts']['total'] > 0 ? round(($systemHealth['sip_accounts']['active'] / $systemHealth['sip_accounts']['total']) * 100) : 0; @endphp
                            <p class="text-lg font-bold text-indigo-600">{{ $sipPct }}%</p>
                            <p class="text-xs text-gray-500">utilization</p>
                        </div>
                    </div>

                    {{-- Invoices --}}
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg {{ $systemHealth['invoices']['overdue'] > 0 ? 'bg-red-100' : 'bg-sky-100' }} flex items-center justify-center">
                                <svg class="w-5 h-5 {{ $systemHealth['invoices']['overdue'] > 0 ? 'text-red-600' : 'text-sky-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Invoices</p>
                                <p class="text-xs text-gray-500">{{ $systemHealth['invoices']['pending'] }} pending</p>
                            </div>
                        </div>
                        <div class="text-right">
                            @if($systemHealth['invoices']['overdue'] > 0)
                                <p class="text-lg font-bold text-red-600">{{ $systemHealth['invoices']['overdue'] }}</p>
                                <p class="text-xs text-red-600">overdue</p>
                            @else
                                <p class="text-lg font-bold text-emerald-600">0</p>
                                <p class="text-xs text-emerald-600">overdue</p>
                            @endif
                        </div>
                    </div>

                    {{-- KYC Pending --}}
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg {{ $entityCounts['pending_kyc'] > 0 ? 'bg-amber-100' : 'bg-gray-100' }} flex items-center justify-center">
                                <svg class="w-5 h-5 {{ $entityCounts['pending_kyc'] > 0 ? 'text-amber-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">KYC Reviews</p>
                                <p class="text-xs text-gray-500">Verification queue</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold {{ $entityCounts['pending_kyc'] > 0 ? 'text-amber-600' : 'text-gray-400' }}">{{ $entityCounts['pending_kyc'] }}</p>
                            <p class="text-xs {{ $entityCounts['pending_kyc'] > 0 ? 'text-amber-600' : 'text-gray-400' }}">pending</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Second Row: Platform Overview & Top Destinations --}}
    <div class="grid grid-cols-12 gap-6 mb-6">
        {{-- Platform Overview --}}
        <div class="col-span-12 lg:col-span-8">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Platform Overview</h3>
                    <a href="{{ route('admin.users.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium flex items-center">
                        Manage Users
                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <a href="{{ route('admin.users.index', ['role' => 'reseller']) }}" class="group flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:border-emerald-200 hover:bg-emerald-50/50 transition-all">
                            <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0 group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2"/>
                                    <circle cx="12" cy="7" r="4" stroke-width="2"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xl font-bold text-gray-900">{{ number_format($entityCounts['resellers']) }}</p>
                                <p class="text-xs text-gray-500">Resellers</p>
                            </div>
                        </a>

                        <a href="{{ route('admin.users.index', ['role' => 'client']) }}" class="group flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:border-sky-200 hover:bg-sky-50/50 transition-all">
                            <div class="w-10 h-10 rounded-lg bg-sky-100 text-sky-600 flex items-center justify-center shrink-0 group-hover:bg-sky-500 group-hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                                    <circle cx="9" cy="7" r="4" stroke-width="2"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xl font-bold text-gray-900">{{ number_format($entityCounts['clients']) }}</p>
                                <p class="text-xs text-gray-500">Clients</p>
                            </div>
                        </a>

                        <a href="{{ route('admin.sip-accounts.index') }}" class="group flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:border-violet-200 hover:bg-violet-50/50 transition-all">
                            <div class="w-10 h-10 rounded-lg bg-violet-100 text-violet-600 flex items-center justify-center shrink-0 group-hover:bg-violet-500 group-hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xl font-bold text-gray-900">{{ number_format($entityCounts['sip_accounts']) }}</p>
                                <p class="text-xs text-gray-500">SIP Accounts</p>
                            </div>
                        </a>

                        @if(auth()->user()->isSuperAdmin())
                            <a href="{{ route('admin.trunks.index') }}" class="group flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:border-indigo-200 hover:bg-indigo-50/50 transition-all">
                                <div class="w-10 h-10 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0 group-hover:bg-indigo-500 group-hover:text-white transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xl font-bold text-gray-900">{{ number_format($entityCounts['active_trunks']) }}</p>
                                    <p class="text-xs text-gray-500">Active Trunks</p>
                                </div>
                            </a>
                        @endif

                        <a href="{{ route('admin.dids.index') }}" class="group flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:border-cyan-200 hover:bg-cyan-50/50 transition-all">
                            <div class="w-10 h-10 rounded-lg bg-cyan-100 text-cyan-600 flex items-center justify-center shrink-0 group-hover:bg-cyan-500 group-hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xl font-bold text-gray-900">{{ number_format($entityCounts['active_dids']) }}</p>
                                <p class="text-xs text-gray-500">Active DIDs</p>
                            </div>
                        </a>

                        @if(auth()->user()->isSuperAdmin())
                            <a href="{{ route('admin.trunk-routes.index') }}" class="group flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:border-rose-200 hover:bg-rose-50/50 transition-all">
                                <div class="w-10 h-10 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center shrink-0 group-hover:bg-rose-500 group-hover:text-white transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                                    </svg>
                                </div>
                                @php
                                    $routeCount = \App\Models\TrunkRoute::where('status', 'active')->count();
                                @endphp
                                <div>
                                    <p class="text-xl font-bold text-gray-900">{{ number_format($routeCount) }}</p>
                                    <p class="text-xs text-gray-500">Routes</p>
                                </div>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Top Destinations --}}
        <div class="col-span-12 lg:col-span-4">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm h-full">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">Top Destinations</h3>
                </div>
                <div class="p-5">
                    @if($topDestinations->isNotEmpty())
                        <div class="space-y-3">
                            @foreach($topDestinations as $index => $dest)
                                <div class="flex items-center gap-3">
                                    <span class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-500">{{ $index + 1 }}</span>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-mono font-medium text-gray-900 truncate">{{ $dest->destination_prefix }}</span>
                                            <span class="text-sm font-bold text-gray-900">{{ number_format($dest->calls) }}</span>
                                        </div>
                                        <div class="mt-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                            @php
                                                $maxCalls = $topDestinations->first()->calls;
                                                $pct = $maxCalls > 0 ? ($dest->calls / $maxCalls) * 100 : 0;
                                            @endphp
                                            <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full" style="width: {{ $pct }}%"></div>
                                        </div>
                                        <div class="flex items-center justify-between mt-1 text-xs text-gray-500">
                                            <span>{{ number_format($dest->answered) }} answered</span>
                                            <span>{{ format_currency($dest->revenue) }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-400">
                            <svg class="w-10 h-10 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <p class="text-sm">No destination data</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions & Recent Calls --}}
    <div class="grid grid-cols-12 gap-6">
        {{-- Recent Calls --}}
        <div class="col-span-12 lg:col-span-8">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Recent Calls</h3>
                    <a href="{{ route('admin.cdr.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium flex items-center">
                        View All CDR
                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Time</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Caller / Callee</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Duration</th>
                                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($recentCalls as $call)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-5 py-3 whitespace-nowrap">
                                        <a href="{{ route('admin.cdr.show', ['uuid' => $call->uuid, 'date' => $call->call_start?->format('Y-m-d')]) }}"
                                           class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                            {{ $call->call_start?->format('H:i:s') }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3">
                                        <p class="text-sm font-mono text-gray-900">{{ $call->caller }}</p>
                                        <p class="text-xs font-mono text-gray-500">{{ $call->callee }}</p>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <span class="text-sm font-mono text-gray-700 tabular-nums">
                                            {{ sprintf('%d:%02d', intdiv($call->duration, 60), $call->duration % 60) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        @switch($call->disposition)
                                            @case('ANSWERED')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1"></span>
                                                    Answered
                                                </span>
                                                @break
                                            @case('NO ANSWER')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1"></span>
                                                    No Answer
                                                </span>
                                                @break
                                            @case('BUSY')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-orange-500 mr-1"></span>
                                                    Busy
                                                </span>
                                                @break
                                            @case('FAILED')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1"></span>
                                                    Failed
                                                </span>
                                                @break
                                            @default
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                    {{ $call->disposition }}
                                                </span>
                                        @endswitch
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-10 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mb-3">
                                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                                </svg>
                                            </div>
                                            <p class="text-sm text-gray-500">No calls yet today</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="col-span-12 lg:col-span-4">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm h-full">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">Quick Actions</h3>
                </div>
                <div class="p-4 space-y-2">
                    <a href="{{ route('admin.users.create') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors group">
                        <div class="w-9 h-9 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center group-hover:bg-indigo-500 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-900">Create User</p>
                            <p class="text-xs text-gray-500">Add reseller or client</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 group-hover:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    <a href="{{ route('admin.sip-accounts.create') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors group">
                        <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-900">New SIP Account</p>
                            <p class="text-xs text-gray-500">Create SIP endpoint</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 group-hover:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    <a href="{{ route('admin.kyc.index') }}?status=pending" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors group {{ $entityCounts['pending_kyc'] > 0 ? 'bg-amber-50' : '' }}">
                        <div class="w-9 h-9 rounded-lg {{ $entityCounts['pending_kyc'] > 0 ? 'bg-amber-200 text-amber-700' : 'bg-amber-100 text-amber-600' }} flex items-center justify-center group-hover:bg-amber-500 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-900">Review KYC</p>
                            <p class="text-xs text-gray-500">{{ $entityCounts['pending_kyc'] }} pending verifications</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 group-hover:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    <a href="{{ route('admin.operational-reports.index') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors group">
                        <div class="w-9 h-9 rounded-lg bg-sky-100 text-sky-600 flex items-center justify-center group-hover:bg-sky-500 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-900">View Reports</p>
                            <p class="text-xs text-gray-500">Operational analytics</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 group-hover:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    @if(auth()->user()->isSuperAdmin())
                        <a href="{{ route('admin.rate-groups.index') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors group">
                            <div class="w-9 h-9 rounded-lg bg-violet-100 text-violet-600 flex items-center justify-center group-hover:bg-violet-500 group-hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-gray-900">Manage Rates</p>
                                <p class="text-xs text-gray-500">Rate groups & pricing</p>
                            </div>
                            <svg class="w-4 h-4 text-gray-300 group-hover:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>

                        <a href="{{ route('admin.trunks.index') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors group">
                            <div class="w-9 h-9 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center group-hover:bg-rose-500 group-hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-gray-900">Manage Trunks</p>
                                <p class="text-xs text-gray-500">SIP trunk connections</p>
                            </div>
                            <svg class="w-4 h-4 text-gray-300 group-hover:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    (function() {
        // --- Live Operations WebSocket ---
        var WS_URL = (window.location.protocol === 'https:' ? 'wss://' : 'ws://') + window.location.host + '/ws/live-calls';
        var ws = null;
        var reconnectAttempts = 0;
        var maxReconnect = 10;

        // Counters
        var concurrent = 0;
        var todayCalls = {{ $todayStats['today_calls'] ?? 0 }};
        var todayAnswered = {{ $todayStats['today_answered'] ?? 0 }};
        var todayFailed = {{ ($todayStats['today_calls'] ?? 0) - ($todayStats['today_answered'] ?? 0) }};
        var callStartTimestamps = []; // for CPS calculation
        var answeredCallIds = {}; // track which calls were answered

        // DOM references
        var elConcurrent = document.getElementById('live-concurrent');
        var elCps = document.getElementById('live-cps');
        var elAsr = document.getElementById('live-asr');
        var elAsrBadge = document.getElementById('live-asr-badge');
        var elTodayCalls = document.getElementById('live-today-calls');
        var elTodayAnswered = document.getElementById('live-today-answered');
        var elTodayFailed = document.getElementById('live-today-failed');
        var elWsStatus = document.getElementById('ws-status');
        var elPulse = document.getElementById('live-pulse');

        function updateDOM() {
            elConcurrent.textContent = concurrent;
            elTodayCalls.textContent = todayCalls.toLocaleString();
            elTodayAnswered.textContent = todayAnswered.toLocaleString();
            elTodayFailed.textContent = todayFailed.toLocaleString();

            // Pulse indicator
            if (concurrent > 0) {
                elPulse.classList.remove('hidden');
            } else {
                elPulse.classList.add('hidden');
            }

            // ASR calculation
            var asr = todayCalls > 0 ? ((todayAnswered / todayCalls) * 100) : 0;
            elAsr.innerHTML = asr.toFixed(1) + '<span class="text-lg">%</span>';

            // ASR color coding
            if (todayCalls === 0) {
                elAsr.className = 'text-3xl font-bold tabular-nums text-gray-400';
                elAsrBadge.className = 'text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-500';
                elAsrBadge.textContent = '--';
            } else if (asr >= 60) {
                elAsr.className = 'text-3xl font-bold tabular-nums text-emerald-600';
                elAsrBadge.className = 'text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-600';
                elAsrBadge.textContent = 'Good';
            } else if (asr >= 40) {
                elAsr.className = 'text-3xl font-bold tabular-nums text-amber-600';
                elAsrBadge.className = 'text-xs font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-600';
                elAsrBadge.textContent = 'Fair';
            } else {
                elAsr.className = 'text-3xl font-bold tabular-nums text-red-600';
                elAsrBadge.className = 'text-xs font-medium px-2 py-0.5 rounded-full bg-red-100 text-red-600';
                elAsrBadge.textContent = 'Low';
            }

            // CPS: count call_start timestamps in the last 1 second
            var now = Date.now();
            callStartTimestamps = callStartTimestamps.filter(function(t) { return now - t < 1000; });
            elCps.textContent = callStartTimestamps.length.toFixed(1);
        }

        function setWsStatus(status) {
            if (status === 'connected') {
                elWsStatus.className = 'w-2 h-2 rounded-full bg-emerald-500';
                elWsStatus.title = 'WebSocket: connected';
            } else if (status === 'disconnected') {
                elWsStatus.className = 'w-2 h-2 rounded-full bg-red-500';
                elWsStatus.title = 'WebSocket: disconnected';
            } else {
                elWsStatus.className = 'w-2 h-2 rounded-full bg-gray-300';
                elWsStatus.title = 'WebSocket: connecting...';
            }
        }

        function connect() {
            setWsStatus('connecting');
            ws = new WebSocket(WS_URL);

            ws.onopen = function() {
                reconnectAttempts = 0;
                setWsStatus('connected');
            };

            ws.onmessage = function(event) {
                var data = JSON.parse(event.data);
                handleMessage(data);
            };

            ws.onclose = function() {
                setWsStatus('disconnected');
                if (reconnectAttempts < maxReconnect) {
                    reconnectAttempts++;
                    setTimeout(connect, Math.min(1000 * reconnectAttempts, 10000));
                }
            };

            ws.onerror = function() {
                setWsStatus('disconnected');
            };
        }

        function handleMessage(data) {
            switch (data.type) {
                case 'snapshot':
                    concurrent = data.stats ? (data.stats.active_calls_count || 0) : 0;
                    break;
                case 'call_start':
                    concurrent++;
                    todayCalls++;
                    callStartTimestamps.push(Date.now());
                    // Track this call as not-yet-answered
                    if (data.unique_id) {
                        answeredCallIds[data.unique_id] = false;
                    }
                    break;
                case 'call_answered':
                    todayAnswered++;
                    // Mark as answered
                    if (data.unique_id) {
                        answeredCallIds[data.unique_id] = true;
                    }
                    break;
                case 'call_end':
                    concurrent = Math.max(0, concurrent - 1);
                    // If this call was never answered, count as failed
                    if (data.unique_id && !answeredCallIds[data.unique_id]) {
                        todayFailed++;
                    }
                    // Clean up tracking
                    if (data.unique_id) {
                        delete answeredCallIds[data.unique_id];
                    }
                    break;
            }
            updateDOM();
        }

        // Initial DOM render with server-side data
        updateDOM();

        // Update CPS display every second
        setInterval(function() {
            var now = Date.now();
            callStartTimestamps = callStartTimestamps.filter(function(t) { return now - t < 1000; });
            elCps.textContent = callStartTimestamps.length.toFixed(1);
        }, 1000);

        // Connect WebSocket
        connect();
    })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        function callVolumeChart() {
            return {
                initChart() {
                    const ctx = document.getElementById('callVolumeChart').getContext('2d');
                    const dailyData = @json($dailyData);

                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: dailyData.map(d => d.label),
                            datasets: [
                                {
                                    label: 'Total Calls',
                                    data: dailyData.map(d => d.calls),
                                    borderColor: 'rgb(99, 102, 241)',
                                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                                    fill: true,
                                    tension: 0.4,
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                },
                                {
                                    label: 'Answered',
                                    data: dailyData.map(d => d.answered),
                                    borderColor: 'rgb(16, 185, 129)',
                                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                    fill: true,
                                    tension: 0.4,
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                intersect: false,
                                mode: 'index',
                            },
                            plugins: {
                                legend: {
                                    position: 'top',
                                    align: 'end',
                                    labels: {
                                        usePointStyle: true,
                                        boxWidth: 6,
                                        boxHeight: 6,
                                        padding: 15,
                                        font: { size: 11 }
                                    }
                                },
                                tooltip: {
                                    backgroundColor: 'white',
                                    titleColor: '#1f2937',
                                    bodyColor: '#4b5563',
                                    borderColor: '#e5e7eb',
                                    borderWidth: 1,
                                    padding: 12,
                                    displayColors: true,
                                    usePointStyle: true,
                                }
                            },
                            scales: {
                                x: {
                                    grid: { display: false },
                                    ticks: { font: { size: 11 }, color: '#9ca3af' }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: { color: '#f3f4f6' },
                                    ticks: { font: { size: 11 }, color: '#9ca3af' }
                                }
                            }
                        }
                    });
                }
            }
        }
    </script>
    @endpush
</x-admin-layout>
