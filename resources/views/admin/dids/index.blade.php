<x-admin-layout>
    <x-slot name="header">DIDs</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">DIDs</h2>
            <p class="page-subtitle">Manage direct inward dialing numbers</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.dids.create') }}" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add DID
            </a>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="filter-card">
        <form method="GET" class="filter-row flex-wrap">
            <div class="filter-search-box">
                <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search number, provider..." class="filter-input">
            </div>

            <select name="status" class="filter-select">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="unassigned" {{ request('status') === 'unassigned' ? 'selected' : '' }}>Unassigned</option>
                <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
            </select>

            <select name="trunk_id" class="filter-select">
                <option value="">All Trunks</option>
                @foreach ($trunks as $trunk)
                    <option value="{{ $trunk->id }}" {{ request('trunk_id') == $trunk->id ? 'selected' : '' }}>
                        {{ $trunk->name }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="btn-search-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Search
            </button>

            @if(request()->hasAny(['status', 'trunk_id', 'search']))
                <a href="{{ route('admin.dids.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Data Table --}}
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Number</th>
                    <th>Provider</th>
                    <th>Trunk</th>
                    <th>Assigned To</th>
                    <th>Destination</th>
                    <th>Cost / Price</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($dids as $did)
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="avatar avatar-emerald">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="user-name font-mono">{{ $did->number }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="text-gray-900">{{ $did->provider }}</td>
                        <td>
                            @if ($did->trunk)
                                <a href="{{ route('admin.trunks.show', $did->trunk) }}" class="text-indigo-600 hover:text-indigo-500">
                                    {{ $did->trunk->name }}
                                </a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td>
                            @if ($did->assignedUser)
                                <a href="{{ route('admin.users.show', $did->assignedUser) }}" class="text-indigo-600 hover:text-indigo-500">
                                    {{ $did->assignedUser->name }}
                                </a>
                                <span class="text-xs text-gray-500">({{ ucfirst($did->assignedUser->role) }})</span>
                            @else
                                <span class="text-gray-400 italic">Unassigned</span>
                            @endif
                        </td>
                        <td>
                            @if ($did->destination_type === 'sip_account' && $did->destination_id)
                                <span class="inline-flex items-center gap-1">
                                    <span class="badge badge-info">SIP</span>
                                    <span class="text-sm">{{ $did->destination_id }}</span>
                                </span>
                            @elseif ($did->destination_type === 'ring_group' && $did->destination_id)
                                <span class="inline-flex items-center gap-1">
                                    <span class="badge badge-purple">RING</span>
                                    <span class="text-sm">{{ $did->destination_id }}</span>
                                </span>
                            @elseif ($did->destination_type === 'external' && $did->destination_number)
                                <span class="inline-flex items-center gap-1">
                                    <span class="badge badge-warning">EXT</span>
                                    <span class="font-mono text-xs">{{ $did->destination_number }}</span>
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="font-medium">
                            <span class="text-gray-500">{{ format_currency($did->monthly_cost) }}</span>
                            <span class="text-gray-400">/</span>
                            <span class="text-gray-900">{{ format_currency($did->monthly_price) }}</span>
                        </td>
                        <td>
                            @if ($did->status === 'active')
                                <span class="badge badge-success">Active</span>
                            @elseif ($did->status === 'unassigned')
                                <span class="badge badge-warning">Unassigned</span>
                            @else
                                <span class="badge badge-danger">Disabled</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('admin.dids.show', $did) }}" class="action-icon" title="View">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            <a href="{{ route('admin.dids.edit', $did) }}" class="action-icon" title="Edit">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-12">
                            <div class="empty-state">
                                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                </svg>
                                <p class="empty-text">No DIDs found</p>
                                <a href="{{ route('admin.dids.create') }}" class="empty-link-admin">Add your first DID</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($dids->hasPages())
        <div class="mt-6">
            {{ $dids->withQueryString()->links() }}
        </div>
    @endif
</x-admin-layout>
