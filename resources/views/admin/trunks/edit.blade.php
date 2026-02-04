<x-admin-layout>
    <x-slot name="header">Edit Trunk: {{ $trunk->name }}</x-slot>

    <div class="max-w-3xl" x-data="{
        direction: '{{ old('direction', $trunk->direction) }}',
        cliMode: '{{ old('cli_mode', $trunk->cli_mode) }}',
        register: {{ old('register', $trunk->register) ? 'true' : 'false' }},
        healthCheck: {{ old('health_check', $trunk->health_check) ? 'true' : 'false' }}
    }">
        <form method="POST" action="{{ route('admin.trunks.update', $trunk) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="bg-white shadow sm:rounded-lg p-6 space-y-6">

                {{-- Section: Basic Information --}}
                <h3 class="text-base font-semibold text-gray-900">Basic Information</h3>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Trunk Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $trunk->name) }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <label for="provider" class="block text-sm font-medium text-gray-700">Provider</label>
                        <input type="text" id="provider" name="provider" value="{{ old('provider', $trunk->provider) }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <x-input-error :messages="$errors->get('provider')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <label for="direction" class="block text-sm font-medium text-gray-700">Direction</label>
                    <select id="direction" name="direction" required x-model="direction"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="outgoing">Outgoing</option>
                        <option value="incoming">Incoming</option>
                        <option value="both">Both (Incoming & Outgoing)</option>
                    </select>
                    <x-input-error :messages="$errors->get('direction')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="host" class="block text-sm font-medium text-gray-700">Host / IP</label>
                        <input type="text" id="host" name="host" value="{{ old('host', $trunk->host) }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <x-input-error :messages="$errors->get('host')" class="mt-2" />
                    </div>
                    <div>
                        <label for="port" class="block text-sm font-medium text-gray-700">Port</label>
                        <input type="number" id="port" name="port" value="{{ old('port', $trunk->port) }}" required min="1" max="65535"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <x-input-error :messages="$errors->get('port')" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div>
                        <label for="transport" class="block text-sm font-medium text-gray-700">Transport</label>
                        <select id="transport" name="transport" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="udp" {{ old('transport', $trunk->transport) === 'udp' ? 'selected' : '' }}>UDP</option>
                            <option value="tcp" {{ old('transport', $trunk->transport) === 'tcp' ? 'selected' : '' }}>TCP</option>
                            <option value="tls" {{ old('transport', $trunk->transport) === 'tls' ? 'selected' : '' }}>TLS</option>
                        </select>
                        <x-input-error :messages="$errors->get('transport')" class="mt-2" />
                    </div>
                    <div>
                        <label for="codec_allow" class="block text-sm font-medium text-gray-700">Codecs</label>
                        <input type="text" id="codec_allow" name="codec_allow" value="{{ old('codec_allow', $trunk->codec_allow) }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <x-input-error :messages="$errors->get('codec_allow')" class="mt-2" />
                    </div>
                    <div>
                        <label for="max_channels" class="block text-sm font-medium text-gray-700">Max Channels</label>
                        <input type="number" id="max_channels" name="max_channels" value="{{ old('max_channels', $trunk->max_channels) }}" required min="1" max="9999"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <x-input-error :messages="$errors->get('max_channels')" class="mt-2" />
                    </div>
                </div>

                {{-- Section: Status --}}
                <hr class="border-gray-200">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select id="status" name="status" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="active" {{ old('status', $trunk->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="disabled" {{ old('status', $trunk->status) === 'disabled' ? 'selected' : '' }}>Disabled</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Disabled trunks are removed from the Asterisk PJSIP config.</p>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </div>

                {{-- Section: Authentication (outgoing/both) --}}
                <template x-if="direction === 'outgoing' || direction === 'both'">
                    <div class="space-y-6">
                        <hr class="border-gray-200">
                        <h3 class="text-base font-semibold text-gray-900">Authentication & Registration</h3>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                                <input type="text" id="username" name="username" value="{{ old('username', $trunk->username) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <p class="mt-1 text-xs text-gray-500">Required for registration-based auth.</p>
                                <x-input-error :messages="$errors->get('username')" class="mt-2" />
                            </div>
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                                <input type="text" id="password" name="password" value="{{ old('password') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono"
                                       placeholder="Leave blank to keep current">
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <label class="flex items-center">
                                <input type="hidden" name="register" value="0">
                                <input type="checkbox" name="register" value="1" x-model="register"
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Enable SIP Registration</span>
                            </label>
                        </div>

                        <div x-show="register" x-cloak>
                            <label for="register_string" class="block text-sm font-medium text-gray-700">Custom Registration URI</label>
                            <input type="text" id="register_string" name="register_string" value="{{ old('register_string', $trunk->register_string) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                   placeholder="Leave blank for auto-generated">
                            <p class="mt-1 text-xs text-gray-500">Override auto-generated server_uri for non-standard providers.</p>
                            <x-input-error :messages="$errors->get('register_string')" class="mt-2" />
                        </div>

                        <div>
                            <label for="outgoing_priority" class="block text-sm font-medium text-gray-700">Outgoing Priority</label>
                            <input type="number" id="outgoing_priority" name="outgoing_priority" value="{{ old('outgoing_priority', $trunk->outgoing_priority) }}" required min="1" max="100"
                                   class="mt-1 block w-48 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <p class="mt-1 text-xs text-gray-500">Lower number = higher priority for outbound route selection.</p>
                            <x-input-error :messages="$errors->get('outgoing_priority')" class="mt-2" />
                        </div>
                    </div>
                </template>

                {{-- Hidden defaults for incoming-only --}}
                <template x-if="direction === 'incoming'">
                    <div>
                        <input type="hidden" name="username" value="">
                        <input type="hidden" name="password" value="">
                        <input type="hidden" name="register" value="0">
                        <input type="hidden" name="register_string" value="">
                        <input type="hidden" name="outgoing_priority" value="1">
                    </div>
                </template>

                {{-- Section: Dial String Manipulation (outgoing/both) --}}
                <template x-if="direction === 'outgoing' || direction === 'both'">
                    <div class="space-y-6">
                        <hr class="border-gray-200">
                        <h3 class="text-base font-semibold text-gray-900">Dial String Manipulation</h3>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="dial_pattern_match" class="block text-sm font-medium text-gray-700">Pattern Match</label>
                                <input type="text" id="dial_pattern_match" name="dial_pattern_match" value="{{ old('dial_pattern_match', $trunk->dial_pattern_match) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                       placeholder="e.g. ^0(\d+)$">
                                <x-input-error :messages="$errors->get('dial_pattern_match')" class="mt-2" />
                            </div>
                            <div>
                                <label for="dial_pattern_replace" class="block text-sm font-medium text-gray-700">Pattern Replace</label>
                                <input type="text" id="dial_pattern_replace" name="dial_pattern_replace" value="{{ old('dial_pattern_replace', $trunk->dial_pattern_replace) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                       placeholder="e.g. +44$1">
                                <x-input-error :messages="$errors->get('dial_pattern_replace')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                            <div>
                                <label for="dial_prefix" class="block text-sm font-medium text-gray-700">Dial Prefix</label>
                                <input type="text" id="dial_prefix" name="dial_prefix" value="{{ old('dial_prefix', $trunk->dial_prefix) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <x-input-error :messages="$errors->get('dial_prefix')" class="mt-2" />
                            </div>
                            <div>
                                <label for="dial_strip_digits" class="block text-sm font-medium text-gray-700">Strip Digits</label>
                                <input type="number" id="dial_strip_digits" name="dial_strip_digits" value="{{ old('dial_strip_digits', $trunk->dial_strip_digits) }}" required min="0" max="20"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <x-input-error :messages="$errors->get('dial_strip_digits')" class="mt-2" />
                            </div>
                            <div>
                                <label for="tech_prefix" class="block text-sm font-medium text-gray-700">Tech Prefix</label>
                                <input type="text" id="tech_prefix" name="tech_prefix" value="{{ old('tech_prefix', $trunk->tech_prefix) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <x-input-error :messages="$errors->get('tech_prefix')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Hidden defaults for incoming-only --}}
                <template x-if="direction === 'incoming'">
                    <div>
                        <input type="hidden" name="dial_pattern_match" value="">
                        <input type="hidden" name="dial_pattern_replace" value="">
                        <input type="hidden" name="dial_prefix" value="">
                        <input type="hidden" name="dial_strip_digits" value="0">
                        <input type="hidden" name="tech_prefix" value="">
                    </div>
                </template>

                {{-- Section: CLI Manipulation (outgoing/both) --}}
                <template x-if="direction === 'outgoing' || direction === 'both'">
                    <div class="space-y-6">
                        <hr class="border-gray-200">
                        <h3 class="text-base font-semibold text-gray-900">CLI / Caller ID Manipulation</h3>

                        <div>
                            <label for="cli_mode" class="block text-sm font-medium text-gray-700">CLI Mode</label>
                            <select id="cli_mode" name="cli_mode" required x-model="cliMode"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="passthrough">Passthrough (use original)</option>
                                <option value="override">Override (fixed number)</option>
                                <option value="prefix_strip">Prefix Strip/Add</option>
                                <option value="translate">Translate (regex)</option>
                                <option value="hide">Hide (anonymous)</option>
                            </select>
                            <x-input-error :messages="$errors->get('cli_mode')" class="mt-2" />
                        </div>

                        <div x-show="cliMode === 'override'" x-cloak>
                            <label for="cli_override_number" class="block text-sm font-medium text-gray-700">Override Number</label>
                            <input type="text" id="cli_override_number" name="cli_override_number" value="{{ old('cli_override_number', $trunk->cli_override_number) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                   placeholder="+44123456789">
                            <x-input-error :messages="$errors->get('cli_override_number')" class="mt-2" />
                        </div>

                        <div x-show="cliMode === 'prefix_strip'" x-cloak class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="cli_prefix_strip" class="block text-sm font-medium text-gray-700">Strip Digits from CLI</label>
                                <input type="number" id="cli_prefix_strip" name="cli_prefix_strip" value="{{ old('cli_prefix_strip', $trunk->cli_prefix_strip) }}" min="0" max="20"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <x-input-error :messages="$errors->get('cli_prefix_strip')" class="mt-2" />
                            </div>
                            <div>
                                <label for="cli_prefix_add" class="block text-sm font-medium text-gray-700">Add Prefix to CLI</label>
                                <input type="text" id="cli_prefix_add" name="cli_prefix_add" value="{{ old('cli_prefix_add', $trunk->cli_prefix_add) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <x-input-error :messages="$errors->get('cli_prefix_add')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Hidden defaults for incoming-only --}}
                <template x-if="direction === 'incoming'">
                    <div>
                        <input type="hidden" name="cli_mode" value="passthrough">
                        <input type="hidden" name="cli_override_number" value="">
                        <input type="hidden" name="cli_prefix_strip" value="0">
                        <input type="hidden" name="cli_prefix_add" value="">
                    </div>
                </template>

                {{-- Section: Incoming Settings (incoming/both) --}}
                <template x-if="direction === 'incoming' || direction === 'both'">
                    <div class="space-y-6">
                        <hr class="border-gray-200">
                        <h3 class="text-base font-semibold text-gray-900">Incoming Settings</h3>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="incoming_context" class="block text-sm font-medium text-gray-700">Incoming Context</label>
                                <input type="text" id="incoming_context" name="incoming_context" value="{{ old('incoming_context', $trunk->incoming_context) }}" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <x-input-error :messages="$errors->get('incoming_context')" class="mt-2" />
                            </div>
                            <div>
                                <label for="incoming_auth_type" class="block text-sm font-medium text-gray-700">Incoming Auth Type</label>
                                <select id="incoming_auth_type" name="incoming_auth_type" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="ip" {{ old('incoming_auth_type', $trunk->incoming_auth_type) === 'ip' ? 'selected' : '' }}>IP-based</option>
                                    <option value="registration" {{ old('incoming_auth_type', $trunk->incoming_auth_type) === 'registration' ? 'selected' : '' }}>Registration</option>
                                    <option value="both" {{ old('incoming_auth_type', $trunk->incoming_auth_type) === 'both' ? 'selected' : '' }}>Both</option>
                                </select>
                                <x-input-error :messages="$errors->get('incoming_auth_type')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <label for="incoming_ip_acl" class="block text-sm font-medium text-gray-700">IP ACL (additional IPs)</label>
                            <input type="text" id="incoming_ip_acl" name="incoming_ip_acl" value="{{ old('incoming_ip_acl', $trunk->incoming_ip_acl) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                   placeholder="1.2.3.4, 5.6.7.0/24">
                            <p class="mt-1 text-xs text-gray-500">Comma-separated IPs/CIDRs. The trunk host is automatically included.</p>
                            <x-input-error :messages="$errors->get('incoming_ip_acl')" class="mt-2" />
                        </div>
                    </div>
                </template>

                {{-- Hidden defaults for outgoing-only --}}
                <template x-if="direction === 'outgoing'">
                    <div>
                        <input type="hidden" name="incoming_context" value="from-trunk">
                        <input type="hidden" name="incoming_auth_type" value="ip">
                        <input type="hidden" name="incoming_ip_acl" value="">
                    </div>
                </template>

                {{-- Section: Health Monitoring --}}
                <hr class="border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Health Monitoring</h3>

                <div class="flex items-center gap-4">
                    <label class="flex items-center">
                        <input type="hidden" name="health_check" value="0">
                        <input type="checkbox" name="health_check" value="1" x-model="healthCheck"
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-700">Enable Health Monitoring</span>
                    </label>
                </div>

                <div x-show="healthCheck" x-cloak class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div>
                        <label for="health_check_interval" class="block text-sm font-medium text-gray-700">Check Interval (sec)</label>
                        <input type="number" id="health_check_interval" name="health_check_interval" value="{{ old('health_check_interval', $trunk->health_check_interval) }}" required min="10" max="3600"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <x-input-error :messages="$errors->get('health_check_interval')" class="mt-2" />
                    </div>
                    <div>
                        <label for="health_auto_disable_threshold" class="block text-sm font-medium text-gray-700">Auto-disable After</label>
                        <input type="number" id="health_auto_disable_threshold" name="health_auto_disable_threshold" value="{{ old('health_auto_disable_threshold', $trunk->health_auto_disable_threshold) }}" required min="1" max="100"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">Consecutive failures.</p>
                        <x-input-error :messages="$errors->get('health_auto_disable_threshold')" class="mt-2" />
                    </div>
                    <div>
                        <label for="health_asr_threshold" class="block text-sm font-medium text-gray-700">ASR Threshold (%)</label>
                        <input type="number" id="health_asr_threshold" name="health_asr_threshold" value="{{ old('health_asr_threshold', $trunk->health_asr_threshold) }}" step="0.01" min="0" max="100"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                               placeholder="Optional">
                        <x-input-error :messages="$errors->get('health_asr_threshold')" class="mt-2" />
                    </div>
                </div>

                {{-- Section: Notes --}}
                <hr class="border-gray-200">
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea id="notes" name="notes" rows="3"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                              placeholder="Optional notes about this trunk...">{{ old('notes', $trunk->notes) }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>
            </div>

            <div class="flex items-center justify-end gap-x-3">
                <a href="{{ route('admin.trunks.show', $trunk) }}" class="text-sm font-semibold text-gray-900">Cancel</a>
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Update Trunk
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
