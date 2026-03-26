<x-admin-layout>
    <x-slot name="header">Monthly Summary</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-400 to-indigo-500 flex items-center justify-center shadow-lg shadow-indigo-200">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Monthly Summary</h2>
                <p class="page-subtitle">Month-over-month call volume analysis</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.operational-reports.monthly.export', request()->query()) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
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

    {{-- Filter Bar --}}
    <div class="filter-card mb-3">
        <form method="GET" class="space-y-3">
            {{-- Row 1: Dates + Trunk + Direction + Disposition --}}
            <div class="flex items-center gap-3">
                <input type="date" name="date_from" value="{{ request('date_from', $dateFrom->format('Y-m-d')) }}" class="form-input text-sm flex-1">
                <input type="date" name="date_to" value="{{ request('date_to', $dateTo->format('Y-m-d')) }}" class="form-input text-sm flex-1">
                <select name="trunk_id" class="filter-select flex-1">
                    <option value="">All Trunks</option>
                    @foreach($trunks as $trunk)
                        <option value="{{ $trunk->id }}" {{ request('trunk_id') == $trunk->id ? 'selected' : '' }}>{{ $trunk->name }}</option>
                    @endforeach
                </select>
                <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden text-xs flex-shrink-0">
                    <a href="{{ route('admin.operational-reports.monthly', array_merge(request()->except('call_flow'), [])) }}" class="px-3 py-2 font-medium {{ !request('call_flow') ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">All</a>
                    <a href="{{ route('admin.operational-reports.monthly', array_merge(request()->except('call_flow'), ['call_flow' => 'trunk_to_sip'])) }}" class="px-3 py-2 font-medium border-l {{ request('call_flow') === 'trunk_to_sip' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">Inbound</a>
                    <a href="{{ route('admin.operational-reports.monthly', array_merge(request()->except('call_flow'), ['call_flow' => 'sip_to_trunk'])) }}" class="px-3 py-2 font-medium border-l {{ request('call_flow') === 'sip_to_trunk' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">Outbound</a>
                    <a href="{{ route('admin.operational-reports.monthly', array_merge(request()->except('call_flow'), ['call_flow' => 'sip_to_sip'])) }}" class="px-3 py-2 font-medium border-l {{ request('call_flow') === 'sip_to_sip' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">P2P</a>
                </div>
                <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden text-xs flex-shrink-0">
                    <a href="{{ route('admin.operational-reports.monthly', array_merge(request()->except('disposition'), [])) }}" class="px-3 py-2 font-medium {{ !request('disposition') ? 'bg-gray-800 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">All</a>
                    <a href="{{ route('admin.operational-reports.monthly', array_merge(request()->except('disposition'), ['disposition' => 'ANSWERED'])) }}" class="px-3 py-2 font-medium border-l {{ request('disposition') === 'ANSWERED' ? 'bg-emerald-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">Answered</a>
                    <a href="{{ route('admin.operational-reports.monthly', array_merge(request()->except('disposition'), ['disposition' => 'NO ANSWER'])) }}" class="px-3 py-2 font-medium border-l {{ request('disposition') === 'NO ANSWER' ? 'bg-amber-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">No Answer</a>
                    <a href="{{ route('admin.operational-reports.monthly', array_merge(request()->except('disposition'), ['disposition' => 'FAILED'])) }}" class="px-3 py-2 font-medium border-l {{ request('disposition') === 'FAILED' ? 'bg-red-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">Failed</a>
                </div>
            </div>

            {{-- Row 2: Type + Reseller + Client + Buttons --}}
            <div class="flex items-center gap-3">
                <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden text-xs flex-shrink-0">
                    <a href="{{ route('admin.operational-reports.monthly', array_merge(request()->except('call_type'), [])) }}" class="px-2.5 py-2 font-medium {{ !request('call_type') ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">All</a>
                    <a href="{{ route('admin.operational-reports.monthly', array_merge(request()->except('call_type'), ['call_type' => 'regular'])) }}" class="px-2.5 py-2 font-medium border-l {{ request('call_type') === 'regular' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">Regular</a>
                    <a href="{{ route('admin.operational-reports.monthly', array_merge(request()->except('call_type'), ['call_type' => 'broadcast'])) }}" class="px-2.5 py-2 font-medium border-l {{ request('call_type') === 'broadcast' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">Broadcast</a>
                </div>
                {{-- Reseller --}}
                <div class="relative flex-1" x-data="resellerFilter()" @click.away="open = false">
                    <input type="hidden" name="reseller_id" :value="selectedId">
                    <div class="relative">
                        <input type="text" x-model="query" @focus="open = true" @click="open = true" @input="open = true; selectedId = ''" placeholder="All Resellers" class="form-input text-sm pr-8" autocomplete="off">
                        <button type="button" x-show="query" x-cloak @click="selectedId = ''; query = ''" class="absolute right-2 top-1/2 -translate-y-1/2 p-0.5 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-400 hover:text-gray-600">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div x-show="open && filtered.length > 0" x-cloak class="absolute z-20 mt-1 w-full bg-white rounded-lg border border-gray-200 shadow-lg max-h-48 overflow-y-auto">
                        <button type="button" @click="selectedId = ''; query = ''; open = false" class="w-full px-3 py-2 text-left text-sm hover:bg-indigo-50 text-gray-500">All Resellers</button>
                        <template x-for="r in filtered" :key="r.id">
                            <button type="button" @click="selectedId = String(r.id); query = r.name; open = false" class="w-full px-3 py-2 text-left text-sm hover:bg-indigo-50 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                        <span class="text-xs font-medium text-indigo-600" x-text="r.name.substring(0, 1).toUpperCase()"></span>
                                    </div>
                                    <span class="font-medium text-gray-900" x-text="r.name"></span>
                                </div>
                                <span class="text-xs text-gray-400" x-text="r.email"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Client --}}
                <div class="relative flex-1" x-data="clientFilter()" @click.away="open = false">
                    <input type="hidden" name="user_id" :value="selectedId">
                    <div class="relative">
                        <input type="text" x-model="query" @focus="open = true" @click="open = true" @input="open = true; selectedId = ''" placeholder="All Clients" class="form-input text-sm pr-8" autocomplete="off">
                        <button type="button" x-show="query" x-cloak @click="selectedId = ''; query = ''" class="absolute right-2 top-1/2 -translate-y-1/2 p-0.5 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-400 hover:text-gray-600">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div x-show="open && filtered.length > 0" x-cloak class="absolute z-20 mt-1 w-full bg-white rounded-lg border border-gray-200 shadow-lg max-h-48 overflow-y-auto">
                        <button type="button" @click="selectedId = ''; query = ''; open = false" class="w-full px-3 py-2 text-left text-sm hover:bg-indigo-50 text-gray-500">All Clients</button>
                        <template x-for="c in filtered" :key="c.id">
                            <button type="button" @click="selectedId = String(c.id); query = c.name; open = false" class="w-full px-3 py-2 text-left text-sm hover:bg-indigo-50 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-sky-100 flex items-center justify-center flex-shrink-0">
                                        <span class="text-xs font-medium text-sky-600" x-text="c.name.substring(0, 1).toUpperCase()"></span>
                                    </div>
                                    <span class="font-medium text-gray-900" x-text="c.name"></span>
                                </div>
                                <span class="text-xs text-gray-400" x-text="c.email"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Buttons --}}
                <button type="submit" class="btn-search-admin">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Search
                </button>
                @if(request()->hasAny(['reseller_id', 'user_id', 'trunk_id', 'disposition', 'call_flow', 'call_type', 'date_from', 'date_to']))
                    <a href="{{ route('admin.operational-reports.monthly') }}" class="btn-clear">Clear</a>
                @endif
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
    var _resellers = @json($resellers->map(function ($r) { return ['id' => $r->id, 'name' => $r->name, 'email' => $r->email]; }));
    var _clients = @json($clients->map(function ($c) { return ['id' => $c->id, 'name' => $c->name, 'email' => $c->email]; }));

    function resellerFilter() {
        return {
            open: false, query: '', selectedId: '{{ request('reseller_id') }}', filtered: _resellers.slice(0, 5),
            init() {
                if (this.selectedId) { var f = _resellers.find(function(r) { return String(r.id) === String(this.selectedId); }.bind(this)); if (f) this.query = f.name; }
                this.$watch('query', function(val) { if (!val) { this.filtered = _resellers.slice(0, 5); return; } var q = val.toLowerCase(); this.filtered = _resellers.filter(function(r) { return r.name.toLowerCase().indexOf(q) > -1 || r.email.toLowerCase().indexOf(q) > -1; }).slice(0, 5); }.bind(this));
            }
        }
    }
    function clientFilter() {
        return {
            open: false, query: '', selectedId: '{{ request('user_id') }}', filtered: _clients.slice(0, 5),
            init() {
                if (this.selectedId) { var f = _clients.find(function(c) { return String(c.id) === String(this.selectedId); }.bind(this)); if (f) this.query = f.name; }
                this.$watch('query', function(val) { if (!val) { this.filtered = _clients.slice(0, 5); return; } var q = val.toLowerCase(); this.filtered = _clients.filter(function(c) { return c.name.toLowerCase().indexOf(q) > -1 || c.email.toLowerCase().indexOf(q) > -1; }).slice(0, 5); }.bind(this));
            }
        }
    }
    </script>
    @endpush

    {{-- Stats Cards --}}
    @php
        $totalDuration = $totals['total_duration'] ?? ($totals['minutes'] * 60);
        $acdSeconds = ($totals['answered_calls'] > 0) ? round($totalDuration / $totals['answered_calls']) : 0;
        $acdMin = intdiv($acdSeconds, 60);
        $acdSec = $acdSeconds % 60;
    @endphp
    <div class="mb-5" style="display:grid; grid-template-columns: repeat(6, 1fr); gap:1rem;">
        <div class="stat-card">
            <div class="stat-icon bg-indigo-100">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            </div>
            <div class="stat-content">
                <p class="stat-value">{{ number_format($totals['total_calls']) }}</p>
                <p class="stat-label">Total Calls</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-emerald-100">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="stat-content">
                <p class="stat-value text-emerald-600">{{ number_format($totals['answered_calls']) }}</p>
                <p class="stat-label">Answered</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-red-100">
                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="stat-content">
                <p class="stat-value text-red-500">{{ number_format($totals['failed_calls']) }}</p>
                <p class="stat-label">Failed</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon {{ $totals['asr'] >= 50 ? 'bg-emerald-100' : 'bg-amber-100' }}">
                <svg class="w-6 h-6 {{ $totals['asr'] >= 50 ? 'text-emerald-600' : 'text-amber-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <div class="stat-content">
                <p class="stat-value {{ $totals['asr'] >= 50 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $totals['asr'] }}%</p>
                <p class="stat-label">ASR</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-blue-100">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="stat-content">
                <p class="stat-value tabular-nums">{{ $acdMin }}:{{ sprintf('%02d', $acdSec) }}</p>
                <p class="stat-label">ACD</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-indigo-100">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="stat-content">
                <p class="stat-value">{{ number_format($totals['minutes'], 0) }}</p>
                <p class="stat-label">Minutes</p>
            </div>
        </div>
    </div>

    {{-- Data Table --}}
    @if($rows->count() > 0)
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                <span class="text-sm text-gray-600">
                    Showing <span class="font-semibold">{{ $rows->count() }}</span> months
                    ({{ $dateFrom->format('M Y') }} &mdash; {{ $dateTo->format('M Y') }})
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" width="40">SL</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Month</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Calls</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Answered</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Failed</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">ASR%</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">ACD</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Minutes</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Change</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $index => $row)
                            <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-indigo-50/50 transition-all border-b border-gray-100 group">
                                <td class="px-3 py-2 text-gray-400 tabular-nums text-center">{{ $index + 1 }}</td>
                                <td class="px-3 py-2 font-medium text-gray-900">{{ $row->month_label }}</td>
                                <td class="px-3 py-2 text-right font-medium tabular-nums">{{ number_format($row->total_calls) }}</td>
                                <td class="px-3 py-2 text-right text-emerald-600 tabular-nums">{{ number_format($row->answered_calls) }}</td>
                                <td class="px-3 py-2 text-right text-red-500 tabular-nums">{{ number_format($row->failed_calls) }}</td>
                                <td class="px-3 py-2 text-right">
                                    <span class="{{ $row->asr >= 50 ? 'text-emerald-600' : 'text-amber-600' }} font-medium">{{ $row->asr }}%</span>
                                </td>
                                <td class="px-3 py-2 text-right text-gray-600">{{ $row->acd > 0 ? sprintf('%dm %ds', intdiv($row->acd, 60), $row->acd % 60) : '-' }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ number_format($row->minutes, 0) }}</td>
                                <td class="px-3 py-2 text-right">
                                    @if($row->change === null)
                                        <span class="text-gray-400">&mdash;</span>
                                    @elseif($row->change >= 0)
                                        <span class="inline-flex items-center text-emerald-600 font-medium">
                                            <svg class="w-4 h-4 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                            +{{ $row->change }}%
                                        </span>
                                    @else
                                        <span class="inline-flex items-center text-red-500 font-medium">
                                            <svg class="w-4 h-4 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            {{ $row->change }}%
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50 font-bold border-t border-gray-200">
                            <td class="px-3 py-2"></td>
                            <td class="px-3 py-2">Totals</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ number_format($totals['total_calls']) }}</td>
                            <td class="px-3 py-2 text-right text-emerald-600 tabular-nums">{{ number_format($totals['answered_calls']) }}</td>
                            <td class="px-3 py-2 text-right text-red-500 tabular-nums">{{ number_format($totals['failed_calls']) }}</td>
                            <td class="px-3 py-2 text-right">
                                <span class="{{ $totals['asr'] >= 50 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $totals['asr'] }}%</span>
                            </td>
                            @php $totalAcd = $totals['answered_calls'] > 0 ? round($rows->sum('total_billsec') / $totals['answered_calls']) : 0; @endphp
                            <td class="px-3 py-2 text-right text-gray-600">{{ $totalAcd > 0 ? sprintf('%dm %ds', intdiv($totalAcd, 60), $totalAcd % 60) : '-' }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ number_format($totals['minutes'], 0) }}</td>
                            <td class="px-3 py-2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    @else
        <div class="bg-white rounded-xl border border-gray-200 py-16">
            <div class="text-center">
                <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-indigo-100 flex items-center justify-center">
                    <svg class="w-10 h-10 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-1">No Data Found</h3>
                <p class="text-gray-500 text-sm">No call records found for the selected period</p>
            </div>
        </div>
    @endif

</x-admin-layout>
