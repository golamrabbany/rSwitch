<x-admin-layout>
    <x-slot name="header">Operational Reports</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-400 to-indigo-500 flex items-center justify-center shadow-lg shadow-indigo-200">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Operational Reports</h2>
                <p class="page-subtitle">Real-time call monitoring and statistics</p>
            </div>
        </div>
        <div class="page-actions">
            <button type="button" onclick="location.reload()" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Refresh
            </button>
        </div>
    </div>

    {{-- Stats Overview --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-6">
        {{-- Active Calls - Live Indicator --}}
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between mb-2">
                <p class="text-emerald-100 text-xs font-medium uppercase tracking-wide">Live</p>
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-white"></span>
                </span>
            </div>
            <p class="text-3xl font-bold" id="idx-live-count">{{ $activeCalls }}</p>
            <p class="text-emerald-100 text-xs mt-1">Active Calls</p>
        </div>

        {{-- Today's Total --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-gray-500 text-xs font-medium uppercase tracking-wide mb-2">Today</p>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($todayTotal) }}</p>
            <p class="text-gray-400 text-xs mt-1">Total Calls</p>
        </div>

        {{-- Inbound --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-gray-500 text-xs font-medium uppercase tracking-wide mb-2">Inbound</p>
            <p class="text-3xl font-bold text-blue-600">{{ number_format($todayInbound) }}</p>
            <p class="text-gray-400 text-xs mt-1">Received</p>
        </div>

        {{-- Outbound --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-gray-500 text-xs font-medium uppercase tracking-wide mb-2">Outbound</p>
            <p class="text-3xl font-bold text-purple-600">{{ number_format($todayOutbound) }}</p>
            <p class="text-gray-400 text-xs mt-1">Sent</p>
        </div>

        {{-- Answered --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-gray-500 text-xs font-medium uppercase tracking-wide mb-2">Answered</p>
            <p class="text-3xl font-bold text-emerald-600">{{ number_format($todayAnswered) }}</p>
            <p class="text-gray-400 text-xs mt-1">Connected</p>
        </div>

        {{-- ASR --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-gray-500 text-xs font-medium uppercase tracking-wide mb-2">ASR</p>
            <p class="text-3xl font-bold {{ $todayAsr >= 50 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $todayAsr }}%</p>
            <p class="text-gray-400 text-xs mt-1">Answer Rate</p>
        </div>

        {{-- Minutes --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-gray-500 text-xs font-medium uppercase tracking-wide mb-2">Minutes</p>
            <p class="text-3xl font-bold text-indigo-600">{{ number_format($todayMinutes, 0) }}</p>
            <p class="text-gray-400 text-xs mt-1">Duration</p>
        </div>
    </div>

    {{-- Quick Navigation --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <a href="{{ route('admin.operational-reports.active') }}" class="group bg-white rounded-xl border border-gray-200 p-5 hover:border-indigo-300 hover:shadow-md transition-all">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-400 to-indigo-500 flex items-center justify-center shadow-md">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-gray-900 group-hover:text-indigo-600">Active Calls</p>
                    <p class="text-sm text-gray-500"><span id="idx-nav-live">{{ $activeCalls }}</span> live now</p>
                </div>
                <svg class="w-5 h-5 text-gray-300 group-hover:text-indigo-500 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>

        <a href="{{ route('admin.operational-reports.inbound') }}" class="group bg-white rounded-xl border border-gray-200 p-5 hover:border-indigo-300 hover:shadow-md transition-all">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-400 to-indigo-500 flex items-center justify-center shadow-md">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.517l2.257-1.128a1 1 0 00.502-1.21L9.228 3.683A1 1 0 008.279 3H5z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-gray-900 group-hover:text-indigo-600">Inbound Calls</p>
                    <p class="text-sm text-gray-500">{{ number_format($todayInbound) }} today</p>
                </div>
                <svg class="w-5 h-5 text-gray-300 group-hover:text-indigo-500 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>

        <a href="{{ route('admin.operational-reports.outbound') }}" class="group bg-white rounded-xl border border-gray-200 p-5 hover:border-indigo-300 hover:shadow-md transition-all">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-400 to-indigo-500 flex items-center justify-center shadow-md">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-gray-900 group-hover:text-indigo-600">Outbound Calls</p>
                    <p class="text-sm text-gray-500">{{ number_format($todayOutbound) }} today</p>
                </div>
                <svg class="w-5 h-5 text-gray-300 group-hover:text-indigo-500 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>

        <a href="{{ route('admin.operational-reports.summary') }}" class="group bg-white rounded-xl border border-gray-200 p-5 hover:border-indigo-300 hover:shadow-md transition-all">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-400 to-indigo-500 flex items-center justify-center shadow-md">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-gray-900 group-hover:text-indigo-600">Call Summary</p>
                    <p class="text-sm text-gray-500">Combined statistics</p>
                </div>
                <svg class="w-5 h-5 text-gray-300 group-hover:text-indigo-500 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>
    </div>

    {{-- Time-Based Reports --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <a href="{{ route('admin.operational-reports.daily') }}" class="group bg-white rounded-xl border border-gray-200 p-5 hover:border-indigo-300 hover:shadow-md transition-all">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-400 to-indigo-500 flex items-center justify-center shadow-md">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-gray-900 group-hover:text-indigo-600">Daily Summary</p>
                    <p class="text-sm text-gray-500">Day-by-day trends</p>
                </div>
                <svg class="w-5 h-5 text-gray-300 group-hover:text-indigo-500 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>

        <a href="{{ route('admin.operational-reports.monthly') }}" class="group bg-white rounded-xl border border-gray-200 p-5 hover:border-indigo-300 hover:shadow-md transition-all">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-400 to-indigo-500 flex items-center justify-center shadow-md">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-gray-900 group-hover:text-indigo-600">Monthly Summary</p>
                    <p class="text-sm text-gray-500">Month-over-month analysis</p>
                </div>
                <svg class="w-5 h-5 text-gray-300 group-hover:text-indigo-500 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>

        <a href="{{ route('admin.operational-reports.hourly') }}" class="group bg-white rounded-xl border border-gray-200 p-5 hover:border-indigo-300 hover:shadow-md transition-all">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-400 to-indigo-500 flex items-center justify-center shadow-md">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-gray-900 group-hover:text-indigo-600">Hourly Summary</p>
                    <p class="text-sm text-gray-500">Hour-by-hour breakdown</p>
                </div>
                <svg class="w-5 h-5 text-gray-300 group-hover:text-indigo-500 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>
    </div>

    {{-- Main Content --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Active Calls Table --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <h3 class="font-semibold text-gray-900">Active Calls</h3>
                        @if($activeCalls > 0)
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                {{ $activeCalls }} Live
                            </span>
                        @endif
                    </div>
                    @if($activeCalls > 0)
                        <a href="{{ route('admin.operational-reports.active') }}" class="text-sm text-indigo-600 hover:text-indigo-500 font-medium">View All</a>
                    @endif
                </div>

                @if($recentActive->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-100">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Caller</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Callee</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Duration</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Trunk</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($recentActive as $call)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            @if($call->call_flow === 'trunk_to_sip')
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                                    IN
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-purple-50 text-purple-700">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                                    OUT
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 font-mono text-gray-900">{{ Str::limit($call->caller, 15) }}</td>
                                        <td class="px-4 py-3 font-mono text-gray-900">{{ Str::limit($call->callee, 15) }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center gap-1.5 text-emerald-600 font-medium">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                {{ $call->call_start->diffForHumans(null, true) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">
                                            {{ Str::limit($call->call_flow === 'trunk_to_sip' ? ($call->incomingTrunk->name ?? '-') : ($call->outgoingTrunk->name ?? '-'), 12) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="py-12 text-center">
                        <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-gray-100 flex items-center justify-center">
                            <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </div>
                        <p class="text-gray-500 font-medium">No active calls</p>
                        <p class="text-sm text-gray-400">All lines are idle</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Top SIP Accounts --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900 text-sm">Top SIP Accounts</h3>
                </div>
                @if($topSipAccounts->count() > 0)
                    <div class="divide-y divide-gray-50">
                        @foreach($topSipAccounts as $index => $item)
                            @if($item->sipAccount)
                                <div class="px-4 py-3 flex items-center gap-3 hover:bg-gray-50">
                                    <span class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-600 text-xs font-bold flex items-center justify-center">{{ $index + 1 }}</span>
                                    <a href="{{ route('admin.sip-accounts.show', $item->sipAccount) }}" class="flex-1 text-sm text-gray-900 hover:text-indigo-600 truncate">
                                        {{ $item->sipAccount->username }}
                                    </a>
                                    <span class="text-sm font-semibold text-gray-500">{{ $item->call_count }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="p-4 text-center">
                        <p class="text-sm text-gray-400">No activity today</p>
                    </div>
                @endif
            </div>

            {{-- Top Trunks --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900 text-sm">Top Trunks</h3>
                </div>
                @if($topTrunks->count() > 0)
                    <div class="divide-y divide-gray-50">
                        @foreach($topTrunks as $index => $item)
                            @if($item->trunk)
                                <div class="px-4 py-3 flex items-center gap-3 hover:bg-gray-50">
                                    <span class="w-5 h-5 rounded-full bg-amber-100 text-amber-600 text-xs font-bold flex items-center justify-center">{{ $index + 1 }}</span>
                                    <div class="flex-1 min-w-0">
                                        <a href="{{ route('admin.trunks.show', $item->trunk) }}" class="text-sm text-gray-900 hover:text-indigo-600 truncate block">
                                            {{ $item->trunk->name }}
                                        </a>
                                        <span class="text-xs text-gray-400">{{ ucfirst($item->trunk->direction) }}</span>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-500">{{ $item->call_count }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="p-4 text-center">
                        <p class="text-sm text-gray-400">No activity today</p>
                    </div>
                @endif
            </div>

            {{-- Quick Summary --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900 text-sm">Today's Summary</h3>
                </div>
                <div class="p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Inbound Active</span>
                        <span class="text-sm font-semibold text-blue-600">{{ $inboundActive ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Outbound Active</span>
                        <span class="text-sm font-semibold text-purple-600">{{ $outboundActive ?? 0 }}</span>
                    </div>
                    <div class="border-t border-gray-100 pt-3 flex items-center justify-between">
                        <span class="text-sm text-gray-500">Failed/Missed</span>
                        <span class="text-sm font-semibold text-red-500">{{ number_format($todayTotal - $todayAnswered) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script>
(function() {
    const WS_URL = (window.location.protocol === 'https:' ? 'wss://' : 'ws://') + window.location.host + '/ws/live-calls';
    let ws = null;

    function connect() {
        ws = new WebSocket(WS_URL);

        ws.onmessage = function(event) {
            const data = JSON.parse(event.data);
            if (data.stats) {
                const el = document.getElementById('idx-live-count');
                const nav = document.getElementById('idx-nav-live');
                if (el) el.textContent = data.stats.total;
                if (nav) nav.textContent = data.stats.total;
            }
        };

        ws.onclose = function() {
            setTimeout(connect, 5000);
        };
    }

    // Send ping to keep alive
    setInterval(function() {
        if (ws && ws.readyState === WebSocket.OPEN) ws.send('ping');
    }, 25000);

    connect();
})();
</script>
@endpush
</x-admin-layout>
