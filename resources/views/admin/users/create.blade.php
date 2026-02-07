<x-admin-layout>
    <x-slot name="header">Create User</x-slot>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
            @csrf

            <div class="card">
                <div class="card-header">
                    <h3 class="text-base font-semibold text-gray-900">Account Information</h3>
                </div>
                <div class="card-body space-y-5">
                    <div>
                        <label for="role" class="form-label">Role</label>
                        <select id="role" name="role" required class="form-select"
                                x-data x-on:change="$dispatch('role-changed', { role: $el.value })">
                            <option value="reseller" {{ old('role') === 'reseller' ? 'selected' : '' }}>Reseller</option>
                            <option value="client" {{ old('role') === 'client' ? 'selected' : '' }}>Client</option>
                        </select>
                        <x-input-error :messages="$errors->get('role')" class="mt-2" />
                    </div>

                    <div x-data="{ role: '{{ old('role', 'reseller') }}' }" x-on:role-changed.window="role = $event.detail.role"
                         x-show="role === 'client'" x-cloak>
                        <label for="parent_id" class="form-label">Parent Reseller</label>
                        <select id="parent_id" name="parent_id" class="form-select">
                            <option value="">Select Reseller</option>
                            @foreach ($resellers as $reseller)
                                <option value="{{ $reseller->id }}" {{ old('parent_id') == $reseller->id ? 'selected' : '' }}>
                                    {{ $reseller->name }} ({{ $reseller->email }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
                    </div>

                    <div>
                        <label for="name" class="form-label">Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required class="form-input">
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required class="form-input">
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" required class="form-input">
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>
                        <div>
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" required class="form-input">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="text-base font-semibold text-gray-900">Billing Settings</h3>
                </div>
                <div class="card-body space-y-5">
                    <div>
                        <label for="billing_type" class="form-label">Billing Type</label>
                        <select id="billing_type" name="billing_type" required class="form-select">
                            <option value="prepaid" {{ old('billing_type') === 'prepaid' ? 'selected' : '' }}>Prepaid</option>
                            <option value="postpaid" {{ old('billing_type') === 'postpaid' ? 'selected' : '' }}>Postpaid</option>
                        </select>
                        <x-input-error :messages="$errors->get('billing_type')" class="mt-2" />
                    </div>

                    <div>
                        <label for="rate_group_id" class="form-label">Rate Group</label>
                        <select id="rate_group_id" name="rate_group_id" class="form-select">
                            <option value="">None</option>
                            @foreach ($rateGroups as $rateGroup)
                                <option value="{{ $rateGroup->id }}" {{ old('rate_group_id') == $rateGroup->id ? 'selected' : '' }}>
                                    {{ $rateGroup->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('rate_group_id')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                        <div>
                            <label for="balance" class="form-label">Initial Balance</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 text-sm">$</span>
                                <input type="number" id="balance" name="balance" value="{{ old('balance', '0') }}" step="0.01" min="0"
                                       class="form-input pl-7">
                            </div>
                            <x-input-error :messages="$errors->get('balance')" class="mt-2" />
                        </div>
                        <div>
                            <label for="credit_limit" class="form-label">Credit Limit</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 text-sm">$</span>
                                <input type="number" id="credit_limit" name="credit_limit" value="{{ old('credit_limit', '0') }}" step="0.01" min="0"
                                       class="form-input pl-7">
                            </div>
                            <x-input-error :messages="$errors->get('credit_limit')" class="mt-2" />
                        </div>
                        <div>
                            <label for="max_channels" class="form-label">Max Channels</label>
                            <input type="number" id="max_channels" name="max_channels" value="{{ old('max_channels', '10') }}" min="1"
                                   class="form-input">
                            <x-input-error :messages="$errors->get('max_channels')" class="mt-2" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.users.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">
                    <svg class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Create User
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
