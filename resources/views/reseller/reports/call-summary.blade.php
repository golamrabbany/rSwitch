<x-reseller-layout>
    <x-slot name="header">Call Summary</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Call Summary</h2>
            <p class="page-subtitle">Daily breakdown of call activity</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('reseller.reports.call-summary.export', request()->query()) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export
            </a>
        </div>
    </div>

    {{-- Totals --}}
    @if($totals)
    @php
        $totalCalls = $totals->total_calls ?? 0;
        $answeredCalls = $totals->answered_calls ?? 0;
        $asr = $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100, 1) : 0;
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="stat-card">
            <div class="stat-icon bg-emerald-100"><svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg></div>
            <div class="stat-content">
                <p class="stat-value">{{ number_format($totalCalls) }}</p>
                <p class="stat-label">Total Calls</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ number_format($answeredCalls) }} answered / {{ number_format($totalCalls - $answeredCalls) }} failed</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-blue-100"><svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            <div class="stat-content">
                <p class="stat-value">{{ number_format(($totals->total_billable ?? 0) / 60, 0) }}</p>
                <p class="stat-label">Billed Minutes</p>
                <p class="text-xs {{ $asr >= 50 ? 'text-emerald-500' : 'text-amber-500' }} mt-0.5 font-medium">ASR {{ $asr }}%</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-purple-100"><svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1"/></svg></div>
            <div class="stat-content">
                <p class="stat-value">{{ format_currency($totals->total_cost ?? 0) }}</p>
                <p class="stat-label">Client Cost</p>
                <p class="text-xs text-gray-400 mt-0.5">My cost: {{ format_currency($totals->reseller_cost ?? 0) }}</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-amber-100"><svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg></div>
            <div class="stat-content">
                <p class="stat-value text-emerald-600">{{ format_currency(($totals->total_cost ?? 0) - ($totals->reseller_cost ?? 0)) }}</p>
                <p class="stat-label">Profit</p>
            </div>
        </div>
    </div>
    @endif

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
            <button type="submit" class="btn-search-reseller">Filter</button>
            @if(request()->hasAny(['date_from', 'date_to', 'user_id']))
                <a href="{{ route('reseller.reports.call-summary') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    <div class="data-table-container">
        @if($summary->total() > 0)
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
                <span class="text-sm text-gray-600">
                    Showing <span class="font-semibold">{{ $summary->firstItem() }}–{{ $summary->lastItem() }}</span> of <span class="font-semibold">{{ number_format($summary->total()) }}</span> days
                </span>
            </div>
        @endif
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th class="text-right">Total Calls</th>
                    <th class="text-right">Answered</th>
                    <th class="text-right">Failed</th>
                    <th class="text-right">ASR %</th>
                    <th class="text-right">Billed Min</th>
                    <th class="text-right">Client Cost</th>
                    <th class="text-right">My Cost</th>
                    <th class="text-right">Profit</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($summary as $row)
                    @php
                        $answered = $row->answered_calls ?? 0;
                        $total = $row->total_calls ?? 0;
                        $failed = $total - $answered;
                        $asr = $total > 0 ? round(($answered / $total) * 100, 1) : 0;
                        $clientCost = $row->total_cost ?? 0;
                        $myCost = $row->reseller_cost ?? 0;
                        $profit = $clientCost - $myCost;
                    @endphp
                    <tr>
                        <td class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($row->date)->format('M d, Y (D)') }}</td>
                        <td class="text-right font-semibold text-gray-900">{{ number_format($total) }}</td>
                        <td class="text-right text-emerald-600 font-medium">{{ number_format($answered) }}</td>
                        <td class="text-right text-red-500">{{ number_format($failed) }}</td>
                        <td class="text-right">
                            <span class="{{ $asr >= 50 ? 'text-emerald-600' : 'text-amber-600' }} font-medium">{{ $asr }}%</span>
                        </td>
                        <td class="text-right tabular-nums text-gray-600">{{ number_format(($row->total_billable ?? 0) / 60, 0) }}</td>
                        <td class="text-right tabular-nums font-mono text-gray-900">{{ format_currency($clientCost) }}</td>
                        <td class="text-right tabular-nums font-mono text-gray-500">{{ format_currency($myCost) }}</td>
                        <td class="text-right tabular-nums font-mono font-medium {{ $profit > 0 ? 'text-emerald-600' : 'text-red-500' }}">{{ format_currency($profit) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center py-12"><div class="empty-state"><p class="empty-text">No data for this period</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($summary->hasPages())
        <div class="mt-4 flex justify-end">
            {{ $summary->withQueryString()->onEachSide(1)->links('pagination::simple-tailwind') }}
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
