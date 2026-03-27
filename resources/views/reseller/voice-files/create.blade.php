<x-reseller-layout>
    <x-slot name="header">Upload Voice Template</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Upload Voice Template</h2>
            <p class="page-subtitle">Upload a voice file for broadcasting</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('reseller.voice-files.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back
            </a>
        </div>
    </div>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('reseller.voice-files.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-card">
                <div class="form-card-header">
                    <h3 class="form-card-title">Voice Template Details</h3>
                    <p class="form-card-subtitle">Upload a WAV or MP3 file (max 10MB)</p>
                </div>
                <div class="form-card-body space-y-4">
                    <div class="form-group">
                        <label for="name" class="form-label">Template Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required class="form-input" placeholder="e.g. Payment Reminder">
                        <p class="form-hint">A descriptive name for this voice template</p>
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="form-group">
                        <label class="form-label">Audio File</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-emerald-400 transition-colors">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                                </svg>
                                <div class="flex text-sm text-gray-600 justify-center">
                                    <label for="voice_file" class="relative cursor-pointer rounded-md font-medium text-emerald-600 hover:text-emerald-500">
                                        <span>Upload a file</span>
                                        <input id="voice_file" name="voice_file" type="file" accept=".wav,.mp3" required class="sr-only">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">WAV or MP3 up to 10MB</p>
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('voice_file')" class="mt-2" />
                    </div>

                    <div class="flex items-start gap-3 p-3 bg-amber-50 rounded-lg border border-amber-200">
                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div class="text-sm text-amber-800">
                            <p class="font-medium">Requires Admin Approval</p>
                            <p class="text-amber-600 mt-0.5">Your voice template will be submitted for review. You can use it in broadcasts once approved.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 mt-6">
                <a href="{{ route('reseller.voice-files.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary" style="background: #059669;">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Upload Template
                </button>
            </div>
        </form>
    </div>
</x-reseller-layout>
