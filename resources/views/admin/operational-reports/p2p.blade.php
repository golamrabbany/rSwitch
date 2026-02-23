<x-admin-layout>
    <x-slot name="header">P2P Calls</x-slot>

    @php
        $asr = $stats['total_calls'] > 0 ? ($stats['answered_calls'] / $stats['total_calls']) * 100 : 0;
        $totalDur = (int) $stats['total_duration'];
        $totalBill = (int) $stats['total_billsec'];
    @endphp

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">P2P Calls</h2>
            <p class="page-subtitle">Internal SIP-to-SIP calls between accounts</p>
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

    {{-- Stats Grid --}}
    <div class="cdr-stats-grid !lg:grid-cols-4" style="grid-template-columns: repeat(4, minmax(0, 1fr))">
        <div class="cdr-stat-card cdr-stat-total">
            <div class="cdr-stat-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
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
    </div>

    {{-- Filter Card --}}
    <div class="filter-card" x-data="{
        userSearch: '{{ $users->firstWhere('id', request('user_id'))?->name ?? '' }}',
        userId: '{{ request('user_id') }}',
        userOpen: false,
        users: {{ $users->toJson() }},
        get filteredUsers() {
            if (!this.userSearch) return this.users.slice(0, 20);
            const s = this.userSearch.toLowerCase();
            return this.users.filter(u => u.name.toLowerCase().includes(s) || (u.email && u.email.toLowerCase().includes(s))).slice(0, 20);
        },
        selectUser(user) {
            this.userSearch = user.name;
            this.userId = user.id;
            this.userOpen = false;
        },
        clearUser() {
            this.userSearch = '';
            this.userId = '';
            this.$refs.userInput.focus();
        }
    }">
        <form method="GET" action="{{ route('admin.operational-reports.p2p') }}" class="flex flex-wrap items-end gap-3">
            <div class="cdr-filter-item">
                <label for="date_from" class="cdr-filter-label">Date From</label>
                <input type="date" id="date_from" name="date_from" value="{{ $dateFrom->format('Y-m-d') }}" required class="filter-date">
            </div>
            <div class="cdr-filter-item">
                <label for="date_to" class="cdr-filter-label">Date To</label>
                <input type="date" id="date_to" name="date_to" value="{{ $dateTo->format('Y-m-d') }}" required class="filter-date">
            </div>
            <div class="cdr-filter-item relative">
                <label class="cdr-filter-label">User</label>
                <input type="hidden" name="user_id" :value="userId">
                <div class="relative">
                    <input type="text"
                           x-ref="userInput"
                           x-model="userSearch"
                           @focus="userOpen = true"
                           @click="userOpen = true"
                           @input="userOpen = true"
                           @keydown.escape="userOpen = false"
                           @keydown.tab="userOpen = false"
                           class="filter-input pr-8"
                           placeholder="Search user..."
                           autocomplete="off">
                    <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1">
                        <button type="button" x-show="userSearch" x-cloak @click="clearUser()" class="p-0.5 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                {{-- Dropdown --}}
                <div x-show="userOpen && filteredUsers.length > 0"
                     x-cloak
                     @click.outside="userOpen = false"
                     class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto">
                    <template x-for="user in filteredUsers" :key="user.id">
                        <div @click="selectUser(user)"
                             class="px-3 py-2 cursor-pointer hover:bg-indigo-50 flex items-center justify-between"
                             :class="{ 'bg-indigo-50': userId == user.id }">
                            <div>
                                <span class="text-sm font-medium text-gray-900" x-text="user.name"></span>
                                <span class="text-xs text-gray-400 ml-1" x-text="'(' + user.role.charAt(0).toUpperCase() + user.role.slice(1) + ')'"></span>
                            </div>
                        </div>
                    </template>
                </div>
                <div x-show="userOpen && userSearch && filteredUsers.length === 0"
                     x-cloak
                     @click.outside="userOpen = false"
                     class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg p-3 text-center text-sm text-gray-500">
                    No users found
                </div>
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
                <label for="search" class="cdr-filter-label">Caller / Callee</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Number prefix..." class="filter-input">
            </div>
            <button type="submit" class="btn-search-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filter
            </button>
            @if(request()->hasAny(['disposition', 'user_id', 'search']))
                <a href="{{ route('admin.operational-reports.p2p') }}" class="btn-clear">Clear Filters</a>
            @endif
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
                    <th class="text-center">Duration</th>
                    <th class="text-center">Billsec</th>
                    <th>Disposition</th>
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
                        <td class="text-center font-mono tabular-nums whitespace-nowrap">
                            {{ sprintf('%02d:%02d', intdiv($record->duration, 60), $record->duration % 60) }}
                        </td>
                        <td class="text-center font-mono tabular-nums whitespace-nowrap">
                            {{ sprintf('%02d:%02d', intdiv($record->billsec, 60), $record->billsec % 60) }}
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
                        <td colspan="7" class="text-center py-12">
                            <div class="empty-state">
                                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                <p class="empty-text">No P2P call records found for this date range</p>
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
