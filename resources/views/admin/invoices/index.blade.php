<x-admin-layout>
    <x-slot name="header">Invoices</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Invoices</h2>
                <p class="page-subtitle">Manage customer invoices and billing</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.invoices.create') }}" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Create Invoice
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="invoice-stats-grid">
        <div class="invoice-stat-card invoice-stat-draft">
            <div class="invoice-stat-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
            <div class="invoice-stat-content">
                <span class="invoice-stat-value">{{ $stats['draft'] ?? 0 }}</span>
                <span class="invoice-stat-label">Draft</span>
            </div>
        </div>
        <div class="invoice-stat-card invoice-stat-issued">
            <div class="invoice-stat-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="invoice-stat-content">
                <span class="invoice-stat-value">{{ $stats['issued'] ?? 0 }}</span>
                <span class="invoice-stat-label">Issued</span>
            </div>
        </div>
        <div class="invoice-stat-card invoice-stat-paid">
            <div class="invoice-stat-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="invoice-stat-content">
                <span class="invoice-stat-value">{{ $stats['paid'] ?? 0 }}</span>
                <span class="invoice-stat-label">Paid</span>
            </div>
        </div>
        <div class="invoice-stat-card invoice-stat-overdue">
            <div class="invoice-stat-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="invoice-stat-content">
                <span class="invoice-stat-value">{{ $stats['overdue'] ?? 0 }}</span>
                <span class="invoice-stat-label">Overdue</span>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="filter-card">
        <form method="GET" action="{{ route('admin.invoices.index') }}" class="filter-row flex-wrap">
            <select name="user_id" class="filter-select">
                <option value="">All Users</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>

            <select name="status" class="filter-select">
                <option value="">All Statuses</option>
                @foreach (['draft', 'issued', 'paid', 'overdue', 'cancelled'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
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

            @if(request()->hasAny(['user_id', 'status', 'date_from', 'date_to']))
                <a href="{{ route('admin.invoices.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Data Table --}}
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>User</th>
                    <th>Period</th>
                    <th class="text-right">Amount</th>
                    <th>Status</th>
                    <th>Due Date</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoices as $invoice)
                    <tr>
                        <td>
                            <span class="font-mono font-medium text-gray-900">{{ $invoice->invoice_number }}</span>
                        </td>
                        <td>
                            <div class="user-cell">
                                <div class="avatar {{ $invoice->user?->role === 'reseller' ? 'avatar-emerald' : 'avatar-sky' }}">
                                    {{ strtoupper(substr($invoice->user?->name ?? '?', 0, 1)) }}
                                </div>
                                <div>
                                    <a href="{{ route('admin.users.show', $invoice->user_id) }}" class="user-name text-indigo-600 hover:text-indigo-700">
                                        {{ $invoice->user?->name ?? '—' }}
                                    </a>
                                    <div class="user-email">{{ ucfirst($invoice->user?->role ?? '') }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="text-gray-600">
                            {{ $invoice->period_start?->format('M d') }} - {{ $invoice->period_end?->format('M d, Y') }}
                        </td>
                        <td class="text-right font-semibold text-gray-900">
                            {{ format_currency($invoice->total_amount) }}
                        </td>
                        <td>
                            @php
                                $statusClass = match($invoice->status) {
                                    'draft' => 'badge-gray',
                                    'issued' => 'badge-blue',
                                    'paid' => 'badge-success',
                                    'overdue' => 'badge-danger',
                                    'cancelled' => 'badge-gray',
                                    default => 'badge-gray'
                                };
                            @endphp
                            <span class="badge {{ $statusClass }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </td>
                        <td class="text-gray-600">
                            {{ $invoice->due_date?->format('M d, Y') }}
                        </td>
                        <td>
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('admin.invoices.show', $invoice) }}" class="action-icon" title="View">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="action-icon" title="Download PDF">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="empty-text">No invoices found</p>
                                <a href="{{ route('admin.invoices.create') }}" class="empty-link-admin">Create your first invoice</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($invoices->hasPages())
        <div class="mt-6">
            {{ $invoices->withQueryString()->links() }}
        </div>
    @endif
</x-admin-layout>
