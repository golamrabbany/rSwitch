@php
    $roleFilter = request('role');
    if ($roleFilter === 'reseller') {
        $pageTitle = 'Reseller Accounts';
        $pageSubtitle = 'Manage reseller accounts';
        $createLabel = 'Add Reseller';
    } elseif ($roleFilter === 'client') {
        $pageTitle = 'Client Accounts';
        $pageSubtitle = 'Manage client accounts';
        $createLabel = 'Add Client';
    } else {
        $pageTitle = 'Users';
        $pageSubtitle = 'Manage admin, reseller and client accounts';
        $createLabel = 'Create User';
    }
@endphp

<x-admin-layout>
    <x-slot name="header">{{ $pageTitle }}</x-slot>

    {{-- Section 1: Header with title and action buttons --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">{{ $pageTitle }}</h2>
            <p class="page-subtitle">{{ $pageSubtitle }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.users.create', $roleFilter ? ['role' => $roleFilter] : []) }}" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                {{ $createLabel }}
            </a>
        </div>
    </div>

    {{-- KYC Summary Tabs (Client list only) --}}
    @if($roleFilter === 'client' && !empty($kycStats))
        <div class="flex flex-wrap gap-2 mb-4">
            <a href="{{ route('admin.users.index', ['role' => 'client']) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium transition-colors {{ !request('kyc_status') ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                All <span class="font-bold">{{ $kycStats['total'] }}</span>
            </a>
            <a href="{{ route('admin.users.index', ['role' => 'client', 'kyc_status' => 'pending']) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium transition-colors {{ request('kyc_status') === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                Pending <span class="font-bold">{{ $kycStats['pending'] }}</span>
            </a>
            <a href="{{ route('admin.users.index', ['role' => 'client', 'kyc_status' => 'approved']) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium transition-colors {{ request('kyc_status') === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                Approved <span class="font-bold">{{ $kycStats['approved'] }}</span>
            </a>
            <a href="{{ route('admin.users.index', ['role' => 'client', 'kyc_status' => 'rejected']) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium transition-colors {{ request('kyc_status') === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                Rejected <span class="font-bold">{{ $kycStats['rejected'] }}</span>
            </a>
        </div>
    @endif

    {{-- Section 2: Filter Card --}}
    <div class="filter-card mb-3">
        <form method="GET" class="filter-row">
            @if($roleFilter)
                <input type="hidden" name="role" value="{{ $roleFilter }}">
            @endif

            <div class="filter-search-box" style="flex: 1 1 0%;">
                <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email..." class="filter-input">
            </div>

            @unless($roleFilter)
                <select name="role" class="filter-select">
                    <option value="">All Roles</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admins</option>
                    <option value="reseller" {{ request('role') === 'reseller' ? 'selected' : '' }}>Resellers</option>
                    <option value="client" {{ request('role') === 'client' ? 'selected' : '' }}>Clients</option>
                </select>
            @endunless

            @if($roleFilter === 'client' && $resellers->count() > 0)
                <div class="relative" @click.outside="open = false" x-data="{
                    open: false,
                    search: '{{ $resellers->firstWhere('id', request('parent_id'))?->name ?? '' }}',
                    selectedId: '{{ request('parent_id') }}',
                    resellers: {{ $resellers->toJson() }},
                    get filtered() {
                        if (!this.search) return this.resellers;
                        return this.resellers.filter(r =>
                            r.name.toLowerCase().includes(this.search.toLowerCase()) ||
                            r.email.toLowerCase().includes(this.search.toLowerCase())
                        );
                    },
                    select(reseller) {
                        this.search = reseller.name;
                        this.selectedId = reseller.id;
                        this.open = false;
                    },
                    clear() {
                        this.search = '';
                        this.selectedId = '';
                    }
                }">
                    <input type="hidden" name="parent_id" :value="selectedId">
                    <div class="relative">
                        <input type="text"
                               x-model="search"
                               @focus="open = true"
                               @click="open = true"
                               @input="open = true; selectedId = ''"
                               placeholder="Filter by Reseller..."
                               class="filter-input pr-9"
                               :class="selectedId ? 'border-indigo-500 ring-1 ring-indigo-500' : ''"
                               autocomplete="off">
                        <button type="button"
                                x-show="search"
                                @click="clear()"
                                class="absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600 hover:bg-indigo-200 hover:text-indigo-700 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div x-show="open && filtered.length > 0"
                         x-transition
                         class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                        <template x-for="reseller in filtered" :key="reseller.id">
                            <button type="button"
                                    @click="select(reseller)"
                                    class="w-full px-4 py-2 text-left hover:bg-indigo-50 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-medium flex-shrink-0"
                                     x-text="reseller.name.charAt(0).toUpperCase()"></div>
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-gray-900 truncate" x-text="reseller.name"></div>
                                    <div class="text-xs text-gray-500 truncate" x-text="reseller.email"></div>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>
            @endif

            {{-- Rate Group auto-suggest --}}
            <div class="relative" style="flex: 1 1 0%;" x-data="tariffFilter()" @click.outside="open = false">
                <input type="hidden" name="rate_group_id" :value="selectedId">
                <div class="relative">
                    <input type="text" x-model="search"
                           @focus="open = true"
                           @input="open = true; selectedId = ''"
                           @keydown.escape="open = false"
                           class="filter-input pr-8"
                           placeholder="Rate" autocomplete="off">
                    <button type="button" x-show="search" x-cloak @click="search = ''; selectedId = ''"
                            class="absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div x-show="open && filtered.length > 0" x-cloak
                     class="absolute z-50 mt-1 w-56 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-auto">
                    <template x-for="g in filtered" :key="g.id">
                        <div @click="selectedId = g.id; search = g.name; open = false"
                             class="px-3 py-2 text-sm cursor-pointer hover:bg-indigo-50"
                             :class="{ 'bg-indigo-50 font-medium': selectedId == g.id }">
                            <span x-text="g.name"></span>
                        </div>
                    </template>
                </div>
            </div>

            <select name="billing_type" class="filter-select">
                <option value="">Bill Type</option>
                <option value="prepaid" {{ request('billing_type') === 'prepaid' ? 'selected' : '' }}>Prepaid</option>
                <option value="postpaid" {{ request('billing_type') === 'postpaid' ? 'selected' : '' }}>Postpaid</option>
            </select>

            <select name="status" class="filter-select">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
            </select>

            <button type="submit" class="btn-search-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Search
            </button>

            @if(request()->hasAny(['status', 'search', 'parent_id', 'billing_type', 'rate_group_id']))
                <a href="{{ route('admin.users.index', $roleFilter ? ['role' => $roleFilter] : []) }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Section 3: Table --}}
    @if($users->total() > 0)
    @endif
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        {{-- Summary Bar --}}
        @if($users->total() > 0)
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    {{ $roleFilter === 'client' ? 'Client Accounts' : ($roleFilter === 'reseller' ? 'Reseller Accounts' : 'Users') }} Total : {{ number_format($users->total()) }} &middot; Showing {{ $users->firstItem() }} to {{ $users->lastItem() }}
                </span>
            </div>
        @endif

        {{-- Table --}}
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" width="40">SL</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Account</th>
                    @if($roleFilter === 'reseller')
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Company</th>
                    @endif
                    @if($roleFilter === 'client')
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Reseller</th>
                    @endif
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Balance</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tariff</th>
                    @if($roleFilter !== 'client')
                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Channels</th>
                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Clients/SIP</th>
                    @else
                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Chn/SIP</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">KYC</th>
                    @endif
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-indigo-50/50 transition-all border-b border-gray-100 group">
                        <td class="px-3 py-2 text-gray-400 tabular-nums text-center">{{ $users->firstItem() + $loop->index }}</td>
                        <td class="px-3 py-2">
                            <a href="{{ route('admin.users.show', $user) }}" class="block">
                                <p class="font-semibold text-gray-800 group-hover:text-indigo-600 transition-colors">{{ $user->name }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $user->email }}</p>
                            </a>
                        </td>
                        @if($roleFilter === 'reseller')
                            <td class="px-3 py-2">
                                @if($user->company_name)
                                    <p class="text-sm font-medium text-gray-800">{{ $user->company_name }}</p>
                                    <p class="text-xs text-gray-400">{{ $user->phone ?? '' }}</p>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                        @endif
                        @if($roleFilter === 'client')
                            <td class="px-3 py-2">
                                @if($user->parent && $user->parent->isSuperAdmin())
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-indigo-600"><span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>Direct</span>
                                @elseif($user->parent)
                                    <a href="{{ route('admin.users.show', $user->parent) }}" class="text-indigo-600 hover:text-indigo-700 font-medium text-sm">{{ $user->parent->name }}</a>
                                @else
                                    <span class="text-gray-300">-</span>
                                @endif
                            </td>
                        @endif
                        <td class="px-3 py-2">
                            <span class="font-bold text-gray-900 tabular-nums">{{ format_currency($user->balance) }}</span>
                            <p class="text-xs text-gray-400 mt-0.5">{{ ucfirst($user->billing_type) }}</p>
                        </td>
                        <td class="px-3 py-2 text-gray-600">{{ $user->rateGroup?->name ?? '-' }}</td>
                        @if($roleFilter !== 'client')
                            <td class="px-3 py-2 text-gray-700 text-center">
                                <span class="font-semibold tabular-nums">{{ $user->max_channels }}</span>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span class="tabular-nums text-gray-700">{{ $user->children_count }}</span>
                                <span class="text-gray-300 mx-0.5">/</span>
                                <span class="tabular-nums text-gray-700">{{ $user->sip_accounts_count }}</span>
                            </td>
                        @else
                            <td class="px-3 py-2 text-center">
                                <span class="tabular-nums text-gray-700">{{ $user->max_channels }}</span>
                                <span class="text-gray-300 mx-0.5">/</span>
                                <span class="tabular-nums text-gray-700">{{ $user->sip_accounts_count }}</span>
                            </td>
                            <td class="px-3 py-2">
                                @switch($user->kyc_status)
                                    @case('approved') <span class="badge badge-success">Approved</span> @break
                                    @case('pending') <span class="badge badge-warning">Pending</span> @break
                                    @case('rejected') <span class="badge badge-danger">Rejected</span> @break
                                    @default <span class="badge badge-gray">Not Submitted</span>
                                @endswitch
                            </td>
                        @endif
                        <td class="px-3 py-2">
                            @if($user->status === 'active')
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Active
                                </span>
                            @elseif($user->status === 'suspended')
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Suspended
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Disabled
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center">
                            <div class="flex items-center justify-center gap-1.5 opacity-60 group-hover:opacity-100 transition-opacity">
                                @if($user->isClient())
                                    @if($user->kycProfile)
                                        <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-kyc', { detail: { id: {{ $user->id }} } }))" class="p-1.5 rounded-lg transition-colors {{ $user->kyc_status === 'approved' ? 'text-emerald-500 hover:text-emerald-700 hover:bg-emerald-50' : ($user->kyc_status === 'pending' ? 'text-amber-500 hover:text-amber-700 hover:bg-amber-50' : ($user->kyc_status === 'rejected' ? 'text-red-500 hover:text-red-700 hover:bg-red-50' : 'text-gray-400 hover:text-gray-600 hover:bg-gray-50')) }}" title="KYC: {{ ucfirst($user->kyc_status) }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                                        </button>
                                    @else
                                        <span class="p-1.5 rounded-lg text-gray-300 cursor-not-allowed" title="KYC: Not Submitted">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                                        </span>
                                    @endif
                                @endif
                                @if($user->isReseller() || $user->isClient())
                                    <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-topup', { detail: { id: {{ $user->id }}, name: '{{ addslashes($user->name) }}', balance: {{ $user->balance }}, role: '{{ $user->role }}', parentName: '{{ addslashes($user->parent?->name ?? '') }}', parentId: {{ $user->parent_id ?? 'null' }} } }))" class="p-1.5 rounded-lg text-emerald-500 hover:text-emerald-700 hover:bg-emerald-50 transition-colors" title="Top Up">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </button>
                                @endif
                                <a href="{{ route('admin.users.show', $user) }}" class="p-1.5 rounded-lg text-blue-500 hover:text-blue-700 hover:bg-blue-50 transition-colors" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('admin.users.edit', $user) }}" class="p-1.5 rounded-lg text-amber-500 hover:text-amber-700 hover:bg-amber-50 transition-colors" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $roleFilter === 'client' ? 9 : ($roleFilter === 'reseller' ? 9 : 8) }}" class="px-4 py-12 text-center">
                            <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <p class="text-sm text-gray-400">No users found</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div class="mt-4 flex justify-end">
            {{ $users->withQueryString()->onEachSide(1)->links('pagination::simple-tailwind') }}
        </div>
    @endif


    {{-- Top Up Modal --}}
    <div x-data="{ show: false, userId: null, userName: '', userBalance: 0, userRole: '', parentName: '', parentId: null }"
         @open-topup.window="userId = $event.detail.id; userName = $event.detail.name; userBalance = $event.detail.balance; userRole = $event.detail.role; parentName = $event.detail.parentName; parentId = $event.detail.parentId; show = true">
        <div x-show="show" x-cloak class="relative z-50">
            <div x-show="show" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4" @click="show = false">
                    <div x-show="show" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                         class="relative bg-white rounded-xl shadow-2xl w-full max-w-md" @click.stop>

                        {{-- Header --}}
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Top Up Balance</h3>
                                    <p class="text-sm text-gray-500" x-text="userName"></p>
                                </div>
                            </div>
                            <button @click="show = false" class="rounded-lg p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        {{-- Current Balance --}}
                        <div class="px-6 pt-4">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span class="text-sm text-gray-600">Current Balance</span>
                                <span class="text-sm font-mono font-bold" :class="userBalance > 0 ? 'text-emerald-600' : 'text-red-500'" x-text="'{{ currency_symbol() }}' + parseFloat(userBalance).toFixed(2)"></span>
                            </div>
                        </div>

                        {{-- Form --}}
                        <form method="POST" :action="'/admin/users/' + userId + '/adjust-balance'" x-data="{ operation: 'credit' }">
                            @csrf
                            <input type="hidden" name="operation" :value="operation">

                            <div class="px-6 py-4 space-y-4">
                                {{-- Operation Toggle --}}
                                <div>
                                    <label class="form-label">Operation</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <label class="flex flex-col items-center justify-center py-3 rounded-lg border-2 cursor-pointer transition-all"
                                               :class="operation === 'credit' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 bg-white hover:border-gray-300'">
                                            <input type="radio" value="credit" x-model="operation" class="sr-only">
                                            <svg class="w-5 h-5 mb-1" :class="operation === 'credit' ? 'text-indigo-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6"/></svg>
                                            <span class="text-sm font-medium" :class="operation === 'credit' ? 'text-indigo-700' : 'text-gray-500'">Credit</span>
                                        </label>
                                        <label class="flex flex-col items-center justify-center py-3 rounded-lg border-2 cursor-pointer transition-all"
                                               :class="operation === 'debit' ? 'border-red-500 bg-red-50' : 'border-gray-200 bg-white hover:border-gray-300'">
                                            <input type="radio" value="debit" x-model="operation" class="sr-only">
                                            <svg class="w-5 h-5 mb-1" :class="operation === 'debit' ? 'text-red-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6"/></svg>
                                            <span class="text-sm font-medium" :class="operation === 'debit' ? 'text-red-700' : 'text-gray-500'">Debit</span>
                                        </label>
                                    </div>
                                </div>

                                <div>
                                    <label class="form-label">Amount ({{ currency_symbol() }})</label>
                                    <input type="number" name="amount" required step="0.01" min="0.01" max="999999.99" placeholder="0.00" class="form-input font-mono text-lg">
                                    <p class="form-hint">Amount to add to account</p>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="form-label">Source</label>
                                        <select name="source" required class="form-input">
                                            <option value="">Select...</option>
                                            <option value="cash">Cash</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                            <option value="bkash">bKash</option>
                                            <option value="nagad">Nagad</option>
                                            <option value="rocket">Rocket</option>
                                            <option value="upay">Upay</option>
                                            <option value="credit_card">Credit Card</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label">Remarks</label>
                                        <input type="text" name="remarks" class="form-input" placeholder="TXN ref...">
                                    </div>
                                </div>

                                {{-- Also credit parent reseller (client only) --}}
                                <div x-show="userRole === 'client' && parentName" x-cloak class="p-3 bg-amber-50 border border-amber-200 rounded-lg">
                                    <label class="flex items-start gap-3 cursor-pointer">
                                        <input type="checkbox" name="adjust_reseller" value="1" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 mt-0.5">
                                        <div>
                                            <p class="text-sm font-medium text-gray-800">Also credit parent reseller</p>
                                            <p class="text-xs text-gray-500" x-text="parentName + ' — same amount will be applied'"></p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-xl">
                                <button type="button" @click="show = false" class="btn-secondary">Cancel</button>
                                <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg text-white transition-colors"
                                        :class="operation === 'credit' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-red-600 hover:bg-red-700'">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span x-text="operation === 'credit' ? 'Add Credit' : 'Deduct Balance'"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- KYC Modal --}}
    @if($roleFilter === 'client')
    <div x-data="{ show: false, data: null }"
         @open-kyc.window="data = ({{ Js::from($kycDataJson) }})[$event.detail.id] || null; if (data) show = true">
        <div x-show="show" x-cloak class="relative z-50">
            <div x-show="show" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4" @click="show = false">
                    <div x-show="show" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                         class="relative bg-white rounded-xl shadow-2xl w-full max-w-lg" @click.stop>

                        {{-- Header --}}
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">KYC Information</h3>
                                    <p class="text-sm text-gray-500" x-text="data?.name"></p>
                                </div>
                            </div>
                            <button @click="show = false" class="rounded-lg p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        {{-- Content --}}
                        <div x-show="data" class="px-6 py-4 space-y-4 max-h-[28rem] overflow-y-auto">
                            {{-- Status --}}
                            <div class="p-3 rounded-lg"
                                 :class="data?.kyc_status === 'approved' ? 'bg-emerald-50' : (data?.kyc_status === 'pending' ? 'bg-amber-50' : (data?.kyc_status === 'rejected' ? 'bg-red-50' : 'bg-gray-50'))">
                                <span class="text-sm font-medium" :class="data?.kyc_status === 'approved' ? 'text-emerald-700' : (data?.kyc_status === 'pending' ? 'text-amber-700' : (data?.kyc_status === 'rejected' ? 'text-red-700' : 'text-gray-700'))" x-text="'Status: ' + (data?.kyc_status ? data.kyc_status.charAt(0).toUpperCase() + data.kyc_status.slice(1) : 'N/A')"></span>
                            </div>

                            {{-- Personal Info --}}
                            <div class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                                <div>
                                    <p class="text-xs text-gray-500">Account Type</p>
                                    <p class="font-medium text-gray-900" x-text="data?.account_type || '—'"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Full Name</p>
                                    <p class="font-medium text-gray-900" x-text="data?.full_name || '—'"></p>
                                </div>
                                <div x-show="data?.contact_person">
                                    <p class="text-xs text-gray-500">Contact Person</p>
                                    <p class="font-medium text-gray-900" x-text="data?.contact_person"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Phone</p>
                                    <p class="font-medium text-gray-900" x-text="data?.phone || '—'"></p>
                                </div>
                                <div x-show="data?.alt_phone">
                                    <p class="text-xs text-gray-500">Alt Phone</p>
                                    <p class="font-medium text-gray-900" x-text="data?.alt_phone"></p>
                                </div>
                            </div>

                            {{-- ID Info --}}
                            <div class="border-t border-gray-100 pt-3">
                                <div class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                                    <div>
                                        <p class="text-xs text-gray-500">ID Type</p>
                                        <p class="font-medium text-gray-900" x-text="data?.id_type || '—'"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">ID Number</p>
                                        <p class="font-medium font-mono text-gray-900" x-text="data?.id_number || '—'"></p>
                                    </div>
                                    <div x-show="data?.id_expiry">
                                        <p class="text-xs text-gray-500">ID Expiry</p>
                                        <p class="font-medium text-gray-900" x-text="data?.id_expiry"></p>
                                    </div>
                                </div>
                            </div>

                            {{-- Address --}}
                            <div x-show="data?.address" class="border-t border-gray-100 pt-3 text-sm">
                                <p class="text-xs text-gray-500">Address</p>
                                <p class="font-medium text-gray-900" x-text="data?.address"></p>
                            </div>

                            {{-- Timeline --}}
                            <div class="border-t border-gray-100 pt-3">
                                <div class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                                    <div>
                                        <p class="text-xs text-gray-500">Submitted</p>
                                        <p class="font-medium text-gray-900" x-text="data?.submitted_at || '—'"></p>
                                    </div>
                                    <div x-show="data?.reviewed_at">
                                        <p class="text-xs text-gray-500">Reviewed</p>
                                        <p class="font-medium text-gray-900"><span x-text="data?.reviewed_at"></span> <span x-show="data?.reviewer" class="text-gray-500">by <span x-text="data?.reviewer"></span></span></p>
                                    </div>
                                </div>
                            </div>

                            {{-- Rejection Reason --}}
                            <div x-show="data?.kyc_status === 'rejected' && data?.rejected_reason" class="p-3 bg-red-50 rounded-lg border border-red-200">
                                <p class="text-xs font-medium text-red-700">Rejection Reason</p>
                                <p class="text-sm text-red-600 mt-0.5" x-text="data?.rejected_reason"></p>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div x-show="data" class="px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-xl">
                            <div class="flex items-center justify-between">
                                <button @click="show = false" type="button" class="btn-secondary text-sm">Close</button>
                                <div class="flex items-center gap-2">
                                    <template x-if="data?.kyc_status !== 'approved' && data?.kyc_profile_id">
                                        <form :action="'/admin/kyc/' + data.kyc_profile_id + '/approve'" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition-colors">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Approve
                                            </button>
                                        </form>
                                    </template>
                                    <template x-if="data?.kyc_status === 'pending' && data?.kyc_profile_id">
                                        <form :action="'/admin/kyc/' + data.kyc_profile_id + '/reject'" method="POST" class="inline"
                                              @submit.prevent="
                                                  let reason = prompt('Enter rejection reason:');
                                                  if (reason) {
                                                      let fd = new FormData($event.target);
                                                      fd.append('reason', reason);
                                                      fetch($event.target.action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                                                      .then(() => location.reload());
                                                  }
                                              ">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg bg-white border border-red-300 text-red-600 hover:bg-red-50 transition-colors">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                Reject
                                            </button>
                                        </form>
                                    </template>
                                    <a :href="'/admin/kyc/' + data?.kyc_profile_id" class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors">
                                        View Full KYC
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @push('scripts')
    <script>
    var _rateGroups = @json($rateGroups);
    function tariffFilter() {
        return {
            open: false,
            search: '{{ $rateGroups->firstWhere("id", request("rate_group_id"))?->name ?? "" }}',
            selectedId: '{{ request("rate_group_id", "") }}',
            get filtered() {
                if (!this.search) return _rateGroups;
                var q = this.search.toLowerCase();
                return _rateGroups.filter(function(g) { return g.name.toLowerCase().indexOf(q) > -1; });
            }
        }
    }

    </script>
    @endpush
</x-admin-layout>
