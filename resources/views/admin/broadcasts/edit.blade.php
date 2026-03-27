<x-admin-layout>
    <x-slot name="header">Edit Broadcast</x-slot>

    <div class="page-header-row">
        <div>
            <h2 class="page-title">Edit: {{ $broadcast->name }}</h2>
            <p class="page-subtitle">Update broadcast settings before resuming</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.broadcasts.show', $broadcast) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Cancel
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <form method="POST" action="{{ route('admin.broadcasts.update', $broadcast) }}">
                @csrf
                @method('PUT')

                {{-- Broadcast Info (read-only) --}}
                <div class="form-card mb-6">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Broadcast Info</h3>
                        <p class="form-card-subtitle">These fields cannot be changed</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
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
                                <p class="text-xs text-gray-500 mb-1">Created</p>
                                <p class="text-sm font-medium text-gray-900">{{ $broadcast->created_at->format('d M Y, g:i A') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Editable Settings --}}
                <div class="form-card mb-6">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Editable Settings</h3>
                        <p class="form-card-subtitle">You can change these before resuming the broadcast</p>
                    </div>
                    <div class="form-card-body space-y-4">
                        {{-- Voice Template (read-only) --}}
                        @if($broadcast->voiceFile)
                            <div class="form-group">
                                <label class="form-label">Voice Template</label>
                                <input type="text" class="form-input bg-gray-50" value="{{ $broadcast->voiceFile->name }} ({{ strtoupper($broadcast->voiceFile->format) }}{{ $broadcast->voiceFile->duration ? ', ' . $broadcast->voiceFile->duration . 's' : '' }})" disabled>
                                <p class="form-hint">Template cannot be changed after creation</p>
                            </div>
                        @endif

                        {{-- SIP Account --}}
                        <div class="form-group">
                            <label class="form-label">SIP Account</label>
                            <select name="sip_account_id" class="form-input"
                                    onchange="var ch = this.options[this.selectedIndex].dataset.channels; if (ch) document.getElementById('max_concurrent').value = ch;">
                                @foreach($sipAccounts as $sip)
                                    <option value="{{ $sip->id }}" data-channels="{{ $sip->max_channels }}" {{ $broadcast->sip_account_id == $sip->id ? 'selected' : '' }}>
                                        {{ $sip->username }}{{ $sip->max_channels ? ' (' . $sip->max_channels . ' ch)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="form-hint">Active SIP accounts for this client</p>
                            @error('sip_account_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Concurrent + Timeout --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Max Concurrent Calls</label>
                                <input type="number" id="max_concurrent" name="max_concurrent" value="{{ old('max_concurrent', $broadcast->max_concurrent) }}" min="1" max="50" class="form-input">
                                <p class="form-hint">Auto-set from SIP channel limit. You can increase.</p>
                                @error('max_concurrent') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label">Ring Timeout (seconds)</label>
                                <input type="number" name="ring_timeout" value="{{ old('ring_timeout', $broadcast->ring_timeout) }}" min="10" max="120" class="form-input">
                                <p class="form-hint">How long to ring before giving up</p>
                                @error('ring_timeout') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
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

        {{-- Sidebar --}}
        <div class="space-y-4" style="position:sticky; top:1rem;">
            {{-- Client --}}
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Client</h3></div>
                <div class="detail-card-body">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-indigo-600">{{ strtoupper(substr($broadcast->user?->name ?? '?', 0, 2)) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $broadcast->user?->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $broadcast->user?->email }}</p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                        <span class="text-xs text-gray-500">Balance</span>
                        <span class="text-sm font-mono font-semibold {{ ($broadcast->user?->balance ?? 0) > 0 ? 'text-emerald-600' : 'text-red-500' }}">{{ currency_symbol() }}{{ number_format($broadcast->user?->balance ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Status --}}
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">Broadcast Status</h3></div>
                <div class="detail-card-body space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                            @if($broadcast->status === 'draft') bg-gray-100 text-gray-700
                            @elseif($broadcast->status === 'scheduled') bg-blue-100 text-blue-700
                            @elseif($broadcast->status === 'paused') bg-amber-100 text-amber-700
                            @else bg-gray-100 text-gray-700
                            @endif">{{ ucfirst($broadcast->status) }}</span>
                    </div>
                    <div class="flex justify-between"><span class="text-gray-500">Type</span><span class="font-medium text-gray-700">{{ ucfirst($broadcast->type) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Numbers</span><span class="font-medium text-gray-700">{{ number_format($broadcast->total_numbers) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Current Template</span><span class="font-medium text-gray-700">{{ $broadcast->voiceFile?->name ?? '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Current SIP</span><span class="font-medium text-gray-700">{{ $broadcast->sipAccount?->username ?? '—' }}</span></div>
                    @if($broadcast->scheduled_at)
                        <div class="flex justify-between"><span class="text-gray-500">Scheduled</span><span class="font-medium text-gray-700">{{ $broadcast->scheduled_at->format('d M Y, g:i A') }}</span></div>
                    @endif
                </div>
            </div>

            {{-- What You Can Change --}}
            <div class="detail-card">
                <div class="detail-card-header"><h3 class="detail-card-title">What You Can Change</h3></div>
                <div class="detail-card-body text-xs text-gray-500 space-y-2">
                    <div class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>SIP Account</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Max Concurrent Calls</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Ring Timeout</span>
                    </div>
                    <div class="border-t border-gray-100 pt-2 mt-2">
                        <div class="flex items-center gap-2 text-gray-400">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span>Name, template, type, numbers — fixed</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
