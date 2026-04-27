<x-reseller-layout>
    <x-slot name="header">Failed Calls</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Failed Calls</h2>
            <p class="page-subtitle">Unanswered, busy, and failed call attempts</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('reseller.reports.failed-calls.export', request()->query()) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="stat-card">
            <div class="stat-icon bg-red-100"><svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg></div>
            <div class="stat-content"><p class="stat-value">{{ number_format($stats['total']) }}</p><p class="stat-label">Total Failed</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-amber-100"><svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            <div class="stat-content"><p class="stat-value">{{ number_format($stats['duration'] / 60, 0) }}</p><p class="stat-label">Wasted Minutes</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-gray-100"><svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            <div class="stat-content"><p class="stat-value">{{ $stats['total'] > 0 ? number_format($stats['duration'] / $stats['total']) : 0 }}s</p><p class="stat-label">Avg Ring Time</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-blue-100"><svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            <div class="stat-content"><p class="stat-value">{{ format_currency($stats['cost']) }}</p><p class="stat-label">Lost Revenue</p></div>
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
            {{-- Client auto-suggest --}}
            <div class="relative flex-1 min-w-0" x-data="clientSearch()" @click.away="open = false">
                <input type="hidden" name="user_id" :value="selectedId">
                <input type="text" x-model="clientQuery" @focus="open = true" @click="open = true" @input="open = true; selectedId = ''" placeholder="All Clients" class="filter-input" style="padding-left: 1rem;" autocomplete="off">
                <div x-show="open" x-cloak class="absolute z-20 mt-1 w-64 bg-white rounded-lg border border-gray-200 shadow-lg max-h-48 overflow-y-auto">
                    <button type="button" @click="selectedId = ''; clientQuery = ''; open = false" class="w-full px-3 py-2 text-left text-sm hover:bg-emerald-50 text-gray-500">All Clients</button>
                    <template x-for="c in filteredList" :key="c.id">
                        <button type="button" @click="selectedId = String(c.id); clientQuery = c.name; open = false" class="w-full px-3 py-2 text-left text-sm hover:bg-emerald-50 flex items-center justify-between">
                            <span class="font-medium text-gray-900" x-text="c.name"></span>
                            <span class="text-xs text-gray-400" x-text="c.email"></span>
                        </button>
                    </template>
                </div>
            </div>
            <div class="filter-search-box flex-1 min-w-0">
                <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search caller/callee..." class="filter-input">
            </div>
            <button type="submit" class="btn-search-reseller">Filter</button>
            @if(request()->hasAny(['search', 'date_from', 'date_to', 'user_id']))
                <a href="{{ route('reseller.reports.failed-calls') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    <div class="data-table-container">
        @if($records->total() > 0)
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
                <span class="text-sm text-gray-600">
                    Showing <span class="font-semibold">{{ $records->firstItem() }}–{{ $records->lastItem() }}</span> of <span class="font-semibold">{{ number_format($records->total()) }}</span> results
                </span>
            </div>
        @endif
        <table class="data-table data-table-compact">
            <thead>
                <tr>
                    <th>Date/Time</th>
                    <th>Caller</th>
                    <th>Callee</th>
                    <th style="text-align: center">Ring Time</th>
                    <th>Disposition</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $r)
                    <tr>
                        <td class="whitespace-nowrap">
                            <div class="text-gray-900">{{ $r->call_start?->format('H:i:s') }}</div>
                            <div class="text-xs text-gray-400">{{ $r->call_start?->format('M d, Y') }}</div>
                        </td>
                        <td>
                            <div class="font-mono text-gray-900">{{ Str::limit($r->caller, 15) }}</div>
                            <div class="text-xs text-gray-400">{{ $r->user?->name ?? '—' }}</div>
                        </td>
                        <td class="font-mono text-gray-900">{{ Str::limit($r->callee, 15) }}</td>
                        <td style="text-align: center" class="tabular-nums text-gray-600">{{ $r->duration }}s</td>
                        <td>
                            @switch($r->disposition)
                                @case('NO ANSWER') <span class="badge badge-warning">No Answer</span> @break
                                @case('BUSY') <span class="badge badge-warning">Busy</span> @break
                                @case('FAILED') <span class="badge badge-danger">Failed</span> @break
                                @case('CANCEL') <span class="badge badge-gray" title="Caller hung up before answer">Cancelled</span> @break
                                @default <span class="badge badge-gray">{{ $r->disposition }}</span>
                            @endswitch
                        </td>
                        <td class="text-xs text-gray-500">{{ $r->hangup_cause ? str_replace('_', ' ', $r->hangup_cause) : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-12"><div class="empty-state"><p class="empty-text">No failed calls found</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($records->hasPages())
        <div class="mt-4 flex justify-end">
            {{ $records->withQueryString()->onEachSide(1)->links('pagination::simple-tailwind') }}
        </div>
    @endif

    @push('scripts')
    <script>
    const _rptClients = @json($clients);

    function clientSearch() {
        return {
            open: false,
            clientQuery: '',
            selectedId: '{{ request('user_id') }}',
            filteredList: _rptClients,
            init() {
                if (this.selectedId) {
                    var found = _rptClients.find(function(c) { return String(c.id) === String(this.selectedId); }.bind(this));
                    if (found) this.clientQuery = found.name;
                }
                this.$watch('clientQuery', function(val) {
                    if (!val) { this.filteredList = _rptClients; return; }
                    var q = val.toLowerCase();
                    this.filteredList = _rptClients.filter(function(c) { return c.name.toLowerCase().indexOf(q) > -1 || (c.email && c.email.toLowerCase().indexOf(q) > -1); });
                }.bind(this));
            }
        }
    }
    </script>
    @endpush
</x-reseller-layout>
