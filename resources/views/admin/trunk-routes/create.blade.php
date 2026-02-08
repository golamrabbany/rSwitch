<x-admin-layout>
    <x-slot name="header">Create Routing Rule</x-slot>

    {{-- Page Header --}}
    <div class="page-header-row">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
            </div>
            <div>
                <h2 class="page-title">Create Routing Rule</h2>
                <p class="page-subtitle">Define a new trunk routing rule</p>
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

    <form method="POST" action="{{ route('admin.trunk-routes.store') }}" x-data="{
        timeBasedRouting: {{ old('time_start') ? 'true' : 'false' }},
        dayRestriction: {{ old('days_of_week') || old('days') ? 'true' : 'false' }}
    }">
        @csrf

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
                                        <option value="{{ $trunk->id }}" {{ old('trunk_id', $selectedTrunkId) == $trunk->id ? 'selected' : '' }}>
                                            {{ $trunk->name }} ({{ $trunk->provider }})
                                        </option>
                                    @endforeach
                                </select>
                                <p class="form-hint">Only outgoing/both trunks are shown.</p>
                                <x-input-error :messages="$errors->get('trunk_id')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="prefix" class="form-label">Destination Prefix</label>
                                <input type="text" id="prefix" name="prefix" value="{{ old('prefix') }}" required
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
                                <input type="number" id="priority" name="priority" value="{{ old('priority', '1') }}" required min="1" max="100" class="form-input">
                                <p class="form-hint">Lower = higher priority</p>
                                <x-input-error :messages="$errors->get('priority')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="weight" class="form-label">Weight</label>
                                <input type="number" id="weight" name="weight" value="{{ old('weight', '100') }}" required min="1" max="1000" class="form-input">
                                <p class="form-hint">Load balancing weight</p>
                                <x-input-error :messages="$errors->get('weight')" class="mt-2" />
                            </div>
                            <div class="form-group">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" required class="form-input">
                                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="disabled" {{ old('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
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
                                    <input type="time" id="time_start" name="time_start" value="{{ old('time_start') }}" class="form-input">
                                    <p class="form-hint">Inclusive (e.g. 06:00)</p>
                                    <x-input-error :messages="$errors->get('time_start')" class="mt-2" />
                                </div>
                                <div class="form-group">
                                    <label for="time_end" class="form-label">End Time</label>
                                    <input type="time" id="time_end" name="time_end" value="{{ old('time_end') }}" class="form-input">
                                    <p class="form-hint">Exclusive (e.g. 18:00)</p>
                                    <x-input-error :messages="$errors->get('time_end')" class="mt-2" />
                                </div>
                                <div class="form-group">
                                    <label for="timezone" class="form-label">Timezone</label>
                                    <select id="timezone" name="timezone" class="form-input">
                                        <option value="UTC" {{ old('timezone', 'UTC') === 'UTC' ? 'selected' : '' }}>UTC</option>
                                        <option value="Asia/Dhaka" {{ old('timezone') === 'Asia/Dhaka' ? 'selected' : '' }}>Asia/Dhaka (UTC+6)</option>
                                        <option value="Europe/London" {{ old('timezone') === 'Europe/London' ? 'selected' : '' }}>Europe/London</option>
                                        <option value="Europe/Berlin" {{ old('timezone') === 'Europe/Berlin' ? 'selected' : '' }}>Europe/Berlin</option>
                                        <option value="America/New_York" {{ old('timezone') === 'America/New_York' ? 'selected' : '' }}>America/New_York</option>
                                        <option value="America/Chicago" {{ old('timezone') === 'America/Chicago' ? 'selected' : '' }}>America/Chicago</option>
                                        <option value="America/Los_Angeles" {{ old('timezone') === 'America/Los_Angeles' ? 'selected' : '' }}>America/Los_Angeles</option>
                                        <option value="Asia/Kolkata" {{ old('timezone') === 'Asia/Kolkata' ? 'selected' : '' }}>Asia/Kolkata (UTC+5:30)</option>
                                        <option value="Asia/Singapore" {{ old('timezone') === 'Asia/Singapore' ? 'selected' : '' }}>Asia/Singapore (UTC+8)</option>
                                        <option value="Australia/Sydney" {{ old('timezone') === 'Australia/Sydney' ? 'selected' : '' }}>Australia/Sydney</option>
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
                                    @php $oldDays = old('days', []); @endphp
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Create Routing Rule
                    </button>
                </div>
            </div>

            {{-- Sidebar - Right Side --}}
            <div class="space-y-6">
                {{-- Quick Info --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Quick Info</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="flex items-center gap-3 p-3 bg-indigo-50 rounded-lg mb-4">
                            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-indigo-800">Longest Prefix Match</p>
                                <p class="text-xs text-indigo-600">More specific prefixes are matched first</p>
                            </div>
                        </div>

                        <div class="space-y-3 text-sm">
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Priority 1 = primary route</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Priority 2+ = failover routes</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Weight for load balancing</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Example Prefixes --}}
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h3 class="detail-card-title">Example Prefixes</h3>
                    </div>
                    <div class="detail-card-body">
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="font-mono text-gray-700">880</span>
                                <span class="text-xs text-gray-500">Bangladesh</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="font-mono text-gray-700">88017</span>
                                <span class="text-xs text-gray-500">Grameenphone</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="font-mono text-gray-700">44</span>
                                <span class="text-xs text-gray-500">United Kingdom</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                <span class="font-mono text-gray-700">1</span>
                                <span class="text-xs text-gray-500">USA/Canada</span>
                            </div>
                            <div class="flex justify-between items-center py-1">
                                <span class="font-mono text-gray-700">91</span>
                                <span class="text-xs text-gray-500">India</span>
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
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Use time windows for off-peak rate routes</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Create same prefix rules with different priorities for failover</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>Time windows can overlap with warnings</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Use Route Test Tool to verify configuration</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-admin-layout>
