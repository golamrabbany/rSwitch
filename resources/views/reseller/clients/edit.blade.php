<x-reseller-layout>
    <x-slot name="header">Edit Client</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row mb-6">
        <div>
            <h2 class="page-title">Edit Client</h2>
            <p class="page-subtitle">Update account details for {{ $client->name }}</p>
        </div>
        <div class="page-actions">
            <span class="badge {{ $client->status === 'active' ? 'badge-success' : 'badge-warning' }}">
                {{ ucfirst($client->status) }}
            </span>
        </div>
    </div>

    <form method="POST" action="{{ route('reseller.clients.update', $client) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
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
                            <input type="text" id="name" name="name" value="{{ old('name', $client->name) }}" required class="form-input" placeholder="Enter full name">
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" id="email" name="email" value="{{ old('email', $client->email) }}" required class="form-input" placeholder="Enter email address">
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div class="form-group">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" required class="form-input">
                                <option value="active" {{ old('status', $client->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="suspended" {{ old('status', $client->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
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

            {{-- Right Column - Billing & Limits --}}
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
                                <option value="prepaid" {{ old('billing_type', $client->billing_type) === 'prepaid' ? 'selected' : '' }}>Prepaid</option>
                                <option value="postpaid" {{ old('billing_type', $client->billing_type) === 'postpaid' ? 'selected' : '' }}>Postpaid</option>
                            </select>
                            <x-input-error :messages="$errors->get('billing_type')" class="mt-2" />
                        </div>

                        <div class="form-group">
                            <label for="rate_group_id" class="form-label">Tariff / Rate Group</label>
                            <select id="rate_group_id" name="rate_group_id" class="form-input">
                                <option value="">Select Rate Group</option>
                                @foreach ($rateGroups as $rateGroup)
                                    <option value="{{ $rateGroup->id }}" {{ old('rate_group_id', $client->rate_group_id) == $rateGroup->id ? 'selected' : '' }}>
                                        {{ $rateGroup->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('rate_group_id')" class="mt-2" />
                        </div>

                        <div class="form-group">
                            <label for="credit_limit" class="form-label">Credit Limit</label>
                            <div class="input-with-prefix">
                                <span class="input-prefix">$</span>
                                <input type="number" id="credit_limit" name="credit_limit" value="{{ old('credit_limit', $client->credit_limit) }}" step="0.01" min="0" class="form-input pl-8">
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
                            <input type="number" id="max_channels" name="max_channels" value="{{ old('max_channels', $client->max_channels) }}" min="1" class="form-input">
                            <p class="form-hint">Maximum concurrent calls allowed</p>
                            <x-input-error :messages="$errors->get('max_channels')" class="mt-2" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="form-actions">
            <a href="{{ route('reseller.clients.show', $client) }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary-reseller">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Save Changes
            </button>
        </div>
    </form>
</x-reseller-layout>
