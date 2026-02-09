<x-admin-layout>
    <x-slot name="header">Payments</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Payments</h2>
                <p class="page-subtitle">Track all payment transactions</p>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="payment-stats-grid">
        <div class="payment-stat-card payment-stat-completed">
            <div class="payment-stat-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="payment-stat-content">
                <span class="payment-stat-value">{{ $stats['completed'] ?? 0 }}</span>
                <span class="payment-stat-label">Completed</span>
            </div>
        </div>
        <div class="payment-stat-card payment-stat-pending">
            <div class="payment-stat-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="payment-stat-content">
                <span class="payment-stat-value">{{ $stats['pending'] ?? 0 }}</span>
                <span class="payment-stat-label">Pending</span>
            </div>
        </div>
        <div class="payment-stat-card payment-stat-failed">
            <div class="payment-stat-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="payment-stat-content">
                <span class="payment-stat-value">{{ $stats['failed'] ?? 0 }}</span>
                <span class="payment-stat-label">Failed</span>
            </div>
        </div>
        <div class="payment-stat-card payment-stat-total">
            <div class="payment-stat-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="payment-stat-content">
                <span class="payment-stat-value">{{ format_currency($stats['total_amount'] ?? 0) }}</span>
                <span class="payment-stat-label">Total Amount</span>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="filter-card">
        <form method="GET" action="{{ route('admin.payments.index') }}" class="filter-row flex-wrap">
            <select name="user_id" class="filter-select">
                <option value="">All Users</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>

            <select name="status" class="filter-select">
                <option value="">All Statuses</option>
                @foreach (['pending', 'completed', 'failed', 'refunded'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>

            <select name="payment_method" class="filter-select">
                <option value="">All Methods</option>
                @foreach (['manual_admin', 'manual_reseller', 'stripe', 'paypal', 'bank_transfer'] as $m)
                    <option value="{{ $m }}" {{ request('payment_method') === $m ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $m)) }}</option>
                @endforeach
            </select>

            <input type="date" name="date_from" value="{{ request('date_from') }}" class="filter-date" placeholder="From">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="filter-date" placeholder="To">

            <button type="submit" class="btn-search-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filter
            </button>

            @if(request()->hasAny(['user_id', 'status', 'payment_method', 'date_from', 'date_to']))
                <a href="{{ route('admin.payments.index') }}" class="btn-clear">Clear</a>
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
                    <th class="text-right">Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Invoice</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr>
                        <td>
                            <div class="txn-date">
                                <span class="txn-date-main">{{ $payment->created_at->format('M d, Y') }}</span>
                                <span class="txn-date-time">{{ $payment->created_at->format('H:i') }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="user-cell">
                                <div class="avatar {{ $payment->user?->role === 'reseller' ? 'avatar-emerald' : 'avatar-sky' }}">
                                    {{ strtoupper(substr($payment->user?->name ?? '?', 0, 1)) }}
                                </div>
                                <div>
                                    <a href="{{ route('admin.users.show', $payment->user_id) }}" class="user-name text-indigo-600 hover:text-indigo-700">
                                        {{ $payment->user?->name ?? '—' }}
                                    </a>
                                    <div class="user-email">{{ ucfirst($payment->user?->role ?? '') }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="text-right">
                            <span class="font-semibold text-gray-900">{{ format_currency($payment->amount) }}</span>
                        </td>
                        <td>
                            @php
                                $methodClass = match($payment->payment_method) {
                                    'stripe' => 'badge-purple',
                                    'paypal' => 'badge-blue',
                                    'manual_admin' => 'badge-info',
                                    'manual_reseller' => 'badge-success',
                                    'bank_transfer' => 'badge-gray',
                                    default => 'badge-gray'
                                };
                            @endphp
                            <span class="badge {{ $methodClass }}">
                                {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                            </span>
                        </td>
                        <td>
                            @php
                                $statusClass = match($payment->status) {
                                    'completed' => 'badge-success',
                                    'pending' => 'badge-warning',
                                    'failed' => 'badge-danger',
                                    'refunded' => 'badge-info',
                                    default => 'badge-gray'
                                };
                            @endphp
                            <span class="badge {{ $statusClass }}">
                                {{ ucfirst($payment->status) }}
                            </span>
                        </td>
                        <td>
                            @if($payment->invoice)
                                <a href="{{ route('admin.invoices.show', $payment->invoice) }}" class="text-indigo-600 hover:text-indigo-700 font-mono text-sm">
                                    {{ $payment->invoice->invoice_number }}
                                </a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-center">
                                <a href="{{ route('admin.payments.show', $payment) }}" class="action-icon" title="View">
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
                                <p class="empty-text">No payments found</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
        <div class="mt-6">
            {{ $payments->withQueryString()->links() }}
        </div>
    @endif
</x-admin-layout>
