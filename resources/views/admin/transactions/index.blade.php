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
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div class="stat-card">
            <div class="stat-card-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <div class="stat-card-content">
                <span class="stat-card-value">{{ format_currency($stats['total_credits'] ?? 0) }}</span>
                <span class="stat-card-label">Total Credits</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                </svg>
            </div>
            <div class="stat-card-content">
                <span class="stat-card-value">{{ format_currency($stats['total_debits'] ?? 0) }}</span>
                <span class="stat-card-label">Total Debits</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon" style="background: linear-gradient(135deg, #6366f1, #4f46e5);">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <div class="stat-card-content">
                <span class="stat-card-value">{{ number_format($stats['total_count'] ?? 0) }}</span>
                <span class="stat-card-label">Total Transactions</span>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="filter-card mb-3">
        <form method="GET" action="{{ route('admin.transactions.index') }}" class="filter-row flex-wrap">
            <div class="filter-search-box">
                <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search description..." class="filter-input">
            </div>

            <select name="user_id" class="filter-select">
                <option value="">All Users</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                        {{ $u->name }}
                    </option>
                @endforeach
            </select>

            <select name="type" class="filter-select">
                <option value="">All Types</option>
                @foreach (['topup', 'call_charge', 'reseller_call_charge', 'client_payment', 'did_charge', 'refund', 'adjustment', 'invoice_payment'] as $t)
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
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($transactions->total() > 0)
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    Total : {{ number_format($transactions->total()) }} &middot; Showing {{ $transactions->firstItem() }}–{{ $transactions->lastItem() }}
                </span>
            </div>
        @endif
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:40px">SL</th>
                    <th>Date</th>
                    <th>User</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                    <th class="text-right">Balance</th>
                    <th class="text-center" style="width:50px">
                        <svg class="w-4 h-4 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $txn)
                    <tr>
                        <td class="text-gray-400 text-xs">{{ $transactions->firstItem() + $loop->index }}</td>
                        <td>
                            <span class="text-sm text-gray-900">{{ $txn->created_at->format('M d, Y') }}</span>
                            <span class="block text-xs text-gray-400">{{ $txn->created_at->format('H:i:s') }}</span>
                        </td>
                        <td>
                            <a href="{{ route('admin.users.show', $txn->user_id) }}" class="text-indigo-600 hover:text-indigo-700 font-medium text-sm">
                                {{ $txn->user?->name ?? '—' }}
                            </a>
                            <span class="block text-xs text-gray-400">{{ ucfirst($txn->user?->role ?? '') }}</span>
                        </td>
                        <td>
                            @php
                                $isCredit = in_array($txn->type, ['topup', 'client_payment']);
                                $typeColor = match($txn->type) {
                                    'topup' => 'badge-success',
                                    'client_payment' => 'badge-success',
                                    'call_charge' => 'badge-danger',
                                    'reseller_call_charge' => 'badge-danger',
                                    'refund' => 'badge-warning',
                                    'adjustment' => 'badge-warning',
                                    default => 'badge-gray',
                                };
                            @endphp
                            <span class="badge {{ $typeColor }}">{{ ucfirst(str_replace('_', ' ', $txn->type)) }}</span>
                        </td>
                        <td class="text-gray-600 text-sm">{{ Str::limit($txn->description, 45) }}</td>
                        <td class="text-right">
                            @if($txn->amount >= 0)
                                <span class="text-sm font-semibold text-emerald-600">+{{ format_currency($txn->amount, 4) }}</span>
                            @else
                                <span class="text-sm font-semibold text-red-600">{{ format_currency($txn->amount, 4) }}</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <span class="text-sm font-medium text-gray-900">{{ format_currency($txn->balance_after, 4) }}</span>
                        </td>
                        <td>
                            <div class="flex items-center justify-center">
                                <a href="{{ route('admin.transactions.show', $txn) }}" class="text-indigo-500 hover:text-indigo-700" title="View">
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
                        <td colspan="8" class="text-center py-12">
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
