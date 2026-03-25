<x-admin-layout>
    <x-slot name="header">Dashboard</x-slot>

    {{-- ═══════════════════════════════════════════════════════════
         SECTION A: NOC Live Operations Strip (dark, always on top)
         ═══════════════════════════════════════════════════════════ --}}
    <div class="bg-slate-900 -mx-6 px-6 py-4 mb-6 rounded-xl">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-0 divide-x divide-slate-700">
            {{-- Live Calls --}}
            <div class="px-5 py-1">
                <div class="flex items-center gap-2 mb-1">
                    <p class="text-slate-400 text-xs font-medium uppercase tracking-wide">Live Calls</p>
                    <span id="ws-status" class="w-2 h-2 rounded-full bg-gray-500" title="WebSocket: connecting..."></span>
                </div>
                <div class="flex items-center gap-2">
                    <p class="text-2xl font-bold text-white tabular-nums" id="live-concurrent">0</p>
                    <span id="live-pulse" class="relative flex h-3 w-3 hidden">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                </div>
                <p class="text-xs text-slate-500">concurrent channels</p>
            </div>

            {{-- CPS --}}
            <div class="px-5 py-1">
                <p class="text-slate-400 text-xs font-medium uppercase tracking-wide mb-1">CPS</p>
                <p class="text-2xl font-bold text-white tabular-nums" id="live-cps">0.0</p>
                <p class="text-xs text-slate-500">calls per second</p>
            </div>

            {{-- Live ASR --}}
            <div class="px-5 py-1">
                <div class="flex items-center gap-2 mb-1">
                    <p class="text-slate-400 text-xs font-medium uppercase tracking-wide">Live ASR</p>
                    <span id="live-asr-badge" class="text-xs font-medium px-1.5 py-0.5 rounded-full bg-slate-700 text-slate-400">--</span>
                </div>
                <p class="text-2xl font-bold tabular-nums text-white" id="live-asr">0.0<span class="text-lg">%</span></p>
                <p class="text-xs text-slate-500">answer seizure ratio</p>
            </div>

            {{-- Today's Activity --}}
            <div class="px-5 py-1">
                <p class="text-slate-400 text-xs font-medium uppercase tracking-wide mb-1">Today</p>
                <p class="text-2xl font-bold text-white tabular-nums" id="live-today-calls">0</p>
                <div class="flex items-center gap-2 text-xs">
                    <span class="text-emerald-400"><span id="live-today-answered">0</span> ans</span>
                    <span class="text-slate-600">|</span>
                    <span class="text-red-400"><span id="live-today-failed">0</span> fail</span>
                </div>
            </div>

            {{-- Date & Time --}}
            <div class="px-5 py-1 hidden md:block text-right">
                <p class="text-slate-400 text-xs font-medium uppercase tracking-wide mb-1">System Time</p>
                <p class="text-lg font-bold text-white">{{ now()->format('g:i A') }}</p>
                <p class="text-xs text-slate-500">{{ now()->format('D, M j, Y') }}</p>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════
         SECTION B: Weekly KPI Cards (4-col)
         ═══════════════════════════════════════════════════ --}}
    @php
        $revenueChange = ($prevWeekStats['total_cost'] ?? 0) > 0
            ? round((($weekStats['total_cost'] - $prevWeekStats['total_cost']) / $prevWeekStats['total_cost']) * 100, 1) : 0;
        $callsChange = ($prevWeekStats['total_calls'] ?? 0) > 0
            ? round((($weekStats['total_calls'] - $prevWeekStats['total_calls']) / $prevWeekStats['total_calls']) * 100, 1) : 0;
        $acdMins = intdiv($weekStats['acd'], 60);
        $acdSecs = $weekStats['acd'] % 60;
        $totalHours = intdiv($weekStats['total_duration'], 3600);
        $totalMins = intdiv($weekStats['total_duration'] % 3600, 60);
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Revenue --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-xs font-medium text-gray-400">7 Days</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ format_currency($weekStats['total_cost']) }}</p>
            <p class="text-sm text-gray-500 mb-2">Revenue</p>
            <div class="flex items-center text-xs">
                @if($revenueChange >= 0)
                    <span class="inline-flex items-center text-emerald-600"><svg class="w-3 h-3 mr-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>{{ abs($revenueChange) }}%</span>
                @else
                    <span class="inline-flex items-center text-red-600"><svg class="w-3 h-3 mr-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>{{ abs($revenueChange) }}%</span>
                @endif
                <span class="text-gray-400 ml-1">vs last week</span>
            </div>
        </div>

        {{-- Total Calls --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-400 to-emerald-500 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                </div>
                <span class="text-xs font-medium text-gray-400">7 Days</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($weekStats['total_calls']) }}</p>
            <p class="text-sm text-gray-500 mb-2">Total Calls</p>
            <div class="flex items-center text-xs">
                @if($callsChange >= 0)
                    <span class="inline-flex items-center text-emerald-600"><svg class="w-3 h-3 mr-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>{{ abs($callsChange) }}%</span>
                @else
                    <span class="inline-flex items-center text-red-600"><svg class="w-3 h-3 mr-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>{{ abs($callsChange) }}%</span>
                @endif
                <span class="text-gray-400 ml-1">vs last week</span>
            </div>
        </div>

        {{-- ASR --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-400 to-blue-500 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
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

        {{-- ACD --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-xs font-medium text-gray-400">ACD</span>
            </div>
            <p class="text-2xl font-bold text-gray-900 tabular-nums">{{ $acdMins }}:{{ sprintf('%02d', $acdSecs) }}</p>
            <p class="text-sm text-gray-500 mb-2">Avg Call Duration</p>
            <div class="flex items-center text-xs text-gray-500">Total: {{ $totalHours }}h {{ $totalMins }}m talk time</div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════
         SECTION C: Financial Pulse (3-col)
         ═══════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        {{-- This Month Revenue --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </div>
                    <span class="text-sm font-medium text-gray-700">Monthly Revenue</span>
                </div>
                @php $revChange = $financialSummary['revenue_change'] ?? 0; @endphp
                @if($revChange != 0)
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $revChange >= 0 ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600' }}">
                        {{ $revChange >= 0 ? '+' : '' }}{{ $revChange }}%
                    </span>
                @endif
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ format_currency($financialSummary['this_month_revenue'] ?? 0) }}</p>
            <p class="text-xs text-gray-400 mt-1">Last month: {{ format_currency($financialSummary['last_month_revenue'] ?? 0) }}</p>
        </div>

        {{-- Outstanding Balance --}}
        @php $outstanding = $financialSummary['outstanding_balance'] ?? 0; @endphp
        <a href="{{ route('admin.invoices.index') }}" class="bg-white rounded-xl border {{ $outstanding > 0 ? 'border-red-200 bg-red-50/30' : 'border-gray-200' }} p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-8 h-8 rounded-lg {{ $outstanding > 0 ? 'bg-red-100' : 'bg-emerald-100' }} flex items-center justify-center">
                    <svg class="w-4 h-4 {{ $outstanding > 0 ? 'text-red-600' : 'text-emerald-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <span class="text-sm font-medium text-gray-700">Outstanding</span>
            </div>
            <p class="text-2xl font-bold {{ $outstanding > 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ format_currency($outstanding) }}</p>
            <p class="text-xs text-gray-400 mt-1">unpaid issued invoices</p>
        </a>

        {{-- Total User Balance --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <span class="text-sm font-medium text-gray-700">Platform Balance</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ format_currency($financialSummary['total_user_balance'] ?? 0) }}</p>
            <p class="text-xs text-gray-400 mt-1">total prepaid balance across all users</p>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════
         SECTION D: Platform Inventory Bar (6-col compact)
         ═══════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-3 md:grid-cols-6 gap-3 mb-6">
        <a href="{{ route('admin.users.index', ['role' => 'reseller']) }}" class="bg-white rounded-lg border-l-4 border-emerald-500 px-4 py-3 shadow-sm hover:shadow-md transition-shadow">
            <p class="text-xl font-bold text-gray-900">{{ number_format($entityCounts['resellers']) }}</p>
            <p class="text-xs text-gray-500">Resellers</p>
        </a>
        <a href="{{ route('admin.users.index', ['role' => 'client']) }}" class="bg-white rounded-lg border-l-4 border-sky-500 px-4 py-3 shadow-sm hover:shadow-md transition-shadow">
            <p class="text-xl font-bold text-gray-900">{{ number_format($entityCounts['clients']) }}</p>
            <p class="text-xs text-gray-500">Clients</p>
        </a>
        <a href="{{ route('admin.sip-accounts.index') }}" class="bg-white rounded-lg border-l-4 border-indigo-500 px-4 py-3 shadow-sm hover:shadow-md transition-shadow">
            <p class="text-xl font-bold text-gray-900">{{ number_format($entityCounts['sip_accounts']) }}</p>
            <p class="text-xs text-gray-500">SIP Accounts</p>
        </a>
        @if(auth()->user()->isSuperAdmin())
        <a href="{{ route('admin.trunks.index') }}" class="bg-white rounded-lg border-l-4 border-purple-500 px-4 py-3 shadow-sm hover:shadow-md transition-shadow">
            <p class="text-xl font-bold text-gray-900">{{ number_format($entityCounts['active_trunks']) }}</p>
            <p class="text-xs text-gray-500">Active Trunks</p>
        </a>
        @endif
        <a href="{{ route('admin.dids.index') }}" class="bg-white rounded-lg border-l-4 border-blue-500 px-4 py-3 shadow-sm hover:shadow-md transition-shadow">
            <p class="text-xl font-bold text-gray-900">{{ number_format($entityCounts['active_dids']) }}</p>
            <p class="text-xs text-gray-500">Active DIDs</p>
        </a>
        @if(auth()->user()->isSuperAdmin())
        <a href="{{ route('admin.trunk-routes.index') }}" class="bg-white rounded-lg border-l-4 border-rose-500 px-4 py-3 shadow-sm hover:shadow-md transition-shadow">
            @php $routeCount = \App\Models\TrunkRoute::where('status', 'active')->count(); @endphp
            <p class="text-xl font-bold text-gray-900">{{ number_format($routeCount) }}</p>
            <p class="text-xs text-gray-500">Routes</p>
        </a>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════
         SECTION E: Chart + System Health (8+4 grid)
         ═══════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-12 gap-6 mb-6">
        {{-- Call Volume & Revenue Chart --}}
        <div class="col-span-12 lg:col-span-8">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm" x-data="{ chartTab: 'daily' }">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Call Volume & Revenue</h3>
                    <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                        <button @click="chartTab = 'daily'; $nextTick(() => initDailyChart())" :class="chartTab === 'daily' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'" class="px-3 py-1.5 text-xs font-medium">7 Days</button>
                        <button @click="chartTab = 'hourly'; $nextTick(() => initHourlyChart())" :class="chartTab === 'hourly' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'" class="px-3 py-1.5 text-xs font-medium border-l">Today Hourly</button>
                    </div>
                </div>
                <div class="p-5">
                    <div class="h-56">
                        <canvas x-show="chartTab === 'daily'" id="dailyChart" style="display:block;"></canvas>
                        <canvas x-show="chartTab === 'hourly'" id="hourlyChart" style="display:none;"></canvas>
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
                    {{-- Trunks --}}
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg {{ $systemHealth['trunks']['unhealthy'] > 0 ? 'bg-amber-100' : 'bg-emerald-100' }} flex items-center justify-center">
                                <svg class="w-5 h-5 {{ $systemHealth['trunks']['unhealthy'] > 0 ? 'text-amber-600' : 'text-emerald-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Trunks</p>
                                <p class="text-xs text-gray-500">{{ $systemHealth['trunks']['active'] }}/{{ $systemHealth['trunks']['total'] }} active</p>
                            </div>
                        </div>
                        <p class="text-lg font-bold {{ $systemHealth['trunks']['health_pct'] >= 80 ? 'text-emerald-600' : ($systemHealth['trunks']['health_pct'] >= 50 ? 'text-amber-600' : 'text-red-600') }}">{{ $systemHealth['trunks']['health_pct'] }}%</p>
                    </div>

                    {{-- SIP Accounts --}}
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-indigo-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">SIP Accounts</p>
                                <p class="text-xs text-gray-500">{{ $systemHealth['sip_accounts']['active'] }}/{{ $systemHealth['sip_accounts']['total'] }} active</p>
                            </div>
                        </div>
                        @php $sipPct = $systemHealth['sip_accounts']['total'] > 0 ? round(($systemHealth['sip_accounts']['active'] / $systemHealth['sip_accounts']['total']) * 100) : 0; @endphp
                        <p class="text-lg font-bold text-indigo-600">{{ $sipPct }}%</p>
                    </div>

                    {{-- Invoices --}}
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg {{ $systemHealth['invoices']['overdue'] > 0 ? 'bg-red-100' : 'bg-sky-100' }} flex items-center justify-center">
                                <svg class="w-5 h-5 {{ $systemHealth['invoices']['overdue'] > 0 ? 'text-red-600' : 'text-sky-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Invoices</p>
                                <p class="text-xs text-gray-500">{{ $systemHealth['invoices']['pending'] }} pending</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold {{ $systemHealth['invoices']['overdue'] > 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ $systemHealth['invoices']['overdue'] }}</p>
                            <p class="text-xs {{ $systemHealth['invoices']['overdue'] > 0 ? 'text-red-600' : 'text-emerald-600' }}">overdue</p>
                        </div>
                    </div>

                    {{-- KYC --}}
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg {{ $entityCounts['pending_kyc'] > 0 ? 'bg-amber-100' : 'bg-gray-100' }} flex items-center justify-center">
                                <svg class="w-5 h-5 {{ $entityCounts['pending_kyc'] > 0 ? 'text-amber-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">KYC Reviews</p>
                                <p class="text-xs text-gray-500">Verification queue</p>
                            </div>
                        </div>
                        <p class="text-lg font-bold {{ $entityCounts['pending_kyc'] > 0 ? 'text-amber-600' : 'text-gray-400' }}">{{ $entityCounts['pending_kyc'] }}</p>
                    </div>

                    {{-- Broadcasts --}}
                    <a href="{{ route('admin.broadcasts.index') }}" class="flex items-center justify-between group">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg {{ ($broadcastStats['running'] ?? 0) > 0 ? 'bg-indigo-100' : 'bg-gray-100' }} flex items-center justify-center">
                                <svg class="w-5 h-5 {{ ($broadcastStats['running'] ?? 0) > 0 ? 'text-indigo-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 group-hover:text-indigo-600">Broadcasts</p>
                                <p class="text-xs text-gray-500">{{ $broadcastStats['completed'] ?? 0 }} done / {{ $broadcastStats['total'] ?? 0 }} total</p>
                            </div>
                        </div>
                        <p class="text-lg font-bold {{ ($broadcastStats['running'] ?? 0) > 0 ? 'text-indigo-600' : 'text-gray-400' }}">{{ $broadcastStats['running'] ?? 0 }}</p>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════
         SECTION F: Recent Calls + Top Destinations + Quick Actions
         ═══════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-12 gap-6">
        {{-- Recent Calls --}}
        <div class="col-span-12 lg:col-span-5">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Recent Calls</h3>
                    <a href="{{ route('admin.cdr.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">View CDR</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Time</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Caller / Callee</th>
                                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">Dur</th>
                                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">Cost</th>
                                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse ($recentCalls as $call)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        <a href="{{ route('admin.cdr.show', ['uuid' => $call->uuid, 'date' => $call->call_start?->format('Y-m-d')]) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">{{ $call->call_start?->format('H:i:s') }}</a>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <p class="text-xs font-mono text-gray-900 truncate" style="max-width:120px;">{{ $call->caller }}</p>
                                        <p class="text-xs font-mono text-gray-400 truncate" style="max-width:120px;">{{ $call->callee }}</p>
                                    </td>
                                    <td class="px-4 py-2.5 text-right">
                                        <span class="text-xs font-mono text-gray-700 tabular-nums">{{ sprintf('%d:%02d', intdiv($call->duration, 60), $call->duration % 60) }}</span>
                                    </td>
                                    <td class="px-4 py-2.5 text-right">
                                        <span class="text-xs font-mono text-gray-700 tabular-nums">{{ format_currency($call->total_cost ?? 0, 2) }}</span>
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        @switch($call->disposition)
                                            @case('ANSWERED')
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700"><span class="w-1 h-1 rounded-full bg-emerald-500 mr-1"></span>OK</span>
                                                @break
                                            @case('NO ANSWER')
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">NA</span>
                                                @break
                                            @case('BUSY')
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700">BSY</span>
                                                @break
                                            @case('FAILED')
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">FAIL</span>
                                                @break
                                            @default
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">{{ Str::limit($call->disposition, 4) }}</span>
                                        @endswitch
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-10 text-center">
                                        <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        <p class="text-sm text-gray-400">No calls yet today</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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
                                            @php $maxCalls = $topDestinations->first()->calls; $pct = $maxCalls > 0 ? ($dest->calls / $maxCalls) * 100 : 0; @endphp
                                            <div class="h-full bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-full" style="width: {{ $pct }}%"></div>
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
                            <svg class="w-10 h-10 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            <p class="text-sm">No destination data</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="col-span-12 lg:col-span-3">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm h-full">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">Quick Actions</h3>
                </div>
                <div class="p-4 space-y-2">
                    <a href="{{ route('admin.users.create') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors group">
                        <div class="w-9 h-9 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center group-hover:bg-indigo-500 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-900">Create User</p>
                            <p class="text-xs text-gray-500">Add reseller or client</p>
                        </div>
                    </a>

                    <a href="{{ route('admin.kyc.index') }}?status=pending" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors group {{ $entityCounts['pending_kyc'] > 0 ? 'bg-amber-50' : '' }}">
                        <div class="w-9 h-9 rounded-lg {{ $entityCounts['pending_kyc'] > 0 ? 'bg-amber-200 text-amber-700' : 'bg-amber-100 text-amber-600' }} flex items-center justify-center group-hover:bg-amber-500 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-900">Review KYC</p>
                            <p class="text-xs text-gray-500">{{ $entityCounts['pending_kyc'] }} pending</p>
                        </div>
                        @if($entityCounts['pending_kyc'] > 0)
                            <span class="w-5 h-5 rounded-full bg-red-500 text-white text-xs flex items-center justify-center">{{ $entityCounts['pending_kyc'] }}</span>
                        @endif
                    </a>

                    <a href="{{ route('admin.operational-reports.active') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors group">
                        <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-900">Active Calls</p>
                            <p class="text-xs text-gray-500">Live monitor</p>
                        </div>
                        <span id="qa-live-count" class="text-xs font-bold text-emerald-600 tabular-nums">0</span>
                    </a>

                    <a href="{{ route('admin.broadcasts.create') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors group">
                        <div class="w-9 h-9 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center group-hover:bg-purple-500 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-900">New Broadcast</p>
                            <p class="text-xs text-gray-500">Voice campaign</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════
         SCRIPTS: WebSocket + Charts
         ═══════════════════════════════════════════════════ --}}
    @push('scripts')
    <script>
    (function() {
        // --- Live Operations WebSocket ---
        var WS_URL = (window.location.protocol === 'https:' ? 'wss://' : 'ws://') + window.location.host + '/ws/live-calls';
        var ws = null;
        var reconnectAttempts = 0;
        var maxReconnect = 10;
        var concurrent = 0;
        var todayCalls = {{ $todayStats['today_calls'] ?? 0 }};
        var todayAnswered = {{ $todayStats['today_answered'] ?? 0 }};
        var todayFailed = {{ ($todayStats['today_calls'] ?? 0) - ($todayStats['today_answered'] ?? 0) }};
        var callStartTimestamps = [];
        var answeredCallIds = {};

        var elConcurrent = document.getElementById('live-concurrent');
        var elCps = document.getElementById('live-cps');
        var elAsr = document.getElementById('live-asr');
        var elAsrBadge = document.getElementById('live-asr-badge');
        var elTodayCalls = document.getElementById('live-today-calls');
        var elTodayAnswered = document.getElementById('live-today-answered');
        var elTodayFailed = document.getElementById('live-today-failed');
        var elWsStatus = document.getElementById('ws-status');
        var elPulse = document.getElementById('live-pulse');
        var elQaLive = document.getElementById('qa-live-count');

        function updateDOM() {
            elConcurrent.textContent = concurrent;
            elTodayCalls.textContent = todayCalls.toLocaleString();
            elTodayAnswered.textContent = todayAnswered.toLocaleString();
            elTodayFailed.textContent = todayFailed.toLocaleString();
            if (elQaLive) elQaLive.textContent = concurrent;

            if (concurrent > 0) { elPulse.classList.remove('hidden'); } else { elPulse.classList.add('hidden'); }

            var asr = todayCalls > 0 ? ((todayAnswered / todayCalls) * 100) : 0;
            elAsr.innerHTML = asr.toFixed(1) + '<span class="text-lg">%</span>';

            if (todayCalls === 0) {
                elAsr.className = 'text-2xl font-bold tabular-nums text-slate-500';
                elAsrBadge.className = 'text-xs font-medium px-1.5 py-0.5 rounded-full bg-slate-700 text-slate-400';
                elAsrBadge.textContent = '--';
            } else if (asr >= 60) {
                elAsr.className = 'text-2xl font-bold tabular-nums text-emerald-400';
                elAsrBadge.className = 'text-xs font-medium px-1.5 py-0.5 rounded-full bg-emerald-900/50 text-emerald-400';
                elAsrBadge.textContent = 'Good';
            } else if (asr >= 40) {
                elAsr.className = 'text-2xl font-bold tabular-nums text-amber-400';
                elAsrBadge.className = 'text-xs font-medium px-1.5 py-0.5 rounded-full bg-amber-900/50 text-amber-400';
                elAsrBadge.textContent = 'Fair';
            } else {
                elAsr.className = 'text-2xl font-bold tabular-nums text-red-400';
                elAsrBadge.className = 'text-xs font-medium px-1.5 py-0.5 rounded-full bg-red-900/50 text-red-400';
                elAsrBadge.textContent = 'Low';
            }

            var now = Date.now();
            callStartTimestamps = callStartTimestamps.filter(function(t) { return now - t < 1000; });
            elCps.textContent = callStartTimestamps.length.toFixed(1);
        }

        function setWsStatus(status) {
            if (status === 'connected') { elWsStatus.className = 'w-2 h-2 rounded-full bg-emerald-500'; elWsStatus.title = 'WebSocket: connected'; }
            else if (status === 'disconnected') { elWsStatus.className = 'w-2 h-2 rounded-full bg-red-500'; elWsStatus.title = 'WebSocket: disconnected'; }
            else { elWsStatus.className = 'w-2 h-2 rounded-full bg-gray-500'; elWsStatus.title = 'WebSocket: connecting...'; }
        }

        function connect() {
            setWsStatus('connecting');
            ws = new WebSocket(WS_URL);
            ws.onopen = function() { reconnectAttempts = 0; setWsStatus('connected'); };
            ws.onmessage = function(event) { handleMessage(JSON.parse(event.data)); };
            ws.onclose = function() { setWsStatus('disconnected'); if (reconnectAttempts < maxReconnect) { reconnectAttempts++; setTimeout(connect, Math.min(1000 * reconnectAttempts, 10000)); } };
            ws.onerror = function() { setWsStatus('disconnected'); };
        }

        function handleMessage(data) {
            switch (data.type) {
                case 'snapshot': concurrent = data.stats ? (data.stats.active_calls_count || 0) : 0; break;
                case 'call_start': concurrent++; todayCalls++; callStartTimestamps.push(Date.now()); if (data.unique_id) answeredCallIds[data.unique_id] = false; break;
                case 'call_answered': todayAnswered++; if (data.unique_id) answeredCallIds[data.unique_id] = true; break;
                case 'call_end': concurrent = Math.max(0, concurrent - 1); if (data.unique_id && !answeredCallIds[data.unique_id]) todayFailed++; if (data.unique_id) delete answeredCallIds[data.unique_id]; break;
            }
            updateDOM();
        }

        updateDOM();
        setInterval(function() { var now = Date.now(); callStartTimestamps = callStartTimestamps.filter(function(t) { return now - t < 1000; }); elCps.textContent = callStartTimestamps.length.toFixed(1); }, 1000);
        connect();
    })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        var dailyChartInstance = null;
        var hourlyChartInstance = null;

        function initDailyChart() {
            var ctx = document.getElementById('dailyChart');
            if (!ctx) return;
            if (dailyChartInstance) dailyChartInstance.destroy();
            var data = @json($dailyData);

            dailyChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.label),
                    datasets: [
                        { label: 'Total Calls', data: data.map(d => d.calls), backgroundColor: 'rgba(99,102,241,0.7)', borderRadius: 4, yAxisID: 'y', order: 2 },
                        { label: 'Answered', data: data.map(d => d.answered), backgroundColor: 'rgba(16,185,129,0.7)', borderRadius: 4, yAxisID: 'y', order: 3 },
                        { label: 'Revenue', data: data.map(d => d.revenue), type: 'line', borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.1)', fill: true, tension: 0.4, pointRadius: 4, pointHoverRadius: 6, yAxisID: 'y1', order: 1 },
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: { legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 6, boxHeight: 6, padding: 15, font: { size: 11 } } }, tooltip: { backgroundColor: 'white', titleColor: '#1f2937', bodyColor: '#4b5563', borderColor: '#e5e7eb', borderWidth: 1, padding: 12 } },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#9ca3af' } },
                        y: { beginAtZero: true, position: 'left', grid: { color: '#f3f4f6' }, ticks: { font: { size: 11 }, color: '#9ca3af' } },
                        y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { font: { size: 11 }, color: '#f59e0b', callback: function(v) { return '$' + v.toFixed(0); } } }
                    }
                }
            });
        }

        function initHourlyChart() {
            var ctx = document.getElementById('hourlyChart');
            if (!ctx) return;
            if (hourlyChartInstance) hourlyChartInstance.destroy();
            var data = @json($hourlyData);

            hourlyChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.label),
                    datasets: [
                        { label: 'Total Calls', data: data.map(d => d.calls), backgroundColor: data.map((d, i) => i >= 8 && i <= 20 ? 'rgba(99,102,241,0.7)' : 'rgba(148,163,184,0.4)'), borderRadius: 3 },
                        { label: 'Answered', data: data.map(d => d.answered), backgroundColor: 'rgba(16,185,129,0.6)', borderRadius: 3 },
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 6, boxHeight: 6, padding: 15, font: { size: 11 } } } },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#9ca3af', maxRotation: 0 } },
                        y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { font: { size: 11 }, color: '#9ca3af' } }
                    }
                }
            });
        }

        // Init daily chart on page load
        document.addEventListener('DOMContentLoaded', function() { initDailyChart(); });
    </script>
    @endpush
</x-admin-layout>
