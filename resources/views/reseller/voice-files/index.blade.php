<x-reseller-layout>
    <x-slot name="header">Voice Files</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">Voice Files</h2>
            <p class="page-subtitle">Voice files uploaded by your clients</p>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="filter-card">
        <form method="GET" class="filter-row flex-wrap">
            <div class="filter-search-box">
                <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, client..." class="filter-input">
            </div>
            <select name="status" class="filter-select">
                <option value="">All Statuses</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
            <button type="submit" class="btn-search-reseller">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Search
            </button>
            @if(request()->hasAny(['status', 'search']))
                <a href="{{ route('reseller.voice-files.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Data Table --}}
    <div class="data-table-container">
        @if($voiceFiles->total() > 0)
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
                <span class="text-sm text-gray-600">
                    Showing <span class="font-semibold">{{ $voiceFiles->firstItem() }}–{{ $voiceFiles->lastItem() }}</span> of <span class="font-semibold">{{ number_format($voiceFiles->total()) }}</span> voice files
                </span>
            </div>
        @endif
        <table class="data-table data-table-compact">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Client</th>
                    <th>Duration</th>
                    <th>Format</th>
                    <th>Status</th>
                    <th>Uploaded</th>
                    <th style="text-align: center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($voiceFiles as $file)
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="avatar avatar-emerald">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="user-name">{{ $file->name }}</div>
                                    <div class="user-email">{{ $file->original_filename ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($file->user)
                                <div class="text-sm font-medium text-gray-900">{{ $file->user->name }}</div>
                                <div class="text-xs text-gray-400">{{ $file->user->email }}</div>
                            @else
                                <span class="text-gray-400">--</span>
                            @endif
                        </td>
                        <td class="text-sm text-gray-700">
                            @if($file->duration)
                                {{ floor($file->duration / 60) }}m {{ $file->duration % 60 }}s
                            @else
                                <span class="text-gray-400">--</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-gray">{{ strtoupper($file->format) }}</span>
                        </td>
                        <td>
                            @switch($file->status)
                                @case('approved')
                                    <span class="badge badge-success">Approved</span>
                                    @break
                                @case('pending')
                                    <span class="badge badge-warning">Pending</span>
                                    @break
                                @case('rejected')
                                    <span class="badge badge-danger">Rejected</span>
                                    @break
                                @default
                                    <span class="badge badge-gray">{{ ucfirst($file->status) }}</span>
                            @endswitch
                        </td>
                        <td class="text-sm text-gray-500">
                            <div>{{ $file->created_at->format('M d, Y') }}</div>
                            <div class="text-xs text-gray-400">{{ $file->created_at->diffForHumans() }}</div>
                        </td>
                        <td>
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('reseller.voice-files.show', $file) }}" class="action-icon" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                                </svg>
                                <p class="empty-text">No voice files found</p>
                                <p class="text-sm text-gray-400">Voice files uploaded by your clients will appear here</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($voiceFiles->hasPages())
        <div class="mt-4 flex justify-end">
            {{ $voiceFiles->withQueryString()->onEachSide(1)->links('pagination::simple-tailwind') }}
        </div>
    @endif
</x-reseller-layout>
