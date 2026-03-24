<x-client-layout>
    <x-slot name="header">SIP Accounts</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">My SIP Accounts</h2>
            <p class="page-subtitle">Your SIP endpoints — {{ $sipAccounts->total() }} account(s)</p>
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
            <select name="status" class="filter-select">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
            </select>
            <button type="submit" class="btn-search">Search</button>
            @if(request()->hasAny(['status', 'search']))
                <a href="{{ route('client.sip-accounts.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="data-table-container">
        @if($sipAccounts->total() > 0)
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
                <span class="text-sm text-gray-600">
                    Showing <span class="font-semibold">{{ $sipAccounts->firstItem() }}–{{ $sipAccounts->lastItem() }}</span> of <span class="font-semibold">{{ number_format($sipAccounts->total()) }}</span> accounts
                </span>
            </div>
        @endif
        <table class="data-table">
            <thead>
                <tr>
                    <th>SIP Account</th>
                    <th>Caller ID</th>
                    <th style="text-align: center">Channels</th>
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
                                <div class="avatar avatar-indigo">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="user-name font-mono">{{ $sip->username }}</div>
                                    <div class="user-email flex items-center gap-1.5" x-data="{ show: false }">
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
                            </div>
                        </td>
                        <td>
                            <div class="text-sm text-gray-900">{{ $sip->caller_id_name ?: '—' }}</div>
                            <div class="text-xs text-gray-400 font-mono">{{ $sip->caller_id_number ?: '—' }}</div>
                        </td>
                        <td style="text-align: center" class="font-semibold text-gray-900">{{ $sip->max_channels }}</td>
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
                                <a href="{{ route('client.sip-accounts.show', $sip) }}" class="action-icon" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('client.sip-accounts.edit', $sip) }}" class="action-icon" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($sipAccounts->hasPages())
        <div class="mt-4 flex justify-end">
            {{ $sipAccounts->withQueryString()->onEachSide(1)->links('pagination::simple-tailwind') }}
        </div>
    @endif

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var cells = document.querySelectorAll('.reg-status[data-username]');
        if (cells.length === 0) return;

        var usernames = [];
        cells.forEach(function(c) { usernames.push(c.dataset.username); });

        fetch('/client/sip-accounts/registration-status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ usernames: usernames })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            cells.forEach(function(cell) {
                var username = cell.dataset.username;
                var info = data[username];
                if (info && info.registered) {
                    cell.innerHTML = '<span class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-600"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Registered</span>' +
                        '<div class="text-xs text-gray-400 mt-0.5">' + (info.contact || '') + '</div>';
                } else {
                    cell.innerHTML = '<span class="inline-flex items-center gap-1.5 text-xs text-gray-400"><span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>Unregistered</span>';
                }
            });
        })
        .catch(function() {
            cells.forEach(function(cell) {
                cell.innerHTML = '<span class="text-xs text-gray-300">--</span>';
            });
        });
    });
    </script>
    @endpush
</x-client-layout>
