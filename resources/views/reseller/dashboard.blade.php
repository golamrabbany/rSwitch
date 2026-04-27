<x-reseller-layout>
    <x-slot name="header">Dashboard</x-slot>

    @php
        $user = auth()->user();
        $totalDur = $weekStats['total_duration'];
        $callsChange = ($prevWeekStats['total_calls'] ?? 0) > 0
            ? round((($weekStats['total_calls'] - $prevWeekStats['total_calls']) / $prevWeekStats['total_calls']) * 100, 1)
            : ($weekStats['total_calls'] > 0 ? 100 : 0);
        $costChange = ($prevWeekStats['total_cost'] ?? 0) > 0
            ? round((($weekStats['total_cost'] - $prevWeekStats['total_cost']) / $prevWeekStats['total_cost']) * 100, 1)
            : ($weekStats['total_cost'] > 0 ? 100 : 0);
    @endphp

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">
                @php $hour = now()->hour; $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening'); @endphp
                {{ $greeting }}, {{ $user->name }}
            </h2>
            <p class="page-subtitle">{{ ucfirst($user->billing_type) }} Account &middot; {{ now()->format('l, F j, Y') }}</p>
        </div>
    </div>

    {{-- Account Summary Strip --}}
    <div class="rounded-xl mb-6" style="background:linear-gradient(135deg, #064e3b 0%, #065f46 100%); padding:0.75rem 0;">
        <div style="display:grid; grid-template-columns: repeat(5, 1fr);">
            <div style="padding:0 1.5rem; border-right:1px solid rgba(255,255,255,0.1);">
                <p class="text-xs font-semibold uppercase tracking-wider mb-1" style="color:#a7f3d0; letter-spacing:0.1em;">Balance</p>
                <p class="font-bold text-white tabular-nums" style="font-size:1.5rem; line-height:1;">{{ format_currency($user->balance) }}</p>
                <p class="text-xs mt-1" style="color:#6ee7b7;">{{ ucfirst($user->billing_type) }}{{ $user->credit_limit > 0 ? ' / Credit: ' . format_currency($user->credit_limit) : '' }}</p>
            </div>
            <div style="padding:0 1.5rem; border-right:1px solid rgba(255,255,255,0.1);">
                <p class="text-xs font-semibold uppercase tracking-wider mb-1" style="color:#a7f3d0; letter-spacing:0.1em;">Clients</p>
                <p class="font-bold text-white tabular-nums" style="font-size:1.5rem; line-height:1;">{{ number_format($entityCounts['clients'] ?? 0) }}</p>
                <p class="text-xs mt-1" style="color:#6ee7b7;">active accounts</p>
            </div>
            <div style="padding:0 1.5rem; border-right:1px solid rgba(255,255,255,0.1);">
                <p class="text-xs font-semibold uppercase tracking-wider mb-1" style="color:#a7f3d0; letter-spacing:0.1em;">SIP Accounts</p>
                <p class="font-bold text-white tabular-nums" style="font-size:1.5rem; line-height:1;">{{ number_format($entityCounts['sip_accounts'] ?? 0) }}</p>
                <p class="text-xs mt-1" style="color:#6ee7b7;">endpoints</p>
            </div>
            <div style="padding:0 1.5rem; border-right:1px solid rgba(255,255,255,0.1);">
                <p class="text-xs font-semibold uppercase tracking-wider mb-1" style="color:#a7f3d0; letter-spacing:0.1em;">Today's Calls</p>
                <p class="font-bold text-white tabular-nums" style="font-size:1.5rem; line-height:1;">{{ number_format($todayStats['today_calls']) }}</p>
                <div class="flex items-center gap-2 text-xs mt-1">
                    <span style="color:#4ade80;">{{ number_format($todayStats['today_answered']) }} ans</span>
                    <span style="color:rgba(255,255,255,0.2);">|</span>
                    <span style="color:#fca5a5;">{{ $todayStats['today_calls'] - $todayStats['today_answered'] }} fail</span>
                </div>
            </div>
            <div style="padding:0 1.5rem;">
                <p class="text-xs font-semibold uppercase tracking-wider mb-1" style="color:#a7f3d0; letter-spacing:0.1em;">Today's Cost</p>
                <p class="font-bold text-white tabular-nums" style="font-size:1.5rem; line-height:1;">{{ format_currency($todayStats['today_cost']) }}</p>
                <p class="text-xs mt-1" style="color:#6ee7b7;">{{ $user->currency }}</p>
            </div>
        </div>
    </div>

    {{-- Weekly Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">7-Day Calls</p>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($weekStats['total_calls']) }}</p>
            <div class="flex items-center justify-between mt-2">
                <span class="text-xs text-gray-400">vs prev week</span>
                <span class="inline-flex items-center text-xs {{ $callsChange >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $callsChange >= 0 ? '+' : '' }}{{ $callsChange }}%
                </span>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Answered</p>
            <p class="text-2xl font-bold text-emerald-600">{{ number_format($weekStats['answered_calls']) }}</p>
            <div class="flex items-center justify-between mt-2">
                <span class="text-xs text-gray-400">ASR</span>
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold {{ $weekStats['asr'] >= 50 ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                    {{ $weekStats['asr'] }}%
                </span>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Failed</p>
            <p class="text-2xl font-bold text-red-500">{{ number_format($weekStats['failed_calls']) }}</p>
            <div class="flex items-center mt-2">
                <span class="text-xs text-gray-400">{{ $weekStats['total_calls'] > 0 ? round(($weekStats['failed_calls'] / $weekStats['total_calls']) * 100, 1) : 0 }}% rate</span>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Duration</p>
            <p class="text-2xl font-bold text-gray-900">{{ sprintf('%d:%02d', intdiv($totalDur, 3600), intdiv($totalDur % 3600, 60)) }}</p>
            <div class="flex items-center mt-2">
                <span class="text-xs text-gray-400">{{ $weekStats['acd'] > 0 ? sprintf('%dm %ds', intdiv($weekStats['acd'], 60), $weekStats['acd'] % 60) : '0s' }} avg</span>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">7-Day Cost</p>
            <p class="text-2xl font-bold text-gray-900">{{ format_currency($weekStats['total_cost']) }}</p>
            <div class="flex items-center justify-between mt-2">
                <span class="text-xs text-gray-400">vs prev</span>
                <span class="inline-flex items-center text-xs {{ $costChange >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $costChange >= 0 ? '+' : '' }}{{ $costChange }}%
                </span>
            </div>
        </div>
    </div>

    {{-- Chart + Sidebar --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {{-- Chart --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">Call Volume (Last 7 Days)</h3>
                <span class="text-xs text-gray-400">{{ number_format($weekStats['total_calls']) }} total</span>
            </div>
            <div class="px-5 py-3">
                <canvas id="dailyChart" height="160"></canvas>
            </div>
        </div>

        {{-- Quick Actions + Account --}}
        <div class="space-y-4">
            {{-- Account Info --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-900 text-sm">Account</h3>
                    @if($user->kyc_status === 'approved')
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Verified</span>
                    @else
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>{{ ucfirst($user->kyc_status) }}</span>
                    @endif
                </div>
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Max Channels</span>
                        <span class="font-semibold text-gray-900">{{ $user->max_channels }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Currency</span>
                        <span class="font-semibold text-gray-900">{{ $user->currency }}</span>
                    </div>
                    @if($user->daily_spend_limit)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Daily Limit</span>
                        <span class="font-semibold text-gray-900">{{ format_currency($user->daily_spend_limit) }}</span>
                    </div>
                    @endif
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">DIDs</span>
                        <span class="font-semibold text-gray-900">{{ number_format($entityCounts['dids'] ?? 0) }}</span>
                    </div>
                </div>
            </div>

            {{-- Broadcast Stats --}}
            @if(($broadcastStats['total'] ?? 0) > 0)
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-900 text-sm">Broadcasts</h3>
                    <a href="{{ route('reseller.broadcasts.index') }}" class="text-xs text-emerald-600 hover:text-emerald-700 font-medium">View All</a>
                </div>
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Running</span>
                        <span class="font-semibold {{ ($broadcastStats['running'] ?? 0) > 0 ? 'text-emerald-600' : 'text-gray-900' }}">{{ $broadcastStats['running'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Completed</span>
                        <span class="font-semibold text-gray-900">{{ $broadcastStats['completed'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Total</span>
                        <span class="font-semibold text-gray-900">{{ $broadcastStats['total'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
            @endif

            {{-- Quick Actions --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="font-semibold text-gray-900 text-sm mb-3">Quick Actions</h3>
                <div class="space-y-1.5">
                    <a href="{{ route('reseller.clients.create') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-emerald-50 transition-colors group">
                        <div class="w-7 h-7 rounded-lg bg-emerald-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </div>
                        <span class="text-sm text-gray-700 group-hover:text-gray-900">Add Client</span>
                    </a>
                    <a href="{{ route('reseller.sip-accounts.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-emerald-50 transition-colors group">
                        <div class="w-7 h-7 rounded-lg bg-blue-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </div>
                        <span class="text-sm text-gray-700 group-hover:text-gray-900">SIP Accounts</span>
                    </a>
                    <a href="{{ route('reseller.cdr.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-emerald-50 transition-colors group">
                        <div class="w-7 h-7 rounded-lg bg-indigo-100 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                        <span class="text-sm text-gray-700 group-hover:text-gray-900">Call Records</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Calls --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">Recent Calls</h3>
            <a href="{{ route('reseller.cdr.index') }}" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">View All</a>
        </div>

        @if($recentCalls->count() > 0)
            <div class="overflow-x-auto" style="max-height: 360px; overflow-y: auto;">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100 sticky top-0">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Time</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Caller</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Callee</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Dur.</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Client Cost</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">My Cost</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Profit</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Client</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentCalls as $call)
                            @php
                                $clientCost = (float) $call->total_cost;
                                $resellerCost = (float) $call->reseller_cost;
                                $profit = $clientCost - $resellerCost;
                            @endphp
                            <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-emerald-50/50 transition-all border-b border-gray-100">
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <div class="text-gray-900">{{ $call->call_start?->format('H:i:s') }}</div>
                                    <div class="text-xs text-gray-400">{{ $call->call_start?->format('M d') }}</div>
                                </td>
                                <td class="px-3 py-2 font-mono text-gray-900">{{ Str::limit($call->caller, 15) }}</td>
                                <td class="px-3 py-2 font-mono text-gray-900">{{ Str::limit($call->callee, 15) }}</td>
                                <td class="px-3 py-2 text-right tabular-nums text-gray-700">{{ sprintf('%d:%02d', intdiv($call->billsec, 60), $call->billsec % 60) }}</td>
                                <td class="px-3 py-2 text-right tabular-nums font-medium text-gray-900">{{ $clientCost > 0 ? format_currency($clientCost) : '—' }}</td>
                                <td class="px-3 py-2 text-right tabular-nums text-gray-500">{{ $resellerCost > 0 ? format_currency($resellerCost) : '—' }}</td>
                                <td class="px-3 py-2 text-right tabular-nums font-medium {{ $profit > 0 ? 'text-emerald-600' : ($profit < 0 ? 'text-red-600' : 'text-gray-400') }}">{{ $clientCost > 0 ? format_currency($profit) : '—' }}</td>
                                <td class="px-3 py-2 text-gray-500">{{ $call->user?->name ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    @switch($call->disposition)
                                        @case('ANSWERED')
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Answered</span>
                                            @break
                                        @case('NO ANSWER')
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>No Answer</span>
                                            @break
                                        @case('CANCEL')
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-600" title="Caller hung up before answer"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>Cancelled</span>
                                            @break
                                        @case('BUSY')
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Busy</span>
                                            @break
                                        @case('FAILED')
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Failed</span>
                                            @break
                                        @default
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-500"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>{{ $call->disposition }}</span>
                                    @endswitch
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="py-12 text-center">
                <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                <p class="text-sm text-gray-400">No calls today</p>
            </div>
        @endif
    </div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    const ctx = document.getElementById('dailyChart').getContext('2d');
    const dailyData = @json($dailyData);

    const emeraldGrad = ctx.createLinearGradient(0, 0, 0, 250);
    emeraldGrad.addColorStop(0, 'rgba(16, 185, 129, 0.3)');
    emeraldGrad.addColorStop(1, 'rgba(16, 185, 129, 0.02)');

    const grayGrad = ctx.createLinearGradient(0, 0, 0, 250);
    grayGrad.addColorStop(0, 'rgba(99, 102, 241, 0.2)');
    grayGrad.addColorStop(1, 'rgba(99, 102, 241, 0.02)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dailyData.map(d => d.date ? new Date(d.date).toLocaleDateString('en', {weekday: 'short', month: 'short', day: 'numeric'}) : d.label),
            datasets: [
                {
                    label: 'Total Calls',
                    data: dailyData.map(d => d.calls),
                    borderColor: 'rgb(99, 102, 241)',
                    backgroundColor: grayGrad,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgb(99, 102, 241)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                },
                {
                    label: 'Answered',
                    data: dailyData.map(d => d.answered),
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: emeraldGrad,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgb(16, 185, 129)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: {
                    position: 'top', align: 'end',
                    labels: { boxWidth: 10, boxHeight: 10, borderRadius: 5, useBorderRadius: true, padding: 20, font: { size: 12, weight: '500' }, color: '#6b7280' }
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.95)', titleFont: { size: 13, weight: '600' }, bodyFont: { size: 12 },
                    padding: 12, cornerRadius: 10, displayColors: true, boxWidth: 8, boxHeight: 8, boxPadding: 4
                }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)', drawBorder: false }, ticks: { font: { size: 11 }, color: '#9ca3af', padding: 8, maxTicksLimit: 5 }, border: { display: false } },
                x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#9ca3af', padding: 8 }, border: { display: false } }
            }
        }
    });
</script>
@endpush
</x-reseller-layout>
