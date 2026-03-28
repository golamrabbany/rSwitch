<x-reseller-layout>
    <x-slot name="header">Edit Broadcast</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Edit: {{ $broadcast->name }}</h2>
            <p class="page-subtitle">Update broadcast settings</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('reseller.broadcasts.show', $broadcast) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Cancel
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <form method="POST" action="{{ route('reseller.broadcasts.update', $broadcast) }}">
                @csrf
                @method('PUT')

                {{-- Broadcast Info (read-only) --}}
                <div class="form-card mb-6">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Broadcast Info</h3>
                        <p class="form-card-subtitle">These fields cannot be changed</p>
                    </div>
                    <div class="form-card-body">
                        <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:1rem;">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Broadcast Name</p>
                                <p class="text-sm font-medium text-gray-900">{{ $broadcast->name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Type</p>
                                <p class="text-sm font-medium text-gray-900">{{ ucfirst($broadcast->type) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Total Numbers</p>
                                <p class="text-sm font-medium text-gray-900">{{ number_format($broadcast->total_numbers) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Voice Template</p>
                                <p class="text-sm font-medium text-gray-900">{{ $broadcast->voiceFile?->name ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Editable Settings --}}
                <div class="form-card mb-6">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Editable Settings</h3>
                        <p class="form-card-subtitle">You can change these before starting</p>
                    </div>
                    <div class="form-card-body space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Max Concurrent Calls</label>
                                <input type="number" name="max_concurrent" value="{{ old('max_concurrent', $broadcast->max_concurrent) }}" min="1" max="50" class="form-input">
                                <p class="form-hint">Maximum simultaneous calls</p>
                                <x-input-error :messages="$errors->get('max_concurrent')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Ring Timeout (seconds)</label>
                                <input type="number" name="ring_timeout" value="{{ old('ring_timeout', $broadcast->ring_timeout) }}" min="10" max="120" class="form-input">
                                <p class="form-hint">How long to ring before giving up</p>
                                <x-input-error :messages="$errors->get('ring_timeout')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('reseller.broadcasts.show', $broadcast) }}" class="btn-secondary">Cancel</a>
                    <button type="submit" name="edit_action" value="save" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        Save Draft
                    </button>
                    <button type="submit" name="edit_action" value="start" class="btn-primary" style="background: #059669;">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                        Update & Start
                    </button>
                </div>
            </form>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-4" style="position:sticky; top:1rem;">
            {{-- Client --}}
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Client</h3></div>
                <div class="detail-card-body">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-emerald-600">{{ strtoupper(substr($broadcast->user?->name ?? '?', 0, 2)) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $broadcast->user?->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $broadcast->user?->email }}</p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                        <span class="text-xs text-gray-500">Balance</span>
                        <span class="text-sm font-mono font-semibold {{ ($broadcast->user?->balance ?? 0) > 0 ? 'text-emerald-600' : 'text-red-500' }}">{{ format_currency($broadcast->user?->balance ?? 0) }}</span>
                    </div>
                </div>
            </div>

            {{-- Broadcast Status --}}
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Broadcast Status</h3></div>
                <div class="detail-card-body space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                            @if($broadcast->status === 'draft') bg-gray-100 text-gray-700
                            @elseif($broadcast->status === 'scheduled') bg-blue-100 text-blue-700
                            @elseif($broadcast->status === 'paused') bg-amber-100 text-amber-700
                            @endif">{{ ucfirst($broadcast->status) }}</span>
                    </div>
                    <div class="flex justify-between"><span class="text-gray-500">SIP Account</span><span class="font-medium text-gray-700">{{ $broadcast->sipAccount?->username ?? '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Template</span><span class="font-medium text-gray-700">{{ $broadcast->voiceFile?->name ?? '—' }}</span></div>
                    @if($broadcast->scheduled_at)
                        <div class="flex justify-between"><span class="text-gray-500">Scheduled</span><span class="font-medium text-gray-700">{{ $broadcast->scheduled_at->format('d M Y, g:i A') }}</span></div>
                    @endif
                </div>
            </div>

            {{-- What You Can Change --}}
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">What You Can Change</h3></div>
                <div class="detail-card-body text-xs space-y-2">
                    <div class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span class="text-gray-600">Max Concurrent Calls</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span class="text-gray-600">Ring Timeout</span>
                    </div>
                    <div class="border-t border-gray-100 pt-2 mt-2">
                        <div class="flex items-center gap-2 text-gray-400">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span>Name, template, numbers — fixed</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-reseller-layout>
