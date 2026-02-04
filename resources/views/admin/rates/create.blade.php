<x-admin-layout>
    <x-slot name="header">Add Rate to: {{ $rateGroup->name }}</x-slot>

    <div class="max-w-2xl">
        <div class="bg-white shadow sm:rounded-lg p-6">
            <form method="POST" action="{{ route('admin.rate-groups.rates.store', $rateGroup) }}">
                @csrf

                <div class="space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="prefix" class="block text-sm font-medium text-gray-700">Prefix</label>
                            <input type="text" id="prefix" name="prefix" value="{{ old('prefix') }}" required
                                   placeholder="e.g. 1201"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono">
                            <p class="mt-1 text-xs text-gray-500">Numeric prefix for destination matching (1-20 digits).</p>
                            <x-input-error :messages="$errors->get('prefix')" class="mt-2" />
                        </div>
                        <div>
                            <label for="destination" class="block text-sm font-medium text-gray-700">Destination</label>
                            <input type="text" id="destination" name="destination" value="{{ old('destination') }}" required
                                   placeholder="e.g. United States - New Jersey"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <x-input-error :messages="$errors->get('destination')" class="mt-2" />
                        </div>
                    </div>

                    <hr class="border-gray-200">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="rate_per_minute" class="block text-sm font-medium text-gray-700">Rate per Minute ($)</label>
                            <input type="number" id="rate_per_minute" name="rate_per_minute" value="{{ old('rate_per_minute') }}" required
                                   step="0.000001" min="0"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <x-input-error :messages="$errors->get('rate_per_minute')" class="mt-2" />
                        </div>
                        <div>
                            <label for="connection_fee" class="block text-sm font-medium text-gray-700">Connection Fee ($)</label>
                            <input type="number" id="connection_fee" name="connection_fee" value="{{ old('connection_fee', '0') }}"
                                   step="0.000001" min="0"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <x-input-error :messages="$errors->get('connection_fee')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="min_duration" class="block text-sm font-medium text-gray-700">Minimum Duration (seconds)</label>
                            <input type="number" id="min_duration" name="min_duration" value="{{ old('min_duration', '0') }}"
                                   min="0"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <x-input-error :messages="$errors->get('min_duration')" class="mt-2" />
                        </div>
                        <div>
                            <label for="billing_increment" class="block text-sm font-medium text-gray-700">Billing Increment (seconds)</label>
                            <input type="number" id="billing_increment" name="billing_increment" value="{{ old('billing_increment', '6') }}"
                                   min="1"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <p class="mt-1 text-xs text-gray-500">Common: 1 (per-second), 6 (6/6), 60 (per-minute).</p>
                            <x-input-error :messages="$errors->get('billing_increment')" class="mt-2" />
                        </div>
                    </div>

                    <hr class="border-gray-200">

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label for="effective_date" class="block text-sm font-medium text-gray-700">Effective Date</label>
                            <input type="date" id="effective_date" name="effective_date" value="{{ old('effective_date', now()->format('Y-m-d')) }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <x-input-error :messages="$errors->get('effective_date')" class="mt-2" />
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700">End Date (optional)</label>
                            <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <p class="mt-1 text-xs text-gray-500">Leave empty for no expiry.</p>
                            <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
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
                </div>

                <div class="mt-6 flex items-center gap-3">
                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Create Rate
                    </button>
                    <a href="{{ route('admin.rate-groups.show', $rateGroup) }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
