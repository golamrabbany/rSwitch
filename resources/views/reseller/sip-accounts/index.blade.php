<x-reseller-layout>
    <x-slot name="header">SIP Accounts</x-slot>

    <div x-data="sipPage()" x-cloak>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">SIP Accounts</h2>
            <p class="page-subtitle">Manage SIP endpoints for your clients</p>
        </div>
        <div class="page-actions">
            <button @click="openAdd()" class="btn-action-primary-reseller">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add SIP Account
            </button>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="filter-card">
        <form method="GET" class="filter-row flex-wrap">
            <div class="filter-search-box">
                <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search username, caller ID..." class="filter-input">
            </div>
            <select name="user_id" class="filter-select">
                <option value="">All Clients</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>
            <select name="status" class="filter-select">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
            </select>
            <button type="submit" class="btn-search-reseller">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Search
            </button>
            @if(request()->hasAny(['status', 'search', 'user_id']))
                <a href="{{ route('reseller.sip-accounts.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>SIP Account</th>
                    <th>Client</th>
                    <th>Caller ID</th>
                    <th class="text-center">Channels</th>
                    <th>Registration</th>
                    <th>Status</th>
                    <th style="text-align: center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sipAccounts as $sip)
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="avatar avatar-emerald">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="user-name font-mono">{{ $sip->username }}</div>
                                    <div class="user-email flex items-center gap-1.5" x-data="{ show: false }">
                                        <span class="font-mono text-xs" x-text="show ? '{{ $sip->password }}' : '••••••••'"></span>
                                        <button type="button" @click="show = !show" class="text-gray-400 hover:text-emerald-600 transition-colors">
                                            <svg x-show="!show" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            <svg x-show="show" x-cloak class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href="{{ route('reseller.clients.show', $sip->user) }}" class="text-emerald-600 hover:text-emerald-500 font-medium">{{ $sip->user->name }}</a>
                        </td>
                        <td>
                            <div class="text-sm text-gray-900">{{ $sip->caller_id_name ?: '—' }}</div>
                            <div class="text-xs text-gray-400 font-mono">{{ $sip->caller_id_number ?: '—' }}</div>
                        </td>
                        <td class="text-center font-semibold text-gray-900">{{ $sip->max_channels }}</td>
                        <td>
                            <div class="reg-status" data-username="{{ $sip->username }}">
                                <span class="text-gray-300">--</span>
                            </div>
                        </td>
                        <td>
                            @switch($sip->status)
                                @case('active') <span class="badge badge-success">Active</span> @break
                                @case('suspended') <span class="badge badge-warning">Suspended</span> @break
                                @default <span class="badge badge-danger">Disabled</span>
                            @endswitch
                        </td>
                        <td>
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('reseller.sip-accounts.show', $sip) }}" class="action-icon" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <button @click="openEdit({{ json_encode(['id' => $sip->id, 'user_id' => $sip->user_id, 'password' => $sip->password, 'caller_id_name' => $sip->caller_id_name, 'caller_id_number' => $sip->caller_id_number, 'max_channels' => $sip->max_channels, 'codec_allow' => $sip->codec_allow, 'status' => $sip->status]) }})" class="action-icon" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-12">
                            <div class="empty-state">
                                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <p class="empty-text">No SIP accounts found</p>
                                <button @click="openAdd()" class="empty-link-reseller">Create your first SIP account</button>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($sipAccounts->hasPages())
        <div class="mt-6">{{ $sipAccounts->withQueryString()->links() }}</div>
    @endif

    {{-- Add/Edit SIP Account Modal --}}
    <div x-show="showModal" x-cloak class="relative z-50" @keydown.escape.window="showModal = false">
        <div x-show="showModal"
             x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4" @click="showModal = false">
                <div x-show="showModal"
                     x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                     class="relative transform overflow-hidden rounded-xl bg-white shadow-2xl transition-all w-full max-w-2xl" @click.stop>

                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="mode === 'add' ? 'bg-emerald-100' : 'bg-amber-100'">
                                <template x-if="mode === 'add'">
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </template>
                                <template x-if="mode === 'edit'">
                                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </template>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900" x-text="mode === 'add' ? 'Add SIP Account' : 'Edit SIP Account'"></h3>
                                <p class="text-sm text-gray-500" x-text="mode === 'add' ? 'Create a new SIP endpoint' : 'Update SIP account settings'"></p>
                            </div>
                        </div>
                        <button @click="showModal = false" type="button" class="rounded-lg p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form :action="mode === 'add' ? '{{ route('reseller.sip-accounts.store') }}' : '{{ url('reseller/sip-accounts') }}/' + editId" method="POST">
                        @csrf
                        <template x-if="mode === 'edit'">
                            <input type="hidden" name="_method" value="PUT">
                        </template>
                        <input type="hidden" name="auth_type" value="password">

                        <div class="px-6 py-5 space-y-4 max-h-[65vh] overflow-y-auto">
                            {{-- Client --}}
                            <div class="relative" x-data="clientSearch()" x-init="initSearch()">
                                <label class="form-label">Client</label>
                                <input type="hidden" name="user_id" :value="form.user_id">
                                <div class="relative">
                                    <input type="text" x-model="search" @focus="open = true" @click="open = true" @input="open = true; form.user_id = ''; kycError = ''" :disabled="mode === 'edit'" class="form-input pr-16" :class="mode === 'edit' ? 'bg-gray-50 text-gray-500' : ''" placeholder="Search client by name or email..." autocomplete="off">
                                    <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1">
                                        <button type="button" x-show="search" x-cloak @click="search = ''; form.user_id = ''; kycError = ''; open = false" class="p-1 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-400 mt-1">Select the client who owns this SIP account</p>
                                <div x-show="kycError" x-cloak class="mt-2 flex items-center gap-2 px-3 py-2 rounded-lg bg-red-50 border border-red-200">
                                    <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <span class="text-xs text-red-700" x-text="kycError"></span>
                                </div>
                                <div x-show="open && mode === 'add' && filteredClients.length > 0" @click.away="open = false" class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto">
                                    <template x-for="c in filteredClients" :key="c.id">
                                        <div @click="selectClient(c)" class="px-4 py-2 cursor-pointer hover:bg-emerald-50 flex items-center justify-between" :class="{ 'bg-emerald-50': form.user_id == c.id }">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center">
                                                    <span class="text-xs font-medium text-emerald-600" x-text="c.name.substring(0, 2).toUpperCase()"></span>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900" x-text="c.name"></p>
                                                    <p class="text-xs text-gray-500" x-text="c.email"></p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm font-mono font-semibold" :class="parseFloat(c.balance) > 0 ? 'text-emerald-600' : 'text-red-500'" x-text="'{{ currency_symbol() }}' + parseFloat(c.balance || 0).toFixed(2)"></p>
                                                <p class="text-xs" :class="c.kyc_status === 'approved' ? 'text-emerald-500' : 'text-amber-500'" x-text="c.kyc_status === 'approved' ? 'KYC Approved' : 'KYC: ' + (c.kyc_status || 'none')"></p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Username + Password --}}
                            @php
                                $sipPrefix = \App\Models\SystemSetting::get('sip_pin_prefix', '');
                                $sipMinLen = \App\Models\SystemSetting::get('sip_pin_min_length', 4);
                                $sipMaxLen = \App\Models\SystemSetting::get('sip_pin_max_length', 10);
                            @endphp
                            <div :style="kycError ? 'opacity: 0.4; pointer-events: none;' : ''">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label">Username (PIN)</label>
                                    <template x-if="mode === 'add'">
                                        <div class="relative">
                                            @if($sipPrefix)
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-mono font-medium">{{ $sipPrefix }}</span>
                                            @endif
                                            <input type="text" name="username" required placeholder="{{ $sipPrefix ? str_repeat('0', $sipMinLen) : 'e.g. 200001' }}" class="form-input font-mono" style="{{ $sipPrefix ? 'padding-left: ' . (strlen($sipPrefix) * 0.6 + 1) . 'rem;' : '' }}">
                                        </div>
                                    </template>
                                    <template x-if="mode === 'edit'">
                                        <input type="text" disabled :value="editUsername" class="form-input font-mono bg-gray-50 text-gray-500">
                                    </template>
                                    <p class="text-xs text-gray-400 mt-1">{{ $sipPrefix ? "Prefix '{$sipPrefix}' + {$sipMinLen}-{$sipMaxLen} digits" : "Numeric, {$sipMinLen}-{$sipMaxLen} digits" }}</p>
                                    @if(!empty(auth()->user()->sip_ranges))
                                        @foreach(auth()->user()->sip_ranges as $range)
                                            <p class="text-xs text-indigo-500 mt-0.5 font-medium">Range: {{ $range['start'] }} — {{ $range['end'] }}</p>
                                        @endforeach
                                    @else
                                        <p class="text-xs text-emerald-500 mt-0.5">Any number allowed (no range restriction)</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="form-label" x-text="mode === 'add' ? 'Password' : 'New Password'"></label>
                                    <input type="text" name="password" x-model="form.password" :required="mode === 'add'" class="form-input font-mono" :placeholder="mode === 'edit' ? 'Keep current' : ''">
                                    <p class="text-xs text-gray-400 mt-1" x-text="mode === 'add' ? 'Min 6 characters' : 'Leave blank to keep current'"></p>
                                </div>
                            </div>

                            {{-- Caller ID Name only --}}
                            <div>
                                <label class="form-label">Caller ID Name</label>
                                <input type="text" name="caller_id_name" x-model="form.caller_id_name" required class="form-input">
                                <p class="text-xs text-gray-400 mt-1">Display name for outgoing calls</p>
                            </div>
                            {{-- Hidden: Caller ID Number = username, Max Channels = 1 --}}
                            <input type="hidden" name="caller_id_number" :value="form.caller_id_number">
                            <input type="hidden" name="max_channels" value="1">

                            {{-- Codec --}}
                            <div>
                                <div x-data="{
                                    codecs: ['ulaw', 'alaw', 'g729'],
                                    get selected() { return (form.codec_allow || 'ulaw').split(',').map(s => s.trim()).filter(Boolean); },
                                    set selected(val) { form.codec_allow = val.join(','); },
                                    toggle(val) {
                                        let s = this.selected;
                                        const idx = s.indexOf(val);
                                        if (idx > -1 && s.length > 1) s.splice(idx, 1);
                                        else if (idx === -1) s.push(val);
                                        this.selected = s;
                                    },
                                    isSelected(val) { return this.selected.includes(val); }
                                }">
                                    <label class="form-label">Codec</label>
                                    <input type="hidden" name="codec_allow" :value="form.codec_allow">
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="codec in codecs" :key="codec">
                                            <button type="button" @click="toggle(codec)"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border text-sm font-medium transition-all cursor-pointer"
                                                :class="isSelected(codec)
                                                    ? 'bg-emerald-50 border-emerald-300 text-emerald-700 ring-1 ring-emerald-200'
                                                    : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50'">
                                                <svg x-show="isSelected(codec)" class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                <span class="font-mono" x-text="codec"></span>
                                            </button>
                                        </template>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">Audio codecs for this endpoint</p>
                                </div>
                            </div>

                            </div>{{-- /kycError disable wrapper --}}

                            {{-- Status (edit only) --}}
                            <template x-if="mode === 'edit'">
                                <div>
                                    <label class="form-label">Status</label>
                                    <select name="status" x-model="form.status" class="form-input">
                                        <option value="active">Active</option>
                                        <option value="suspended">Suspended</option>
                                        <option value="disabled">Disabled</option>
                                    </select>
                                    <p class="text-xs text-gray-400 mt-1">Disabled accounts cannot register</p>
                                </div>
                            </template>
                        </div>

                        <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-xl">
                            <button type="button" @click="showModal = false" class="btn-secondary">Cancel</button>
                            <button type="submit" class="btn-primary-reseller" :disabled="kycError !== ''" :class="kycError !== '' ? 'opacity-50 cursor-not-allowed' : ''">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span x-text="mode === 'add' ? 'Create' : 'Save Changes'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    </div>{{-- close x-data --}}

