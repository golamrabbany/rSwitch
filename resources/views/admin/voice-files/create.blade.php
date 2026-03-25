<x-admin-layout>
    <x-slot name="header">Upload Voice File</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Upload Voice File</h2>
            <p class="page-subtitle">Upload a voice file on behalf of a client</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.voice-files.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="form-card">
                <div class="form-card-header">
                    <h3 class="form-card-title">Voice File Details</h3>
                    <p class="form-card-subtitle">Select client and upload audio file</p>
                </div>
                <div class="form-card-body">
                    <form method="POST" action="{{ route('admin.voice-files.store') }}" enctype="multipart/form-data" class="space-y-5">
                        @csrf

                        <div class="form-group">
                            <label class="form-label">Client</label>
                            <select name="user_id" required class="form-input">
                                <option value="">Select Client</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ old('user_id') == $client->id ? 'selected' : '' }}>{{ $client->name }} ({{ $client->email }})</option>
                                @endforeach
                            </select>
                            <p class="form-hint">The client who will own this voice file</p>
                            @error('user_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" required class="form-input" placeholder="e.g. Welcome Message">
                            <p class="form-hint">A descriptive name for this voice file</p>
                            @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-group" x-data="{ fileName: '' }">
                            <label class="form-label">Audio File</label>
                            <label class="flex items-center justify-center w-full px-4 py-6 border-2 border-dashed border-gray-200 rounded-lg cursor-pointer hover:border-indigo-300 hover:bg-indigo-50/30 transition-colors">
                                <div class="flex flex-col items-center gap-2 text-center">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                    <span class="text-sm text-gray-500" x-text="fileName || 'Click to upload — WAV or MP3 (max 10MB)'"></span>
                                </div>
                                <input type="file" name="voice_file" required accept=".wav,.mp3" class="hidden" @change="fileName = $event.target.files[0]?.name || ''">
                            </label>
                            <p class="form-hint">Accepted: WAV, MP3. Max 10MB. Recommended: under 5 minutes.</p>
                            @error('voice_file') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="submit" class="btn-action-primary-admin">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                Upload Voice File
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div>
                        <p class="text-sm text-blue-800 font-medium">Super Admin Upload</p>
                        <p class="text-sm text-blue-600 mt-1">Files uploaded by Super Admin are automatically approved. No review needed.</p>
                    </div>
                </div>
            </div>

            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Accepted Formats</h3>
                </div>
                <div class="detail-card-body text-sm text-gray-600 space-y-2">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>WAV (recommended)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>MP3</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Max 10MB file size</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Auto-converted to 8kHz mono WAV</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
