<x-admin-layout>
    <x-slot name="header">Operational Reports</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Operational Reports</h2>
            <p class="page-subtitle">Real-time call monitoring and statistics</p>
        </div>
        <div class="page-actions">
            <button type="button" onclick="location.reload()" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Refresh
            </button>
        </div>
    </div>

    {{-- Stats --}}
    @php
        $failedCalls = $todayTotal - $todayAnswered;
        $acdSeconds = ($todayAnswered > 0 && $todayMinutes > 0) ? round(($todayMinutes * 60) / $todayAnswered) : 0;
        $acdMin = intdiv($acdSeconds, 60); $acdSec = $acdSeconds % 60;
    @endphp
    <div class="mb-5" style="display:grid; grid-template-columns: repeat(5, 1fr); gap:1rem;">
        <div class="stat-card">
            <div class="stat-icon bg-emerald-100"><svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg></div>
            <div class="stat-content">
                <p class="stat-value text-emerald-600" id="idx-live-count">{{ $activeCalls }}</p>
                <p class="stat-label">Live Calls</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-indigo-100"><svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg></div>
            <div class="stat-content">
                <p class="stat-value">{{ number_format($todayTotal) }}</p>
                <p class="stat-label">Today's Calls</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-emerald-100"><svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            <div class="stat-content">
                <p class="stat-value text-emerald-600">{{ number_format($todayAnswered) }}</p>
                <p class="stat-label">Answered</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon {{ $todayAsr >= 50 ? 'bg-emerald-100' : 'bg-amber-100' }}"><svg class="w-6 h-6 {{ $todayAsr >= 50 ? 'text-emerald-600' : 'text-amber-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></div>
            <div class="stat-content">
                <p class="stat-value {{ $todayAsr >= 50 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $todayAsr }}%</p>
                <p class="stat-label">ASR</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-blue-100"><svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            <div class="stat-content">
                <p class="stat-value tabular-nums">{{ $acdMin }}:{{ sprintf('%02d', $acdSec) }}</p>
                <p class="stat-label">ACD</p>
            </div>
        </div>
    </div>

    {{-- Quick Navigation --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <a href="{{ route('admin.operational-reports.active') }}" class="group bg-white rounded-lg border border-gray-200 p-4 hover:border-indigo-300 hover:shadow-md transition-all flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0 group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                <svg class="w-5 h-5 text-emerald-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 group-hover:text-indigo-600">Active Calls</p>
                <p class="text-xs text-gray-500"><span id="idx-nav-live">{{ $activeCalls }}</span> live</p>
            </div>
        </a>
        <a href="{{ route('admin.operational-reports.inbound') }}" class="group bg-white rounded-lg border border-gray-200 p-4 hover:border-indigo-300 hover:shadow-md transition-all flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0 group-hover:bg-blue-500 transition-colors">
                <svg class="w-5 h-5 text-blue-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.517l2.257-1.128a1 1 0 00.502-1.21L9.228 3.683A1 1 0 008.279 3H5z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 group-hover:text-indigo-600">Inbound</p>
                <p class="text-xs text-gray-500">{{ number_format($todayInbound) }} today</p>
            </div>
        </a>
        <a href="{{ route('admin.operational-reports.outbound') }}" class="group bg-white rounded-lg border border-gray-200 p-4 hover:border-indigo-300 hover:shadow-md transition-all flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center flex-shrink-0 group-hover:bg-purple-500 transition-colors">
                <svg class="w-5 h-5 text-purple-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 group-hover:text-indigo-600">Outbound</p>
                <p class="text-xs text-gray-500">{{ number_format($todayOutbound) }} today</p>
            </div>
        </a>
        <a href="{{ route('admin.operational-reports.p2p') }}" class="group bg-white rounded-lg border border-gray-200 p-4 hover:border-indigo-300 hover:shadow-md transition-all flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0 group-hover:bg-amber-500 transition-colors">
                <svg class="w-5 h-5 text-amber-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 group-hover:text-indigo-600">P2P Calls</p>
                <p class="text-xs text-gray-500">Internal calls</p>
            </div>
        </a>
    </div>

    {{-- Reports Navigation --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
        <a href="{{ route('admin.operational-reports.summary') }}" class="group bg-white rounded-lg border border-gray-200 p-4 hover:border-indigo-300 hover:shadow-md transition-all flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0 group-hover:bg-indigo-500 transition-colors">
                <svg class="w-5 h-5 text-indigo-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 group-hover:text-indigo-600">Call Summary</p>
                <p class="text-xs text-gray-500">Combined stats</p>
            </div>
        </a>
        <a href="{{ route('admin.operational-reports.hourly') }}" class="group bg-white rounded-lg border border-gray-200 p-4 hover:border-indigo-300 hover:shadow-md transition-all flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0 group-hover:bg-blue-500 transition-colors">
                <svg class="w-5 h-5 text-blue-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 group-hover:text-indigo-600">Hourly</p>
                <p class="text-xs text-gray-500">Hour breakdown</p>
            </div>
        </a>
        <a href="{{ route('admin.operational-reports.daily') }}" class="group bg-white rounded-lg border border-gray-200 p-4 hover:border-indigo-300 hover:shadow-md transition-all flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0 group-hover:bg-amber-500 transition-colors">
                <svg class="w-5 h-5 text-amber-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 group-hover:text-indigo-600">Daily</p>
                <p class="text-xs text-gray-500">Day-by-day</p>
            </div>
        </a>
        <a href="{{ route('admin.operational-reports.monthly') }}" class="group bg-white rounded-lg border border-gray-200 p-4 hover:border-indigo-300 hover:shadow-md transition-all flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0 group-hover:bg-emerald-500 transition-colors">
                <svg class="w-5 h-5 text-emerald-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 group-hover:text-indigo-600">Monthly</p>
                <p class="text-xs text-gray-500">Month trends</p>
            </div>
        </a>
    </div>

    {{-- Main Content --}}
    <div class="grid grid-cols-12 gap-6">
        {{-- Active Calls Table --}}
        <div class="col-span-12 lg:col-span-8">
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <h3 class="font-semibold text-gray-900 text-sm">Active Calls</h3>
                        @if($activeCalls > 0)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                {{ $activeCalls }} Live
                            </span>
                        @endif
                    </div>
                    <a href="{{ route('admin.operational-reports.active') }}" class="text-xs text-indigo-600 hover:text-indigo-500 font-medium">View All</a>
                </div>

                @if($recentActive->count() > 0)
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Caller</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Callee</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Duration</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Trunk</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentActive as $call)
                                <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-indigo-50/50 transition-all border-b border-gray-100">
                                    <td class="px-3 py-2">
                                        @if($call->call_flow === 'trunk_to_sip')
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-blue-700"><span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>IN</span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-purple-700"><span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>OUT</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 font-mono text-gray-900">{{ Str::limit($call->caller, 15) }}</td>
                                    <td class="px-3 py-2 font-mono text-gray-900">{{ Str::limit($call->callee, 15) }}</td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex items-center gap-1 text-emerald-600 font-medium text-xs">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            {{ $call->call_start->diffForHumans(null, true) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-gray-600 text-xs">{{ Str::limit($call->call_flow === 'trunk_to_sip' ? ($call->incomingTrunk->name ?? '-') : ($call->outgoingTrunk->name ?? '-'), 12) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="py-10 text-center">
                        <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <p class="text-sm text-gray-500">No active calls</p>
                        <p class="text-xs text-gray-400">All lines are idle</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-span-12 lg:col-span-4 space-y-4">
            {{-- Top SIP Accounts --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-2.5 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900 text-sm">Top SIP Accounts</h3>
                </div>
                @if($topSipAccounts->count() > 0)
                    <div class="divide-y divide-gray-50">
                        @foreach($topSipAccounts as $index => $item)
                            @if($item->sipAccount)
                                <div class="px-4 py-2.5 flex items-center gap-2 hover:bg-gray-50">
                                    <span class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-600 text-xs font-bold flex items-center justify-center">{{ $index + 1 }}</span>
                                    <a href="{{ route('admin.sip-accounts.show', $item->sipAccount) }}" class="flex-1 text-sm text-gray-900 hover:text-indigo-600 truncate">{{ $item->sipAccount->username }}</a>
                                    <span class="text-xs font-semibold text-gray-500">{{ $item->call_count }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="p-4 text-center"><p class="text-xs text-gray-400">No activity today</p></div>
                @endif
            </div>

            {{-- Top Trunks --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-2.5 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900 text-sm">Top Trunks</h3>
                </div>
                @if($topTrunks->count() > 0)
                    <div class="divide-y divide-gray-50">
                        @foreach($topTrunks as $index => $item)
                            @if($item->trunk)
                                <div class="px-4 py-2.5 flex items-center gap-2 hover:bg-gray-50">
                                    <span class="w-5 h-5 rounded-full bg-amber-100 text-amber-600 text-xs font-bold flex items-center justify-center">{{ $index + 1 }}</span>
                                    <div class="flex-1 min-w-0">
                                        <a href="{{ route('admin.trunks.show', $item->trunk) }}" class="text-sm text-gray-900 hover:text-indigo-600 truncate block">{{ $item->trunk->name }}</a>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-500">{{ $item->call_count }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="p-4 text-center"><p class="text-xs text-gray-400">No activity today</p></div>
                @endif
            </div>

            {{-- Quick Stats --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-2.5 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900 text-sm">Today's Breakdown</h3>
                </div>
                <div class="p-4 space-y-2 text-sm">
                    <div class="flex items-center justify-between"><span class="text-gray-500">Inbound</span><span class="font-semibold text-blue-600">{{ number_format($todayInbound) }}</span></div>
                    <div class="flex items-center justify-between"><span class="text-gray-500">Outbound</span><span class="font-semibold text-purple-600">{{ number_format($todayOutbound) }}</span></div>
                    <div class="flex items-center justify-between"><span class="text-gray-500">Failed</span><span class="font-semibold text-red-500">{{ number_format($failedCalls) }}</span></div>
                    <div class="flex items-center justify-between"><span class="text-gray-500">Minutes</span><span class="font-semibold text-indigo-600">{{ number_format($todayMinutes, 0) }}</span></div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    (function() {
        const WS_URL = (window.location.protocol === 'https:' ? 'wss://' : 'ws://') + window.location.host + '/ws/live-calls';
        let ws = null;
        function connect() {
            ws = new WebSocket(WS_URL);
            ws.onmessage = function(event) {
                const data = JSON.parse(event.data);
                if (data.stats) {
                    const el = document.getElementById('idx-live-count');
                    const nav = document.getElementById('idx-nav-live');
                    if (el) el.textContent = data.stats.total;
                    if (nav) nav.textContent = data.stats.total;
                }
            };
            ws.onclose = function() { setTimeout(connect, 5000); };
        }
        setInterval(function() { if (ws && ws.readyState === WebSocket.OPEN) ws.send('ping'); }, 25000);
        connect();
    })();
    </script>
    @endpush
</x-admin-layout>
