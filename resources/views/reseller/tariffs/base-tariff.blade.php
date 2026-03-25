<x-reseller-layout>
    <x-slot name="header">Base Tariff</x-slot>

    @if($baseTariff)
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $baseTariff->name }}</h2>
                    <div class="flex items-center gap-3 mt-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Base Tariff</span>
                        <span class="text-sm text-gray-500">{{ $rates->total() }} rates</span>
                        <span class="text-sm text-gray-400">Assigned by Admin</span>
                    </div>
                </div>
            </div>
            <a href="{{ route('reseller.base-tariff.export') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export
            </a>
        </div>

        {{-- Info Banner --}}
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-sm text-blue-800 font-medium">This is your cost rate plan</p>
                    <p class="text-sm text-blue-600 mt-0.5">These rates determine your cost per call. Create your own tariff under "My Rate Groups" with higher rates to earn profit on each call.</p>
                </div>
            </div>
        </div>

        {{-- Search --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6 shadow-sm">
            <form method="GET" class="flex items-center gap-3">
                <div class="flex-1 max-w-md relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by prefix or destination..." class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700">Search</button>
                @if(request('search'))
                    <a href="{{ route('reseller.base-tariff') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
                @endif
            </form>
        </div>

        {{-- Rates Table --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                <span class="text-sm text-gray-600">
                    Showing <span class="font-semibold">{{ $rates->firstItem() }}–{{ $rates->lastItem() }}</span> of <span class="font-semibold">{{ $rates->total() }}</span> rates
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="text-left">Prefix</th>
                            <th class="text-left">Destination</th>
                            <th class="text-right">Rate / Minute</th>
                            <th class="text-right">Connection Fee</th>
                            <th class="text-right">Min Duration</th>
                            <th class="text-right">Billing Increment</th>
                            <th class="text-left">Rate Type</th>
                            <th class="text-left">Effective Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rates as $rate)
                            <tr>
                                <td class="font-mono font-semibold text-gray-900">{{ $rate->prefix }}</td>
                                <td>{{ $rate->destination }}</td>
                                <td class="text-right tabular-nums font-medium text-gray-900">{{ format_currency($rate->rate_per_minute, 6) }}</td>
                                <td class="text-right tabular-nums text-gray-600">{{ format_currency($rate->connection_fee, 6) }}</td>
                                <td class="text-right text-gray-600">{{ $rate->min_duration }}s</td>
                                <td class="text-right text-gray-600">{{ $rate->billing_increment }}s</td>
                                <td>
                                    @if($rate->rate_type === 'broadcast')
                                        <span class="badge badge-purple">Broadcast</span>
                                    @else
                                        <span class="badge badge-gray">Regular</span>
                                    @endif
                                </td>
                                <td class="text-gray-500">{{ $rate->effective_date }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-8 text-gray-500">
                                    @if(request('search'))
                                        No rates found for "{{ request('search') }}"
                                    @else
                                        No rates in this rate group
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($rates->hasPages())
                <div class="px-4 py-3 border-t border-gray-200">
                    {{ $rates->withQueryString()->links() }}
                </div>
            @endif
        </div>
    @else
        {{-- No Base Tariff --}}
        <div class="bg-white rounded-xl border border-gray-200 py-16 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-amber-100 flex items-center justify-center">
                <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.27 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-1">No Base Tariff Assigned</h3>
            <p class="text-gray-500 text-sm">Contact your administrator to assign a base tariff to your account.</p>
        </div>
    @endif
</x-reseller-layout>
