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
            <div class="lg:col-span-2 space-y-6">

                {{-- Login Information --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Login Information</h3>
                        <p class="form-card-subtitle">Credentials and account status</p>
                    </div>
                    <div class="form-card-body">
                        @if($user->role === 'client')
                        <div class="form-group" x-data="resellerSearch()" @click.away="open = false">
                            <label class="form-label">Parent Reseller</label>
                            <div class="relative">
                                <input type="hidden" name="parent_id" :value="selectedId">
                                <div class="relative">
                                    <input type="text" x-model="query" @focus="open = true" @click="open = true" @input="open = true; selectedId = ''" placeholder="Search reseller..." class="form-input pr-8" autocomplete="off">
                                    <button type="button" x-show="query" x-cloak @click="selectedId = ''; query = ''" class="absolute right-2 top-1/2 -translate-y-1/2 p-0.5 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-400 hover:text-gray-600">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <div x-show="open" x-cloak class="absolute z-20 mt-1 w-full bg-white rounded-lg border border-gray-200 shadow-lg max-h-48 overflow-y-auto">
                                    @if(auth()->user()->isSuperAdmin())
                                        <button type="button" @click="selectedId = ''; query = 'Direct (No Reseller)'; open = false" class="w-full px-3 py-2 text-left text-sm hover:bg-indigo-50 flex items-center gap-2 border-b border-gray-100">
                                            <span class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            </span>
                                            <span class="font-medium text-indigo-600">Direct (No Reseller)</span>
                                        </button>
                                    @endif
                                    <template x-for="r in filtered" :key="r.id">
                                        <button type="button" @click="selectedId = String(r.id); query = r.name; open = false" class="w-full px-3 py-2 text-left text-sm hover:bg-indigo-50 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <div class="w-6 h-6 rounded-full bg-sky-100 flex items-center justify-center flex-shrink-0">
                                                    <span class="text-xs font-medium text-sky-600" x-text="r.name.substring(0, 1).toUpperCase()"></span>
                                                </div>
                                                <span class="font-medium text-gray-900" x-text="r.name"></span>
                                            </div>
                                            <span class="text-xs text-gray-400" x-text="r.email"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <p class="form-hint">Select a reseller or choose "Direct" for Super Admin managed client</p>
                            <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
                        </div>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="form-group">
                                <label class="form-label">Account Name</label>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="form-input" placeholder="Enter full name">
                                <p class="form-hint">Display name for this account</p>
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" value="{{ old('username', $user->username) }}" required class="form-input" placeholder="e.g. 09647123456">
                                <p class="form-hint">Login identifier</p>
                                <x-input-error :messages="$errors->get('username')" class="mt-2" />
                            </div>
                            <div class="form-group" x-data="{ status: '{{ old('status', $user->status) }}' }">
                                <label class="form-label">Status</label>
                                <input type="hidden" name="status" :value="status">
                                <div class="flex gap-1.5">
                                    <button type="button" @click="status = 'active'" class="flex-1 py-2 rounded-lg border-2 text-xs font-medium transition-all" :class="status === 'active' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 text-gray-500 hover:border-gray-300'">Active</button>
                                    <button type="button" @click="status = 'suspended'" class="flex-1 py-2 rounded-lg border-2 text-xs font-medium transition-all" :class="status === 'suspended' ? 'border-amber-500 bg-amber-50 text-amber-700' : 'border-gray-200 text-gray-500 hover:border-gray-300'">Suspended</button>
                                    <button type="button" @click="status = 'disabled'" class="flex-1 py-2 rounded-lg border-2 text-xs font-medium transition-all" :class="status === 'disabled' ? 'border-red-500 bg-red-50 text-red-700' : 'border-gray-200 text-gray-500 hover:border-gray-300'">Disabled</button>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">New Password</label>
                                <input type="password" name="password" class="form-input" placeholder="Leave blank to keep current">
                                <p class="form-hint">Minimum 8 characters</p>
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="password_confirmation" class="form-input" placeholder="Confirm new password">
                                <p class="form-hint">Re-enter to confirm new password</p>
                            </div>
                        </div>
                        <div class="form-group mt-4">
                            <label class="form-label">Email <span class="text-gray-400 font-normal">(optional)</span></label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-input" placeholder="user@example.com">
                            <p class="form-hint">For OTP, password reset, and email notifications. Leave blank if not available.</p>
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                    </div>
                </div>

                {{-- SIP Account Ranges (Reseller only) --}}
                @if($user->role === 'reseller')
                @php
                    $sipPrefix = \App\Models\SystemSetting::get('sip_pin_prefix', '');
                    $existingRanges = $user->sip_ranges ?: [['start' => '', 'end' => '']];
                @endphp
                <div class="form-card" x-data="{ ranges: {{ json_encode($existingRanges) }} }">
                    <div class="form-card-header">
                        <div class="flex items-center justify-between w-full">
                            <div>
                                <h3 class="form-card-title">SIP Account Ranges</h3>
                                <p class="form-card-subtitle">Allowed number ranges for SIP accounts</p>
                            </div>
                            <button type="button" @click="ranges.push({ start: '', end: '' })" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Range
                            </button>
                        </div>
                    </div>
                    <div class="form-card-body space-y-3">
                        <template x-for="(range, index) in ranges" :key="index">
                            <div class="flex items-center gap-3">
                                <div class="flex-1">
                                    <div class="relative">
                                        @if($sipPrefix)
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-mono text-sm">{{ $sipPrefix }}</span>
                                        @endif
                                        <input type="text" :name="'sip_ranges[' + index + '][start]'" x-model="range.start" class="form-input font-mono" style="{{ $sipPrefix ? 'padding-left: ' . (strlen($sipPrefix) * 0.6 + 1) . 'rem;' : '' }}" placeholder="Start">
                                    </div>
                                </div>
                                <span class="text-gray-400 text-sm">to</span>
                                <div class="flex-1">
                                    <div class="relative">
                                        @if($sipPrefix)
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-mono text-sm">{{ $sipPrefix }}</span>
                                        @endif
                                        <input type="text" :name="'sip_ranges[' + index + '][end]'" x-model="range.end" class="form-input font-mono" style="{{ $sipPrefix ? 'padding-left: ' . (strlen($sipPrefix) * 0.6 + 1) . 'rem;' : '' }}" placeholder="End">
                                    </div>
                                </div>
                                <button type="button" @click="ranges.length > 1 ? ranges.splice(index, 1) : (ranges[0] = { start: '', end: '' })" class="p-2 rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </template>
                        <p class="text-xs text-gray-400">Leave empty to allow any PIN.</p>
                    </div>
                </div>
                @endif

                {{-- Billing Info --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Billing Info</h3>
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
                                <p class="form-hint">Prepaid deducts in real-time, postpaid uses credit</p>
                                <x-input-error :messages="$errors->get('billing_type')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Rate Group</label>
                                <select name="rate_group_id" class="form-input">
                                    <option value="">Select Rate Group</option>
                                    @foreach ($rateGroups as $rateGroup)
                                        <option value="{{ $rateGroup->id }}" {{ old('rate_group_id', $user->rate_group_id) == $rateGroup->id ? 'selected' : '' }}>{{ $rateGroup->name }}</option>
                                    @endforeach
                                </select>
                                <p class="form-hint">Defines per-minute call pricing</p>
                                <x-input-error :messages="$errors->get('rate_group_id')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Balance</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">{{ currency_symbol() }}</span>
                                    <input type="number" name="balance" value="{{ old('balance', $user->balance) }}" step="0.01" class="form-input pl-8">
                                </div>
                                <p class="form-hint">Current account balance (use Adjust Balance for tracked changes)</p>
                                <x-input-error :messages="$errors->get('balance')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Credit Limit</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">{{ currency_symbol() }}</span>
                                    <input type="number" name="credit_limit" value="{{ old('credit_limit', $user->credit_limit) }}" step="0.01" min="0" class="form-input pl-8">
                                </div>
                                <p class="form-hint">Maximum negative balance allowed for postpaid</p>
                                <x-input-error :messages="$errors->get('credit_limit')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Max Channels</label>
                                <input type="number" name="max_channels" value="{{ old('max_channels', $user->max_channels) }}" min="1" class="form-input">
                                <p class="form-hint">Maximum simultaneous calls allowed</p>
                                <x-input-error :messages="$errors->get('max_channels')" class="mt-2" />
                            </div>
                            @if(auth()->user()->isSuperAdmin())
                            <div class="form-group" x-data="{ autoBal: {{ old('auto_recharge_enabled', $user->auto_recharge_enabled) ? 'true' : 'false' }} }">
                                <label class="form-label">Auto Balance</label>
                                <label class="inline-flex items-center gap-2 cursor-pointer" style="min-height:42px">
                                    <input type="hidden" name="auto_recharge_enabled" value="0">
                                    <input type="checkbox" name="auto_recharge_enabled" value="1" x-model="autoBal" class="rounded border-gray-300 text-indigo-600">
                                    <span class="text-sm text-gray-700">Enable auto top-up</span>
                                </label>
                                <p class="form-hint">Super admin only — auto top-up ৳50–200 (bKash/Nagad) at the trigger.</p>
                                <div x-show="autoBal" x-transition class="mt-2" style="{{ old('auto_recharge_enabled', $user->auto_recharge_enabled) ? '' : 'display:none' }}">
                                    <label class="form-label">Auto-recharge when balance ≤</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">{{ currency_symbol() }}</span>
                                        <input type="number" name="low_balance_threshold" value="{{ old('low_balance_threshold', $user->low_balance_threshold) }}" step="0.01" min="0" class="form-input pl-8">
                                    </div>
                                    <p class="form-hint">Top-up fires once balance reaches this amount or lower.</p>
                                    <x-input-error :messages="$errors->get('low_balance_threshold')" class="mt-2" />
                                </div>
                            </div>
                            @endif
                            <div class="form-group">
                                <label class="form-label">Daily Spend Limit</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">{{ currency_symbol() }}</span>
                                    <input type="number" name="daily_spend_limit" value="{{ old('daily_spend_limit', $user->daily_spend_limit) }}" step="0.01" min="0" class="form-input pl-8">
                                </div>
                                <p class="form-hint">Empty = unlimited</p>
                                <x-input-error :messages="$errors->get('daily_spend_limit')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Daily Call Limit</label>
                                <input type="number" name="daily_call_limit" value="{{ old('daily_call_limit', $user->daily_call_limit) }}" min="0" class="form-input">
                                <p class="form-hint">Empty = unlimited</p>
                                <x-input-error :messages="$errors->get('daily_call_limit')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Contact --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Contact</h3>
                        <p class="form-card-subtitle">Phone numbers and address</p>
                    </div>
                    <div class="form-card-body space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="form-group">
                                <label class="form-label">Contact Email</label>
                                <input type="email" name="contact_email" value="{{ old('contact_email', $user->contact_email) }}" class="form-input" placeholder="contact@example.com">
                                <p class="form-hint">Separate from login email, for correspondence</p>
                                <x-input-error :messages="$errors->get('contact_email')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-input" placeholder="e.g. +8801712345678">
                                <p class="form-hint">Primary contact number with country code</p>
                                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Alternative Phone</label>
                                <input type="text" name="alt_phone" value="{{ old('alt_phone', $user->alt_phone) }}" class="form-input" placeholder="Optional">
                                <p class="form-hint">Secondary or mobile number</p>
                                <x-input-error :messages="$errors->get('alt_phone')" class="mt-2" />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" value="{{ old('address', $user->address) }}" class="form-input" placeholder="Street address">
                                <p class="form-hint">Street address, building, floor</p>
                                <x-input-error :messages="$errors->get('address')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">City</label>
                                <input type="text" name="city" value="{{ old('city', $user->city) }}" class="form-input" placeholder="City">
                                <p class="form-hint">City or district</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="form-group">
                                <label class="form-label">State</label>
                                <input type="text" name="state" value="{{ old('state', $user->state) }}" class="form-input" placeholder="State / Division">
                                <p class="form-hint">State, province or division</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Country</label>
                                <input type="text" name="country" value="{{ old('country', $user->country) }}" class="form-input" placeholder="Country">
                                <p class="form-hint">Full country name</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Zip Code</label>
                                <input type="text" name="zip_code" value="{{ old('zip_code', $user->zip_code) }}" class="form-input" placeholder="Zip / Postal">
                                <p class="form-hint">Postal or zip code</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Company Details --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Company Details</h3>
                        <p class="form-card-subtitle">Business information (optional)</p>
                    </div>
                    <div class="form-card-body space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Company Name</label>
                                <input type="text" name="company_name" value="{{ old('company_name', $user->company_name) }}" class="form-input" placeholder="e.g. ABC Telecom Ltd">
                                <p class="form-hint">Registered business or trading name</p>
                                <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Company Email</label>
                                <input type="email" name="company_email" value="{{ old('company_email', $user->company_email) }}" class="form-input" placeholder="info@company.com">
                                <p class="form-hint">Official business email address</p>
                                <x-input-error :messages="$errors->get('company_email')" class="mt-2" />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Website</label>
                                <input type="text" name="company_website" value="{{ old('company_website', $user->company_website) }}" class="form-input" placeholder="e.g. https://example.com">
                                <p class="form-hint">Company website URL</p>
                                <x-input-error :messages="$errors->get('company_website')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Notes</label>
                                <input type="text" name="notes" value="{{ old('notes', $user->notes) }}" class="form-input" placeholder="Internal notes...">
                                <p class="form-hint">Internal remarks, not visible to user</p>
                                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
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

            {{-- Sidebar --}}
            <div class="space-y-6">
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
            </div>
        </div>
    </form>

    @if($user->role === 'client')
    @push('scripts')
    <script>
    var _resellers = @json($resellers->map(function ($r) { return ['id' => $r->id, 'name' => $r->name, 'email' => $r->email ?? '']; }));

    function resellerSearch() {
        var currentParentId = '{{ $user->parent_id ?? '' }}';
        var currentParentIsReseller = @json(optional($user->parent)->role === 'reseller');
        var oldVal = '{{ old('parent_id') }}';

        // Pre-fill: if old() exists use it; else use current parent_id when parent is reseller; otherwise treat as Direct.
        var initialId = oldVal !== '' ? oldVal : (currentParentIsReseller ? currentParentId : '');
        var initialName = '';
        if (initialId) {
            var found = _resellers.find(function (r) { return String(r.id) === String(initialId); });
            if (found) initialName = found.name;
        } else if (!currentParentIsReseller) {
            initialName = 'Direct (No Reseller)';
        }

        return {
            open: false,
            query: initialName,
            selectedId: initialId,
            filtered: _resellers,
            init() {
                this.$watch('query', function (val) {
                    if (!val) { this.filtered = _resellers; return; }
                    var q = val.toLowerCase();
                    this.filtered = _resellers.filter(function (r) {
                        return r.name.toLowerCase().indexOf(q) > -1 || (r.email || '').toLowerCase().indexOf(q) > -1;
                    });
                }.bind(this));
            }
        };
    }
    </script>
    @endpush
    @endif
</x-admin-layout>
