<x-client-layout>
    <x-slot name="header">Success Calls</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Success Calls</h2>
            <p class="page-subtitle">Answered calls — {{ $dateFrom->format('M d') }} to {{ $dateTo->format('M d, Y') }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('client.reports.export-success-calls', request()->query()) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Total</p>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Duration</p>
            <p class="text-2xl font-bold text-gray-900">{{ sprintf('%d:%02d', intdiv($stats['duration'], 3600), intdiv($stats['duration'] % 3600, 60)) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Billable</p>
            <p class="text-2xl font-bold text-gray-900">{{ sprintf('%d:%02d', intdiv($stats['billsec'], 3600), intdiv($stats['billsec'] % 3600, 60)) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Cost</p>
            <p class="text-2xl font-bold text-gray-900">{{ format_currency($stats['cost']) }}</p>
        </div>
    </div>

    <div class="filter-card mb-3">
        <form method="GET" class="filter-row flex-wrap">
            <input type="date" name="date_from" value="{{ $dateFrom->format('Y-m-d') }}" class="filter-select">
            <input type="date" name="date_to" value="{{ $dateTo->format('Y-m-d') }}" class="filter-select">
            <div class="filter-search-box">
                <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search caller/callee..." class="filter-input">
            </div>
            <button type="submit" class="btn-search-admin">Search</button>
            @if(request()->hasAny(['search', 'date_from', 'date_to']))
                <a href="{{ route('client.reports.success-calls') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($records->total() > 0)
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    Total : {{ number_format($records->total()) }} &middot; Showing {{ $records->firstItem() }} to {{ $records->lastItem() }}
                </span>
            </div>
        @endif
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" width="40">SL</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date/Time</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Caller</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Callee</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Duration</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Rate/Min</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Cost</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $r)
                    <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-indigo-50/50 transition-all border-b border-gray-100">
                        <td class="px-3 py-2 text-gray-400 tabular-nums text-center">{{ $records->firstItem() + $loop->index }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            <div class="text-gray-900">{{ $r->call_start?->format('H:i:s') }}</div>
                            <div class="text-xs text-gray-400">{{ $r->call_start?->format('M d') }}</div>
                        </td>
                        <td class="px-3 py-2 font-mono text-gray-900">{{ $r->caller }}</td>
                        <td class="px-3 py-2 font-mono text-gray-900">{{ $r->callee }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ sprintf('%d:%02d', intdiv($r->billable_duration, 60), $r->billable_duration % 60) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums text-gray-500">{{ format_currency($r->rate_per_minute, 4) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums font-medium text-gray-900">{{ format_currency($r->total_cost) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center">
                            <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-sm text-gray-400">No success calls found for this period</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($records->hasPages())
        <div class="mt-4 flex justify-end">{{ $records->withQueryString()->links() }}</div>
    @endif
</x-client-layout>
