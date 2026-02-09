<x-admin-layout>
    <x-slot name="header">Create Webhook Endpoint</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Create Webhook Endpoint</h2>
                <p class="page-subtitle">Configure a new webhook integration</p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.webhooks.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Webhooks
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.webhooks.store') }}">
        @csrf

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
                                <option value="">Select user...</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }}) — {{ ucfirst($user->role) }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                        </div>

                        <div class="form-group">
                            <label for="url" class="form-label">Webhook URL</label>
                            <input type="url" id="url" name="url" value="{{ old('url') }}" required
                                   placeholder="https://example.com/webhook" class="form-input">
                            <x-input-error :messages="$errors->get('url')" class="mt-2" />
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Description <span class="text-gray-400">(optional)</span></label>
                            <input type="text" id="description" name="description" value="{{ old('description') }}"
                                   placeholder="Optional description..." class="form-input">
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
                                           {{ in_array($key, old('events', [])) ? 'checked' : '' }}
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
                            <input type="checkbox" name="active" value="1" {{ old('active', true) ? 'checked' : '' }}
                                   class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700">Active - Enable this webhook endpoint</span>
                        </label>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.webhooks.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Create Endpoint
                    </button>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Quick Info</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>HMAC-SHA256 signed requests</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Automatic retries on failure</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Delivery logs available</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Auto-disabled after 10 failures</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Signing Secret</h3>
                    </div>
                    <div class="detail-card-body">
                        <p class="text-sm text-gray-600">
                            A signing secret will be generated and shown <strong>once</strong> after creation. Make sure to copy it.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
