<x-admin-layout>
    <x-slot name="header">Edit Webhook Endpoint</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Edit Webhook Endpoint</h2>
                <p class="page-subtitle break-all">{{ Str::limit($webhook->url, 50) }}</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.webhooks.show', $webhook) }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Details
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.webhooks.update', $webhook) }}">
        @csrf @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Endpoint Details</h3>
                        <p class="form-card-subtitle">Configure the webhook URL and user</p>
                    </div>
                    <div class="form-card-body">
                        <div class="form-group">
                            <label for="user_id" class="form-label">User</label>
                            <select id="user_id" name="user_id" required class="form-input">
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id', $webhook->user_id) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }}) — {{ ucfirst($user->role) }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                        </div>

                        <div class="form-group">
                            <label for="url" class="form-label">Webhook URL</label>
                            <input type="url" id="url" name="url" value="{{ old('url', $webhook->url) }}" required class="form-input">
                            <x-input-error :messages="$errors->get('url')" class="mt-2" />
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Description <span class="text-gray-400">(optional)</span></label>
                            <input type="text" id="description" name="description" value="{{ old('description', $webhook->description) }}" class="form-input">
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Events</h3>
                        <p class="form-card-subtitle">Select which events trigger this webhook</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-2 gap-3">
                            @foreach ($events as $key => $label)
                                <label class="webhook-event-option">
                                    <input type="checkbox" name="events[]" value="{{ $key }}"
                                           {{ in_array($key, old('events', $webhook->events)) ? 'checked' : '' }}
                                           class="webhook-event-checkbox">
                                    <span class="webhook-event-label">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('events')" class="mt-2" />
                    </div>
                </div>

                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Status</h3>
                    </div>
                    <div class="form-card-body">
                        <label class="flex items-center gap-3">
                            <input type="checkbox" name="active" value="1" {{ old('active', $webhook->active) ? 'checked' : '' }}
                                   class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700">Active - Enable this webhook endpoint</span>
                        </label>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.webhooks.show', $webhook) }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Update Endpoint
                    </button>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Endpoint Info</h3>
                    </div>
                    <div class="detail-card-body space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Created</span>
                            <span class="text-gray-900">{{ $webhook->created_at->format('M d, Y') }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Failures</span>
                            <span class="{{ $webhook->failure_count > 0 ? 'text-red-600 font-semibold' : 'text-gray-900' }}">{{ $webhook->failure_count }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Last Triggered</span>
                            <span class="text-gray-900">{{ $webhook->last_triggered_at?->diffForHumans() ?? 'Never' }}</span>
                        </div>
                    </div>
                </div>

                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Danger Zone</h3>
                    </div>
                    <div class="detail-card-body">
                        <form method="POST" action="{{ route('admin.webhooks.destroy', $webhook) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-danger w-full" onclick="return confirm('Delete this webhook endpoint?')">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Delete Endpoint
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
