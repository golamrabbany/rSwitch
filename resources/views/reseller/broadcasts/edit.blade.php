<x-reseller-layout>
    <x-slot name="header">Edit Broadcast</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Edit Broadcast</h2>
            <p class="page-subtitle">{{ $broadcast->name }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('reseller.broadcasts.show', $broadcast) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back
            </a>
        </div>
    </div>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('reseller.broadcasts.update', $broadcast) }}">
            @csrf
            @method('PUT')

            <div class="form-card">
                <div class="form-card-header">
                    <h3 class="form-card-title">Broadcast Settings</h3>
                    <p class="form-card-subtitle">Edit broadcast before starting</p>
                </div>
                <div class="form-card-body space-y-4">
                    <div class="form-group">
                        <label for="name" class="form-label">Broadcast Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $broadcast->name) }}" required class="form-input">
                        <p class="form-hint">Descriptive name for this broadcast campaign</p>
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="form-group">
                        <label for="max_concurrent" class="form-label">Max Concurrent Calls</label>
                        <input type="number" id="max_concurrent" name="max_concurrent" value="{{ old('max_concurrent', $broadcast->max_concurrent) }}" min="1" max="50" class="form-input">
                        <p class="form-hint">Maximum simultaneous calls (1-50)</p>
                        <x-input-error :messages="$errors->get('max_concurrent')" class="mt-2" />
                    </div>

                    <div class="form-group">
                        <label for="ring_timeout" class="form-label">Ring Timeout (seconds)</label>
                        <input type="number" id="ring_timeout" name="ring_timeout" value="{{ old('ring_timeout', $broadcast->ring_timeout) }}" min="10" max="120" class="form-input">
                        <p class="form-hint">How long to ring before giving up (10-120s)</p>
                        <x-input-error :messages="$errors->get('ring_timeout')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 mt-6">
                <a href="{{ route('reseller.broadcasts.show', $broadcast) }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary" style="background: #059669;">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Update Broadcast
                </button>
            </div>
        </form>
    </div>
</x-reseller-layout>
