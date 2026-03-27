<x-admin-layout>
    <x-slot name="header">Edit Voice Template</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Edit: {{ $voiceFile->name }}</h2>
            <p class="page-subtitle">Update voice template details</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.voice-files.show', $voiceFile) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Cancel
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('admin.voice-files.update', $voiceFile) }}" enctype="multipart/form-data"
                  x-data="{ replaceFile: false }">
                @csrf
                @method('PUT')

                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Template Details</h3>
                    </div>
                    <div class="form-card-body space-y-4">
                        <div class="form-group">
                            <label class="form-label">Client</label>
                            <input type="text" class="form-input bg-gray-50" value="{{ $voiceFile->user?->name ?? '-' }} ({{ $voiceFile->user?->email }})" disabled>
                        </div>

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
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-700">{{ $voiceFile->original_filename }}</p>
                                    <p class="text-xs text-gray-500">{{ strtoupper($voiceFile->format) }} &middot; {{ $voiceFile->duration ? $voiceFile->duration . 's' : 'unknown' }}</p>
                                </div>
                            </div>
                            <audio controls class="w-full mt-2" preload="none">
                                <source src="{{ route('admin.voice-files.play', $voiceFile) }}" type="audio/{{ $voiceFile->format === 'wav' ? 'wav' : 'mpeg' }}">
                            </audio>
                        </div>

                        {{-- Replace File --}}
                        <div class="form-group">
                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="replace_toggle" x-model="replaceFile" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <label for="replace_toggle" class="text-sm font-medium text-gray-700 cursor-pointer">Replace audio file</label>
                            </div>

                            <div x-show="replaceFile" x-transition class="mt-3">
                                <label class="flex items-center gap-2 px-3 py-2 border border-gray-300 rounded-lg cursor-pointer hover:border-indigo-400 hover:bg-indigo-50/50 transition-all">
                                    <svg class="w-5 h-5 text-indigo-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                    <span class="text-sm text-gray-500">Upload new audio file <span class="text-xs text-gray-400">(WAV/MP3, max 10MB)</span></span>
                                    <input type="file" name="voice_file" accept=".wav,.mp3" class="hidden">
                                </label>
                                <p class="form-hint mt-1">The old file will be replaced. Auto-converted to 8kHz WAV.</p>
                            </div>

                            @error('voice_file') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="submit" class="btn-action-primary-admin">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Update Template
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="space-y-4" style="position:sticky; top:1rem;">
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Current Status</h3></div>
                <div class="detail-card-body space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status</span>
                        <span class="font-medium {{ $voiceFile->status === 'approved' ? 'text-emerald-600' : ($voiceFile->status === 'pending' ? 'text-amber-600' : 'text-gray-500') }}">{{ ucfirst($voiceFile->status) }}</span>
                    </div>
                    <div class="flex justify-between"><span class="text-gray-500">Client</span><span class="font-medium">{{ $voiceFile->user?->name }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Uploaded</span><span class="font-medium">{{ $voiceFile->created_at->format('M d, Y') }}</span></div>
                    <p class="text-xs text-gray-400 pt-2">Editing will not change the approval status.</p>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
