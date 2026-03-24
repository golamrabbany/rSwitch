<x-client-layout>
    <x-slot name="header">Call Records</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Call Records</h2>
            <p class="page-subtitle">View your call history and usage</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('client.cdr.export', request()->query()) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export
            </a>
        </div>
    </div>

    {{-- Stats --}}
    @php
        $asr = $stats['total_calls'] > 0 ? round(($stats['answered_calls'] / $stats['total_calls']) * 100, 1) : 0;
        $totalDur = (int) $stats['total_duration'];
        $totalBill = (int) $stats['total_billable'];
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Total Calls</p>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_calls']) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Answered</p>
            <p class="text-2xl font-bold text-emerald-600">{{ number_format($stats['answered_calls']) }}</p>
            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold mt-1 {{ $asr >= 50 ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $asr }}% ASR</span>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Duration</p>
            <p class="text-2xl font-bold text-gray-900">{{ sprintf('%d:%02d', intdiv($totalDur, 3600), intdiv($totalDur % 3600, 60)) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Billable</p>
            <p class="text-2xl font-bold text-gray-900">{{ sprintf('%d:%02d', intdiv($totalBill, 3600), intdiv($totalBill % 3600, 60)) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Total Cost</p>
            <p class="text-2xl font-bold text-gray-900">{{ format_currency($stats['total_cost']) }}</p>
        </div>
    </div>

    {{-- Filter --}}
    <div class="filter-card">
        <form method="GET" class="filter-row flex-wrap">
            <div class="flex-1 min-w-0">
                <input type="date" name="date_from" value="{{ $dateFrom->format('Y-m-d') }}" class="filter-input">
            </div>
            <div class="flex-1 min-w-0">
                <input type="date" name="date_to" value="{{ $dateTo->format('Y-m-d') }}" class="filter-input">
            </div>
            <select name="disposition" class="filter-select">
                <option value="">All Dispositions</option>
                @foreach (['ANSWERED', 'NO ANSWER', 'BUSY', 'FAILED', 'CANCEL'] as $d)
                    <option value="{{ $d }}" {{ request('disposition') === $d ? 'selected' : '' }}>{{ $d }}</option>
                @endforeach
            </select>
            <div class="filter-search-box flex-1 min-w-0">
                <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search caller/callee..." class="filter-input">
            </div>
            <button type="submit" class="btn-search">Filter</button>
            @if(request()->hasAny(['disposition', 'search']))
                <a href="{{ route('client.cdr.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="data-table-container">
        @if($records->total() > 0)
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
                <span class="text-sm text-gray-600">
                    Showing <span class="font-semibold">{{ $records->firstItem() }}–{{ $records->lastItem() }}</span> of <span class="font-semibold">{{ number_format($records->total()) }}</span> records
                </span>
            </div>
        @endif
        <table class="data-table data-table-compact">
            <thead>
                <tr>
                    <th>Date/Time</th>
                    <th>Caller</th>
                    <th>Callee</th>
                    <th style="text-align: center">Duration</th>
                    <th style="text-align: right">Rate/Min</th>
                    <th style="text-align: right">Cost</th>
                    <th>Disposition</th>
                    <th style="text-align: center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $r)
                    <tr>
                        <td class="whitespace-nowrap">
                            <div class="text-gray-900">{{ $r->call_start?->format('H:i:s') }}</div>
                            <div class="text-xs text-gray-400">{{ $r->call_start?->format('M d, Y') }}</div>
                        </td>
                        <td class="font-mono text-gray-900">{{ Str::limit($r->caller, 15) }}</td>
                        <td class="font-mono text-gray-900">{{ Str::limit($r->callee, 15) }}</td>
                        <td style="text-align: center" class="tabular-nums text-gray-900 font-medium">
                            {{ sprintf('%d:%02d', intdiv($r->billable_duration, 60), $r->billable_duration % 60) }}
                        </td>
                        <td style="text-align: right" class="tabular-nums font-mono text-gray-600">
                            {{ $r->rate_per_minute > 0 ? format_currency($r->rate_per_minute, 4) : '—' }}
                        </td>
                        <td style="text-align: right" class="tabular-nums font-mono font-medium text-gray-900">
                            {{ (float) $r->total_cost > 0 ? format_currency($r->total_cost) : '—' }}
                        </td>
                        <td>
                            @switch($r->disposition)
                                @case('ANSWERED') <span class="badge badge-success">Answered</span> @break
                                @case('NO ANSWER') <span class="badge badge-warning">No Answer</span> @break
                                @case('BUSY') <span class="badge badge-warning">Busy</span> @break
                                @case('FAILED') <span class="badge badge-danger">Failed</span> @break
                                @default <span class="badge badge-gray">{{ $r->disposition }}</span>
                            @endswitch
                        </td>
                        <td>
                            <div class="flex items-center justify-center">
                                <a href="{{ route('client.cdr.show', ['uuid' => $r->uuid, 'date' => $r->call_start?->format('Y-m-d')]) }}" class="action-icon" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-12"><div class="empty-state"><p class="empty-text">No call records found for this date range</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($records->hasPages())
        <div class="mt-4 flex justify-end">
            {{ $records->withQueryString()->onEachSide(1)->links('pagination::simple-tailwind') }}
        </div>
    @endif
</x-client-layout>
