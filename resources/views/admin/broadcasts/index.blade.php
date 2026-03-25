<x-admin-layout>
    <x-slot name="header">Broadcasts</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">Broadcasts</h2>
            <p class="page-subtitle">Manage all voice broadcast campaigns</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.broadcasts.create') }}" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                New Broadcast
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
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, client..." class="filter-input">
            </div>
            <select name="status" class="filter-select">
                <option value="">All Statuses</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                <option value="queued" {{ request('status') === 'queued' ? 'selected' : '' }}>Queued</option>
                <option value="running" {{ request('status') === 'running' ? 'selected' : '' }}>Running</option>
                <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>Paused</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
            </select>
            <button type="submit" class="btn-search-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Search
            </button>
            @if(request()->hasAny(['status', 'search']))
                <a href="{{ route('admin.broadcasts.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Data Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($broadcasts->total() > 0)
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    Broadcasts Total : {{ number_format($broadcasts->total()) }} &middot; Showing {{ $broadcasts->firstItem() }} to {{ $broadcasts->lastItem() }}
                </span>
            </div>
        @endif
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" width="40">SL</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Client</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Progress</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Cost</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Created</th>
                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($broadcasts as $broadcast)
                    <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-indigo-50/50 transition-all border-b border-gray-100 group">
                        <td class="px-3 py-2 text-gray-400 tabular-nums text-center">{{ $broadcasts->firstItem() + $loop->index }}</td>
                        <td class="px-3 py-2">
                            <div class="font-medium text-gray-900">{{ $broadcast->name }}</div>
                        </td>
                        <td class="px-3 py-2">
                            @if($broadcast->user)
                                <a href="{{ route('admin.users.show', $broadcast->user) }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                                    {{ $broadcast->user->name }}
                                </a>
                                <div class="text-xs text-gray-400">{{ $broadcast->user->email }}</div>
                            @else
                                <span class="text-gray-400">--</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            @if($broadcast->type === 'survey')
                                <span class="badge badge-purple">Survey</span>
                            @else
                                <span class="badge badge-blue">Simple</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            @switch($broadcast->status)
                                @case('draft')
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-500"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>Draft</span>
                                    @break
                                @case('scheduled')
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-blue-700"><span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>Scheduled</span>
                                    @break
                                @case('queued')
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-blue-700"><span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>Queued</span>
                                    @break
                                @case('running')
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Running</span>
                                    @break
                                @case('paused')
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Paused</span>
                                    @break
                                @case('completed')
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Completed</span>
                                    @break
                                @case('cancelled')
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-500"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>Cancelled</span>
                                    @break
                                @case('failed')
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Failed</span>
                                    @break
                            @endswitch
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-2">
                                <div class="w-20 bg-gray-200 rounded-full h-2">
                                    <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ $broadcast->total_numbers > 0 ? round($broadcast->dialed_count / $broadcast->total_numbers * 100) : 0 }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500">{{ $broadcast->answered_count }}/{{ $broadcast->total_numbers }}</span>
                            </div>
                        </td>
                        <td class="px-3 py-2 text-right text-sm text-gray-700">
                            {{ format_currency($broadcast->total_cost ?? 0) }}
                        </td>
                        <td class="px-3 py-2 text-sm text-gray-500">
                            <div>{{ $broadcast->created_at->format('M d, Y') }}</div>
                            <div class="text-xs text-gray-400">{{ $broadcast->created_at->diffForHumans() }}</div>
                        </td>
                        <td class="px-3 py-2 text-center">
                            <div class="flex items-center justify-center gap-1.5 opacity-60 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('admin.broadcasts.show', $broadcast) }}" class="p-1 rounded text-blue-500 hover:text-blue-700 hover:bg-blue-50 transition-colors" title="View">
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
                        <td colspan="9" class="text-center py-12">
                            <div class="empty-state">
                                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                </svg>
                                <p class="empty-text">No broadcasts found</p>
                                <p class="text-sm text-gray-400">Broadcast campaigns will appear here</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($broadcasts->hasPages())
        <div class="mt-4 flex justify-end">
            {{ $broadcasts->withQueryString()->onEachSide(1)->links('pagination::simple-tailwind') }}
        </div>
    @endif
</x-admin-layout>
