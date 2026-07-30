<x-admin-layout>
    <x-slot name="header">Call Quality</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Call Quality</h2>
            <p class="page-subtitle">Per-leg RTP analysis — carrier vs customer</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="filter-card mb-3">
        <form method="GET" class="filter-row flex-wrap">
            <input type="date" name="date_from" value="{{ request('date_from', $dateFrom->format('Y-m-d')) }}" class="filter-input" style="width:auto;">
            <input type="date" name="date_to" value="{{ request('date_to', $dateTo->format('Y-m-d')) }}" class="filter-input" style="width:auto;">
            <select name="group_by" class="filter-input" style="width:auto;">
                <option value="client" {{ $groupBy === 'client' ? 'selected' : '' }}>By Client</option>
                <option value="trunk" {{ $groupBy === 'trunk' ? 'selected' : '' }}>By Trunk</option>
                <option value="prefix" {{ $groupBy === 'prefix' ? 'selected' : '' }}>By Destination</option>
            </select>
            <button type="submit" class="btn-search-admin">Apply</button>
        </form>
    </div>

    {{-- Coverage: an average over a thin sample must never read as complete --}}
    <div class="mb-3 px-4 py-2 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800">
        QoS captured on <strong>{{ number_format($stats->captured) }}</strong> of
        <strong>{{ number_format($stats->answered) }}</strong> answered calls
        (<strong>{{ $coverage }}%</strong>). Averages below cover only captured calls.
        Customer-side transmit counts are not shown — that counter is known to be unreliable.
    </div>

    {{-- Summary cards --}}
    @php
        $band = function ($mes) {
            if ($mes === null) return ['—', 'text-gray-400'];
            if ($mes >= 80) return ['Excellent', 'text-emerald-600'];
            if ($mes >= 60) return ['Good', 'text-blue-600'];
            if ($mes >= 40) return ['Fair', 'text-amber-600'];
            return ['Poor', 'text-red-600'];
        };
        [$trunkBand, $trunkColor] = $band($stats->trunk_mes);
        [$custBand, $custColor] = $band($stats->cust_mes);
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="bg-white rounded-xl border border-gray-200 p-3">
            <p class="text-xs text-gray-500">Carrier MES</p>
            <p class="text-lg font-semibold {{ $trunkColor }}">{{ $stats->trunk_mes !== null ? number_format($stats->trunk_mes, 1) : '—' }}
                <span class="text-xs font-normal">{{ $trunkBand }}</span></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-3">
            <p class="text-xs text-gray-500">Customer MES</p>
            <p class="text-lg font-semibold {{ $custColor }}">{{ $stats->cust_mes !== null ? number_format($stats->cust_mes, 1) : '—' }}
                <span class="text-xs font-normal">{{ $custBand }}</span></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-3">
            <p class="text-xs text-gray-500">Carrier jitter</p>
            <p class="text-lg font-semibold text-gray-900">{{ $stats->trunk_jitter_ms !== null ? number_format($stats->trunk_jitter_ms, 1) : '—' }} ms</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-3">
            <p class="text-xs text-gray-500">Customer jitter</p>
            <p class="text-lg font-semibold text-gray-900">{{ $stats->cust_jitter_ms !== null ? number_format($stats->cust_jitter_ms, 1) : '—' }} ms</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-3">
            <p class="text-xs text-gray-500">Carrier loss</p>
            <p class="text-lg font-semibold text-gray-900">{{ $stats->trunk_loss_pct !== null ? number_format($stats->trunk_loss_pct, 3) : '—' }}%</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-3">
            <p class="text-xs text-gray-500">Customer loss</p>
            <p class="text-lg font-semibold text-gray-900">{{ $stats->cust_loss_pct !== null ? number_format($stats->cust_loss_pct, 3) : '—' }}%</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-3">
            <p class="text-xs text-gray-500">Avg RTT</p>
            <p class="text-lg font-semibold text-gray-900">{{ $stats->trunk_rtt_ms !== null ? number_format($stats->trunk_rtt_ms, 1) : '—' }} ms</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-3">
            <p class="text-xs text-gray-500">No audio / one-way</p>
            <p class="text-lg font-semibold {{ ($stats->no_audio + $stats->one_way) > 0 ? 'text-red-600' : 'text-gray-900' }}">
                {{ number_format($stats->no_audio) }} / {{ number_format($stats->one_way) }}</p>
        </div>
    </div>

    {{-- Trend --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-4">
        <p class="text-sm font-semibold text-gray-700 mb-2">Jitter over time (ms)</p>
        <canvas id="jitterChart" height="80"></canvas>
    </div>

    {{-- Breakdown --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ ucfirst($groupBy) }}</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Calls</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Answered</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">MES</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Jitter</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Loss</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">RTT</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">No audio</th>
                </tr>
            </thead>
            <tbody>
                @forelse($breakdown as $row)
                    <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} border-b border-gray-100">
                        <td class="px-3 py-2 text-gray-900">{{ $row->label }}</td>
                        <td class="px-3 py-2 text-right tabular-nums text-gray-600">{{ number_format($row->calls) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums text-gray-600">{{ number_format($row->answered) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums font-medium {{ $row->trunk_mes !== null && $row->trunk_mes < 60 ? 'text-red-600' : 'text-gray-900' }}">
                            {{ $row->trunk_mes !== null ? number_format($row->trunk_mes, 1) : '—' }}</td>
                        <td class="px-3 py-2 text-right tabular-nums text-gray-600">{{ $row->trunk_jitter_ms !== null ? number_format($row->trunk_jitter_ms, 1) : '—' }}</td>
                        <td class="px-3 py-2 text-right tabular-nums text-gray-600">{{ $row->trunk_loss_pct !== null ? number_format($row->trunk_loss_pct, 2) . '%' : '—' }}</td>
                        <td class="px-3 py-2 text-right tabular-nums text-gray-600">{{ $row->trunk_rtt_ms !== null ? number_format($row->trunk_rtt_ms, 0) : '—' }}</td>
                        <td class="px-3 py-2 text-right tabular-nums {{ $row->no_audio > 0 ? 'text-red-600 font-medium' : 'text-gray-400' }}">{{ number_format($row->no_audio) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-3 py-8 text-center text-gray-400 text-sm">No QoS data captured in this range.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @push('scripts')
    {{-- SRI hash verified against the real CDN payload (Chart.js v4.4.1, 205,399 bytes,
         hash stable across two independent fetches). Without it a CDN compromise runs
         arbitrary JS in an authenticated admin session. The two existing Chart.js tags
         in this codebase lack SRI -- worth retrofitting separately, out of scope here. --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"
            integrity="sha384-9nhczxUqK87bcKHh20fSQcTGD4qq5GhayNYSYWqwBkINBhOfQLg/P5HG5lF1urn4"
            crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var ctx = document.getElementById('jitterChart');
            if (!ctx) return;
            var rows = @json($hourly);
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: rows.map(function (r) { return r.bucket.slice(11, 16); }),
                    datasets: [
                        { label: 'Carrier', data: rows.map(function (r) { return r.trunk_jitter_ms; }),
                          borderColor: '#ef4444', backgroundColor: 'transparent', tension: 0.3 },
                        { label: 'Customer', data: rows.map(function (r) { return r.cust_jitter_ms; }),
                          borderColor: '#6366f1', backgroundColor: 'transparent', tension: 0.3 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, title: { display: true, text: 'ms' } } }
                }
            });
        });
    </script>
    @endpush
</x-admin-layout>
