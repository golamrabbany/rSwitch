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
    <div class="filter-card mb-3">
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
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($invoices->total() > 0)
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    Invoices Total : {{ number_format($invoices->total()) }} &middot; Showing {{ $invoices->firstItem() }} to {{ $invoices->lastItem() }}
                </span>
            </div>
        @endif
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">SL</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Invoice #</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Period</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Due Date</th>
                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoices as $invoice)
                    <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-indigo-50/50 transition-all border-b border-gray-100 group">
                        <td class="px-3 py-2 text-gray-500 text-xs">{{ $invoices->firstItem() + $loop->index }}</td>
                        <td class="px-3 py-2">
                            <span class="font-mono font-medium text-gray-900">{{ $invoice->invoice_number }}</span>
                        </td>
                        <td class="px-3 py-2">
                            <div>
                                <a href="{{ route('admin.users.show', $invoice->user_id) }}" class="text-indigo-600 hover:text-indigo-700 font-medium text-sm">
                                    {{ $invoice->user?->name ?? '—' }}
                                </a>
                                <div class="text-xs text-gray-400">{{ ucfirst($invoice->user?->role ?? '') }}</div>
                            </div>
                        </td>
                        <td class="px-3 py-2 text-gray-600">
                            {{ $invoice->period_start?->format('M d') }} - {{ $invoice->period_end?->format('M d, Y') }}
                        </td>
                        <td class="px-3 py-2 text-right font-semibold text-gray-900">
                            {{ format_currency($invoice->total_amount) }}
                        </td>
                        <td class="px-3 py-2">
                            @php
                                $statusColor = match($invoice->status) {
                                    'draft' => 'bg-gray-400',
                                    'issued' => 'bg-blue-500',
                                    'paid' => 'bg-emerald-500',
                                    'overdue' => 'bg-red-500',
                                    'cancelled' => 'bg-gray-400',
                                    default => 'bg-gray-400'
                                };
                            @endphp
                            <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-700">
                                <span class="w-1.5 h-1.5 rounded-full {{ $statusColor }}"></span>
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-gray-600">
                            {{ $invoice->due_date?->format('M d, Y') }}
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex items-center justify-center gap-1.5 opacity-60 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('admin.invoices.show', $invoice) }}" class="text-blue-600 hover:text-blue-700" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="text-amber-600 hover:text-amber-700" title="Download PDF">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
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
