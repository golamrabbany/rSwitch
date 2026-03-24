@php
    if ($user->role === 'reseller') {
        $pageTitle = 'Edit Reseller';
        $backRoute = route('admin.users.index', ['role' => 'reseller']);
    } elseif ($user->role === 'client') {
        $pageTitle = 'Edit Client';
        $backRoute = route('admin.users.index', ['role' => 'client']);
    } else {
        $pageTitle = 'Edit User';
        $backRoute = route('admin.users.index');
    }
@endphp

<x-admin-layout>
    <x-slot name="header">{{ $pageTitle }}</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">{{ $pageTitle }}</h2>
                <p class="page-subtitle">{{ $user->name }} — {{ $user->email }}</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ $backRoute }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form (2/3) --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Account Details --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Account Details</h3>
                        <p class="form-card-subtitle">Basic account information</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="form-input" placeholder="Enter full name">
                                <p class="form-hint">Legal name or company name</p>
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="form-input" placeholder="Enter email address">
                                <p class="form-hint">Used for login and notifications</p>
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" required class="form-input">
                                <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="suspended" {{ old('status', $user->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                                <option value="disabled" {{ old('status', $user->status) === 'disabled' ? 'selected' : '' }}>Disabled</option>
                            </select>
                            <p class="form-hint">Disabled accounts cannot make calls or login</p>
                            <x-input-error :messages="$errors->get('status')" class="mt-2" />
                        </div>
                    </div>
                </div>

                {{-- Security --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Security</h3>
                        <p class="form-card-subtitle">Update account password</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">New Password</label>
                                <input type="password" name="password" class="form-input" placeholder="Enter new password">
                                <p class="form-hint">Leave blank to keep current</p>
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="password_confirmation" class="form-input" placeholder="Confirm new password">
                                <p class="form-hint">Must match new password</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Billing & Limits --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Billing & Limits</h3>
                        <p class="form-card-subtitle">Rate plan, balance and usage limits</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Billing Type</label>
                                <select name="billing_type" required class="form-input">
                                    <option value="prepaid" {{ old('billing_type', $user->billing_type) === 'prepaid' ? 'selected' : '' }}>Prepaid</option>
                                    <option value="postpaid" {{ old('billing_type', $user->billing_type) === 'postpaid' ? 'selected' : '' }}>Postpaid</option>
                                </select>
                                <p class="form-hint">Prepaid deducts in real-time</p>
                                <x-input-error :messages="$errors->get('billing_type')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Rate Group</label>
                                <select name="rate_group_id" class="form-input">
                                    <option value="">Select Rate Group</option>
                                    @foreach ($rateGroups as $rateGroup)
                                        <option value="{{ $rateGroup->id }}" {{ old('rate_group_id', $user->rate_group_id) == $rateGroup->id ? 'selected' : '' }}>
                                            {{ $rateGroup->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="form-hint">Defines call pricing</p>
                                <x-input-error :messages="$errors->get('rate_group_id')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Balance</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">{{ currency_symbol() }}</span>
                                    <input type="number" name="balance" value="{{ old('balance', $user->balance) }}" step="0.01" class="form-input pl-8">
                                </div>
                                <p class="form-hint">Current account balance</p>
                                <x-input-error :messages="$errors->get('balance')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Credit Limit</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">{{ currency_symbol() }}</span>
                                    <input type="number" name="credit_limit" value="{{ old('credit_limit', $user->credit_limit) }}" step="0.01" min="0" class="form-input pl-8">
                                </div>
                                <p class="form-hint">For postpaid accounts only</p>
                                <x-input-error :messages="$errors->get('credit_limit')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Max Channels</label>
                                <input type="number" name="max_channels" value="{{ old('max_channels', $user->max_channels) }}" min="1" class="form-input">
                                <p class="form-hint">Maximum concurrent calls</p>
                                <x-input-error :messages="$errors->get('max_channels')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Daily Spend Limit</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">{{ currency_symbol() }}</span>
                                    <input type="number" name="daily_spend_limit" value="{{ old('daily_spend_limit', $user->daily_spend_limit) }}" step="0.01" min="0" class="form-input pl-8">
                                </div>
                                <p class="form-hint">Empty = unlimited</p>
                                <x-input-error :messages="$errors->get('daily_spend_limit')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.users.show', $user) }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Changes
                    </button>
                </div>
            </div>

            {{-- Sidebar (1/3) --}}
            <div class="space-y-6">
                {{-- Account Info --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Account Info</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Account ID</span>
                                <span class="text-sm font-mono text-gray-900">#{{ $user->id }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Role</span>
                                <span class="badge {{ $user->role === 'reseller' ? 'badge-purple' : 'badge-blue' }}">{{ ucfirst($user->role) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">KYC Status</span>
                                @switch($user->kyc_status)
                                    @case('approved') <span class="badge badge-success">Approved</span> @break
                                    @case('pending') <span class="badge badge-warning">Pending</span> @break
                                    @default <span class="badge badge-gray">{{ ucfirst($user->kyc_status ?? 'None') }}</span>
                                @endswitch
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">2FA</span>
                                <span class="badge {{ $user->two_fa_enabled ? 'badge-success' : 'badge-gray' }}">{{ $user->two_fa_enabled ? 'Enabled' : 'Disabled' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Created</span>
                                <span class="text-sm text-gray-900">{{ $user->created_at->format('M d, Y') }}</span>
                            </div>
                            @if($user->parent)
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Parent</span>
                                <a href="{{ route('admin.users.show', $user->parent) }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">{{ $user->parent->name }}</a>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Activity --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Activity</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">SIP Accounts</span>
                                <span class="text-sm font-medium text-gray-900">{{ $user->sipAccounts()->count() }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">DIDs</span>
                                <span class="text-sm font-medium text-gray-900">{{ $user->dids()->count() }}</span>
                            </div>
                            @if($user->role === 'reseller')
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Clients</span>
                                <span class="text-sm font-medium text-gray-900">{{ $user->children()->count() }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Quick Links --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Quick Links</h3>
                    </div>
                    <div class="detail-card-body space-y-2">
                        <a href="{{ route('admin.users.show', $user) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 transition-colors group">
                            <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center group-hover:bg-indigo-200">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </div>
                            <span class="text-sm text-gray-700 group-hover:text-gray-900">View Profile</span>
                        </a>
                        <a href="{{ route('admin.sip-accounts.index', ['user_id' => $user->id]) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 transition-colors group">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center group-hover:bg-blue-200">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </div>
                            <span class="text-sm text-gray-700 group-hover:text-gray-900">SIP Accounts</span>
                        </a>
                        <a href="{{ route('admin.cdr.index', ['user_id' => $user->id]) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 transition-colors group">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center group-hover:bg-emerald-200">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            </div>
                            <span class="text-sm text-gray-700 group-hover:text-gray-900">Call Records</span>
                        </a>
                    </div>
                </div>

                {{-- Warning --}}
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-amber-800">Important</p>
                            <ul class="text-xs text-amber-700 mt-1 space-y-1">
                                <li>Disabling blocks all calls</li>
                                <li>Balance changes bypass audit trail</li>
                                <li>Use profile page for tracked adjustments</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
