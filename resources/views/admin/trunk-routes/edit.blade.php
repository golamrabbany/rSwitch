<x-admin-layout>
    <x-slot name="header">Edit Routing Rule</x-slot>

    @php
        $existingDays = $trunkRoute->days_of_week ? explode(',', $trunkRoute->days_of_week) : [];
    @endphp

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Edit Routing Rule</h2>
                <p class="page-subtitle">Prefix: <span class="font-mono">{{ $trunkRoute->prefix }}</span></p>
            </div>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.trunk-routes.index') }}" class="btn-action-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to List
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.trunk-routes.update', $trunkRoute) }}" x-data="{
        timeBasedRouting: {{ old('time_start', $trunkRoute->time_start) ? 'true' : 'false' }},
        dayRestriction: {{ old('days_of_week', $trunkRoute->days_of_week) || old('days') ? 'true' : 'false' }}
    }">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Form - Left Side --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Route Target --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Route Target</h3>
                        <p class="form-card-subtitle">Select trunk and destination prefix</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="trunk_id" class="form-label">Trunk</label>
                                <select id="trunk_id" name="trunk_id" required class="form-input">
                                    <option value="">Select a trunk...</option>
                                    @foreach ($trunks as $trunk)
                                        <option value="{{ $trunk->id }}" {{ old('trunk_id', $trunkRoute->trunk_id) == $trunk->id ? 'selected' : '' }}>
                                            {{ $trunk->name }} ({{ $trunk->provider }})
                                        </option>
                                    @endforeach
                                </select>
                                <p class="form-hint">Only outgoing/both trunks are shown.</p>
                                <x-input-error :messages="$errors->get('trunk_id')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="prefix" class="form-label">Destination Prefix</label>
                                <input type="text" id="prefix" name="prefix" value="{{ old('prefix', $trunkRoute->prefix) }}" required
                                       class="form-input font-mono" placeholder="e.g. 88017, 44, 1">
                                <p class="form-hint">Numeric digits only. Longer prefix = more specific match.</p>
                                <x-input-error :messages="$errors->get('prefix')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Priority & Load Balancing --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Priority & Load Balancing</h3>
                        <p class="form-card-subtitle">Configure routing priority and weight</p>
                    </div>
                    <div class="form-card-body">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="form-group">
                                <label for="priority" class="form-label">Priority</label>
                                <input type="number" id="priority" name="priority" value="{{ old('priority', $trunkRoute->priority) }}" required min="1" max="100" class="form-input">
                                <p class="form-hint">Lower = higher priority</p>
                                <x-input-error :messages="$errors->get('priority')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="weight" class="form-label">Weight</label>
                                <input type="number" id="weight" name="weight" value="{{ old('weight', $trunkRoute->weight) }}" required min="1" max="1000" class="form-input">
                                <p class="form-hint">Load balancing weight</p>
                                <x-input-error :messages="$errors->get('weight')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" required class="form-input">
                                    <option value="active" {{ old('status', $trunkRoute->status) === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="disabled" {{ old('status', $trunkRoute->status) === 'disabled' ? 'selected' : '' }}>Disabled</option>
                                </select>
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Time-Based Routing --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">Time-Based Routing</h3>
                        <p class="form-card-subtitle">Optional time window restriction</p>
                    </div>
                    <div class="form-card-body">
                        <div class="form-group">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" x-model="timeBasedRouting" class="form-checkbox">
                                <span class="text-sm text-gray-700">Enable time window restriction</span>
                            </label>
                            <p class="form-hint">Leave unchecked for routes that are always active.</p>
                        </div>

                        <div x-show="timeBasedRouting" x-cloak class="space-y-4 mt-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="form-group">
                                    <label for="time_start" class="form-label">Start Time</label>
                                    <input type="time" id="time_start" name="time_start" value="{{ old('time_start', $trunkRoute->time_start ? substr($trunkRoute->time_start, 0, 5) : '') }}" class="form-input">
                                    <p class="form-hint">Inclusive (e.g. 06:00)</p>
                                    <x-input-error :messages="$errors->get('time_start')" class="mt-2" />
                                </div>
                                <div class="form-group">
                                    <label for="time_end" class="form-label">End Time</label>
                                    <input type="time" id="time_end" name="time_end" value="{{ old('time_end', $trunkRoute->time_end ? substr($trunkRoute->time_end, 0, 5) : '') }}" class="form-input">
                                    <p class="form-hint">Exclusive (e.g. 18:00)</p>
                                    <x-input-error :messages="$errors->get('time_end')" class="mt-2" />
                                </div>
                                <div class="form-group">
                                    <label for="timezone" class="form-label">Timezone</label>
                                    <select id="timezone" name="timezone" class="form-input">
                                        <option value="UTC" {{ old('timezone', $trunkRoute->timezone) === 'UTC' ? 'selected' : '' }}>UTC</option>
                                        <option value="Asia/Dhaka" {{ old('timezone', $trunkRoute->timezone) === 'Asia/Dhaka' ? 'selected' : '' }}>Asia/Dhaka (UTC+6)</option>
                                        <option value="Europe/London" {{ old('timezone', $trunkRoute->timezone) === 'Europe/London' ? 'selected' : '' }}>Europe/London</option>
                                        <option value="Europe/Berlin" {{ old('timezone', $trunkRoute->timezone) === 'Europe/Berlin' ? 'selected' : '' }}>Europe/Berlin</option>
                                        <option value="America/New_York" {{ old('timezone', $trunkRoute->timezone) === 'America/New_York' ? 'selected' : '' }}>America/New_York</option>
                                        <option value="America/Chicago" {{ old('timezone', $trunkRoute->timezone) === 'America/Chicago' ? 'selected' : '' }}>America/Chicago</option>
                                        <option value="America/Los_Angeles" {{ old('timezone', $trunkRoute->timezone) === 'America/Los_Angeles' ? 'selected' : '' }}>America/Los_Angeles</option>
                                        <option value="Asia/Kolkata" {{ old('timezone', $trunkRoute->timezone) === 'Asia/Kolkata' ? 'selected' : '' }}>Asia/Kolkata (UTC+5:30)</option>
                                        <option value="Asia/Singapore" {{ old('timezone', $trunkRoute->timezone) === 'Asia/Singapore' ? 'selected' : '' }}>Asia/Singapore (UTC+8)</option>
                                        <option value="Australia/Sydney" {{ old('timezone', $trunkRoute->timezone) === 'Australia/Sydney' ? 'selected' : '' }}>Australia/Sydney</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('timezone')" class="mt-2" />
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" x-model="dayRestriction" class="form-checkbox">
                                    <span class="text-sm text-gray-700">Restrict to specific days</span>
                                </label>
                            </div>

                            <div x-show="dayRestriction" x-cloak class="form-group">
                                <label class="form-label">Days of Week</label>
                                <div class="flex flex-wrap gap-4 mt-2">
                                    @php $oldDays = old('days', $existingDays); @endphp
                                    @foreach(['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun'] as $val => $label)
                                        <label class="flex items-center gap-1.5">
                                            <input type="checkbox" name="days[]" value="{{ $val }}" {{ in_array($val, $oldDays) ? 'checked' : '' }} class="form-checkbox">
                                            <span class="text-sm text-gray-700">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <x-input-error :messages="$errors->get('days_of_week')" class="mt-2" />
                            </div>
                        </div>

                        {{-- Hidden defaults when time-based routing is off --}}
                        <template x-if="!timeBasedRouting">
                            <div>
                                <input type="hidden" name="time_start" value="">
                                <input type="hidden" name="time_end" value="">
                                <input type="hidden" name="timezone" value="UTC">
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.trunk-routes.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Changes
                    </button>
                </div>
            </div>

            {{-- Sidebar - Right Side --}}
            <div class="space-y-6">
                {{-- Route Info --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Route Info</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-mono font-medium text-gray-900">{{ $trunkRoute->prefix }}</p>
                                <span class="badge {{ $trunkRoute->status === 'active' ? 'badge-success' : 'badge-danger' }}">
                                    {{ ucfirst($trunkRoute->status) }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-3 text-sm border-t border-gray-100 pt-4">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Route ID</span>
                                <span class="font-mono text-gray-900">#{{ $trunkRoute->id }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Trunk</span>
                                <a href="{{ route('admin.trunks.show', $trunkRoute->trunk) }}" class="text-indigo-600 hover:text-indigo-800">
                                    {{ $trunkRoute->trunk->name }}
                                </a>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Created</span>
                                <span class="text-gray-900">{{ $trunkRoute->created_at->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Priority Guide --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Priority Guide</h3>
                    </div>
                    <div class="detail-card-body space-y-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-success">Priority 1</span>
                            </div>
                            <p class="text-xs text-gray-500">Primary route - tried first</p>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-warning">Priority 2+</span>
                            </div>
                            <p class="text-xs text-gray-500">Failover routes - used when primary fails</p>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-info">Same Priority</span>
                            </div>
                            <p class="text-xs text-gray-500">Load balanced by weight</p>
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
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Changes take effect immediately</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Disable to pause without deleting</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Active calls may use old routing</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Use Route Test Tool to verify</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
