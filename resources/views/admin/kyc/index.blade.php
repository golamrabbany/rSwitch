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
    <div class="filter-card">
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
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Applicant</th>
                    <th>Account Type</th>
                    <th>ID Type</th>
                    <th>Documents</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($profiles as $profile)
                    @php $kycStatus = $profile->user?->kyc_status ?? 'none'; @endphp
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="avatar avatar-purple">
                                    {{ strtoupper(substr($profile->full_name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="user-name">{{ $profile->full_name }}</div>
                                    <div class="user-email">{{ $profile->user?->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $profile->account_type === 'company' ? 'badge-blue' : 'badge-gray' }}">
                                {{ ucfirst($profile->account_type) }}
                            </span>
                        </td>
                        <td class="text-gray-700">
                            {{ str_replace('_', ' ', ucfirst($profile->id_type)) }}
                        </td>
                        <td>
                            <div class="kyc-docs-count">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span>{{ $profile->documents->count() }} file(s)</span>
                            </div>
                        </td>
                        <td>
                            @if ($kycStatus === 'approved')
                                <span class="badge badge-success">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Approved
                                </span>
                            @elseif ($kycStatus === 'pending')
                                <span class="badge badge-warning">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                    </svg>
                                    Pending
                                </span>
                            @elseif ($kycStatus === 'rejected')
                                <span class="badge badge-danger">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                    Rejected
                                </span>
                            @else
                                <span class="badge badge-gray">None</span>
                            @endif
                        </td>
                        <td class="text-gray-500 text-sm">
                            @if($profile->submitted_at)
                                <div>{{ $profile->submitted_at->format('M d, Y') }}</div>
                                <div class="text-xs text-gray-400">{{ $profile->submitted_at->diffForHumans() }}</div>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('admin.kyc.show', $profile) }}" class="action-icon" title="Review">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                @if($kycStatus === 'pending')
                                    <form method="POST" action="{{ route('admin.kyc.approve', $profile) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="action-icon action-approve" title="Quick Approve">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-12">
                            <div class="empty-state">
                                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                <p class="empty-text">No KYC submissions found</p>
                                <p class="text-sm text-gray-400">KYC submissions from resellers and clients will appear here</p>
                            </div>
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
