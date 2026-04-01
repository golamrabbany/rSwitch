<x-admin-layout>
    <x-slot name="header">Create Trunk</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Create Trunk</h2>
                <p class="page-subtitle">Add a new SIP trunk connection</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.trunks.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to List
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.trunks.store') }}" x-data="{
        direction: '{{ old('direction', 'outgoing') }}',
        cliMode: '{{ old('cli_mode', 'passthrough') }}',
        register: {{ old('register') ? 'true' : 'false' }},
        healthCheck: {{ old('health_check', '1') ? 'true' : 'false' }}
    }">
        @csrf

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
                                <input type="text" id="name" name="name" value="{{ old('name') }}" required class="form-input" placeholder="e.g. VoIP-Provider-1">
                                <p class="form-hint">Unique name to identify this trunk</p>
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div class="form-group">
                                <label for="provider" class="form-label">Provider</label>
                                <input type="text" id="provider" name="provider" value="{{ old('provider') }}" required class="form-input" placeholder="e.g. Twilio, Telnyx">
                                <p class="form-hint">SIP trunk provider company name</p>
                                <x-input-error :messages="$errors->get('provider')" class="mt-2" />
                            </div>

                            <div class="form-group">
                                <label for="direction" class="form-label">Direction</label>
                                <select id="direction" name="direction" required x-model="direction" class="form-input">
                                    <option value="outgoing">Outgoing</option>
                                    <option value="incoming">Incoming</option>
                                    <option value="both">Both (Incoming & Outgoing)</option>
                                </select>
                                <p class="form-hint">Outgoing for sending, Incoming for receiving calls</p>
                                <x-input-error :messages="$errors->get('direction')" class="mt-2" />
                            </div>

                            <div class="form-group">
                                <label for="rate_group_id" class="form-label">Provider Rate Group</label>
                                <select id="rate_group_id" name="rate_group_id" class="form-input">
                                    <option value="">— No Rate Group —</option>
                                    @foreach ($rateGroups as $rg)
                                        <option value="{{ $rg->id }}" {{ old('rate_group_id') == $rg->id ? 'selected' : '' }}>{{ $rg->name }}</option>
                                    @endforeach
                                </select>
                                <p class="form-hint">Provider's rate card — used for trunk cost in P&L reports</p>
                                <x-input-error :messages="$errors->get('rate_group_id')" class="mt-2" />
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
                                <input type="text" id="host" name="host" value="{{ old('host') }}" required class="form-input" placeholder="sip.provider.com">
                                <p class="form-hint">Provider's SIP server hostname or IP address</p>
                                <x-input-error :messages="$errors->get('host')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="port" class="form-label">Port</label>
                                <input type="number" id="port" name="port" value="{{ old('port', '5060') }}" required min="1" max="65535" class="form-input">
                                <p class="form-hint">Default: 5060 (UDP/TCP), 5061 (TLS)</p>
                                <x-input-error :messages="$errors->get('port')" class="mt-2" />
                            </div>

                            <div class="form-group">
                                <label for="transport" class="form-label">Transport</label>
                                <select id="transport" name="transport" required class="form-input">
                                    <option value="udp" {{ old('transport') === 'udp' ? 'selected' : '' }}>UDP</option>
                                    <option value="tcp" {{ old('transport') === 'tcp' ? 'selected' : '' }}>TCP</option>
                                    <option value="tls" {{ old('transport') === 'tls' ? 'selected' : '' }}>TLS</option>
                                </select>
                                <p class="form-hint">UDP is standard, TLS for encrypted connections</p>
                                <x-input-error :messages="$errors->get('transport')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="max_channels" class="form-label">Max Channels</label>
                                <input type="number" id="max_channels" name="max_channels" value="{{ old('max_channels', '30') }}" required min="1" max="9999" class="form-input">
                                <p class="form-hint">Maximum concurrent calls on this trunk</p>
                                <x-input-error :messages="$errors->get('max_channels')" class="mt-2" />
                            </div>

                            <div class="form-group md:col-span-2" x-data="{
                                codecs: ['ulaw', 'alaw', 'g729', 'g722', 'opus', 'gsm', 'ilbc'],
                                selected: '{{ old('codec_allow', 'ulaw,alaw,g729') }}'.split(',').map(s => s.trim()).filter(Boolean),
                                toggle(val) {
                                    const idx = this.selected.indexOf(val);
                                    if (idx > -1) { this.selected.splice(idx, 1); }
                                    else { this.selected.push(val); }
                                },
                                isSelected(val) { return this.selected.includes(val); },
                                get value() { return this.selected.join(','); }
                            }">
                                <label class="form-label">Codecs</label>
                                <input type="hidden" name="codec_allow" :value="value">
                                <div class="flex flex-wrap gap-2">
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
                                <p class="form-hint mt-2">Click to select/deselect. At least one required.</p>
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
                                    <input type="text" id="username" name="username" value="{{ old('username') }}" class="form-input">
                                    <p class="form-hint">SIP username provided by the trunk provider</p>
                                    <x-input-error :messages="$errors->get('username')" class="mt-2" />
                                </div>

                                <div class="form-group">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="text" id="password" name="password" value="{{ old('password') }}" class="form-input font-mono">
                                    <p class="form-hint">SIP password for authentication</p>
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
                                    <input type="text" id="register_string" name="register_string" value="{{ old('register_string') }}" class="form-input" placeholder="Leave blank for auto-generated">
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
                                <input type="hidden" name="incoming_context" value="from-trunk">

                                <div class="form-group">
                                    <label for="incoming_auth_type" class="form-label">Auth Type</label>
                                    <select id="incoming_auth_type" name="incoming_auth_type" required class="form-input">
                                        <option value="ip" {{ old('incoming_auth_type', 'ip') === 'ip' ? 'selected' : '' }}>IP-based</option>
                                        <option value="registration" {{ old('incoming_auth_type') === 'registration' ? 'selected' : '' }}>Registration</option>
                                        <option value="both" {{ old('incoming_auth_type') === 'both' ? 'selected' : '' }}>Both</option>
                                    </select>
                                    <p class="form-hint">How to identify calls from this trunk</p>
                                    <x-input-error :messages="$errors->get('incoming_auth_type')" class="mt-2" />
                                </div>

                                <div class="form-group md:col-span-2">
                                    <label for="incoming_ip_acl" class="form-label">IP ACL</label>
                                    <input type="text" id="incoming_ip_acl" name="incoming_ip_acl" value="{{ old('incoming_ip_acl') }}" class="form-input font-mono" placeholder="1.2.3.4, 5.6.7.0/24">
                                    <p class="form-hint">Allowed source IPs, comma-separated. Required for IP-based auth.</p>
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
                                    <input type="text" id="dial_pattern_match" name="dial_pattern_match" value="{{ old('dial_pattern_match') }}" class="form-input font-mono" placeholder="^0(\d+)$">
                                    <p class="form-hint">Regex to match numbers before sending to trunk</p>
                                    <x-input-error :messages="$errors->get('dial_pattern_match')" class="mt-2" />
                                </div>

                                <div class="form-group">
                                    <label for="dial_pattern_replace" class="form-label">Pattern Replace</label>
                                    <input type="text" id="dial_pattern_replace" name="dial_pattern_replace" value="{{ old('dial_pattern_replace') }}" class="form-input font-mono" placeholder="+44$1">
                                    <p class="form-hint">Replacement pattern using regex groups</p>
                                    <x-input-error :messages="$errors->get('dial_pattern_replace')" class="mt-2" />
                                </div>

                                <div class="form-group">
                                    <label for="dial_prefix" class="form-label">Dial Prefix</label>
                                    <input type="text" id="dial_prefix" name="dial_prefix" value="{{ old('dial_prefix') }}" class="form-input font-mono">
                                    <p class="form-hint">Prefix added before the number when dialing</p>
                                    <x-input-error :messages="$errors->get('dial_prefix')" class="mt-2" />
                                </div>
                                <div class="form-group">
                                    <label for="dial_strip_digits" class="form-label">Strip Digits</label>
                                    <input type="number" id="dial_strip_digits" name="dial_strip_digits" value="{{ old('dial_strip_digits', '0') }}" required min="0" max="20" class="form-input">
                                    <p class="form-hint">Number of leading digits to remove</p>
                                    <x-input-error :messages="$errors->get('dial_strip_digits')" class="mt-2" />
                                </div>

                                <div class="form-group md:col-span-2">
                                    <label for="tech_prefix" class="form-label">Tech Prefix</label>
                                    <input type="text" id="tech_prefix" name="tech_prefix" value="{{ old('tech_prefix') }}" class="form-input font-mono">
                                    <p class="form-hint">Technical prefix prepended for routing (e.g. provider-specific code)</p>
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
                                    <p class="form-hint">How caller ID is handled on outgoing calls</p>
                                    <x-input-error :messages="$errors->get('cli_mode')" class="mt-2" />
                                </div>

                                <div x-show="cliMode === 'override'" x-cloak class="form-group md:col-span-2">
                                    <label for="cli_override_number" class="form-label">Override Number</label>
                                    <input type="text" id="cli_override_number" name="cli_override_number" value="{{ old('cli_override_number') }}" class="form-input font-mono" placeholder="+44123456789">
                                    <p class="form-hint">Fixed caller ID number sent to provider</p>
                                    <x-input-error :messages="$errors->get('cli_override_number')" class="mt-2" />
                                </div>

                                <template x-if="cliMode === 'prefix_strip'">
                                    <div class="form-group">
                                        <label for="cli_prefix_strip" class="form-label">Strip Digits</label>
                                        <input type="number" id="cli_prefix_strip" name="cli_prefix_strip" value="{{ old('cli_prefix_strip', '0') }}" min="0" max="20" class="form-input">
                                        <p class="form-hint">Remove leading digits from caller ID</p>
                                        <x-input-error :messages="$errors->get('cli_prefix_strip')" class="mt-2" />
                                    </div>
                                </template>
                                <template x-if="cliMode === 'prefix_strip'">
                                    <div class="form-group">
                                        <label for="cli_prefix_add" class="form-label">Add Prefix</label>
                                        <input type="text" id="cli_prefix_add" name="cli_prefix_add" value="{{ old('cli_prefix_add') }}" class="form-input font-mono">
                                        <p class="form-hint">Prefix added to caller ID after stripping</p>
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
                                <input type="number" id="health_check_interval" name="health_check_interval" value="{{ old('health_check_interval', '60') }}" required min="10" max="3600" class="form-input">
                                <p class="form-hint">How often to send OPTIONS ping (seconds)</p>
                                <x-input-error :messages="$errors->get('health_check_interval')" class="mt-2" />
                            </div>

                            <div x-show="healthCheck" x-cloak class="form-group">
                                <label for="health_auto_disable_threshold" class="form-label">Auto-disable Threshold</label>
                                <input type="number" id="health_auto_disable_threshold" name="health_auto_disable_threshold" value="{{ old('health_auto_disable_threshold', '5') }}" required min="1" max="100" class="form-input">
                                <p class="form-hint">Consecutive failures before auto-disabling trunk</p>
                                <x-input-error :messages="$errors->get('health_auto_disable_threshold')" class="mt-2" />
                            </div>

                            <div x-show="healthCheck" x-cloak class="form-group md:col-span-2">
                                <label for="health_asr_threshold" class="form-label">ASR Threshold (%)</label>
                                <input type="number" id="health_asr_threshold" name="health_asr_threshold" value="{{ old('health_asr_threshold') }}" step="0.01" min="0" max="100" class="form-input" placeholder="Optional">
                                <p class="form-hint">Disable trunk if ASR drops below this percentage</p>
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
                            <textarea id="notes" name="notes" rows="3" class="form-input" placeholder="Optional notes about this trunk...">{{ old('notes') }}</textarea>
                            <p class="form-hint">Internal notes — not visible to providers or clients</p>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.trunks.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Create Trunk
                    </button>
                </div>
            </div>

            {{-- Sidebar - Right Side --}}
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
                                <p class="text-sm font-medium text-indigo-800">Auto Provisioning</p>
                                <p class="text-xs text-indigo-600">Trunk will be provisioned to rSwitch automatically</p>
                            </div>
                        </div>

                        <div class="space-y-3 text-sm">
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>PJSIP endpoint configured</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Authentication setup</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Ready for routing rules</span>
                            </div>
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
                            <p class="text-xs text-gray-500">For making calls to external numbers via this trunk.</p>
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
                            <p class="text-xs text-gray-500">Bidirectional trunk for both inbound and outbound calls.</p>
                        </div>
                    </div>
                </div>

                {{-- Common Codecs --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Common Codecs</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="font-mono text-gray-700">ulaw</span>
                                <span class="text-xs text-gray-500">G.711 (NA)</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="font-mono text-gray-700">alaw</span>
                                <span class="text-xs text-gray-500">G.711 (EU)</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="font-mono text-gray-700">g729</span>
                                <span class="text-xs text-gray-500">Low BW</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="font-mono text-gray-700">g722</span>
                                <span class="text-xs text-gray-500">HD Voice</span>
                            </div>
                            <div class="flex justify-between items-center py-1">
                                <span class="font-mono text-gray-700">opus</span>
                                <span class="text-xs text-gray-500">Modern</span>
                            </div>
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
                                <span>Enable health monitoring to auto-disable failed trunks</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Use pattern matching for number format conversion</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Test trunk connectivity after creation</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Add routing rules to direct calls to this trunk</span>
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
