<x-reseller-layout>
    <x-slot name="header">Survey Templates</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Survey Templates</h2>
            <p class="page-subtitle">Reusable survey configurations for broadcasts</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('reseller.survey-templates.create') }}" class="btn-action-primary-reseller">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Create Template
            </a>
        </div>
    </div>

    {{-- Smart Summary Tabs --}}
    @if(isset($stats))
    <div class="flex items-center gap-3 mb-4 flex-wrap">
        <a href="{{ route('reseller.survey-templates.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border {{ !request('status') ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }} text-sm font-medium transition-colors">
            All <span class="px-1.5 py-0.5 rounded-full text-xs tabular-nums {{ !request('status') ? 'bg-emerald-100' : 'bg-gray-100' }}">{{ $stats['total'] }}</span>
        </a>
        <a href="{{ route('reseller.survey-templates.index', ['status' => 'draft']) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border {{ request('status') === 'draft' ? 'bg-gray-100 border-gray-300 text-gray-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }} text-sm font-medium transition-colors">
            <span class="w-2 h-2 rounded-full bg-gray-400"></span>
            Draft <span class="px-1.5 py-0.5 rounded-full text-xs tabular-nums {{ request('status') === 'draft' ? 'bg-gray-200' : 'bg-gray-100' }}">{{ $stats['draft'] ?? 0 }}</span>
        </a>
        <a href="{{ route('reseller.survey-templates.index', ['status' => 'pending']) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border {{ request('status') === 'pending' ? 'bg-amber-50 border-amber-200 text-amber-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }} text-sm font-medium transition-colors">
            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
            Pending <span class="px-1.5 py-0.5 rounded-full text-xs tabular-nums {{ request('status') === 'pending' ? 'bg-amber-100' : 'bg-gray-100' }}">{{ $stats['pending'] }}</span>
        </a>
        <a href="{{ route('reseller.survey-templates.index', ['status' => 'approved']) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border {{ request('status') === 'approved' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }} text-sm font-medium transition-colors">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            Approved <span class="px-1.5 py-0.5 rounded-full text-xs tabular-nums {{ request('status') === 'approved' ? 'bg-emerald-100' : 'bg-gray-100' }}">{{ $stats['approved'] }}</span>
        </a>
        <a href="{{ route('reseller.survey-templates.index', ['status' => 'rejected']) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border {{ request('status') === 'rejected' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }} text-sm font-medium transition-colors">
            <span class="w-2 h-2 rounded-full bg-red-500"></span>
            Rejected <span class="px-1.5 py-0.5 rounded-full text-xs tabular-nums {{ request('status') === 'rejected' ? 'bg-red-100' : 'bg-gray-100' }}">{{ $stats['rejected'] }}</span>
        </a>
    </div>
    @endif

    <div class="filter-card mb-3">
        <form method="GET" class="filter-row">
            <div class="filter-search-box">
                <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search templates..." class="filter-input">
            </div>
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <button type="submit" class="btn-search-reseller">Search</button>
            @if(request('search'))
                <a href="{{ route('reseller.survey-templates.index', request('status') ? ['status' => request('status')] : []) }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($templates->total() > 0)
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    Survey Templates Total : {{ number_format($templates->total()) }} &middot; Showing {{ $templates->firstItem() }} to {{ $templates->lastItem() }}
                </span>
            </div>
        @endif
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" width="40">SL</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Client</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Questions</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Created</th>
                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($templates as $template)
                    <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-emerald-50/50 transition-all border-b border-gray-100 group">
                        <td class="px-3 py-2 text-gray-400 tabular-nums text-center">{{ $templates->firstItem() + $loop->index }}</td>
                        <td class="px-3 py-2">
                            <p class="font-semibold text-gray-800 group-hover:text-emerald-600">{{ $template->name }}</p>
                            @if($template->description)
                                <p class="text-xs text-gray-400">{{ Str::limit($template->description, 50) }}</p>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            <p class="text-sm font-medium text-gray-800">{{ $template->client?->name ?? '—' }}</p>
                            <p class="text-xs text-gray-400">{{ $template->client?->email ?? '' }}</p>
                        </td>
                        <td class="px-3 py-2 text-gray-700 tabular-nums">{{ $template->getQuestionCount() }}</td>
                        <td class="px-3 py-2">
                            @if($template->status === 'approved')
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Approved</span>
                            @elseif($template->status === 'pending')
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Pending</span>
                            @elseif($template->status === 'rejected')
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Rejected</span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-500"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>{{ ucfirst($template->status) }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            <span class="text-gray-800">{{ $template->created_at->format('d M Y') }}</span>
                        </td>
                        <td class="px-3 py-2 text-center">
                            <div class="flex items-center justify-center gap-1.5 opacity-60 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('reseller.survey-templates.show', $template) }}" class="p-1.5 rounded-lg text-blue-500 hover:text-blue-700 hover:bg-blue-50 transition-colors" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                @if(in_array($template->status, ['draft', 'pending']))
                                    <a href="{{ route('reseller.survey-templates.edit', $template) }}" class="p-1.5 rounded-lg text-amber-500 hover:text-amber-700 hover:bg-amber-50 transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                @else
                                    <span class="p-1.5 rounded-lg text-gray-300 cursor-not-allowed" title="{{ ucfirst($template->status) }} — cannot edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center">
                            <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <p class="text-sm text-gray-400">No survey templates found</p>
                            <a href="{{ route('reseller.survey-templates.create') }}" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">Create your first template</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($templates->hasPages())
        <div class="mt-4 flex justify-end">
            {{ $templates->withQueryString()->onEachSide(1)->links('pagination::simple-tailwind') }}
        </div>
    @endif
</x-reseller-layout>
