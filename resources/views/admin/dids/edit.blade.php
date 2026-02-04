<x-admin-layout>
    <x-slot name="header">Edit DID: {{ $did->number }}</x-slot>

    <div class="max-w-2xl" x-data="{
        destinationType: '{{ old('destination_type', $did->destination_type) }}'
    }">
        <form method="POST" action="{{ route('admin.dids.update', $did) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="bg-white shadow sm:rounded-lg p-6 space-y-6">

                {{-- Section: DID Information --}}
                <h3 class="text-base font-semibold text-gray-900">DID Information</h3>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Number</label>
                        <p class="mt-1 text-sm font-mono text-gray-900 bg-gray-50 px-3 py-2 rounded-md border border-gray-200">{{ $did->number }}</p>
                        <input type="hidden" name="number" value="{{ $did->number }}">
                        <p class="mt-1 text-xs text-gray-500">Number cannot be changed after creation.</p>
                    </div>
                    <div>
                        <label for="provider" class="block text-sm font-medium text-gray-700">Provider</label>
                        <input type="text" id="provider" name="provider" value="{{ old('provider', $did->provider) }}" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <x-input-error :messages="$errors->get('provider')" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="trunk_id" class="block text-sm font-medium text-gray-700">Incoming Trunk</label>
                        <select id="trunk_id" name="trunk_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">Select a trunk...</option>
                            @foreach ($trunks as $trunk)
                                <option value="{{ $trunk->id }}" {{ old('trunk_id', $did->trunk_id) == $trunk->id ? 'selected' : '' }}>
                                    {{ $trunk->name }} ({{ $trunk->provider }}) — {{ $trunk->direction }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Only incoming/both trunks are shown.</p>
                        <x-input-error :messages="$errors->get('trunk_id')" class="mt-2" />
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select id="status" name="status" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="active" {{ old('status', $did->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="unassigned" {{ old('status', $did->status) === 'unassigned' ? 'selected' : '' }}>Unassigned</option>
                            <option value="disabled" {{ old('status', $did->status) === 'disabled' ? 'selected' : '' }}>Disabled</option>
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>
                </div>

                {{-- Section: Assignment --}}
                <hr class="border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Assignment</h3>

                <div>
                    <label for="assigned_to_user_id" class="block text-sm font-medium text-gray-700">Assigned User</label>
                    <select id="assigned_to_user_id" name="assigned_to_user_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Unassigned</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ old('assigned_to_user_id', $did->assigned_to_user_id) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }}) — {{ ucfirst($user->role) }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('assigned_to_user_id')" class="mt-2" />
                </div>

                {{-- Section: Destination Routing --}}
                <hr class="border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Destination Routing</h3>

                <div>
                    <label for="destination_type" class="block text-sm font-medium text-gray-700">Destination Type</label>
                    <select id="destination_type" name="destination_type" required
                            x-model="destinationType"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="sip_account">SIP Account</option>
                        <option value="external">External Number</option>
                        <option value="ring_group" disabled>Ring Group (Coming Soon)</option>
                    </select>
                    <x-input-error :messages="$errors->get('destination_type')" class="mt-2" />
                </div>

                <div x-show="destinationType === 'sip_account'" x-cloak>
                    <label for="destination_id" class="block text-sm font-medium text-gray-700">SIP Account</label>
                    <select id="destination_id" name="destination_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Select SIP account...</option>
                        @foreach ($sipAccounts as $sip)
                            <option value="{{ $sip->id }}" {{ old('destination_id', $did->destination_id) == $sip->id ? 'selected' : '' }}>
                                {{ $sip->username }} — {{ $sip->user->name ?? 'Unknown' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Incoming calls to this DID will ring this SIP account.</p>
                    <x-input-error :messages="$errors->get('destination_id')" class="mt-2" />
                </div>

                <div x-show="destinationType === 'external'" x-cloak>
                    <label for="destination_number" class="block text-sm font-medium text-gray-700">External Number</label>
                    <input type="text" id="destination_number" name="destination_number" value="{{ old('destination_number', $did->destination_number) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono"
                           placeholder="+1234567890">
                    <p class="mt-1 text-xs text-gray-500">Calls will be forwarded to this external number via an outgoing trunk.</p>
                    <x-input-error :messages="$errors->get('destination_number')" class="mt-2" />
                </div>

                {{-- Section: Billing --}}
                <hr class="border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Billing</h3>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="monthly_cost" class="block text-sm font-medium text-gray-700">Monthly Cost</label>
                        <input type="number" id="monthly_cost" name="monthly_cost" value="{{ old('monthly_cost', $did->monthly_cost) }}" required
                               step="0.0001" min="0" max="9999.9999"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">Your cost from the provider.</p>
                        <x-input-error :messages="$errors->get('monthly_cost')" class="mt-2" />
                    </div>
                    <div>
                        <label for="monthly_price" class="block text-sm font-medium text-gray-700">Monthly Price</label>
                        <input type="number" id="monthly_price" name="monthly_price" value="{{ old('monthly_price', $did->monthly_price) }}" required
                               step="0.0001" min="0" max="9999.9999"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">Price charged to the client.</p>
                        <x-input-error :messages="$errors->get('monthly_price')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-x-3">
                <a href="{{ route('admin.dids.show', $did) }}" class="text-sm font-semibold text-gray-900">Cancel</a>
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Update DID
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
