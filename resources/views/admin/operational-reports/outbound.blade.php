<x-admin-layout>
    <x-slot name="header">Outbound Calls</x-slot>

    @php
        $answeredCount = $answeredCalls ?? 0;
        $totalDuration = $totalMinutes * 60;
        $acdSeconds = ($answeredCount > 0) ? round($totalDuration / $answeredCount) : 0;
        $acdMin = intdiv($acdSeconds, 60); $acdSec = $acdSeconds % 60;
    @endphp

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">Outbound Calls</h2>
            <p class="page-subtitle">Calls sent to external destinations via trunks</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.operational-reports.outbound.export', request()->query()) }}" class="btn-action-secondary">
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

    {{-- Stats Cards (compact) --}}
    @php
        $asrColor = $asr >= 50 ? ['bg' => 'bg-emerald-100', 'icon' => 'text-emerald-600', 'val' => 'text-emerald-600'] : ['bg' => 'bg-amber-100', 'icon' => 'text-amber-600', 'val' => 'text-amber-600'];
    @endphp
    <div class="mb-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
        <div class="flex items-center gap-2 px-3 py-2 bg-white rounded-lg border border-gray-200">
            <div class="w-7 h-7 rounded-md bg-indigo-100 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            </div>
            <div class="flex items-baseline gap-1.5 min-w-0">
                <p class="text-lg font-semibold text-gray-900 leading-none tabular-nums">{{ number_format($totalCalls) }}</p>
                <p class="text-xs text-gray-500 truncate">Total</p>
            </div>
        </div>
        <div class="flex items-center gap-2 px-3 py-2 bg-white rounded-lg border border-gray-200">
            <div class="w-7 h-7 rounded-md bg-emerald-100 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div class="flex items-baseline gap-1.5 min-w-0">
                <p class="text-lg font-semibold text-emerald-600 leading-none tabular-nums">{{ number_format($answeredCalls) }}</p>
                <p class="text-xs text-gray-500 truncate">Answered</p>
            </div>
        </div>
        <div class="flex items-center gap-2 px-3 py-2 bg-white rounded-lg border border-gray-200">
            <div class="w-7 h-7 rounded-md bg-red-100 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <div class="flex items-baseline gap-1.5 min-w-0">
                <p class="text-lg font-semibold text-red-600 leading-none tabular-nums">{{ number_format($totalCalls - $answeredCalls) }}</p>
                <p class="text-xs text-gray-500 truncate">Failed</p>
            </div>
        </div>
        <div class="flex items-center gap-2 px-3 py-2 bg-white rounded-lg border border-gray-200">
            <div class="w-7 h-7 rounded-md {{ $asrColor['bg'] }} flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 {{ $asrColor['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <div class="flex items-baseline gap-1.5 min-w-0">
                <p class="text-lg font-semibold {{ $asrColor['val'] }} leading-none tabular-nums">{{ $asr }}%</p>
                <p class="text-xs text-gray-500 truncate">ASR</p>
            </div>
        </div>
        <div class="flex items-center gap-2 px-3 py-2 bg-white rounded-lg border border-gray-200">
            <div class="w-7 h-7 rounded-md bg-blue-100 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="flex items-baseline gap-1.5 min-w-0">
                <p class="text-lg font-semibold text-blue-600 leading-none tabular-nums">{{ $acdMin }}:{{ str_pad($acdSec, 2, '0', STR_PAD_LEFT) }}</p>
                <p class="text-xs text-gray-500 truncate">ACD</p>
            </div>
        </div>
        <div class="flex items-center gap-2 px-3 py-2 bg-white rounded-lg border border-gray-200">
            <div class="w-7 h-7 rounded-md bg-indigo-100 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="flex items-baseline gap-1.5 min-w-0">
                <p class="text-lg font-semibold text-gray-900 leading-none tabular-nums">{{ number_format($totalMinutes, 0) }}</p>
                <p class="text-xs text-gray-500 truncate">Minutes</p>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="filter-card mb-3">
        <form method="GET" class="space-y-3">
            {{-- Row 1: Destination + Dates + Disposition + Trunk --}}
            <div class="flex items-center gap-3">
                <div class="filter-search-box flex-1">
                    <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search destination..." class="filter-input">
                </div>
                <input type="date" name="date_from" value="{{ request('date_from', now()->format('Y-m-d')) }}" class="filter-input" style="width:auto;">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="filter-input" style="width:auto;">
                <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden text-xs flex-shrink-0">
                    <a href="{{ route('admin.operational-reports.outbound', array_merge(request()->except('disposition'), [])) }}" class="px-3 py-2 font-medium {{ !request('disposition') ? 'bg-gray-900 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">All</a>
                    <a href="{{ route('admin.operational-reports.outbound', array_merge(request()->except('disposition'), ['disposition' => 'ANSWERED'])) }}" class="px-3 py-2 font-medium border-l {{ request('disposition') === 'ANSWERED' ? 'bg-emerald-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">Answered</a>
                    <a href="{{ route('admin.operational-reports.outbound', array_merge(request()->except('disposition'), ['disposition' => 'NO ANSWER'])) }}" class="px-3 py-2 font-medium border-l {{ request('disposition') === 'NO ANSWER' ? 'bg-amber-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">No Answer</a>
                    <a href="{{ route('admin.operational-reports.outbound', array_merge(request()->except('disposition'), ['disposition' => 'FAILED'])) }}" class="px-3 py-2 font-medium border-l {{ request('disposition') === 'FAILED' ? 'bg-red-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">Failed</a>
                </div>
                <select name="trunk_id" class="filter-input" style="width:auto;">
                    <option value="">All Trunks</option>
                    @foreach($trunks as $trunk)
                        <option value="{{ $trunk->id }}" {{ request('trunk_id') == $trunk->id ? 'selected' : '' }}>{{ $trunk->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Row 2: Reseller + Client + Source IP + Caller ID + Buttons --}}
            <div class="flex items-center gap-3">
                {{-- Reseller --}}
                <div class="relative flex-1" x-data="resellerFilter()" @click.away="open = false">
                    <input type="hidden" name="reseller_id" :value="selectedId">
                    <input type="text" x-model="query" @focus="open = true" @click="open = true" @input="open = true; selectedId = ''" placeholder="All Resellers" class="filter-input" autocomplete="off">
                    <div x-show="open" x-cloak class="absolute z-20 mt-1 w-64 bg-white rounded-lg border border-gray-200 shadow-lg max-h-48 overflow-y-auto">
                        <button type="button" @click="selectedId = ''; query = ''; open = false" class="w-full px-3 py-2 text-left text-sm hover:bg-indigo-50 text-gray-500">All Resellers</button>
                        <template x-for="r in filtered" :key="r.id">
                            <button type="button" @click="selectedId = String(r.id); query = r.name; open = false" class="w-full px-3 py-2 text-left text-sm hover:bg-indigo-50 flex items-center justify-between">
                                <span class="font-medium text-gray-900" x-text="r.name"></span>
                                <span class="text-xs text-gray-400" x-text="r.email"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Client --}}
                <div class="relative flex-1" x-data="clientFilter()" @click.away="open = false">
                    <input type="hidden" name="user_id" :value="selectedId">
                    <input type="text" x-model="query" @focus="open = true" @click="open = true" @input="open = true; selectedId = ''" placeholder="All Clients" class="filter-input" autocomplete="off">
                    <div x-show="open" x-cloak class="absolute z-20 mt-1 w-64 bg-white rounded-lg border border-gray-200 shadow-lg max-h-48 overflow-y-auto">
                        <button type="button" @click="selectedId = ''; query = ''; open = false" class="w-full px-3 py-2 text-left text-sm hover:bg-indigo-50 text-gray-500">All Clients</button>
                        <template x-for="c in filtered" :key="c.id">
                            <button type="button" @click="selectedId = String(c.id); query = c.name; open = false" class="w-full px-3 py-2 text-left text-sm hover:bg-indigo-50 flex items-center justify-between">
                                <span class="font-medium text-gray-900" x-text="c.name"></span>
                                <span class="text-xs text-gray-400" x-text="c.email"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Source IP --}}
                <input type="text" name="source_ip" value="{{ request('source_ip') }}" placeholder="Source IP" class="filter-input flex-1">

                {{-- Caller ID --}}
                <input type="text" name="caller_id" value="{{ request('caller_id') }}" placeholder="Caller ID" class="filter-input flex-1">

                {{-- Buttons --}}
                <button type="submit" class="btn-search-admin flex-shrink-0">Search</button>
                @if(request()->hasAny(['search', 'disposition', 'trunk_id', 'date_to', 'reseller_id', 'user_id', 'source_ip', 'caller_id']))
                    <a href="{{ route('admin.operational-reports.outbound') }}" class="btn-clear flex-shrink-0">Clear</a>
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
                this.$watch('query', function(val) {
                    if (!val) { this.filtered = _resellers.slice(0, 5); return; }
                    var q = val.toLowerCase();
                    this.filtered = _resellers.filter(function(r) { return r.name.toLowerCase().indexOf(q) > -1 || r.email.toLowerCase().indexOf(q) > -1; }).slice(0, 5);
                }.bind(this));
            }
        }
    }

    function clientFilter() {
        return {
            open: false, query: '', selectedId: '{{ request('user_id') }}', filtered: _clients.slice(0, 5),
            init() {
                if (this.selectedId) { var f = _clients.find(function(c) { return String(c.id) === String(this.selectedId); }.bind(this)); if (f) this.query = f.name; }
                this.$watch('query', function(val) {
                    if (!val) { this.filtered = _clients.slice(0, 5); return; }
                    var q = val.toLowerCase();
                    this.filtered = _clients.filter(function(c) { return c.name.toLowerCase().indexOf(q) > -1 || c.email.toLowerCase().indexOf(q) > -1; }).slice(0, 5);
                }.bind(this));
            }
        }
    }
    </script>
    @endpush

    {{-- Calls Table --}}
    @if($calls->count() > 0)
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            {{-- Summary Bar --}}
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    Outbound Calls Total : {{ number_format($calls->total()) }} &middot; Showing {{ $calls->firstItem() }} to {{ $calls->lastItem() }}
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 text-xs text-gray-500">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Answered
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-xs text-gray-500">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span> No Answer
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-xs text-gray-500">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span> Failed
                    </span>
                </div>
            </div>

            <x-cdr-archive-banner />

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" width="40">SL</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">SIP Account</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Caller ID</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Destination</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Call Time</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Ring</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Bill Dur.</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Trunk</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($calls as $call)
                            <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-indigo-50/50 transition-all border-b border-gray-100 group">
                                {{-- SL --}}
                                <td class="px-3 py-2 text-gray-400 tabular-nums text-center">{{ $calls->firstItem() + $loop->index }}</td>

                                {{-- SIP Account + User --}}
                                <td class="px-3 py-2">
                                    @if($call->sipAccount)
                                        <a href="{{ route('admin.sip-accounts.show', $call->sipAccount) }}" class="text-indigo-600 hover:text-indigo-500 font-mono font-medium">
                                            {{ $call->sipAccount->username }}
                                        </a>
                                        @if($call->user)
                                            <div class="text-xs text-gray-400">{{ Str::limit($call->user->name, 20) }}</div>
                                        @endif
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>

                                {{-- Caller ID + Source IP --}}
                                <td class="px-3 py-2">
                                    <span class="font-mono text-gray-900">{{ $call->caller_id ?: $call->caller }}</span>
                                    @if($call->sipAccount?->last_registered_ip)
                                        <div class="text-xs text-gray-400 font-mono">{{ $call->sipAccount->last_registered_ip }}</div>
                                    @endif
                                </td>

                                {{-- Destination --}}
                                <td class="px-3 py-2">
                                    <span class="font-mono text-gray-900">{{ $call->callee }}</span>
                                </td>

                                {{-- Call Time --}}
                                <td class="px-3 py-2">
                                    <div class="text-gray-900 text-xs font-mono">{{ $call->call_start->format('Y-m-d H:i:s') }}</div>
                                    <div class="text-xs text-gray-400 font-mono">{{ $call->call_end?->format('Y-m-d H:i:s') ?? '-' }}</div>
                                </td>

                                {{-- Ring --}}
                                <td class="px-3 py-2 text-right tabular-nums text-gray-500">
                                    {{ max(0, ($call->duration ?? 0) - ($call->billsec ?? 0)) }}s
                                </td>

                                {{-- Bill Duration --}}
                                <td class="px-3 py-2 text-right tabular-nums text-gray-700 font-medium">
                                    {{ $call->billsec ?? 0 }}s
                                </td>

                                {{-- Trunk --}}
                                <td class="px-3 py-2">
                                    @if($call->outgoingTrunk)
                                        <a href="{{ route('admin.trunks.show', $call->outgoingTrunk) }}" class="text-indigo-600 hover:text-indigo-500 font-medium">
                                            {{ Str::limit($call->outgoingTrunk->name, 20) }}
                                        </a>
                                        <div class="text-xs text-gray-400 font-mono">{{ $call->outgoingTrunk->host }}</div>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td class="px-3 py-2">
                                    @switch($call->disposition)
                                        @case('ANSWERED')
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Answered</span>
                                            @break
                                        @case('NO ANSWER')
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>No Answer</span>
                                            @break
                                        @case('BUSY')
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-blue-700"><span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>Busy</span>
                                            @break
                                        @case('FAILED')
                                        @case('CONGESTION')
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Failed</span>
                                            @break
                                        @default
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-500"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>{{ $call->disposition ?? 'Unknown' }}</span>
                                    @endswitch
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($calls->hasPages())
                <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                    {{ $calls->withQueryString()->links() }}
                </div>
            @endif
        </div>
    @else
        {{-- Empty State --}}
        <div class="bg-white rounded-xl border border-gray-200 py-16">
            <div class="text-center">
                <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-indigo-100 flex items-center justify-center">
                    <svg class="w-10 h-10 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-1">No Outbound Calls Found</h3>
                <p class="text-gray-500 text-sm">Try adjusting your filters or date range</p>
            </div>
        </div>
    @endif
</x-admin-layout>
