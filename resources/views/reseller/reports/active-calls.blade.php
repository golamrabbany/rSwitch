<x-reseller-layout>
    <x-slot name="header">Active Calls</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Active Calls</h2>
            <p class="page-subtitle">Currently ongoing calls — {{ $calls->count() }} active</p>
        </div>
        <div class="page-actions">
            <button onclick="location.reload()" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Refresh
            </button>
        </div>
    </div>

    {{-- Search --}}
    <div class="filter-card">
        <form method="GET" class="filter-row flex-wrap">
            <div class="filter-search-box">
                <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search caller or callee..." class="filter-input">
            </div>
            <button type="submit" class="btn-search-reseller">Search</button>
            @if(request('search'))
                <a href="{{ route('reseller.reports.active-calls') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Caller</th>
                    <th>Callee</th>
                    <th>Direction</th>
                    <th>Started</th>
                    <th>Duration</th>
                    <th>Client</th>
                    <th>SIP Account</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($calls as $call)
                    <tr>
                        <td class="font-mono text-gray-900">{{ $call->caller }}</td>
                        <td class="font-mono text-gray-900">{{ $call->callee }}</td>
                        <td>
                            @if($call->call_flow === 'trunk_to_sip')
                                <span class="badge badge-blue">Inbound</span>
                            @elseif($call->call_flow === 'sip_to_trunk')
                                <span class="badge badge-purple">Outbound</span>
                            @else
                                <span class="badge badge-gray">{{ $call->call_flow }}</span>
                            @endif
                        </td>
                        <td class="text-gray-500">{{ $call->call_start?->format('H:i:s') }}</td>
                        <td>
                            <span class="inline-flex items-center gap-1.5 text-emerald-600 font-medium">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                                {{ $call->call_start?->diffForHumans(null, true) }}
                            </span>
                        </td>
                        <td class="text-gray-600">{{ $call->user?->name ?? '—' }}</td>
                        <td class="font-mono text-gray-500 text-xs">{{ $call->sipAccount?->username ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-12">
                            <div class="empty-state">
                                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <p class="empty-text">No active calls</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-reseller-layout>
