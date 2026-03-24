<x-admin-layout>
    <x-slot name="header">Edit Trunk</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Edit Trunk</h2>
                <p class="page-subtitle">{{ $trunk->name }}</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.trunks.show', $trunk) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Details
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.trunks.update', $trunk) }}" x-data="{
        direction: '{{ old('direction', $trunk->direction) }}',
        cliMode: '{{ old('cli_mode', $trunk->cli_mode) }}',
        register: {{ old('register', $trunk->register) ? 'true' : 'false' }},
        healthCheck: {{ old('health_check', $trunk->health_check) ? 'true' : 'false' }}
    }">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form - Left Side --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Basic Information --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Basic Information</h3>
                        <p class="form-card-subtitle">Trunk identification and direction</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="name" class="form-label">Trunk Name</label>
                                <input type="text" id="name" name="name" value="{{ old('name', $trunk->name) }}" required class="form-input">
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div class="form-group">
                                <label for="provider" class="form-label">Provider</label>
                                <input type="text" id="provider" name="provider" value="{{ old('provider', $trunk->provider) }}" required class="form-input">
                                <x-input-error :messages="$errors->get('provider')" class="mt-2" />
                            </div>

                            <div class="form-group">
                                <label for="direction" class="form-label">Direction</label>
                                <select id="direction" name="direction" required x-model="direction" class="form-input">
                                    <option value="outgoing">Outgoing</option>
                                    <option value="incoming">Incoming</option>
                                    <option value="both">Both (Incoming & Outgoing)</option>
                                </select>
                                <x-input-error :messages="$errors->get('direction')" class="mt-2" />
                            </div>

                            <div class="form-group">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" required class="form-input">
                                    <option value="active" {{ old('status', $trunk->status) === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="disabled" {{ old('status', $trunk->status) === 'disabled' ? 'selected' : '' }}>Disabled</option>
                                </select>
                                <p class="form-hint">Disabled trunks are removed from PJSIP config</p>
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Connection Settings --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Connection</h3>
                        <p class="form-card-subtitle">Host, transport and codec settings</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="host" class="form-label">Host / IP</label>
                                <input type="text" id="host" name="host" value="{{ old('host', $trunk->host) }}" required class="form-input">
                                <x-input-error :messages="$errors->get('host')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="port" class="form-label">Port</label>
                                <input type="number" id="port" name="port" value="{{ old('port', $trunk->port) }}" required min="1" max="65535" class="form-input">
                                <x-input-error :messages="$errors->get('port')" class="mt-2" />
                            </div>

                            <div class="form-group">
                                <label for="transport" class="form-label">Transport</label>
                                <select id="transport" name="transport" required class="form-input">
                                    <option value="udp" {{ old('transport', $trunk->transport) === 'udp' ? 'selected' : '' }}>UDP</option>
                                    <option value="tcp" {{ old('transport', $trunk->transport) === 'tcp' ? 'selected' : '' }}>TCP</option>
                                    <option value="tls" {{ old('transport', $trunk->transport) === 'tls' ? 'selected' : '' }}>TLS</option>
                                </select>
                                <x-input-error :messages="$errors->get('transport')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="max_channels" class="form-label">Max Channels</label>
                                <input type="number" id="max_channels" name="max_channels" value="{{ old('max_channels', $trunk->max_channels) }}" required min="1" max="9999" class="form-input">
                                <x-input-error :messages="$errors->get('max_channels')" class="mt-2" />
                            </div>

                            <div class="form-group md:col-span-2">
                                <label for="codec_allow" class="form-label">Codecs</label>
                                <input type="text" id="codec_allow" name="codec_allow" value="{{ old('codec_allow', $trunk->codec_allow) }}" required class="form-input font-mono">
                                <p class="form-hint">Comma-separated codec list in priority order</p>
                                <x-input-error :messages="$errors->get('codec_allow')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Authentication (outgoing/both) --}}
                <template x-if="direction === 'outgoing' || direction === 'both'">
                    <div class="form-card">
                        <div class="form-card-header">
                            <h3 class="form-card-title">Authentication</h3>
                            <p class="form-card-subtitle">Credentials for outgoing calls</p>
                        </div>
                        <div class="form-card-body">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-group">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" id="username" name="username" value="{{ old('username', $trunk->username) }}" class="form-input">
                                    <x-input-error :messages="$errors->get('username')" class="mt-2" />
                                </div>

                                <div class="form-group">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="text" id="password" name="password" value="{{ old('password') }}" class="form-input font-mono" placeholder="Leave blank to keep current">
                                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                                </div>

                                <div class="form-group md:col-span-2">
                                    <label class="flex items-center gap-2">
                                        <input type="hidden" name="register" value="0">
                                        <input type="checkbox" name="register" value="1" x-model="register" class="form-checkbox">
                                        <span class="text-sm text-gray-700">Enable SIP Registration</span>
                                    </label>
                                </div>

                                <div x-show="register" x-cloak class="form-group md:col-span-2">
                                    <label for="register_string" class="form-label">Custom Registration URI</label>
                                    <input type="text" id="register_string" name="register_string" value="{{ old('register_string', $trunk->register_string) }}" class="form-input" placeholder="Leave blank for auto-generated">
                                    <x-input-error :messages="$errors->get('register_string')" class="mt-2" />
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Incoming Settings (incoming/both) --}}
                <template x-if="direction === 'incoming' || direction === 'both'">
                    <div class="form-card">
                        <div class="form-card-header">
                            <h3 class="form-card-title">Incoming Settings</h3>
                            <p class="form-card-subtitle">Configuration for incoming calls</p>
                        </div>
                        <div class="form-card-body">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-group">
                                    <label for="incoming_context" class="form-label">Incoming Context</label>
                                    <input type="text" id="incoming_context" name="incoming_context" value="{{ old('incoming_context', $trunk->incoming_context) }}" required class="form-input font-mono">
                                    <x-input-error :messages="$errors->get('incoming_context')" class="mt-2" />
                                </div>

                                <div class="form-group">
                                    <label for="incoming_auth_type" class="form-label">Auth Type</label>
                                    <select id="incoming_auth_type" name="incoming_auth_type" required class="form-input">
                                        <option value="ip" {{ old('incoming_auth_type', $trunk->incoming_auth_type) === 'ip' ? 'selected' : '' }}>IP-based</option>
                                        <option value="registration" {{ old('incoming_auth_type', $trunk->incoming_auth_type) === 'registration' ? 'selected' : '' }}>Registration</option>
                                        <option value="both" {{ old('incoming_auth_type', $trunk->incoming_auth_type) === 'both' ? 'selected' : '' }}>Both</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('incoming_auth_type')" class="mt-2" />
                                </div>

                                <div class="form-group md:col-span-2">
                                    <label for="incoming_ip_acl" class="form-label">IP ACL</label>
                                    <input type="text" id="incoming_ip_acl" name="incoming_ip_acl" value="{{ old('incoming_ip_acl', $trunk->incoming_ip_acl) }}" class="form-input font-mono" placeholder="1.2.3.4, 5.6.7.0/24">
                                    <p class="form-hint">Comma-separated IPs/CIDRs</p>
                                    <x-input-error :messages="$errors->get('incoming_ip_acl')" class="mt-2" />
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Dial Manipulation (outgoing/both) --}}
                <template x-if="direction === 'outgoing' || direction === 'both'">
                    <div class="form-card">
                        <div class="form-card-header">
                            <h3 class="form-card-title">Dial Manipulation</h3>
                            <p class="form-card-subtitle">Number transformation rules</p>
                        </div>
                        <div class="form-card-body">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-group">
                                    <label for="dial_pattern_match" class="form-label">Pattern Match</label>
                                    <input type="text" id="dial_pattern_match" name="dial_pattern_match" value="{{ old('dial_pattern_match', $trunk->dial_pattern_match) }}" class="form-input font-mono" placeholder="^0(\d+)$">
                                    <x-input-error :messages="$errors->get('dial_pattern_match')" class="mt-2" />
                                </div>

                                <div class="form-group">
                                    <label for="dial_pattern_replace" class="form-label">Pattern Replace</label>
                                    <input type="text" id="dial_pattern_replace" name="dial_pattern_replace" value="{{ old('dial_pattern_replace', $trunk->dial_pattern_replace) }}" class="form-input font-mono" placeholder="+44$1">
                                    <x-input-error :messages="$errors->get('dial_pattern_replace')" class="mt-2" />
                                </div>

                                <div class="form-group">
                                    <label for="dial_prefix" class="form-label">Dial Prefix</label>
                                    <input type="text" id="dial_prefix" name="dial_prefix" value="{{ old('dial_prefix', $trunk->dial_prefix) }}" class="form-input font-mono">
                                    <x-input-error :messages="$errors->get('dial_prefix')" class="mt-2" />
                                </div>
                                <div class="form-group">
                                    <label for="dial_strip_digits" class="form-label">Strip Digits</label>
                                    <input type="number" id="dial_strip_digits" name="dial_strip_digits" value="{{ old('dial_strip_digits', $trunk->dial_strip_digits) }}" required min="0" max="20" class="form-input">
                                    <x-input-error :messages="$errors->get('dial_strip_digits')" class="mt-2" />
                                </div>

                                <div class="form-group md:col-span-2">
                                    <label for="tech_prefix" class="form-label">Tech Prefix</label>
                                    <input type="text" id="tech_prefix" name="tech_prefix" value="{{ old('tech_prefix', $trunk->tech_prefix) }}" class="form-input font-mono">
                                    <x-input-error :messages="$errors->get('tech_prefix')" class="mt-2" />
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- CLI Manipulation (outgoing/both) --}}
                <template x-if="direction === 'outgoing' || direction === 'both'">
                    <div class="form-card">
                        <div class="form-card-header">
                            <h3 class="form-card-title">Caller ID</h3>
                            <p class="form-card-subtitle">CLI manipulation settings</p>
                        </div>
                        <div class="form-card-body">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-group md:col-span-2">
                                    <label for="cli_mode" class="form-label">CLI Mode</label>
                                    <select id="cli_mode" name="cli_mode" required x-model="cliMode" class="form-input">
                                        <option value="passthrough">Passthrough</option>
                                        <option value="override">Override</option>
                                        <option value="prefix_strip">Prefix Strip/Add</option>
                                        <option value="translate">Translate</option>
                                        <option value="hide">Hide (Anonymous)</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('cli_mode')" class="mt-2" />
                                </div>

                                <div x-show="cliMode === 'override'" x-cloak class="form-group md:col-span-2">
                                    <label for="cli_override_number" class="form-label">Override Number</label>
                                    <input type="text" id="cli_override_number" name="cli_override_number" value="{{ old('cli_override_number', $trunk->cli_override_number) }}" class="form-input font-mono" placeholder="+44123456789">
                                    <x-input-error :messages="$errors->get('cli_override_number')" class="mt-2" />
                                </div>

                                <template x-if="cliMode === 'prefix_strip'">
                                    <div class="form-group">
                                        <label for="cli_prefix_strip" class="form-label">Strip Digits</label>
                                        <input type="number" id="cli_prefix_strip" name="cli_prefix_strip" value="{{ old('cli_prefix_strip', $trunk->cli_prefix_strip) }}" min="0" max="20" class="form-input">
                                        <x-input-error :messages="$errors->get('cli_prefix_strip')" class="mt-2" />
                                    </div>
                                </template>
                                <template x-if="cliMode === 'prefix_strip'">
                                    <div class="form-group">
                                        <label for="cli_prefix_add" class="form-label">Add Prefix</label>
                                        <input type="text" id="cli_prefix_add" name="cli_prefix_add" value="{{ old('cli_prefix_add', $trunk->cli_prefix_add) }}" class="form-input font-mono">
                                        <x-input-error :messages="$errors->get('cli_prefix_add')" class="mt-2" />
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Health Monitoring --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Health Monitoring</h3>
                        <p class="form-card-subtitle">Automatic trunk monitoring</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group md:col-span-2">
                                <label class="flex items-center gap-2">
                                    <input type="hidden" name="health_check" value="0">
                                    <input type="checkbox" name="health_check" value="1" x-model="healthCheck" class="form-checkbox">
                                    <span class="text-sm text-gray-700">Enable Health Monitoring</span>
                                </label>
                            </div>

                            <div x-show="healthCheck" x-cloak class="form-group">
                                <label for="health_check_interval" class="form-label">Check Interval (sec)</label>
                                <input type="number" id="health_check_interval" name="health_check_interval" value="{{ old('health_check_interval', $trunk->health_check_interval) }}" required min="10" max="3600" class="form-input">
                                <x-input-error :messages="$errors->get('health_check_interval')" class="mt-2" />
                            </div>

                            <div x-show="healthCheck" x-cloak class="form-group">
                                <label for="health_auto_disable_threshold" class="form-label">Auto-disable Threshold</label>
                                <input type="number" id="health_auto_disable_threshold" name="health_auto_disable_threshold" value="{{ old('health_auto_disable_threshold', $trunk->health_auto_disable_threshold) }}" required min="1" max="100" class="form-input">
                                <p class="form-hint">Consecutive failures before disable</p>
                                <x-input-error :messages="$errors->get('health_auto_disable_threshold')" class="mt-2" />
                            </div>

                            <div x-show="healthCheck" x-cloak class="form-group md:col-span-2">
                                <label for="health_asr_threshold" class="form-label">ASR Threshold (%)</label>
                                <input type="number" id="health_asr_threshold" name="health_asr_threshold" value="{{ old('health_asr_threshold', $trunk->health_asr_threshold) }}" step="0.01" min="0" max="100" class="form-input" placeholder="Optional">
                                <x-input-error :messages="$errors->get('health_asr_threshold')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Notes</h3>
                        <p class="form-card-subtitle">Additional information</p>
                    </div>
                    <div class="form-card-body">
                        <div class="form-group">
                            <textarea id="notes" name="notes" rows="3" class="form-input" placeholder="Optional notes about this trunk...">{{ old('notes', $trunk->notes) }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.trunks.show', $trunk) }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Changes
                    </button>
                </div>
            </div>

            {{-- Sidebar - Right Side --}}
            <div class="space-y-6">
                {{-- Account Info --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Trunk Info</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">{{ $trunk->name }}</p>
                                <span class="badge {{ $trunk->status === 'active' ? 'badge-success' : ($trunk->status === 'auto_disabled' ? 'badge-warning' : 'badge-danger') }}">
                                    {{ $trunk->status === 'auto_disabled' ? 'Auto-disabled' : ucfirst($trunk->status) }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-3 text-sm border-t border-gray-100 pt-4">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Trunk ID</span>
                                <span class="font-mono text-gray-900">#{{ $trunk->id }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Health</span>
                                @if($trunk->health_status === 'up')
                                    <span class="badge badge-success">Healthy</span>
                                @elseif($trunk->health_status === 'down')
                                    <span class="badge badge-danger">Down</span>
                                @else
                                    <span class="badge badge-gray">Unknown</span>
                                @endif
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Created</span>
                                <span class="text-gray-900">{{ $trunk->created_at->format('M d, Y') }}</span>
                            </div>
                            @if($trunk->routes->count() > 0)
                            <div class="flex justify-between">
                                <span class="text-gray-500">Routes</span>
                                <span class="font-medium text-gray-900">{{ $trunk->routes->count() }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Direction Types --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Direction Types</h3>
                    </div>
                    <div class="detail-card-body space-y-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-success">Outgoing</span>
                            </div>
                            <p class="text-xs text-gray-500">For making calls to external numbers.</p>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-info">Incoming</span>
                            </div>
                            <p class="text-xs text-gray-500">For receiving calls from external sources.</p>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-purple">Both</span>
                            </div>
                            <p class="text-xs text-gray-500">Bidirectional for inbound and outbound calls.</p>
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
                                <span>Leave password blank to keep current</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Changes trigger PJSIP reload</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Disabling removes trunk from rSwitch</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Active calls may be affected</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Hidden defaults based on direction --}}
        <template x-if="direction === 'incoming'">
            <div>
                <input type="hidden" name="username" value="">
                <input type="hidden" name="password" value="">
                <input type="hidden" name="register" value="0">
                <input type="hidden" name="register_string" value="">
                <input type="hidden" name="dial_pattern_match" value="">
                <input type="hidden" name="dial_pattern_replace" value="">
                <input type="hidden" name="dial_prefix" value="">
                <input type="hidden" name="dial_strip_digits" value="0">
                <input type="hidden" name="tech_prefix" value="">
                <input type="hidden" name="cli_mode" value="passthrough">
                <input type="hidden" name="cli_override_number" value="">
                <input type="hidden" name="cli_prefix_strip" value="0">
                <input type="hidden" name="cli_prefix_add" value="">
            </div>
        </template>
        <template x-if="direction === 'outgoing'">
            <div>
                <input type="hidden" name="incoming_context" value="from-trunk">
                <input type="hidden" name="incoming_auth_type" value="ip">
                <input type="hidden" name="incoming_ip_acl" value="">
            </div>
        </template>
    </form>
</x-admin-layout>
