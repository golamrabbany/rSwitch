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
    <div class="filter-card mb-3" x-data="{
        resellerOpen: false,
        resellerSearch: '{{ $resellers->firstWhere('id', request('reseller_id'))?->name ?? '' }}',
        resellerId: '{{ request('reseller_id') }}',
        resellers: {{ $resellers->toJson() }},

        clientOpen: false,
        clientSearch: '{{ $selectedClient->name ?? '' }}',
        clientId: '{{ request('client_id') }}',
        clientResults: [],
        clientLoading: false,
        clientDebounce: null,

        get filteredResellers() {
            if (!this.resellerSearch) return this.resellers;
            return this.resellers.filter(r =>
                r.name.toLowerCase().includes(this.resellerSearch.toLowerCase()) ||
                r.email.toLowerCase().includes(this.resellerSearch.toLowerCase())
            );
        },

        selectReseller(reseller) {
            this.resellerSearch = reseller.name;
            this.resellerId = reseller.id;
            this.resellerOpen = false;
            this.clientSearch = '';
            this.clientId = '';
            this.clientResults = [];
        },

        clearReseller() {
            this.resellerSearch = '';
            this.resellerId = '';
            this.clientSearch = '';
            this.clientId = '';
            this.clientResults = [];
        },

        searchClients() {
            clearTimeout(this.clientDebounce);
            this.clientDebounce = setTimeout(() => {
                if (!this.clientSearch || this.clientSearch.length < 2) {
                    this.clientResults = [];
                    return;
                }
                this.clientLoading = true;
                let url = '{{ route('admin.sip-accounts.search-clients') }}?q=' + encodeURIComponent(this.clientSearch);
                if (this.resellerId) url += '&reseller_id=' + this.resellerId;
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(data => { this.clientResults = data; this.clientLoading = false; })
                    .catch(() => { this.clientLoading = false; });
            }, 300);
        },

        selectClient(client) {
            this.clientSearch = client.name;
            this.clientId = client.id;
            this.clientOpen = false;
        },

        clearClient() {
            this.clientSearch = '';
            this.clientId = '';
            this.clientResults = [];
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
                                x-show="resellerSearch" x-cloak
                                @click="clearReseller()"
                                class="absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600 hover:bg-indigo-200 hover:text-indigo-700 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div x-show="resellerOpen && filteredResellers.length > 0"
                         x-cloak
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

            {{-- Client Filter (AJAX search) --}}
            <div class="relative">
                <input type="hidden" name="client_id" :value="clientId">
                <div class="relative">
                    <input type="text"
                           x-model="clientSearch"
                           @focus="clientOpen = true"
                           @click="clientOpen = true"
                           @input="clientOpen = true; clientId = ''; searchClients()"
                           placeholder="Filter by Client..."
                           class="filter-input pr-9 w-48"
                           :class="clientId ? 'border-indigo-500 ring-1 ring-indigo-500' : ''"
                           autocomplete="off">
                    <button type="button"
                            x-show="clientSearch" x-cloak
                            @click="clearClient()"
                            class="absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600 hover:bg-indigo-200 hover:text-indigo-700 transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div x-show="clientOpen && clientResults.length > 0"
                     x-cloak
                     @click.away="clientOpen = false"
                     x-transition
                     class="absolute z-50 mt-1 w-64 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                    <template x-for="client in clientResults" :key="client.id">
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
                </div>
                <div x-show="clientOpen && clientLoading" x-cloak
                     class="absolute z-50 mt-1 w-64 bg-white border border-gray-200 rounded-lg shadow-lg p-4 text-center text-sm text-gray-500">
                    Searching...
                </div>
                <div x-show="clientOpen && !clientLoading && clientSearch.length >= 2 && clientResults.length === 0" x-cloak
                     @click.away="clientOpen = false"
                     class="absolute z-50 mt-1 w-64 bg-white border border-gray-200 rounded-lg shadow-lg p-4 text-center text-sm text-gray-500">
                    No clients found
                </div>
            </div>

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
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($sipAccounts->total() > 0)
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    SIP Accounts Total : {{ number_format($sipAccounts->total()) }} &middot; Showing {{ $sipAccounts->firstItem() }} to {{ $sipAccounts->lastItem() }}
                </span>
            </div>
        @endif
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" width="40">SL</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">SIP Account</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Owner</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Caller ID</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Chan</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Registration</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sipAccounts as $sip)
                    <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-indigo-50/50 transition-all border-b border-gray-100 group">
                        <td class="px-3 py-2 text-gray-400 tabular-nums text-center">{{ $sipAccounts->firstItem() + $loop->index }}</td>
                        <td class="px-3 py-2">
                            <div>
                                <div class="font-medium text-gray-900 font-mono">{{ $sip->username }}</div>
                                <div class="text-xs text-gray-500 flex items-center gap-1.5" x-data="{ show: false }">
                                    <span class="font-mono text-xs" x-text="show ? '{{ $sip->password }}' : '••••••••'"></span>
                                    <button type="button" @click="show = !show" class="text-gray-400 hover:text-indigo-600 transition-colors">
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
                        </td>
                        <td class="px-3 py-2">
                            <a href="{{ route('admin.users.show', $sip->user) }}" class="text-indigo-600 hover:text-indigo-700 font-medium">
                                {{ $sip->user->name }}
                            </a>
                            <div class="text-xs text-gray-500">{{ ucfirst($sip->user->role) }}</div>
                        </td>
                        <td class="px-3 py-2">
                            <div class="text-sm text-gray-900">{{ $sip->caller_id_name }}</div>
                            <div class="text-xs text-gray-500 font-mono">{{ $sip->caller_id_number }}</div>
                        </td>
                        <td class="px-3 py-2 font-medium">{{ $sip->max_channels }}</td>
                        <td class="px-3 py-2">
                            <div class="reg-status" data-username="{{ $sip->username }}">
                                <span class="text-gray-300">--</span>
                            </div>
                        </td>
                        <td class="px-3 py-2">
                            @if($sip->status === 'active')
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Active</span>
                            @elseif($sip->status === 'suspended')
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Suspended</span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Disabled</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center">
                            <div class="flex items-center justify-center gap-1.5 opacity-60 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('admin.sip-accounts.show', $sip) }}" class="p-1 rounded text-blue-500 hover:text-blue-700 hover:bg-blue-50 transition-colors" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('admin.sip-accounts.edit', $sip) }}" class="p-1 rounded text-amber-500 hover:text-amber-700 hover:bg-amber-50 transition-colors" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-12">
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

