<x-admin-layout>
    <x-slot name="header">Call Detail</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl flex items-center justify-center {{ $record->disposition === 'ANSWERED' ? 'bg-emerald-100' : ($record->disposition === 'FAILED' ? 'bg-red-100' : 'bg-amber-100') }}">
                @if($record->disposition === 'ANSWERED')
                    <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                @elseif($record->disposition === 'FAILED')
                    <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                @else
                    <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                @endif
            </div>
            <div>
                <h2 class="page-title">Call Detail Record</h2>
                <div class="flex items-center gap-2 mt-1">
                    @switch($record->disposition)
                        @case('ANSWERED') <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Answered</span> @break
                        @case('NO ANSWER') <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>No Answer</span> @break
                        @case('BUSY') <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Busy</span> @break
                        @case('FAILED') <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Failed</span> @break
                        @case('CANCEL') <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-600" title="Caller hung up before answer"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>Cancelled</span> @break
                    @endswitch
                    <span class="text-gray-300">|</span>
                    <span class="badge badge-gray">{{ ucfirst(str_replace('_', ' ', $record->call_flow)) }}</span>
                    <span class="badge {{ $record->status === 'charged' || $record->status === 'rated' ? 'badge-success' : ($record->status === 'in_progress' ? 'badge-warning' : 'badge-gray') }}">{{ ucfirst(str_replace('_', ' ', $record->status)) }}</span>
                </div>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.cdr.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to CDR
            </a>
        </div>
    </div>

    {{-- Call Summary Banner --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
        <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
            {{-- Left: Caller → Callee --}}
            <div class="flex items-center gap-4">
                <div class="text-center">
                    <p class="text-xs text-gray-500 mb-1">Caller</p>
                    <p class="font-mono font-bold text-gray-900">{{ $record->caller }}</p>
                    @if($record->sipAccount)
                        <p class="text-xs text-indigo-600">{{ $record->sipAccount->username }}</p>
                    @endif
                </div>
                <svg class="w-6 h-6 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                <div class="text-center">
                    <p class="text-xs text-gray-500 mb-1">Callee</p>
                    <p class="font-mono font-bold text-gray-900">{{ $record->callee }}</p>
                    @if($record->destination && $record->destination !== $record->callee)
                        <p class="text-xs text-gray-400">→ {{ $record->destination }}</p>
                    @endif
                </div>
            </div>
            {{-- Right: Key Metrics --}}
            <div style="display:grid; grid-template-columns: repeat(5, 1fr); gap: 0.75rem;">
                <div class="text-center p-2 rounded-lg bg-gray-50">
                    <p class="text-xs text-gray-500">Ring</p>
                    <p class="text-sm font-bold text-gray-900 tabular-nums">{{ max(0, ($record->duration ?? 0) - ($record->billsec ?? 0)) }}s</p>
                </div>
                <div class="text-center p-2 rounded-lg bg-gray-50">
                    <p class="text-xs text-gray-500">Talk</p>
                    <p class="text-sm font-bold text-gray-900 tabular-nums">{{ sprintf('%d:%02d', intdiv($record->billsec, 60), $record->billsec % 60) }}</p>
                </div>
                <div class="text-center p-2 rounded-lg bg-gray-50">
                    <p class="text-xs text-gray-500">Total</p>
                    <p class="text-sm font-bold text-gray-900 tabular-nums">{{ sprintf('%d:%02d', intdiv($record->duration, 60), $record->duration % 60) }}</p>
                </div>
                <div class="text-center p-2 rounded-lg bg-gray-50">
                    <p class="text-xs text-gray-500">Cost</p>
                    <p class="text-sm font-bold text-indigo-600 tabular-nums">{{ format_currency($record->total_cost, 4) }}</p>
                </div>
                <div class="text-center p-2 rounded-lg bg-gray-50">
                    <p class="text-xs text-gray-500">Date</p>
                    <p class="text-sm font-bold text-gray-900">{{ $record->call_start?->format('d M') }}</p>
                    <p class="text-xs text-gray-400">{{ $record->call_start?->format('H:i:s') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Call Information --}}
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Call Information</h3></div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item md:col-span-2">
                            <span class="detail-label">UUID</span>
                            <span class="detail-value font-mono text-xs break-all text-gray-500">{{ $record->uuid }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Caller</span>
                            <span class="detail-value font-mono font-semibold">{{ $record->caller }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Callee</span>
                            <span class="detail-value font-mono font-semibold">{{ $record->callee }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Caller ID</span>
                            <span class="detail-value">{{ $record->caller_id ?? '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Destination</span>
                            <span class="detail-value font-mono">{{ $record->destination ?: '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Matched Prefix</span>
                            <span class="detail-value font-mono font-bold text-indigo-600">{{ $record->matched_prefix ?: '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Hangup Cause</span>
                            <span class="detail-value">{{ $record->hangup_cause ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Timing --}}
            @php
                $ringDuration = max(0, ($record->duration ?? 0) - ($record->billsec ?? 0));
                $connectedAt = ($record->call_end && $record->billsec > 0) ? $record->call_end->copy()->subSeconds($record->billsec) : null;
            @endphp
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Timing & Duration</h3></div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Ring Start</span>
                            <span class="detail-value">{{ $record->call_start?->format('d M Y, H:i:s') }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Connected</span>
                            <span class="detail-value">{{ $connectedAt?->format('d M Y, H:i:s') ?? '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Hangup</span>
                            <span class="detail-value">{{ $record->call_end?->format('d M Y, H:i:s') ?? '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Ring Duration</span>
                            <span class="detail-value tabular-nums">{{ $ringDuration }}s</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Talk Duration</span>
                            <span class="detail-value tabular-nums font-semibold">{{ sprintf('%d:%02d', intdiv($record->billsec, 60), $record->billsec % 60) }} <span class="text-gray-400 text-xs">({{ $record->billsec }}s)</span></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Total Duration</span>
                            <span class="detail-value tabular-nums">{{ sprintf('%d:%02d', intdiv($record->duration, 60), $record->duration % 60) }} <span class="text-gray-400 text-xs">({{ $record->duration }}s)</span></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Billable Duration</span>
                            <span class="detail-value tabular-nums font-semibold">{{ sprintf('%d:%02d', intdiv($record->billable_duration, 60), $record->billable_duration % 60) }} <span class="text-gray-400 text-xs">({{ $record->billable_duration }}s)</span></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Rated At</span>
                            <span class="detail-value">{{ $record->rated_at?->format('d M Y, H:i:s') ?? 'Not rated' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Billing --}}
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Billing</h3></div>
                <div class="detail-card-body">
                    <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem;" class="mb-4">
                        <div class="p-3 rounded-lg border border-gray-200">
                            <p class="text-xs text-gray-500">Rate / Minute</p>
                            <p class="text-sm font-semibold text-gray-900">{{ format_currency($record->rate_per_minute, 6) }}</p>
                        </div>
                        <div class="p-3 rounded-lg border border-gray-200">
                            <p class="text-xs text-gray-500">Connection Fee</p>
                            <p class="text-sm font-semibold text-gray-900">{{ format_currency($record->connection_fee, 6) }}</p>
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem;">
                        <div class="p-3 rounded-lg bg-indigo-50 border border-indigo-100 text-center">
                            <p class="text-xs text-indigo-600">Client Cost</p>
                            <p class="text-lg font-bold text-indigo-700">{{ format_currency($record->total_cost, 4) }}</p>
                        </div>
                        <div class="p-3 rounded-lg bg-emerald-50 border border-emerald-100 text-center">
                            <p class="text-xs text-emerald-600">Reseller Cost</p>
                            <p class="text-lg font-bold text-emerald-700">{{ format_currency($record->reseller_cost, 4) }}</p>
                        </div>
                        <div class="p-3 rounded-lg bg-amber-50 border border-amber-100 text-center">
                            <p class="text-xs text-amber-600">Trunk Cost</p>
                            <p class="text-lg font-bold text-amber-700">{{ format_currency($record->trunk_cost, 4) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Technical --}}
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Technical Details</h3></div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Channel</span>
                            <span class="detail-value font-mono text-xs">{{ $record->ast_channel ?? '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Dst Channel</span>
                            <span class="detail-value font-mono text-xs">{{ $record->ast_dstchannel ?? '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Context</span>
                            <span class="detail-value font-mono text-xs">{{ $record->ast_context ?? '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Source IP</span>
                            <span class="detail-value font-mono text-xs">{{ $record->src_ip ?? '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Destination IP</span>
                            <span class="detail-value font-mono text-xs">{{ $record->dst_ip ?? '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Rate Group</span>
                            <span class="detail-value">{{ $record->rateGroup?->name ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recording --}}
            @if($hasRecording)
            <div class="detail-card">
                <div class="detail-card-header">
                    <div class="flex items-start justify-between w-full">
                        <h3 class="detail-card-title">Call Recording</h3>
                        <a href="{{ route('admin.cdr.recording', $record->uuid) }}" download class="text-xs text-indigo-600 hover:text-indigo-500 font-medium">Download</a>
                    </div>
                </div>
                <div class="detail-card-body">
                    <audio controls preload="none" class="w-full"><source src="{{ route('admin.cdr.recording', $record->uuid) }}" type="audio/wav"></audio>
                </div>
            </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- User --}}
            @if($record->user)
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">User</h3></div>
                <div class="detail-card-body">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-semibold">{{ strtoupper(substr($record->user->name, 0, 1)) }}</div>
                        <div>
                            <a href="{{ route('admin.users.show', $record->user) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">{{ $record->user->name }}</a>
                            <p class="text-xs text-gray-500">{{ ucfirst($record->user->role) }}</p>
                        </div>
                    </div>
                    <div class="space-y-1.5 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500">Email</span><span class="text-gray-900 text-xs truncate ml-2">{{ $record->user->email }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Balance</span><span class="font-bold text-gray-900">{{ format_currency($record->user->balance ?? 0) }}</span></div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Reseller --}}
            @if($record->reseller)
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Reseller</h3></div>
                <div class="detail-card-body">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center font-semibold">{{ strtoupper(substr($record->reseller->name, 0, 1)) }}</div>
                        <div>
                            <a href="{{ route('admin.users.show', $record->reseller) }}" class="text-sm font-semibold text-emerald-600 hover:text-emerald-700">{{ $record->reseller->name }}</a>
                            <p class="text-xs text-gray-500">Reseller</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Routing --}}
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Routing</h3></div>
                <div class="detail-card-body space-y-3">
                    @if($record->sipAccount)
                    <div class="flex items-center gap-3 p-2.5 rounded-lg bg-indigo-50">
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">SIP Account</p>
                            <a href="{{ route('admin.sip-accounts.show', $record->sipAccount) }}" class="text-sm font-semibold text-indigo-600 font-mono">{{ $record->sipAccount->username }}</a>
                        </div>
                    </div>
                    @endif
                    @if($record->incomingTrunk)
                    <div class="flex items-center gap-3 p-2.5 rounded-lg bg-emerald-50">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Incoming Trunk</p>
                            <a href="{{ route('admin.trunks.show', $record->incomingTrunk) }}" class="text-sm font-semibold text-emerald-600">{{ $record->incomingTrunk->name }}</a>
                        </div>
                    </div>
                    @endif
                    @if($record->outgoingTrunk)
                    <div class="flex items-center gap-3 p-2.5 rounded-lg bg-purple-50">
                        <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Outgoing Trunk</p>
                            <a href="{{ route('admin.trunks.show', $record->outgoingTrunk) }}" class="text-sm font-semibold text-purple-600">{{ $record->outgoingTrunk->name }}</a>
                        </div>
                    </div>
                    @endif
                    @if($record->did)
                    <div class="flex items-center gap-3 p-2.5 rounded-lg bg-sky-50">
                        <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">DID</p>
                            <a href="{{ route('admin.dids.show', $record->did) }}" class="text-sm font-semibold text-sky-600 font-mono">{{ $record->did->number }}</a>
                        </div>
                    </div>
                    @endif
                    @if(!$record->sipAccount && !$record->incomingTrunk && !$record->outgoingTrunk && !$record->did)
                        <p class="text-sm text-gray-400 text-center py-2">No routing information</p>
                    @endif
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Quick Actions</h3></div>
                <div class="detail-card-body space-y-2">
                    @if($record->user)
                    <a href="{{ route('admin.cdr.index', ['user_id' => $record->user_id]) }}" class="quick-action-link">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        View User's Calls
                    </a>
                    <a href="{{ route('admin.users.show', $record->user) }}" class="quick-action-link">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        View User Profile
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
