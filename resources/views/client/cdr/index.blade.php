<x-client-layout>
    <x-slot name="header">CDR / Reports</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">Call Records</h2>
            <p class="page-subtitle">View your call history and usage</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('client.cdr.export', request()->query()) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export CSV
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    @php
        $asr = $stats['total_calls'] > 0 ? ($stats['answered_calls'] / $stats['total_calls']) * 100 : 0;
        $totalDur = (int) $stats['total_duration'];
        $totalBill = (int) $stats['total_billable'];
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="stat-card">
            <div class="stat-icon bg-sky-100 text-sky-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
            </div>
            <div class="stat-content">
                <span class="stat-value">{{ number_format($stats['total_calls']) }}</span>
                <span class="stat-label">Total Calls</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-emerald-100 text-emerald-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="stat-content">
                <span class="stat-value">{{ number_format($stats['answered_calls']) }}</span>
                <span class="stat-label">Answered ({{ number_format($asr, 1) }}%)</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-purple-100 text-purple-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <span class="stat-value">{{ sprintf('%d:%02d:%02d', intdiv($totalDur, 3600), intdiv($totalDur % 3600, 60), $totalDur % 60) }}</span>
                <span class="stat-label">Total Duration</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-amber-100 text-amber-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <span class="stat-value">{{ sprintf('%d:%02d:%02d', intdiv($totalBill, 3600), intdiv($totalBill % 3600, 60), $totalBill % 60) }}</span>
                <span class="stat-label">Billable Duration</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-rose-100 text-rose-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <span class="stat-value">{{ format_currency($stats['total_cost']) }}</span>
                <span class="stat-label">Total Cost</span>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="filter-card">
        <form method="GET" action="{{ route('client.cdr.index') }}" class="filter-row flex-wrap">
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600 whitespace-nowrap">From:</label>
                <input type="date" name="date_from" value="{{ $dateFrom->format('Y-m-d') }}" required class="filter-date">
            </div>

            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600 whitespace-nowrap">To:</label>
                <input type="date" name="date_to" value="{{ $dateTo->format('Y-m-d') }}" required class="filter-date">
            </div>

            <select name="disposition" class="filter-select">
                <option value="">All Dispositions</option>
                @foreach (['ANSWERED', 'NO ANSWER', 'BUSY', 'FAILED', 'CANCEL'] as $d)
                    <option value="{{ $d }}" {{ request('disposition') === $d ? 'selected' : '' }}>{{ $d }}</option>
                @endforeach
            </select>

            <div class="filter-search-box">
                <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Caller/callee prefix..." class="filter-input">
            </div>

            <button type="submit" class="btn-search-client">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filter
            </button>

            @if(request()->hasAny(['disposition', 'search']))
                <a href="{{ route('client.cdr.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date / Time</th>
                    <th>Caller</th>
                    <th>Callee</th>
                    <th class="text-right">Duration</th>
                    <th class="text-right">Cost</th>
                    <th>Disposition</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        <td class="whitespace-nowrap">{{ $record->call_start?->format('M d H:i:s') }}</td>
                        <td class="font-mono">{{ $record->caller }}</td>
                        <td class="font-mono">{{ $record->callee }}</td>
                        <td class="text-right tabular-nums">
                            {{ sprintf('%d:%02d', intdiv($record->duration, 60), $record->duration % 60) }}
                        </td>
                        <td class="text-right tabular-nums">{{ format_currency($record->total_cost, 4) }}</td>
                        <td>
                            @switch($record->disposition)
                                @case('ANSWERED')
                                    <span class="badge badge-success">ANSWERED</span>
                                    @break
                                @case('NO ANSWER')
                                    <span class="badge badge-warning">NO ANSWER</span>
                                    @break
                                @case('BUSY')
                                    <span class="badge badge-warning">BUSY</span>
                                    @break
                                @case('FAILED')
                                    <span class="badge badge-danger">FAILED</span>
                                    @break
                                @default
                                    <span class="badge badge-gray">{{ $record->disposition }}</span>
                            @endswitch
                        </td>
                        <td class="text-center">
                            <a href="{{ route('client.cdr.show', ['uuid' => $record->uuid, 'date' => $record->call_start?->format('Y-m-d')]) }}" class="action-icon" title="View">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-12">
                            <div class="empty-state">
                                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                <p class="empty-text">No call records found for this date range</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($records->hasPages())
        <div class="mt-6">
            {{ $records->withQueryString()->links() }}
        </div>
    @endif
</x-client-layout>
