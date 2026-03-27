<x-reseller-layout>
    <x-slot name="header">Edit Voice Template</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Edit: {{ $voiceFile->name }}</h2>
            <p class="page-subtitle">Update voice template details</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('reseller.voice-files.show', $voiceFile) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Cancel
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('reseller.voice-files.update', $voiceFile) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Template Details</h3>
                    </div>
                    <div class="form-card-body space-y-4">
                        <div class="form-group">
                            <label class="form-label">Template Name</label>
                            <input type="text" name="name" value="{{ old('name', $voiceFile->name) }}" required class="form-input" placeholder="e.g. Welcome Message, Payment Reminder">
                            @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Current Audio --}}
                        <div class="form-group">
                            <label class="form-label">Current Audio</label>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                                <div>
                                    <p class="text-sm font-medium text-gray-700">{{ $voiceFile->original_filename }}</p>
                                    <p class="text-xs text-gray-500">{{ strtoupper($voiceFile->format) }} &middot; {{ $voiceFile->duration ? $voiceFile->duration . 's' : 'unknown' }}</p>
                                </div>
                            </div>
                            <audio controls class="w-full mt-2" preload="none">
                                <source src="{{ route('reseller.voice-files.play', $voiceFile) }}" type="audio/{{ $voiceFile->format === 'wav' ? 'wav' : 'mpeg' }}">
                            </audio>
                        </div>

                        {{-- Replace File --}}
                        <div class="form-group">
                            <label class="form-label">Replace Audio (optional)</label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-emerald-400 transition-colors">
                                <div class="space-y-1 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    <div class="flex text-sm text-gray-600 justify-center">
                                        <label for="voice_file" class="relative cursor-pointer rounded-md font-medium text-emerald-600 hover:text-emerald-500">
                                            <span>Upload a new file</span>
                                            <input id="voice_file" name="voice_file" type="file" accept=".wav,.mp3" class="sr-only">
                                        </label>
                                        <p class="pl-1">to replace current</p>
                                    </div>
                                    <p class="text-xs text-gray-500">WAV or MP3 up to 10MB</p>
                                </div>
                            </div>
                            @error('voice_file') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="submit" class="btn-primary" style="background: #059669;">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Update Template
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="space-y-4" style="position:sticky; top:1rem;">
            {{-- Client Info --}}
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Client</h3></div>
                <div class="detail-card-body">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-emerald-600">{{ strtoupper(substr($voiceFile->user?->name ?? '?', 0, 2)) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $voiceFile->user?->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $voiceFile->user?->email }}</p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                        <span class="text-xs text-gray-500">Balance</span>
                        <span class="text-sm font-mono font-semibold {{ ($voiceFile->user?->balance ?? 0) > 0 ? 'text-emerald-600' : 'text-red-500' }}">{{ currency_symbol() }}{{ number_format($voiceFile->user?->balance ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Status --}}
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Current Status</h3></div>
                <div class="detail-card-body space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status</span>
                        <span class="font-medium text-amber-600">Pending</span>
                    </div>
                    <div class="flex justify-between"><span class="text-gray-500">Format</span><span class="font-medium text-gray-700">{{ strtoupper($voiceFile->format) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Duration</span><span class="font-medium text-gray-700">{{ $voiceFile->duration ? $voiceFile->duration . 's' : 'Unknown' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Uploaded</span><span class="font-medium text-gray-700">{{ $voiceFile->created_at->format('M d, Y') }}</span></div>
                </div>
            </div>

            {{-- File Requirements --}}
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">File Requirements</h3></div>
                <div class="detail-card-body text-sm text-gray-500 space-y-2">
                    <div class="flex items-center justify-between"><span>Formats</span><span class="font-medium text-gray-700">WAV, MP3</span></div>
                    <div class="flex items-center justify-between"><span>Max size</span><span class="font-medium text-gray-700">10MB per file</span></div>
                    <div class="flex items-center justify-between"><span>Conversion</span><span class="font-medium text-gray-700">Auto 8kHz WAV</span></div>
                </div>
            </div>
        </div>
    </div>
</x-reseller-layout>
