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
                </div>
            </div>
        </div>
        <div class="page-actions">
            @if(auth()->user()->isSuperAdmin())
                {{-- Edit --}}
                <a href="{{ route('admin.broadcasts.edit', $broadcast) }}" class="btn-action-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit
                </a>

                {{-- Pause/Suspend (if running) --}}
                @if($broadcast->status === 'running')
                    <form method="POST" action="{{ route('admin.broadcasts.suspend', $broadcast) }}" class="inline">@csrf
                        <button type="submit" class="btn-action-secondary text-amber-600 border-amber-300 hover:bg-amber-50">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Pause
                        </button>
                    </form>
                @endif

                {{-- Cancel (if not already completed/cancelled/failed) --}}
                @if(!in_array($broadcast->status, ['completed', 'cancelled', 'failed']))
                    <form method="POST" action="{{ route('admin.broadcasts.cancel', $broadcast) }}" class="inline" onsubmit="return confirm('Cancel this broadcast?')">@csrf
                        <button type="submit" class="btn-action-secondary text-red-600 border-red-300 hover:bg-red-50">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Cancel
                        </button>
                    </form>
                @endif
            @endif

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

    {{-- Stat Cards + Progress with live polling --}}
    <div x-data="broadcastStats()" x-init="init()">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="stat-card">
                <div class="stat-icon bg-indigo-100">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <p class="stat-value" x-text="fmt(total)">{{ number_format($broadcast->total_numbers) }}</p>
                    <p class="stat-label">Total Numbers</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-blue-100">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <p class="stat-value" x-text="fmt(answered + failed)">{{ number_format($broadcast->dialed_count) }}</p>
                    <p class="stat-label">Dialed</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-emerald-100">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <p class="stat-value" x-text="fmt(answered)">{{ number_format($broadcast->answered_count) }}</p>
                    <p class="stat-label">Answered</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-red-100">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <p class="stat-value" x-text="fmt(failed)">{{ number_format($broadcast->failed_count) }}</p>
                    <p class="stat-label">Failed</p>
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
                <div class="bg-indigo-500 h-3 rounded-full transition-all duration-500" :style="'width: ' + progress + '%'"></div>
            </div>
            <div class="flex items-center justify-between mt-2 text-xs text-gray-500">
                <span x-text="fmt(answered + failed) + ' dialed of ' + fmt(total)">{{ number_format($broadcast->dialed_count) }} dialed of {{ number_format($broadcast->total_numbers) }}</span>
                <span x-text="fmt(answered) + ' answered'">{{ number_format($broadcast->answered_count) }} answered</span>
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
            <form method="POST" action="{{ route('admin.broadcasts.start', $broadcast) }}">
                @csrf
                <button type="submit" class="btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Start Broadcast
                </button>
            </form>
        @endif

        @if($broadcast->status === 'running')
            <form method="POST" action="{{ route('admin.broadcasts.pause', $broadcast) }}">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Pause
                </button>
            </form>
        @endif

        @if($broadcast->status === 'paused')
            <form method="POST" action="{{ route('admin.broadcasts.resume', $broadcast) }}">
                @csrf
                <button type="submit" class="btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Resume
                </button>
            </form>
        @endif

        @if(in_array($broadcast->status, ['draft', 'scheduled', 'queued', 'running', 'paused']))
            <form method="POST" action="{{ route('admin.broadcasts.cancel', $broadcast) }}" onsubmit="return confirm('Are you sure you want to cancel this broadcast?')">
                @csrf
                <button type="submit" class="btn-danger">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Cancel Broadcast
                </button>
            </form>
        @endif

        @if(in_array($broadcast->status, ['running', 'completed', 'paused']))
            <a href="{{ route('admin.broadcasts.results', $broadcast) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                View Results
            </a>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Broadcast Details --}}
        <div class="lg:col-span-2">
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Broadcast Details</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Type</span>
                            <span class="detail-value">
                                @if($broadcast->type === 'survey')
                                    <span class="badge badge-purple">Survey</span>
                                @else
                                    <span class="badge badge-blue">Simple</span>
                                @endif
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Voice File</span>
                            <span class="detail-value">{{ $broadcast->voiceFile->name ?? '--' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">SIP Account</span>
                            <span class="detail-value">{{ $broadcast->sipAccount->username ?? '--' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Caller ID</span>
                            <span class="detail-value">{{ $broadcast->sipAccount->caller_id_number ?? '--' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Max Concurrent</span>
                            <span class="detail-value">{{ $broadcast->max_concurrent }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Ring Timeout</span>
                            <span class="detail-value">{{ $broadcast->ring_timeout }}s</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Total Cost</span>
                            <span class="detail-value">{{ format_currency($broadcast->total_cost ?? 0) }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Created</span>
                            <span class="detail-value">{{ $broadcast->created_at->format('M d, Y H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Client Info Sidebar --}}
        <div class="space-y-6">
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Client Information</h3>
                </div>
                <div class="detail-card-body">
                    @if($broadcast->user)
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                <span class="text-sm font-bold text-indigo-600">{{ strtoupper(substr($broadcast->user->name, 0, 1)) }}</span>
                            </div>
                            <div>
                                <a href="{{ route('admin.users.show', $broadcast->user) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">
                                    {{ $broadcast->user->name }}
                                </a>
                                <div class="text-xs text-gray-500">{{ $broadcast->user->email }}</div>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Balance</span>
                                <span class="font-medium text-gray-900">{{ format_currency($broadcast->user->balance ?? 0) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Role</span>
                                <span class="font-medium text-gray-900">{{ ucfirst($broadcast->user->role) }}</span>
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-gray-400">Client information unavailable</p>
                    @endif
                </div>
            </div>

            @if($broadcast->created_by_user)
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Created By</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                                <span class="text-sm font-bold text-gray-600">{{ strtoupper(substr($broadcast->created_by_user->name, 0, 1)) }}</span>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $broadcast->created_by_user->name }}</div>
                                <div class="text-xs text-gray-500">{{ $broadcast->created_at->format('M d, Y H:i') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
