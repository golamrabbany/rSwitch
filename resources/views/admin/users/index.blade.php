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
    <div class="filter-card">
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
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Account</th>
                    @if($roleFilter === 'client')
                        <th>Reseller</th>
                    @endif
                    <th>Balance</th>
                    <th>Tariff</th>
                    <th>Billing Type</th>
                    <th>Channels</th>
                    <th>{{ $roleFilter === 'client' ? 'SIP' : 'Clients/SIP' }}</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="avatar avatar-indigo">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="user-name">{{ $user->name }}</div>
                                    <div class="user-email">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        @if($roleFilter === 'client')
                            <td>
                                @if($user->parent)
                                    <a href="{{ route('admin.users.show', $user->parent) }}" class="text-indigo-600 hover:text-indigo-700 font-medium">
                                        {{ $user->parent->name }}
                                    </a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        @endif
                        <td class="font-medium">{{ format_currency($user->balance) }}</td>
                        <td>{{ $user->rateGroup?->name ?? '-' }}</td>
                        <td>
                            @if($user->billing_type === 'prepaid')
                                <span class="badge badge-blue">Prepaid</span>
                            @else
                                <span class="badge badge-purple">Postpaid</span>
                            @endif
                        </td>
                        <td>{{ $user->max_channels }}</td>
                        <td>{{ $roleFilter === 'client' ? $user->sip_accounts_count : $user->children_count.'/'.$user->sip_accounts_count }}</td>
                        <td>
                            @if($user->status === 'active')
                                <span class="badge badge-success">Active</span>
                            @elseif($user->status === 'suspended')
                                <span class="badge badge-warning">Suspended</span>
                            @else
                                <span class="badge badge-danger">Disabled</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('admin.users.show', $user) }}" class="action-icon" title="View">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            <a href="{{ route('admin.users.edit', $user) }}" class="action-icon" title="Edit">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $roleFilter === 'client' ? 9 : 8 }}" class="text-center py-12">
                            <div class="empty-state">
                                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <p class="empty-text">No users found</p>
                                <a href="{{ route('admin.users.create') }}" class="empty-link-admin">Create your first user</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div class="mt-6">
            {{ $users->withQueryString()->links() }}
        </div>
    @endif
</x-admin-layout>
