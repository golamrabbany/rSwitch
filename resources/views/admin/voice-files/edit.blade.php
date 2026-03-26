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
            <form method="POST" action="{{ route('admin.voice-files.update', $voiceFile) }}">
                @csrf
                @method('PUT')

                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Template Details</h3>
                    </div>
                    <div class="form-card-body space-y-4">
                        <div class="form-group">
                            <label class="form-label">Client</label>
                            <input type="text" class="form-input bg-gray-50" value="{{ $voiceFile->user?->name ?? '-' }}" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Template Name</label>
                            <input type="text" name="name" value="{{ old('name', $voiceFile->name) }}" required class="form-input">
                            @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Original File</label>
                            <input type="text" class="form-input bg-gray-50" value="{{ $voiceFile->original_filename }} — {{ strtoupper($voiceFile->format) }}, {{ $voiceFile->duration ? floor($voiceFile->duration / 60).'m '.$voiceFile->duration % 60 .'s' : 'unknown duration' }}" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Audio Preview</label>
                            <audio controls class="w-full" preload="none">
                                <source src="{{ route('admin.voice-files.play', $voiceFile) }}" type="audio/{{ $voiceFile->format === 'wav' ? 'wav' : 'mpeg' }}">
                            </audio>
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
                    <div class="flex justify-between"><span class="text-gray-500">Status</span>
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
