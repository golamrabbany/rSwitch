<x-reseller-layout>
    <x-slot name="header">Success Calls</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Success Calls</h2>
            <p class="page-subtitle">Answered calls with billing details</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('reseller.reports.success-calls.export', request()->query()) }}" class="btn-action-secondary">
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
            <div class="stat-icon bg-emerald-100"><svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/></svg></div>
            <div class="stat-content"><p class="stat-value">{{ number_format($stats['total']) }}</p><p class="stat-label">Answered</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-blue-100"><svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            <div class="stat-content"><p class="stat-value">{{ number_format($stats['duration'] / 60, 0) }}</p><p class="stat-label">Minutes</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-purple-100"><svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1"/></svg></div>
            <div class="stat-content"><p class="stat-value">{{ format_currency($stats['cost']) }}</p><p class="stat-label">Total Cost</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-gray-100"><svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5"/></svg></div>
            <div class="stat-content"><p class="stat-value">{{ $stats['total'] > 0 ? number_format($stats['duration'] / $stats['total']) : 0 }}s</p><p class="stat-label">Avg Duration</p></div>
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
                <a href="{{ route('reseller.reports.success-calls') }}" class="btn-clear">Clear</a>
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
                    <th style="text-align: center">Duration</th>
                    <th class="text-right">Client Rate</th>
                    <th class="text-right">My Rate</th>
                    <th class="text-right">Client Cost</th>
                    <th class="text-right">My Cost</th>
                    <th class="text-right">Profit</th>
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
                        @php
                            $clientCost = (float) $r->total_cost;
                            $resellerCost = (float) $r->reseller_cost;
                            $profit = $clientCost - $resellerCost;
                        @endphp
                        <td style="text-align: center" class="tabular-nums text-gray-900 font-medium">{{ sprintf('%d:%02d', intdiv($r->billable_duration, 60), $r->billable_duration % 60) }}</td>
                        <td class="text-right tabular-nums font-mono text-gray-600">{{ $r->rate_per_minute > 0 ? format_currency($r->rate_per_minute, 4) : '—' }}</td>
                        <td class="text-right tabular-nums font-mono text-gray-400">{{ $resellerCost > 0 && $r->billable_duration > 0 ? format_currency($resellerCost / ($r->billable_duration / 60), 4) : '—' }}</td>
                        <td class="text-right tabular-nums font-mono font-medium text-gray-900">{{ $clientCost > 0 ? format_currency($clientCost) : '—' }}</td>
                        <td class="text-right tabular-nums font-mono text-gray-500">{{ $resellerCost > 0 ? format_currency($resellerCost) : '—' }}</td>
                        <td class="text-right tabular-nums font-mono font-medium {{ $profit > 0 ? 'text-emerald-600' : ($profit < 0 ? 'text-red-500' : 'text-gray-400') }}">{{ $clientCost > 0 ? format_currency($profit) : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center py-12"><div class="empty-state"><p class="empty-text">No success calls found</p></div></td></tr>
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
