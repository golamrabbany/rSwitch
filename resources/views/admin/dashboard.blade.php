<x-admin-layout>
    <x-slot name="header">Dashboard</x-slot>

    {{-- Entity Cards --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <a href="{{ route('admin.users.index', ['role' => 'reseller']) }}" class="stat-card group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-card-label">Resellers</p>
                    <p class="stat-card-value">{{ number_format($entityCounts['resellers']) }}</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center group-hover:bg-primary-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2"/>
                        <circle cx="12" cy="7" r="4" stroke-width="1.5"/>
                    </svg>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.users.index', ['role' => 'client']) }}" class="stat-card group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-card-label">Clients</p>
                    <p class="stat-card-value">{{ number_format($entityCounts['clients']) }}</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center group-hover:bg-blue-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                        <circle cx="9" cy="7" r="4" stroke-width="1.5"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M23 21v-2a4 4 0 00-3-3.87"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 3.13a4 4 0 010 7.75"/>
                    </svg>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.sip-accounts.index') }}" class="stat-card group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-card-label">SIP Accounts</p>
                    <p class="stat-card-value">{{ number_format($entityCounts['sip_accounts']) }}</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:bg-emerald-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.trunks.index') }}" class="stat-card group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-card-label">Active Trunks</p>
                    <p class="stat-card-value">{{ number_format($entityCounts['active_trunks']) }}</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center group-hover:bg-violet-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.dids.index') }}" class="stat-card group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-card-label">Active DIDs</p>
                    <p class="stat-card-value">{{ number_format($entityCounts['active_dids']) }}</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center group-hover:bg-cyan-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                    </svg>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.kyc.index') }}?status=pending" class="stat-card group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-card-label">Pending KYC</p>
                    <p class="stat-card-value {{ $entityCounts['pending_kyc'] > 0 ? 'text-amber-600' : '' }}">{{ number_format($entityCounts['pending_kyc']) }}</p>
                </div>
                <div class="w-10 h-10 rounded-lg {{ $entityCounts['pending_kyc'] > 0 ? 'bg-amber-50 text-amber-600' : 'bg-gray-50 text-gray-400' }} flex items-center justify-center group-hover:bg-amber-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
            </div>
        </a>
    </div>

    {{-- Call Stats (Last 7 Days) --}}
    @php
        $totalDur = $weekStats['total_duration'];
        $totalBill = $weekStats['total_billable'];
    @endphp
    <div class="mt-8">
        <div class="section-header">
            <h2 class="section-title">Last 7 Days Performance</h2>
        </div>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            <div class="stat-card">
                <p class="stat-card-label">Total Calls</p>
                <p class="stat-card-value">{{ number_format($weekStats['total_calls']) }}</p>
                <p class="stat-card-sub">Today: {{ number_format($todayStats['today_calls']) }}</p>
            </div>

            <div class="stat-card">
                <p class="stat-card-label">Answered Calls</p>
                <p class="stat-card-value text-emerald-600">{{ number_format($weekStats['answered_calls']) }}</p>
                <p class="stat-card-sub">
                    <span class="inline-flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        {{ $weekStats['asr'] }}% ASR
                    </span>
                </p>
            </div>

            <div class="stat-card">
                <p class="stat-card-label">Total Duration</p>
                <p class="stat-card-value font-mono">{{ sprintf('%d:%02d:%02d', intdiv($totalDur, 3600), intdiv($totalDur % 3600, 60), $totalDur % 60) }}</p>
                <p class="stat-card-sub">Hours : Minutes : Seconds</p>
            </div>

            <div class="stat-card">
                <p class="stat-card-label">Billable Duration</p>
                <p class="stat-card-value font-mono">{{ sprintf('%d:%02d:%02d', intdiv($totalBill, 3600), intdiv($totalBill % 3600, 60), $totalBill % 60) }}</p>
                <p class="stat-card-sub">Hours : Minutes : Seconds</p>
            </div>

            <div class="stat-card">
                <p class="stat-card-label">Total Cost</p>
                <p class="stat-card-value">${{ number_format($weekStats['total_cost'], 2) }}</p>
                <p class="stat-card-sub">Today: ${{ number_format($todayStats['today_cost'], 2) }}</p>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="mt-8">
        <div class="section-header">
            <h2 class="section-title">Quick Actions</h2>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <a href="{{ route('admin.users.create') }}" class="quick-action">
                <div class="quick-action-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900">Create User</p>
                    <p class="text-sm text-gray-500">Add a new reseller or client</p>
                </div>
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"/>
                </svg>
            </a>

            <a href="{{ route('admin.kyc.index') }}?status=pending" class="quick-action">
                <div class="quick-action-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900">Review KYC</p>
                    <p class="text-sm text-gray-500">Pending verifications</p>
                </div>
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"/>
                </svg>
            </a>

            <a href="{{ route('admin.cdr.index') }}" class="quick-action">
                <div class="quick-action-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900">View CDR</p>
                    <p class="text-sm text-gray-500">Call detail records and reports</p>
                </div>
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>

    {{-- Recent Calls --}}
    <div class="mt-8">
        <div class="section-header">
            <h2 class="section-title">Recent Calls</h2>
            <a href="{{ route('admin.cdr.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">
                View all
                <span aria-hidden="true"> &rarr;</span>
            </a>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Caller</th>
                        <th>Callee</th>
                        <th class="text-right">Duration</th>
                        <th>Status</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentCalls as $call)
                        <tr>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('admin.cdr.show', ['uuid' => $call->uuid, 'date' => $call->call_start?->format('Y-m-d')]) }}"
                                   class="font-medium text-primary-600 hover:text-primary-700">
                                    {{ $call->call_start?->format('H:i:s') }}
                                </a>
                            </td>
                            <td class="font-mono text-gray-900">{{ $call->caller }}</td>
                            <td class="font-mono text-gray-900">{{ $call->callee }}</td>
                            <td class="text-right font-mono tabular-nums">
                                {{ sprintf('%d:%02d', intdiv($call->duration, 60), $call->duration % 60) }}
                            </td>
                            <td>
                                @switch($call->disposition)
                                    @case('ANSWERED')
                                        <span class="badge-success">Answered</span>
                                        @break
                                    @case('NO ANSWER')
                                        <span class="badge-warning">No Answer</span>
                                        @break
                                    @case('BUSY')
                                        <span class="badge-warning">Busy</span>
                                        @break
                                    @case('FAILED')
                                        <span class="badge-danger">Failed</span>
                                        @break
                                    @default
                                        <span class="badge-gray">{{ $call->disposition }}</span>
                                @endswitch
                            </td>
                            <td class="text-gray-500">{{ $call->user?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    <p class="text-gray-500">No calls today yet</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
