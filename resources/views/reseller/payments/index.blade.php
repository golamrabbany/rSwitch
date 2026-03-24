<x-reseller-layout>
    <x-slot name="header">My Payments</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">My Payments</h2>
            <p class="page-subtitle">Your balance topup and payment history</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="stat-card">
            <div class="stat-icon bg-emerald-100"><svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1"/></svg></div>
            <div class="stat-content"><p class="stat-value">{{ format_currency(auth()->user()->balance) }}</p><p class="stat-label">Current Balance</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-blue-100"><svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21l-7-4-7 4V5a2 2 0 012-2h10a2 2 0 012 2v16z"/></svg></div>
            <div class="stat-content"><p class="stat-value">{{ number_format($stats->total ?? 0) }}</p><p class="stat-label">Total Payments</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-green-100"><svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/></svg></div>
            <div class="stat-content"><p class="stat-value text-emerald-600">{{ format_currency($stats->total_paid ?? 0) }}</p><p class="stat-label">Total Paid</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-amber-100"><svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            <div class="stat-content"><p class="stat-value text-amber-600">{{ format_currency($stats->total_pending ?? 0) }}</p><p class="stat-label">Pending</p></div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="filter-card">
        <form method="GET" class="filter-row flex-wrap">
            <div class="flex-1 min-w-0">
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="filter-input">
            </div>
            <div class="flex-1 min-w-0">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="filter-input">
            </div>
            <select name="status" class="filter-select">
                <option value="">All Status</option>
                @foreach (['completed', 'pending', 'failed', 'refunded'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-search-reseller">Filter</button>
            @if(request()->hasAny(['date_from', 'date_to', 'status']))
                <a href="{{ route('reseller.payments.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    <div class="data-table-container">
        @if($payments->total() > 0)
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
                <span class="text-sm text-gray-600">
                    Showing <span class="font-semibold">{{ $payments->firstItem() }}–{{ $payments->lastItem() }}</span> of <span class="font-semibold">{{ number_format($payments->total()) }}</span> payments
                </span>
            </div>
        @endif
        <table class="data-table data-table-compact">
            <thead>
                <tr>
                    <th>Date/Time</th>
                    <th>Method</th>
                    <th>Notes</th>
                    <th>Recharged By</th>
                    <th>Status</th>
                    <th style="text-align: right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $p)
                    <tr>
                        <td class="whitespace-nowrap">
                            <div class="text-gray-900">{{ $p->created_at->format('H:i:s') }}</div>
                            <div class="text-xs text-gray-400">{{ $p->created_at->format('M d, Y') }}</div>
                        </td>
                        <td class="text-gray-900">{{ ucfirst(str_replace('_', ' ', $p->payment_method)) }}</td>
                        <td class="text-gray-500 text-sm">{{ Str::limit($p->notes, 40) ?? '—' }}</td>
                        <td class="text-gray-600">{{ $p->rechargedBy?->name ?? '—' }}</td>
                        <td>
                            @switch($p->status)
                                @case('completed') <span class="badge badge-success">Completed</span> @break
                                @case('pending') <span class="badge badge-warning">Pending</span> @break
                                @case('failed') <span class="badge badge-danger">Failed</span> @break
                                @case('refunded') <span class="badge badge-gray">Refunded</span> @break
                                @default <span class="badge badge-gray">{{ $p->status }}</span>
                            @endswitch
                        </td>
                        <td style="text-align: right" class="tabular-nums font-mono font-medium text-emerald-600">{{ format_currency($p->amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-12"><div class="empty-state"><p class="empty-text">No payments found</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
        <div class="mt-4 flex justify-end">
            {{ $payments->withQueryString()->onEachSide(1)->links('pagination::simple-tailwind') }}
        </div>
    @endif
</x-reseller-layout>
