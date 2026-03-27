<x-admin-layout>
    <x-slot name="header">Voice Template: {{ $voiceFile->name }}</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $voiceFile->status === 'approved' ? 'bg-emerald-100' : ($voiceFile->status === 'pending' ? 'bg-amber-100' : ($voiceFile->status === 'rejected' ? 'bg-red-100' : 'bg-gray-100')) }}">
                <svg class="w-5 h-5 {{ $voiceFile->status === 'approved' ? 'text-emerald-600' : ($voiceFile->status === 'pending' ? 'text-amber-600' : ($voiceFile->status === 'rejected' ? 'text-red-600' : 'text-gray-500')) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
            </div>
            <div>
                <h2 class="page-title">{{ $voiceFile->name }}</h2>
                <div class="flex items-center gap-2 mt-0.5">
                    @switch($voiceFile->status)
                        @case('approved') <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Approved</span> @break
                        @case('pending') <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Pending</span> @break
                        @case('rejected') <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Rejected</span> @break
                        @case('suspended') <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Suspended</span> @break
                    @endswitch
                    <span class="text-xs text-gray-400">&middot;</span>
                    <span class="text-xs text-gray-500">{{ $voiceFile->user?->name }}</span>
                </div>
            </div>
        </div>
        <div class="page-actions">
            @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.voice-files.edit', $voiceFile) }}" class="btn-action-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit
                </a>
                @if($voiceFile->status !== 'approved')
                    <form method="POST" action="{{ route('admin.voice-files.approve', $voiceFile) }}" class="inline">@csrf
                        <button type="submit" class="btn-action-primary-admin bg-emerald-600 hover:bg-emerald-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Approve
                        </button>
                    </form>
                @endif
                @if($voiceFile->status !== 'rejected')
                    <button type="button" onclick="document.getElementById('rejectModal').classList.remove('hidden')" class="btn-action-secondary text-red-600 border-red-300 hover:bg-red-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Reject
                    </button>
                @endif
                @if($voiceFile->status === 'approved')
                    <form method="POST" action="{{ route('admin.voice-files.suspend', $voiceFile) }}" class="inline">@csrf
                        <button type="submit" class="btn-action-secondary text-amber-600 border-amber-300 hover:bg-amber-50">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Suspend
                        </button>
                    </form>
                @endif
                @if(in_array($voiceFile->status, ['rejected', 'suspended']))
                    <form method="POST" action="{{ route('admin.voice-files.set-pending', $voiceFile) }}" class="inline">@csrf
                        <button type="submit" class="btn-action-secondary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Set Pending
                        </button>
                    </form>
                @endif
            @endif
            <a href="{{ route('admin.voice-files.download', $voiceFile) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Download
            </a>
            @if(auth()->user()->isSuperAdmin() && !$voiceFile->broadcasts()->exists())
                <form method="POST" action="{{ route('admin.voice-files.destroy', $voiceFile) }}" class="inline" onsubmit="return confirm('Delete this voice template? This cannot be undone.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-action-secondary text-red-600 border-red-300 hover:bg-red-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Delete
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.voice-files.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back
            </a>
        </div>
    </div>

    {{-- Rejection Banner --}}
    @if($voiceFile->rejection_reason)
        <div class="flex items-start gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-lg mb-6">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div>
                <p class="text-sm font-semibold text-red-700">Rejected</p>
                <p class="text-sm text-red-600 mt-0.5">{{ $voiceFile->rejection_reason }}</p>
            </div>
        </div>
    @endif

    {{-- Reject Modal --}}
    @if(auth()->user()->isSuperAdmin() && $voiceFile->status !== 'rejected')
        <div id="rejectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Reject Voice Template</h3>
                <form method="POST" action="{{ route('admin.voice-files.reject', $voiceFile) }}">
                    @csrf
                    <textarea name="rejection_reason" class="form-input w-full" rows="3" placeholder="Reason for rejection..." required></textarea>
                    <div class="flex justify-end gap-3 mt-4">
                        <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="btn-action-secondary">Cancel</button>
                        <button type="submit" class="btn-action-primary-admin bg-red-600 hover:bg-red-700">Reject</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

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
