<x-admin-layout>
    <x-slot name="header">Hourly Summary</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-400 to-indigo-500 flex items-center justify-center shadow-lg shadow-indigo-200">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Hourly Summary</h2>
                <p class="page-subtitle">Hour-by-hour call breakdown</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.operational-reports.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back
            </a>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6" x-data="clientSearch()">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            {{-- Date Range --}}
            <input type="date" name="date_from" value="{{ request('date_from', $dateFrom->format('Y-m-d')) }}" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            <span class="text-gray-400">to</span>
            <input type="date" name="date_to" value="{{ request('date_to', $dateTo->format('Y-m-d')) }}" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">

            {{-- Reseller Filter --}}
            <select name="reseller_id" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" @change="onResellerChange($event)">
                <option value="">All Resellers</option>
                @foreach($resellers as $reseller)
                    <option value="{{ $reseller->id }}" {{ request('reseller_id') == $reseller->id ? 'selected' : '' }}>{{ $reseller->name }}</option>
                @endforeach
            </select>

            {{-- Client Search --}}
            <div class="relative" @click.away="showDropdown = false">
                <input type="text" x-model="search" @input.debounce.300ms="fetchClients" @focus="showDropdown = results.length > 0" placeholder="Search client..." class="w-44 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" autocomplete="off">
                <input type="hidden" name="user_id" :value="selectedId">
                <div x-show="showDropdown && results.length > 0" class="absolute z-50 mt-1 w-64 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                    <template x-for="client in results" :key="client.id">
                        <button type="button" @click="selectClient(client)" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50" x-text="client.name + ' (' + client.email + ')'"></button>
                    </template>
                </div>
            </div>

            {{-- Trunk Filter --}}
            <select name="trunk_id" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">All Trunks</option>
                @foreach($trunks as $trunk)
                    <option value="{{ $trunk->id }}" {{ request('trunk_id') == $trunk->id ? 'selected' : '' }}>{{ $trunk->name }}</option>
                @endforeach
            </select>

            {{-- Disposition Toggle --}}
            <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                <a href="{{ route('admin.operational-reports.hourly', array_merge(request()->except('disposition'), [])) }}"
                   class="px-3 py-2 text-sm font-medium {{ !request('disposition') ? 'bg-gray-900 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">All</a>
                <a href="{{ route('admin.operational-reports.hourly', array_merge(request()->except('disposition'), ['disposition' => 'ANSWERED'])) }}"
                   class="px-3 py-2 text-sm font-medium border-l {{ request('disposition') === 'ANSWERED' ? 'bg-emerald-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">Answered</a>
                <a href="{{ route('admin.operational-reports.hourly', array_merge(request()->except('disposition'), ['disposition' => 'NO ANSWER'])) }}"
                   class="px-3 py-2 text-sm font-medium border-l {{ request('disposition') === 'NO ANSWER' ? 'bg-amber-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">No Answer</a>
                <a href="{{ route('admin.operational-reports.hourly', array_merge(request()->except('disposition'), ['disposition' => 'FAILED'])) }}"
                   class="px-3 py-2 text-sm font-medium border-l {{ request('disposition') === 'FAILED' ? 'bg-red-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">Failed</a>
            </div>

            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">Apply</button>

            @if(request()->hasAny(['reseller_id', 'user_id', 'trunk_id', 'disposition', 'date_from', 'date_to']))
                <a href="{{ route('admin.operational-reports.hourly') }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700">Clear</a>
            @endif
        </form>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-indigo-100 text-xs font-medium uppercase tracking-wide">Total Calls</p>
                    <p class="text-3xl font-bold mt-1">{{ number_format($totals['total_calls']) }}</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium uppercase tracking-wide">Answered</p>
                    <p class="text-3xl font-bold text-emerald-600 mt-1">{{ number_format($totals['answered_calls']) }}</p>
                </div>
                <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium uppercase tracking-wide">Failed</p>
                    <p class="text-3xl font-bold text-red-500 mt-1">{{ number_format($totals['failed_calls']) }}</p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium uppercase tracking-wide">ASR</p>
                    <p class="text-3xl font-bold {{ $totals['asr'] >= 50 ? 'text-emerald-600' : 'text-amber-600' }} mt-1">{{ $totals['asr'] }}%</p>
                </div>
                <div class="w-12 h-12 {{ $totals['asr'] >= 50 ? 'bg-emerald-100' : 'bg-amber-100' }} rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 {{ $totals['asr'] >= 50 ? 'text-emerald-600' : 'text-amber-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium uppercase tracking-wide">Minutes</p>
                    <p class="text-3xl font-bold text-indigo-600 mt-1">{{ number_format($totals['minutes'], 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
            <span class="text-sm text-gray-600">
                24-hour breakdown for
                @if($dateFrom->isSameDay($dateTo))
                    <span class="font-semibold">{{ $dateFrom->format('M d, Y') }}</span>
                @else
                    <span class="font-semibold">{{ $dateFrom->format('M d, Y') }}</span> &mdash; <span class="font-semibold">{{ $dateTo->format('M d, Y') }}</span>
                @endif
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="w-12">#</th>
                        <th>Hour</th>
                        <th class="text-right">Total Calls</th>
                        <th class="text-right">Answered</th>
                        <th class="text-right">Failed</th>
                        <th class="text-right">ASR%</th>
                        <th class="text-right">Minutes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $index => $row)
                        <tr class="{{ $row->total_calls === 0 ? 'text-gray-400' : '' }}">
                            <td class="text-gray-400">{{ $index + 1 }}</td>
                            <td class="font-medium {{ $row->total_calls > 0 ? 'text-gray-900' : '' }}">{{ $row->hour_label }}</td>
                            <td class="text-right {{ $row->total_calls > 0 ? 'font-medium' : '' }}">{{ number_format($row->total_calls) }}</td>
                            <td class="text-right {{ $row->answered_calls > 0 ? 'text-emerald-600' : '' }}">{{ number_format($row->answered_calls) }}</td>
                            <td class="text-right {{ $row->failed_calls > 0 ? 'text-red-500' : '' }}">{{ number_format($row->failed_calls) }}</td>
                            <td class="text-right">
                                @if($row->total_calls > 0)
                                    <span class="{{ $row->asr >= 50 ? 'text-emerald-600' : 'text-amber-600' }} font-medium">{{ $row->asr }}%</span>
                                @else
                                    &mdash;
                                @endif
                            </td>
                            <td class="text-right">{{ number_format($row->minutes, 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 font-bold">
                        <td></td>
                        <td>Totals</td>
                        <td class="text-right">{{ number_format($totals['total_calls']) }}</td>
                        <td class="text-right text-emerald-600">{{ number_format($totals['answered_calls']) }}</td>
                        <td class="text-right text-red-500">{{ number_format($totals['failed_calls']) }}</td>
                        <td class="text-right">
                            <span class="{{ $totals['asr'] >= 50 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $totals['asr'] }}%</span>
                        </td>
                        <td class="text-right">{{ number_format($totals['minutes'], 0) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Chart --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Hourly Call Distribution</h3>
        <canvas id="hourlyChart" height="80"></canvas>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        function clientSearch() {
            return {
                search: '{{ request('user_id') ? \App\Models\User::find(request('user_id'))?->name : '' }}',
                selectedId: '{{ request('user_id') }}',
                results: [],
                showDropdown: false,
                async fetchClients() {
                    if (this.search.length < 2) { this.results = []; this.showDropdown = false; return; }
                    const resellerId = document.querySelector('[name=reseller_id]')?.value || '';
                    const res = await fetch(`{{ route('admin.sip-accounts.search-clients') }}?q=${encodeURIComponent(this.search)}&reseller_id=${resellerId}`);
                    const data = await res.json();
                    this.results = data;
                    this.showDropdown = true;
                },
                selectClient(client) {
                    this.search = client.name;
                    this.selectedId = client.id;
                    this.showDropdown = false;
                },
                onResellerChange(e) {
                    this.search = '';
                    this.selectedId = '';
                    this.results = [];
                }
            };
        }

        new Chart(document.getElementById('hourlyChart'), {
            type: 'bar',
            data: {
                labels: @json($chartLabels),
                datasets: [
                    {
                        label: 'Answered',
                        data: @json($chartAnswered),
                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                        borderColor: '#10b981',
                        borderWidth: 1,
                    },
                    {
                        label: 'Failed',
                        data: @json($chartFailed),
                        backgroundColor: 'rgba(239, 68, 68, 0.7)',
                        borderColor: '#ef4444',
                        borderWidth: 1,
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'top' } },
                scales: {
                    x: { stacked: true },
                    y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    </script>
    @endpush
</x-admin-layout>
