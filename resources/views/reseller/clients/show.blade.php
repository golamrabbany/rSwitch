<x-reseller-layout>
    <x-slot name="header">{{ $client->name }}</x-slot>

    <div x-data="{ sipModal: false, topupModal: false }" x-cloak>

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

            {{-- Contact --}}
            @if($client->phone || $client->contact_email || $client->address)
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Contact</h3>
                </div>
                <div class="detail-card-body">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-y-4 gap-x-6">
                        @if($client->contact_email)
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wide">Contact Email</p>
                                <p class="text-sm font-medium text-gray-900 mt-1">{{ $client->contact_email }}</p>
                            </div>
                        @endif
                        @if($client->phone)
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wide">Phone</p>
                                <p class="text-sm font-medium text-gray-900 mt-1">{{ $client->phone }}</p>
                            </div>
                        @endif
                        @if($client->alt_phone)
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wide">Alt Phone</p>
                                <p class="text-sm font-medium text-gray-900 mt-1">{{ $client->alt_phone }}</p>
                            </div>
                        @endif
                    </div>
                    @if($client->address)
                        <div class="mt-4 pt-3 border-t border-gray-100">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Address</p>
                            <p class="text-sm font-medium text-gray-900 mt-1">
                                {{ $client->address }}
                                @if($client->city || $client->state)
                                    <br>{{ collect([$client->city, $client->state])->filter()->implode(', ') }}
                                @endif
                                @if($client->country || $client->zip_code)
                                    <br>{{ collect([$client->country, $client->zip_code])->filter()->implode(' ') }}
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Company Details --}}
            @if($client->company_name || $client->company_email || $client->company_website)
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Company Details</h3>
                </div>
                <div class="detail-card-body">
                    <div class="grid grid-cols-2 gap-y-4 gap-x-6">
                        @if($client->company_name)
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wide">Company Name</p>
                                <p class="text-sm font-medium text-gray-900 mt-1">{{ $client->company_name }}</p>
                            </div>
                        @endif
                        @if($client->company_email)
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wide">Company Email</p>
                                <p class="text-sm font-medium text-gray-900 mt-1">{{ $client->company_email }}</p>
                            </div>
                        @endif
                        @if($client->company_website)
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wide">Website</p>
                                <p class="text-sm font-medium text-gray-900 mt-1"><a href="{{ $client->company_website }}" target="_blank" class="text-emerald-600 hover:text-emerald-700">{{ $client->company_website }}</a></p>
                            </div>
                        @endif
                        @if($client->notes)
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wide">Notes</p>
                                <p class="text-sm text-gray-700 mt-1">{{ $client->notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- KYC Information --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">KYC Information</h3>
                </div>
                <div class="detail-card-body">
                    @if($client->kycProfile)
                        @php $kyc = $client->kycProfile; @endphp
                        {{-- Status --}}
                        <div class="p-3 rounded-lg mb-4 {{ $client->kyc_status === 'approved' ? 'bg-emerald-50' : ($client->kyc_status === 'pending' ? 'bg-amber-50' : ($client->kyc_status === 'rejected' ? 'bg-red-50' : 'bg-gray-50')) }}">
                            <div class="flex items-center gap-2">
                                @if($client->kyc_status === 'approved')
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span class="text-sm font-semibold text-emerald-700">Approved</span>
                                @elseif($client->kyc_status === 'pending')
                                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span class="text-sm font-semibold text-amber-700">Pending Review</span>
                                @elseif($client->kyc_status === 'rejected')
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span class="text-sm font-semibold text-red-700">Rejected</span>
                                @endif
                            </div>
                        </div>

                        {{-- Details --}}
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-y-4 gap-x-6">
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wide">Account Type</p>
                                <p class="text-sm font-medium text-gray-900 mt-1">{{ ucfirst($kyc->account_type ?? '—') }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wide">Full Name</p>
                                <p class="text-sm font-medium text-gray-900 mt-1">{{ $kyc->full_name ?? '—' }}</p>
                            </div>
                            @if($kyc->contact_person)
                                <div>
                                    <p class="text-xs text-gray-400 uppercase tracking-wide">Contact Person</p>
                                    <p class="text-sm font-medium text-gray-900 mt-1">{{ $kyc->contact_person }}</p>
                                </div>
                            @endif
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wide">Phone</p>
                                <p class="text-sm font-medium text-gray-900 mt-1">{{ $kyc->phone ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wide">ID Type</p>
                                <p class="text-sm font-medium text-gray-900 mt-1">{{ $kyc->id_type ? ucfirst(str_replace('_', ' ', $kyc->id_type)) : '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wide">ID Number</p>
                                <p class="text-sm font-mono font-medium text-gray-900 mt-1">{{ $kyc->id_number ?? '—' }}</p>
                            </div>
                        </div>

                        {{-- Address --}}
                        @if($kyc->address_line1)
                            <div class="mt-4 pt-3 border-t border-gray-100">
                                <p class="text-xs text-gray-400 uppercase tracking-wide">KYC Address</p>
                                <p class="text-sm font-medium text-gray-900 mt-1">
                                    {{ $kyc->address_line1 }}
                                    @if($kyc->address_line2) <br>{{ $kyc->address_line2 }} @endif
                                    @if($kyc->city || $kyc->state)
                                        <br>{{ collect([$kyc->city, $kyc->state])->filter()->implode(', ') }}
                                    @endif
                                    @if($kyc->country || $kyc->postal_code)
                                        <br>{{ collect([$kyc->country, $kyc->postal_code])->filter()->implode(' ') }}
                                    @endif
                                </p>
                            </div>
                        @endif

                        {{-- Rejection reason --}}
                        @if($client->kyc_status === 'rejected' && $client->kyc_rejected_reason)
                            <div class="mt-4 p-3 bg-red-50 rounded-lg border border-red-200">
                                <p class="text-xs font-medium text-red-700">Rejection Reason</p>
                                <p class="text-sm text-red-600 mt-1">{{ $client->kyc_rejected_reason }}</p>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-6">
                            <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <p class="text-sm text-gray-500">KYC not submitted yet</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- SIP Accounts --}}
            <div class="detail-card">
                <div class="detail-card-header flex items-center justify-between">
                    <h3 class="detail-card-title">SIP Accounts ({{ $client->sipAccounts->count() }})</h3>
                    @if($client->sipAccounts->isNotEmpty())
                        <a href="{{ route('reseller.sip-accounts.index', ['user_id' => $client->id]) }}" class="text-sm text-emerald-600 hover:text-emerald-500 font-medium">View All</a>
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
                                @foreach($client->sipAccounts->take(10) as $sip)
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
                            <button @click="sipModal = true" class="text-sm text-emerald-600 hover:text-emerald-500 font-medium">Add first SIP account</button>
                        @else
                            <p class="text-xs text-amber-600 mt-1">KYC approval required before adding SIP accounts</p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Recent Top-Ups --}}
            <div class="detail-card">
                <div class="detail-card-header flex items-center justify-between">
                    <h3 class="detail-card-title">Recent Top-Ups</h3>
                    <a href="{{ route('reseller.transactions.index', ['user_id' => $client->id]) }}" class="text-sm text-emerald-600 hover:text-emerald-500 font-medium">View All</a>
                </div>
                @if($recentTopups->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="data-table data-table-compact">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th class="text-right">Amount</th>
                                    <th>Source</th>
                                    <th>Remarks</th>
                                    <th class="text-right">Balance After</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentTopups as $txn)
                                    <tr>
                                        <td class="whitespace-nowrap text-gray-500">{{ $txn->created_at->format('M d, Y h:i A') }}</td>
                                        <td class="text-right font-mono font-semibold text-emerald-600">+{{ format_currency($txn->amount) }}</td>
                                        <td class="text-gray-600">{{ $txn->source ? ucwords(str_replace('_', ' ', $txn->source)) : '—' }}</td>
                                        <td class="text-gray-500 text-xs">{{ $txn->remarks ?: ($txn->description ?: '—') }}</td>
                                        <td class="text-right font-mono text-gray-600">{{ format_currency($txn->balance_after) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="py-8 text-center">
                        <p class="text-sm text-gray-400">No top-ups yet</p>
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
                        <button @click="sipModal = true" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 transition-colors group text-left">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center group-hover:bg-emerald-200">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </div>
                            <span class="text-sm text-gray-700 group-hover:text-gray-900">Add SIP Account</span>
                        </button>
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
                    <button @click="topupModal = true" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 transition-colors group text-left">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center group-hover:bg-blue-200">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <span class="text-sm text-gray-700 group-hover:text-gray-900">Top Up Balance</span>
                    </button>
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

    {{-- Add SIP Account Modal --}}
    @if($client->kyc_status === 'approved')
    <div x-show="sipModal" x-cloak class="relative z-50" @keydown.escape.window="sipModal = false">
        <div x-show="sipModal"
             x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4" @click="sipModal = false">
                <div x-show="sipModal"
                     x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                     class="relative transform overflow-hidden rounded-xl bg-white shadow-2xl transition-all w-full max-w-2xl" @click.stop>

                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Add SIP Account</h3>
                                <p class="text-sm text-gray-500">{{ $client->name }}</p>
                            </div>
                        </div>
                        <button @click="sipModal = false" type="button" class="rounded-lg p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('reseller.sip-accounts.store') }}">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $client->id }}">

                        <div class="px-6 py-5 space-y-4 max-h-[70vh] overflow-y-auto">
                            {{-- Account --}}
                            @php
                                $sipPrefix = \App\Models\SystemSetting::get('sip_pin_prefix', '');
                                $sipMinLen = \App\Models\SystemSetting::get('sip_pin_min_length', 4);
                                $sipMaxLen = \App\Models\SystemSetting::get('sip_pin_max_length', 10);
                            @endphp
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label">Username (PIN)</label>
                                    <div class="relative">
                                        @if($sipPrefix)
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-mono font-medium">{{ $sipPrefix }}</span>
                                        @endif
                                        <input type="text" name="username" required placeholder="{{ $sipPrefix ? str_repeat('0', $sipMinLen) : 'e.g. 200001' }}" class="form-input font-mono" style="{{ $sipPrefix ? 'padding-left: ' . (strlen($sipPrefix) * 0.6 + 1) . 'rem;' : '' }}">
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">{{ $sipPrefix ? "Prefix '{$sipPrefix}' + {$sipMinLen}-{$sipMaxLen} digits" : "Numeric, {$sipMinLen}-{$sipMaxLen} digits" }}</p>
                                    @if(!empty(auth()->user()->sip_ranges))
                                        @foreach(auth()->user()->sip_ranges as $range)
                                            <p class="text-xs text-indigo-500 mt-0.5 font-medium">Range: {{ $range['start'] }} — {{ $range['end'] }}</p>
                                        @endforeach
                                    @else
                                        <p class="text-xs text-emerald-500 mt-0.5">Any number allowed (no range restriction)</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="form-label">Password</label>
                                    <input type="text" name="password" required value="{{ \Illuminate\Support\Str::random(16) }}" class="form-input font-mono">
                                    <p class="text-xs text-gray-400 mt-1">Min 6 characters</p>
                                </div>
                            </div>

                            <input type="hidden" name="auth_type" value="password">

                            {{-- Caller ID --}}
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label">Caller ID Name</label>
                                    <input type="text" name="caller_id_name" required value="{{ $client->name }}" class="form-input">
                                </div>
                                <div>
                                    <label class="form-label">Caller ID Number</label>
                                    <input type="text" name="caller_id_number" required placeholder="e.g. 01XXXXXXXXX" class="form-input">
                                </div>
                            </div>

                            {{-- Settings --}}
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label">Max Channels</label>
                                    @php $defaultSipChannels = \App\Models\SystemSetting::get('default_max_channels', 10); @endphp
                                    <input type="number" name="max_channels" required value="{{ min($defaultSipChannels, $sipChannelAvailable) }}" min="1" max="{{ $sipChannelAvailable }}" class="form-input" {{ $sipChannelAvailable <= 0 ? 'disabled' : '' }}>
                                    <p class="text-xs text-gray-400 mt-1">Available: {{ $sipChannelAvailable }} of {{ $client->max_channels }}</p>
                                    <x-input-error :messages="$errors->get('max_channels')" class="mt-1" />
                                </div>
                                <div x-data="{
                                    codecs: ['ulaw', 'alaw', 'g729'],
                                    selected: ['ulaw'],
                                    toggle(val) {
                                        const idx = this.selected.indexOf(val);
                                        if (idx > -1 && this.selected.length > 1) { this.selected.splice(idx, 1); }
                                        else if (idx === -1) { this.selected.push(val); }
                                    },
                                    isSelected(val) { return this.selected.includes(val); },
                                    get value() { return this.selected.join(','); }
                                }">
                                    <label class="form-label">Codec</label>
                                    <input type="hidden" name="codec_allow" :value="value">
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="codec in codecs" :key="codec">
                                            <button type="button" @click="toggle(codec)"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border text-sm font-medium transition-all cursor-pointer"
                                                :class="isSelected(codec)
                                                    ? 'bg-emerald-50 border-emerald-300 text-emerald-700 ring-1 ring-emerald-200'
                                                    : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50'">
                                                <svg x-show="isSelected(codec)" class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                <span class="font-mono" x-text="codec"></span>
                                            </button>
                                        </template>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">Click to select/deselect</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-xl">
                            <button type="button" @click="sipModal = false" class="btn-secondary">Cancel</button>
                            <button type="submit" class="btn-primary-reseller">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Create SIP Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Top Up Balance Modal --}}
    <div x-show="topupModal" x-cloak class="relative z-50" @keydown.escape.window="topupModal = false">
        <div x-show="topupModal"
             x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4" @click="topupModal = false">
                <div x-show="topupModal"
                     x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                     class="relative transform overflow-hidden rounded-xl bg-white shadow-2xl transition-all w-full max-w-md" @click.stop>

                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Top Up Balance</h3>
                                <p class="text-sm text-gray-500">{{ $client->name }} — Current: {{ format_currency($client->balance) }}</p>
                            </div>
                        </div>
                        <button @click="topupModal = false" type="button" class="rounded-lg p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Reseller Balance Info --}}
                    <div class="px-6 pt-4">
                        <div class="flex items-center justify-between p-3 rounded-lg {{ auth()->user()->balance > 0 ? 'bg-emerald-50 border border-emerald-200' : 'bg-red-50 border border-red-200' }}">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 {{ auth()->user()->balance > 0 ? 'text-emerald-500' : 'text-red-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                <span class="text-sm {{ auth()->user()->balance > 0 ? 'text-emerald-700' : 'text-red-700' }}">Your Available Balance</span>
                            </div>
                            <span class="text-sm font-mono font-bold {{ auth()->user()->balance > 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ format_currency(auth()->user()->balance) }}</span>
                        </div>
                        @if(auth()->user()->balance <= 0)
                            <p class="text-xs text-red-600 mt-2 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                Insufficient balance. Please contact admin to recharge your account.
                            </p>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('reseller.balance.store') }}">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $client->id }}">

                        <div class="px-6 py-5 space-y-4">
                            <div>
                                <label class="form-label">Amount ({{ currency_symbol() }})</label>
                                <input type="number" name="amount" required step="0.01" min="0.01" max="{{ auth()->user()->balance > 0 ? auth()->user()->balance : 0 }}" placeholder="0.00" class="form-input font-mono text-lg" {{ auth()->user()->balance <= 0 ? 'disabled' : '' }}>
                                <p class="text-xs text-gray-400 mt-1">Amount will be deducted from your balance and credited to client</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label">Source</label>
                                    <select name="source" class="form-input">
                                        <option value="">Select source...</option>
                                        <option value="cash">Cash</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="bkash">bKash</option>
                                        <option value="nagad">Nagad</option>
                                        <option value="rocket">Rocket</option>
                                        <option value="upay">Upay</option>
                                        <option value="credit_card">Credit Card</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Remarks</label>
                                    <input type="text" name="remarks" placeholder="e.g., TXN-12345" class="form-input">
                                    <p class="text-xs text-gray-400 mt-1">Reference number or short note</p>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="2" placeholder="Additional details (optional)" class="form-input"></textarea>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-xl">
                            <button type="button" @click="topupModal = false" class="btn-secondary">Cancel</button>
                            <button type="submit" class="btn-primary-reseller" {{ auth()->user()->balance <= 0 ? 'disabled' : '' }} style="{{ auth()->user()->balance <= 0 ? 'opacity: 0.5; cursor: not-allowed;' : '' }}">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Transfer to Client
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    </div>{{-- close x-data --}}
</x-reseller-layout>
