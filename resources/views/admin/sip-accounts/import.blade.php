<x-admin-layout>
    <x-slot name="header">Import SIP Accounts</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Import SIP Accounts</h2>
                <p class="page-subtitle">Bulk import from XLS/XLSX file</p>
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

    <form method="POST" action="{{ route('admin.sip-accounts.import') }}" enctype="multipart/form-data" x-data="{
        ownerOpen: false,
        ownerSearch: '{{ old('user_id_name', '') }}',
        ownerId: '{{ old('user_id', '') }}',
        ownerResults: [],
        ownerLoading: false,
        ownerDebounce: null,
        authType: '{{ old('auth_type', 'password') }}',
        allowP2p: {{ old('allow_p2p', '1') == '1' ? 'true' : 'false' }},
        allowRecording: {{ old('allow_recording', '0') == '1' ? 'true' : 'false' }},
        randomCli: {{ old('random_caller_id', '0') == '1' ? 'true' : 'false' }},
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form - Left Side --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- File Upload --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Upload File</h3>
                        <p class="form-card-subtitle">Select an XLS/XLSX file containing username and password columns</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="xls_file" class="form-label">XLS/XLSX File</label>
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-indigo-400 transition-colors"
                                     x-data="{ fileName: '' }"
                                     @dragover.prevent="$el.classList.add('border-indigo-500', 'bg-indigo-50')"
                                     @dragleave.prevent="$el.classList.remove('border-indigo-500', 'bg-indigo-50')"
                                     @drop.prevent="$el.classList.remove('border-indigo-500', 'bg-indigo-50'); fileName = $event.dataTransfer.files[0]?.name || ''; $refs.fileInput.files = $event.dataTransfer.files">
                                    <div class="space-y-1 text-center">
                                        <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <div class="flex text-sm text-gray-600 justify-center">
                                            <label for="xls_file" class="relative cursor-pointer rounded-md font-medium text-indigo-600 hover:text-indigo-500">
                                                <span>Upload a file</span>
                                                <input id="xls_file" name="xls_file" type="file" accept=".xls,.xlsx" required class="sr-only" x-ref="fileInput" @change="fileName = $event.target.files[0]?.name || ''">
                                            </label>
                                            <p class="pl-1">or drag and drop</p>
                                        </div>
                                        <p class="text-xs text-gray-500">XLS or XLSX up to 5MB</p>
                                        <p x-show="fileName" x-text="fileName" class="text-sm font-medium text-indigo-600 mt-2"></p>
                                    </div>
                                </div>
                                <x-input-error :messages="$errors->get('xls_file')" class="mt-2" />
                            </div>

                            <div class="form-group">
                                <label for="mode" class="form-label">Import Mode</label>
                                <select id="mode" name="mode" required class="form-input">
                                    <option value="add" @selected(old('mode', 'add') === 'add')>Add Only - Skip existing usernames</option>
                                    <option value="update" @selected(old('mode') === 'update')>Update Only - Only update existing accounts</option>
                                    <option value="add_update" @selected(old('mode') === 'add_update')>Add & Update - Create new and update existing</option>
                                </select>
                                <p class="form-hint">Choose how to handle existing SIP accounts.</p>
                                <x-input-error :messages="$errors->get('mode')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Client Selection --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Client</h3>
                        <p class="form-card-subtitle">All imported accounts will be assigned to this client</p>
                    </div>
                    <div class="form-card-body">
                        <div class="form-group">
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
                            <p class="form-hint">All imported SIP accounts will be owned by this client</p>
                            <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                        </div>
                    </div>
                </div>

                {{-- Authentication --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Authentication</h3>
                        <p class="form-card-subtitle">Security settings applied to all imported accounts</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="auth_type" class="form-label">Authentication Type</label>
                                <select id="auth_type" name="auth_type" required class="form-input" x-model="authType">
                                    <option value="password">Password Only</option>
                                    <option value="ip">IP Only</option>
                                    <option value="both">Password + IP</option>
                                </select>
                                <x-input-error :messages="$errors->get('auth_type')" class="mt-2" />
                            </div>

                            <div class="form-group" x-show="authType === 'ip' || authType === 'both'" x-cloak>
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
                                <p class="form-hint mt-2">Applied to all imported accounts. At least one required.</p>
                                <x-input-error :messages="$errors->get('codec_allow')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Call Features --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Call Features</h3>
                        <p class="form-card-subtitle">Feature toggles applied to all imported accounts</p>
                    </div>
                    <div class="form-card-body">
                        <div class="space-y-4">
                            {{-- Allow P2P Calls --}}
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Allow P2P Calls</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Enable internal SIP-to-SIP (PIN-to-PIN) calls</p>
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
                                    <p class="text-xs text-gray-500 mt-0.5">Record all calls for imported accounts</p>
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
                                        <p class="text-xs text-gray-500 mt-0.5">Use a random caller ID from another SIP account under the same reseller</p>
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

                {{-- Caller ID & Channels --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Caller ID & Channels</h3>
                        <p class="form-card-subtitle">Leave caller ID fields blank to use each account's username as default</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="form-group">
                                <label for="caller_id_name" class="form-label">Caller ID Name <span class="text-gray-400 font-normal">(optional)</span></label>
                                <input type="text" id="caller_id_name" name="caller_id_name" value="{{ old('caller_id_name') }}" class="form-input" placeholder="Leave blank = use username">
                                <p class="form-hint">Applied to all accounts. Blank = username</p>
                                <x-input-error :messages="$errors->get('caller_id_name')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="caller_id_number" class="form-label">Caller ID Number <span class="text-gray-400 font-normal">(optional)</span></label>
                                <input type="text" id="caller_id_number" name="caller_id_number" value="{{ old('caller_id_number') }}" class="form-input font-mono" placeholder="Leave blank = use username">
                                <p class="form-hint">Applied to all accounts. Blank = username</p>
                                <x-input-error :messages="$errors->get('caller_id_number')" class="mt-2" />
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

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.sip-accounts.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Import SIP Accounts
                    </button>
                </div>
            </div>

            {{-- Sidebar - Right Side --}}
            <div class="space-y-6">
                {{-- XLS Format --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">XLS Format</h3>
                    </div>
                    <div class="detail-card-body">
                        <p class="text-sm text-gray-600 mb-4">Your file only needs two columns:</p>

                        <div class="space-y-3">
                            <div class="flex items-start gap-3">
                                <span class="badge badge-danger">Required</span>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">username</p>
                                    <p class="text-xs text-gray-500">Unique SIP endpoint ID (alphanumeric, dashes, underscores)</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="badge badge-danger">Required</span>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">password</p>
                                    <p class="text-xs text-gray-500">SIP password (min 6 characters for new accounts)</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 bg-gray-900 rounded-lg p-3 overflow-x-auto">
                            <table class="text-xs text-gray-300 font-mono">
                                <thead>
                                    <tr>
                                        <th class="text-left pr-6 pb-1 text-indigo-400">username</th>
                                        <th class="text-left pb-1 text-indigo-400">password</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td class="pr-6">100001</td><td>StrongP@ss123!</td></tr>
                                    <tr><td class="pr-6">100002</td><td>An0therP@ss456</td></tr>
                                    <tr><td class="pr-6">100003</td><td>S3cureP@ss789</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <a href="{{ route('admin.sip-accounts.import-template') }}" class="mt-4 inline-flex items-center gap-2 w-full justify-center px-4 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-sm font-medium rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Download Template
                        </a>
                    </div>
                </div>

                {{-- How It Works --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">How It Works</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="space-y-3">
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-indigo-600">1</span>
                                </div>
                                <p class="text-sm text-gray-600">Upload an XLS/XLSX file with <strong>username</strong> and <strong>password</strong> columns</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-indigo-600">2</span>
                                </div>
                                <p class="text-sm text-gray-600">Select the client and configure auth, codecs, and features on this form</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-indigo-600">3</span>
                                </div>
                                <p class="text-sm text-gray-600">All accounts are created with the <strong>same configuration</strong> from the form</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-indigo-600">4</span>
                                </div>
                                <p class="text-sm text-gray-600">Active accounts are automatically provisioned to Asterisk</p>
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
                        <ul class="text-sm text-gray-600 space-y-2">
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Only <strong>username</strong> and <strong>password</strong> come from the file. Everything else is set on this form.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Leave Caller ID fields blank to default each account's caller ID to its username.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Any extra columns in the file (like "balance") are safely ignored.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Passwords are stored in plain text for SIP auth. Use strong passwords.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
