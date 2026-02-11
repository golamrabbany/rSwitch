<x-client-layout>
    <x-slot name="header">Client Dashboard</x-slot>

    {{-- Hero Section with Greeting --}}
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
                <p class="text-gray-500 mt-1">Here's your account overview</p>
            </div>
            <div class="text-right hidden sm:block">
                <p class="text-sm font-medium text-gray-900">{{ now()->format('l, F j, Y') }}</p>
                <p class="text-sm text-gray-500">{{ now()->format('g:i A') }}</p>
            </div>
        </div>
    </div>

    {{-- Primary KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Balance Card --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ auth()->user()->billing_type === 'prepaid' ? 'bg-indigo-100 text-indigo-600' : 'bg-purple-100 text-purple-600' }}">
                    {{ ucfirst(auth()->user()->billing_type) }}
                </span>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ format_currency(auth()->user()->balance) }}</p>
            <p class="text-sm text-gray-500 mb-2">Account Balance</p>
            <a href="{{ route('client.payments.create') }}" class="inline-flex items-center text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add Funds
            </a>
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
            <div class="flex items-center text-xs text-gray-500">
                Today: {{ number_format($todayStats['today_calls']) }}
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
            </div>
        </div>

        {{-- Total Cost Card --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-gray-400">7 Days</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ format_currency($weekStats['total_cost']) }}</p>
            <p class="text-sm text-gray-500 mb-2">Total Spent</p>
            <div class="flex items-center text-xs text-gray-500">
                Today: {{ format_currency($todayStats['today_cost']) }}
            </div>
        </div>
    </div>

    {{-- Today's Stats Banner --}}
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl p-5 mb-6 text-white shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h3 class="text-lg font-semibold opacity-90">Today's Activity</h3>
                <p class="text-sm opacity-70">{{ now()->format('l, F j') }}</p>
            </div>
            <div class="flex items-center gap-8">
                <div class="text-center">
                    <p class="text-3xl font-bold">{{ number_format($todayStats['today_calls']) }}</p>
                    <p class="text-sm opacity-70">Calls</p>
                </div>
                <div class="w-px h-10 bg-white/20"></div>
                <div class="text-center">
                    <p class="text-3xl font-bold">{{ number_format($todayStats['today_answered']) }}</p>
                    <p class="text-sm opacity-70">Answered</p>
                </div>
                <div class="w-px h-10 bg-white/20"></div>
                <div class="text-center">
                    <p class="text-3xl font-bold">{{ format_currency($todayStats['today_cost']) }}</p>
                    <p class="text-sm opacity-70">Spent</p>
                </div>
                <div class="w-px h-10 bg-white/20 hidden lg:block"></div>
                <div class="text-center hidden lg:block">
                    @php
                        $todayDurMins = intdiv($todayStats['today_duration'] ?? 0, 60);
                    @endphp
                    <p class="text-3xl font-bold tabular-nums">{{ $todayDurMins }}</p>
                    <p class="text-sm opacity-70">Minutes</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-12 gap-6 mb-6">
        {{-- Duration Stats --}}
        <div class="col-span-12 lg:col-span-8">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Call Duration Stats (Last 7 Days)</h3>
                    <a href="{{ route('client.cdr.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium flex items-center">
                        View All CDR
                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="p-5">
                    @php
                        $totalDur = $weekStats['total_duration'];
                        $totalBill = $weekStats['total_billable'];
                        $totalHours = intdiv($totalDur, 3600);
                        $totalMins = intdiv($totalDur % 3600, 60);
                        $billHours = intdiv($totalBill, 3600);
                        $billMins = intdiv($totalBill % 3600, 60);
                    @endphp
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="p-4 rounded-lg border border-gray-100 bg-gray-50/50">
                            <div class="w-9 h-9 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center mb-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <p class="text-xl font-bold text-gray-900 tabular-nums">{{ $totalHours }}h {{ $totalMins }}m</p>
                            <p class="text-xs text-gray-500">Total Duration</p>
                        </div>
                        <div class="p-4 rounded-lg border border-gray-100 bg-gray-50/50">
                            <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center mb-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <p class="text-xl font-bold text-gray-900 tabular-nums">{{ $billHours }}h {{ $billMins }}m</p>
                            <p class="text-xs text-gray-500">Billable Duration</p>
                        </div>
                        <div class="p-4 rounded-lg border border-gray-100 bg-gray-50/50">
                            <div class="w-9 h-9 rounded-lg bg-sky-100 text-sky-600 flex items-center justify-center mb-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                            </div>
                            <p class="text-xl font-bold text-gray-900">{{ auth()->user()->max_channels }}</p>
                            <p class="text-xs text-gray-500">Max Channels</p>
                        </div>
                        <div class="p-4 rounded-lg border border-gray-100 bg-gray-50/50">
                            <div class="w-9 h-9 rounded-lg {{ auth()->user()->kyc_status === 'approved' ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600' }} flex items-center justify-center mb-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <p class="text-xl font-bold {{ auth()->user()->kyc_status === 'approved' ? 'text-emerald-600' : 'text-amber-600' }}">{{ ucfirst(auth()->user()->kyc_status) }}</p>
                            <p class="text-xs text-gray-500">KYC Status</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- My Resources --}}
        <div class="col-span-12 lg:col-span-4">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm h-full">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">My Resources</h3>
                </div>
                <div class="p-5 space-y-4">
                    {{-- SIP Accounts --}}
                    <a href="{{ route('client.sip-accounts.index') }}" class="flex items-center justify-between p-3 rounded-lg border border-gray-100 hover:border-indigo-200 hover:bg-indigo-50/50 transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center group-hover:bg-indigo-500 group-hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">SIP Accounts</p>
                                <p class="text-xs text-gray-500">Manage endpoints</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold text-indigo-600">{{ number_format($entityCounts['sip_accounts'] ?? 0) }}</p>
                        </div>
                    </a>

                    {{-- DIDs --}}
                    <a href="{{ route('client.dids.index') }}" class="flex items-center justify-between p-3 rounded-lg border border-gray-100 hover:border-emerald-200 hover:bg-emerald-50/50 transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">DIDs</p>
                                <p class="text-xs text-gray-500">Phone numbers</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold text-emerald-600">{{ number_format($entityCounts['dids'] ?? 0) }}</p>
                        </div>
                    </a>

                    {{-- Invoices --}}
                    <a href="{{ route('client.invoices.index') }}" class="flex items-center justify-between p-3 rounded-lg border border-gray-100 hover:border-sky-200 hover:bg-sky-50/50 transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-sky-100 text-sky-600 flex items-center justify-center group-hover:bg-sky-500 group-hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Invoices</p>
                                <p class="text-xs text-gray-500">Billing history</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <svg class="w-4 h-4 text-gray-300 group-hover:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </a>

                    {{-- Transactions --}}
                    <a href="{{ route('client.transactions.index') }}" class="flex items-center justify-between p-3 rounded-lg border border-gray-100 hover:border-violet-200 hover:bg-violet-50/50 transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-violet-100 text-violet-600 flex items-center justify-center group-hover:bg-violet-500 group-hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Transactions</p>
                                <p class="text-xs text-gray-500">Payment history</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <svg class="w-4 h-4 text-gray-300 group-hover:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Calls --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">Recent Calls (Today)</h3>
            <a href="{{ route('client.cdr.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium flex items-center">
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
                                <span class="text-sm font-medium text-gray-900">{{ $call->call_start?->format('H:i:s') }}</span>
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
</x-client-layout>
