<x-admin-layout>
    <x-slot name="header">Ring Groups</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">Ring Groups</h2>
            <p class="page-subtitle">Manage call distribution groups</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.ring-groups.create') }}" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add Ring Group
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
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, description..." class="filter-input">
            </div>

            <select name="status" class="filter-select">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
            </select>

            <select name="strategy" class="filter-select">
                <option value="">All Strategies</option>
                <option value="simultaneous" {{ request('strategy') === 'simultaneous' ? 'selected' : '' }}>Simultaneous</option>
                <option value="sequential" {{ request('strategy') === 'sequential' ? 'selected' : '' }}>Sequential</option>
                <option value="random" {{ request('strategy') === 'random' ? 'selected' : '' }}>Random</option>
            </select>

            <button type="submit" class="btn-search-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Search
            </button>

            @if(request()->hasAny(['status', 'search', 'strategy']))
                <a href="{{ route('admin.ring-groups.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Data Table --}}
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Ring Group</th>
                    <th>Strategy</th>
                    <th>Members</th>
                    <th>Owner</th>
                    <th>Timeout</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($ringGroups as $rg)
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="avatar avatar-purple">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <a href="{{ route('admin.ring-groups.show', $rg) }}" class="user-name text-indigo-600 hover:text-indigo-800">{{ $rg->name }}</a>
                                    @if ($rg->description)
                                        <div class="user-email">{{ Str::limit($rg->description, 40) }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($rg->strategy === 'simultaneous')
                                <span class="badge badge-info">Simultaneous</span>
                            @elseif($rg->strategy === 'sequential')
                                <span class="badge badge-purple">Sequential</span>
                            @else
                                <span class="badge badge-warning">Random</span>
                            @endif
                        </td>
                        <td>
                            <span class="font-medium">{{ $rg->members_count }}</span>
                            <span class="text-gray-400">members</span>
                        </td>
                        <td>
                            @if($rg->user)
                                <a href="{{ route('admin.users.show', $rg->user) }}" class="text-indigo-600 hover:text-indigo-700">
                                    {{ $rg->user->name }}
                                </a>
                            @else
                                <span class="text-gray-400">Global</span>
                            @endif
                        </td>
                        <td>
                            <span class="form-input-mono">{{ $rg->ring_timeout }}s</span>
                        </td>
                        <td>
                            @if($rg->status === 'active')
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Disabled</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('admin.ring-groups.show', $rg) }}" class="action-icon" title="View">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            <a href="{{ route('admin.ring-groups.edit', $rg) }}" class="action-icon" title="Edit">
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <p class="empty-text">No ring groups found</p>
                                <a href="{{ route('admin.ring-groups.create') }}" class="empty-link-admin">Create your first ring group</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($ringGroups->hasPages())
        <div class="mt-6">
            {{ $ringGroups->withQueryString()->links() }}
        </div>
    @endif
</x-admin-layout>
