<x-admin-layout>
    <x-slot name="header">Recharge Admin Details</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row mb-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Recharge Admin Details</h2>
                <p class="page-subtitle">View account information for {{ $rechargeAdmin->name }}</p>
            </div>
        </div>
        <div class="page-actions flex items-center gap-3">
            <span class="badge {{ $rechargeAdmin->status === 'active' ? 'badge-success' : ($rechargeAdmin->status === 'suspended' ? 'badge-warning' : 'badge-danger') }}">
                {{ ucfirst($rechargeAdmin->status) }}
            </span>
            <a href="{{ route('admin.recharge-admins.edit', $rechargeAdmin) }}" class="btn-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
            </a>
            <a href="{{ route('admin.recharge-admins.index') }}" class="btn-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to List
            </a>
        </div>
    </div>

    {{-- User Header Card --}}
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white font-bold text-xl">
                        {{ strtoupper(substr($rechargeAdmin->name, 0, 2)) }}
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-slate-900">{{ $rechargeAdmin->name }}</h3>
                        <p class="text-sm text-slate-500">{{ $rechargeAdmin->email }}</p>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="badge badge-amber">Recharge Admin</span>
                            <span class="badge {{ $rechargeAdmin->two_factor_secret ? 'badge-success' : 'badge-gray' }}">
                                2FA {{ $rechargeAdmin->two_factor_secret ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.audit-logs.index', ['user_id' => $rechargeAdmin->id]) }}" class="quick-action-btn">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <span>Audit Logs</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Cards Row --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="stat-card">
            <div class="stat-icon stat-icon-blue">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <p class="stat-value">{{ $stats['resellers'] }}</p>
                <p class="stat-label">Assigned Resellers</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-green">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <p class="stat-value">{{ $stats['clients'] }}</p>
                <p class="stat-label">Total Clients</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-amber">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <div class="stat-content">
                <p class="stat-value">{{ $stats['total_transactions'] }}</p>
                <p class="stat-label">Total Transactions</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-purple">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <p class="stat-value">{{ $stats['transactions_today'] }}</p>
                <p class="stat-label">Today's Transactions</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column - Account Information --}}
        <div class="space-y-6">
            {{-- Account Information --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Account Information</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Account ID</span>
                            <span class="detail-value font-mono">#{{ $rechargeAdmin->id }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Role</span>
                            <span class="badge badge-amber">Recharge Admin</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Status</span>
                            <span class="badge {{ $rechargeAdmin->status === 'active' ? 'badge-success' : ($rechargeAdmin->status === 'suspended' ? 'badge-warning' : 'badge-danger') }}">
                                {{ ucfirst($rechargeAdmin->status) }}
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Created</span>
                            <span class="detail-value">{{ $rechargeAdmin->created_at->format('M d, Y H:i') }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Last Login</span>
                            <span class="detail-value">{{ $rechargeAdmin->last_login_at?->format('M d, Y H:i') ?? 'Never' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">2FA Status</span>
                            <span class="badge {{ $rechargeAdmin->two_factor_secret ? 'badge-success' : 'badge-gray' }}">
                                {{ $rechargeAdmin->two_factor_secret ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Access Level --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Access Level</h3>
                </div>
                <div class="detail-card-body">
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-amber-900">Balance Operations Only</h4>
                                <p class="text-sm text-amber-700 mt-1">This admin can only perform balance recharge and adjustments for assigned resellers.</p>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center gap-2 text-gray-600">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>View users, SIP, DIDs, CDR, transactions</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-600">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Balance recharge / adjustment</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-600">
                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            <span>Create/edit/delete any resources</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-600">
                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            <span>System settings, trunks, rates</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Middle Column - Assigned Resellers --}}
        <div class="space-y-6">
            {{-- Assigned Resellers --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Assigned Resellers</h3>
                    <p class="text-xs text-slate-500 mt-1">Can manage balance for these resellers and their clients</p>
                </div>
                <div class="detail-card-body p-0">
                    @if($rechargeAdmin->assignedResellers->count() > 0)
                        <div class="divide-y divide-slate-100">
                            @foreach($rechargeAdmin->assignedResellers as $reseller)
                                <div class="px-4 py-3 flex items-center justify-between hover:bg-slate-50">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center">
                                            <span class="text-sm font-medium text-blue-700">{{ strtoupper(substr($reseller->name, 0, 1)) }}</span>
                                        </div>
                                        <div>
                                            <a href="{{ route('admin.users.show', $reseller) }}" class="text-sm font-medium text-slate-900 hover:text-indigo-600">
                                                {{ $reseller->name }}
                                            </a>
                                            <p class="text-xs text-slate-500">{{ $reseller->email }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-slate-500">{{ $reseller->children_count ?? 0 }} clients</span>
                                        <span class="badge {{ $reseller->status === 'active' ? 'badge-success' : 'badge-gray' }}">
                                            {{ ucfirst($reseller->status) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-6 text-center">
                            <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <p class="text-sm text-slate-500">No resellers assigned</p>
                            <a href="{{ route('admin.recharge-admins.edit', $rechargeAdmin) }}" class="text-sm text-indigo-600 hover:underline mt-1 inline-block">
                                Assign resellers
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Quick Actions</h3>
                </div>
                <div class="detail-card-body space-y-2">
                    <a href="{{ route('admin.recharge-admins.edit', $rechargeAdmin) }}" class="quick-action-link">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <span>Edit Account</span>
                    </a>
                    <a href="{{ route('admin.audit-logs.index', ['user_id' => $rechargeAdmin->id]) }}" class="quick-action-link">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <span>View Audit Logs</span>
                    </a>
                    <form action="{{ route('admin.recharge-admins.destroy', $rechargeAdmin) }}" method="POST" class="inline"
                          onsubmit="return confirm('Are you sure you want to delete this recharge admin?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="quick-action-link text-red-600 hover:text-red-700 hover:bg-red-50 w-full">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            <span>Delete Account</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Right Column - Recent Activity --}}
        <div class="space-y-6">
            {{-- Recent Transactions --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Recent Transactions</h3>
                    <p class="text-xs text-slate-500 mt-1">Balance operations by this admin</p>
                </div>
                <div class="detail-card-body p-0">
                    @if($recentTransactions->count() > 0)
                        <div class="divide-y divide-slate-100">
                            @foreach($recentTransactions as $transaction)
                                <div class="px-4 py-3">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-slate-900">{{ $transaction->user?->name ?? 'N/A' }}</p>
                                            <p class="text-xs text-slate-500">{{ $transaction->created_at->format('M d, H:i') }}</p>
                                        </div>
                                        <span class="font-medium {{ floatval($transaction->amount) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ floatval($transaction->amount) >= 0 ? '+' : '' }}${{ number_format(abs($transaction->amount), 2) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-6 text-center">
                            <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="text-sm text-slate-500">No transactions yet</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Security Tips --}}
            <div class="card bg-amber-50 border-amber-200">
                <div class="card-body">
                    <div class="flex gap-3">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-amber-800">Audit Trail</h4>
                            <p class="mt-1 text-xs text-amber-700">
                                All balance operations by this admin are logged with full details including reason, amounts, and timestamps.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
