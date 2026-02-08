@php
    if ($user->role === 'reseller') {
        $pageTitle = 'Edit Reseller';
        $backRoute = route('admin.users.index', ['role' => 'reseller']);
        $iconColor = 'from-emerald-500 to-teal-600';
        $roleLabel = 'Reseller';
    } elseif ($user->role === 'client') {
        $pageTitle = 'Edit Client';
        $backRoute = route('admin.users.index', ['role' => 'client']);
        $iconColor = 'from-sky-500 to-blue-600';
        $roleLabel = 'Client';
    } else {
        $pageTitle = 'Edit User';
        $backRoute = route('admin.users.index');
        $iconColor = 'from-indigo-500 to-purple-600';
        $roleLabel = 'User';
    }
@endphp

<x-admin-layout>
    <x-slot name="header">{{ $pageTitle }}</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row mb-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br {{ $iconColor }} flex items-center justify-center shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">{{ $pageTitle }}</h2>
                <p class="page-subtitle">Update account details for {{ $user->name }}</p>
            </div>
        </div>
        <div class="page-actions flex items-center gap-3">
            <span class="badge {{ $user->status === 'active' ? 'badge-success' : ($user->status === 'suspended' ? 'badge-warning' : 'badge-danger') }}">
                {{ ucfirst($user->status) }}
            </span>
            <a href="{{ $backRoute }}" class="btn-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to List
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Column - Account Information --}}
            <div class="space-y-6">
                {{-- Account Details Card --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Account Details</h3>
                        <p class="form-card-subtitle">Basic account information</p>
                    </div>
                    <div class="form-card-body">
                        <div class="form-group">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required class="form-input" placeholder="Enter full name">
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required class="form-input" placeholder="Enter email address">
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div class="form-group">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" required class="form-input">
                                <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="suspended" {{ old('status', $user->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                                <option value="disabled" {{ old('status', $user->status) === 'disabled' ? 'selected' : '' }}>Disabled</option>
                            </select>
                            <x-input-error :messages="$errors->get('status')" class="mt-2" />
                        </div>
                    </div>
                </div>

                {{-- Security Card --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Security</h3>
                        <p class="form-card-subtitle">Update account password</p>
                    </div>
                    <div class="form-card-body">
                        <div class="form-group">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" id="password" name="password" class="form-input" placeholder="Enter new password">
                            <p class="form-hint">Leave blank to keep current password</p>
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-input" placeholder="Confirm new password">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Middle Column - Billing & Limits --}}
            <div class="space-y-6">
                {{-- Billing Settings Card --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Billing Settings</h3>
                        <p class="form-card-subtitle">Configure billing and rates</p>
                    </div>
                    <div class="form-card-body">
                        <div class="form-group">
                            <label for="billing_type" class="form-label">Billing Type</label>
                            <select id="billing_type" name="billing_type" required class="form-input">
                                <option value="prepaid" {{ old('billing_type', $user->billing_type) === 'prepaid' ? 'selected' : '' }}>Prepaid</option>
                                <option value="postpaid" {{ old('billing_type', $user->billing_type) === 'postpaid' ? 'selected' : '' }}>Postpaid</option>
                            </select>
                            <x-input-error :messages="$errors->get('billing_type')" class="mt-2" />
                        </div>

                        <div class="form-group">
                            <label for="rate_group_id" class="form-label">Tariff / Rate Group</label>
                            <select id="rate_group_id" name="rate_group_id" class="form-input">
                                <option value="">Select Rate Group</option>
                                @foreach ($rateGroups as $rateGroup)
                                    <option value="{{ $rateGroup->id }}" {{ old('rate_group_id', $user->rate_group_id) == $rateGroup->id ? 'selected' : '' }}>
                                        {{ $rateGroup->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('rate_group_id')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="balance" class="form-label">Balance</label>
                                <div class="input-with-prefix">
                                    <span class="input-prefix">$</span>
                                    <input type="number" id="balance" name="balance" value="{{ old('balance', $user->balance) }}" step="0.01" class="form-input pl-8">
                                </div>
                                <x-input-error :messages="$errors->get('balance')" class="mt-2" />
                            </div>

                            <div class="form-group">
                                <label for="credit_limit" class="form-label">Credit Limit</label>
                                <div class="input-with-prefix">
                                    <span class="input-prefix">$</span>
                                    <input type="number" id="credit_limit" name="credit_limit" value="{{ old('credit_limit', $user->credit_limit) }}" step="0.01" min="0" class="form-input pl-8">
                                </div>
                                <x-input-error :messages="$errors->get('credit_limit')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Limits Card --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Limits & Restrictions</h3>
                        <p class="form-card-subtitle">Set usage limits</p>
                    </div>
                    <div class="form-card-body">
                        <div class="form-group">
                            <label for="max_channels" class="form-label">Max Channels</label>
                            <input type="number" id="max_channels" name="max_channels" value="{{ old('max_channels', $user->max_channels) }}" min="1" class="form-input">
                            <p class="form-hint">Maximum concurrent calls allowed</p>
                            <x-input-error :messages="$errors->get('max_channels')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="daily_spend_limit" class="form-label">Daily Spend Limit</label>
                                <div class="input-with-prefix">
                                    <span class="input-prefix">$</span>
                                    <input type="number" id="daily_spend_limit" name="daily_spend_limit" value="{{ old('daily_spend_limit', $user->daily_spend_limit) }}" step="0.01" min="0" class="form-input pl-8">
                                </div>
                                <p class="form-hint">Empty = unlimited</p>
                                <x-input-error :messages="$errors->get('daily_spend_limit')" class="mt-2" />
                            </div>

                            <div class="form-group">
                                <label for="daily_call_limit" class="form-label">Daily Call Limit</label>
                                <input type="number" id="daily_call_limit" name="daily_call_limit" value="{{ old('daily_call_limit', $user->daily_call_limit) }}" min="0" class="form-input">
                                <p class="form-hint">Empty = unlimited</p>
                                <x-input-error :messages="$errors->get('daily_call_limit')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="form-actions">
                    <a href="{{ route('admin.users.show', $user) }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Changes
                    </button>
                </div>
            </div>

            {{-- Right Column - Account Info Sidebar --}}
            <div class="space-y-6">
                {{-- Current Account Info --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-sm font-semibold text-slate-900">Account Info</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="divide-y divide-slate-100">
                            <div class="px-4 py-3 flex justify-between items-center">
                                <span class="text-sm text-slate-500">Account ID</span>
                                <span class="text-sm font-mono text-slate-900">#{{ $user->id }}</span>
                            </div>
                            <div class="px-4 py-3 flex justify-between items-center">
                                <span class="text-sm text-slate-500">Role</span>
                                <span class="badge {{ $user->role === 'reseller' ? 'badge-info' : 'badge-gray' }}">{{ ucfirst($user->role) }}</span>
                            </div>
                            <div class="px-4 py-3 flex justify-between items-center">
                                <span class="text-sm text-slate-500">KYC Status</span>
                                <span class="badge {{ $user->kyc_status === 'approved' ? 'badge-success' : ($user->kyc_status === 'pending' ? 'badge-warning' : 'badge-gray') }}">
                                    {{ ucfirst($user->kyc_status ?? 'None') }}
                                </span>
                            </div>
                            <div class="px-4 py-3 flex justify-between items-center">
                                <span class="text-sm text-slate-500">2FA</span>
                                <span class="badge {{ $user->two_fa_enabled ? 'badge-success' : 'badge-gray' }}">
                                    {{ $user->two_fa_enabled ? 'Enabled' : 'Disabled' }}
                                </span>
                            </div>
                            <div class="px-4 py-3 flex justify-between items-center">
                                <span class="text-sm text-slate-500">Created</span>
                                <span class="text-sm text-slate-900">{{ $user->created_at->format('M d, Y') }}</span>
                            </div>
                            @if($user->parent)
                            <div class="px-4 py-3 flex justify-between items-center">
                                <span class="text-sm text-slate-500">Parent</span>
                                <a href="{{ route('admin.users.show', $user->parent) }}" class="text-sm text-indigo-600 hover:text-indigo-700">
                                    {{ $user->parent->name }}
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Account Activity --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-sm font-semibold text-slate-900">Account Activity</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="divide-y divide-slate-100">
                            <div class="px-4 py-3 flex justify-between items-center">
                                <span class="text-sm text-slate-500">SIP Accounts</span>
                                <span class="text-sm font-medium text-slate-900">{{ $user->sipAccounts()->count() }}</span>
                            </div>
                            <div class="px-4 py-3 flex justify-between items-center">
                                <span class="text-sm text-slate-500">DIDs</span>
                                <span class="text-sm font-medium text-slate-900">{{ $user->dids()->count() }}</span>
                            </div>
                            <div class="px-4 py-3 flex justify-between items-center">
                                <span class="text-sm text-slate-500">Invoices</span>
                                <span class="text-sm font-medium text-slate-900">{{ $user->invoices()->count() }}</span>
                            </div>
                            @if($user->role === 'reseller')
                            <div class="px-4 py-3 flex justify-between items-center">
                                <span class="text-sm text-slate-500">Clients</span>
                                <span class="text-sm font-medium text-slate-900">{{ $user->children()->count() }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Quick Links --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-sm font-semibold text-slate-900">Quick Links</h3>
                    </div>
                    <div class="card-body space-y-2">
                        <a href="{{ route('admin.users.show', $user) }}" class="quick-action-link">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <span>View Profile</span>
                        </a>
                        <a href="{{ route('admin.sip-accounts.index', ['user_id' => $user->id]) }}" class="quick-action-link">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <span>SIP Accounts</span>
                        </a>
                        <a href="{{ route('admin.cdr.index', ['user_id' => $user->id]) }}" class="quick-action-link">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span>Call Records</span>
                        </a>
                        <a href="{{ route('admin.transactions.index', ['user_id' => $user->id]) }}" class="quick-action-link">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Transactions</span>
                        </a>
                    </div>
                </div>

                {{-- Tips Card --}}
                <div class="card bg-amber-50 border-amber-200">
                    <div class="card-body">
                        <div class="flex gap-3">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-amber-800">Important Notes</h4>
                                <ul class="mt-2 text-xs text-amber-700 space-y-1">
                                    <li>Changing status to "Disabled" will block all calls</li>
                                    <li>Balance changes here bypass the audit trail</li>
                                    <li>Use "Adjust Balance" on the profile for tracked changes</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
