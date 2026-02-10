<x-admin-layout>
    <x-slot name="header">Regular Admins</x-slot>

    {{-- Section 1: Header with title and action buttons --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">Regular Admins</h2>
            <p class="page-subtitle">Manage admin accounts and their reseller assignments</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.admins.create') }}" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add Regular Admin
            </a>
        </div>
    </div>

    {{-- Section 2: Filter Card --}}
    <div class="filter-card">
        <form method="GET" class="filter-row">
            <div class="filter-search-box">
                <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, email..." class="filter-input">
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

            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('admin.admins.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Section 3: Table --}}
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Account</th>
                    <th>Status</th>
                    <th>Assigned Resellers</th>
                    <th>Created</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($admins as $admin)
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="avatar avatar-indigo">
                                    {{ strtoupper(substr($admin->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="user-name">{{ $admin->name }}</div>
                                    <div class="user-email">{{ $admin->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($admin->status === 'active')
                                <span class="badge badge-success">Active</span>
                            @elseif($admin->status === 'suspended')
                                <span class="badge badge-warning">Suspended</span>
                            @else
                                <span class="badge badge-danger">Disabled</span>
                            @endif
                        </td>
                        <td>
                            @if($admin->assigned_resellers_count > 0)
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="badge badge-blue">
                                        {{ $admin->assigned_resellers_count }} reseller{{ $admin->assigned_resellers_count > 1 ? 's' : '' }}
                                    </span>
                                    @foreach($admin->assignedResellers->take(2) as $reseller)
                                        <span class="text-xs text-gray-500">{{ $reseller->name }}</span>
                                        @if(!$loop->last), @endif
                                    @endforeach
                                    @if($admin->assignedResellers->count() > 2)
                                        <span class="text-xs text-gray-400">+{{ $admin->assignedResellers->count() - 2 }} more</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-gray-400 text-sm">No resellers assigned</span>
                            @endif
                        </td>
                        <td class="text-sm text-gray-500">
                            {{ $admin->created_at->format('M d, Y') }}
                        </td>
                        <td class="text-center whitespace-nowrap">
                            <a href="{{ route('admin.admins.show', $admin) }}" class="action-icon" title="View">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            <a href="{{ route('admin.admins.edit', $admin) }}" class="action-icon" title="Edit">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            @if($admin->id !== auth()->id())
                                <form action="{{ route('admin.admins.destroy', $admin) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Are you sure you want to delete this admin?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-icon action-icon-danger" title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-12">
                            <div class="empty-state">
                                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <p class="empty-text">No regular admins found</p>
                                <a href="{{ route('admin.admins.create') }}" class="empty-link-admin">Create your first regular admin</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($admins->hasPages())
        <div class="mt-6">
            {{ $admins->withQueryString()->links() }}
        </div>
    @endif
</x-admin-layout>
