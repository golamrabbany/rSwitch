<x-admin-layout>
    <x-slot name="header">Transaction Details</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">Transaction #{{ $transaction->id }}</h2>
            <p class="page-subtitle">View transaction details and references</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.transactions.index') }}" class="btn-action-outline">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Transactions
            </a>
        </div>
    </div>

    {{-- Amount Banner --}}
    <div class="txn-amount-banner {{ $transaction->amount >= 0 ? 'txn-amount-banner-credit' : 'txn-amount-banner-debit' }}">
        <div class="txn-amount-banner-content">
            <div class="txn-amount-banner-icon">
                @if($transaction->amount >= 0)
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                @else
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                    </svg>
                @endif
            </div>
            <div class="txn-amount-banner-text">
                <span class="txn-amount-banner-label">{{ $transaction->amount >= 0 ? 'Credit' : 'Debit' }} Amount</span>
                <span class="txn-amount-banner-value">{{ $transaction->amount >= 0 ? '+' : '' }}{{ format_currency(abs($transaction->amount), 4) }}</span>
            </div>
        </div>
        <div class="txn-amount-banner-meta">
            <span class="badge {{ in_array($transaction->type, ['topup', 'refund']) ? 'badge-success' : 'badge-danger' }}">
                {{ ucfirst(str_replace('_', ' ', $transaction->type)) }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Transaction Details --}}
        <div class="detail-card">
            <div class="detail-card-header">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <h3 class="detail-card-title">Transaction Details</h3>
                </div>
            </div>
            <div class="detail-card-body">
                <div class="txn-detail-grid">
                    <div class="txn-detail-item">
                        <span class="txn-detail-label">Transaction ID</span>
                        <span class="txn-detail-value font-mono">#{{ $transaction->id }}</span>
                    </div>
                    <div class="txn-detail-item">
                        <span class="txn-detail-label">Date & Time</span>
                        <span class="txn-detail-value">{{ $transaction->created_at->format('M d, Y H:i:s') }}</span>
                    </div>
                    <div class="txn-detail-item">
                        <span class="txn-detail-label">Type</span>
                        <span class="txn-detail-value">
                            <span class="badge {{ in_array($transaction->type, ['topup', 'refund']) ? 'badge-success' : 'badge-danger' }}">
                                {{ ucfirst(str_replace('_', ' ', $transaction->type)) }}
                            </span>
                        </span>
                    </div>
                    <div class="txn-detail-item">
                        <span class="txn-detail-label">Balance After</span>
                        <span class="txn-detail-value font-semibold">{{ format_currency($transaction->balance_after, 4) }}</span>
                    </div>
                </div>

                @if($transaction->description)
                    <div class="txn-description-section">
                        <span class="txn-detail-label">Description</span>
                        <p class="txn-description-text">{{ $transaction->description }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- User Information --}}
        <div class="detail-card">
            <div class="detail-card-header">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <h3 class="detail-card-title">User Information</h3>
                </div>
            </div>
            <div class="detail-card-body">
                @if($transaction->user)
                    <div class="txn-user-card">
                        <div class="avatar {{ $transaction->user->role === 'reseller' ? 'avatar-emerald' : ($transaction->user->role === 'admin' ? 'avatar-indigo' : 'avatar-sky') }}">
                            {{ strtoupper(substr($transaction->user->name, 0, 1)) }}
                        </div>
                        <div class="txn-user-info">
                            <a href="{{ route('admin.users.show', $transaction->user) }}" class="txn-user-name">
                                {{ $transaction->user->name }}
                            </a>
                            <span class="txn-user-email">{{ $transaction->user->email }}</span>
                            <span class="badge badge-gray mt-1">{{ ucfirst($transaction->user->role) }}</span>
                        </div>
                    </div>
                @else
                    <div class="txn-empty-user">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span class="text-gray-400 text-sm">User not available</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Reference & Audit --}}
        <div class="detail-card">
            <div class="detail-card-header">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                    <h3 class="detail-card-title">Reference & Audit</h3>
                </div>
            </div>
            <div class="detail-card-body">
                <div class="txn-detail-grid">
                    <div class="txn-detail-item">
                        <span class="txn-detail-label">Reference Type</span>
                        <span class="txn-detail-value">
                            @if($transaction->reference_type)
                                <span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $transaction->reference_type)) }}</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </span>
                    </div>
                    <div class="txn-detail-item">
                        <span class="txn-detail-label">Reference ID</span>
                        <span class="txn-detail-value font-mono">{{ $transaction->reference_id ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Created By --}}
        <div class="detail-card">
            <div class="detail-card-header">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <h3 class="detail-card-title">Created By</h3>
                </div>
            </div>
            <div class="detail-card-body">
                @if($transaction->creator)
                    <div class="txn-user-card">
                        <div class="avatar {{ $transaction->creator->role === 'admin' ? 'avatar-indigo' : 'avatar-emerald' }}">
                            {{ strtoupper(substr($transaction->creator->name, 0, 1)) }}
                        </div>
                        <div class="txn-user-info">
                            <a href="{{ route('admin.users.show', $transaction->creator) }}" class="txn-user-name">
                                {{ $transaction->creator->name }}
                            </a>
                            <span class="txn-user-email">{{ $transaction->creator->email }}</span>
                            <span class="badge badge-purple mt-1">{{ ucfirst($transaction->creator->role) }}</span>
                        </div>
                    </div>
                @else
                    <div class="txn-system-badge">
                        <div class="txn-system-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span class="text-sm text-gray-600">System Generated</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
