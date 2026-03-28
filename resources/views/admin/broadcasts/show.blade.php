<x-admin-layout>
    <x-slot name="header">Broadcast: {{ $broadcast->name }}</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">{{ $broadcast->name }}</h2>
                <div class="flex items-center gap-2 mt-1">
                    @switch($broadcast->status)
                        @case('draft') <span class="badge badge-gray">Draft</span> @break
                        @case('scheduled')
                            <span class="badge badge-blue">Scheduled</span>
                            @if($broadcast->scheduled_at)
                                <span class="text-xs text-gray-500">{{ $broadcast->scheduled_at->format('M d, Y g:i A') }}</span>
                            @endif
                            @break
                        @case('queued') <span class="badge badge-blue">Queued</span> @break
                        @case('running') <span class="badge badge-success">Running</span> @break
                        @case('paused') <span class="badge badge-warning">Paused</span> @break
                        @case('completed') <span class="badge badge-success">Completed</span> @break
                        @case('cancelled') <span class="badge badge-gray">Cancelled</span> @break
                        @case('failed') <span class="badge badge-danger">Failed</span> @break
                    @endswitch
                    <span class="text-xs text-gray-400">&middot; {{ ucfirst($broadcast->type) }}</span>
                </div>
            </div>
        </div>
        <div class="page-actions">
            @if(auth()->user()->isSuperAdmin() && in_array($broadcast->status, ['draft', 'scheduled', 'paused']))
                <a href="{{ route('admin.broadcasts.edit', $broadcast) }}" class="btn-action-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit
                </a>
            @endif
            <a href="{{ route('admin.broadcasts.export-results', $broadcast) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export
            </a>
            <a href="{{ route('admin.broadcasts.results', $broadcast) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Results
            </a>
            <a href="{{ route('admin.broadcasts.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back
            </a>
        </div>
    </div>

    {{-- Type & Schedule Banner --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <div class="flex flex-wrap items-center gap-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg {{ $broadcast->type === 'survey' ? 'bg-purple-100' : 'bg-blue-100' }} flex items-center justify-center">
                    @if($broadcast->type === 'survey')
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    @else
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                    @endif
                </div>
                <div>
                    <p class="text-xs text-gray-500">Broadcast Type</p>
                    <p class="text-sm font-semibold text-gray-900">{{ $broadcast->type === 'survey' ? 'Survey Broadcast' : 'Simple Voice Broadcast' }}</p>
                </div>
            </div>

            <div class="h-8 w-px bg-gray-200"></div>

            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Schedule</p>
                    @if($broadcast->scheduled_at)
                        <p class="text-sm font-semibold text-gray-900">{{ $broadcast->scheduled_at->format('d M Y, g:i A') }}</p>
                    @else
                        <p class="text-sm font-semibold text-gray-900">Manual Start</p>
                    @endif
                </div>
            </div>

            <div class="h-8 w-px bg-gray-200"></div>

            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Voice Template</p>
                    <p class="text-sm font-semibold text-gray-900">{{ $broadcast->voiceFile->name ?? '—' }}</p>
                </div>
            </div>

            <div class="h-8 w-px bg-gray-200"></div>

            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500">SIP Account</p>
                    <p class="text-sm font-semibold text-gray-900">{{ $broadcast->sipAccount->username ?? '—' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div x-data="broadcastStats()" x-init="init()">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            <div class="stat-card">
                <div class="stat-icon bg-indigo-100">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div class="stat-content">
                    <p class="stat-value" x-text="fmt(total)">{{ number_format($broadcast->total_numbers) }}</p>
                    <p class="stat-label">Total Numbers</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-blue-100">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                </div>
                <div class="stat-content">
                    <p class="stat-value" x-text="fmt(answered + failed)">{{ number_format($broadcast->dialed_count) }}</p>
                    <p class="stat-label">Dialed</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-emerald-100">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="stat-content">
                    <p class="stat-value" x-text="fmt(answered)">{{ number_format($broadcast->answered_count) }}</p>
                    <p class="stat-label">Answered</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-red-100">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="stat-content">
                    <p class="stat-value" x-text="fmt(failed)">{{ number_format($broadcast->failed_count) }}</p>
                    <p class="stat-label">Failed</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-amber-100">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="stat-content">
                    <p class="stat-value">{{ format_currency($callStats->total_cost ?? 0) }}</p>
                    <p class="stat-label">Total Cost</p>
                </div>
            </div>
        </div>

        {{-- Progress Bar --}}
        <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Progress</span>
                <span class="text-sm font-medium text-gray-700" x-text="progress + '%'">
                    {{ $broadcast->total_numbers > 0 ? round($broadcast->dialed_count / $broadcast->total_numbers * 100) : 0 }}%
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="h-3 rounded-full transition-all duration-500" :style="'width: ' + progress + '%'"
                     :class="progress >= 100 ? 'bg-emerald-500' : 'bg-indigo-500'"></div>
            </div>
            <div class="flex items-center justify-between mt-2 text-xs text-gray-500">
                <span x-text="fmt(answered + failed) + ' dialed of ' + fmt(total)"></span>
                <span>{{ $callStats->avg_duration ? round($callStats->avg_duration) . 's avg duration' : '' }} &middot; {{ $callStats->total_duration ? gmdate('H:i:s', $callStats->total_duration) . ' total talk time' : '' }}</span>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function broadcastStats() {
            return {
                total: {{ $broadcast->total_numbers }},
                answered: {{ $broadcast->answered_count }},
                failed: {{ $broadcast->failed_count }},
                progress: {{ $broadcast->total_numbers > 0 ? round($broadcast->dialed_count / $broadcast->total_numbers * 100) : 0 }},
                status: '{{ $broadcast->status }}',
                polling: null,
                fmt(n) { return n.toLocaleString(); },
                init() {
                    if (['running', 'queued', 'paused'].includes(this.status)) {
                        this.polling = setInterval(() => this.refresh(), 5000);
                    }
                },
                async refresh() {
                    try {
                        const res = await fetch('{{ route("admin.broadcasts.stats", $broadcast) }}');
                        const d = await res.json();
                        this.total = d.total;
                        this.answered = d.answered;
                        this.failed = d.failed;
                        this.progress = d.progress;
                        if (['completed', 'cancelled', 'failed'].includes(d.status) && d.status !== this.status) {
                            this.status = d.status;
                            clearInterval(this.polling);
                            location.reload();
                        }
                        this.status = d.status;
                    } catch (e) {}
                }
            };
        }
    </script>
    @endpush

    {{-- Control Buttons --}}
    <div class="flex items-center gap-3 mb-6">
        @if($broadcast->status === 'draft')
            <form method="POST" action="{{ route('admin.broadcasts.start', $broadcast) }}">@csrf
                <button type="submit" class="btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Start Broadcast
                </button>
            </form>
        @endif
        @if($broadcast->status === 'running')
            <form method="POST" action="{{ route('admin.broadcasts.pause', $broadcast) }}">@csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Pause
                </button>
            </form>
        @endif
        @if($broadcast->status === 'paused')
            <form method="POST" action="{{ route('admin.broadcasts.resume', $broadcast) }}">@csrf
                <button type="submit" class="btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Resume
                </button>
            </form>
        @endif
        @if(in_array($broadcast->status, ['draft', 'scheduled', 'queued', 'running', 'paused']))
            <form method="POST" action="{{ route('admin.broadcasts.cancel', $broadcast) }}" onsubmit="return confirm('Cancel this broadcast?')">@csrf
                <button type="submit" class="btn-danger">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Cancel Broadcast
                </button>
            </form>
        @endif

        {{-- Clone --}}
        @if(in_array($broadcast->status, ['completed', 'cancelled', 'failed']))
            <button type="button" onclick="document.getElementById('cloneModal').classList.remove('hidden')" class="inline-flex items-center px-4 py-2 bg-white border border-indigo-300 text-indigo-600 text-sm font-medium rounded-lg hover:bg-indigo-50 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                Clone
            </button>
        @endif

        @if(auth()->user()->isSuperAdmin() && in_array($broadcast->status, ['draft', 'cancelled']) && $broadcast->answered_count == 0)
            <form method="POST" action="{{ route('admin.broadcasts.destroy', $broadcast) }}" class="inline" onsubmit="return confirm('Delete this broadcast and all its numbers? This cannot be undone.')">
                @csrf @method('DELETE')
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-white border border-red-300 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Delete
                </button>
            </form>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left: Details + Number Breakdown --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Broadcast Configuration --}}
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Broadcast Configuration</h3></div>
                <div class="detail-card-body">
                    <div class="grid grid-cols-2 gap-x-8 gap-y-4">
                        <div>
                            <p class="text-xs text-gray-500 mb-0.5">Type</p>
                            <p class="text-sm font-medium text-gray-900">
                                @if($broadcast->type === 'survey')
                                    <span class="badge badge-purple">Survey</span>
                                @else
                                    <span class="badge badge-blue">Simple</span>
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-0.5">Voice Template</p>
                            <p class="text-sm font-medium text-gray-900">{{ $broadcast->voiceFile->name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-0.5">SIP Account</p>
                            <p class="text-sm font-medium text-gray-900">{{ $broadcast->sipAccount->username ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-0.5">Caller ID</p>
                            <p class="text-sm font-medium text-gray-900">{{ $broadcast->caller_id_number ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-0.5">Max Concurrent</p>
                            <p class="text-sm font-medium text-gray-900">{{ $broadcast->max_concurrent }} calls</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-0.5">Ring Timeout</p>
                            <p class="text-sm font-medium text-gray-900">{{ $broadcast->ring_timeout }}s</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-0.5">Created</p>
                            <p class="text-sm font-medium text-gray-900">{{ $broadcast->created_at->format('d M Y, g:i A') }}</p>
                        </div>
                        @if($broadcast->scheduled_at)
                            <div>
                                <p class="text-xs text-gray-500 mb-0.5">Scheduled For</p>
                                <p class="text-sm font-medium text-gray-900">{{ $broadcast->scheduled_at->format('d M Y, g:i A') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Number Status Breakdown --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Number Status Breakdown</h3>
                </div>
                <div class="detail-card-body">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        @php
                            $statuses = [
                                'pending' => ['label' => 'Pending', 'color' => 'gray', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                                'dialing' => ['label' => 'Dialing', 'color' => 'blue', 'icon' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'],
                                'answered' => ['label' => 'Answered', 'color' => 'emerald', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                                'no_answer' => ['label' => 'No Answer', 'color' => 'amber', 'icon' => 'M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829'],
                                'busy' => ['label' => 'Busy', 'color' => 'orange', 'icon' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
                                'failed' => ['label' => 'Failed', 'color' => 'red', 'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'],
                            ];
                        @endphp
                        @foreach($statuses as $key => $s)
                            <div class="flex items-center gap-3 p-3 bg-{{ $s['color'] }}-50 rounded-lg">
                                <svg class="w-5 h-5 text-{{ $s['color'] }}-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $s['icon'] }}"/></svg>
                                <div>
                                    <p class="text-lg font-bold text-{{ $s['color'] }}-700">{{ number_format($numberStats[$key] ?? 0) }}</p>
                                    <p class="text-xs text-{{ $s['color'] }}-600">{{ $s['label'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Call Performance --}}
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Call Performance</h3></div>
                <div class="detail-card-body">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <p class="text-xl font-bold text-gray-900">{{ $broadcast->total_numbers > 0 ? round(($broadcast->answered_count / $broadcast->total_numbers) * 100, 1) : 0 }}%</p>
                            <p class="text-xs text-gray-500 mt-1">Answer Rate</p>
                        </div>
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <p class="text-xl font-bold text-gray-900">{{ $callStats->avg_duration ? round($callStats->avg_duration) . 's' : '0s' }}</p>
                            <p class="text-xs text-gray-500 mt-1">Avg Duration</p>
                        </div>
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <p class="text-xl font-bold text-gray-900">{{ $callStats->total_duration ? gmdate('H:i:s', $callStats->total_duration) : '00:00:00' }}</p>
                            <p class="text-xs text-gray-500 mt-1">Total Talk Time</p>
                        </div>
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <p class="text-xl font-bold text-gray-900">{{ format_currency($callStats->total_cost ?? 0) }}</p>
                            <p class="text-xs text-gray-500 mt-1">Total Cost</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Sidebar --}}
        <div class="space-y-4" style="position:sticky; top:1rem;">
            {{-- Client --}}
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Client</h3></div>
                <div class="detail-card-body">
                    @if($broadcast->user)
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                <span class="text-sm font-bold text-indigo-600">{{ strtoupper(substr($broadcast->user->name, 0, 2)) }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('admin.users.show', $broadcast->user) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700 truncate block">{{ $broadcast->user->name }}</a>
                                <p class="text-xs text-gray-500 truncate">{{ $broadcast->user->email }}</p>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                            <span class="text-xs text-gray-500">Balance</span>
                            <span class="text-sm font-mono font-semibold {{ ($broadcast->user->balance ?? 0) > 0 ? 'text-emerald-600' : 'text-red-500' }}">{{ format_currency($broadcast->user->balance ?? 0) }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Quick Actions</h3></div>
                <div class="detail-card-body space-y-2">
                    <a href="{{ route('admin.broadcasts.results', $broadcast) }}" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        View Detailed Results
                    </a>
                    <a href="{{ route('admin.broadcasts.export-results', $broadcast) }}" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download Report
                    </a>
                    @if($broadcast->user)
                        <a href="{{ route('admin.users.show', $broadcast->user) }}" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            View Client Profile
                        </a>
                    @endif
                </div>
            </div>

            {{-- Created By --}}
            @if($broadcast->creator)
                <div class="detail-card">
                    <div class="detail-card-header"><h3 class="detail-card-title">Created By</h3></div>
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                                <span class="text-sm font-bold text-gray-600">{{ strtoupper(substr($broadcast->creator->name, 0, 2)) }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $broadcast->creator->name }}</p>
                                <p class="text-xs text-gray-500">{{ $broadcast->created_at->format('d M Y, g:i A') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
{{-- Clone Modal --}}
@if(in_array($broadcast->status, ['completed', 'cancelled', 'failed']))
<div id="cloneModal" class="hidden fixed inset-0 z-50 overflow-hidden" x-data="{ scheduleType: 'now' }">
    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" onclick="document.getElementById('cloneModal').classList.add('hidden')"></div>
    <div class="fixed inset-0 flex items-center justify-center p-6">
        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md" style="max-height: calc(100vh - 3rem);" onclick="event.stopPropagation()">
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Clone Broadcast</h3>
                        <p class="text-sm text-gray-500">{{ $broadcast->name }}</p>
                    </div>
                </div>
                <button onclick="document.getElementById('cloneModal').classList.add('hidden')" class="rounded-lg p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.broadcasts.clone', $broadcast) }}">
                @csrf
                <div class="overflow-y-auto px-6 py-4 space-y-4" style="max-height: calc(100vh - 12rem);">
                    {{-- Summary --}}
                    <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:0.5rem;">
                        <div class="p-2 bg-gray-50 rounded-lg text-center">
                            <p class="text-sm font-bold text-gray-900">{{ number_format($broadcast->total_numbers) }}</p>
                            <p class="text-xs text-gray-500">Total</p>
                        </div>
                        <div class="p-2 bg-emerald-50 rounded-lg text-center">
                            <p class="text-sm font-bold text-emerald-700">{{ number_format($broadcast->answered_count) }}</p>
                            <p class="text-xs text-emerald-600">Answered</p>
                        </div>
                        <div class="p-2 bg-red-50 rounded-lg text-center">
                            <p class="text-sm font-bold text-red-700">{{ number_format($broadcast->failed_count) }}</p>
                            <p class="text-xs text-red-600">Failed</p>
                        </div>
                        <div class="p-2 bg-amber-50 rounded-lg text-center">
                            <p class="text-sm font-bold text-amber-700">{{ $broadcast->total_numbers > 0 ? round(($broadcast->answered_count / $broadcast->total_numbers) * 100) : 0 }}%</p>
                            <p class="text-xs text-amber-600">Rate</p>
                        </div>
                    </div>

                    {{-- Name --}}
                    <div class="form-group">
                        <label class="form-label">Broadcast Name</label>
                        <input type="text" name="name" value="{{ $broadcast->name }} (Copy)" required class="form-input">
                    </div>

                    {{-- Number Option --}}
                    <div class="form-group">
                        <label class="form-label">Phone Numbers</label>
                        <div class="space-y-2">
                            <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="number_option" value="all" checked class="text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <p class="text-sm font-medium text-gray-800">All numbers <span class="text-gray-500">({{ number_format($broadcast->total_numbers) }})</span></p>
                                    <p class="text-xs text-gray-500">Clone all phone numbers, reset to pending</p>
                                </div>
                            </label>
                            @php $failedCount = $broadcast->total_numbers - $broadcast->answered_count; @endphp
                            <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="number_option" value="failed_only" class="text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <p class="text-sm font-medium text-gray-800">Failed/unanswered only <span class="text-gray-500">({{ number_format($failedCount) }})</span></p>
                                    <p class="text-xs text-gray-500">Skip already answered numbers</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Schedule --}}
                    <div class="form-group">
                        <label class="form-label">Schedule</label>
                        <div class="flex gap-3">
                            <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer px-4 py-2 rounded-lg border transition-colors" :class="scheduleType === 'now' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">
                                <input type="radio" name="schedule_type" value="now" x-model="scheduleType" class="sr-only">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                <span class="font-medium text-sm">Start Manually</span>
                            </label>
                            <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer px-4 py-2 rounded-lg border transition-colors" :class="scheduleType === 'scheduled' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'">
                                <input type="radio" name="schedule_type" value="scheduled" x-model="scheduleType" class="sr-only">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span class="font-medium text-sm">Schedule</span>
                            </label>
                        </div>
                        <div x-show="scheduleType === 'scheduled'" x-transition class="grid grid-cols-2 gap-3 mt-3">
                            <input type="date" name="scheduled_date" class="form-input" min="{{ now()->format('Y-m-d') }}">
                            <input type="time" name="scheduled_time" class="form-input">
                        </div>
                    </div>

                    {{-- Settings --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Max Concurrent</label>
                            <input type="number" name="max_concurrent" value="{{ $broadcast->max_concurrent }}" min="1" max="50" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ring Timeout (s)</label>
                            <input type="number" name="ring_timeout" value="{{ $broadcast->ring_timeout }}" min="10" max="120" class="form-input">
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-xl">
                    <button type="button" onclick="document.getElementById('cloneModal').classList.add('hidden')" class="btn-secondary text-sm">Cancel</button>
                    <div class="flex items-center gap-2">
                        <button type="submit" name="clone_action" value="draft" class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                            Save Draft
                        </button>
                        <template x-if="scheduleType === 'now'">
                            <button type="submit" name="clone_action" value="start" class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                                Clone & Start
                            </button>
                        </template>
                        <template x-if="scheduleType === 'scheduled'">
                            <button type="submit" name="clone_action" value="schedule" class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Clone & Schedule
                            </button>
                        </template>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
</x-admin-layout>
