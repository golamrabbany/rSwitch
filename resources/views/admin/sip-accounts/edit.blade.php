<x-admin-layout>
    <x-slot name="header">Edit SIP Account</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Edit SIP Account</h2>
                <p class="page-subtitle font-mono">{{ $sipAccount->username }}</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.sip-accounts.show', $sipAccount) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Details
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.sip-accounts.update', $sipAccount) }}" x-data="{
        ownerOpen: false,
        ownerSearch: '{{ $sipAccount->user->name }}',
        ownerId: '{{ old('user_id', $sipAccount->user_id) }}',
        ownerResults: [],
        ownerLoading: false,
        ownerDebounce: null,
        searchOwners() {
            clearTimeout(this.ownerDebounce);
            this.ownerDebounce = setTimeout(() => {
                if (!this.ownerSearch || this.ownerSearch.length < 2) {
                    this.ownerResults = [];
                    return;
                }
                this.ownerLoading = true;
                fetch('{{ route('admin.sip-accounts.search-clients') }}?q=' + encodeURIComponent(this.ownerSearch), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => { this.ownerResults = data; this.ownerLoading = false; })
                .catch(() => { this.ownerLoading = false; });
            }, 300);
        },
        selectOwner(user) {
            this.ownerSearch = user.name;
            this.ownerId = user.id;
            this.ownerOpen = false;
        },
        clearOwner() {
            this.ownerSearch = '';
            this.ownerId = '';
            this.ownerResults = [];
            this.$refs.ownerInput.focus();
        }
    }">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form - Left Side --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Account Settings --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Account Settings</h3>
                        <p class="form-card-subtitle">Basic SIP account configuration</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group md:col-span-2">
                                <label class="form-label">Client</label>
                                <div class="relative">
                                    <input type="hidden" name="user_id" :value="ownerId">
                                    <div class="relative">
                                        <input type="text"
                                               x-ref="ownerInput"
                                               x-model="ownerSearch"
                                               @focus="ownerOpen = true"
                                               @click="ownerOpen = true"
                                               @input="ownerOpen = true; ownerId = ''; searchOwners()"
                                               @keydown.escape="ownerOpen = false"
                                               @keydown.tab="ownerOpen = false"
                                               class="form-input pr-16"
                                               placeholder="Search client by name or email..."
                                               autocomplete="off">
                                        <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1">
                                            <button type="button" x-show="ownerSearch" x-cloak @click="clearOwner()" class="p-1 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                            </svg>
                                        </div>
                                    </div>
                                    {{-- Dropdown --}}
                                    <div x-show="ownerOpen && ownerResults.length > 0"
                                         x-cloak
                                         @click.outside="ownerOpen = false"
                                         class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto">
                                        <template x-for="user in ownerResults" :key="user.id">
                                            <div @click="selectOwner(user)"
                                                 class="px-4 py-2 cursor-pointer hover:bg-indigo-50 flex items-center justify-between"
                                                 :class="{ 'bg-indigo-50': ownerId == user.id }">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-full bg-sky-100 flex items-center justify-center">
                                                        <span class="text-xs font-medium text-sky-600" x-text="user.name.substring(0, 2).toUpperCase()"></span>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900" x-text="user.name"></p>
                                                        <p class="text-xs text-gray-500" x-text="user.email"></p>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-sm font-mono font-semibold" :class="parseFloat(user.balance) > 0 ? 'text-emerald-600' : 'text-red-500'" x-text="'{{ currency_symbol() }}' + parseFloat(user.balance || 0).toFixed(2)"></p>
                                                    <p class="text-xs" :class="user.kyc_status === 'approved' ? 'text-emerald-500' : 'text-amber-500'" x-text="user.kyc_status === 'approved' ? 'KYC Approved' : 'KYC: ' + (user.kyc_status || 'none')"></p>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    {{-- Loading --}}
                                    <div x-show="ownerOpen && ownerLoading" x-cloak
                                         class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg p-4 text-center text-sm text-gray-500">
                                        Searching...
                                    </div>
                                    {{-- No results --}}
                                    <div x-show="ownerOpen && !ownerLoading && ownerSearch.length >= 2 && ownerResults.length === 0 && !ownerId"
                                         x-cloak
                                         @click.outside="ownerOpen = false"
                                         class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg p-4 text-center text-sm text-gray-500">
                                        No clients found matching "<span x-text="ownerSearch"></span>"
                                    </div>
                                </div>
                                <p class="form-hint">SIP accounts can only be assigned to clients</p>
                                <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                            </div>

                            <div class="form-group">
                                <label class="form-label">Username (SIP ID)</label>
                                <div class="form-input bg-gray-50 font-mono text-gray-600 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    {{ $sipAccount->username }}
                                </div>
                                <p class="form-hint">Username cannot be changed after creation</p>
                            </div>

                            <div class="form-group">
                                <label for="max_channels" class="form-label">Max Channels</label>
                                <input type="number" id="max_channels" name="max_channels" value="{{ old('max_channels', $sipAccount->max_channels) }}" required min="1" max="100" class="form-input">
                                <p class="form-hint">Concurrent call limit (1-100)</p>
                                <x-input-error :messages="$errors->get('max_channels')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Authentication --}}
                <div class="form-card" x-data="{ authType: '{{ old('auth_type', $sipAccount->auth_type) }}' }">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Authentication</h3>
                        <p class="form-card-subtitle">Security and access control settings</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="password" class="form-label">SIP Password</label>
                                <div class="relative">
                                    <input type="text" id="password" name="password" value="{{ old('password') }}" class="form-input font-mono pr-20" placeholder="Leave blank to keep current">
                                    <button type="button" onclick="regeneratePassword()" class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                        Generate New
                                    </button>
                                </div>
                                <p class="form-hint">Min 6 chars. Leave blank to keep current password.</p>
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            <div class="form-group">
                                <label for="auth_type" class="form-label">Authentication Type</label>
                                <select id="auth_type" name="auth_type" required class="form-input" x-model="authType">
                                    <option value="password">Password Only</option>
                                    <option value="ip">IP Only</option>
                                    <option value="both">Password + IP</option>
                                </select>
                                <x-input-error :messages="$errors->get('auth_type')" class="mt-2" />
                            </div>

                            <div class="form-group md:col-span-2" x-show="authType === 'ip' || authType === 'both'" x-cloak>
                                <label for="allowed_ips" class="form-label">Allowed IPs</label>
                                <input type="text" id="allowed_ips" name="allowed_ips" value="{{ old('allowed_ips', $sipAccount->allowed_ips) }}" class="form-input font-mono" placeholder="192.168.1.100, 10.0.0.0/24">
                                <p class="form-hint">Comma-separated IPs or CIDR ranges</p>
                                <x-input-error :messages="$errors->get('allowed_ips')" class="mt-2" />
                            </div>

                            <div class="form-group md:col-span-2" x-data="{
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
                                <p class="form-hint mt-2">Click to select/deselect codecs. At least one is required.</p>
                                <x-input-error :messages="$errors->get('codec_allow')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Call Features --}}
                <div class="form-card" x-data="{
                    allowP2p: {{ old('allow_p2p', $sipAccount->allow_p2p) ? 'true' : 'false' }},
                    allowRecording: {{ old('allow_recording', $sipAccount->allow_recording) ? 'true' : 'false' }},
                    randomCli: {{ old('random_caller_id', $sipAccount->random_caller_id) ? 'true' : 'false' }}
                }">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Call Features</h3>
                        <p class="form-card-subtitle">P2P calling and call recording options</p>
                    </div>
                    <div class="form-card-body">
                        <div class="space-y-4">
                            {{-- Allow P2P Calls --}}
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Allow P2P Calls</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Enable internal SIP-to-SIP (PIN-to-PIN) calls between accounts</p>
                                </div>
                                <input type="hidden" name="allow_p2p" :value="allowP2p ? '1' : '0'">
                                <button type="button" @click="allowP2p = !allowP2p"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    :class="allowP2p ? 'bg-indigo-600' : 'bg-gray-200'"
                                    role="switch" :aria-checked="allowP2p">
                                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                        :class="allowP2p ? 'translate-x-5' : 'translate-x-0'"></span>
                                </button>
                            </div>

                            {{-- Allow Call Recording --}}
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Allow Call Recording</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Record all calls made by this SIP account</p>
                                </div>
                                <input type="hidden" name="allow_recording" :value="allowRecording ? '1' : '0'">
                                <button type="button" @click="allowRecording = !allowRecording"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    :class="allowRecording ? 'bg-indigo-600' : 'bg-gray-200'"
                                    role="switch" :aria-checked="allowRecording">
                                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                        :class="allowRecording ? 'translate-x-5' : 'translate-x-0'"></span>
                                </button>
                            </div>

                            {{-- Random Caller ID (Super Admin only) --}}
                            @if(auth()->user()->isSuperAdmin())
                                <div class="flex items-center justify-between p-4 bg-amber-50 rounded-lg border border-amber-200">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Random Caller ID</p>
                                        <p class="text-xs text-gray-500 mt-0.5">At call time, use a random caller ID from another SIP account under the same reseller</p>
                                    </div>
                                    <input type="hidden" name="random_caller_id" :value="randomCli ? '1' : '0'">
                                    <button type="button" @click="randomCli = !randomCli"
                                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                        :class="randomCli ? 'bg-indigo-600' : 'bg-gray-200'"
                                        role="switch" :aria-checked="randomCli">
                                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                            :class="randomCli ? 'translate-x-5' : 'translate-x-0'"></span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Call Forwarding --}}
                <div class="form-card" x-data="{
                    cfEnabled: {{ old('call_forward_enabled', $sipAccount->call_forward_enabled) ? 'true' : 'false' }},
                    destType: '{{ old('call_forward_dest_type', $sipAccount->call_forward_dest_type ?? 'number') }}'
                }">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Call Forwarding</h3>
                        <p class="form-card-subtitle">Forward incoming calls to another number or via routing rules</p>
                    </div>
                    <div class="form-card-body">
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg mb-4">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Enable Call Forwarding</p>
                                <p class="text-xs text-gray-500 mt-0.5">Forward calls to a SIP account, mobile number, or via routing rules</p>
                            </div>
                            <input type="hidden" name="call_forward_enabled" :value="cfEnabled ? '1' : '0'">
                            <button type="button" @click="cfEnabled = !cfEnabled"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                :class="cfEnabled ? 'bg-indigo-600' : 'bg-gray-200'"
                                role="switch" :aria-checked="cfEnabled">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                    :class="cfEnabled ? 'translate-x-5' : 'translate-x-0'"></span>
                            </button>
                        </div>

                        <div x-show="cfEnabled" x-cloak class="space-y-4">
                            {{-- Destination Type Toggle --}}
                            <div class="form-group">
                                <label class="form-label">Destination Type</label>
                                <input type="hidden" name="call_forward_dest_type" :value="destType">
                                <div class="flex gap-2">
                                    <button type="button" @click="destType = 'number'"
                                            :class="destType === 'number' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-200 hover:border-indigo-300'"
                                            class="flex-1 py-2 px-4 rounded-lg border text-sm font-medium transition-all text-center">
                                        Number
                                    </button>
                                    <button type="button" @click="destType = 'route'"
                                            :class="destType === 'route' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-200 hover:border-indigo-300'"
                                            class="flex-1 py-2 px-4 rounded-lg border text-sm font-medium transition-all text-center">
                                        Route (via Routing Rules)
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="form-group">
                                    <label for="call_forward_type" class="form-label">Forward Type</label>
                                    <select id="call_forward_type" name="call_forward_type" class="form-input">
                                        <option value="cfu" {{ old('call_forward_type', $sipAccount->call_forward_type) === 'cfu' ? 'selected' : '' }}>Unconditional (CFU)</option>
                                        <option value="cfnr" {{ old('call_forward_type', $sipAccount->call_forward_type) === 'cfnr' ? 'selected' : '' }}>No Reply (CFNR)</option>
                                        <option value="cfb" {{ old('call_forward_type', $sipAccount->call_forward_type) === 'cfb' ? 'selected' : '' }}>Busy (CFB)</option>
                                        <option value="cfnr_cfb" {{ old('call_forward_type', $sipAccount->call_forward_type) === 'cfnr_cfb' ? 'selected' : '' }}>No Reply + Busy</option>
                                    </select>
                                </div>

                                <div class="form-group" x-show="destType === 'number'">
                                    <label for="call_forward_destination" class="form-label">Forward To</label>
                                    <input type="text" id="call_forward_destination" name="call_forward_destination"
                                           value="{{ old('call_forward_destination', $sipAccount->call_forward_destination) }}"
                                           class="form-input font-mono" placeholder="SIP account or mobile number">
                                </div>
                                <div class="form-group" x-show="destType === 'route'" x-cloak>
                                    <label class="form-label">Forward To</label>
                                    <div class="form-input bg-gray-50 text-gray-600 text-sm">
                                        SIP number → Routing Rules → Trunk
                                    </div>
                                    <p class="form-hint">Uses the SIP username as destination, routed via matching trunk rules</p>
                                </div>

                                <div class="form-group">
                                    <label for="call_forward_timeout" class="form-label">Ring Timeout (s)</label>
                                    <input type="number" id="call_forward_timeout" name="call_forward_timeout"
                                           value="{{ old('call_forward_timeout', $sipAccount->call_forward_timeout ?? 20) }}"
                                           class="form-input" min="5" max="120" placeholder="20">
                                    <p class="form-hint">Seconds to ring before forwarding (CFNR)</p>
                                </div>
                            </div>

                            <template x-if="destType === 'route'">
                                <div class="p-3 bg-indigo-50 rounded-lg border border-indigo-100">
                                    <p class="text-xs text-indigo-700">
                                        <strong>Route mode:</strong> The SIP number ({{ $sipAccount->username }}) will be sent to the matching trunk via routing rules.
                                        Remove/Add prefix and MNP will be applied automatically.
                                    </p>
                                </div>
                            </template>

                            <div class="p-3 bg-amber-50 rounded-lg border border-amber-200">
                                <p class="text-xs text-amber-700">
                                    <strong>CFU:</strong> Always forward, never ring this account.
                                    <strong>CFNR:</strong> Ring first, forward if no answer.
                                    <strong>CFB:</strong> Forward only when busy.
                                    Forwarding to a mobile number will be billed to the account owner.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Caller ID --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Caller ID</h3>
                        <p class="form-card-subtitle">Outbound caller identification</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="caller_id_name" class="form-label">Caller ID Name</label>
                                <input type="text" id="caller_id_name" name="caller_id_name" value="{{ old('caller_id_name', $sipAccount->caller_id_name) }}" required class="form-input">
                                <x-input-error :messages="$errors->get('caller_id_name')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="caller_id_number" class="form-label">Caller ID Number</label>
                                <input type="text" id="caller_id_number" name="caller_id_number" value="{{ old('caller_id_number', $sipAccount->caller_id_number) }}" required class="form-input font-mono">
                                <x-input-error :messages="$errors->get('caller_id_number')" class="mt-2" />
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Status --}}
                <div class="form-card" x-data="{ status: '{{ old('status', $sipAccount->status) }}' }">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Account Status</h3>
                        <p class="form-card-subtitle">Control account access and provisioning</p>
                    </div>
                    <div class="form-card-body">
                        <div class="form-group">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" required class="form-input" x-model="status">
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                                <option value="disabled">Disabled</option>
                            </select>
                            <x-input-error :messages="$errors->get('status')" class="mt-2" />
                        </div>

                        {{-- Status Warning --}}
                        <div x-show="status !== 'active'" x-cloak class="mt-4 p-3 rounded-lg border" :class="{
                            'bg-amber-50 border-amber-200': status === 'suspended',
                            'bg-red-50 border-red-200': status === 'disabled'
                        }">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" :class="{
                                    'text-amber-500': status === 'suspended',
                                    'text-red-500': status === 'disabled'
                                }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium" :class="{
                                        'text-amber-800': status === 'suspended',
                                        'text-red-800': status === 'disabled'
                                    }" x-text="status === 'suspended' ? 'Account will be suspended' : 'Account will be disabled'"></p>
                                    <p class="text-xs mt-1" :class="{
                                        'text-amber-600': status === 'suspended',
                                        'text-red-600': status === 'disabled'
                                    }">This account will be deprovisioned from rSwitch and will not be able to make or receive calls.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.sip-accounts.show', $sipAccount) }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Update SIP Account
                    </button>
                </div>
            </div>

            {{-- Sidebar - Right Side --}}
            <div class="space-y-6">
                {{-- Current Account Info --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Account Info</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                                <span class="text-lg font-bold text-white">{{ strtoupper(substr($sipAccount->username, 0, 2)) }}</span>
                            </div>
                            <div>
                                <p class="font-mono font-medium text-gray-900">{{ $sipAccount->username }}</p>
                                <span class="badge {{ $sipAccount->status === 'active' ? 'badge-success' : ($sipAccount->status === 'suspended' ? 'badge-warning' : 'badge-danger') }}">
                                    {{ ucfirst($sipAccount->status) }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-3 text-sm border-t border-gray-100 pt-4">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Created</span>
                                <span class="text-gray-900">{{ $sipAccount->created_at->format('M d, Y') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Last Updated</span>
                                <span class="text-gray-900">{{ $sipAccount->updated_at->diffForHumans() }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Auth Type</span>
                                <span class="font-mono text-gray-900">{{ $sipAccount->auth_type }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Client Info --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Current Client</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-sky-100 flex items-center justify-center">
                                <span class="text-sm font-medium text-sky-600">{{ strtoupper(substr($sipAccount->user->name, 0, 2)) }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $sipAccount->user->name }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ $sipAccount->user->email }}</p>
                            </div>
                            <span class="badge badge-blue">Client</span>
                        </div>
                        @if($sipAccount->user->parent)
                            <div class="mt-3 pt-3 border-t border-gray-100 text-xs text-gray-500">
                                <span>Reseller:</span>
                                <a href="{{ route('admin.users.show', $sipAccount->user->parent) }}" class="text-indigo-600 hover:text-indigo-800 ml-1">{{ $sipAccount->user->parent->name }}</a>
                            </div>
                        @endif
                        <a href="{{ route('admin.users.show', $sipAccount->user) }}" class="mt-3 text-sm text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                            View Client Profile
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>

                {{-- Authentication Types --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Authentication Types</h3>
                    </div>
                    <div class="detail-card-body space-y-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-info">Password</span>
                            </div>
                            <p class="text-xs text-gray-500">Standard SIP authentication. Most common option.</p>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-purple">IP Only</span>
                            </div>
                            <p class="text-xs text-gray-500">Authenticate by source IP. Good for trusted PBX systems.</p>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-success">Both</span>
                            </div>
                            <p class="text-xs text-gray-500">Requires password AND IP whitelist. Maximum security.</p>
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
                                <span>Leave password blank to keep current</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Changes are auto-provisioned to rSwitch</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Status change affects active calls</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Changing owner may affect billing</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        function regeneratePassword() {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%^&*';
            let password = '';
            for (let i = 0; i < 20; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('password').value = password;
        }
    </script>
</x-admin-layout>
