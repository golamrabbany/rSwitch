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

    {{-- Section 2: Filter Card --}}
    <div class="filter-card mb-3">
        <form method="GET" class="filter-row">
            @if($roleFilter)
                <input type="hidden" name="role" value="{{ $roleFilter }}">
            @endif

            <div class="filter-search-box">
                <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, email..." class="filter-input">
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
                <div class="relative" x-data="{
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
                         @click.away="open = false"
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

            @if(request()->hasAny(['status', 'search', 'parent_id']))
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
                    {{ $roleFilter === 'client' ? 'Client' : 'Reseller' }} Accounts Total : {{ number_format($users->total()) }} &middot; Showing {{ $users->firstItem() }} to {{ $users->lastItem() }}
                </span>
            </div>
        @endif

        {{-- Table --}}
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" width="40">SL</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Account</th>
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
                        @if($roleFilter === 'client')
                            <td class="px-3 py-2">
                                @if($user->parent)
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
                        <td colspan="{{ $roleFilter === 'client' ? 9 : 8 }}" class="px-4 py-12 text-center">
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
</x-admin-layout>
