<x-admin-layout>
    <x-slot name="header">KYC Review</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">KYC Review</h2>
            <p class="page-subtitle">Review and manage customer verification requests</p>
        </div>
        <div class="page-actions">
            <div class="kyc-stats-row">
                <div class="kyc-stat-item kyc-stat-pending">
                    <span class="kyc-stat-count">{{ $stats['pending'] ?? 0 }}</span>
                    <span class="kyc-stat-label">Pending</span>
                </div>
                <div class="kyc-stat-item kyc-stat-approved">
                    <span class="kyc-stat-count">{{ $stats['approved'] ?? 0 }}</span>
                    <span class="kyc-stat-label">Approved</span>
                </div>
                <div class="kyc-stat-item kyc-stat-rejected">
                    <span class="kyc-stat-count">{{ $stats['rejected'] ?? 0 }}</span>
                    <span class="kyc-stat-label">Rejected</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="filter-card mb-3">
        <form method="GET" class="filter-row flex-wrap">
            <div class="filter-search-box">
                <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email, ID number..." class="filter-input">
            </div>

            <select name="status" class="filter-select">
                <option value="">All Statuses</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>

            <select name="account_type" class="filter-select">
                <option value="">All Account Types</option>
                <option value="individual" {{ request('account_type') === 'individual' ? 'selected' : '' }}>Individual</option>
                <option value="company" {{ request('account_type') === 'company' ? 'selected' : '' }}>Company</option>
            </select>

            <button type="submit" class="btn-search-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Search
            </button>

            @if(request()->hasAny(['status', 'account_type', 'search']))
                <a href="{{ route('admin.kyc.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Data Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($profiles->total() > 0)
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    KYC Reviews Total : {{ number_format($profiles->total()) }} &middot; Showing {{ $profiles->firstItem() }} to {{ $profiles->lastItem() }}
                </span>
            </div>
        @endif
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" width="40">SL</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Applicant</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ID Type</th>
                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Docs</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Submitted</th>
                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($profiles as $profile)
                    @php $kycStatus = $profile->user?->kyc_status ?? 'none'; @endphp
                    <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-indigo-50/50 transition-all border-b border-gray-100 group">
                        <td class="px-3 py-2 text-gray-400 tabular-nums text-center">{{ $profiles->firstItem() + $loop->index }}</td>
                        <td class="px-3 py-2">
                            <a href="{{ route('admin.kyc.show', $profile) }}">
                                <p class="font-semibold text-gray-800 group-hover:text-indigo-600 transition-colors">{{ $profile->full_name }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $profile->user?->email }}</p>
                            </a>
                        </td>
                        <td class="px-3 py-2">
                            <span class="badge {{ $profile->account_type === 'company' ? 'badge-blue' : 'badge-gray' }}">{{ ucfirst($profile->account_type) }}</span>
                        </td>
                        <td class="px-3 py-2 text-gray-600">{{ str_replace('_', ' ', ucfirst($profile->id_type)) }}</td>
                        <td class="px-3 py-2 text-center">
                            <span class="inline-flex items-center gap-1 text-gray-500">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                {{ $profile->documents->count() }}
                            </span>
                        </td>
                        <td class="px-3 py-2">
                            @if ($kycStatus === 'approved')
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Approved</span>
                            @elseif ($kycStatus === 'pending')
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Pending</span>
                            @elseif ($kycStatus === 'rejected')
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Rejected</span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-400"><span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>None</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            @if($profile->submitted_at)
                                <p class="text-gray-700">{{ $profile->submitted_at->format('M d, Y') }}</p>
                                <p class="text-xs text-gray-400">{{ $profile->submitted_at->diffForHumans() }}</p>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center">
                            <div class="flex items-center justify-center gap-1.5 opacity-60 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('admin.kyc.show', $profile) }}" class="p-1.5 rounded-lg text-blue-500 hover:text-blue-700 hover:bg-blue-50 transition-colors" title="Review">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                @if($kycStatus === 'pending')
                                    <form method="POST" action="{{ route('admin.kyc.approve', $profile) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="p-1.5 rounded-lg text-emerald-500 hover:text-emerald-700 hover:bg-emerald-50 transition-colors" title="Quick Approve">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center">
                            <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            <p class="text-sm text-gray-400">No KYC submissions found</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($profiles->hasPages())
        <div class="mt-6">
            {{ $profiles->withQueryString()->links() }}
        </div>
    @endif
</x-admin-layout>
