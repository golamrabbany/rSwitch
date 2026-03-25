@php
    $roleParam = request('role');
    if ($roleParam === 'reseller') {
        $pageTitle = 'Add Reseller';
        $defaultRole = 'reseller';
    } elseif ($roleParam === 'client') {
        $pageTitle = 'Add Client';
        $defaultRole = 'client';
    } else {
        $pageTitle = 'Create User';
        $defaultRole = old('role', 'reseller');
    }
@endphp

<x-admin-layout>
    <x-slot name="header">{{ $pageTitle }}</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">{{ $pageTitle }}</h2>
                <p class="page-subtitle">Fill in the details to create a new account</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.users.index', $roleParam ? ['role' => $roleParam] : []) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.users.store') }}" x-data="{ role: '{{ $defaultRole }}' }">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form (2/3) --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Account Details --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Account Details</h3>
                        <p class="form-card-subtitle">Login credentials and account type</p>
                    </div>
                    <div class="form-card-body">
                        @unless($roleParam)
                            <div class="form-group">
                                <label class="form-label">Account Type</label>
                                <select name="role" required class="form-input" x-model="role">
                                    <option value="reseller">Reseller</option>
                                    <option value="client">Client</option>
                                </select>
                                <p class="form-hint">Resellers manage clients, clients are end users</p>
                                <x-input-error :messages="$errors->get('role')" class="mt-2" />
                            </div>
                        @else
                            <input type="hidden" name="role" value="{{ $roleParam }}">
                        @endunless

                        <div class="form-group" x-show="role === 'client'" x-cloak>
                            <label class="form-label">Parent Reseller</label>
                            <div class="relative" x-data="resellerSearch()" @click.away="open = false">
                                <input type="hidden" name="parent_id" :value="selectedId">
                                <input type="text" x-model="query" @focus="open = true" @click="open = true" @input="open = true; selectedId = ''" placeholder="Search reseller..." class="form-input" style="padding-left: 1rem;" autocomplete="off">
                                <div x-show="open" x-cloak class="absolute z-20 mt-1 w-full bg-white rounded-lg border border-gray-200 shadow-lg max-h-48 overflow-y-auto">
                                    <template x-for="r in filtered" :key="r.id">
                                        <button type="button" @click="selectedId = String(r.id); query = r.name; open = false" class="w-full px-3 py-2 text-left text-sm hover:bg-indigo-50 flex items-center justify-between">
                                            <span class="font-medium text-gray-900" x-text="r.name"></span>
                                            <span class="text-xs text-gray-400" x-text="r.email"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <p class="form-hint">Client must belong to a reseller</p>
                            <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" value="{{ old('name') }}" required class="form-input" placeholder="Enter full name">
                                <p class="form-hint">Legal name or company name</p>
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" value="{{ old('email') }}" required class="form-input" placeholder="Enter email address">
                                <p class="form-hint">Used for login and notifications</p>
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" required class="form-input" placeholder="Min 8 characters">
                                <p class="form-hint">Minimum 8 characters</p>
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="password_confirmation" required class="form-input" placeholder="Confirm password">
                                <p class="form-hint">Must match password</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Billing & Limits --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Billing & Limits</h3>
                        <p class="form-card-subtitle">Rate plan, balance and usage limits</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Billing Type</label>
                                <select name="billing_type" required class="form-input">
                                    <option value="prepaid" {{ old('billing_type') === 'prepaid' ? 'selected' : '' }}>Prepaid</option>
                                    <option value="postpaid" {{ old('billing_type') === 'postpaid' ? 'selected' : '' }}>Postpaid</option>
                                </select>
                                <p class="form-hint">Prepaid deducts in real-time</p>
                                <x-input-error :messages="$errors->get('billing_type')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Rate Group</label>
                                <select name="rate_group_id" class="form-input">
                                    <option value="">Select Rate Group</option>
                                    @foreach ($rateGroups as $rateGroup)
                                        <option value="{{ $rateGroup->id }}" {{ old('rate_group_id') == $rateGroup->id ? 'selected' : '' }}>
                                            {{ $rateGroup->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="form-hint">Defines call pricing</p>
                                <x-input-error :messages="$errors->get('rate_group_id')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Initial Balance</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">{{ currency_symbol() }}</span>
                                    <input type="number" name="balance" value="{{ old('balance', '0') }}" step="0.01" min="0" class="form-input pl-8">
                                </div>
                                <p class="form-hint">Starting account balance</p>
                                <x-input-error :messages="$errors->get('balance')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Credit Limit</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">{{ currency_symbol() }}</span>
                                    <input type="number" name="credit_limit" value="{{ old('credit_limit', '0') }}" step="0.01" min="0" class="form-input pl-8">
                                </div>
                                <p class="form-hint">For postpaid accounts only</p>
                                <x-input-error :messages="$errors->get('credit_limit')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Max Channels</label>
                                <input type="number" name="max_channels" value="{{ old('max_channels', '10') }}" min="1" class="form-input" placeholder="10">
                                <p class="form-hint">Maximum concurrent calls</p>
                                <x-input-error :messages="$errors->get('max_channels')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SIP Ranges (Reseller only) --}}
                @php $sipPrefix = \App\Models\SystemSetting::get('sip_pin_prefix', ''); @endphp
                <div class="form-card" x-show="role === 'reseller'" x-cloak x-data="{ ranges: [{ start: '', end: '' }] }">
                    <div class="form-card-header">
                        <div class="flex items-center justify-between w-full">
                            <div>
                                <h3 class="form-card-title">SIP Account Ranges</h3>
                                <p class="form-card-subtitle">Assign number ranges for this reseller's SIP accounts</p>
                            </div>
                            <button type="button" @click="ranges.push({ start: '', end: '' })" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Range
                            </button>
                        </div>
                    </div>
                    <div class="form-card-body space-y-3">
                        <template x-for="(range, index) in ranges" :key="index">
                            <div class="flex items-center gap-3">
                                <div class="flex-1">
                                    <div class="relative">
                                        @if($sipPrefix)
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-mono text-sm">{{ $sipPrefix }}</span>
                                        @endif
                                        <input type="text" :name="'sip_ranges[' + index + '][start]'" x-model="range.start" class="form-input font-mono" style="{{ $sipPrefix ? 'padding-left: ' . (strlen($sipPrefix) * 0.6 + 1) . 'rem;' : '' }}" placeholder="Start (e.g. 100000)">
                                    </div>
                                </div>
                                <span class="text-gray-400 text-sm">to</span>
                                <div class="flex-1">
                                    <div class="relative">
                                        @if($sipPrefix)
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-mono text-sm">{{ $sipPrefix }}</span>
                                        @endif
                                        <input type="text" :name="'sip_ranges[' + index + '][end]'" x-model="range.end" class="form-input font-mono" style="{{ $sipPrefix ? 'padding-left: ' . (strlen($sipPrefix) * 0.6 + 1) . 'rem;' : '' }}" placeholder="End (e.g. 100050)">
                                    </div>
                                </div>
                                <button type="button" @click="ranges.length > 1 ? ranges.splice(index, 1) : null" :class="ranges.length <= 1 ? 'opacity-30 cursor-not-allowed' : 'hover:bg-red-50 hover:text-red-600'" class="p-2 rounded-lg text-gray-400 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </template>
                        <p class="text-xs text-gray-400">Leave empty to allow any PIN. Start and end must be valid numbers. Reseller can only create SIP accounts within these ranges.</p>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.users.index', $roleParam ? ['role' => $roleParam] : []) }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        {{ $pageTitle }}
                    </button>
                </div>
            </div>

            {{-- Sidebar (1/3) --}}
            <div class="space-y-6">
                {{-- Quick Info --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Quick Info</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3 p-3 bg-indigo-50 rounded-lg mb-4">
                            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-indigo-800">Account Creation</p>
                                <p class="text-xs text-indigo-600">New account will be active immediately</p>
                            </div>
                        </div>

                        <div class="space-y-3 text-sm">
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Can login immediately</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>KYC submission required</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Rate group defines pricing</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Account Types --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Account Types</h3>
                    </div>
                    <div class="detail-card-body space-y-4">
                        <div>
                            <span class="badge badge-purple">Reseller</span>
                            <p class="text-xs text-gray-500 mt-1">Can manage their own clients, set rates, and resell services.</p>
                        </div>
                        <div>
                            <span class="badge badge-blue">Client</span>
                            <p class="text-xs text-gray-500 mt-1">End user account. Must belong to a reseller.</p>
                        </div>
                    </div>
                </div>

                {{-- Tips --}}
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-amber-800">Important</p>
                            <ul class="text-xs text-amber-700 mt-1 space-y-1">
                                <li>Clients must have a parent reseller</li>
                                <li>KYC must be approved for full access</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
    var _resellers = @json($resellers->map(function ($r) { return ['id' => $r->id, 'name' => $r->name, 'email' => $r->email]; }));

    function resellerSearch() {
        return {
            open: false,
            query: '',
            selectedId: '{{ old('parent_id') }}',
            filtered: _resellers,
            init() {
                if (this.selectedId) {
                    var found = _resellers.find(function(r) { return String(r.id) === String(this.selectedId); }.bind(this));
                    if (found) this.query = found.name;
                }
                this.$watch('query', function(val) {
                    if (!val) { this.filtered = _resellers; return; }
                    var q = val.toLowerCase();
                    this.filtered = _resellers.filter(function(r) { return r.name.toLowerCase().indexOf(q) > -1 || r.email.toLowerCase().indexOf(q) > -1; });
                }.bind(this));
            }
        }
    }
    </script>
    @endpush
</x-admin-layout>
