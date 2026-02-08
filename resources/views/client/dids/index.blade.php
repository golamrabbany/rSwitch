<x-client-layout>
    <x-slot name="header">DIDs</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">My DIDs</h2>
            <p class="page-subtitle">View your assigned phone numbers</p>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="filter-card">
        <form method="GET" class="filter-row">
            <div class="filter-search-box">
                <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by number..." class="filter-input">
            </div>

            <select name="status" class="filter-select">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
            </select>

            <button type="submit" class="btn-search-client">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Search
            </button>

            @if(request()->hasAny(['status', 'search']))
                <a href="{{ route('client.dids.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Number</th>
                    <th>Trunk</th>
                    <th>Destination</th>
                    <th class="text-right">Monthly Price</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($dids as $did)
                    <tr>
                        <td>
                            <span class="font-mono font-medium text-gray-900">{{ $did->number }}</span>
                        </td>
                        <td class="text-gray-500">{{ $did->trunk?->name ?? '—' }}</td>
                        <td>
                            @if($did->destination_type === 'sip_account' && $did->destinationSipAccount)
                                <span class="badge badge-info">SIP</span>
                                <span class="ml-1 text-gray-900">{{ $did->destinationSipAccount->username }}</span>
                            @elseif($did->destination_type === 'external' && $did->destination_number)
                                <span class="badge badge-purple">External</span>
                                <span class="ml-1 font-mono text-gray-900">{{ $did->destination_number }}</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="text-right font-medium">{{ format_currency($did->monthly_price) }}</td>
                        <td>
                            @if($did->status === 'active')
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-warning">Suspended</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('client.dids.show', $did) }}" class="action-icon" title="View">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-12">
                            <div class="empty-state">
                                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                </svg>
                                <p class="empty-text">No DIDs assigned to your account</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($dids->hasPages())
        <div class="mt-6">
            {{ $dids->withQueryString()->links() }}
        </div>
    @endif
</x-client-layout>
