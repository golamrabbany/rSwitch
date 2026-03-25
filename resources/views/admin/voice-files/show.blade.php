<x-admin-layout>
    <x-slot name="header">Voice File: {{ $voiceFile->name }}</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            <a href="{{ route('admin.voice-files.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to List
            </a>
            <a href="{{ route('admin.voice-files.download', $voiceFile) }}" class="btn-action-primary-admin">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Download
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
                        <source src="{{ route('admin.voice-files.play', $voiceFile) }}" type="audio/{{ $voiceFile->format === 'mp3' ? 'mpeg' : 'wav' }}">
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
                            <span class="detail-label">Uploaded By</span>
                            <span class="detail-value">
                                @if($voiceFile->user)
                                    <a href="{{ route('admin.users.show', $voiceFile->user) }}" class="text-indigo-600 hover:text-indigo-700">{{ $voiceFile->user->name }}</a>
                                @else
                                    <span class="text-gray-400">--</span>
                                @endif
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Uploaded At</span>
                            <span class="detail-value">{{ $voiceFile->created_at->format('M d, Y H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Approval Status Info --}}
            @if($voiceFile->status === 'approved')
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-emerald-800">Approved</p>
                            <p class="text-xs text-emerald-600 mt-0.5">
                                @if($voiceFile->approved_by_user)
                                    Approved by {{ $voiceFile->approved_by_user->name }}
                                @endif
                                @if($voiceFile->approved_at)
                                    on {{ $voiceFile->approved_at->format('M d, Y H:i') }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            @elseif($voiceFile->status === 'rejected')
                <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-red-800">Rejected</p>
                            @if($voiceFile->rejection_reason)
                                <p class="text-sm text-red-700 mt-1"><strong>Reason:</strong> {{ $voiceFile->rejection_reason }}</p>
                            @endif
                            <p class="text-xs text-red-600 mt-0.5">
                                @if($voiceFile->rejected_by_user)
                                    Rejected by {{ $voiceFile->rejected_by_user->name }}
                                @endif
                                @if($voiceFile->rejected_at)
                                    on {{ $voiceFile->rejected_at->format('M d, Y H:i') }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            @elseif($voiceFile->status === 'pending')
                {{-- Approve / Reject Actions --}}
                <div x-data="{ showReject: false }" class="space-y-4">
                    <div class="flex items-center gap-3">
                        <form method="POST" action="{{ route('admin.voice-files.approve', $voiceFile) }}">
                            @csrf
                            <button type="submit" class="btn-success">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Approve
                            </button>
                        </form>
                        <button type="button" @click="showReject = true" class="btn-danger">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Reject
                        </button>
                    </div>

                    {{-- Rejection Modal --}}
                    <div x-show="showReject" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                            {{-- Background overlay --}}
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showReject = false"></div>

                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                            <div class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                                <div class="sm:flex sm:items-start">
                                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                    </div>
                                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Reject Voice File</h3>
                                        <div class="mt-2">
                                            <p class="text-sm text-gray-500">Please provide a reason for rejecting this voice file. The client will see this reason.</p>
                                        </div>
                                        <form method="POST" action="{{ route('admin.voice-files.reject', $voiceFile) }}" class="mt-4">
                                            @csrf
                                            <textarea name="rejection_reason" rows="3" required
                                                class="form-input w-full"
                                                placeholder="Reason for rejection..."></textarea>
                                            <div class="mt-4 flex justify-end gap-3">
                                                <button type="button" @click="showReject = false" class="btn-secondary">Cancel</button>
                                                <button type="submit" class="btn-danger">Confirm Rejection</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
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
                                <a href="{{ route('admin.users.show', $voiceFile->user) }}" class="detail-value text-indigo-600 hover:text-indigo-700">
                                    {{ $voiceFile->user->name }}
                                </a>
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
</x-admin-layout>
