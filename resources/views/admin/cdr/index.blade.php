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

    {{-- Filter Card --}}
    <div class="filter-card mb-3">
        <form method="GET" action="{{ route('admin.cdr.index') }}">
            <div class="filter-row flex-wrap">
                <input type="date" name="date_from" value="{{ $dateFrom->format('Y-m-d') }}" required class="filter-select" title="Date From">
                <input type="date" name="date_to" value="{{ $dateTo->format('Y-m-d') }}" required class="filter-select" title="Date To">

                {{-- User auto-suggest --}}
                <div class="relative" x-data="{
                    open: false,
                    search: '{{ $users->firstWhere('id', request('user_id'))?->name ?? '' }}',
                    selectedId: '{{ request('user_id') }}',
                    users: {{ $users->toJson() }},
                    get filtered() {
                        if (!this.search) return this.users.slice(0, 20);
                        const s = this.search.toLowerCase();
                        return this.users.filter(u => u.name.toLowerCase().includes(s) || u.email.toLowerCase().includes(s)).slice(0, 20);
                    },
                    select(u) { this.search = u.name; this.selectedId = u.id; this.open = false; },
                    clear() { this.search = ''; this.selectedId = ''; }
                }" @click.outside="open = false" @keydown.escape="open = false">
                    <input type="hidden" name="user_id" :value="selectedId">
                    <div class="relative">
                        <input type="text" x-model="search"
                               @focus="open = true"
                               @input="open = true; selectedId = ''"
                               placeholder="Filter by user..."
                               class="filter-input pr-9"
                               :class="selectedId ? 'border-indigo-500 ring-1 ring-indigo-500' : ''"
                               autocomplete="off">
                        <button type="button" x-show="search" @click="clear()"
                                class="absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600 hover:bg-indigo-200 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div x-show="open && filtered.length > 0" x-transition x-cloak
                         class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto" style="min-width: 280px;">
                        <template x-for="u in filtered" :key="u.id">
                            <button type="button" @click="select(u)"
                                    class="w-full px-4 py-2 text-left hover:bg-indigo-50 flex items-center gap-3">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-medium flex-shrink-0"
                                     :class="u.role === 'reseller' ? 'bg-emerald-100 text-emerald-600' : 'bg-sky-100 text-sky-600'"
                                     x-text="u.name.charAt(0).toUpperCase()"></div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-medium text-gray-900 truncate" x-text="u.name"></div>
                                    <div class="text-xs text-gray-500 truncate" x-text="u.email"></div>
                                </div>
                                <span class="text-xs px-2 py-0.5 rounded-full flex-shrink-0"
                                      :class="u.role === 'reseller' ? 'bg-emerald-100 text-emerald-700' : 'bg-sky-100 text-sky-700'"
                                      x-text="u.role.charAt(0).toUpperCase() + u.role.slice(1)"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <select name="disposition" class="filter-select">
                    <option value="">All Dispositions</option>
                    @foreach (['ANSWERED', 'NO ANSWER', 'BUSY', 'FAILED', 'CANCEL'] as $d)
                        <option value="{{ $d }}" {{ request('disposition') === $d ? 'selected' : '' }}>{{ $d }}</option>
                    @endforeach
                </select>

                <select name="call_flow" class="filter-select">
                    <option value="">All Flows</option>
                    @foreach (['sip_to_trunk' => 'Outbound', 'trunk_to_sip' => 'Inbound', 'sip_to_sip' => 'P2P', 'trunk_to_trunk' => 'Transit'] as $val => $label)
                        <option value="{{ $val }}" {{ request('call_flow') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="status" class="filter-select">
                    <option value="">All Status</option>
                    @foreach (['rated', 'charged', 'in_progress', 'failed', 'unbillable'] as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                    @endforeach
                </select>

                <select name="trunk_id" class="filter-select">
                    <option value="">All Trunks</option>
                    @foreach ($trunks as $trunk)
                        <option value="{{ $trunk->id }}" {{ request('trunk_id') == $trunk->id ? 'selected' : '' }}>{{ $trunk->name }}</option>
                    @endforeach
                </select>

                <div class="filter-search-box">
                    <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Caller / Callee..." class="filter-input">
                </div>

                <button type="submit" class="btn-search-admin">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Search
                </button>

                @if(request()->hasAny(['disposition', 'call_flow', 'status', 'trunk_id', 'user_id', 'search']))
                    <a href="{{ route('admin.cdr.index') }}" class="btn-clear">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <x-cdr-archive-banner />

    {{-- Data Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        {{-- Summary Bar --}}
        @if($records->total() > 0)
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    Total : {{ number_format($records->total()) }} &middot; Showing {{ $records->firstItem() }}–{{ $records->lastItem() }}
                    &middot; Answered: {{ number_format($stats['answered_calls']) }} ({{ number_format($asr, 1) }}% ASR)
                    &middot; Duration: {{ sprintf('%d:%02d:%02d', intdiv($totalDur, 3600), intdiv($totalDur % 3600, 60), $totalDur % 60) }}
                    &middot; Cost: {{ format_currency($stats['total_cost']) }}
                </span>
            </div>
        @endif

        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" width="40">SL</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date / Time</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Caller</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Callee</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Ring</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Bill Dur.</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Cost</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Disposition</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $record)
                    <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-indigo-50/50 transition-all border-b border-gray-100 group">
                        <td class="px-3 py-2 text-gray-400 tabular-nums text-center">{{ $records->firstItem() + $loop->index }}</td>
                        <td class="px-3 py-2">
                            <span class="text-gray-800">{{ $record->call_start?->format('M d, Y') }}</span>
                            <span class="block text-xs text-gray-400">{{ $record->call_start?->format('H:i:s') }}</span>
                        </td>
                        <td class="px-3 py-2">
                            <span class="font-medium text-gray-900 tabular-nums">{{ $record->caller }}</span>
                            @if ($record->user)
                                <a href="{{ route('admin.users.show', $record->user) }}" class="block text-xs text-indigo-600 hover:text-indigo-700">{{ $record->user->name }}</a>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            <span class="tabular-nums text-gray-900">{{ $record->callee }}</span>
                        </td>
                        <td class="px-3 py-2 text-right tabular-nums text-gray-500">
                            {{ max(0, ($record->duration ?? 0) - ($record->billsec ?? 0)) }}s
                        </td>
                        <td class="px-3 py-2 text-right tabular-nums text-gray-700 font-medium">
                            {{ $record->billsec }}s
                        </td>
                        <td class="px-3 py-2 text-right tabular-nums font-bold text-gray-900">
                            {{ format_currency($record->total_cost, 4) }}
                        </td>
                        <td class="px-3 py-2">
                            @switch($record->disposition)
                                @case('ANSWERED')
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Answered</span>
                                    @break
                                @case('NO ANSWER')
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>No Answer</span>
                                    @break
                                @case('BUSY')
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Busy</span>
                                    @break
                                @case('FAILED')
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Failed</span>
                                    @break
                                @case('CANCEL')
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-500"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>Cancel</span>
                                    @break
                                @default
                                    <span class="text-gray-400">—</span>
                            @endswitch
                        </td>
                        <td class="px-3 py-2">
                            @switch($record->status)
                                @case('rated')
                                @case('charged')
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>{{ ucfirst($record->status) }}</span>
                                    @break
                                @case('in_progress')
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>In Progress</span>
                                    @break
                                @case('failed')
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Failed</span>
                                    @break
                                @case('unbillable')
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-500"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>Unbillable</span>
                                    @break
                            @endswitch
                        </td>
                        <td class="px-3 py-2 text-center">
                            <div class="flex items-center justify-center gap-1.5 opacity-60 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('admin.cdr.show', ['uuid' => $record->uuid, 'date' => $record->call_start?->format('Y-m-d')]) }}" class="p-1.5 rounded-lg text-blue-500 hover:text-blue-700 hover:bg-blue-50 transition-colors" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-12 text-center">
                            <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="text-sm text-gray-400">No call records found for this date range</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($records->hasPages())
        <div class="mt-4 flex justify-end">
            {{ $records->withQueryString()->onEachSide(1)->links('pagination::simple-tailwind') }}
        </div>
    @endif
</x-admin-layout>
