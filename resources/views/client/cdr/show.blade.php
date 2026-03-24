<x-client-layout>
    <x-slot name="header">Call Detail</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl flex items-center justify-center {{ $record->disposition === 'ANSWERED' ? 'bg-emerald-100' : 'bg-red-100' }}">
                <svg class="w-7 h-7 {{ $record->disposition === 'ANSWERED' ? 'text-emerald-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">{{ $record->caller }} &rarr; {{ $record->callee }}</h2>
                <div class="flex items-center gap-2 mt-1">
                    @switch($record->disposition)
                        @case('ANSWERED') <span class="badge badge-success">Answered</span> @break
                        @case('NO ANSWER') <span class="badge badge-warning">No Answer</span> @break
                        @case('BUSY') <span class="badge badge-warning">Busy</span> @break
                        @case('FAILED') <span class="badge badge-danger">Failed</span> @break
                        @default <span class="badge badge-gray">{{ $record->disposition }}</span>
                    @endswitch
                    <span class="text-sm text-gray-500">{{ $record->call_start?->format('M d, Y H:i:s') }}</span>
                </div>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('client.cdr.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back
            </a>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="stat-card">
            <div class="stat-icon bg-blue-100">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="stat-content">
                <p class="stat-value">{{ sprintf('%d:%02d', intdiv($record->duration, 60), $record->duration % 60) }}</p>
                <p class="stat-label">Duration</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-emerald-100">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="stat-content">
                <p class="stat-value">{{ sprintf('%d:%02d', intdiv($record->billable_duration, 60), $record->billable_duration % 60) }}</p>
                <p class="stat-label">Billable</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-purple-100">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1"/></svg>
            </div>
            <div class="stat-content">
                <p class="stat-value">{{ format_currency($record->rate_per_minute, 4) }}</p>
                <p class="stat-label">Rate/Min</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-indigo-100">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div class="stat-content">
                <p class="stat-value">{{ format_currency($record->total_cost, 4) }}</p>
                <p class="stat-label">Total Cost</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content (2/3) --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Call Information --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Call Information</h3>
                </div>
                <div class="detail-card-body">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-y-5 gap-x-6">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Caller</p>
                            <p class="text-sm font-mono font-semibold text-gray-900 mt-1">{{ $record->caller }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Callee</p>
                            <p class="text-sm font-mono font-semibold text-gray-900 mt-1">{{ $record->callee }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Destination</p>
                            <p class="text-sm text-gray-900 mt-1">{{ $record->destination ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Caller ID</p>
                            <p class="text-sm text-gray-900 mt-1">{{ $record->caller_id ?? '—' }}</p>
                        </div>
                        @if($record->sipAccount)
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">SIP Account</p>
                            <a href="{{ route('client.sip-accounts.show', $record->sipAccount) }}" class="text-sm font-mono text-indigo-600 hover:text-indigo-500 mt-1 inline-block">{{ $record->sipAccount->username }}</a>
                        </div>
                        @endif
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">UUID</p>
                            <p class="text-xs font-mono text-gray-500 mt-1 break-all">{{ $record->uuid }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Timing --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Timing & Duration</h3>
                </div>
                <div class="detail-card-body">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-y-5 gap-x-6">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Call Start</p>
                            <p class="text-sm text-gray-900 mt-1">{{ $record->call_start?->format('M d, Y') }}</p>
                            <p class="text-xs text-gray-500">{{ $record->call_start?->format('H:i:s') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Call End</p>
                            <p class="text-sm text-gray-900 mt-1">{{ $record->call_end?->format('M d, Y') ?? '—' }}</p>
                            <p class="text-xs text-gray-500">{{ $record->call_end?->format('H:i:s') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Duration</p>
                            <p class="text-sm font-medium text-gray-900 mt-1">{{ sprintf('%d:%02d', intdiv($record->duration, 60), $record->duration % 60) }}</p>
                            <p class="text-xs text-gray-500">{{ $record->duration }}s</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Billable</p>
                            <p class="text-sm font-medium text-gray-900 mt-1">{{ sprintf('%d:%02d', intdiv($record->billable_duration, 60), $record->billable_duration % 60) }}</p>
                            <p class="text-xs text-gray-500">{{ $record->billable_duration }}s</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar (1/3) --}}
        <div class="space-y-6">
            {{-- Billing --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Billing Details</h3>
                </div>
                <div class="detail-card-body">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">Rate / Minute</span>
                            <span class="text-sm font-mono font-medium text-gray-900">{{ format_currency($record->rate_per_minute, 6) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">Connection Fee</span>
                            <span class="text-sm font-mono text-gray-900">{{ format_currency($record->connection_fee, 6) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">Billable Duration</span>
                            <span class="text-sm text-gray-900">{{ $record->billable_duration }}s</span>
                        </div>
                        <hr class="border-gray-100">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Total Cost</span>
                            <span class="text-base font-bold text-gray-900">{{ format_currency($record->total_cost, 4) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Hangup Info --}}
            @if($record->hangup_cause)
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Hangup Info</h3>
                </div>
                <div class="detail-card-body">
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Hangup Cause</p>
                            <p class="text-sm text-gray-900 mt-1">{{ str_replace('_', ' ', $record->hangup_cause) }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-client-layout>
