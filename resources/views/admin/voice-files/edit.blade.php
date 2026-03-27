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
            <form method="POST" action="{{ route('admin.voice-files.update', $voiceFile) }}"
                  x-data="{
                      newVoiceFileId: '',
                      newFileName: '',
                      newFileDuration: '',
                      uploading: false,
                      uploadError: '',
                      replaced: false
                  }">
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
                            <input type="text" name="name" value="{{ old('name', $voiceFile->name) }}" required class="form-input" placeholder="e.g. Welcome Message, Payment Reminder" x-ref="nameInput">
                            @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Audio File Section --}}
                        <div class="form-group">
                            <label class="form-label">Audio File</label>
                            <input type="hidden" name="new_voice_file_id" :value="newVoiceFileId">

                            {{-- Current file (shown when not replaced) --}}
                            <div x-show="!replaced && !uploading">
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <div class="flex items-center gap-3">
                                        <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                                        <div>
                                            <p class="text-sm font-medium text-gray-700">{{ $voiceFile->original_filename }}</p>
                                            <p class="text-xs text-gray-500">{{ strtoupper($voiceFile->format) }} &middot; {{ $voiceFile->duration ? $voiceFile->duration . 's' : 'unknown' }}</p>
                                        </div>
                                    </div>
                                </div>
                                <audio controls class="w-full mt-2" preload="none">
                                    <source src="{{ route('admin.voice-files.play', $voiceFile) }}" type="audio/{{ $voiceFile->format === 'wav' ? 'wav' : 'mpeg' }}">
                                </audio>

                                {{-- Replace button --}}
                                <div class="mt-3">
                                    <label class="flex items-center gap-2 px-3 py-2 border border-gray-300 rounded-lg cursor-pointer hover:border-indigo-400 hover:bg-indigo-50/50 transition-all">
                                        <svg class="w-5 h-5 text-indigo-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        <span class="text-sm text-gray-600">Replace with new file <span class="text-xs text-gray-400">(WAV/MP3, max 10MB)</span></span>
                                        <input type="file" accept=".wav,.mp3" class="hidden"
                                               @change="
                                                   let file = $event.target.files[0];
                                                   if (!file) return;
                                                   uploading = true;
                                                   uploadError = '';
                                                   let fd = new FormData();
                                                   fd.append('voice_file', file);
                                                   fd.append('label', $refs.nameInput?.value || 'Voice Template');
                                                   fd.append('_token', '{{ csrf_token() }}');
                                                   fetch('{{ route('admin.survey-templates.upload-voice-file') }}', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                                                   .then(r => { if (!r.ok) throw new Error('Upload failed'); return r.json(); })
                                                   .then(data => { newVoiceFileId = data.id; newFileName = data.name; newFileDuration = data.duration; uploading = false; replaced = true; })
                                                   .catch(e => { uploadError = 'Upload failed. Check file format and size.'; uploading = false; });
                                               ">
                                    </label>
                                </div>
                            </div>

                            {{-- Uploading state --}}
                            <div x-show="uploading" x-cloak class="flex items-center gap-2 px-3 py-3 bg-indigo-50 border border-indigo-200 rounded-lg">
                                <svg class="w-4 h-4 text-indigo-500 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <span class="text-sm text-indigo-600">Uploading & converting...</span>
                            </div>

                            {{-- New file uploaded --}}
                            <div x-show="replaced" x-cloak>
                                <div class="flex items-center justify-between px-3 py-3 bg-emerald-50 border border-emerald-200 rounded-lg">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span class="text-sm font-medium text-emerald-700" x-text="newFileName || 'New file uploaded'"></span>
                                        <span class="text-xs text-emerald-500" x-text="newFileDuration ? '(' + newFileDuration + 's)' : ''"></span>
                                    </div>
                                    <button type="button" @click="newVoiceFileId = ''; newFileName = ''; newFileDuration = ''; replaced = false;" class="text-xs text-red-500 hover:text-red-700 font-medium">Undo</button>
                                </div>
                                <p class="form-hint mt-1">New file will replace the current one when you save.</p>
                            </div>

                            <p x-show="uploadError" x-cloak class="text-xs text-red-500 mt-1" x-text="uploadError"></p>
                            @error('new_voice_file_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
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
