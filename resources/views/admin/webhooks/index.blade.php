<x-admin-layout>
    <x-slot name="header">Webhook Endpoints</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Webhook Endpoints</h2>
                <p class="page-subtitle">Manage webhook integrations</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.webhooks.create') }}" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                New Endpoint
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
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search URL or description..." class="filter-input">
            </div>

            <select name="status" class="filter-select">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>

            <button type="submit" class="btn-search-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filter
            </button>

            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('admin.webhooks.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Data Table --}}
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>URL</th>
                    <th>User</th>
                    <th>Events</th>
                    <th>Status</th>
                    <th>Failures</th>
                    <th>Last Triggered</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($endpoints as $ep)
                    <tr>
                        <td>
                            <div class="max-w-xs">
                                <a href="{{ route('admin.webhooks.show', $ep) }}" class="text-indigo-600 hover:text-indigo-700 font-medium truncate block">
                                    {{ Str::limit($ep->url, 40) }}
                                </a>
                                @if ($ep->description)
                                    <p class="text-xs text-gray-500 truncate">{{ $ep->description }}</p>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="user-cell">
                                <div class="avatar avatar-indigo">
                                    {{ strtoupper(substr($ep->user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="user-name">{{ $ep->user->name }}</div>
                                    <div class="user-email">{{ ucfirst($ep->user->role) }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-info">{{ count($ep->events) }} events</span>
                        </td>
                        <td>
                            @if ($ep->active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <span class="{{ $ep->failure_count > 0 ? 'text-red-600 font-semibold' : 'text-gray-500' }}">
                                {{ $ep->failure_count }}
                            </span>
                        </td>
                        <td class="text-gray-600">
                            {{ $ep->last_triggered_at?->diffForHumans() ?? 'Never' }}
                        </td>
                        <td class="text-center whitespace-nowrap">
                            <a href="{{ route('admin.webhooks.show', $ep) }}" class="action-icon" title="View">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            <a href="{{ route('admin.webhooks.edit', $ep) }}" class="action-icon" title="Edit">
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                                <p class="empty-text">No webhook endpoints configured</p>
                                <a href="{{ route('admin.webhooks.create') }}" class="empty-link-admin">Create your first endpoint</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($endpoints->hasPages())
        <div class="mt-6">
            {{ $endpoints->withQueryString()->links() }}
        </div>
    @endif
</x-admin-layout>
