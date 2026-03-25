<x-reseller-layout>
    <x-slot name="header">Voice File: {{ $voiceFile->name }}</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-emerald-100 flex items-center justify-center">
                <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">{{ $voiceFile->name }}</h2>
                <div class="flex items-center gap-2 mt-1">
                    @switch($voiceFile->status)
                        @case('approved')
                            <span class="badge badge-success">Approved</span>
                            @break
                        @case('pending')
                            <span class="badge badge-warning">Pending</span>
                            @break
                        @case('rejected')
                            <span class="badge badge-danger">Rejected</span>
                            @break
                    @endswitch
                </div>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('reseller.voice-files.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to List
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content - Left Side --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Audio Preview --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Audio Preview</h3>
                </div>
                <div class="detail-card-body">
                    <audio controls class="w-full">
                        <source src="{{ route('reseller.voice-files.play', $voiceFile) }}" type="audio/{{ $voiceFile->format === 'mp3' ? 'mpeg' : 'wav' }}">
                        Your browser does not support the audio element.
                    </audio>
                    <div class="flex items-center gap-4 mt-3 text-sm text-gray-500">
                        @if($voiceFile->duration)
                            <span>Duration: {{ floor($voiceFile->duration / 60) }}m {{ $voiceFile->duration % 60 }}s</span>
                        @endif
                        <span>Format: {{ strtoupper($voiceFile->format) }}</span>
                    </div>
                </div>
            </div>

            {{-- File Details --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">File Details</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Name</span>
                            <span class="detail-value">{{ $voiceFile->name }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Original Filename</span>
                            <span class="detail-value font-mono">{{ $voiceFile->original_filename ?? '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Format</span>
                            <span class="detail-value">
                                <span class="badge badge-gray">{{ strtoupper($voiceFile->format) }}</span>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Duration</span>
                            <span class="detail-value">
                                @if($voiceFile->duration)
                                    {{ floor($voiceFile->duration / 60) }}m {{ $voiceFile->duration % 60 }}s
                                @else
                                    <span class="text-gray-400">--</span>
                                @endif
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">File Size</span>
                            <span class="detail-value">
                                @if($voiceFile->file_size)
                                    {{ number_format($voiceFile->file_size / 1024, 1) }} KB
                                @else
                                    <span class="text-gray-400">--</span>
                                @endif
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Status</span>
                            <span class="detail-value">
                                @switch($voiceFile->status)
                                    @case('approved')
                                        <span class="badge badge-success">Approved</span>
                                        @break
                                    @case('pending')
                                        <span class="badge badge-warning">Pending</span>
                                        @break
                                    @case('rejected')
                                        <span class="badge badge-danger">Rejected</span>
                                        @break
                                @endswitch
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Uploaded At</span>
                            <span class="detail-value">{{ $voiceFile->created_at->format('M d, Y H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Status Info --}}
            @if($voiceFile->status === 'approved')
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-emerald-800">This voice file has been approved and is ready for broadcast.</p>
                            @if($voiceFile->approved_at)
                                <p class="text-xs text-emerald-600 mt-0.5">Approved {{ $voiceFile->approved_at->format('M d, Y H:i') }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @elseif($voiceFile->status === 'pending')
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm font-medium text-amber-800">This voice file is pending admin approval.</p>
                    </div>
                </div>
            @elseif($voiceFile->status === 'rejected')
                <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-red-800">This voice file has been rejected.</p>
                            @if($voiceFile->rejection_reason)
                                <p class="text-sm text-red-700 mt-1"><strong>Reason:</strong> {{ $voiceFile->rejection_reason }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar - Right Side --}}
        <div class="space-y-6">
            {{-- Client Info --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Client Info</h3>
                </div>
                <div class="detail-card-body">
                    @if($voiceFile->user)
                        <div class="space-y-3">
                            <div class="detail-item">
                                <span class="detail-label">Name</span>
                                <span class="detail-value">{{ $voiceFile->user->name }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Email</span>
                                <span class="detail-value">{{ $voiceFile->user->email }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Balance</span>
                                <span class="detail-value font-semibold">{{ format_currency($voiceFile->user->balance) }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">KYC Status</span>
                                <span class="detail-value">
                                    @switch($voiceFile->user->kyc_status)
                                        @case('approved')
                                            <span class="badge badge-success">Approved</span>
                                            @break
                                        @case('pending')
                                            <span class="badge badge-warning">Pending</span>
                                            @break
                                        @case('rejected')
                                            <span class="badge badge-danger">Rejected</span>
                                            @break
                                        @default
                                            <span class="badge badge-gray">Not Submitted</span>
                                    @endswitch
                                </span>
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-gray-400">Client information unavailable</p>
                    @endif
                </div>
            </div>

            {{-- Usage --}}
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Usage</h3>
                </div>
                <div class="detail-card-body">
                    <div class="detail-item">
                        <span class="detail-label">Broadcasts</span>
                        <span class="detail-value font-semibold">{{ $broadcastsCount ?? 0 }}</span>
                    </div>
                    @if(($broadcastsCount ?? 0) === 0)
                        <p class="text-xs text-gray-400 mt-2">This voice file has not been used in any broadcasts yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-reseller-layout>
