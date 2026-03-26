<x-admin-layout>
    <x-slot name="header">Profit & Loss</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-400 to-indigo-500 flex items-center justify-center shadow-lg shadow-indigo-200">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Profit & Loss</h2>
                <p class="page-subtitle">Revenue, cost, and profit analysis by reseller</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.operational-reports.profit-loss.export', request()->query()) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        {{-- Total Revenue --}}
        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-indigo-100 text-xs font-medium uppercase tracking-wide">Total Revenue</p>
                    <p class="text-3xl font-bold mt-1">{{ format_currency($totalRevenue) }}</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Reseller Cost --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium uppercase tracking-wide">Reseller Cost</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ format_currency($totalResellerCost) }}</p>
                </div>
                <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Total Profit --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium uppercase tracking-wide">Total Profit</p>
                    <p class="text-3xl font-bold {{ $totalProfit >= 0 ? 'text-emerald-600' : 'text-red-600' }} mt-1">{{ format_currency($totalProfit) }}</p>
                </div>
                <div class="w-12 h-12 {{ $totalProfit >= 0 ? 'bg-emerald-100' : 'bg-red-100' }} rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 {{ $totalProfit >= 0 ? 'text-emerald-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Avg Margin --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium uppercase tracking-wide">Avg Margin</p>
                    <p class="text-3xl font-bold {{ $avgMargin >= 20 ? 'text-emerald-600' : ($avgMargin >= 10 ? 'text-amber-600' : 'text-red-600') }} mt-1">{{ $avgMargin }}%</p>
                </div>
                <div class="w-12 h-12 {{ $avgMargin >= 20 ? 'bg-emerald-100' : ($avgMargin >= 10 ? 'bg-amber-100' : 'bg-red-100') }} rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 {{ $avgMargin >= 20 ? 'text-emerald-600' : ($avgMargin >= 10 ? 'text-amber-600' : 'text-red-600') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <form method="GET">
            <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 0.75rem; align-items: center;">
                <input type="date" name="date_from" value="{{ $dateFrom->format('Y-m-d') }}" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
                <input type="date" name="date_to" value="{{ $dateTo->format('Y-m-d') }}" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
                <div class="flex items-center gap-2">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 whitespace-nowrap">Search</button>
                    @if(request()->hasAny(['date_from', 'date_to']))
                        <a href="{{ route('admin.operational-reports.profit-loss') }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700 whitespace-nowrap">Clear</a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Data Table --}}
    @if($data->count() > 0)
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                <span class="text-sm text-gray-600">
                    Showing <span class="font-semibold">{{ $data->count() }}</span> resellers
                    &middot; Period: <span class="font-semibold">{{ $dateFrom->format('Y-m-d') }}</span> to <span class="font-semibold">{{ $dateTo->format('Y-m-d') }}</span>
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Reseller</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider" style="text-align: right">Total Calls</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider" style="text-align: right">Answered</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider" style="text-align: right">ASR%</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider" style="text-align: right">Minutes</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider" style="text-align: right">Client Revenue</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider" style="text-align: right">Reseller Cost</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider" style="text-align: right">Profit</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider" style="text-align: right">Margin %</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($data as $i => $row)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ $i + 1 }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $row->reseller_name }}</td>
                                <td class="px-4 py-3 text-gray-900" style="text-align: right">{{ number_format($row->total_calls) }}</td>
                                <td class="px-4 py-3 text-gray-900" style="text-align: right">{{ number_format($row->answered_calls) }}</td>
                                <td class="px-4 py-3" style="text-align: right">
                                    <span class="{{ $row->asr >= 50 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $row->asr }}%</span>
                                </td>
                                <td class="px-4 py-3 text-gray-900" style="text-align: right">{{ number_format($row->minutes, 2) }}</td>
                                <td class="px-4 py-3 text-gray-900 font-mono" style="text-align: right">{{ format_currency($row->client_revenue) }}</td>
                                <td class="px-4 py-3 text-gray-900 font-mono" style="text-align: right">{{ format_currency($row->reseller_cost) }}</td>
                                <td class="px-4 py-3 font-mono font-semibold {{ $row->profit >= 0 ? 'text-emerald-600' : 'text-red-600' }}" style="text-align: right">{{ format_currency($row->profit) }}</td>
                                <td class="px-4 py-3 font-semibold" style="text-align: right">
                                    <span class="{{ $row->margin_pct >= 20 ? 'text-emerald-600' : ($row->margin_pct >= 10 ? 'text-amber-600' : 'text-red-600') }}">{{ $row->margin_pct }}%</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50 border-t-2 border-gray-300 font-semibold">
                            <td class="px-4 py-3 text-gray-500"></td>
                            <td class="px-4 py-3 text-gray-900">TOTAL</td>
                            <td class="px-4 py-3 text-gray-900" style="text-align: right">{{ number_format($totalCalls) }}</td>
                            <td class="px-4 py-3 text-gray-900" style="text-align: right">{{ number_format($totalAnswered) }}</td>
                            <td class="px-4 py-3" style="text-align: right">
                                @php $totalAsr = $totalCalls > 0 ? round(($totalAnswered / $totalCalls) * 100, 1) : 0; @endphp
                                <span class="{{ $totalAsr >= 50 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $totalAsr }}%</span>
                            </td>
                            <td class="px-4 py-3 text-gray-900" style="text-align: right">{{ number_format($totalMinutes, 2) }}</td>
                            <td class="px-4 py-3 text-gray-900 font-mono" style="text-align: right">{{ format_currency($totalRevenue) }}</td>
                            <td class="px-4 py-3 text-gray-900 font-mono" style="text-align: right">{{ format_currency($totalResellerCost) }}</td>
                            <td class="px-4 py-3 font-mono {{ $totalProfit >= 0 ? 'text-emerald-600' : 'text-red-600' }}" style="text-align: right">{{ format_currency($totalProfit) }}</td>
                            <td class="px-4 py-3" style="text-align: right">
                                <span class="{{ $avgMargin >= 20 ? 'text-emerald-600' : ($avgMargin >= 10 ? 'text-amber-600' : 'text-red-600') }}">{{ $avgMargin }}%</span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @else
        {{-- Empty State --}}
        <div class="bg-white rounded-xl border border-gray-200 py-16">
            <div class="text-center">
                <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-indigo-100 flex items-center justify-center">
                    <svg class="w-10 h-10 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-1">No Data Found</h3>
                <p class="text-gray-500 text-sm">No call records with reseller data found for the selected period</p>
            </div>
        </div>
    @endif

    {{-- Transit P&L Section --}}
    @if(isset($transitData) && $transitData->count() > 0)
        <div class="mt-8">
            <div class="page-header-row mb-4">
                <div>
                    <h2 class="page-title">Transit P&L (Trunk-to-Trunk)</h2>
                    <p class="page-subtitle">Revenue and cost by trunk pair</p>
                </div>
            </div>

            {{-- Transit Stats --}}
            <div class="mb-5" style="display:grid; grid-template-columns: repeat(5, 1fr); gap:1rem;">
                <div class="stat-card">
                    <div class="stat-icon bg-indigo-100">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-value">{{ number_format($transitTotalCalls) }}</p>
                        <p class="stat-label">Transit Calls</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-indigo-100">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-value">{{ number_format($transitTotalMinutes, 0) }}</p>
                        <p class="stat-label">Minutes</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-emerald-100">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-value text-emerald-600">{{ format_currency($transitTotalRevenue) }}</p>
                        <p class="stat-label">Revenue</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-red-100">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-value text-red-600">{{ format_currency($transitTotalCost) }}</p>
                        <p class="stat-label">Trunk Cost</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon {{ $transitTotalProfit >= 0 ? 'bg-emerald-100' : 'bg-red-100' }}">
                        <svg class="w-5 h-5 {{ $transitTotalProfit >= 0 ? 'text-emerald-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <p class="stat-value {{ $transitTotalProfit >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ format_currency($transitTotalProfit) }}</p>
                        <p class="stat-label">Profit</p>
                    </div>
                </div>
            </div>

            {{-- Transit Table --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        {{ $transitData->count() }} trunk pairs
                    </span>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" width="40">SL</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Incoming Trunk</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Outgoing Trunk</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Calls</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Minutes</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Revenue</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Cost</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Profit</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Margin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transitData as $row)
                            <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-indigo-50/50 transition-all border-b border-gray-100">
                                <td class="px-3 py-2 text-gray-400 tabular-nums text-center">{{ $loop->iteration }}</td>
                                <td class="px-3 py-2 font-medium text-gray-900">{{ $row->incoming_trunk_name }}</td>
                                <td class="px-3 py-2 font-medium text-gray-900">{{ $row->outgoing_trunk_name }}</td>
                                <td class="px-3 py-2 text-right tabular-nums text-gray-700">{{ number_format($row->total_calls) }}</td>
                                <td class="px-3 py-2 text-right tabular-nums text-gray-700">{{ number_format($row->minutes, 0) }}</td>
                                <td class="px-3 py-2 text-right tabular-nums font-bold text-emerald-600">{{ format_currency($row->revenue) }}</td>
                                <td class="px-3 py-2 text-right tabular-nums font-bold text-red-600">{{ format_currency($row->cost) }}</td>
                                <td class="px-3 py-2 text-right tabular-nums font-bold {{ $row->profit >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ format_currency($row->profit) }}</td>
                                <td class="px-3 py-2 text-right tabular-nums {{ $row->margin_pct >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $row->margin_pct }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-100 font-semibold border-t-2 border-gray-300">
                            <td class="px-3 py-2" colspan="3">Total</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ number_format($transitTotalCalls) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ number_format($transitTotalMinutes, 0) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums text-emerald-600">{{ format_currency($transitTotalRevenue) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums text-red-600">{{ format_currency($transitTotalCost) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums {{ $transitTotalProfit >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ format_currency($transitTotalProfit) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $transitTotalRevenue > 0 ? round(($transitTotalProfit / $transitTotalRevenue) * 100, 1) : 0 }}%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif
</x-admin-layout>