@push('scripts')
<script>
const _clients = @json($clientsJson);

function clientSearch() {
    return {
        open: false,
        search: '',
        filteredClients: _clients,
        selectClient(c) {
            this.form.user_id = String(c.id);
            this.search = c.name;
            this.open = false;
            if (c.kyc_status !== 'approved') {
                this.kycError = c.name + '\'s KYC is not approved (' + (c.kyc_status || 'not submitted') + '). SIP account cannot be created.';
            } else {
                this.kycError = '';
            }
        },
        initSearch() {
            this.$watch('search', (val) => {
                if (!val) { this.filteredClients = _clients; return; }
                const q = val.toLowerCase();
                this.filteredClients = _clients.filter(function(c) { return c.name.toLowerCase().indexOf(q) > -1 || c.email.toLowerCase().indexOf(q) > -1; });
            });
            this.$watch('form.user_id', (val) => {
                if (val) {
                    var found = _clients.find(function(c) { return String(c.id) === String(val); });
                    if (found) this.search = found.name;
                }
            });
            if (this.form && this.form.user_id) {
                var found = _clients.find(function(c) { return String(c.id) === String(this.form.user_id); }.bind(this));
                if (found) this.search = found.name;
            }
        }
    }
}

function sipPage() {
    return {
        showModal: false,
        mode: 'add',
        editId: null,
        editUsername: '',
        kycError: '',
        form: {
            user_id: '',
            password: '{{ \Illuminate\Support\Str::random(16) }}',
            caller_id_name: '',
            caller_id_number: '',
            max_channels: '5',
            codec_allow: 'ulaw',
            status: 'active',
        },
        openAdd() {
            this.mode = 'add';
            this.editId = null;
            this.editUsername = '';
            this.kycError = '';
            this.form = {
                user_id: '',
                password: Math.random().toString(36).slice(-12) + Math.random().toString(36).slice(-4),
                caller_id_name: '',
                caller_id_number: '',
                max_channels: '5',
                codec_allow: 'ulaw',
                status: 'active',
            };
            this.showModal = true;
        },
        openEdit(data) {
            this.mode = 'edit';
            this.editId = data.id;
            this.editUsername = '';
            // Find username from the table row
            const row = event.target.closest('tr');
            if (row) this.editUsername = row.querySelector('.user-name')?.textContent?.trim() || '';
            this.form = {
                user_id: String(data.user_id),
                password: '',
                caller_id_name: data.caller_id_name || '',
                caller_id_number: data.caller_id_number || '',
                max_channels: String(data.max_channels),
                codec_allow: data.codec_allow || 'ulaw',
                status: data.status || 'active',
            };
            this.showModal = true;
        }
    }
}

