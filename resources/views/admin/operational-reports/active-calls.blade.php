<x-admin-layout>
    <x-slot name="header">Active Calls</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div>
            <h2 class="page-title">Active Calls</h2>
            <p class="page-subtitle" id="page-subtitle">Real-time monitoring of {{ number_format($totalActive) }} ongoing calls</p>
        </div>
        <div class="page-actions">
            <button type="button" onclick="location.reload()" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Refresh
            </button>
            <a href="{{ route('admin.operational-reports.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back
            </a>
        </div>
    </div>

    {{-- Stats Cards (compact) --}}
    <div class="mb-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2">
        <div class="flex items-center gap-2 px-3 py-2 bg-white rounded-lg border border-gray-200">
            <div class="w-7 h-7 rounded-md bg-emerald-100 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            </div>
            <div class="flex items-baseline gap-1.5 min-w-0">
                <p class="text-lg font-semibold text-emerald-600 leading-none tabular-nums" id="stat-total">{{ number_format($totalActive) }}</p>
                <p class="text-xs text-gray-500 truncate">Active</p>
            </div>
        </div>
        <div class="flex items-center gap-2 px-3 py-2 bg-white rounded-lg border border-gray-200">
            <div class="w-7 h-7 rounded-md bg-emerald-100 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="flex items-baseline gap-1.5 min-w-0">
                <p class="text-lg font-semibold text-gray-900 leading-none tabular-nums" id="stat-answered">{{ number_format($answeredCount) }}</p>
                <p class="text-xs text-gray-500 truncate">Answered</p>
            </div>
        </div>
        <div class="flex items-center gap-2 px-3 py-2 bg-white rounded-lg border border-gray-200">
            <div class="w-7 h-7 rounded-md bg-amber-100 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            </div>
            <div class="flex items-baseline gap-1.5 min-w-0">
                <p class="text-lg font-semibold text-amber-600 leading-none tabular-nums" id="stat-ringing">{{ number_format($ringingCount) }}</p>
                <p class="text-xs text-gray-500 truncate">Ringing</p>
            </div>
        </div>
        <div class="flex items-center gap-2 px-3 py-2 bg-white rounded-lg border border-gray-200">
            <div class="w-7 h-7 rounded-md bg-blue-100 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.517l2.257-1.128a1 1 0 00.502-1.21L9.228 3.683A1 1 0 008.279 3H5z"/></svg>
            </div>
            <div class="flex items-baseline gap-1.5 min-w-0">
                <p class="text-lg font-semibold text-gray-900 leading-none tabular-nums" id="stat-inbound">{{ number_format($inboundActive) }}</p>
                <p class="text-xs text-gray-500 truncate">Inbound</p>
            </div>
        </div>
        <div class="flex items-center gap-2 px-3 py-2 bg-white rounded-lg border border-gray-200">
            <div class="w-7 h-7 rounded-md bg-purple-100 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            </div>
            <div class="flex items-baseline gap-1.5 min-w-0">
                <p class="text-lg font-semibold text-gray-900 leading-none tabular-nums" id="stat-outbound">{{ number_format($outboundActive) }}</p>
                <p class="text-xs text-gray-500 truncate">Outbound</p>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="filter-card mb-3">
        <form method="GET" class="flex items-center gap-3">
            <div class="filter-search-box flex-1">
                <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search caller or callee..." class="filter-input">
            </div>
            <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden text-xs flex-shrink-0">
                <a href="{{ route('admin.operational-reports.active', array_merge(request()->except('call_flow'), [])) }}" class="px-3 py-2 font-medium {{ !request('call_flow') ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">All</a>
                <a href="{{ route('admin.operational-reports.active', array_merge(request()->except('call_flow'), ['call_flow' => 'trunk_to_sip'])) }}" class="px-3 py-2 font-medium border-l {{ request('call_flow') === 'trunk_to_sip' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">Inbound</a>
                <a href="{{ route('admin.operational-reports.active', array_merge(request()->except('call_flow'), ['call_flow' => 'sip_to_trunk'])) }}" class="px-3 py-2 font-medium border-l {{ request('call_flow') === 'sip_to_trunk' ? 'bg-purple-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">Outbound</a>
            </div>
            <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden text-xs flex-shrink-0">
                <a href="{{ route('admin.operational-reports.active', array_merge(request()->except('call_state'), [])) }}" class="px-3 py-2 font-medium {{ !request('call_state') ? 'bg-gray-800 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">All</a>
                <a href="{{ route('admin.operational-reports.active', array_merge(request()->except('call_state'), ['call_state' => 'answered'])) }}" class="px-3 py-2 font-medium border-l {{ request('call_state') === 'answered' ? 'bg-emerald-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">Answered</a>
                <a href="{{ route('admin.operational-reports.active', array_merge(request()->except('call_state'), ['call_state' => 'ringing'])) }}" class="px-3 py-2 font-medium border-l {{ request('call_state') === 'ringing' ? 'bg-amber-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">Ringing</a>
            </div>
            <button type="submit" class="btn-search-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Search
            </button>
            @if(request()->hasAny(['search', 'call_flow', 'call_state']))
                <a href="{{ route('admin.operational-reports.active') }}" class="btn-clear">Clear</a>
            @endif
        </form>
    </div>

    {{-- Calls Table --}}
    <div id="calls-table" class="bg-white rounded-xl border border-gray-200 overflow-hidden {{ $calls->count() === 0 ? 'hidden' : '' }}">
        <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                <span id="calls-count">{{ $calls->count() }} Live Calls</span>
            </span>
            <div class="flex items-center gap-3 text-xs text-gray-400">
                <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Answered</span>
                <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>Ringing</span>
            </div>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-10">SL</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">SIP</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Client</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Caller</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Callee</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Start Time</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Duration</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Trunk</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Dir</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody id="live-calls-tbody">
                @foreach ($calls as $call)
                    <tr class="{{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-indigo-50/50 transition-all border-b border-gray-100">
                        <td class="px-3 py-2 text-gray-400 tabular-nums">{{ $loop->iteration }}</td>
                        <td class="px-3 py-2">
                            @if($call->sipAccount)
                                <a href="{{ route('admin.sip-accounts.show', $call->sipAccount) }}" class="text-indigo-600 hover:text-indigo-500 font-mono text-xs">{{ $call->sipAccount->username }}</a>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-700">
                            @if($call->sipAccount && $call->sipAccount->user)
                                {{ Str::limit($call->sipAccount->user->name, 18) }}
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 font-mono text-gray-900 text-xs">{{ $call->caller }}</td>
                        <td class="px-3 py-2 font-mono text-gray-900 text-xs">{{ $call->callee }}</td>
                        <td class="px-3 py-2 text-xs text-gray-600 tabular-nums">{{ $call->call_start->format('H:i:s') }}</td>
                        <td class="px-3 py-2">
                            @if($call->call_state === 'answered')
                                <span class="font-medium text-xs text-emerald-600">{{ $call->call_start->diffForHumans(null, true) }}</span>
                            @else
                                <span class="text-xs text-gray-400 italic">Ringing…</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-600">
                            @if($call->call_flow === 'trunk_to_sip' && $call->incomingTrunk)
                                {{ Str::limit($call->incomingTrunk->name, 15) }}
                            @elseif($call->call_flow === 'sip_to_trunk' && $call->outgoingTrunk)
                                {{ Str::limit($call->outgoingTrunk->name, 15) }}
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            @if($call->call_flow === 'trunk_to_sip')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-700">IN</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-purple-100 text-purple-700">OUT</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            @if($call->call_state === 'answered')
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Answered</span>
                            @elseif($call->call_state === 'ringing')
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>Ringing</span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-500"><span class="w-1.5 h-1.5 rounded-full bg-gray-400 animate-pulse"></span>Processing</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
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
                // The displayed row may be keyed by either leg's unique_id,
                // so try unique_id first, then fall back to linked_id.
                removeCallRow(data.unique_id, data.linked_id);
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
        if (call.linked_id) tr.dataset.linked = call.linked_id;
        tr.className = 'hover:bg-gray-50 transition-colors animate-fade-in';

        const statusBadge = call.state === 'answered'
            ? '<span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Answered</span>'
            : '<span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>Ringing</span>';

        const dirBadge = call.call_flow === 'inbound'
            ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-700">IN</span>'
            : '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-purple-100 text-purple-700">OUT</span>';

        const durationEl = call.state === 'answered'
            ? '<span class="relative flex h-2 w-2 inline-block mr-1"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span></span><span class="font-medium text-green-600 call-duration" data-answered="' + (call.answered_at || '') + '">' + formatDuration(call.duration) + '</span>'
            : '<span class="text-xs text-gray-400 italic">Ringing…</span>';

        const startedTime = call.started_at
            ? new Date(call.started_at * 1000).toLocaleTimeString('en-GB', { hour12: false })
            : '—';

        // Column order: SL · SIP · Client · Caller · Callee · Start Time · Duration · Trunk · Dir · Status
        tr.innerHTML = `
            <td class="px-3 py-2 text-gray-400 tabular-nums sl-cell">·</td>
            <td class="px-3 py-2"><span class="text-indigo-600 font-mono text-xs">${escapeHtml(call.sip_account || '—')}</span></td>
            <td class="px-3 py-2 text-xs text-gray-700">${escapeHtml(call.client || '—')}</td>
            <td class="px-3 py-2 font-mono text-gray-900 text-xs">${escapeHtml(call.caller || '—')}</td>
            <td class="px-3 py-2 font-mono text-gray-900 text-xs">${escapeHtml(call.callee || '—')}</td>
            <td class="px-3 py-2 text-xs text-gray-600 tabular-nums">${escapeHtml(startedTime)}</td>
            <td class="px-3 py-2">${durationEl}</td>
            <td class="px-3 py-2 text-xs text-gray-600">${escapeHtml(call.trunk || '—')}</td>
            <td class="px-3 py-2">${dirBadge}</td>
            <td class="px-3 py-2 status-cell">${statusBadge}</td>
        `;

        callsTableBody.prepend(tr);
        renumberRows();
        updateCallsCount();
    }

    function renumberRows() {
        if (!callsTableBody) return;
        Array.from(callsTableBody.children).forEach((tr, idx) => {
            const sl = tr.querySelector('.sl-cell');
            if (sl) sl.textContent = (idx + 1).toString();
        });
    }

    function updateCallRow(call) {
        const row = document.getElementById('call-' + call.unique_id);
        if (row) {
            // Status column is now the last cell
            const statusCell = row.querySelector('.status-cell');
            if (statusCell) {
                statusCell.innerHTML = '<span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Answered</span>';
            }
            // Duration is column 7 (1=SL, 2=SIP, 3=Client, 4=Caller, 5=Callee, 6=Start, 7=Duration)
            const durationCell = row.querySelector('td:nth-child(7)');
            if (durationCell) {
                durationCell.innerHTML = '<span class="relative flex h-2 w-2 inline-block mr-1"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span></span><span class="font-medium text-green-600 call-duration" data-answered="' + (call.answered_at || '') + '">' + formatDuration(call.duration) + '</span>';
            }
            // Flash green briefly
            row.classList.add('bg-emerald-50');
            setTimeout(() => row.classList.remove('bg-emerald-50'), 1500);
        } else {
            addCallRow(call);
        }
    }

    function removeCallRow(uniqueId, linkedId) {
        let row = document.getElementById('call-' + uniqueId);
        if (!row && linkedId) {
            // Row may be keyed by the other leg's unique_id; find by linked_id.
            row = callsTableBody?.querySelector('tr[data-linked="' + linkedId + '"]');
        }
        if (row) {
            row.classList.add('bg-red-50', 'opacity-50');
            setTimeout(() => {
                row.remove();
                renumberRows();
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

    // Update durations every second — only for answered calls (data-answered set).
    setInterval(function() {
        document.querySelectorAll('.call-duration').forEach(el => {
            const answered = parseFloat(el.dataset.answered);
            if (answered) {
                const elapsed = Math.floor(Date.now() / 1000 - answered);
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
