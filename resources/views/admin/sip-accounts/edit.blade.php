<x-admin-layout>
    <x-slot name="header">Edit SIP Account: {{ $sipAccount->username }}</x-slot>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('admin.sip-accounts.update', $sipAccount) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="bg-white shadow sm:rounded-lg p-6 space-y-6">
                <div>
                    <label for="user_id" class="block text-sm font-medium text-gray-700">Owner</label>
                    <select id="user_id" name="user_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id', $sipAccount->user_id) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }}) - {{ ucfirst($user->role) }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Username (SIP ID)</label>
                    <p class="mt-1 text-sm font-mono text-gray-900 bg-gray-50 rounded-md px-3 py-2">{{ $sipAccount->username }}</p>
                    <p class="mt-1 text-xs text-gray-500">Username cannot be changed after creation.</p>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">SIP Password</label>
                    <input type="text" id="password" name="password" value="{{ old('password') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono"
                           placeholder="Leave blank to keep current password">
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div>
                    <label for="auth_type" class="block text-sm font-medium text-gray-700">Authentication Type</label>
                    <select id="auth_type" name="auth_type" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            x-data x-on:change="$dispatch('auth-changed', { type: $el.value })">
                        <option value="password" {{ old('auth_type', $sipAccount->auth_type) === 'password' ? 'selected' : '' }}>Password Only</option>
                        <option value="ip" {{ old('auth_type', $sipAccount->auth_type) === 'ip' ? 'selected' : '' }}>IP Only</option>
                        <option value="both" {{ old('auth_type', $sipAccount->auth_type) === 'both' ? 'selected' : '' }}>Password + IP</option>
                    </select>
                    <x-input-error :messages="$errors->get('auth_type')" class="mt-2" />
                </div>

                <div x-data="{ authType: '{{ old('auth_type', $sipAccount->auth_type) }}' }" x-on:auth-changed.window="authType = $event.detail.type"
                     x-show="authType === 'ip' || authType === 'both'" x-cloak>
                    <label for="allowed_ips" class="block text-sm font-medium text-gray-700">Allowed IPs</label>
                    <input type="text" id="allowed_ips" name="allowed_ips" value="{{ old('allowed_ips', $sipAccount->allowed_ips) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                           placeholder="192.168.1.100, 10.0.0.0/24">
                    <x-input-error :messages="$errors->get('allowed_ips')" class="mt-2" />
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select id="status" name="status" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="active" {{ old('status', $sipAccount->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="suspended" {{ old('status', $sipAccount->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        <option value="disabled" {{ old('status', $sipAccount->status) === 'disabled' ? 'selected' : '' }}>Disabled</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Suspended/disabled accounts are deprovisioned from Asterisk.</p>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </div>

                <hr class="border-gray-200">

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="caller_id_name" class="block text-sm font-medium text-gray-700">Caller ID Name</label>
                        <input type="text" id="caller_id_name" name="caller_id_name" value="{{ old('caller_id_name', $sipAccount->caller_id_name) }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <x-input-error :messages="$errors->get('caller_id_name')" class="mt-2" />
                    </div>
                    <div>
                        <label for="caller_id_number" class="block text-sm font-medium text-gray-700">Caller ID Number</label>
                        <input type="text" id="caller_id_number" name="caller_id_number" value="{{ old('caller_id_number', $sipAccount->caller_id_number) }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <x-input-error :messages="$errors->get('caller_id_number')" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="max_channels" class="block text-sm font-medium text-gray-700">Max Channels</label>
                        <input type="number" id="max_channels" name="max_channels" value="{{ old('max_channels', $sipAccount->max_channels) }}" required min="1" max="100"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <x-input-error :messages="$errors->get('max_channels')" class="mt-2" />
                    </div>
                    <div>
                        <label for="codec_allow" class="block text-sm font-medium text-gray-700">Codecs</label>
                        <input type="text" id="codec_allow" name="codec_allow" value="{{ old('codec_allow', $sipAccount->codec_allow) }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <x-input-error :messages="$errors->get('codec_allow')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-x-3">
                <a href="{{ route('admin.sip-accounts.show', $sipAccount) }}" class="text-sm font-semibold text-gray-900">Cancel</a>
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Update SIP Account
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
