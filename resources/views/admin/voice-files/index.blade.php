<x-admin-layout>
    <x-slot name="header">Voice Template</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">Voice Template</h2>
            <p class="page-subtitle">Manage and approve voice templates for broadcasting</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.voice-files.create') }}" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Upload Template
            </a>
        </div>
    </div>

    {{-- Smart Summary --}}
    <div class="flex items-center gap-3 mb-4 flex-wrap">
        <a href="{{ route('admin.voice-files.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border {{ !request('status') ? 'bg-indigo-50 border-indigo-200 text-indigo-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }} text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
            All <span class="px-1.5 py-0.5 rounded-full bg-gray-100 text-xs tabular-nums {{ !request('status') ? 'bg-indigo-100' : '' }}">{{ $stats['total'] }}</span>
        </a>
        <a href="{{ route('admin.voice-files.index', ['status' => 'pending']) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border {{ request('status') === 'pending' ? 'bg-amber-50 border-amber-200 text-amber-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }} text-sm font-medium transition-colors">
            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
            Pending <span class="px-1.5 py-0.5 rounded-full text-xs tabular-nums {{ request('status') === 'pending' ? 'bg-amber-100' : 'bg-gray-100' }}">{{ $stats['pending'] }}</span>
        </a>
        <a href="{{ route('admin.voice-files.index', ['status' => 'approved']) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border {{ request('status') === 'approved' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }} text-sm font-medium transition-colors">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            Approved <span class="px-1.5 py-0.5 rounded-full text-xs tabular-nums {{ request('status') === 'approved' ? 'bg-emerald-100' : 'bg-gray-100' }}">{{ $stats['approved'] }}</span>
        </a>
        <a href="{{ route('admin.voice-files.index', ['status' => 'rejected']) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border {{ request('status') === 'rejected' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }} text-sm font-medium transition-colors">
            <span class="w-2 h-2 rounded-full bg-red-500"></span>
            Rejected <span class="px-1.5 py-0.5 rounded-full text-xs tabular-nums {{ request('status') === 'rejected' ? 'bg-red-100' : 'bg-gray-100' }}">{{ $stats['rejected'] }}</span>
        </a>
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
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <button type="submit" class="btn-search-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Search
            </button>
            @if(request()->hasAny(['status', 'search']))
                <a href="{{ route('admin.voice-files.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Data Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($voiceFiles->total() > 0)
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    Voice Templates Total : {{ number_format($voiceFiles->total()) }} &middot; Showing {{ $voiceFiles->firstItem() }} to {{ $voiceFiles->lastItem() }}
                </span>
            </div>
        @endif
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" width="40">SL</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Client</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Duration</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Format</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Uploaded</th>
                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($voiceFiles as $file)
                    <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-indigo-50/50 transition-all border-b border-gray-100 group">
                        <td class="px-3 py-2 text-gray-400 tabular-nums text-center">{{ $voiceFiles->firstItem() + $loop->index }}</td>
                        <td class="px-3 py-2">
                            <div>
                                <div class="font-medium text-gray-900">{{ $file->name }}</div>
                                <div class="text-xs text-gray-500">{{ $file->original_filename ?? '' }}</div>
                            </div>
                        </td>
                        <td class="px-3 py-2">
                            @if($file->user)
                                <a href="{{ route('admin.users.show', $file->user) }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                                    {{ $file->user->name }}
                                </a>
                                <div class="text-xs text-gray-400">{{ $file->user->email }}</div>
                            @else
                                <span class="text-gray-400">--</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-sm text-gray-700">
                            @if($file->duration)
                                {{ floor($file->duration / 60) }}m {{ $file->duration % 60 }}s
                            @else
                                <span class="text-gray-400">--</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            <span class="badge badge-gray">{{ strtoupper($file->format) }}</span>
                        </td>
                        <td class="px-3 py-2">
                            @switch($file->status)
                                @case('approved')
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Approved</span>
                                    @break
                                @case('pending')
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Pending</span>
                                    @break
                                @case('rejected')
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Rejected</span>
                                    @break
                                @default
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-500"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>{{ ucfirst($file->status) }}</span>
                            @endswitch
                        </td>
                        <td class="px-3 py-2 text-sm text-gray-500">
                            <div>{{ $file->created_at->format('M d, Y') }}</div>
                            <div class="text-xs text-gray-400">{{ $file->created_at->diffForHumans() }}</div>
                        </td>
                        <td class="px-3 py-2 text-center">
                            <div class="flex items-center justify-center gap-1.5 opacity-60 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('admin.voice-files.show', $file) }}" class="p-1.5 rounded-lg text-blue-500 hover:text-blue-700 hover:bg-blue-50 transition-colors" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                @if(auth()->user()->isSuperAdmin())
                                    <a href="{{ route('admin.voice-files.edit', $file) }}" class="p-1.5 rounded-lg text-amber-500 hover:text-amber-700 hover:bg-amber-50 transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-12">
                            <div class="empty-state">
                                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                                </svg>
                                <p class="empty-text">No voice templates found</p>
                                <p class="text-sm text-gray-400">Voice templates uploaded by clients will appear here</p>
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
</x-admin-layout>
