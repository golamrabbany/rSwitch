<x-admin-layout>
    <x-slot name="header">Payment Details</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Payment #{{ $payment->id }}</h2>
                <p class="page-subtitle">{{ $payment->created_at->format('M d, Y \a\t H:i') }}</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.payments.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Payments
            </a>
        </div>
    </div>

    {{-- Status Banner --}}
    @php
        $statusBannerClass = match($payment->status) {
            'completed' => 'payment-status-completed',
            'pending' => 'payment-status-pending',
            'failed' => 'payment-status-failed',
            'refunded' => 'payment-status-refunded',
            default => 'payment-status-pending'
        };
    @endphp
    <div class="payment-status-banner {{ $statusBannerClass }} mb-6">
        <div class="payment-status-banner-content">
            <div class="payment-status-banner-icon">
                @if($payment->status === 'completed')
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @elseif($payment->status === 'pending')
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @elseif($payment->status === 'failed')
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @else
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                @endif
            </div>
            <div class="payment-status-banner-text">
                <span class="payment-status-banner-title">{{ ucfirst($payment->status) }}</span>
                @if($payment->completed_at)
                    <span class="payment-status-banner-meta">Completed {{ $payment->completed_at->format('M d, Y \a\t H:i') }}</span>
                @endif
            </div>
        </div>
        <div class="payment-status-banner-amount">
            <span class="payment-status-banner-amount-label">Amount</span>
            <span class="payment-status-banner-amount-value">{{ format_currency($payment->amount, 4) }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Payment Details --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Payment Details</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Payment ID</span>
                            <span class="detail-value font-mono">#{{ $payment->id }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Amount</span>
                            <span class="detail-value font-semibold text-gray-900">{{ format_currency($payment->amount, 4) }} {{ $payment->currency }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Payment Method</span>
                            <span class="detail-value">
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
                                <span class="badge {{ $methodClass }}">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Status</span>
                            <span class="detail-value">
                                @php
                                    $statusClass = match($payment->status) {
                                        'completed' => 'badge-success',
                                        'pending' => 'badge-warning',
                                        'failed' => 'badge-danger',
                                        'refunded' => 'badge-info',
                                        default => 'badge-gray'
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }}">{{ ucfirst($payment->status) }}</span>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Created At</span>
                            <span class="detail-value">{{ $payment->created_at->format('M d, Y H:i:s') }}</span>
                        </div>
                        @if($payment->completed_at)
                            <div class="detail-item">
                                <span class="detail-label">Completed At</span>
                                <span class="detail-value">{{ $payment->completed_at->format('M d, Y H:i:s') }}</span>
                            </div>
                        @endif
                    </div>
                    @if($payment->notes)
                        <div class="mt-6 pt-4 border-t border-gray-100">
                            <span class="detail-label">Notes</span>
                            <p class="mt-1 text-sm text-gray-700 bg-gray-50 rounded-lg p-3">{{ $payment->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- References --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">References</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Recharged By</span>
                            <span class="detail-value">
                                @if($payment->rechargedBy)
                                    <a href="{{ route('admin.users.show', $payment->rechargedBy) }}" class="text-indigo-600 hover:text-indigo-700 font-medium">
                                        {{ $payment->rechargedBy->name }}
                                    </a>
                                    <span class="badge badge-gray ml-2">{{ ucfirst($payment->rechargedBy->role) }}</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Invoice</span>
                            <span class="detail-value">
                                @if($payment->invoice)
                                    <a href="{{ route('admin.invoices.show', $payment->invoice) }}" class="text-indigo-600 hover:text-indigo-700 font-mono">
                                        {{ $payment->invoice->invoice_number }}
                                    </a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Transaction</span>
                            <span class="detail-value">
                                @if($payment->transaction)
                                    <a href="{{ route('admin.transactions.show', $payment->transaction) }}" class="text-indigo-600 hover:text-indigo-700 font-mono">
                                        #{{ $payment->transaction->id }}
                                    </a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Gateway Transaction ID</span>
                            <span class="detail-value font-mono text-sm">{{ $payment->gateway_transaction_id ?: '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Gateway Response --}}
            @if($payment->gateway_response)
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Gateway Response</h3>
                    </div>
                    <div class="detail-card-body">
                        <pre class="text-xs text-gray-700 bg-gray-50 rounded-lg p-4 overflow-x-auto font-mono">{{ json_encode($payment->gateway_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- User Info --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Customer</h3>
                </div>
                <div class="detail-card-body">
                    @if($payment->user)
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-full flex items-center justify-center text-xl font-semibold flex-shrink-0 {{ $payment->user->role === 'reseller' ? 'bg-emerald-100 text-emerald-700' : 'bg-sky-100 text-sky-700' }}">
                                {{ strtoupper(substr($payment->user->name, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('admin.users.show', $payment->user) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                                    {{ $payment->user->name }}
                                </a>
                                <p class="text-xs text-gray-500 truncate">{{ $payment->user->email }}</p>
                                <span class="badge {{ $payment->user->role === 'reseller' ? 'badge-success' : 'badge-info' }} mt-1">
                                    {{ ucfirst($payment->user->role) }}
                                </span>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Current Balance</span>
                                <span class="font-semibold text-gray-900">{{ format_currency($payment->user->balance) }}</span>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-3 text-gray-400">
                            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <span class="text-sm">User not found</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Reseller Credit Info (if applicable) --}}
            @if($payment->resellerTransaction)
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Reseller Credit</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm font-semibold">
                                {{ strtoupper(substr($payment->user->parent->name ?? '?', 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ $payment->user->parent->name ?? 'Unknown' }}</p>
                                <span class="badge badge-success">Reseller</span>
                            </div>
                        </div>
                        <div class="flex justify-between text-sm border-t border-gray-100 pt-3">
                            <span class="text-gray-500">Amount Credited</span>
                            <span class="font-semibold text-emerald-600">+{{ format_currency($payment->resellerTransaction->amount, 4) }}</span>
                        </div>
                        <div class="flex justify-between text-sm mt-1">
                            <span class="text-gray-500">Transaction</span>
                            <a href="{{ route('admin.transactions.show', $payment->resellerTransaction) }}" class="text-indigo-600 hover:text-indigo-700 font-mono text-xs">#{{ $payment->resellerTransaction->id }}</a>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Quick Actions --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Quick Actions</h3>
                </div>
                <div class="detail-card-body space-y-2">
                    @if(auth()->user()->isSuperAdmin() && $payment->status === 'completed' && str_starts_with($payment->payment_method, 'online_'))
                        <button onclick="document.getElementById('refundModal').classList.remove('hidden')" class="quick-action-link w-full text-left text-red-600 hover:text-red-700 hover:bg-red-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Refund Payment
                        </button>
                    @endif
                    @if($payment->user)
                        <a href="{{ route('admin.users.show', $payment->user) }}" class="quick-action-link">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            View User Profile
                        </a>
                        <a href="{{ route('admin.transactions.index', ['user_id' => $payment->user_id]) }}" class="quick-action-link">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            View Transactions
                        </a>
                    @endif
                    @if($payment->invoice)
                        <a href="{{ route('admin.invoices.show', $payment->invoice) }}" class="quick-action-link">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            View Invoice
                        </a>
                    @endif
                    @if($payment->transaction)
                        <a href="{{ route('admin.transactions.show', $payment->transaction) }}" class="quick-action-link">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            View Transaction
                        </a>
                    @endif
                </div>
            </div>

            {{-- Payment Timeline --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Timeline</h3>
                </div>
                <div class="detail-card-body">
                    <div class="relative">
                        <div class="absolute left-4 top-0 bottom-0 w-px bg-gray-200"></div>
                        <div class="space-y-4">
                            <div class="relative pl-10">
                                <div class="absolute left-2 w-4 h-4 rounded-full bg-indigo-100 border-2 border-indigo-500"></div>
                                <p class="text-sm font-medium text-gray-900">Payment Created</p>
                                <p class="text-xs text-gray-500">{{ $payment->created_at->format('M d, Y \a\t H:i:s') }}</p>
                            </div>
                            @if($payment->completed_at)
                                <div class="relative pl-10">
                                    <div class="absolute left-2 w-4 h-4 rounded-full bg-emerald-100 border-2 border-emerald-500"></div>
                                    <p class="text-sm font-medium text-gray-900">Payment Completed</p>
                                    <p class="text-xs text-gray-500">{{ $payment->completed_at->format('M d, Y \a\t H:i:s') }}</p>
                                </div>
                            @elseif($payment->status === 'failed')
                                <div class="relative pl-10">
                                    <div class="absolute left-2 w-4 h-4 rounded-full bg-red-100 border-2 border-red-500"></div>
                                    <p class="text-sm font-medium text-gray-900">Payment Failed</p>
                                    <p class="text-xs text-gray-500">{{ $payment->updated_at->format('M d, Y \a\t H:i:s') }}</p>
                                </div>
                            @elseif($payment->status === 'pending')
                                <div class="relative pl-10">
                                    <div class="absolute left-2 w-4 h-4 rounded-full bg-amber-100 border-2 border-amber-500 animate-pulse"></div>
                                    <p class="text-sm font-medium text-gray-900">Awaiting Completion</p>
                                    <p class="text-xs text-gray-500">Payment is pending</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- Refund Modal --}}
    @if(auth()->user()->isSuperAdmin() && $payment->status === 'completed' && str_starts_with($payment->payment_method, 'online_'))
        <div id="refundModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="document.getElementById('refundModal').classList.add('hidden')"></div>
                <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6 z-10">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Refund Payment</h3>
                            <p class="text-sm text-gray-500">Payment #{{ $payment->id }} — {{ format_currency($payment->amount, 4) }}</p>
                        </div>
                    </div>

                    <form action="{{ route('admin.payments.refund', $payment) }}" method="POST">
                        @csrf
                        <div class="space-y-4">
                            <div class="form-group">
                                <label class="form-label">Refund Amount</label>
                                <input type="number" name="amount" step="0.01" min="0.01" max="{{ $payment->amount }}"
                                       value="{{ $payment->amount }}" class="form-input" required>
                                <p class="text-xs text-gray-500 mt-1">Max: {{ format_currency($payment->amount, 4) }}</p>
                            </div>

                            @if($payment->user && $payment->user->parent_id && $payment->user->parent && $payment->user->parent->isReseller())
                                <div class="flex items-start gap-3 p-3 bg-amber-50 rounded-lg border border-amber-200">
                                    <input type="checkbox" name="refund_reseller" value="1" id="refundReseller"
                                           class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" checked>
                                    <label for="refundReseller" class="text-sm">
                                        <span class="font-medium text-gray-900">Also refund parent reseller</span>
                                        <span class="block text-gray-500 mt-0.5">
                                            {{ $payment->user->parent->name }} — will be debited the same amount
                                        </span>
                                    </label>
                                </div>
                            @endif

                            <div class="form-group">
                                <label class="form-label">Notes (optional)</label>
                                <textarea name="notes" rows="2" class="form-input" placeholder="Reason for refund..."></textarea>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                            <button type="button" onclick="document.getElementById('refundModal').classList.add('hidden')"
                                    class="btn-action-secondary">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                                Process Refund
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</x-admin-layout>
