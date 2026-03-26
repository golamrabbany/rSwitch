<x-admin-layout>
    <x-slot name="header">Transit Calls</x-slot>

    @php
        $answeredCount = $answeredCalls ?? 0;
        $totalDuration = $totalMinutes * 60;
        $acdSeconds = ($answeredCount > 0) ? round($totalDuration / $answeredCount) : 0;
        $acdMin = intdiv($acdSeconds, 60); $acdSec = $acdSeconds % 60;
    @endphp

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">Transit Calls</h2>
            <p class="page-subtitle">Trunk-to-trunk transit calls passing through the platform</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.operational-reports.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="mb-5" style="display:grid; grid-template-columns: repeat(5, 1fr); gap:1rem;">
        <div class="stat-card">
            <div class="stat-icon bg-indigo-100">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </div>
            <div class="stat-content">
                <p class="stat-value">{{ number_format($totalCalls) }}</p>
                <p class="stat-label">Total Calls</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-emerald-100">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="stat-content">
                <p class="stat-value text-emerald-600">{{ number_format($answeredCalls) }}</p>
                <p class="stat-label">Answered</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon {{ $asr >= 50 ? 'bg-emerald-100' : 'bg-amber-100' }}">
                <svg class="w-5 h-5 {{ $asr >= 50 ? 'text-emerald-600' : 'text-amber-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div class="stat-content">
                <p class="stat-value {{ $asr >= 50 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $asr }}%</p>
                <p class="stat-label">ASR</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-blue-100">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <p class="stat-value text-blue-600">{{ $acdMin }}:{{ str_pad($acdSec, 2, '0', STR_PAD_LEFT) }}</p>
                <p class="stat-label">ACD</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-indigo-100">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <p class="stat-value">{{ number_format($totalMinutes, 0) }}</p>
                <p class="stat-label">Minutes</p>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="filter-card mb-3">
        <form method="GET" class="filter-row">
            <div class="filter-search-box">
                <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search caller/callee..." class="filter-input">
            </div>
            <input type="date" name="date_from" value="{{ request('date_from', now()->format('Y-m-d')) }}" class="filter-select">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="filter-select">

            <select name="disposition" class="filter-select">
                <option value="">All Dispositions</option>
                @foreach (['ANSWERED', 'NO ANSWER', 'BUSY', 'FAILED'] as $d)
                    <option value="{{ $d }}" {{ request('disposition') === $d ? 'selected' : '' }}>{{ $d }}</option>
                @endforeach
            </select>

            <select name="incoming_trunk_id" class="filter-select">
                <option value="">All Incoming Trunks</option>
                @foreach($incomingTrunks as $trunk)
                    <option value="{{ $trunk->id }}" {{ request('incoming_trunk_id') == $trunk->id ? 'selected' : '' }}>{{ $trunk->name }}</option>
                @endforeach
            </select>

            <select name="outgoing_trunk_id" class="filter-select">
                <option value="">All Outgoing Trunks</option>
                @foreach($outgoingTrunks as $trunk)
                    <option value="{{ $trunk->id }}" {{ request('outgoing_trunk_id') == $trunk->id ? 'selected' : '' }}>{{ $trunk->name }}</option>
                @endforeach
            </select>

            <button type="submit" class="btn-search-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Search
            </button>
            @if(request()->hasAny(['search', 'disposition', 'incoming_trunk_id', 'outgoing_trunk_id', 'date_to']))
                <a href="{{ route('admin.operational-reports.transit') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    <x-cdr-archive-banner />

    {{-- Calls Table --}}
    @if($calls->count() > 0)
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    Total : {{ number_format($calls->total()) }} &middot; Showing {{ $calls->firstItem() }}–{{ $calls->lastItem() }}
                </span>
            </div>

            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" width="40">SL</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Caller</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Callee</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Call Time</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Duration</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Incoming Trunk</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Outgoing Trunk</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($calls as $call)
                        <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-indigo-50/50 transition-all border-b border-gray-100 group">
                            <td class="px-3 py-2 text-gray-400 tabular-nums text-center">{{ $calls->firstItem() + $loop->index }}</td>
                            <td class="px-3 py-2">
                                <span class="font-medium text-gray-900 tabular-nums">{{ $call->caller }}</span>
                            </td>
                            <td class="px-3 py-2">
                                <span class="tabular-nums text-gray-900">{{ $call->callee }}</span>
                            </td>
                            <td class="px-3 py-2">
                                <span class="text-gray-800">{{ $call->call_start->format('M d, Y') }}</span>
                                <span class="block text-xs text-gray-400">{{ $call->call_start->format('H:i:s') }}</span>
                            </td>
                            <td class="px-3 py-2">
                                @if($call->duration > 0)
                                    <span class="font-medium text-gray-900">{{ gmdate('H:i:s', $call->duration) }}</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                @if($call->incomingTrunk)
                                    <a href="{{ route('admin.trunks.show', $call->incomingTrunk) }}" class="text-indigo-600 hover:text-indigo-500 font-medium">
                                        {{ Str::limit($call->incomingTrunk->name, 20) }}
                                    </a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                @if($call->outgoingTrunk)
                                    <a href="{{ route('admin.trunks.show', $call->outgoingTrunk) }}" class="text-indigo-600 hover:text-indigo-500 font-medium">
                                        {{ Str::limit($call->outgoingTrunk->name, 20) }}
                                    </a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                @switch($call->disposition)
                                    @case('ANSWERED')
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Answered</span>
                                        @break
                                    @case('NO ANSWER')
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>No Answer</span>
                                        @break
                                    @case('FAILED')
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Failed</span>
                                        @break
                                    @default
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-500"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>{{ $call->disposition ?? 'Unknown' }}</span>
                                @endswitch
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($calls->hasPages())
                <div class="mt-4 flex justify-end px-4 py-3">
                    {{ $calls->withQueryString()->onEachSide(1)->links('pagination::simple-tailwind') }}
                </div>
            @endif
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-200 py-16">
            <div class="text-center">
                <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-indigo-100 flex items-center justify-center">
                    <svg class="w-10 h-10 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-1">No Transit Calls Found</h3>
                <p class="text-gray-500 text-sm">Transit calls appear when trunk-to-trunk routing is active</p>
            </div>
        </div>
    @endif
</x-admin-layout>
