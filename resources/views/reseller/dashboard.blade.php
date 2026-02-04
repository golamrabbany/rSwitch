<x-reseller-layout>
    <x-slot name="header">Reseller Dashboard</x-slot>

    <div class="space-y-6">
            {{-- Welcome Card --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium">Welcome, {{ auth()->user()->name }}</h3>
                    <p class="mt-1 text-sm text-gray-600">Role: Reseller | Billing: {{ ucfirst(auth()->user()->billing_type) }}</p>
                </div>
            </div>

            {{-- Account Cards --}}
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                    <dt class="truncate text-sm font-medium text-gray-500">Balance</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">${{ number_format(auth()->user()->balance, 2) }}</dd>
                </div>
                <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                    <dt class="truncate text-sm font-medium text-gray-500">Max Channels</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ auth()->user()->max_channels }}</dd>
                </div>
                <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                    <dt class="truncate text-sm font-medium text-gray-500">KYC Status</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight {{ auth()->user()->kyc_status === 'approved' ? 'text-green-600' : 'text-yellow-600' }}">
                        {{ ucfirst(auth()->user()->kyc_status) }}
                    </dd>
                </div>
                <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                    <dt class="truncate text-sm font-medium text-gray-500">Total Clients</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($entityCounts['clients'] ?? 0) }}</dd>
                </div>
            </div>

            {{-- Call Stats (Last 7 Days) --}}
            @php
                $totalDur = $weekStats['total_duration'];
                $totalBill = $weekStats['total_billable'];
            @endphp
            <div>
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

            {{-- Entity Summary --}}
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                    <dt class="truncate text-sm font-medium text-gray-500">SIP Accounts</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($entityCounts['sip_accounts'] ?? 0) }}</dd>
                </div>
                <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                    <dt class="truncate text-sm font-medium text-gray-500">DIDs</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($entityCounts['dids'] ?? 0) }}</dd>
                </div>
            </div>

            {{-- Recent Calls --}}
            <div>
                <h2 class="text-base font-semibold text-gray-900 mb-3">Recent Calls (Today)</h2>
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
                                    <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $call->call_start?->format('H:i:s') }}</td>
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
    </div>
</x-reseller-layout>
