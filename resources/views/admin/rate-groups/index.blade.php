<x-admin-layout>
    <x-slot name="header">Rate Management</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">Rate Groups</h2>
            <p class="page-subtitle">Manage rate groups and pricing</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.rate-groups.create') }}" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Create Rate Group
            </a>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="filter-card mb-3">
        <form method="GET" class="filter-row flex-wrap">
            <div class="filter-search-box">
                <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search rate group name..." class="filter-input">
            </div>

            <select name="type" class="filter-select">
                <option value="">All Types</option>
                <option value="admin" {{ request('type') === 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="reseller" {{ request('type') === 'reseller' ? 'selected' : '' }}>Reseller</option>
            </select>

            <button type="submit" class="btn-search-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Search
            </button>

            @if(request()->hasAny(['search', 'type']))
                <a href="{{ route('admin.rate-groups.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Data Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($rateGroups->total() > 0)
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    Rate Groups Total : {{ number_format($rateGroups->total()) }} &middot; Showing {{ $rateGroups->firstItem() }} to {{ $rateGroups->lastItem() }}
                </span>
            </div>
        @endif
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" width="40">SL</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Rate Group</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Rates</th>
                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Users</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Created By</th>
                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rateGroups as $group)
                    <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-indigo-50/50 transition-all border-b border-gray-100 group">
                        <td class="px-3 py-2 text-gray-400 tabular-nums text-center">{{ $rateGroups->firstItem() + $loop->index }}</td>
                        <td class="px-3 py-2">
                            <div>
                                <div class="font-medium text-gray-900">{{ $group->name }}</div>
                                @if($group->description)
                                    <div class="text-xs text-gray-500">{{ Str::limit($group->description, 40) }}</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-3 py-2">
                            @if($group->type === 'admin')
                                <span class="badge badge-blue">Admin</span>
                            @else
                                <span class="badge badge-purple">Reseller</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center">
                            <span class="font-semibold text-gray-900">{{ number_format($group->rates_count) }}</span>
                        </td>
                        <td class="px-3 py-2 text-center">
                            <span class="font-semibold text-gray-900">{{ number_format($group->users_count) }}</span>
                        </td>
                        <td class="px-3 py-2">
                            @if($group->creator)
                                <span class="text-gray-700">{{ $group->creator->name }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center">
                            <div class="flex items-center justify-center gap-0.5 opacity-60 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('admin.rate-groups.show', $group) }}" class="p-1 rounded text-blue-500 hover:text-blue-700 hover:bg-blue-50 transition-colors" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('admin.rate-groups.edit', $group) }}" class="p-1 rounded text-amber-500 hover:text-amber-700 hover:bg-amber-50 transition-colors" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="empty-text">No rate groups found</p>
                                <a href="{{ route('admin.rate-groups.create') }}" class="empty-link-admin">Create your first rate group</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($rateGroups->hasPages())
        <div class="mt-6">
            {{ $rateGroups->withQueryString()->links() }}
        </div>
    @endif
</x-admin-layout>
