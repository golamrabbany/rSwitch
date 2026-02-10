@php
    $roleParam = request('role');
    if ($roleParam === 'reseller') {
        $pageTitle = 'Add Reseller';
        $defaultRole = 'reseller';
        $themeColor = 'emerald';
    } elseif ($roleParam === 'client') {
        $pageTitle = 'Add Client';
        $defaultRole = 'client';
        $themeColor = 'sky';
    } else {
        $pageTitle = 'Create User';
        $defaultRole = old('role', 'reseller');
        $themeColor = 'indigo';
    }
@endphp

<x-admin-layout>
    <x-slot name="header">{{ $pageTitle }}</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-{{ $themeColor }}-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-{{ $themeColor }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">{{ $pageTitle }}</h2>
                <p class="page-subtitle">Fill in the details to create a new account</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.users.index', $roleParam ? ['role' => $roleParam] : []) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to List
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.users.store') }}" x-data="{ role: '{{ $defaultRole }}' }">
        @csrf

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
                        @unless($roleParam)
                            <div class="form-group">
                                <label for="role" class="form-label">Account Type</label>
                                <select id="role" name="role" required class="form-input" x-model="role">
                                    <option value="reseller">Reseller</option>
                                    <option value="client">Client</option>
                                </select>
                                <x-input-error :messages="$errors->get('role')" class="mt-2" />
                            </div>
                        @else
                            <input type="hidden" name="role" value="{{ $roleParam }}">
                        @endunless

                        <div class="form-group" x-show="role === 'client'" x-cloak>
                            <label for="parent_id" class="form-label">
                                Parent Reseller
                                @unless(auth()->user()->isSuperAdmin())
                                    <span class="text-red-500">*</span>
                                @endunless
                            </label>
                            <select id="parent_id" name="parent_id" class="form-input" {{ auth()->user()->isSuperAdmin() ? '' : 'required' }}>
                                <option value="">Select Reseller</option>
                                @foreach ($resellers as $reseller)
                                    <option value="{{ $reseller->id }}" {{ old('parent_id') == $reseller->id ? 'selected' : '' }}>
                                        {{ $reseller->name }} ({{ $reseller->email }})
                                    </option>
                                @endforeach
                            </select>
                            @unless(auth()->user()->isSuperAdmin())
                                <p class="form-hint text-amber-600">Required: You must assign clients to one of your resellers</p>
                            @endunless
                            <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
                        </div>

                        <div class="form-group">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required class="form-input" placeholder="Enter full name">
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required class="form-input" placeholder="Enter email address">
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                    </div>
                </div>

                {{-- Security Card --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Security</h3>
                        <p class="form-card-subtitle">Set account password</p>
                    </div>
                    <div class="form-card-body">
                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" required class="form-input" placeholder="Enter password">
                            <p class="form-hint">Minimum 8 characters</p>
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" required class="form-input" placeholder="Confirm password">
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
                                <option value="prepaid" {{ old('billing_type') === 'prepaid' ? 'selected' : '' }}>Prepaid</option>
                                <option value="postpaid" {{ old('billing_type') === 'postpaid' ? 'selected' : '' }}>Postpaid</option>
                            </select>
                            <x-input-error :messages="$errors->get('billing_type')" class="mt-2" />
                        </div>

                        <div class="form-group">
                            <label for="rate_group_id" class="form-label">Tariff / Rate Group</label>
                            <select id="rate_group_id" name="rate_group_id" class="form-input">
                                <option value="">Select Rate Group</option>
                                @foreach ($rateGroups as $rateGroup)
                                    <option value="{{ $rateGroup->id }}" {{ old('rate_group_id') == $rateGroup->id ? 'selected' : '' }}>
                                        {{ $rateGroup->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('rate_group_id')" class="mt-2" />
                        </div>

                        <div class="form-group">
                            <label for="balance" class="form-label">Initial Balance</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">$</span>
                                <input type="number" id="balance" name="balance" value="{{ old('balance', '0') }}" step="0.01" min="0" class="form-input pl-8">
                            </div>
                            <x-input-error :messages="$errors->get('balance')" class="mt-2" />
                        </div>

                        <div class="form-group">
                            <label for="credit_limit" class="form-label">Credit Limit</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">$</span>
                                <input type="number" id="credit_limit" name="credit_limit" value="{{ old('credit_limit', '0') }}" step="0.01" min="0" class="form-input pl-8">
                            </div>
                            <x-input-error :messages="$errors->get('credit_limit')" class="mt-2" />
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
                            <input type="number" id="max_channels" name="max_channels" value="{{ old('max_channels', '10') }}" min="1" class="form-input" placeholder="10">
                            <p class="form-hint">Maximum concurrent calls allowed</p>
                            <x-input-error :messages="$errors->get('max_channels')" class="mt-2" />
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.users.index', $roleParam ? ['role' => $roleParam] : []) }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        {{ $pageTitle }}
                    </button>
                </div>
            </div>

            {{-- Right Column - Quick Info --}}
            <div class="space-y-6">
                {{-- Quick Info --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Quick Info</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3 p-3 bg-indigo-50 rounded-lg mb-4">
                            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-indigo-800">Account Creation</p>
                                <p class="text-xs text-indigo-600">New account will be active immediately</p>
                            </div>
                        </div>

                        <div class="space-y-3 text-sm">
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Email verification sent</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Can login immediately</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>KYC submission required</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Account Types --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Account Types</h3>
                    </div>
                    <div class="detail-card-body space-y-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-purple">Reseller</span>
                            </div>
                            <p class="text-xs text-gray-500">Can manage their own clients, set rates, and resell services.</p>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-blue">Client</span>
                            </div>
                            <p class="text-xs text-gray-500">End user account. Must belong to a reseller. Can have SIP accounts.</p>
                        </div>
                    </div>
                </div>

                {{-- Billing Types --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Billing Types</h3>
                    </div>
                    <div class="detail-card-body space-y-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-info">Prepaid</span>
                            </div>
                            <p class="text-xs text-gray-500">Must have positive balance to make calls. Real-time balance deduction.</p>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-warning">Postpaid</span>
                            </div>
                            <p class="text-xs text-gray-500">Can use up to credit limit. Billed at end of period.</p>
                        </div>
                    </div>
                </div>

                {{-- Tips --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Tips</h3>
                    </div>
                    <div class="detail-card-body">
                        <ul class="text-xs text-gray-600 space-y-2">
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Clients must have a parent reseller</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Rate group defines call pricing</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>KYC must be approved for full access</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
