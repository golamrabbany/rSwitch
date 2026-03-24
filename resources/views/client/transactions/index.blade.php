<x-client-layout>
    <x-slot name="header">Transactions</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">My Transactions</h2>
            <p class="page-subtitle">All credit and debit transactions on your account</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('client.payments.create') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Funds
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="stat-card">
            <div class="stat-icon bg-indigo-100"><svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1"/></svg></div>
            <div class="stat-content"><p class="stat-value">{{ format_currency($currentBalance) }}</p><p class="stat-label">Current Balance</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-blue-100"><svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21l-7-4-7 4V5a2 2 0 012-2h10a2 2 0 012 2v16z"/></svg></div>
            <div class="stat-content"><p class="stat-value">{{ number_format($stats->total ?? 0) }}</p><p class="stat-label">Transactions</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-green-100"><svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 11l5-5m0 0l5 5m-5-5v12"/></svg></div>
            <div class="stat-content"><p class="stat-value text-emerald-600">{{ format_currency($stats->total_credit ?? 0) }}</p><p class="stat-label">Total Credit</p></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-red-100"><svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 13l-5 5m0 0l-5-5m5 5V6"/></svg></div>
            <div class="stat-content"><p class="stat-value text-red-500">{{ format_currency($stats->total_debit ?? 0) }}</p><p class="stat-label">Total Debit</p></div>
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
            <select name="type" class="filter-select">
                <option value="">All Types</option>
                @foreach (['topup', 'call_charge', 'did_charge', 'refund', 'adjustment', 'invoice_payment'] as $t)
                    <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $t)) }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-search">Filter</button>
            @if(request()->hasAny(['date_from', 'date_to', 'type']))
                <a href="{{ route('client.transactions.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    <div class="data-table-container">
        @if($transactions->total() > 0)
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
                <span class="text-sm text-gray-600">
                    Showing <span class="font-semibold">{{ $transactions->firstItem() }}–{{ $transactions->lastItem() }}</span> of <span class="font-semibold">{{ number_format($transactions->total()) }}</span> transactions
                </span>
            </div>
        @endif
        <table class="data-table data-table-compact">
            <thead>
                <tr>
                    <th>Date/Time</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th style="text-align: right">Amount</th>
                    <th style="text-align: right">Balance After</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $txn)
                    <tr>
                        <td class="whitespace-nowrap">
                            <div class="text-gray-900">{{ $txn->created_at->format('H:i:s') }}</div>
                            <div class="text-xs text-gray-400">{{ $txn->created_at->format('M d, Y') }}</div>
                        </td>
                        <td>
                            @if(in_array($txn->type, ['topup', 'refund']))
                                <span class="badge badge-success">{{ ucfirst(str_replace('_', ' ', $txn->type)) }}</span>
                            @elseif($txn->type === 'adjustment')
                                <span class="badge badge-warning">{{ ucfirst($txn->type) }}</span>
                            @else
                                <span class="badge badge-danger">{{ ucfirst(str_replace('_', ' ', $txn->type)) }}</span>
                            @endif
                        </td>
                        <td class="text-gray-500 text-sm">{{ Str::limit($txn->description, 50) }}</td>
                        <td style="text-align: right" class="tabular-nums font-mono font-medium {{ $txn->amount >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                            {{ $txn->amount >= 0 ? '+' : '' }}{{ format_currency(abs($txn->amount), 4) }}
                        </td>
                        <td style="text-align: right" class="tabular-nums font-mono text-gray-900">{{ format_currency($txn->balance_after, 4) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-12"><div class="empty-state"><p class="empty-text">No transactions found</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transactions->hasPages())
        <div class="mt-4 flex justify-end">
            {{ $transactions->withQueryString()->onEachSide(1)->links('pagination::simple-tailwind') }}
        </div>
    @endif
</x-client-layout>
