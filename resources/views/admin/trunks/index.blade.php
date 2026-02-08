<x-admin-layout>
    <x-slot name="header">Trunks</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">Trunks</h2>
            <p class="page-subtitle">Manage SIP trunk connections and providers</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.trunks.create') }}" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add Trunk
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
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, provider, host..." class="filter-input">
            </div>

            <select name="direction" class="filter-select">
                <option value="">All Directions</option>
                <option value="incoming" {{ request('direction') === 'incoming' ? 'selected' : '' }}>Incoming</option>
                <option value="outgoing" {{ request('direction') === 'outgoing' ? 'selected' : '' }}>Outgoing</option>
                <option value="both" {{ request('direction') === 'both' ? 'selected' : '' }}>Both</option>
            </select>

            <select name="status" class="filter-select">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
                <option value="auto_disabled" {{ request('status') === 'auto_disabled' ? 'selected' : '' }}>Auto-disabled</option>
            </select>

            <button type="submit" class="btn-search-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Search
            </button>

            @if(request()->hasAny(['direction', 'status', 'search']))
                <a href="{{ route('admin.trunks.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Data Table --}}
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Trunk</th>
                    <th>Direction</th>
                    <th>Host</th>
                    <th>Channels</th>
                    <th>Health</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($trunks as $trunk)
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="avatar avatar-indigo">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="user-name">{{ $trunk->name }}</div>
                                    <div class="user-email">{{ $trunk->provider }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($trunk->direction === 'outgoing')
                                <span class="badge badge-success">Outgoing</span>
                            @elseif($trunk->direction === 'incoming')
                                <span class="badge badge-info">Incoming</span>
                            @else
                                <span class="badge badge-purple">Both</span>
                            @endif
                        </td>
                        <td>
                            <div class="text-sm font-mono text-gray-900">{{ $trunk->host }}:{{ $trunk->port }}</div>
                            <div class="text-xs text-gray-500">{{ strtoupper($trunk->transport) }}</div>
                        </td>
                        <td class="font-medium">{{ $trunk->max_channels }}</td>
                        <td>
                            @if($trunk->health_status === 'up')
                                <span class="badge badge-success">Up</span>
                            @elseif($trunk->health_status === 'down')
                                <span class="badge badge-danger">Down</span>
                            @elseif($trunk->health_status === 'degraded')
                                <span class="badge badge-warning">Degraded</span>
                            @else
                                <span class="badge badge-gray">Unknown</span>
                            @endif
                        </td>
                        <td>
                            @if($trunk->status === 'active')
                                <span class="badge badge-success">Active</span>
                            @elseif($trunk->status === 'auto_disabled')
                                <span class="badge badge-warning">Auto-disabled</span>
                            @else
                                <span class="badge badge-danger">Disabled</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('admin.trunks.show', $trunk) }}" class="action-icon" title="View">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            <a href="{{ route('admin.trunks.edit', $trunk) }}" class="action-icon" title="Edit">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-12">
                            <div class="empty-state">
                                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                <p class="empty-text">No trunks found</p>
                                <a href="{{ route('admin.trunks.create') }}" class="empty-link-admin">Create your first trunk</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($trunks->hasPages())
        <div class="mt-6">
            {{ $trunks->withQueryString()->links() }}
        </div>
    @endif
</x-admin-layout>
