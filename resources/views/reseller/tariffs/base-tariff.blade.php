<x-reseller-layout>
    <x-slot name="header">Base Rate Group</x-slot>

    @if($baseTariff)
        {{-- Page Header --}}
        <div class="page-header-row">
            <div>
                <h2 class="page-title">{{ $baseTariff->name }}</h2>
                <p class="page-subtitle">Your base cost rate plan &middot; {{ number_format($rates->total()) }} rates &middot; Assigned by Admin</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('reseller.base-tariff.export') }}" class="btn-action-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export XLSX
                </a>
            </div>
        </div>

        {{-- Info Banner --}}
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-sm text-blue-800 font-medium">This is your cost rate plan</p>
                    <p class="text-sm text-blue-600 mt-0.5">These rates determine your cost per call. Create your own rate group under "My Rate Groups" with higher rates to earn profit.</p>
                </div>
            </div>
        </div>

        {{-- Filter --}}
        <div class="filter-card mb-3">
            <form method="GET" class="filter-row">
                <div class="filter-search-box">
                    <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search prefix or destination..." class="filter-input">
                </div>
                <button type="submit" class="btn-search-reseller">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Search
                </button>
                @if(request('search'))
                    <a href="{{ route('reseller.base-tariff') }}" class="btn-clear">Clear</a>
                @endif
            </form>
        </div>

        {{-- Data Table --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            @if($rates->total() > 0)
                <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        Rates Total : {{ number_format($rates->total()) }} &middot; Showing {{ $rates->firstItem() }} to {{ $rates->lastItem() }}
                    </span>
                </div>
            @endif
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" width="40">SL</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Prefix</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Destination</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Rate/Min</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Conn. Fee</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Min Dur.</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Increment</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Effective</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rates as $rate)
                        <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-emerald-50/50 transition-all border-b border-gray-100">
                            <td class="px-3 py-2 text-gray-400 tabular-nums text-center">{{ $rates->firstItem() + $loop->index }}</td>
                            <td class="px-3 py-2 font-mono font-semibold text-gray-900">{{ $rate->prefix }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ $rate->destination }}</td>
                            <td class="px-3 py-2 text-right tabular-nums font-medium text-gray-900">{{ format_currency($rate->rate_per_minute, 6) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums text-gray-600">{{ format_currency($rate->connection_fee, 6) }}</td>
                            <td class="px-3 py-2 text-right text-gray-600">{{ $rate->min_duration }}s</td>
                            <td class="px-3 py-2 text-right text-gray-600">{{ $rate->billing_increment }}s</td>
                            <td class="px-3 py-2">
                                @if($rate->rate_type === 'broadcast')
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-purple-700"><span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>Broadcast</span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-500"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>Regular</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-500">{{ $rate->effective_date }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center">
                                <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="text-sm text-gray-400">
                                    @if(request('search'))
                                        No rates found for "{{ request('search') }}"
                                    @else
                                        No rates in this rate group
                                    @endif
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rates->hasPages())
            <div class="mt-4 flex justify-end">
                {{ $rates->withQueryString()->links() }}
            </div>
        @endif
    @else
        {{-- No Base Tariff --}}
        <div class="page-header-row">
            <div>
                <h2 class="page-title">Base Rate Group</h2>
                <p class="page-subtitle">Your cost rate plan assigned by admin</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 py-16 text-center">
            <svg class="w-12 h-12 text-amber-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 mb-1">No Base Rate Group Assigned</h3>
            <p class="text-gray-500 text-sm">Contact your administrator to assign a base rate group to your account.</p>
        </div>
    @endif
</x-reseller-layout>
