<x-reseller-layout>
    <x-slot name="header">Edit SIP Account: {{ $sipAccount->username }}</x-slot>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('reseller.sip-accounts.update', $sipAccount) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="bg-white shadow sm:rounded-lg p-6 space-y-6">
                <div>
                    <label for="user_id" class="block text-sm font-medium text-gray-700">Owner</label>
                    <select id="user_id" name="user_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id', $sipAccount->user_id) == $user->id ? 'selected' : '' }}>
                                {{ $user->id === auth()->id() ? 'You (' . $user->name . ')' : $user->name . ' (' . $user->email . ')' }}
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

                <div x-data="{ status: '{{ old('status', $sipAccount->status) }}' }">
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <input type="hidden" name="status" :value="status">
                    <div class="mt-1 flex gap-2">
                        <button type="button" @click="status = 'active'" class="flex-1 py-2 rounded-lg border-2 text-sm font-medium transition-all" :class="status === 'active' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 text-gray-500 hover:border-gray-300'">Active</button>
                        <button type="button" @click="status = 'suspended'" class="flex-1 py-2 rounded-lg border-2 text-sm font-medium transition-all" :class="status === 'suspended' ? 'border-amber-500 bg-amber-50 text-amber-700' : 'border-gray-200 text-gray-500 hover:border-gray-300'">Suspended</button>
                        <button type="button" @click="status = 'disabled'" class="flex-1 py-2 rounded-lg border-2 text-sm font-medium transition-all" :class="status === 'disabled' ? 'border-red-500 bg-red-50 text-red-700' : 'border-gray-200 text-gray-500 hover:border-gray-300'">Disabled</button>
                    </div>
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
                        <input type="number" id="max_channels" name="max_channels" value="{{ old('max_channels', $sipAccount->max_channels) }}" required min="1" max="{{ $sipChannelAvailable + $sipAccount->max_channels }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">Available: {{ $sipChannelAvailable }} of {{ $client->max_channels }} ({{ $client->max_channels - $sipChannelAvailable - $sipAccount->max_channels }} used by other SIP accounts)</p>
                        <x-input-error :messages="$errors->get('max_channels')" class="mt-2" />
                    </div>
                    <div x-data="{
                        codecs: '{{ $availableCodecs }}'.split(',').map(s => s.trim()).filter(Boolean),
                        selected: '{{ old('codec_allow', $sipAccount->codec_allow) }}'.split(',').map(s => s.trim()).filter(Boolean),
                        toggle(val) {
                            const idx = this.selected.indexOf(val);
                            if (idx > -1) { this.selected.splice(idx, 1); }
                            else { this.selected.push(val); }
                        },
                        isSelected(val) { return this.selected.includes(val); },
                        get value() { return this.selected.join(','); }
                    }">
                        <label class="block text-sm font-medium text-gray-700">Codecs</label>
                        <input type="hidden" name="codec_allow" :value="value">
                        <div class="mt-1 flex flex-wrap gap-2">
                            <template x-for="codec in codecs" :key="codec">
                                <button type="button" @click="toggle(codec)"
                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border text-sm font-medium transition-all duration-150 cursor-pointer"
                                    :class="isSelected(codec)
                                        ? 'bg-indigo-50 border-indigo-300 text-indigo-700 ring-1 ring-indigo-200'
                                        : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50'">
                                    <svg x-show="isSelected(codec)" class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span class="font-mono" x-text="codec"></span>
                                </button>
                            </template>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Click to select/deselect codecs.</p>
                        <x-input-error :messages="$errors->get('codec_allow')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-x-3">
                <a href="{{ route('reseller.sip-accounts.show', $sipAccount) }}" class="text-sm font-semibold text-gray-900">Cancel</a>
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Update SIP Account
                </button>
            </div>
        </form>
    </div>
</x-reseller-layout>
