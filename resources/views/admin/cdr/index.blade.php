<x-admin-layout>
    <x-slot name="header">CDR / Call Records</x-slot>

    @php
        $asr = $stats['total_calls'] > 0 ? ($stats['answered_calls'] / $stats['total_calls']) * 100 : 0;
        $totalDur = (int) $stats['total_duration'];
        $totalBill = (int) $stats['total_billable'];
    @endphp

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">Call Records</h2>
            <p class="page-subtitle">View and analyze call detail records</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.cdr.export', request()->query()) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export CSV
            </a>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="cdr-stats-grid">
        <div class="cdr-stat-card cdr-stat-total">
            <div class="cdr-stat-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
            </div>
            <div class="cdr-stat-content">
                <span class="cdr-stat-value">{{ number_format($stats['total_calls']) }}</span>
                <span class="cdr-stat-label">Total Calls</span>
            </div>
        </div>

        <div class="cdr-stat-card cdr-stat-answered">
            <div class="cdr-stat-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="cdr-stat-content">
                <span class="cdr-stat-value">{{ number_format($stats['answered_calls']) }}</span>
                <span class="cdr-stat-label">Answered ({{ number_format($asr, 1) }}% ASR)</span>
            </div>
        </div>

        <div class="cdr-stat-card cdr-stat-duration">
            <div class="cdr-stat-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="cdr-stat-content">
                <span class="cdr-stat-value">{{ sprintf('%d:%02d:%02d', intdiv($totalDur, 3600), intdiv($totalDur % 3600, 60), $totalDur % 60) }}</span>
                <span class="cdr-stat-label">Total Duration</span>
            </div>
        </div>

        <div class="cdr-stat-card cdr-stat-billable">
            <div class="cdr-stat-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="cdr-stat-content">
                <span class="cdr-stat-value">{{ sprintf('%d:%02d:%02d', intdiv($totalBill, 3600), intdiv($totalBill % 3600, 60), $totalBill % 60) }}</span>
                <span class="cdr-stat-label">Billable Duration</span>
            </div>
        </div>

        <div class="cdr-stat-card cdr-stat-cost">
            <div class="cdr-stat-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="cdr-stat-content">
                <span class="cdr-stat-value">{{ format_currency($stats['total_cost']) }}</span>
                <span class="cdr-stat-label">Total Cost</span>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="filter-card">
        <form method="GET" action="{{ route('admin.cdr.index') }}">
            <div class="cdr-filter-grid">
                <div class="cdr-filter-item">
                    <label for="date_from" class="cdr-filter-label">Date From</label>
                    <input type="date" id="date_from" name="date_from" value="{{ $dateFrom->format('Y-m-d') }}" required class="filter-date">
                </div>
                <div class="cdr-filter-item">
                    <label for="date_to" class="cdr-filter-label">Date To</label>
                    <input type="date" id="date_to" name="date_to" value="{{ $dateTo->format('Y-m-d') }}" required class="filter-date">
                </div>
                <div class="cdr-filter-item">
                    <label for="user_id" class="cdr-filter-label">User</label>
                    <select id="user_id" name="user_id" class="filter-select">
                        <option value="">All Users</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ ucfirst($user->role) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="cdr-filter-item">
                    <label for="disposition" class="cdr-filter-label">Disposition</label>
                    <select id="disposition" name="disposition" class="filter-select">
                        <option value="">All</option>
                        @foreach (['ANSWERED', 'NO ANSWER', 'BUSY', 'FAILED', 'CANCEL'] as $d)
                            <option value="{{ $d }}" {{ request('disposition') === $d ? 'selected' : '' }}>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="cdr-filter-item">
                    <label for="call_flow" class="cdr-filter-label">Call Flow</label>
                    <select id="call_flow" name="call_flow" class="filter-select">
                        <option value="">All Flows</option>
                        @foreach (['sip_to_trunk' => 'SIP → Trunk', 'sip_to_sip' => 'SIP → SIP', 'trunk_to_sip' => 'Trunk → SIP', 'trunk_to_trunk' => 'Trunk → Trunk'] as $val => $label)
                            <option value="{{ $val }}" {{ request('call_flow') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="cdr-filter-item">
                    <label for="call_type" class="cdr-filter-label">Call Type</label>
                    <select id="call_type" name="call_type" class="filter-select">
                        <option value="">All Types</option>
                        <option value="regular" {{ request('call_type') === 'regular' ? 'selected' : '' }}>Regular</option>
                        <option value="broadcast" {{ request('call_type') === 'broadcast' ? 'selected' : '' }}>Broadcast</option>
                    </select>
                </div>
                <div class="cdr-filter-item">
                    <label for="status" class="cdr-filter-label">Status</label>
                    <select id="status" name="status" class="filter-select">
                        <option value="">All</option>
                        @foreach (['rated', 'in_progress', 'failed', 'unbillable'] as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="cdr-filter-item">
                    <label for="trunk_id" class="cdr-filter-label">Trunk</label>
                    <select id="trunk_id" name="trunk_id" class="filter-select">
                        <option value="">All Trunks</option>
                        @foreach ($trunks as $trunk)
                            <option value="{{ $trunk->id }}" {{ request('trunk_id') == $trunk->id ? 'selected' : '' }}>
                                {{ $trunk->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="cdr-filter-item">
                    <label for="search" class="cdr-filter-label">Caller / Callee</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Number prefix..." class="filter-input">
                </div>
            </div>
            <div class="flex items-center gap-3 mt-4">
                <button type="submit" class="btn-search-admin">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filter
                </button>
                @if(request()->hasAny(['disposition', 'call_flow', 'call_type', 'status', 'trunk_id', 'user_id', 'search']))
                    <a href="{{ route('admin.cdr.index') }}" class="btn-clear">Clear Filters</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Data Table --}}
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date / Time</th>
                    <th>Caller</th>
                    <th>Callee</th>
                    <th class="text-right">Duration</th>
                    <th class="text-right">Billsec</th>
                    <th class="text-right">Cost</th>
                    <th>Disposition</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        <td>
                            <div class="cdr-date">
                                <span class="cdr-date-main">{{ $record->call_start?->format('M d, Y') }}</span>
                                <span class="cdr-date-time">{{ $record->call_start?->format('H:i:s') }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="cdr-party">
                                <span class="cdr-party-number">{{ $record->caller }}</span>
                                @if ($record->user)
                                    <a href="{{ route('admin.users.show', $record->user) }}" class="cdr-party-name">{{ $record->user->name }}</a>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="cdr-party-number">{{ $record->callee }}</span>
                        </td>
                        <td class="text-right tabular-nums">
                            {{ sprintf('%d:%02d', intdiv($record->duration, 60), $record->duration % 60) }}
                        </td>
                        <td class="text-right tabular-nums">
                            {{ sprintf('%d:%02d', intdiv($record->billsec, 60), $record->billsec % 60) }}
                        </td>
                        <td class="text-right tabular-nums font-medium">
                            {{ format_currency($record->total_cost, 4) }}
                        </td>
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
                                @case('CANCEL')
                                    <span class="badge badge-gray">CANCEL</span>
                                    @break
                                @default
                                    <span class="text-gray-400">—</span>
                            @endswitch
                        </td>
                        <td>
                            @if($record->call_type === 'broadcast')
                                <span class="badge badge-info">Broadcast</span>
                            @else
                                <span class="badge badge-gray">Regular</span>
                            @endif
                        </td>
                        <td>
                            @switch($record->status)
                                @case('rated')
                                    <span class="badge badge-success">Rated</span>
                                    @break
                                @case('in_progress')
                                    <span class="badge badge-warning">In Progress</span>
                                    @break
                                @case('failed')
                                    <span class="badge badge-danger">Failed</span>
                                    @break
                                @case('unbillable')
                                    <span class="badge badge-gray">Unbillable</span>
                                    @break
                            @endswitch
                        </td>
                        <td class="text-center whitespace-nowrap">
                            <a href="{{ route('admin.cdr.show', ['uuid' => $record->uuid, 'date' => $record->call_start?->format('Y-m-d')]) }}" class="action-icon" title="View Details">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-12">
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

    @if ($records->hasPages())
        <div class="mt-6">
            {{ $records->withQueryString()->links() }}
        </div>
    @endif
</x-admin-layout>
