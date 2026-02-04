<x-admin-layout>
    <x-slot name="header">Create SIP Account</x-slot>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('admin.sip-accounts.store') }}" class="space-y-6">
            @csrf

            <div class="bg-white shadow sm:rounded-lg p-6 space-y-6">
                <div>
                    <label for="user_id" class="block text-sm font-medium text-gray-700">Owner</label>
                    <select id="user_id" name="user_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Select User</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id', $selectedUserId) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }}) - {{ ucfirst($user->role) }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">Username (SIP ID)</label>
                        <input type="text" id="username" name="username" value="{{ old('username') }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                               placeholder="e.g. 100001">
                        <p class="mt-1 text-xs text-gray-500">Alphanumeric, dashes, and underscores. Used as SIP endpoint ID.</p>
                        <x-input-error :messages="$errors->get('username')" class="mt-2" />
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">SIP Password</label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input type="text" id="password" name="password" value="{{ old('password', App\Services\SipProvisioningService::generatePassword()) }}" required
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono">
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Minimum 12 characters. Auto-generated for security.</p>
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <label for="auth_type" class="block text-sm font-medium text-gray-700">Authentication Type</label>
                    <select id="auth_type" name="auth_type" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            x-data x-on:change="$dispatch('auth-changed', { type: $el.value })">
                        <option value="password" {{ old('auth_type') === 'password' ? 'selected' : '' }}>Password Only</option>
                        <option value="ip" {{ old('auth_type') === 'ip' ? 'selected' : '' }}>IP Only</option>
                        <option value="both" {{ old('auth_type') === 'both' ? 'selected' : '' }}>Password + IP</option>
                    </select>
                    <x-input-error :messages="$errors->get('auth_type')" class="mt-2" />
                </div>

                <div x-data="{ authType: '{{ old('auth_type', 'password') }}' }" x-on:auth-changed.window="authType = $event.detail.type"
                     x-show="authType === 'ip' || authType === 'both'" x-cloak>
                    <label for="allowed_ips" class="block text-sm font-medium text-gray-700">Allowed IPs</label>
                    <input type="text" id="allowed_ips" name="allowed_ips" value="{{ old('allowed_ips') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                           placeholder="192.168.1.100, 10.0.0.0/24">
                    <p class="mt-1 text-xs text-gray-500">Comma-separated IPs or CIDR ranges.</p>
                    <x-input-error :messages="$errors->get('allowed_ips')" class="mt-2" />
                </div>

                <hr class="border-gray-200">

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="caller_id_name" class="block text-sm font-medium text-gray-700">Caller ID Name</label>
                        <input type="text" id="caller_id_name" name="caller_id_name" value="{{ old('caller_id_name') }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <x-input-error :messages="$errors->get('caller_id_name')" class="mt-2" />
                    </div>
                    <div>
                        <label for="caller_id_number" class="block text-sm font-medium text-gray-700">Caller ID Number</label>
                        <input type="text" id="caller_id_number" name="caller_id_number" value="{{ old('caller_id_number') }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <x-input-error :messages="$errors->get('caller_id_number')" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="max_channels" class="block text-sm font-medium text-gray-700">Max Channels</label>
                        <input type="number" id="max_channels" name="max_channels" value="{{ old('max_channels', '2') }}" required min="1" max="100"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <x-input-error :messages="$errors->get('max_channels')" class="mt-2" />
                    </div>
                    <div>
                        <label for="codec_allow" class="block text-sm font-medium text-gray-700">Codecs</label>
                        <input type="text" id="codec_allow" name="codec_allow" value="{{ old('codec_allow', 'ulaw,alaw,g729') }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">Comma-separated codec list.</p>
                        <x-input-error :messages="$errors->get('codec_allow')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-x-3">
                <a href="{{ route('admin.sip-accounts.index') }}" class="text-sm font-semibold text-gray-900">Cancel</a>
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Create SIP Account
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
