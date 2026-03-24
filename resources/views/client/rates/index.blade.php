<x-client-layout>
    <x-slot name="header">Base Rate</x-slot>

    @if($rateGroup)
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $rateGroup->name }}</h2>
                    <div class="flex items-center gap-3 mt-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">Base Rate</span>
                        <span class="text-sm text-gray-500">{{ $rates->total() }} rates</span>
                        <span class="text-sm text-gray-400">Assigned by Reseller</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Info Banner --}}
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-sm text-blue-800 font-medium">Your call rate plan</p>
                    <p class="text-sm text-blue-600 mt-0.5">These rates determine how much you are charged per call. Rates are set by your reseller.</p>
                </div>
            </div>
        </div>

        {{-- Search --}}
        <div class="filter-card mb-6">
            <form method="GET" class="filter-row">
                <div class="filter-search-box">
                    <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by prefix or destination..." class="filter-input">
                </div>
                <button type="submit" class="btn-search">Search</button>
                @if(request('search'))
                    <a href="{{ route('client.base-rate') }}" class="btn-clear">Clear</a>
                @endif
            </form>
        </div>

        {{-- Rates Table --}}
        <div class="data-table-container">
            @if($rates->total() > 0)
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
                    <span class="text-sm text-gray-600">
                        Showing <span class="font-semibold">{{ $rates->firstItem() }}–{{ $rates->lastItem() }}</span> of <span class="font-semibold">{{ number_format($rates->total()) }}</span> rates
                    </span>
                </div>
            @endif
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Prefix</th>
                        <th>Destination</th>
                        <th style="text-align: right">Rate / Minute</th>
                        <th style="text-align: right">Connection Fee</th>
                        <th style="text-align: right">Min Duration</th>
                        <th style="text-align: right">Billing Increment</th>
                        <th>Effective Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rates as $rate)
                        <tr>
                            <td class="font-mono font-semibold text-gray-900">{{ $rate->prefix }}</td>
                            <td class="text-gray-700">{{ $rate->destination }}</td>
                            <td style="text-align: right" class="tabular-nums font-medium text-gray-900">{{ format_currency($rate->rate_per_minute, 6) }}</td>
                            <td style="text-align: right" class="tabular-nums text-gray-600">{{ format_currency($rate->connection_fee, 6) }}</td>
                            <td style="text-align: right" class="text-gray-600">{{ $rate->min_duration }}s</td>
                            <td style="text-align: right" class="text-gray-600">{{ $rate->billing_increment }}s</td>
                            <td class="text-gray-500">{{ $rate->effective_date }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-12">
                                <div class="empty-state">
                                    <p class="empty-text">
                                        @if(request('search'))
                                            No rates found for "{{ request('search') }}"
                                        @else
                                            No rates in this rate group
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rates->hasPages())
            <div class="mt-4 flex justify-end">
                {{ $rates->withQueryString()->onEachSide(1)->links('pagination::simple-tailwind') }}
            </div>
        @endif
    @else
        {{-- No Rate Group --}}
        <div class="bg-white rounded-xl border border-gray-200 py-16 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-amber-100 flex items-center justify-center">
                <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.27 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-1">No Rate Plan Assigned</h3>
            <p class="text-gray-500 text-sm">Contact your reseller to assign a rate plan to your account.</p>
        </div>
    @endif
</x-client-layout>
