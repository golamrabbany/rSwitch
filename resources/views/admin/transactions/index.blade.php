<x-admin-layout>
    <x-slot name="header">Transactions</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">Transactions</h2>
            <p class="page-subtitle">View all financial transactions across the platform</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.balance.create') }}" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Adjust Balance
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="txn-stats-grid">
        <div class="txn-stat-card txn-stat-credit">
            <div class="txn-stat-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <div class="txn-stat-content">
                <span class="txn-stat-value">{{ format_currency($stats['total_credits'] ?? 0) }}</span>
                <span class="txn-stat-label">Total Credits</span>
            </div>
        </div>
        <div class="txn-stat-card txn-stat-debit">
            <div class="txn-stat-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                </svg>
            </div>
            <div class="txn-stat-content">
                <span class="txn-stat-value">{{ format_currency($stats['total_debits'] ?? 0) }}</span>
                <span class="txn-stat-label">Total Debits</span>
            </div>
        </div>
        <div class="txn-stat-card txn-stat-count">
            <div class="txn-stat-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <div class="txn-stat-content">
                <span class="txn-stat-value">{{ number_format($stats['total_count'] ?? 0) }}</span>
                <span class="txn-stat-label">Total Transactions</span>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="filter-card">
        <form method="GET" action="{{ route('admin.transactions.index') }}" class="filter-row flex-wrap">
            <div class="filter-search-box">
                <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search description..." class="filter-input">
            </div>

            <select name="user_id" class="filter-select">
                <option value="">All Users</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>

            <select name="type" class="filter-select">
                <option value="">All Types</option>
                @foreach (['topup', 'call_charge', 'did_charge', 'refund', 'adjustment', 'invoice_payment'] as $t)
                    <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', $t)) }}
                    </option>
                @endforeach
            </select>

            <input type="date" name="date_from" value="{{ request('date_from') }}" class="filter-date" placeholder="From">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="filter-date" placeholder="To">

            <button type="submit" class="btn-search-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Search
            </button>

            @if(request()->hasAny(['user_id', 'type', 'date_from', 'date_to', 'search']))
                <a href="{{ route('admin.transactions.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Data Table --}}
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>User</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                    <th class="text-right">Balance After</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $txn)
                    <tr>
                        <td>
                            <div class="txn-date">
                                <span class="txn-date-main">{{ $txn->created_at->format('M d, Y') }}</span>
                                <span class="txn-date-time">{{ $txn->created_at->format('H:i') }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="user-cell">
                                <div class="avatar {{ $txn->user?->role === 'reseller' ? 'avatar-emerald' : 'avatar-sky' }}">
                                    {{ strtoupper(substr($txn->user?->name ?? '?', 0, 1)) }}
                                </div>
                                <div>
                                    <a href="{{ route('admin.users.show', $txn->user_id) }}" class="user-name text-indigo-600 hover:text-indigo-700">
                                        {{ $txn->user?->name ?? '—' }}
                                    </a>
                                    <div class="user-email">{{ ucfirst($txn->user?->role ?? '') }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if(in_array($txn->type, ['topup', 'refund']))
                                <span class="badge badge-success">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    {{ ucfirst(str_replace('_', ' ', $txn->type)) }}
                                </span>
                            @else
                                <span class="badge badge-danger">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                    </svg>
                                    {{ ucfirst(str_replace('_', ' ', $txn->type)) }}
                                </span>
                            @endif
                        </td>
                        <td class="text-gray-600">
                            {{ Str::limit($txn->description, 40) }}
                        </td>
                        <td class="text-right">
                            <span class="txn-amount {{ $txn->amount >= 0 ? 'txn-amount-credit' : 'txn-amount-debit' }}">
                                {{ $txn->amount >= 0 ? '+' : '' }}{{ format_currency(abs($txn->amount), 4) }}
                            </span>
                        </td>
                        <td class="text-right font-medium text-gray-900">
                            {{ format_currency($txn->balance_after, 4) }}
                        </td>
                        <td>
                            <div class="flex items-center justify-center">
                                <a href="{{ route('admin.transactions.show', $txn) }}" class="action-icon" title="View Details">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-12">
                            <div class="empty-state">
                                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <p class="empty-text">No transactions found</p>
                                <p class="text-sm text-gray-400">Transactions will appear here when users make payments or receive credits</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transactions->hasPages())
        <div class="mt-6">
            {{ $transactions->withQueryString()->links() }}
        </div>
    @endif
</x-admin-layout>
