<x-admin-layout>
    <x-slot name="header">Create SIP Account</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Create SIP Account</h2>
                <p class="page-subtitle">Add a new SIP endpoint for a user</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.sip-accounts.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to List
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.sip-accounts.store') }}" x-data="{
        ownerOpen: false,
        ownerSearch: '{{ $selectedUser->name ?? old('user_id_name', '') }}',
        ownerId: '{{ old('user_id', $selectedUser->id ?? '') }}',
        ownerResults: [],
        ownerLoading: false,
        ownerDebounce: null,
        username: '{{ old('username') }}',
        callerIdNumber: '{{ old('caller_id_number') }}',
        syncCallerId: true,
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
        },
        updateUsername(val) {
            this.username = val;
            if (this.syncCallerId) {
                this.callerIdNumber = val;
            }
        }
    }">
        @csrf

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
                                                <span class="text-xs px-2 py-0.5 rounded-full bg-sky-100 text-sky-700">Client</span>
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
                                <label for="username" class="form-label">Username (SIP ID)</label>
                                <input type="text" id="username" name="username" x-model="username" @input="updateUsername($event.target.value)" required class="form-input font-mono" placeholder="e.g. 100001">
                                <p class="form-hint">Alphanumeric, dashes, underscores only</p>
                                <x-input-error :messages="$errors->get('username')" class="mt-2" />
                            </div>

                            <div class="form-group">
                                <label for="max_channels" class="form-label">Max Channels</label>
                                <input type="number" id="max_channels" name="max_channels" value="{{ old('max_channels', '2') }}" required min="1" max="100" class="form-input">
                                <p class="form-hint">Concurrent call limit (1-100)</p>
                                <x-input-error :messages="$errors->get('max_channels')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Authentication --}}
                <div class="form-card" x-data="{ authType: '{{ old('auth_type', 'password') }}' }">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Authentication</h3>
                        <p class="form-card-subtitle">Security and access control settings</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="password" class="form-label">SIP Password</label>
                                <div class="relative">
                                    <input type="text" id="password" name="password" value="{{ old('password', App\Services\SipProvisioningService::generatePassword()) }}" required class="form-input font-mono pr-20">
                                    <button type="button" onclick="regeneratePassword()" class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                        Regenerate
                                    </button>
                                </div>
                                <p class="form-hint">Min 6 characters, auto-generated</p>
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
                                <input type="text" id="allowed_ips" name="allowed_ips" value="{{ old('allowed_ips') }}" class="form-input font-mono" placeholder="192.168.1.100, 10.0.0.0/24">
                                <p class="form-hint">Comma-separated IPs or CIDR ranges</p>
                                <x-input-error :messages="$errors->get('allowed_ips')" class="mt-2" />
                            </div>

                            <div class="form-group md:col-span-2" x-data="{
                                codecs: '{{ $availableCodecs }}'.split(',').map(s => s.trim()).filter(Boolean),
                                selected: '{{ old('codec_allow', $availableCodecs) }}'.split(',').map(s => s.trim()).filter(Boolean),
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
                    allowP2p: {{ old('allow_p2p', '1') == '1' ? 'true' : 'false' }},
                    allowRecording: {{ old('allow_recording', '0') == '1' ? 'true' : 'false' }},
                    randomCli: {{ old('random_caller_id', '0') == '1' ? 'true' : 'false' }}
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
                                <input type="text" id="caller_id_name" name="caller_id_name" value="{{ old('caller_id_name') }}" required class="form-input" placeholder="John Doe">
                                <x-input-error :messages="$errors->get('caller_id_name')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="caller_id_number" class="form-label">Caller ID Number</label>
                                <div class="relative">
                                    <input type="text" id="caller_id_number" name="caller_id_number" x-model="callerIdNumber" @input="syncCallerId = false" required class="form-input font-mono pr-24" placeholder="+15551234567">
                                    <button type="button"
                                            x-show="!syncCallerId && username"
                                            @click="callerIdNumber = username; syncCallerId = true"
                                            class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                        Sync with SIP ID
                                    </button>
                                </div>
                                <p class="form-hint" x-show="syncCallerId" x-cloak>Auto-synced with Username (SIP ID)</p>
                                <x-input-error :messages="$errors->get('caller_id_number')" class="mt-2" />
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.sip-accounts.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Create SIP Account
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
                                <p class="text-xs text-indigo-600">Account will be provisioned to Asterisk automatically</p>
                            </div>
                        </div>

                        <div class="space-y-3 text-sm">
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>SIP endpoint created in realtime DB</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Auth credentials configured</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Ready for registration</span>
                            </div>
                        </div>
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
                            <p class="text-xs text-gray-500">Standard SIP authentication with username/password. Most common option.</p>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-purple">IP Only</span>
                            </div>
                            <p class="text-xs text-gray-500">Authenticate by source IP address. No password required. Good for trusted PBX systems.</p>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-success">Both</span>
                            </div>
                            <p class="text-xs text-gray-500">Requires both password AND IP whitelist. Maximum security.</p>
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
                                <span class="text-xs text-gray-500">G.711 μ-law (North America)</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="font-mono text-gray-700">alaw</span>
                                <span class="text-xs text-gray-500">G.711 A-law (Europe)</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="font-mono text-gray-700">g729</span>
                                <span class="text-xs text-gray-500">Low bandwidth (8 kbps)</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="font-mono text-gray-700">g722</span>
                                <span class="text-xs text-gray-500">HD Voice (wideband)</span>
                            </div>
                            <div class="flex justify-between items-center py-1">
                                <span class="font-mono text-gray-700">opus</span>
                                <span class="text-xs text-gray-500">Modern, adaptive codec</span>
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
                                <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Username cannot be changed after creation</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Caller ID Number auto-syncs with SIP ID</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Use strong, unique passwords</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Set channels based on expected concurrent calls</span>
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
