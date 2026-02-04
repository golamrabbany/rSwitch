<x-admin-layout>
    <x-slot name="header">Dashboard</x-slot>

    {{-- Entity Cards --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <a href="{{ route('admin.users.index', ['role' => 'reseller']) }}" class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow hover:shadow-md transition-shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Resellers</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($entityCounts['resellers']) }}</dd>
        </a>
        <a href="{{ route('admin.users.index', ['role' => 'client']) }}" class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow hover:shadow-md transition-shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Clients</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($entityCounts['clients']) }}</dd>
        </a>
        <a href="{{ route('admin.sip-accounts.index') }}" class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow hover:shadow-md transition-shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">SIP Accounts</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($entityCounts['sip_accounts']) }}</dd>
        </a>
        <a href="{{ route('admin.trunks.index') }}" class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow hover:shadow-md transition-shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Active Trunks</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($entityCounts['active_trunks']) }}</dd>
        </a>
        <a href="{{ route('admin.dids.index') }}" class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow hover:shadow-md transition-shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Active DIDs</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($entityCounts['active_dids']) }}</dd>
        </a>
        <a href="{{ route('admin.kyc.index') }}?status=pending" class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow hover:shadow-md transition-shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Pending KYC</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight {{ $entityCounts['pending_kyc'] > 0 ? 'text-yellow-600' : 'text-gray-900' }}">{{ number_format($entityCounts['pending_kyc']) }}</dd>
        </a>
    </div>

    {{-- Call Stats (Last 7 Days) --}}
    @php
        $totalDur = $weekStats['total_duration'];
        $totalBill = $weekStats['total_billable'];
    @endphp
    <div class="mt-6">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Last 7 Days</h2>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5">
            <div class="bg-white shadow sm:rounded-lg p-4">
                <dt class="text-sm font-medium text-gray-500">Total Calls</dt>
                <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($weekStats['total_calls']) }}</dd>
                <dd class="text-xs text-gray-400 mt-1">Today: {{ number_format($todayStats['today_calls']) }}</dd>
            </div>
            <div class="bg-white shadow sm:rounded-lg p-4">
                <dt class="text-sm font-medium text-gray-500">Answered</dt>
                <dd class="mt-1 text-2xl font-semibold text-green-600">
                    {{ number_format($weekStats['answered_calls']) }}
                    <span class="text-sm font-normal text-gray-500">({{ $weekStats['asr'] }}% ASR)</span>
                </dd>
                <dd class="text-xs text-gray-400 mt-1">Today: {{ number_format($todayStats['today_answered']) }}</dd>
            </div>
            <div class="bg-white shadow sm:rounded-lg p-4">
                <dt class="text-sm font-medium text-gray-500">Total Duration</dt>
                <dd class="mt-1 text-2xl font-semibold text-gray-900">
                    {{ sprintf('%d:%02d:%02d', intdiv($totalDur, 3600), intdiv($totalDur % 3600, 60), $totalDur % 60) }}
                </dd>
            </div>
            <div class="bg-white shadow sm:rounded-lg p-4">
                <dt class="text-sm font-medium text-gray-500">Billable Duration</dt>
                <dd class="mt-1 text-2xl font-semibold text-gray-900">
                    {{ sprintf('%d:%02d:%02d', intdiv($totalBill, 3600), intdiv($totalBill % 3600, 60), $totalBill % 60) }}
                </dd>
            </div>
            <div class="bg-white shadow sm:rounded-lg p-4">
                <dt class="text-sm font-medium text-gray-500">Total Cost</dt>
                <dd class="mt-1 text-2xl font-semibold text-gray-900">${{ number_format($weekStats['total_cost'], 2) }}</dd>
                <dd class="text-xs text-gray-400 mt-1">Today: ${{ number_format($todayStats['today_cost'], 2) }}</dd>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="mt-8">
        <h2 class="text-base font-semibold text-gray-900">Quick Actions</h2>
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <a href="{{ route('admin.users.create') }}" class="relative flex items-center space-x-3 rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm hover:border-gray-400">
                <div class="shrink-0">
                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900">Create User</p>
                    <p class="text-sm text-gray-500">Add a new reseller or client</p>
                </div>
            </a>

            <a href="{{ route('admin.kyc.index') }}?status=pending" class="relative flex items-center space-x-3 rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm hover:border-gray-400">
                <div class="shrink-0">
                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900">Review KYC</p>
                    <p class="text-sm text-gray-500">Pending verifications</p>
                </div>
            </a>

            <a href="{{ route('admin.cdr.index') }}" class="relative flex items-center space-x-3 rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm hover:border-gray-400">
                <div class="shrink-0">
                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900">View CDR</p>
                    <p class="text-sm text-gray-500">Call detail records and reports</p>
                </div>
            </a>
        </div>
    </div>

    {{-- Recent Calls --}}
    <div class="mt-8">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-base font-semibold text-gray-900">Recent Calls (Today)</h2>
            <a href="{{ route('admin.cdr.index') }}" class="text-sm text-indigo-600 hover:text-indigo-500">View all</a>
        </div>
        <div class="bg-white shadow sm:rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Caller</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Callee</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disposition</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($recentCalls as $call)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                                <a href="{{ route('admin.cdr.show', ['uuid' => $call->uuid, 'date' => $call->call_start?->format('Y-m-d')]) }}"
                                   class="text-indigo-600 hover:text-indigo-500">
                                    {{ $call->call_start?->format('H:i:s') }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-sm font-mono text-gray-900">{{ $call->caller }}</td>
                            <td class="px-4 py-3 text-sm font-mono text-gray-900">{{ $call->callee }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 text-right tabular-nums">
                                {{ sprintf('%d:%02d', intdiv($call->duration, 60), $call->duration % 60) }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @switch($call->disposition)
                                    @case('ANSWERED')
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">ANSWERED</span>
                                        @break
                                    @case('NO ANSWER')
                                        <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">NO ANSWER</span>
                                        @break
                                    @case('BUSY')
                                        <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">BUSY</span>
                                        @break
                                    @case('FAILED')
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">FAILED</span>
                                        @break
                                    @default
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">{{ $call->disposition }}</span>
                                @endswitch
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $call->user?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">
                                No calls today yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
