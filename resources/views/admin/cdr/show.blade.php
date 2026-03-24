<x-admin-layout>
    <x-slot name="header">Call Detail</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Call Detail Record</h2>
                <div class="flex items-center gap-2 mt-1">
                    @switch($record->disposition)
                        @case('ANSWERED')
                            <span class="badge badge-success">ANSWERED</span>
                            @break
                        @case('NO ANSWER')
                            <span class="badge badge-warning">NO ANSWER</span>
                            @break
                        @case('BUSY')
                            <span class="badge badge-warning">BUSY</span>
                            @break
                        @case('FAILED')
                            <span class="badge badge-danger">FAILED</span>
                            @break
                        @case('CANCEL')
                            <span class="badge badge-gray">CANCEL</span>
                            @break
                    @endswitch
                    @switch($record->status)
                        @case('rated')
                            <span class="badge badge-info">Rated</span>
                            @break
                        @case('in_progress')
                            <span class="badge badge-warning">In Progress</span>
                            @break
                        @case('failed')
                            <span class="badge badge-danger">Failed</span>
                            @break
                        @case('unbillable')
                            <span class="badge badge-gray">Unbillable</span>
                            @break
                    @endswitch
                    @if($record->call_flow)
                        <span class="badge badge-purple">{{ str_replace('_', ' → ', strtoupper(str_replace('_to_', ' → ', $record->call_flow))) }}</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.cdr.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to CDR
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Call Information --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Call Information</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item md:col-span-2">
                            <span class="detail-label">UUID</span>
                            <span class="detail-value font-mono text-xs break-all">{{ $record->uuid }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Caller</span>
                            <span class="detail-value font-mono">{{ $record->caller }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Callee</span>
                            <span class="detail-value font-mono">{{ $record->callee }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Caller ID</span>
                            <span class="detail-value">{{ $record->caller_id ?? '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Destination</span>
                            <span class="detail-value">{{ $record->destination ?: '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Matched Prefix</span>
                            <span class="detail-value font-mono">{{ $record->matched_prefix ?: '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Hangup Cause</span>
                            <span class="detail-value">{{ $record->hangup_cause ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Timing & Duration --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Timing & Duration</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Call Start</span>
                            <span class="detail-value">{{ $record->call_start?->format('M d, Y H:i:s') }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Call End</span>
                            <span class="detail-value">{{ $record->call_end?->format('M d, Y H:i:s') ?? '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Duration</span>
                            <span class="detail-value">
                                {{ sprintf('%d:%02d', intdiv($record->duration, 60), $record->duration % 60) }}
                                <span class="text-gray-400 text-xs">({{ $record->duration }}s)</span>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Billsec</span>
                            <span class="detail-value">
                                {{ sprintf('%d:%02d', intdiv($record->billsec, 60), $record->billsec % 60) }}
                                <span class="text-gray-400 text-xs">({{ $record->billsec }}s)</span>
                            </span>
                        </div>
                        <div class="detail-item md:col-span-2">
                            <span class="detail-label">Billable Duration</span>
                            <span class="detail-value">
                                {{ sprintf('%d:%02d', intdiv($record->billable_duration, 60), $record->billable_duration % 60) }}
                                <span class="text-gray-400 text-xs">({{ $record->billable_duration }}s)</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Billing --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Billing</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Rate / Minute</span>
                            <span class="detail-value">{{ format_currency($record->rate_per_minute, 6) }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Connection Fee</span>
                            <span class="detail-value">{{ format_currency($record->connection_fee, 6) }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Total Cost</span>
                            <span class="detail-value font-semibold text-gray-900">{{ format_currency($record->total_cost, 4) }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Reseller Cost</span>
                            <span class="detail-value">{{ format_currency($record->reseller_cost, 4) }}</span>
                        </div>
                        <div class="detail-item md:col-span-2">
                            <span class="detail-label">Rated At</span>
                            <span class="detail-value">{{ $record->rated_at?->format('M d, Y H:i:s') ?? 'Not rated' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Technical Details --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Technical Details</h3>
                </div>
                <div class="detail-card-body">
                    <div class="space-y-3">
                        <div class="cdr-tech-row">
                            <span class="cdr-tech-label">Channel</span>
                            <span class="cdr-tech-value">{{ $record->ast_channel ?? '—' }}</span>
                        </div>
                        <div class="cdr-tech-row">
                            <span class="cdr-tech-label">Dst Channel</span>
                            <span class="cdr-tech-value">{{ $record->ast_dstchannel ?? '—' }}</span>
                        </div>
                        <div class="cdr-tech-row">
                            <span class="cdr-tech-label">Context</span>
                            <span class="cdr-tech-value">{{ $record->ast_context ?? '—' }}</span>
                        </div>
                        <div class="cdr-tech-row">
                            <span class="cdr-tech-label">Source IP</span>
                            <span class="cdr-tech-value">{{ $record->src_ip ?? '—' }}</span>
                        </div>
                        <div class="cdr-tech-row">
                            <span class="cdr-tech-label">Destination IP</span>
                            <span class="cdr-tech-value">{{ $record->dst_ip ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- User Card --}}
            @if($record->user)
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">User</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="avatar avatar-indigo">
                                {{ strtoupper(substr($record->user->name, 0, 1)) }}
                            </div>
                            <div>
                                <a href="{{ route('admin.users.show', $record->user) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">
                                    {{ $record->user->name }}
                                </a>
                                <p class="text-xs text-gray-500">{{ ucfirst($record->user->role) }}</p>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Email</span>
                                <span class="text-gray-900 truncate ml-2">{{ $record->user->email }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Balance</span>
                                <span class="text-gray-900 font-medium">{{ format_currency($record->user->balance ?? 0) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Reseller Card --}}
            @if($record->reseller)
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Reseller</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3">
                            <div class="avatar avatar-emerald">
                                {{ strtoupper(substr($record->reseller->name, 0, 1)) }}
                            </div>
                            <div>
                                <a href="{{ route('admin.users.show', $record->reseller) }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700">
                                    {{ $record->reseller->name }}
                                </a>
                                <p class="text-xs text-gray-500">Reseller</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Routing Card --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Routing</h3>
                </div>
                <div class="detail-card-body space-y-4">
                    @if($record->sipAccount)
                        <div class="cdr-routing-item">
                            <div class="cdr-routing-icon cdr-routing-sip">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <div>
                                <span class="text-xs text-gray-500">SIP Account</span>
                                <a href="{{ route('admin.sip-accounts.show', $record->sipAccount) }}" class="block text-sm font-medium text-indigo-600 hover:text-indigo-700 font-mono">
                                    {{ $record->sipAccount->username }}
                                </a>
                            </div>
                        </div>
                    @endif

                    @if($record->incomingTrunk)
                        <div class="cdr-routing-item">
                            <div class="cdr-routing-icon cdr-routing-trunk-in">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                                </svg>
                            </div>
                            <div>
                                <span class="text-xs text-gray-500">Incoming Trunk</span>
                                <a href="{{ route('admin.trunks.show', $record->incomingTrunk) }}" class="block text-sm font-medium text-emerald-600 hover:text-emerald-700">
                                    {{ $record->incomingTrunk->name }}
                                </a>
                                <span class="text-xs text-gray-400">{{ $record->incomingTrunk->provider }}</span>
                            </div>
                        </div>
                    @endif

                    @if($record->outgoingTrunk)
                        <div class="cdr-routing-item">
                            <div class="cdr-routing-icon cdr-routing-trunk-out">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                                </svg>
                            </div>
                            <div>
                                <span class="text-xs text-gray-500">Outgoing Trunk</span>
                                <a href="{{ route('admin.trunks.show', $record->outgoingTrunk) }}" class="block text-sm font-medium text-purple-600 hover:text-purple-700">
                                    {{ $record->outgoingTrunk->name }}
                                </a>
                                <span class="text-xs text-gray-400">{{ $record->outgoingTrunk->provider }}</span>
                            </div>
                        </div>
                    @endif

                    @if($record->did)
                        <div class="cdr-routing-item">
                            <div class="cdr-routing-icon cdr-routing-did">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                </svg>
                            </div>
                            <div>
                                <span class="text-xs text-gray-500">DID</span>
                                <a href="{{ route('admin.dids.show', $record->did) }}" class="block text-sm font-medium text-sky-600 hover:text-sky-700 font-mono">
                                    {{ $record->did->number }}
                                </a>
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
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Quick Actions</h3>
                </div>
                <div class="detail-card-body space-y-2">
                    @if($record->user)
                        <a href="{{ route('admin.cdr.index', ['user_id' => $record->user_id]) }}" class="quick-action-btn">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            View User's Calls
                        </a>
                        <a href="{{ route('admin.users.show', $record->user) }}" class="quick-action-btn">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            View User Profile
                        </a>
                    @endif
                    @if($record->sipAccount)
                        <a href="{{ route('admin.sip-accounts.show', $record->sipAccount) }}" class="quick-action-btn">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            View SIP Account
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
