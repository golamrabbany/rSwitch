<x-admin-layout>
    <x-slot name="header">Recharge Admins</x-slot>

    {{-- Section 1: Header with title and action buttons --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">Recharge Admins</h2>
            <p class="page-subtitle">Manage recharge admin accounts with balance-only operations</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.recharge-admins.create') }}" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add Recharge Admin
            </a>
        </div>
    </div>

    {{-- Section 2: Filter Card --}}
    <div class="filter-card mb-3">
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
                <a href="{{ route('admin.recharge-admins.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Section 3: Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($rechargeAdmins->total() > 0)
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    Recharge Admins Total : {{ number_format($rechargeAdmins->total()) }} &middot; Showing {{ $rechargeAdmins->firstItem() }} to {{ $rechargeAdmins->lastItem() }}
                </span>
            </div>
        @endif
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">SL</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Account</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Assigned Resellers</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Created</th>
                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rechargeAdmins as $admin)
                    <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-indigo-50/50 transition-all border-b border-gray-100 group">
                        <td class="px-3 py-2 text-gray-500">{{ $rechargeAdmins->firstItem() + $loop->index }}</td>
                        <td class="px-3 py-2">
                            <div class="font-medium text-gray-900">{{ $admin->name }}</div>
                            <div class="text-xs text-gray-500">{{ $admin->email }}</div>
                        </td>
                        <td class="px-3 py-2">
                            @if($admin->status === 'active')
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Active</span>
                            @elseif($admin->status === 'suspended')
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Suspended</span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Disabled</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            @if($admin->assigned_resellers_count > 0)
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="badge badge-amber">
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
                        <td class="px-3 py-2 text-gray-500">
                            {{ $admin->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex items-center justify-center gap-1.5 opacity-60 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('admin.recharge-admins.show', $admin) }}" class="p-1 rounded text-blue-500 hover:text-blue-700 hover:bg-blue-50" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('admin.recharge-admins.edit', $admin) }}" class="p-1 rounded text-amber-500 hover:text-amber-700 hover:bg-amber-50" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <form action="{{ route('admin.recharge-admins.destroy', $admin) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Are you sure you want to delete this recharge admin?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 rounded text-red-500 hover:text-red-700 hover:bg-red-50" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-12">
                            <div class="empty-state">
                                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="empty-text">No recharge admins found</p>
                                <a href="{{ route('admin.recharge-admins.create') }}" class="empty-link-admin">Create your first recharge admin</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($rechargeAdmins->hasPages())
        <div class="mt-6">
            {{ $rechargeAdmins->withQueryString()->links() }}
        </div>
    @endif
</x-admin-layout>