@push('scripts')
<script>
(function() {
    const WS_URL = (window.location.protocol === 'https:' ? 'wss://' : 'ws://') + window.location.host + '/ws/live-calls';
    const cells = document.querySelectorAll('.reg-status');
    if (!cells.length) return;

    // Build username → cell map for fast updates
    const cellMap = {};
    const usernames = [];
    cells.forEach(cell => {
        const u = cell.dataset.username;
        cellMap[u] = cell;
        usernames.push(u);
    });

    // 1. Load initial status via REST (faster than waiting for WS snapshot)
    fetch('{{ route('admin.sip-accounts.registration-status') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ usernames: usernames })
    })
    .then(r => r.json())
    .then(contacts => applyStatus(contacts))
    .catch(() => {});

    // 2. Connect WebSocket for real-time updates
    let ws = null;
    let reconnectAttempts = 0;

    function connect() {
        ws = new WebSocket(WS_URL);

        ws.onopen = function() {
            reconnectAttempts = 0;
        };

        ws.onmessage = function(event) {
            const data = JSON.parse(event.data);

            if (data.type === 'sip_registered') {
                const cell = cellMap[data.username];
                if (cell) {
                    setRegistered(cell, data.ip);
                    flashCell(cell, 'bg-emerald-50');
                }
            }

            if (data.type === 'sip_unregistered') {
                const cell = cellMap[data.username];
                if (cell) {
                    setUnregistered(cell);
                    flashCell(cell, 'bg-red-50');
                }
            }
        };

        ws.onclose = function() {
            if (reconnectAttempts < 10) {
                reconnectAttempts++;
                setTimeout(connect, Math.min(1000 * reconnectAttempts, 10000));
            }
        };
    }

    function applyStatus(contacts) {
        cells.forEach(cell => {
            const username = cell.dataset.username;
            if (contacts[username]) {
                setRegistered(cell, contacts[username].ip);
            } else {
                setUnregistered(cell);
            }
        });
    }

    function setRegistered(cell, ip) {
        cell.innerHTML = '<div><span class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-600">' +
            '<span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>' +
            'Registered</span>' +
            (ip ? '<div class="text-xs text-gray-400 font-mono mt-0.5">' + escapeHtml(ip) + '</div>' : '') +
            '</div>';
    }

    function setUnregistered(cell) {
        cell.innerHTML = '<span class="inline-flex items-center gap-1.5 text-xs text-gray-400">' +
            '<span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>' +
            'Unregistered</span>';
    }

    function flashCell(cell, colorClass) {
        const row = cell.closest('tr');
        if (row) {
            row.classList.add(colorClass);
            setTimeout(() => row.classList.remove(colorClass), 2000);
        }
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Keep alive
    setInterval(function() {
        if (ws && ws.readyState === WebSocket.OPEN) ws.send('ping');
    }, 25000);

    connect();
})();
</script>
@endpush
</x-admin-layout>
