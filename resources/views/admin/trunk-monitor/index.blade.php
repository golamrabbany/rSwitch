<x-admin-layout>
    <x-slot name="header">Trunk Monitor</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-400 to-indigo-500 flex items-center justify-center shadow-lg shadow-indigo-200">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Trunk Monitor</h2>
                <p class="page-subtitle">Real-time trunk health and utilization</p>
            </div>
        </div>
        <div class="page-actions">
            <form method="POST" action="{{ route('admin.trunk-monitor.refresh') }}" class="inline">
                @csrf
                <button type="submit" class="btn-action-primary-admin">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Refresh Health
                </button>
            </form>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-indigo-100 text-xs font-medium uppercase tracking-wide">Total Trunks</p>
                    <p class="text-3xl font-bold mt-1">{{ $totalTrunks }}</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium uppercase tracking-wide">Active</p>
                    <p class="text-3xl font-bold text-emerald-600 mt-1">{{ $activeTrunks }}</p>
                </div>
                <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium uppercase tracking-wide">Down</p>
                    <p class="text-3xl font-bold {{ $downTrunks > 0 ? 'text-red-500' : 'text-gray-400' }} mt-1">{{ $downTrunks }}</p>
                </div>
                <div class="w-12 h-12 {{ $downTrunks > 0 ? 'bg-red-100' : 'bg-gray-100' }} rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 {{ $downTrunks > 0 ? 'text-red-500' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium uppercase tracking-wide">Active Calls</p>
                    <p class="text-3xl font-bold text-indigo-600 mt-1">{{ $totalActiveCalls }}</p>
                </div>
                <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Trunks Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                Trunk Monitor Total : {{ $totalTrunks }}
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" width="40">SL</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Trunk</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Direction</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Health</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Active Calls</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Utilization</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Last Checked</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($trunks as $trunk)
                        @php
                            $inCalls = $activeInbound[$trunk->id] ?? 0;
                            $outCalls = $activeOutbound[$trunk->id] ?? 0;
                            $totalCalls = $inCalls + $outCalls;
                            $utilization = $trunk->max_channels > 0 ? round(($totalCalls / $trunk->max_channels) * 100, 1) : 0;
                        @endphp
                        <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-indigo-50/50 transition-all border-b border-gray-100 group {{ $trunk->health_status === 'down' ? '!bg-red-50/50' : '' }}">
                            <td class="px-3 py-2 text-gray-400 tabular-nums text-center">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2">
                                <a href="{{ route('admin.trunks.show', $trunk) }}" class="text-indigo-600 hover:text-indigo-500 font-medium">{{ $trunk->name }}</a>
                                <div class="text-xs text-gray-400 font-mono">{{ $trunk->host }}:{{ $trunk->port ?? 5060 }}</div>
                                @if($trunk->provider)
                                    <div class="text-xs text-gray-400">{{ $trunk->provider }}</div>
                                @endif
                            </td>

                            <td class="px-3 py-2">
                                @switch($trunk->direction)
                                    @case('incoming')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Incoming</span>
                                        @break
                                    @case('outgoing')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">Outgoing</span>
                                        @break
                                    @case('both')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Both</span>
                                        @break
                                @endswitch
                            </td>

                            <td class="px-3 py-2">
                                @switch($trunk->status)
                                    @case('active')
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Active</span>
                                        @break
                                    @case('disabled')
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-500"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>Disabled</span>
                                        @break
                                    @case('auto_disabled')
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Auto-Disabled</span>
                                        @break
                                @endswitch
                            </td>

                            <td class="px-3 py-2">
                                @switch($trunk->health_status)
                                    @case('up')
                                        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-600">
                                            <span class="relative flex h-2.5 w-2.5">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                                            </span>
                                            Up
                                        </span>
                                        @break
                                    @case('down')
                                        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-red-600">
                                            <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                                            Down
                                            @if($trunk->health_fail_count > 0)
                                                <span class="text-red-400">({{ $trunk->health_fail_count }}x)</span>
                                            @endif
                                        </span>
                                        @break
                                    @case('degraded')
                                        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-600">
                                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                            Degraded
                                        </span>
                                        @break
                                    @default
                                        <span class="inline-flex items-center gap-1.5 text-xs text-gray-400">
                                            <span class="w-2.5 h-2.5 rounded-full bg-gray-300"></span>
                                            Unknown
                                        </span>
                                @endswitch
                            </td>

                            <td class="px-3 py-2">
                                @if($totalCalls > 0)
                                    <span class="text-sm font-semibold text-gray-900">{{ $totalCalls }}</span>
                                    <div class="text-xs text-gray-400">
                                        @if($inCalls > 0)<span class="text-emerald-500">{{ $inCalls }} in</span>@endif
                                        @if($inCalls > 0 && $outCalls > 0) / @endif
                                        @if($outCalls > 0)<span class="text-purple-500">{{ $outCalls }} out</span>@endif
                                    </div>
                                @else
                                    <span class="text-gray-300">0</span>
                                @endif
                            </td>

                            <td class="px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden" style="max-width: 80px;">
                                        <div class="h-full rounded-full {{ $utilization >= 80 ? 'bg-red-500' : ($utilization >= 50 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ min($utilization, 100) }}%"></div>
                                    </div>
                                    <span class="text-xs {{ $utilization >= 80 ? 'text-red-600' : ($utilization >= 50 ? 'text-amber-600' : 'text-gray-500') }} font-medium">{{ $utilization }}%</span>
                                </div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ $totalCalls }}/{{ $trunk->max_channels }} ch</div>
                            </td>

                            <td class="px-3 py-2">
                                @if($trunk->health_last_checked_at)
                                    <div class="text-xs text-gray-600">{{ $trunk->health_last_checked_at->diffForHumans() }}</div>
                                    <div class="text-xs text-gray-400">{{ $trunk->health_last_checked_at->format('H:i:s') }}</div>
                                @else
                                    <span class="text-xs text-gray-300">Never</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-12 text-center">
                                <div class="text-gray-500">No trunks configured</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
