<x-client-layout>
    <x-slot name="header">Dashboard</x-slot>

    {{-- Hero Greeting --}}
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    @php
                        $hour = now()->hour;
                        $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
                    @endphp
                    {{ $greeting }}, {{ auth()->user()->name }}
                </h1>
                <p class="text-gray-500 mt-1">Client Portal &mdash; {{ ucfirst(auth()->user()->billing_type) }} Account</p>
            </div>
            <div class="hidden sm:block text-right">
                <p class="text-sm font-medium text-gray-900">{{ now()->format('l, F j, Y') }}</p>
                <p class="text-sm text-gray-500">{{ now()->format('g:i A') }}</p>
            </div>
        </div>
    </div>

    {{-- Primary KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Balance --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ auth()->user()->billing_type === 'prepaid' ? 'bg-indigo-100 text-indigo-600' : 'bg-purple-100 text-purple-600' }}">
                    {{ ucfirst(auth()->user()->billing_type) }}
                </span>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ format_currency(auth()->user()->balance) }}</p>
            <p class="text-sm text-gray-500">Account Balance</p>
            @if(auth()->user()->credit_limit > 0)
                <p class="text-xs text-gray-400 mt-1">Credit: {{ format_currency(auth()->user()->credit_limit) }}</p>
            @endif
        </div>

        {{-- SIP Accounts --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-400 to-emerald-500 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <a href="{{ route('client.sip-accounts.index') }}" class="text-xs text-indigo-600 hover:text-indigo-500 font-medium">View</a>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($entityCounts['sip_accounts'] ?? 0) }}</p>
            <p class="text-sm text-gray-500">SIP Accounts</p>
        </div>

        {{-- DIDs --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-400 to-blue-500 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                    </svg>
                </div>
                <a href="{{ route('client.dids.index') }}" class="text-xs text-indigo-600 hover:text-indigo-500 font-medium">View</a>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($entityCounts['dids'] ?? 0) }}</p>
            <p class="text-sm text-gray-500">DIDs / Numbers</p>
        </div>

        {{-- Total Spent --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-400 to-purple-500 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-gray-400">7 Days</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ format_currency($weekStats['total_cost']) }}</p>
            <p class="text-sm text-gray-500">Total Spent</p>
            <div class="flex items-center text-xs text-gray-400 mt-1">
                Today: {{ format_currency($todayStats['today_cost']) }}
            </div>
        </div>
    </div>

    {{-- Call Stats (7 Days) --}}
    @php
        $totalDur = $weekStats['total_duration'];
        $totalBill = $weekStats['total_billable'];
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Total Calls</p>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($weekStats['total_calls']) }}</p>
            <div class="flex items-center mt-2">
                <span class="text-xs text-gray-400">Today: {{ number_format($todayStats['today_calls']) }}</span>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Answered</p>
            <p class="text-2xl font-bold text-emerald-600">{{ number_format($weekStats['answered_calls']) }}</p>
            <div class="flex items-center justify-between mt-2">
                <span class="text-xs text-gray-400">Today: {{ number_format($todayStats['today_answered']) }}</span>
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold {{ $weekStats['asr'] >= 50 ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                    {{ $weekStats['asr'] }}% ASR
                </span>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Failed</p>
            <p class="text-2xl font-bold text-red-500">{{ number_format($weekStats['failed_calls']) }}</p>
            <div class="flex items-center mt-2">
                <span class="text-xs text-gray-400">{{ $weekStats['total_calls'] > 0 ? round(($weekStats['failed_calls'] / $weekStats['total_calls']) * 100, 1) : 0 }}% failure rate</span>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Duration</p>
            <p class="text-2xl font-bold text-gray-900">
                {{ sprintf('%d:%02d', intdiv($totalDur, 3600), intdiv($totalDur % 3600, 60)) }}
            </p>
            <div class="flex items-center mt-2">
                <span class="text-xs text-gray-400">{{ $weekStats['acd'] > 0 ? sprintf('%dm %ds', intdiv($weekStats['acd'], 60), $weekStats['acd'] % 60) : '0s' }} avg/call</span>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Cost</p>
            <p class="text-2xl font-bold text-gray-900">{{ format_currency($weekStats['total_cost']) }}</p>
            <div class="flex items-center mt-2">
                <span class="text-xs text-gray-400">Today: {{ format_currency($todayStats['today_cost']) }}</span>
            </div>
        </div>
    </div>

    {{-- Chart + Account Info --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {{-- Chart --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">Call Volume (Last 7 Days)</h3>
                <span class="text-xs text-gray-400">{{ number_format($weekStats['total_calls']) }} total</span>
            </div>
            <div class="px-5 py-3">
                <canvas id="dailyChart" height="160"></canvas>
            </div>
        </div>

        {{-- Account Info Sidebar --}}
        <div class="space-y-4">
            <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-900 text-sm">Account Status</h3>
                    @if(auth()->user()->kyc_status === 'approved')
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Verified
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                            {{ ucfirst(auth()->user()->kyc_status) }}
                        </span>
                    @endif
                </div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Max Channels</span>
                        <span class="text-sm font-semibold text-gray-900">{{ auth()->user()->max_channels }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Billing Type</span>
                        <span class="text-sm font-semibold text-gray-900">{{ ucfirst(auth()->user()->billing_type) }}</span>
                    </div>
                    @if(auth()->user()->daily_spend_limit)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Daily Limit</span>
                        <span class="text-sm font-semibold text-gray-900">{{ format_currency(auth()->user()->daily_spend_limit) }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                <h3 class="font-semibold text-gray-900 text-sm mb-3">Quick Actions</h3>
                <div class="space-y-2">
                    <a href="{{ route('client.sip-accounts.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 transition-colors group">
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center group-hover:bg-indigo-200 transition-colors">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </div>
                        <span class="text-sm text-gray-700 group-hover:text-gray-900">SIP Accounts</span>
                    </a>
                    <a href="{{ route('client.cdr.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 transition-colors group">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <span class="text-sm text-gray-700 group-hover:text-gray-900">Call Records</span>
                    </a>
                    <a href="{{ route('client.transactions.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 transition-colors group">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center group-hover:bg-emerald-200 transition-colors">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <span class="text-sm text-gray-700 group-hover:text-gray-900">Transactions</span>
                    </a>
                    <a href="{{ route('client.invoices.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 transition-colors group">
                        <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center group-hover:bg-purple-200 transition-colors">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <span class="text-sm text-gray-700 group-hover:text-gray-900">Invoices</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Calls Table --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h3 class="font-semibold text-gray-900">Recent Calls</h3>
                <span class="text-xs text-gray-400">Today</span>
            </div>
            <a href="{{ route('client.cdr.index') }}" class="text-sm text-indigo-600 hover:text-indigo-500 font-medium">View All</a>
        </div>

        @if($recentCalls->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date & Time</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Caller</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Callee</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Duration</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Rate/min</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Cost</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($recentCalls as $call)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-gray-900">{{ $call->call_start?->format('H:i:s') }}</div>
                                    <div class="text-xs text-gray-400">{{ $call->call_start?->format('M d, Y') }}</div>
                                </td>
                                <td class="px-4 py-3 font-mono text-gray-900">{{ Str::limit($call->caller, 15) }}</td>
                                <td class="px-4 py-3 font-mono text-gray-900">{{ Str::limit($call->callee, 15) }}</td>
                                <td class="px-4 py-3 text-gray-900 text-right tabular-nums">
                                    {{ sprintf('%d:%02d', intdiv($call->billsec, 60), $call->billsec % 60) }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums text-gray-600">
                                    {{ $call->rate_per_minute > 0 ? format_currency($call->rate_per_minute, 4) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums font-medium text-gray-900">
                                    {{ (float) $call->total_cost > 0 ? format_currency($call->total_cost) : '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @switch($call->disposition)
                                        @case('ANSWERED')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Answered
                                            </span>
                                            @break
                                        @case('NO ANSWER')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                No Answer
                                            </span>
                                            @break
                                        @case('BUSY')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                Busy
                                            </span>
                                            @break
                                        @case('FAILED')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
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
                <p class="text-gray-500 font-medium">No calls today</p>
                <p class="text-sm text-gray-400">Call activity will appear here</p>
            </div>
        @endif
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        const ctx = document.getElementById('dailyChart').getContext('2d');
        const dailyData = @json($dailyData);

        const indigo = ctx.createLinearGradient(0, 0, 0, 250);
        indigo.addColorStop(0, 'rgba(99, 102, 241, 0.35)');
        indigo.addColorStop(1, 'rgba(99, 102, 241, 0.05)');

        const emerald = ctx.createLinearGradient(0, 0, 0, 250);
        emerald.addColorStop(0, 'rgba(16, 185, 129, 0.35)');
        emerald.addColorStop(1, 'rgba(16, 185, 129, 0.05)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: dailyData.map(d => d.date ? new Date(d.date).toLocaleDateString('en', {weekday: 'short', month: 'short', day: 'numeric'}) : d.label),
                datasets: [
                    {
                        label: 'Total Calls',
                        data: dailyData.map(d => d.calls),
                        borderColor: 'rgb(99, 102, 241)',
                        backgroundColor: indigo,
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: 'rgb(99, 102, 241)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointHoverBorderWidth: 3,
                    },
                    {
                        label: 'Answered',
                        data: dailyData.map(d => d.answered),
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: emerald,
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: 'rgb(16, 185, 129)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointHoverBorderWidth: 3,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            boxWidth: 10, boxHeight: 10, borderRadius: 5, useBorderRadius: true,
                            padding: 20, font: { size: 12, weight: '500' }, color: '#6b7280',
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(17, 24, 39, 0.95)',
                        titleFont: { size: 13, weight: '600' },
                        bodyFont: { size: 12 },
                        padding: 12, cornerRadius: 10,
                        displayColors: true, boxWidth: 8, boxHeight: 8, boxPadding: 4,
                        callbacks: {
                            label: function(context) {
                                return ' ' + context.dataset.label + ': ' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        suggestedMin: Math.max(0, Math.min(...dailyData.map(d => d.answered)) - 10),
                        grid: { color: 'rgba(0, 0, 0, 0.04)', drawBorder: false },
                        ticks: { font: { size: 11 }, color: '#9ca3af', padding: 8, maxTicksLimit: 5 },
                        border: { display: false },
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 }, color: '#9ca3af', padding: 8 },
                        border: { display: false },
                    }
                }
            }
        });
    </script>
    @endpush
</x-client-layout>
