<x-admin-layout>
    <x-slot name="header">Active Calls</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-400 to-indigo-500 flex items-center justify-center shadow-lg shadow-indigo-200">
                <span class="relative flex h-4 w-4">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-4 w-4 bg-white"></span>
                </span>
            </div>
            <div>
                <h2 class="page-title">Active Calls</h2>
                <p class="page-subtitle" id="page-subtitle">Real-time monitoring of {{ number_format($totalActive) }} ongoing calls</p>
            </div>
        </div>
        <div class="page-actions">
            <button type="button" onclick="location.reload()" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Refresh
            </button>
            <a href="{{ route('admin.operational-reports.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
        {{-- Total Active --}}
        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-indigo-100 text-xs font-medium uppercase tracking-wide">Total Active</p>
                    <p class="text-3xl font-bold mt-1" id="stat-total">{{ number_format($totalActive) }}</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <span class="relative flex h-4 w-4">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-4 w-4 bg-white"></span>
                    </span>
                </div>
            </div>
        </div>

        {{-- Answered --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium uppercase tracking-wide">Answered</p>
                    <p class="text-3xl font-bold text-emerald-600 mt-1" id="stat-answered">{{ number_format($answeredCount) }}</p>
                </div>
                <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Ringing --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium uppercase tracking-wide">Ringing</p>
                    <p class="text-3xl font-bold text-amber-600 mt-1" id="stat-ringing">{{ number_format($ringingCount) }}</p>
                </div>
                <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Inbound --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium uppercase tracking-wide">Inbound</p>
                    <p class="text-3xl font-bold text-blue-600 mt-1" id="stat-inbound">{{ number_format($inboundActive) }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.517l2.257-1.128a1 1 0 00.502-1.21L9.228 3.683A1 1 0 008.279 3H5z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Outbound --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium uppercase tracking-wide">Outbound</p>
                    <p class="text-3xl font-bold text-purple-600 mt-1" id="stat-outbound">{{ number_format($outboundActive) }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            {{-- Search --}}
            <div class="flex-1 min-w-[200px] max-w-md">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search caller or callee..." class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            {{-- Direction Filter --}}
            <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                <a href="{{ route('admin.operational-reports.active', array_merge(request()->except('call_flow'), [])) }}"
                   class="px-3 py-2 text-sm font-medium {{ !request('call_flow') ? 'bg-gray-900 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">
                    All
                </a>
                <a href="{{ route('admin.operational-reports.active', array_merge(request()->except('call_flow'), ['call_flow' => 'trunk_to_sip'])) }}"
                   class="px-3 py-2 text-sm font-medium border-l {{ request('call_flow') === 'trunk_to_sip' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">
                    Inbound
                </a>
                <a href="{{ route('admin.operational-reports.active', array_merge(request()->except('call_flow'), ['call_flow' => 'sip_to_trunk'])) }}"
                   class="px-3 py-2 text-sm font-medium border-l {{ request('call_flow') === 'sip_to_trunk' ? 'bg-purple-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">
                    Outbound
                </a>
            </div>

            {{-- Status Filter --}}
            <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                <a href="{{ route('admin.operational-reports.active', array_merge(request()->except('call_state'), [])) }}"
                   class="px-3 py-2 text-sm font-medium {{ !request('call_state') ? 'bg-gray-900 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">
                    All Status
                </a>
                <a href="{{ route('admin.operational-reports.active', array_merge(request()->except('call_state'), ['call_state' => 'answered'])) }}"
                   class="px-3 py-2 text-sm font-medium border-l {{ request('call_state') === 'answered' ? 'bg-emerald-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">
                    Answered
                </a>
                <a href="{{ route('admin.operational-reports.active', array_merge(request()->except('call_state'), ['call_state' => 'ringing'])) }}"
                   class="px-3 py-2 text-sm font-medium border-l {{ request('call_state') === 'ringing' ? 'bg-amber-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">
                    Ringing
                </a>
            </div>

            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                Search
            </button>

            @if(request()->hasAny(['search', 'call_flow', 'call_state']))
                <a href="{{ route('admin.operational-reports.active') }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700">
                    Clear
                </a>
            @endif
        </form>
    </div>

    {{-- Calls Table --}}
    <div id="calls-table" class="bg-white rounded-xl border border-gray-200 overflow-hidden {{ $calls->count() === 0 ? 'hidden' : '' }}">
            {{-- Table Header Info --}}
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-600" id="calls-count">
                        Showing {{ $calls->count() }} live calls
                    </span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 text-xs text-gray-500">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Answered
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-xs text-gray-500">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span> Ringing
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-xs text-gray-500">
                        <span class="w-2 h-2 rounded-full bg-gray-400"></span> Processing
                    </span>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Direction</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Caller</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Callee</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Duration</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">SIP Account</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Trunk</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100" id="live-calls-tbody">
                        @foreach ($calls as $call)
                            <tr class="hover:bg-gray-50 transition-colors">
                                {{-- Status --}}
                                <td class="px-4 py-3">
                                    @if($call->call_state === 'answered')
                                        <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Answered
                                        </span>
                                    @elseif($call->call_state === 'ringing')
                                        <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            Ringing
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400 animate-pulse"></span>
                                            Processing
                                        </span>
                                    @endif
                                </td>

                                {{-- Direction --}}
                                <td class="px-4 py-3">
                                    @if($call->call_flow === 'trunk_to_sip')
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-blue-100 text-blue-700">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                            </svg>
                                            IN
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-purple-100 text-purple-700">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                            </svg>
                                            OUT
                                        </span>
                                    @endif
                                </td>

                                {{-- Caller --}}
                                <td class="px-4 py-3">
                                    <span class="font-mono text-gray-900">{{ $call->caller }}</span>
                                </td>

                                {{-- Callee --}}
                                <td class="px-4 py-3">
                                    <span class="font-mono text-gray-900">{{ $call->callee }}</span>
                                </td>

                                {{-- Duration --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5">
                                        @if($call->call_state === 'answered')
                                            <span class="relative flex h-2 w-2">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                            </span>
                                        @endif
                                        <span class="font-medium {{ $call->call_state === 'answered' ? 'text-green-600' : 'text-gray-600' }}">
                                            {{ $call->call_start->diffForHumans(null, true) }}
                                        </span>
                                    </div>
                                    <span class="text-xs text-gray-400">{{ $call->call_start->format('H:i:s') }}</span>
                                </td>

                                {{-- SIP Account --}}
                                <td class="px-4 py-3">
                                    @if($call->sipAccount)
                                        <a href="{{ route('admin.sip-accounts.show', $call->sipAccount) }}" class="text-indigo-600 hover:text-indigo-500 font-medium font-mono text-xs">
                                            {{ $call->sipAccount->username }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>

                                {{-- Trunk --}}
                                <td class="px-4 py-3">
                                    @if($call->call_flow === 'trunk_to_sip' && $call->incomingTrunk)
                                        <a href="{{ route('admin.trunks.show', $call->incomingTrunk) }}" class="text-indigo-600 hover:text-indigo-500 font-medium">
                                            {{ Str::limit($call->incomingTrunk->name, 15) }}
                                        </a>
                                    @elseif($call->call_flow === 'sip_to_trunk' && $call->outgoingTrunk)
                                        <a href="{{ route('admin.trunks.show', $call->outgoingTrunk) }}" class="text-indigo-600 hover:text-indigo-500 font-medium">
                                            {{ Str::limit($call->outgoingTrunk->name, 15) }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>

    {{-- Empty State --}}
    <div id="empty-state" class="bg-white rounded-xl border border-gray-200 py-16 {{ $calls->count() > 0 ? 'hidden' : '' }}">
        <div class="text-center">
            <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-1">No Active Calls</h3>
            <p class="text-gray-500 text-sm">Waiting for calls — updates appear instantly</p>
            <div class="mt-3 inline-flex items-center gap-2 text-xs text-gray-400">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                </span>
                Live monitoring active
            </div>
        </div>
    </div>

    {{-- Live Monitoring Connection Status --}}
    <div id="ws-status" class="fixed bottom-4 right-4 z-50 hidden">
        <div class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium shadow-lg" id="ws-status-badge">
            <span class="w-2 h-2 rounded-full" id="ws-status-dot"></span>
            <span id="ws-status-text"></span>
        </div>
    </div>

@push('scripts')
<script>
(function() {
    const WS_URL = (window.location.protocol === 'https:' ? 'wss://' : 'ws://') + window.location.host + '/ws/live-calls';
    let ws = null;
    let reconnectAttempts = 0;
    const maxReconnect = 10;

    // DOM references
    const statTotal = document.getElementById('stat-total');
    const statAnswered = document.getElementById('stat-answered');
    const statRinging = document.getElementById('stat-ringing');
    const statInbound = document.getElementById('stat-inbound');
    const statOutbound = document.getElementById('stat-outbound');
    const callsTableBody = document.getElementById('live-calls-tbody');
    const callsCount = document.getElementById('calls-count');
    const emptyState = document.getElementById('empty-state');
    const callsTable = document.getElementById('calls-table');
    const wsStatus = document.getElementById('ws-status');
    const wsStatusBadge = document.getElementById('ws-status-badge');
    const wsStatusDot = document.getElementById('ws-status-dot');
    const wsStatusText = document.getElementById('ws-status-text');
    const subtitle = document.getElementById('page-subtitle');

    function connect() {
        ws = new WebSocket(WS_URL);

        ws.onopen = function() {
            reconnectAttempts = 0;
            showStatus('connected', 'Live');
            // Hide status badge after 3 seconds
            setTimeout(() => { wsStatus.classList.add('hidden'); }, 3000);
        };

        ws.onmessage = function(event) {
            const data = JSON.parse(event.data);
            handleMessage(data);
        };

        ws.onclose = function() {
            showStatus('disconnected', 'Reconnecting...');
            if (reconnectAttempts < maxReconnect) {
                reconnectAttempts++;
                setTimeout(connect, Math.min(1000 * reconnectAttempts, 10000));
            }
        };

        ws.onerror = function() {
            showStatus('error', 'Connection error');
        };
    }

    function handleMessage(data) {
        switch (data.type) {
            case 'snapshot':
                updateStats(data.stats);
                renderCallsTable(data.calls);
                break;
            case 'call_start':
                updateStats(data.stats);
                addCallRow(data.call);
                break;
            case 'call_answered':
                updateStats(data.stats);
                updateCallRow(data.call);
                break;
            case 'call_end':
                updateStats(data.stats);
                removeCallRow(data.unique_id);
                break;
            case 'pong':
                break;
        }
    }

    function updateStats(stats) {
        if (!stats) return;
        if (statTotal) statTotal.textContent = stats.total.toLocaleString();
        if (statAnswered) statAnswered.textContent = stats.answered.toLocaleString();
        if (statRinging) statRinging.textContent = stats.ringing.toLocaleString();
        if (statInbound) statInbound.textContent = stats.inbound.toLocaleString();
        if (statOutbound) statOutbound.textContent = stats.outbound.toLocaleString();
        if (subtitle) subtitle.textContent = 'Real-time monitoring of ' + stats.total.toLocaleString() + ' ongoing calls';
    }

    function renderCallsTable(calls) {
        if (!callsTableBody) return;

        if (calls.length === 0) {
            if (callsTable) callsTable.classList.add('hidden');
            if (emptyState) emptyState.classList.remove('hidden');
            return;
        }

        if (callsTable) callsTable.classList.remove('hidden');
        if (emptyState) emptyState.classList.add('hidden');

        callsTableBody.innerHTML = '';
        calls.forEach(call => addCallRow(call));

        if (callsCount) callsCount.textContent = 'Showing ' + calls.length + ' live calls';
    }

    function addCallRow(call) {
        if (!callsTableBody) return;

        // Show table, hide empty state
        if (callsTable) callsTable.classList.remove('hidden');
        if (emptyState) emptyState.classList.add('hidden');

        // Remove existing row if present
        const existing = document.getElementById('call-' + call.unique_id);
        if (existing) existing.remove();

        const tr = document.createElement('tr');
        tr.id = 'call-' + call.unique_id;
        tr.className = 'hover:bg-gray-50 transition-colors animate-fade-in';

        const statusBadge = call.state === 'answered'
            ? '<span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Answered</span>'
            : '<span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>Ringing</span>';

        const dirBadge = call.call_flow === 'inbound'
            ? '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-blue-100 text-blue-700">IN</span>'
            : '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-purple-100 text-purple-700">OUT</span>';

        const durationEl = call.state === 'answered'
            ? '<span class="relative flex h-2 w-2 inline-block mr-1"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span></span><span class="font-medium text-green-600 call-duration" data-started="' + call.started_at + '">' + formatDuration(call.duration) + '</span>'
            : '<span class="font-medium text-gray-600 call-duration" data-started="' + call.started_at + '">' + formatDuration(call.duration) + '</span>';

        tr.innerHTML = `
            <td class="px-4 py-3">${statusBadge}</td>
            <td class="px-4 py-3">${dirBadge}</td>
            <td class="px-4 py-3"><span class="font-mono text-gray-900">${escapeHtml(call.caller || '—')}</span></td>
            <td class="px-4 py-3"><span class="font-mono text-gray-900">${escapeHtml(call.callee || '—')}</span></td>
            <td class="px-4 py-3">${durationEl}</td>
            <td class="px-4 py-3"><span class="text-gray-600">${escapeHtml(call.sip_account || '—')}</span></td>
            <td class="px-4 py-3"><span class="text-gray-600">${escapeHtml(call.trunk || '—')}</span></td>
        `;

        callsTableBody.prepend(tr);
        updateCallsCount();
    }

    function updateCallRow(call) {
        const row = document.getElementById('call-' + call.unique_id);
        if (row) {
            // Update status badge to answered
            const statusCell = row.querySelector('td:first-child');
            if (statusCell) {
                statusCell.innerHTML = '<span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Answered</span>';
            }
            // Flash green briefly
            row.classList.add('bg-emerald-50');
            setTimeout(() => row.classList.remove('bg-emerald-50'), 1500);
        } else {
            addCallRow(call);
        }
    }

    function removeCallRow(uniqueId) {
        const row = document.getElementById('call-' + uniqueId);
        if (row) {
            row.classList.add('bg-red-50', 'opacity-50');
            setTimeout(() => {
                row.remove();
                updateCallsCount();
                // Show empty state if no more rows
                if (callsTableBody && callsTableBody.children.length === 0) {
                    if (callsTable) callsTable.classList.add('hidden');
                    if (emptyState) emptyState.classList.remove('hidden');
                }
            }, 800);
        }
    }

    function updateCallsCount() {
        if (callsCount && callsTableBody) {
            const count = callsTableBody.children.length;
            callsCount.textContent = 'Showing ' + count + ' live calls';
        }
    }

    function formatDuration(seconds) {
        if (!seconds || seconds < 0) return '0s';
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        return m > 0 ? m + 'm ' + s + 's' : s + 's';
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function showStatus(state, text) {
        if (!wsStatus) return;
        wsStatus.classList.remove('hidden');
        wsStatusText.textContent = text;

        if (state === 'connected') {
            wsStatusBadge.className = 'flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium shadow-lg bg-emerald-100 text-emerald-700';
            wsStatusDot.className = 'w-2 h-2 rounded-full bg-emerald-500';
        } else if (state === 'disconnected') {
            wsStatusBadge.className = 'flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium shadow-lg bg-amber-100 text-amber-700';
            wsStatusDot.className = 'w-2 h-2 rounded-full bg-amber-500 animate-pulse';
        } else {
            wsStatusBadge.className = 'flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium shadow-lg bg-red-100 text-red-700';
            wsStatusDot.className = 'w-2 h-2 rounded-full bg-red-500';
        }
    }

    // Update durations every second
    setInterval(function() {
        document.querySelectorAll('.call-duration').forEach(el => {
            const started = parseFloat(el.dataset.started);
            if (started) {
                const elapsed = Math.floor(Date.now() / 1000 - started);
                el.textContent = formatDuration(elapsed);
            }
        });
    }, 1000);

    // Send ping every 25 seconds to keep connection alive
    setInterval(function() {
        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send('ping');
        }
    }, 25000);

    // Start connection
    connect();
})();
</script>
@endpush
</x-admin-layout>
