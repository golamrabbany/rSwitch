<x-client-layout>
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
            <a href="{{ route('client.voice-files.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to List
            </a>
        </div>
    </div>

    {{-- Status Banner --}}
    @if($voiceFile->status === 'approved')
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 mb-6">
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
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 mb-6">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-sm font-medium text-amber-800">Waiting for admin approval. You will be notified once reviewed.</p>
                </div>
            </div>
        </div>
    @elseif($voiceFile->status === 'rejected')
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 mb-6">
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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Voice File Details --}}
        <div class="detail-card">
            <div class="detail-card-header">
                <h3 class="detail-card-title">Voice File Details</h3>
            </div>
            <div class="detail-card-body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Name</span>
                        <span class="detail-value">{{ $voiceFile->name }}</span>
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
                        <span class="detail-label">Uploaded</span>
                        <span class="detail-value">{{ $voiceFile->created_at->format('M d, Y H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Audio Preview --}}
        <div class="detail-card">
            <div class="detail-card-header">
                <h3 class="detail-card-title">Audio Preview</h3>
            </div>
            <div class="detail-card-body">
                @if($voiceFile->status === 'pending')
                    <div class="flex items-center gap-3 p-4 bg-amber-50 rounded-lg">
                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm text-amber-700">Audio preview is available while pending approval.</p>
                    </div>
                @endif
                <div class="mt-4">
                    <audio controls preload="none" class="w-full">
                        <source src="{{ route('client.voice-files.play', $voiceFile) }}" type="audio/{{ $voiceFile->format === 'mp3' ? 'mpeg' : 'wav' }}">
                        Your browser does not support the audio element.
                    </audio>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Action --}}
    @if(!($voiceFile->broadcasts_count ?? 0))
        <div class="mt-6 flex justify-end">
            <form method="POST" action="{{ route('client.voice-files.destroy', $voiceFile) }}" onsubmit="return confirm('Delete this voice file? This action cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete Voice File
                </button>
            </form>
        </div>
    @endif
</x-client-layout>
