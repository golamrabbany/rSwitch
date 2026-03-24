<x-reseller-layout>
    <x-slot name="header">{{ $client->name }}</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-700 text-xl font-bold">
                {{ strtoupper(substr($client->name, 0, 1)) }}
            </div>
            <div>
                <h2 class="page-title">{{ $client->name }}</h2>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-sm text-gray-500">{{ $client->email }}</span>
                    @if($client->status === 'active')
                        <span class="badge badge-success">Active</span>
                    @else
                        <span class="badge badge-warning">Suspended</span>
                    @endif
                    @switch($client->kyc_status)
                        @case('approved')
                            <span class="badge badge-success">KYC Approved</span>
                            @break
                        @case('pending')
                            <span class="badge badge-warning">KYC Pending</span>
                            @break
                        @case('rejected')
                            <span class="badge badge-danger">KYC Rejected</span>
                            @break
                        @default
                            <span class="badge badge-gray">KYC Not Submitted</span>
                    @endswitch
                </div>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('reseller.clients.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back
            </a>
            <a href="{{ route('reseller.clients.edit', $client) }}" class="btn-action-primary-reseller">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
            </a>
            <form method="POST" action="{{ route('reseller.clients.toggle-status', $client) }}" class="inline" onsubmit="return confirm('{{ $client->status === 'active' ? 'Suspend' : 'Activate' }} this client?')">
                @csrf
                @if($client->status === 'active')
                    <button type="submit" class="btn-danger">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                        Suspend
                    </button>
                @else
                    <button type="submit" class="btn-action-primary-reseller">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Activate
                    </button>
                @endif
            </form>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="stat-card">
            <div class="stat-icon bg-emerald-100">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <p class="stat-value">{{ format_currency($client->balance) }}</p>
                <p class="stat-label">Balance</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-blue-100">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
            </div>
            <div class="stat-content">
                <p class="stat-value">{{ $client->sipAccounts->count() }}</p>
                <p class="stat-label">SIP Accounts</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-purple-100">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div class="stat-content">
                <p class="stat-value text-base">{{ $client->rateGroup?->name ?? 'None' }}</p>
                <p class="stat-label">Rate Group</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-gray-100">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="stat-content">
                <p class="stat-value text-base">{{ $client->created_at->format('M d, Y') }}</p>
                <p class="stat-label">Created</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content (2/3) --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Account Details --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Account Details</h3>
                </div>
                <div class="detail-card-body">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-y-4 gap-x-6">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Billing Type</p>
                            <p class="text-sm font-medium text-gray-900 mt-1">
                                @if($client->billing_type === 'prepaid')
                                    <span class="badge badge-blue">Prepaid</span>
                                @else
                                    <span class="badge badge-purple">Postpaid</span>
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Credit Limit</p>
                            <p class="text-sm font-semibold text-gray-900 mt-1">{{ format_currency($client->credit_limit) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Max Channels</p>
                            <p class="text-sm font-semibold text-gray-900 mt-1">{{ $client->max_channels }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Rate Group</p>
                            <p class="text-sm font-medium text-gray-900 mt-1">{{ $client->rateGroup?->name ?? '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SIP Accounts --}}
            <div class="detail-card">
                <div class="detail-card-header flex items-center justify-between">
                    <h3 class="detail-card-title">SIP Accounts ({{ $client->sipAccounts->count() }})</h3>
                    @if($client->kyc_status === 'approved')
                        <a href="{{ route('reseller.sip-accounts.create', ['user_id' => $client->id]) }}" class="btn-action-primary-reseller">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add SIP Account
                        </a>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            KYC approval required
                        </span>
                    @endif
                </div>
                @if($client->sipAccounts->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="data-table data-table-compact">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Auth Type</th>
                                    <th>Caller ID</th>
                                    <th>Channels</th>
                                    <th>Status</th>
                                    <th style="text-align: center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($client->sipAccounts as $sip)
                                    <tr>
                                        <td class="font-mono font-semibold text-emerald-600">{{ $sip->username }}</td>
                                        <td class="text-gray-600">{{ ucfirst($sip->auth_type) }}</td>
                                        <td class="text-gray-500 text-xs">{{ $sip->caller_id_number ?: '—' }}</td>
                                        <td class="text-gray-600">{{ $sip->max_channels }}</td>
                                        <td>
                                            @if($sip->status === 'active')
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-warning">{{ ucfirst($sip->status) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="flex items-center justify-center gap-1">
                                                <a href="{{ route('reseller.sip-accounts.show', $sip) }}" class="action-icon" title="View">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                </a>
                                                <a href="{{ route('reseller.sip-accounts.edit', $sip) }}" class="action-icon" title="Edit">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="py-10 text-center">
                        <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-500">No SIP accounts</p>
                        @if($client->kyc_status === 'approved')
                            <a href="{{ route('reseller.sip-accounts.create', ['user_id' => $client->id]) }}" class="text-sm text-emerald-600 hover:text-emerald-500 font-medium">Add first SIP account</a>
                        @else
                            <p class="text-xs text-amber-600 mt-1">KYC approval required before adding SIP accounts</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Sidebar (1/3) --}}
        <div class="space-y-6">
            {{-- Quick Actions --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Quick Actions</h3>
                </div>
                <div class="detail-card-body space-y-2">
                    @if($client->kyc_status === 'approved')
                        <a href="{{ route('reseller.sip-accounts.create', ['user_id' => $client->id]) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 transition-colors group">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center group-hover:bg-emerald-200">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </div>
                            <span class="text-sm text-gray-700 group-hover:text-gray-900">Add SIP Account</span>
                        </a>
                    @else
                        <div class="flex items-center gap-3 px-3 py-2.5 rounded-lg opacity-50 cursor-not-allowed">
                            <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <span class="text-sm text-gray-400">Add SIP Account (KYC pending)</span>
                        </div>
                    @endif
                    <a href="{{ route('reseller.balance.create', ['user_id' => $client->id]) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 transition-colors group">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center group-hover:bg-blue-200">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <span class="text-sm text-gray-700 group-hover:text-gray-900">Top Up Balance</span>
                    </a>
                    <a href="{{ route('reseller.clients.edit', $client) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 transition-colors group">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center group-hover:bg-amber-200">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <span class="text-sm text-gray-700 group-hover:text-gray-900">Edit Client</span>
                    </a>
                </div>
            </div>

            {{-- KYC Details --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">KYC Details</h3>
                </div>
                <div class="detail-card-body">
                    @if($client->kycProfile)
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Type</span>
                                <span class="text-sm text-gray-900">{{ ucfirst($client->kycProfile->account_type) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Full Name</span>
                                <span class="text-sm font-medium text-gray-900">{{ $client->kycProfile->full_name }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Phone</span>
                                <span class="text-sm text-gray-900">{{ $client->kycProfile->phone }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">ID Type</span>
                                <span class="text-sm text-gray-900">{{ str_replace('_', ' ', ucfirst($client->kycProfile->id_type)) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">ID Number</span>
                                <span class="text-sm font-mono text-gray-900">{{ $client->kycProfile->id_number }}</span>
                            </div>
                            @if($client->kycProfile->city)
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500">Location</span>
                                    <span class="text-sm text-gray-900">{{ $client->kycProfile->city }}, {{ $client->kycProfile->country }}</span>
                                </div>
                            @endif
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Submitted</span>
                                <span class="text-sm text-gray-500">{{ $client->kycProfile->submitted_at?->format('M d, Y') ?? '—' }}</span>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <p class="text-sm text-gray-400">KYC not submitted</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-reseller-layout>
