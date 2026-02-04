<x-admin-layout>
    <x-slot name="header">Create User</x-slot>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
            @csrf

            <div class="bg-white shadow sm:rounded-lg p-6 space-y-6">
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                    <select id="role" name="role" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            x-data x-on:change="$dispatch('role-changed', { role: $el.value })">
                        <option value="reseller" {{ old('role') === 'reseller' ? 'selected' : '' }}>Reseller</option>
                        <option value="client" {{ old('role') === 'client' ? 'selected' : '' }}>Client</option>
                    </select>
                    <x-input-error :messages="$errors->get('role')" class="mt-2" />
                </div>

                <div x-data="{ role: '{{ old('role', 'reseller') }}' }" x-on:role-changed.window="role = $event.detail.role"
                     x-show="role === 'client'" x-cloak>
                    <label for="parent_id" class="block text-sm font-medium text-gray-700">Parent Reseller</label>
                    <select id="parent_id" name="parent_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
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
                    <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" id="password" name="password" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                </div>

                <hr class="border-gray-200">

                <div>
                    <label for="billing_type" class="block text-sm font-medium text-gray-700">Billing Type</label>
                    <select id="billing_type" name="billing_type" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="prepaid" {{ old('billing_type') === 'prepaid' ? 'selected' : '' }}>Prepaid</option>
                        <option value="postpaid" {{ old('billing_type') === 'postpaid' ? 'selected' : '' }}>Postpaid</option>
                    </select>
                    <x-input-error :messages="$errors->get('billing_type')" class="mt-2" />
                </div>

                <div>
                    <label for="rate_group_id" class="block text-sm font-medium text-gray-700">Rate Group</label>
                    <select id="rate_group_id" name="rate_group_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">None</option>
                        @foreach ($rateGroups as $rateGroup)
                            <option value="{{ $rateGroup->id }}" {{ old('rate_group_id') == $rateGroup->id ? 'selected' : '' }}>
                                {{ $rateGroup->name }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('rate_group_id')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div>
                        <label for="balance" class="block text-sm font-medium text-gray-700">Initial Balance</label>
                        <input type="number" id="balance" name="balance" value="{{ old('balance', '0') }}" step="0.01" min="0"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <x-input-error :messages="$errors->get('balance')" class="mt-2" />
                    </div>
                    <div>
                        <label for="credit_limit" class="block text-sm font-medium text-gray-700">Credit Limit</label>
                        <input type="number" id="credit_limit" name="credit_limit" value="{{ old('credit_limit', '0') }}" step="0.01" min="0"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <x-input-error :messages="$errors->get('credit_limit')" class="mt-2" />
                    </div>
                    <div>
                        <label for="max_channels" class="block text-sm font-medium text-gray-700">Max Channels</label>
                        <input type="number" id="max_channels" name="max_channels" value="{{ old('max_channels', '10') }}" min="1"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <x-input-error :messages="$errors->get('max_channels')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-x-3">
                <a href="{{ route('admin.users.index') }}" class="text-sm font-semibold text-gray-900">Cancel</a>
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Create User
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
