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

    <form method="POST" action="{{ route('reseller.broadcasts.update', $broadcast) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form --}}
            <div class="lg:col-span-2 space-y-6">
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

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('reseller.broadcasts.show', $broadcast) }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary" style="background: #059669;">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Update Broadcast
                    </button>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Current Status --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Current Status</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="text-gray-500">Status</span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                    @if($broadcast->status === 'draft') bg-gray-100 text-gray-700
                                    @elseif($broadcast->status === 'scheduled') bg-blue-100 text-blue-700
                                    @endif">{{ ucfirst($broadcast->status) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="text-gray-500">Type</span>
                                <span class="text-gray-900 font-medium">{{ ucfirst($broadcast->type) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="text-gray-500">Numbers</span>
                                <span class="text-gray-900 font-medium">{{ number_format($broadcast->broadcast_numbers_count ?? $broadcast->broadcastNumbers()->count()) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-1">
                                <span class="text-gray-500">Created</span>
                                <span class="text-gray-900">{{ $broadcast->created_at->format('d M Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tips --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Tips</h3>
                    </div>
                    <div class="detail-card-body">
                        <ul class="text-xs text-gray-600 space-y-2">
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Higher concurrency = faster completion but more trunk load</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>30s ring timeout works well for most campaigns</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>You can only edit draft or scheduled broadcasts</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-reseller-layout>
