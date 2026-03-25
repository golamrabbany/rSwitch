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
            <a href="{{ route('admin.operational-reports.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back
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

    {{-- Chart --}}
    @if($data->count() > 0)
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Top 10 Resellers by Profit</h3>
            <div style="height: 300px;">
                <canvas id="profitChart"></canvas>
            </div>
        </div>
    @endif

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var canvas = document.getElementById('profitChart');
        if (!canvas) return;

        var chartLabels = @json($chartLabels);
        var chartData = @json($chartData);

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Profit',
                    data: chartData,
                    backgroundColor: chartData.map(function(v) { return v >= 0 ? 'rgba(16, 185, 129, 0.7)' : 'rgba(239, 68, 68, 0.7)'; }),
                    borderColor: chartData.map(function(v) { return v >= 0 ? '#10b981' : '#ef4444'; }),
                    borderWidth: 1,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true } }
            }
        });
    });
    </script>
    @endpush

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
</x-admin-layout>
