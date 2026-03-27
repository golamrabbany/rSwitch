<x-reseller-layout>
    <x-slot name="header">Voice Template</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">Voice Template</h2>
            <p class="page-subtitle">Manage voice templates for broadcasting</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('reseller.voice-files.create') }}" class="btn-action-primary-reseller">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Upload Template
            </a>
        </div>
    </div>

    {{-- Smart Summary Tabs --}}
    @if(isset($stats))
    <div class="flex items-center gap-3 mb-4 flex-wrap">
        <a href="{{ route('reseller.voice-files.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border {{ !request('status') ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }} text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
            All <span class="px-1.5 py-0.5 rounded-full text-xs tabular-nums {{ !request('status') ? 'bg-emerald-100' : 'bg-gray-100' }}">{{ $stats['total'] }}</span>
        </a>
        <a href="{{ route('reseller.voice-files.index', ['status' => 'pending']) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border {{ request('status') === 'pending' ? 'bg-amber-50 border-amber-200 text-amber-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }} text-sm font-medium transition-colors">
            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
            Pending <span class="px-1.5 py-0.5 rounded-full text-xs tabular-nums {{ request('status') === 'pending' ? 'bg-amber-100' : 'bg-gray-100' }}">{{ $stats['pending'] }}</span>
        </a>
        <a href="{{ route('reseller.voice-files.index', ['status' => 'approved']) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border {{ request('status') === 'approved' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }} text-sm font-medium transition-colors">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            Approved <span class="px-1.5 py-0.5 rounded-full text-xs tabular-nums {{ request('status') === 'approved' ? 'bg-emerald-100' : 'bg-gray-100' }}">{{ $stats['approved'] }}</span>
        </a>
        <a href="{{ route('reseller.voice-files.index', ['status' => 'rejected']) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border {{ request('status') === 'rejected' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }} text-sm font-medium transition-colors">
            <span class="w-2 h-2 rounded-full bg-red-500"></span>
            Rejected <span class="px-1.5 py-0.5 rounded-full text-xs tabular-nums {{ request('status') === 'rejected' ? 'bg-red-100' : 'bg-gray-100' }}">{{ $stats['rejected'] }}</span>
        </a>
    </div>
    @endif

    {{-- Filter Card --}}
    <div class="filter-card mb-3">
        <form method="GET" class="filter-row">
            <div class="filter-search-box">
                <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name..." class="filter-input">
            </div>
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <button type="submit" class="btn-search-reseller">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Search
            </button>
            @if(request()->hasAny(['search']))
                <a href="{{ route('reseller.voice-files.index', request('status') ? ['status' => request('status')] : []) }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Data Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($voiceFiles->total() > 0)
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    Total : {{ number_format($voiceFiles->total()) }} &middot; Showing {{ $voiceFiles->firstItem() }}–{{ $voiceFiles->lastItem() }}
                </span>
            </div>
        @endif
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" width="40">SL</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Duration</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Format</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Uploaded</th>
                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($voiceFiles as $file)
                    <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-emerald-50/50 transition-all border-b border-gray-100 group">
                        <td class="px-3 py-2 text-gray-400 tabular-nums text-center">{{ $voiceFiles->firstItem() + $loop->index }}</td>
                        <td class="px-3 py-2">
                            <p class="font-semibold text-gray-800 group-hover:text-emerald-600 transition-colors">{{ $file->name }}</p>
                            <p class="text-xs text-gray-400">{{ $file->original_filename ?? '' }}</p>
                        </td>
                        <td class="px-3 py-2 text-gray-700 tabular-nums">
                            @if($file->duration)
                                {{ floor($file->duration / 60) }}:{{ str_pad($file->duration % 60, 2, '0', STR_PAD_LEFT) }}
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            <span class="badge badge-gray">{{ strtoupper($file->format) }}</span>
                        </td>
                        <td class="px-3 py-2">
                            @if($file->status === 'approved')
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Approved</span>
                            @elseif($file->status === 'pending')
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Pending</span>
                            @elseif($file->status === 'rejected')
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Rejected</span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-500"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>{{ ucfirst($file->status) }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            <span class="text-gray-800">{{ $file->created_at->format('d M Y') }}</span>
                            <span class="block text-xs text-gray-400">{{ $file->created_at->diffForHumans() }}</span>
                        </td>
                        <td class="px-3 py-2 text-center">
                            <div class="flex items-center justify-center gap-1.5 opacity-60 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('reseller.voice-files.show', $file) }}" class="p-1.5 rounded-lg text-blue-500 hover:text-blue-700 hover:bg-blue-50 transition-colors" title="View">
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
                        <td colspan="7" class="px-4 py-12 text-center">
                            <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                            </svg>
                            <p class="text-sm text-gray-400">No voice templates found</p>
                            <a href="{{ route('reseller.voice-files.create') }}" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">Upload your first template</a>
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
