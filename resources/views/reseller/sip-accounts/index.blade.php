<x-reseller-layout>
    <x-slot name="header">SIP Accounts</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">SIP Accounts</h2>
            <p class="page-subtitle">Manage SIP endpoints for you and your clients</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('reseller.sip-accounts.create') }}" class="btn-action-primary-reseller">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add SIP Account
            </a>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="filter-card">
        <form method="GET" class="filter-row">
            <div class="filter-search-box">
                <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by username, caller ID..." class="filter-input">
            </div>

            <select name="user_id" class="filter-select">
                <option value="">All Owners</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                        {{ $user->id === auth()->id() ? 'You (' . $user->name . ')' : $user->name }}
                    </option>
                @endforeach
            </select>

            <select name="status" class="filter-select">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
            </select>

            <select name="auth_type" class="filter-select">
                <option value="">All Auth Types</option>
                <option value="password" {{ request('auth_type') === 'password' ? 'selected' : '' }}>Password</option>
                <option value="ip" {{ request('auth_type') === 'ip' ? 'selected' : '' }}>IP Based</option>
                <option value="both" {{ request('auth_type') === 'both' ? 'selected' : '' }}>Both</option>
            </select>

            <button type="submit" class="btn-search-reseller">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Search
            </button>

            @if(request()->hasAny(['status', 'search', 'user_id', 'auth_type']))
                <a href="{{ route('reseller.sip-accounts.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Owner</th>
                    <th>Caller ID</th>
                    <th>Chan</th>
                    <th>Status</th>
                    <th class="text-center align-middle">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sipAccounts as $sip)
                    <tr>
                        <td>
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-900">{{ $sip->username }}</span>
                                @if($sip->is_online ?? false)
                                    <span class="w-2 h-2 bg-emerald-500 rounded-full" title="Online"></span>
                                @endif
                            </div>
                            @if($sip->last_registered_at)
                                <div class="text-xs text-gray-500">Last reg: {{ $sip->last_registered_at->diffForHumans() }}</div>
                            @endif
                        </td>
                        <td>
                            @if($sip->user_id === auth()->id())
                                <span class="badge badge-info">You</span>
                            @else
                                <a href="{{ route('reseller.clients.show', $sip->user) }}" class="text-emerald-600 hover:text-emerald-700 font-medium">
                                    {{ $sip->user->name }}
                                </a>
                            @endif
                            <div class="text-xs text-gray-500 capitalize">{{ $sip->user->role }}</div>
                        </td>
                        <td>
                            @if($sip->caller_id_name)
                                <span class="font-medium text-gray-900">{{ $sip->caller_id_name }}</span><br>
                            @endif
                            <span class="text-gray-500 font-mono text-xs">&lt;{{ $sip->caller_id_number }}&gt;</span>
                        </td>
                        <td class="font-medium">{{ $sip->max_channels }}</td>
                        <td>
                            @switch($sip->status)
                                @case('active')
                                    <span class="badge badge-success">Active</span>
                                    @break
                                @case('suspended')
                                    <span class="badge badge-warning">Suspended</span>
                                    @break
                                @default
                                    <span class="badge badge-danger">Disabled</span>
                            @endswitch
                        </td>
                        <td class="text-center align-middle">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('reseller.sip-accounts.show', $sip) }}" class="action-icon" title="View">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('reseller.sip-accounts.edit', $sip) }}" class="action-icon" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-12">
                            <div class="empty-state">
                                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <p class="empty-text">No SIP accounts found</p>
                                <a href="{{ route('reseller.sip-accounts.create') }}" class="empty-link-reseller">Create your first SIP account</a>
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
</x-reseller-layout>
