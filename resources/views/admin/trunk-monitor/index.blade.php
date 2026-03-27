<x-admin-layout>
    <x-slot name="header">Trunk Monitor</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">Trunk Monitor</h2>
            <p class="page-subtitle">Real-time trunk health and utilization</p>
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
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
        <div class="stat-card">
            <div class="stat-card-icon" style="background: linear-gradient(135deg, #6366f1, #4f46e5);">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                </svg>
            </div>
            <div class="stat-card-content">
                <span class="stat-card-value">{{ $totalTrunks }}</span>
                <span class="stat-card-label">Total Trunks</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="stat-card-content">
                <span class="stat-card-value">{{ $activeTrunks }}</span>
                <span class="stat-card-label">Active</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon" style="background: linear-gradient(135deg, {{ $downTrunks > 0 ? '#ef4444, #dc2626' : '#9ca3af, #6b7280' }});">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="stat-card-content">
                <span class="stat-card-value {{ $downTrunks > 0 ? 'text-red-600' : '' }}">{{ $downTrunks }}</span>
                <span class="stat-card-label">Down</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
            </div>
            <div class="stat-card-content">
                <span class="stat-card-value">{{ $totalActiveCalls }}</span>
                <span class="stat-card-label">Active Calls</span>
            </div>
        </div>
    </div>

    {{-- Trunks Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if(count($trunks) > 0)
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    {{ count($trunks) }} Trunks &middot; {{ $activeTrunks }} Active &middot; {{ $totalActiveCalls }} Calls
                </span>
            </div>
        @endif
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" width="40">SL</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Trunk</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Direction</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Health</th>
                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Calls</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Utilization</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Last Check</th>
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
                    <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-indigo-50/50 transition-all border-b border-gray-100 group">
                        <td class="px-3 py-2 text-gray-400 tabular-nums text-center">{{ $loop->iteration }}</td>
                        <td class="px-3 py-2">
                            <a href="{{ route('admin.trunks.show', $trunk) }}" class="font-semibold text-gray-800 group-hover:text-indigo-600 transition-colors">{{ $trunk->name }}</a>
                            <span class="block text-xs text-gray-400 font-mono">{{ $trunk->host }}:{{ $trunk->port ?? 5060 }} &middot; {{ $trunk->provider }}</span>
                        </td>
                        <td class="px-3 py-2">
                            @if($trunk->direction === 'outgoing')
                                <span class="badge badge-success">Outgoing</span>
                            @elseif($trunk->direction === 'incoming')
                                <span class="badge badge-info">Incoming</span>
                            @else
                                <span class="badge badge-purple">Both</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            @if($trunk->health_status === 'up')
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-600">
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                    </span>
                                    Up
                                </span>
                            @elseif($trunk->health_status === 'down')
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-red-600">
                                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                    Down{{ $trunk->health_fail_count > 0 ? " ({$trunk->health_fail_count}x)" : '' }}
                                </span>
                            @elseif($trunk->health_status === 'degraded')
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-600">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                    Degraded
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs text-gray-400">
                                    <span class="w-2 h-2 rounded-full bg-gray-300"></span>
                                    Unknown
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center">
                            @if($totalCalls > 0)
                                <span class="font-bold text-gray-900 tabular-nums">{{ $totalCalls }}</span>
                                <span class="block text-xs text-gray-400 tabular-nums">
                                    @if($inCalls > 0)<span class="text-emerald-500">{{ $inCalls }}in</span>@endif
                                    @if($inCalls > 0 && $outCalls > 0) / @endif
                                    @if($outCalls > 0)<span class="text-indigo-500">{{ $outCalls }}out</span>@endif
                                </span>
                            @else
                                <span class="text-gray-300 tabular-nums">0</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-2">
                                <div class="w-16 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all {{ $utilization >= 80 ? 'bg-red-500' : ($utilization >= 50 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ min($utilization, 100) }}%"></div>
                                </div>
                                <span class="text-xs tabular-nums {{ $utilization >= 80 ? 'text-red-600 font-semibold' : ($utilization >= 50 ? 'text-amber-600' : 'text-gray-500') }}">{{ $utilization }}%</span>
                            </div>
                            <span class="text-xs text-gray-400 tabular-nums">{{ $totalCalls }}/{{ $trunk->max_channels }}</span>
                        </td>
                        <td class="px-3 py-2">
                            @if($trunk->status === 'active')
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Active</span>
                            @elseif($trunk->status === 'auto_disabled')
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Auto-disabled</span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Disabled</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            @if($trunk->health_last_checked_at)
                                <span class="text-xs text-gray-600">{{ $trunk->health_last_checked_at->diffForHumans() }}</span>
                            @else
                                <span class="text-xs text-gray-300">Never</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center">
                            <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                            </svg>
                            <p class="text-sm text-gray-400">No trunks configured</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin-layout>
