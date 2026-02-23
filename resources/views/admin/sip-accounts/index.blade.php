<x-admin-layout>
    <x-slot name="header">SIP Accounts</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">SIP Accounts</h2>
            <p class="page-subtitle">Manage SIP endpoints and credentials</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.sip-accounts.export', request()->query()) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export
            </a>
            <a href="{{ route('admin.sip-accounts.import-form') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Import
            </a>
            <a href="{{ route('admin.sip-accounts.create') }}" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add SIP Account
            </a>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="filter-card" x-data="{
        resellerOpen: false,
        resellerSearch: '{{ $resellers->firstWhere('id', request('reseller_id'))?->name ?? '' }}',
        resellerId: '{{ request('reseller_id') }}',
        resellers: {{ $resellers->toJson() }},

        clientOpen: false,
        clientSearch: '{{ $clients->firstWhere('id', request('client_id'))?->name ?? '' }}',
        clientId: '{{ request('client_id') }}',
        clients: {{ $clients->toJson() }},

        get filteredResellers() {
            if (!this.resellerSearch) return this.resellers;
            return this.resellers.filter(r =>
                r.name.toLowerCase().includes(this.resellerSearch.toLowerCase()) ||
                r.email.toLowerCase().includes(this.resellerSearch.toLowerCase())
            );
        },

        get filteredClients() {
            let filtered = this.clients;
            if (this.resellerId) {
                filtered = filtered.filter(c => c.parent_id == this.resellerId);
            }
            if (!this.clientSearch) return filtered;
            return filtered.filter(c =>
                c.name.toLowerCase().includes(this.clientSearch.toLowerCase()) ||
                c.email.toLowerCase().includes(this.clientSearch.toLowerCase())
            );
        },

        selectReseller(reseller) {
            this.resellerSearch = reseller.name;
            this.resellerId = reseller.id;
            this.resellerOpen = false;
            this.clientSearch = '';
            this.clientId = '';
        },

        clearReseller() {
            this.resellerSearch = '';
            this.resellerId = '';
            this.clientSearch = '';
            this.clientId = '';
        },

        selectClient(client) {
            this.clientSearch = client.name;
            this.clientId = client.id;
            this.clientOpen = false;
        },

        clearClient() {
            this.clientSearch = '';
            this.clientId = '';
        }
    }">
        <form method="GET" class="filter-row flex-wrap">
            <div class="filter-search-box">
                <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search username, caller ID..." class="filter-input">
            </div>

            {{-- Reseller Filter --}}
            @if($resellers->count() > 0)
                <div class="relative">
                    <input type="hidden" name="reseller_id" :value="resellerId">
                    <div class="relative">
                        <input type="text"
                               x-model="resellerSearch"
                               @focus="resellerOpen = true"
                               @click="resellerOpen = true"
                               @input="resellerOpen = true; resellerId = ''"
                               placeholder="Filter by Reseller..."
                               class="filter-input pr-9 w-48"
                               :class="resellerId ? 'border-indigo-500 ring-1 ring-indigo-500' : ''"
                               autocomplete="off">
                        <button type="button"
                                x-show="resellerSearch"
                                @click="clearReseller()"
                                class="absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600 hover:bg-indigo-200 hover:text-indigo-700 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div x-show="resellerOpen && filteredResellers.length > 0"
                         @click.away="resellerOpen = false"
                         x-transition
                         class="absolute z-50 mt-1 w-64 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                        <template x-for="reseller in filteredResellers" :key="reseller.id">
                            <button type="button"
                                    @click="selectReseller(reseller)"
                                    class="w-full px-4 py-2 text-left hover:bg-indigo-50 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-sm font-medium flex-shrink-0"
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

            {{-- Client Filter --}}
            @if($clients->count() > 0)
                <div class="relative">
                    <input type="hidden" name="client_id" :value="clientId">
                    <div class="relative">
                        <input type="text"
                               x-model="clientSearch"
                               @focus="clientOpen = true"
                               @click="clientOpen = true"
                               @input="clientOpen = true; clientId = ''"
                               placeholder="Filter by Client..."
                               class="filter-input pr-9 w-48"
                               :class="clientId ? 'border-indigo-500 ring-1 ring-indigo-500' : ''"
                               autocomplete="off">
                        <button type="button"
                                x-show="clientSearch"
                                @click="clearClient()"
                                class="absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600 hover:bg-indigo-200 hover:text-indigo-700 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div x-show="clientOpen && filteredClients.length > 0"
                         @click.away="clientOpen = false"
                         x-transition
                         class="absolute z-50 mt-1 w-64 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                        <template x-for="client in filteredClients" :key="client.id">
                            <button type="button"
                                    @click="selectClient(client)"
                                    class="w-full px-4 py-2 text-left hover:bg-indigo-50 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-sky-100 text-sky-600 flex items-center justify-center text-sm font-medium flex-shrink-0"
                                     x-text="client.name.charAt(0).toUpperCase()"></div>
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-gray-900 truncate" x-text="client.name"></div>
                                    <div class="text-xs text-gray-500 truncate" x-text="client.email"></div>
                                </div>
                            </button>
                        </template>
                        <div x-show="filteredClients.length === 0 && resellerId" class="px-4 py-3 text-sm text-gray-500 text-center">
                            No clients for this reseller
                        </div>
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

            @if(request()->hasAny(['status', 'search', 'reseller_id', 'client_id']))
                <a href="{{ route('admin.sip-accounts.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Data Table --}}
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>SIP Account</th>
                    <th>Owner</th>
                    <th>Caller ID</th>
                    <th>Chan</th>
                    <th>Registration</th>
                    <th>Status</th>
                    <th class="text-center" style="vertical-align: middle;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sipAccounts as $sip)
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="avatar avatar-indigo">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="user-name font-mono">{{ $sip->username }}</div>
                                    @if($sip->last_registered_at)
                                        <div class="user-email">Reg: {{ $sip->last_registered_at->diffForHumans() }}</div>
                                    @else
                                        <div class="user-email">Never registered</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href="{{ route('admin.users.show', $sip->user) }}" class="text-indigo-600 hover:text-indigo-700 font-medium">
                                {{ $sip->user->name }}
                            </a>
                            <div class="text-xs text-gray-500">{{ ucfirst($sip->user->role) }}</div>
                        </td>
                        <td>
                            <div class="text-sm text-gray-900">{{ $sip->caller_id_name }}</div>
                            <div class="text-xs text-gray-500 font-mono">{{ $sip->caller_id_number }}</div>
                        </td>
                        <td class="font-medium">{{ $sip->max_channels }}</td>
                        <td>
                            @php
                                $contact = $contacts->get($sip->username);
                                $sourceIp = null;
                                if ($contact && $contact->uri) {
                                    preg_match('/@([^:;>]+)/', $contact->uri, $m);
                                    $sourceIp = $m[1] ?? null;
                                }
                            @endphp
                            @if($contact)
                                <span class="badge badge-success">Registered</span>
                                @if($sourceIp)
                                    <div class="text-xs text-gray-500 font-mono mt-0.5">{{ $sourceIp }}</div>
                                @endif
                            @else
                                <span class="badge badge-gray">Unregistered</span>
                            @endif
                        </td>
                        <td>
                            @if($sip->status === 'active')
                                <span class="badge badge-success">Active</span>
                            @elseif($sip->status === 'suspended')
                                <span class="badge badge-warning">Suspended</span>
                            @else
                                <span class="badge badge-danger">Disabled</span>
                            @endif
                        </td>
                        <td class="text-center" style="vertical-align: middle;">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('admin.sip-accounts.show', $sip) }}" class="action-icon" title="View">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('admin.sip-accounts.edit', $sip) }}" class="action-icon" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
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
                                <a href="{{ route('admin.sip-accounts.create') }}" class="empty-link-admin">Create your first SIP account</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($sipAccounts->hasPages())
        <div class="mt-6">
            {{ $sipAccounts->withQueryString()->links() }}
        </div>
    @endif
</x-admin-layout>
