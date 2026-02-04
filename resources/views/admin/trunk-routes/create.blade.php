<x-admin-layout>
    <x-slot name="header">Create Routing Rule</x-slot>

    <div class="max-w-3xl" x-data="{
        timeBasedRouting: {{ old('time_start') ? 'true' : 'false' }},
        dayRestriction: {{ old('days_of_week') || old('days') ? 'true' : 'false' }}
    }">
        <form method="POST" action="{{ route('admin.trunk-routes.store') }}" class="space-y-6">
            @csrf

            <div class="bg-white shadow sm:rounded-lg p-6 space-y-6">

                {{-- Section: Route Target --}}
                <h3 class="text-base font-semibold text-gray-900">Route Target</h3>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="trunk_id" class="block text-sm font-medium text-gray-700">Trunk</label>
                        <select id="trunk_id" name="trunk_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">Select a trunk...</option>
                            @foreach ($trunks as $trunk)
                                <option value="{{ $trunk->id }}" {{ old('trunk_id', $selectedTrunkId) == $trunk->id ? 'selected' : '' }}>
                                    {{ $trunk->name }} ({{ $trunk->provider }})
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Only outgoing/both trunks are shown.</p>
                        <x-input-error :messages="$errors->get('trunk_id')" class="mt-2" />
                    </div>
                    <div>
                        <label for="prefix" class="block text-sm font-medium text-gray-700">Destination Prefix</label>
                        <input type="text" id="prefix" name="prefix" value="{{ old('prefix') }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono"
                               placeholder="e.g. 88017, 44, 1">
                        <p class="mt-1 text-xs text-gray-500">Numeric digits only. Longer prefix = more specific match.</p>
                        <x-input-error :messages="$errors->get('prefix')" class="mt-2" />
                    </div>
                </div>

                {{-- Section: Priority & Weight --}}
                <hr class="border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Priority & Load Balancing</h3>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700">Priority</label>
                        <input type="number" id="priority" name="priority" value="{{ old('priority', '1') }}" required min="1" max="100"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">Lower = higher priority. Use 1 for primary, 2+ for failover.</p>
                        <x-input-error :messages="$errors->get('priority')" class="mt-2" />
                    </div>
                    <div>
                        <label for="weight" class="block text-sm font-medium text-gray-700">Weight</label>
                        <input type="number" id="weight" name="weight" value="{{ old('weight', '100') }}" required min="1" max="1000"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">Load balancing among same-priority routes. Higher = more traffic.</p>
                        <x-input-error :messages="$errors->get('weight')" class="mt-2" />
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select id="status" name="status" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="disabled" {{ old('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>
                </div>

                {{-- Section: Time-Based Routing --}}
                <hr class="border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Time-Based Routing</h3>

                <div class="flex items-center">
                    <label class="flex items-center">
                        <input type="checkbox" x-model="timeBasedRouting"
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-700">Enable time window restriction</span>
                    </label>
                </div>
                <p class="text-xs text-gray-500 -mt-4">Leave unchecked for routes that are always active (or to use as a failover route).</p>

                <div x-show="timeBasedRouting" x-cloak class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                        <div>
                            <label for="time_start" class="block text-sm font-medium text-gray-700">Start Time</label>
                            <input type="time" id="time_start" name="time_start" value="{{ old('time_start') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <p class="mt-1 text-xs text-gray-500">Inclusive (e.g. 06:00)</p>
                            <x-input-error :messages="$errors->get('time_start')" class="mt-2" />
                        </div>
                        <div>
                            <label for="time_end" class="block text-sm font-medium text-gray-700">End Time</label>
                            <input type="time" id="time_end" name="time_end" value="{{ old('time_end') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <p class="mt-1 text-xs text-gray-500">Exclusive (e.g. 18:00)</p>
                            <x-input-error :messages="$errors->get('time_end')" class="mt-2" />
                        </div>
                        <div>
                            <label for="timezone" class="block text-sm font-medium text-gray-700">Timezone</label>
                            <select id="timezone" name="timezone"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
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

                    {{-- Day of week restriction --}}
                    <div class="flex items-center">
                        <label class="flex items-center">
                            <input type="checkbox" x-model="dayRestriction"
                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700">Restrict to specific days</span>
                        </label>
                    </div>

                    <div x-show="dayRestriction" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Days of Week</label>
                        <div class="flex flex-wrap gap-4">
                            @php $oldDays = old('days', []); @endphp
                            @foreach(['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun'] as $val => $label)
                                <label class="flex items-center">
                                    <input type="checkbox" name="days[]" value="{{ $val }}" {{ in_array($val, $oldDays) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="ml-1.5 text-sm text-gray-700">{{ $label }}</span>
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

            <div class="flex items-center justify-end gap-x-3">
                <a href="{{ route('admin.trunk-routes.index') }}" class="text-sm font-semibold text-gray-900">Cancel</a>
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Create Routing Rule
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
