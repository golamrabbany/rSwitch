<x-admin-layout>
    <x-slot name="header">Destination Blacklist</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Destination Blacklist</h2>
                <p class="page-subtitle">Block calls to specific destination prefixes</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.blacklist.create') }}" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add Entry
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
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search prefix or description..." class="filter-input">
            </div>

            <select name="applies_to" class="filter-select">
                <option value="">All Scopes</option>
                <option value="all" {{ request('applies_to') === 'all' ? 'selected' : '' }}>Global (All)</option>
                <option value="specific_users" {{ request('applies_to') === 'specific_users' ? 'selected' : '' }}>Specific User</option>
            </select>

            <button type="submit" class="btn-search-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Search
            </button>

            @if(request()->hasAny(['search', 'applies_to']))
                <a href="{{ route('admin.blacklist.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Data Table --}}
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Prefix</th>
                    <th>Description</th>
                    <th>Applies To</th>
                    <th>Created By</th>
                    <th>Date</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $entry)
                    <tr>
                        <td>
                            <span class="font-mono font-medium text-gray-900">{{ $entry->prefix }}</span>
                        </td>
                        <td class="text-gray-600">
                            {{ Str::limit($entry->description, 40) ?: '—' }}
                        </td>
                        <td>
                            @if($entry->applies_to === 'all')
                                <span class="badge badge-danger">Global</span>
                            @else
                                <a href="{{ route('admin.users.show', $entry->user_id) }}" class="text-indigo-600 hover:text-indigo-700 font-medium">
                                    {{ $entry->user?->name ?? 'User #'.$entry->user_id }}
                                </a>
                            @endif
                        </td>
                        <td class="text-gray-600">
                            {{ $entry->creator?->name ?? '—' }}
                        </td>
                        <td class="text-gray-600">
                            {{ $entry->created_at?->format('M d, Y') }}
                        </td>
                        <td class="text-center whitespace-nowrap">
                            <a href="{{ route('admin.blacklist.edit', $entry) }}" class="action-icon" title="Edit">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('admin.blacklist.destroy', $entry) }}" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="action-icon text-red-500 hover:text-red-700 hover:bg-red-50" title="Delete" onclick="return confirm('Delete this blacklist entry?')">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-12">
                            <div class="empty-state">
                                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                                <p class="empty-text">No blacklist entries found</p>
                                <a href="{{ route('admin.blacklist.create') }}" class="empty-link-admin">Add your first entry</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($entries->hasPages())
        <div class="mt-6">
            {{ $entries->withQueryString()->links() }}
        </div>
    @endif
</x-admin-layout>
