<x-admin-layout>
    <x-slot name="header">Edit Broadcast</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Edit: {{ $broadcast->name }}</h2>
            <p class="page-subtitle">Update broadcast settings</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.broadcasts.show', $broadcast) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Cancel
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('admin.broadcasts.update', $broadcast) }}">
                @csrf
                @method('PUT')

                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Broadcast Settings</h3>
                    </div>
                    <div class="form-card-body space-y-4">
                        <div class="form-group">
                            <label class="form-label">Client</label>
                            <input type="text" class="form-input bg-gray-50" value="{{ $broadcast->user?->name ?? '-' }}" disabled>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Broadcast Name</label>
                            <input type="text" name="name" value="{{ old('name', $broadcast->name) }}" required class="form-input">
                            @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Max Concurrent Calls</label>
                                <input type="number" name="max_concurrent" value="{{ old('max_concurrent', $broadcast->max_concurrent) }}" min="1" max="50" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Ring Timeout (seconds)</label>
                                <input type="number" name="ring_timeout" value="{{ old('ring_timeout', $broadcast->ring_timeout) }}" min="10" max="120" class="form-input">
                            </div>
                        </div>
                        <div class="flex justify-end pt-2">
                            <button type="submit" class="btn-action-primary-admin">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Update Broadcast
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
                    <div class="flex justify-between"><span class="text-gray-500">Status</span><span class="font-medium">{{ ucfirst($broadcast->status) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Type</span><span class="font-medium">{{ ucfirst($broadcast->type) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Numbers</span><span class="font-medium">{{ number_format($broadcast->total_numbers) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Created</span><span class="font-medium">{{ $broadcast->created_at->format('M d, Y') }}</span></div>
                    @if($broadcast->scheduled_at)
                        <div class="flex justify-between"><span class="text-gray-500">Scheduled</span><span class="font-medium">{{ $broadcast->scheduled_at->format('M d, Y g:i A') }}</span></div>
                    @endif
                    <p class="text-xs text-gray-400 pt-2">Only name and call settings can be edited.</p>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