// Registration status (REST + WebSocket)
(function() {
    const cells = document.querySelectorAll('.reg-status');
    if (!cells.length) return;

    const cellMap = {};
    const usernames = [];
    cells.forEach(cell => { const u = cell.dataset.username; cellMap[u] = cell; usernames.push(u); });

    function setReg(cell, ip) { cell.innerHTML = '<div><span class="inline-flex items-center gap-1.5 text-emerald-600 text-xs font-medium"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Registered</span>' + (ip ? '<div class="text-xs text-gray-400 font-mono mt-0.5">' + ip + '</div>' : '') + '</div>'; }
    function setUnreg(cell) { cell.innerHTML = '<span class="inline-flex items-center gap-1.5 text-gray-400 text-xs"><span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>Unregistered</span>'; }

    fetch('{{ route("reseller.sip-accounts.registration-status") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ usernames })
    }).then(r => r.json()).then(c => { cells.forEach(cell => { c[cell.dataset.username] ? setReg(cell, c[cell.dataset.username].ip) : setUnreg(cell); }); }).catch(() => { cells.forEach(setUnreg); });

    const WS_URL = (window.location.protocol === 'https:' ? 'wss://' : 'ws://') + window.location.host + '/ws/live-calls';
    let ws, ra = 0;
    function connect() {
        ws = new WebSocket(WS_URL);
        ws.onopen = () => { ra = 0; };
        ws.onmessage = (e) => {
            const d = JSON.parse(e.data);
            if (d.type === 'sip_registered' && cellMap[d.username]) { setReg(cellMap[d.username], d.ip); const r = cellMap[d.username].closest('tr'); if(r){r.classList.add('bg-emerald-50');setTimeout(()=>r.classList.remove('bg-emerald-50'),2000);} }
            if (d.type === 'sip_unregistered' && cellMap[d.username]) { setUnreg(cellMap[d.username]); const r = cellMap[d.username].closest('tr'); if(r){r.classList.add('bg-red-50');setTimeout(()=>r.classList.remove('bg-red-50'),2000);} }
        };
        ws.onclose = () => { if (ra < 10) { ra++; setTimeout(connect, Math.min(1000*ra, 10000)); } };
    }
    setInterval(() => { if (ws && ws.readyState === 1) ws.send('ping'); }, 25000);
    connect();
})();
</script>
@endpush
</x-reseller-layout>
