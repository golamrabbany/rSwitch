<x-client-layout>
    <x-slot name="header">Call Summary</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Call Summary</h2>
            <p class="page-subtitle">Daily breakdown — {{ $dateFrom->format('M d') }} to {{ $dateTo->format('M d, Y') }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('client.reports.export-call-summary', request()->query()) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export
            </a>
        </div>
    </div>

    @if($totals)
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Total Calls</p>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($totals->total_calls ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Answered</p>
            <p class="text-2xl font-bold text-emerald-600">{{ number_format($totals->answered_calls ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">ASR</p>
            <p class="text-2xl font-bold text-gray-900">{{ ($totals->total_calls ?? 0) > 0 ? round((($totals->answered_calls ?? 0) / $totals->total_calls) * 100, 1) : 0 }}%</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Billed Minutes</p>
            <p class="text-2xl font-bold text-gray-900">{{ number_format(round(($totals->total_billable ?? 0) / 60)) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Total Cost</p>
            <p class="text-2xl font-bold text-gray-900">{{ format_currency($totals->total_cost ?? 0) }}</p>
        </div>
    </div>
    @endif

    <div class="filter-card mb-3">
        <form method="GET" class="filter-row flex-wrap">
            <input type="date" name="date_from" value="{{ $dateFrom->format('Y-m-d') }}" class="filter-select">
            <input type="date" name="date_to" value="{{ $dateTo->format('Y-m-d') }}" class="filter-select">
            <button type="submit" class="btn-search-admin">Filter</button>
            @if(request()->hasAny(['date_from', 'date_to']))
                <a href="{{ route('client.reports.call-summary') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($summary->total() > 0)
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    {{ number_format($summary->total()) }} days &middot; Showing {{ $summary->firstItem() }} to {{ $summary->lastItem() }}
                </span>
            </div>
        @endif
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" width="40">SL</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Answered</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Failed</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">ASR %</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Billed Min</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Cost</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($summary as $row)
                    @php
                        $total = $row->total_calls ?? 0;
                        $answered = $row->answered_calls ?? 0;
                        $asr = $total > 0 ? round(($answered / $total) * 100, 1) : 0;
                    @endphp
                    <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-indigo-50/50 transition-all border-b border-gray-100">
                        <td class="px-3 py-2 text-gray-400 tabular-nums text-center">{{ $summary->firstItem() + $loop->index }}</td>
                        <td class="px-3 py-2 font-medium text-gray-900">{{ \Carbon\Carbon::parse($row->date)->format('M d, Y (D)') }}</td>
                        <td class="px-3 py-2 text-right tabular-nums font-medium">{{ number_format($total) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums text-emerald-600 font-medium">{{ number_format($answered) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums text-red-500">{{ number_format($total - $answered) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold {{ $asr >= 50 ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $asr }}%</span>
                        </td>
                        <td class="px-3 py-2 text-right tabular-nums text-gray-700">{{ number_format(round(($row->total_billable ?? 0) / 60)) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums font-medium text-gray-900">{{ format_currency($row->total_cost ?? 0) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center">
                            <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            <p class="text-sm text-gray-400">No call data for this period</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($summary->hasPages())
        <div class="mt-4 flex justify-end">{{ $summary->withQueryString()->links() }}</div>
    @endif
</x-client-layout>
